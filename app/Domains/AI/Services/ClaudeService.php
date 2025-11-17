<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\DTOs\ClaudeRequest;
use App\Domains\AI\DTOs\ClaudeResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service principal pour communiquer avec l'API Claude d'Anthropic.
 */
class ClaudeService
{
    private string $apiKey;
    private string $apiUrl;
    private string $apiVersion;
    private int $timeout;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct()
    {
        $this->apiKey = config('claude.api.key');
        $this->apiUrl = config('claude.api.url');
        $this->apiVersion = config('claude.api.version');
        $this->timeout = config('claude.api.timeout', 60);
        $this->maxRetries = config('claude.api.max_retries', 3);
        $this->retryDelay = config('claude.api.retry_delay', 1000);

        if (empty($this->apiKey)) {
            throw new \RuntimeException('ANTHROPIC_API_KEY n\'est pas configurée.');
        }
    }

    /**
     * Envoyer une requête à Claude.
     */
    public function sendRequest(ClaudeRequest $request): ClaudeResponse
    {
        // Vérifier le cache si activé
        if (config('claude.cache.enabled')) {
            $cacheKey = $this->getCacheKey($request);
            $cached = Cache::get($cacheKey);

            if ($cached) {
                Log::channel(config('claude.logging.channels.default'))
                    ->debug('Claude response from cache', ['cache_key' => $cacheKey]);

                return unserialize($cached);
            }
        }

        // Envoyer la requête avec retry logic
        $response = $this->sendWithRetry($request);

        // Mettre en cache si applicable
        if (config('claude.cache.enabled') && !$response->isError()) {
            $ttl = $this->getCacheTtl($request);
            Cache::put($cacheKey, serialize($response), $ttl);
        }

        // Logger la requête si activé
        if (config('claude.logging.log_requests')) {
            $this->logRequest($request, $response);
        }

        return $response;
    }

    /**
     * Envoyer la requête avec logique de retry.
     */
    private function sendWithRetry(ClaudeRequest $request): ClaudeResponse
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            try {
                return $this->doSendRequest($request);
            } catch (RequestException | ConnectionException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->maxRetries) {
                    // Attendre avant de réessayer (exponential backoff)
                    usleep($this->retryDelay * pow(2, $attempts - 1) * 1000);

                    Log::channel(config('claude.logging.channels.error'))
                        ->warning('Claude API retry', [
                            'attempt' => $attempts,
                            'error' => $e->getMessage(),
                        ]);
                }
            }
        }

        // Toutes les tentatives ont échoué
        Log::channel(config('claude.logging.channels.error'))
            ->error('Claude API failed after retries', [
                'attempts' => $attempts,
                'error' => $lastException?->getMessage(),
            ]);

        return ClaudeResponse::error(
            'Erreur de communication avec Claude API: ' . $lastException?->getMessage(),
            ['attempts' => $attempts]
        );
    }

    /**
     * Effectuer l'appel API réel.
     */
    private function doSendRequest(ClaudeRequest $request): ClaudeResponse
    {
        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => $this->apiVersion,
            'content-type' => 'application/json',
        ])
            ->timeout($this->timeout)
            ->post("{$this->apiUrl}/messages", $request->toArray());

        if (!$response->successful()) {
            $error = $response->json('error.message', 'Erreur inconnue');
            $type = $response->json('error.type', 'unknown');

            throw new RequestException($response, "Claude API Error ({$type}): {$error}");
        }

        return ClaudeResponse::fromApiResponse($response->json());
    }

    /**
     * Générer une clé de cache unique pour la requête.
     */
    private function getCacheKey(ClaudeRequest $request): string
    {
        $data = [
            'prompt' => md5($request->prompt),
            'model' => $request->model->value,
            'system' => md5($request->systemPrompt ?? ''),
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
        ];

        return config('claude.cache.prefix') . ':' . md5(json_encode($data));
    }

    /**
     * Obtenir le TTL du cache en fonction du type de requête.
     */
    private function getCacheTtl(ClaudeRequest $request): int
    {
        // Déterminer le type basé sur les métadonnées
        $type = $request->metadata['type'] ?? 'default';

        return config("claude.cache.ttl.{$type}", 3600);
    }

    /**
     * Logger la requête et la réponse.
     */
    private function logRequest(ClaudeRequest $request, ClaudeResponse $response): void
    {
        $logData = [
            'model' => $request->model->value,
            'prompt_length' => strlen($request->prompt),
            'response_length' => strlen($response->content),
            'tokens' => [
                'input' => $response->inputTokens,
                'output' => $response->outputTokens,
                'total' => $response->getTotalTokens(),
            ],
            'cost' => $response->getTotalCost(),
            'metadata' => $request->metadata,
        ];

        if (config('claude.logging.anonymize.user_data')) {
            unset($logData['metadata']['user_id']);
        }

        Log::channel(config('claude.logging.channels.default'))
            ->info('Claude API request', $logData);

        if (config('claude.logging.log_usage')) {
            $this->trackUsage($response);
        }
    }

    /**
     * Tracker l'utilisation de l'API.
     */
    private function trackUsage(ClaudeResponse $response): void
    {
        $date = now()->format('Y-m-d');
        $key = "claude:usage:{$date}";

        Cache::increment("{$key}:requests", 1);
        Cache::increment("{$key}:input_tokens", $response->inputTokens);
        Cache::increment("{$key}:output_tokens", $response->outputTokens);

        // Incrémenter le coût (multiplié par 10000 pour éviter les décimales)
        Cache::increment("{$key}:cost", (int)($response->getTotalCost() * 10000));
    }

    /**
     * Obtenir les statistiques d'utilisation pour une date donnée.
     */
    public function getUsageStats(?string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');
        $key = "claude:usage:{$date}";

        return [
            'date' => $date,
            'requests' => Cache::get("{$key}:requests", 0),
            'tokens' => [
                'input' => Cache::get("{$key}:input_tokens", 0),
                'output' => Cache::get("{$key}:output_tokens", 0),
            ],
            'cost' => Cache::get("{$key}:cost", 0) / 10000,
        ];
    }

    /**
     * Vérifier si les limites quotidiennes sont atteintes.
     */
    public function isRateLimited(): bool
    {
        $stats = $this->getUsageStats();
        $dailyLimit = config('claude.limits.global.daily', 1000);

        return $stats['requests'] >= $dailyLimit;
    }

    /**
     * Effacer le cache pour un type spécifique.
     */
    public function clearCache(?string $type = null): bool
    {
        if ($type) {
            return Cache::flush(); // TODO: Améliorer pour ne flush que le prefix
        }

        return Cache::tags([config('claude.cache.prefix')])->flush();
    }
}
