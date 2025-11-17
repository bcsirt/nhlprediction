<?php

namespace App\Domains\AI\DTOs;

use App\Domains\AI\Enums\ClaudeModel;

/**
 * DTO pour les réponses de l'API Claude.
 */
class ClaudeResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly ClaudeModel $model,
        public readonly string $role,
        public readonly string $stopReason,
        public readonly int $inputTokens,
        public readonly int $outputTokens,
        public readonly array $metadata = [],
        public readonly ?string $error = null,
    ) {}

    /**
     * Créer depuis la réponse brute de l'API.
     */
    public static function fromApiResponse(array $response): self
    {
        // Extraire le contenu textuel
        $content = '';
        if (isset($response['content']) && is_array($response['content'])) {
            foreach ($response['content'] as $block) {
                if ($block['type'] === 'text') {
                    $content .= $block['text'];
                }
            }
        }

        return new self(
            id: $response['id'] ?? '',
            content: $content,
            model: ClaudeModel::from($response['model'] ?? ClaudeModel::HAIKU->value),
            role: $response['role'] ?? 'assistant',
            stopReason: $response['stop_reason'] ?? 'end_turn',
            inputTokens: $response['usage']['input_tokens'] ?? 0,
            outputTokens: $response['usage']['output_tokens'] ?? 0,
            metadata: $response['metadata'] ?? [],
        );
    }

    /**
     * Créer une réponse d'erreur.
     */
    public static function error(string $error, array $metadata = []): self
    {
        return new self(
            id: '',
            content: '',
            model: ClaudeModel::HAIKU,
            role: 'assistant',
            stopReason: 'error',
            inputTokens: 0,
            outputTokens: 0,
            metadata: $metadata,
            error: $error,
        );
    }

    /**
     * Vérifier si la réponse est en erreur.
     */
    public function isError(): bool
    {
        return $this->error !== null;
    }

    /**
     * Obtenir le coût total de la requête.
     */
    public function getTotalCost(): float
    {
        $inputCost = ($this->inputTokens / 1_000_000) * $this->model->inputCostPer1M();
        $outputCost = ($this->outputTokens / 1_000_000) * $this->model->outputCostPer1M();

        return $inputCost + $outputCost;
    }

    /**
     * Obtenir le nombre total de tokens.
     */
    public function getTotalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * Convertir en tableau.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'model' => $this->model->value,
            'role' => $this->role,
            'stop_reason' => $this->stopReason,
            'usage' => [
                'input_tokens' => $this->inputTokens,
                'output_tokens' => $this->outputTokens,
                'total_tokens' => $this->getTotalTokens(),
            ],
            'cost' => [
                'total' => $this->getTotalCost(),
                'currency' => 'USD',
            ],
            'metadata' => $this->metadata,
            'error' => $this->error,
        ];
    }
}
