<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Services;

use App\Domains\DataIngestion\Services\NHLApiService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests pour NHLApiService
 */
class NHLApiServiceTest extends TestCase
{
    private NHLApiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        $this->service = new NHLApiService();
    }

    /**
     * @test
     */
    public function it_can_fetch_todays_games(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023020001,
                                'homeTeam' => ['id' => 8, 'abbrev' => 'MTL'],
                                'awayTeam' => ['id' => 10, 'abbrev' => 'TOR'],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $games = $this->service->getTodayGames();

        $this->assertIsArray($games);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'schedule');
        });
    }

    /**
     * @test
     */
    public function it_can_fetch_games_for_specific_date(): void
    {
        $date = '2024-01-15';

        Http::fake([
            "*/schedule/{$date}" => Http::response([
                'gameWeek' => [
                    [
                        'date' => $date,
                        'games' => [
                            ['id' => 2023020100],
                            ['id' => 2023020101],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $games = $this->service->getGamesForDate($date);

        $this->assertIsArray($games);
        Http::assertSent(function ($request) use ($date) {
            return str_contains($request->url(), "schedule/{$date}");
        });
    }

    /**
     * @test
     */
    public function it_can_fetch_single_game_details(): void
    {
        $gameId = 2023020001;

        Http::fake([
            "*/gamecenter/{$gameId}/landing" => Http::response([
                'id' => $gameId,
                'season' => 20232024,
                'gameType' => 2,
                'gameDate' => '2024-01-15',
                'homeTeam' => [
                    'id' => 8,
                    'name' => ['default' => 'Canadiens de Montréal'],
                    'score' => 3,
                ],
                'awayTeam' => [
                    'id' => 10,
                    'name' => ['default' => 'Toronto Maple Leafs'],
                    'score' => 2,
                ],
            ], 200),
        ]);

        $game = $this->service->getGame($gameId);

        $this->assertIsArray($game);
        $this->assertEquals($gameId, $game['id']);
        $this->assertEquals(8, $game['homeTeam']['id']);

        Http::assertSent(function ($request) use ($gameId) {
            return str_contains($request->url(), "gamecenter/{$gameId}");
        });
    }

    /**
     * @test
     */
    public function it_can_fetch_all_teams(): void
    {
        Http::fake([
            '*/teams' => Http::response([
                'data' => [
                    [
                        'id' => 8,
                        'fullName' => 'Montréal Canadiens',
                        'triCode' => 'MTL',
                    ],
                    [
                        'id' => 10,
                        'fullName' => 'Toronto Maple Leafs',
                        'triCode' => 'TOR',
                    ],
                ],
            ], 200),
        ]);

        $teams = $this->service->getTeams();

        $this->assertIsArray($teams);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/teams');
        });
    }

    /**
     * @test
     */
    public function it_can_fetch_team_roster(): void
    {
        $teamId = 8;
        $season = '20232024';

        Http::fake([
            "*/roster/{$teamId}/{$season}" => Http::response([
                'forwards' => [
                    [
                        'id' => 8471214,
                        'firstName' => ['default' => 'Nick'],
                        'lastName' => ['default' => 'Suzuki'],
                        'sweaterNumber' => 14,
                    ],
                ],
                'defensemen' => [],
                'goalies' => [],
            ], 200),
        ]);

        $roster = $this->service->getTeamRoster($teamId, $season);

        $this->assertIsArray($roster);
        Http::assertSent(function ($request) use ($teamId, $season) {
            return str_contains($request->url(), "roster/{$teamId}/{$season}");
        });
    }

    /**
     * @test
     */
    public function it_can_fetch_standings(): void
    {
        Http::fake([
            '*/standings/now' => Http::response([
                'standings' => [
                    [
                        'teamAbbrev' => ['default' => 'MTL'],
                        'wins' => 20,
                        'losses' => 15,
                        'otLosses' => 5,
                        'points' => 45,
                    ],
                ],
            ], 200),
        ]);

        $standings = $this->service->getStandings();

        $this->assertIsArray($standings);
        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'standings');
        });
    }

    /**
     * @test
     */
    public function it_caches_api_responses(): void
    {
        $gameId = 2023020001;
        $cacheKey = "nhl_api:game:{$gameId}";

        Http::fake([
            "*/gamecenter/{$gameId}/landing" => Http::response(['id' => $gameId], 200),
        ]);

        // First call - should hit API
        $this->service->getGame($gameId);
        Http::assertSentCount(1);

        // Second call - should use cache
        $this->service->getGame($gameId);
        Http::assertSentCount(1); // Still 1, not 2

        // Verify cache was used
        $this->assertTrue(Cache::has($cacheKey));
    }

    /**
     * @test
     */
    public function it_retries_on_api_failure(): void
    {
        Http::fake([
            '*/teams' => Http::sequence()
                ->push(null, 500) // First attempt fails
                ->push(null, 500) // Second attempt fails
                ->push(['data' => []], 200), // Third attempt succeeds
        ]);

        $teams = $this->service->getTeams();

        $this->assertIsArray($teams);
        Http::assertSentCount(3);
    }

    /**
     * @test
     */
    public function it_returns_empty_array_after_max_retries(): void
    {
        Http::fake([
            '*/teams' => Http::response(null, 500),
        ]);

        $teams = $this->service->getTeams();

        $this->assertIsArray($teams);
        $this->assertEmpty($teams);
        Http::assertSentCount(3); // Default retry count
    }

    /**
     * @test
     */
    public function it_handles_network_exceptions_gracefully(): void
    {
        Http::fake(function () {
            throw new \Exception('Network error');
        });

        $teams = $this->service->getTeams();

        $this->assertIsArray($teams);
        $this->assertEmpty($teams);
    }

    /**
     * @test
     */
    public function it_can_fetch_player_stats(): void
    {
        $playerId = 8471214;
        $season = '20232024';

        Http::fake([
            "*/player/{$playerId}/landing" => Http::response([
                'playerId' => $playerId,
                'seasonTotals' => [
                    [
                        'season' => 20232024,
                        'gameTypeId' => 2,
                        'gamesPlayed' => 50,
                        'goals' => 20,
                        'assists' => 30,
                    ],
                ],
            ], 200),
        ]);

        $stats = $this->service->getPlayerStats($playerId);

        $this->assertIsArray($stats);
        $this->assertEquals($playerId, $stats['playerId']);

        Http::assertSent(function ($request) use ($playerId) {
            return str_contains($request->url(), "player/{$playerId}");
        });
    }

    /**
     * @test
     */
    public function it_can_get_game_boxscore(): void
    {
        $gameId = 2023020001;

        Http::fake([
            "*/gamecenter/{$gameId}/boxscore" => Http::response([
                'gameId' => $gameId,
                'playerByGameStats' => [
                    'homeTeam' => [
                        'forwards' => [],
                        'defense' => [],
                        'goalies' => [],
                    ],
                    'awayTeam' => [
                        'forwards' => [],
                        'defense' => [],
                        'goalies' => [],
                    ],
                ],
            ], 200),
        ]);

        $boxscore = $this->service->getGameBoxscore($gameId);

        $this->assertIsArray($boxscore);
        $this->assertEquals($gameId, $boxscore['gameId']);

        Http::assertSent(function ($request) use ($gameId) {
            return str_contains($request->url(), "boxscore");
        });
    }

    /**
     * @test
     */
    public function cache_key_is_consistent_for_same_requests(): void
    {
        $gameId = 2023020001;

        Http::fake([
            "*/gamecenter/{$gameId}/landing" => Http::response(['id' => $gameId], 200),
        ]);

        // Make two identical requests
        $this->service->getGame($gameId);
        $this->service->getGame($gameId);

        // Should only hit API once
        Http::assertSentCount(1);
    }

    /**
     * @test
     */
    public function different_dates_use_different_cache_keys(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response(['gameWeek' => []], 200),
        ]);

        $this->service->getGamesForDate('2024-01-15');
        $this->service->getGamesForDate('2024-01-16');

        // Should make two separate API calls
        Http::assertSentCount(2);
    }
}
