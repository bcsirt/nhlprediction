<?php

namespace App\Domains\DataIngestion\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service pour interagir avec l'API NHL officielle.
 *
 * @see https://api-web.nhle.com/v1
 */
class NHLApiService
{
    private string $baseUrl;
    private int $timeout;
    private int $retryTimes;
    private int $retrySleep;

    public function __construct()
    {
        $this->baseUrl = config('nhl.api.base_url');
        $this->timeout = config('nhl.api.timeout', 30);
        $this->retryTimes = config('nhl.api.retry_times', 3);
        $this->retrySleep = config('nhl.api.retry_sleep', 1000);
    }

    /**
     * Récupérer toutes les équipes NHL.
     *
     * @return array
     */
    public function getTeams(): array
    {
        return $this->cachedRequest(
            'teams',
            fn() => $this->get('/standings/now')['standings'] ?? []
        );
    }

    /**
     * Récupérer les détails d'une équipe.
     *
     * @param string $teamAbbreviation Ex: "MTL", "TOR"
     * @return array|null
     */
    public function getTeam(string $teamAbbreviation): ?array
    {
        return $this->cachedRequest(
            "team:{$teamAbbreviation}",
            fn() => $this->get("/club-stats/{$teamAbbreviation}/now")
        );
    }

    /**
     * Récupérer les matchs pour une date donnée.
     *
     * @param string $date Format: YYYY-MM-DD
     * @return array
     */
    public function getGamesForDate(string $date): array
    {
        $response = $this->get("/score/{$date}");

        return $response['games'] ?? [];
    }

    /**
     * Récupérer les matchs d'aujourd'hui.
     *
     * @return array
     */
    public function getTodayGames(): array
    {
        return $this->getGamesForDate(now()->format('Y-m-d'));
    }

    /**
     * Récupérer les détails d'un match.
     *
     * @param int $gameId
     * @return array|null
     */
    public function getGame(int $gameId): ?array
    {
        return $this->cachedRequest(
            "game:{$gameId}",
            fn() => $this->get("/gamecenter/{$gameId}/landing"),
            config('nhl.cache.live_game')
        );
    }

    /**
     * Récupérer le boxscore d'un match.
     *
     * @param int $gameId
     * @return array|null
     */
    public function getGameBoxscore(int $gameId): ?array
    {
        return $this->cachedRequest(
            "boxscore:{$gameId}",
            fn() => $this->get("/gamecenter/{$gameId}/boxscore"),
            config('nhl.cache.live_game')
        );
    }

    /**
     * Récupérer le play-by-play d'un match.
     *
     * @param int $gameId
     * @return array|null
     */
    public function getGamePlayByPlay(int $gameId): ?array
    {
        return $this->cachedRequest(
            "playbyplay:{$gameId}",
            fn() => $this->get("/gamecenter/{$gameId}/play-by-play"),
            config('nhl.cache.live_game')
        );
    }

    /**
     * Récupérer les statistiques d'une équipe pour une saison.
     *
     * @param string $teamAbbreviation
     * @param string $season Format: 20232024
     * @return array|null
     */
    public function getTeamStats(string $teamAbbreviation, string $season): ?array
    {
        return $this->cachedRequest(
            "team_stats:{$teamAbbreviation}:{$season}",
            fn() => $this->get("/club-stats/{$teamAbbreviation}/{$season}/2"),
            config('nhl.cache.team_stats')
        );
    }

    /**
     * Récupérer le roster d'une équipe.
     *
     * @param string $teamAbbreviation
     * @param string $season Format: 20232024
     * @return array
     */
    public function getTeamRoster(string $teamAbbreviation, string $season): array
    {
        $response = $this->get("/roster/{$teamAbbreviation}/{$season}");

        return array_merge(
            $response['forwards'] ?? [],
            $response['defensemen'] ?? [],
            $response['goalies'] ?? []
        );
    }

    /**
     * Récupérer les statistiques d'un joueur.
     *
     * @param int $playerId
     * @return array|null
     */
    public function getPlayerStats(int $playerId): ?array
    {
        return $this->cachedRequest(
            "player_stats:{$playerId}",
            fn() => $this->get("/player/{$playerId}/landing"),
            config('nhl.cache.player_stats')
        );
    }

    /**
     * Récupérer le calendrier d'une équipe.
     *
     * @param string $teamAbbreviation
     * @param string $season Format: 20232024
     * @return array
     */
    public function getTeamSchedule(string $teamAbbreviation, string $season): array
    {
        $response = $this->get("/club-schedule/{$teamAbbreviation}/{$season}");

        return $response['games'] ?? [];
    }

    /**
     * Récupérer le classement actuel.
     *
     * @return array
     */
    public function getStandings(): array
    {
        return $this->cachedRequest(
            'standings',
            fn() => $this->get('/standings/now'),
            config('nhl.cache.games_schedule')
        );
    }

    /**
     * Effectuer une requête GET à l'API NHL.
     *
     * @param string $endpoint
     * @param array $params
     * @return array
     * @throws RequestException
     */
    private function get(string $endpoint, array $params = []): array
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->retryTimes) {
            try {
                $response = Http::timeout($this->timeout)
                    ->get($this->baseUrl . $endpoint, $params);

                if ($response->successful()) {
                    return $response->json() ?? [];
                }

                throw new RequestException($response);
            } catch (ConnectionException | RequestException $e) {
                $lastException = $e;
                $attempts++;

                if ($attempts < $this->retryTimes) {
                    usleep($this->retrySleep * 1000);

                    Log::warning('NHL API retry', [
                        'endpoint' => $endpoint,
                        'attempt' => $attempts,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        Log::error('NHL API failed after retries', [
            'endpoint' => $endpoint,
            'attempts' => $attempts,
            'error' => $lastException?->getMessage(),
        ]);

        return [];
    }

    /**
     * Effectuer une requête avec cache.
     *
     * @param string $cacheKey
     * @param callable $callback
     * @param int|null $ttl
     * @return mixed
     */
    private function cachedRequest(string $cacheKey, callable $callback, ?int $ttl = null)
    {
        $ttl = $ttl ?? config('nhl.cache.teams', 3600);
        $key = "nhl:api:{$cacheKey}";

        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Vider le cache pour une clé donnée.
     *
     * @param string $cacheKey
     * @return bool
     */
    public function clearCache(string $cacheKey): bool
    {
        return Cache::forget("nhl:api:{$cacheKey}");
    }

    /**
     * Vider tout le cache NHL.
     *
     * @return bool
     */
    public function clearAllCache(): bool
    {
        // TODO: Implémenter avec tags ou prefix
        return Cache::flush();
    }
}
