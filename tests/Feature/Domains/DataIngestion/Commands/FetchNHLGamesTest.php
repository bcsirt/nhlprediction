<?php

declare(strict_types=1);

namespace Tests\Feature\Domains\DataIngestion\Commands;

use App\Domains\DataIngestion\Services\NHLApiService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Feature tests pour la commande FetchNHLGames
 */
class FetchNHLGamesTest extends TestCase
{
    /**
     * @test
     */
    public function it_can_fetch_todays_games_without_argument(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023020001,
                                'gameType' => 2,
                                'gameDate' => now()->format('Y-m-d'),
                                'homeTeam' => [
                                    'id' => 8,
                                    'abbrev' => 'MTL',
                                    'placeName' => ['default' => 'Montréal'],
                                    'score' => 0,
                                ],
                                'awayTeam' => [
                                    'id' => 10,
                                    'abbrev' => 'TOR',
                                    'placeName' => ['default' => 'Toronto'],
                                    'score' => 0,
                                ],
                                'gameState' => 'FUT',
                                'gameScheduleState' => 'OK',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutput('🏒 Récupération des matchs NHL...')
            ->assertExitCode(0);
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
                            [
                                'id' => 2023020100,
                                'gameType' => 2,
                                'gameDate' => $date,
                                'homeTeam' => [
                                    'id' => 8,
                                    'abbrev' => 'MTL',
                                    'placeName' => ['default' => 'Montréal'],
                                    'score' => 3,
                                ],
                                'awayTeam' => [
                                    'id' => 10,
                                    'abbrev' => 'TOR',
                                    'placeName' => ['default' => 'Toronto'],
                                    'score' => 2,
                                ],
                                'gameState' => 'OFF',
                                'gameScheduleState' => 'OK',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games', ['date' => $date])
            ->expectsOutput('🏒 Récupération des matchs NHL...')
            ->expectsOutput("📅 Date: {$date}")
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_displays_no_games_message_when_no_games_found(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutput('ℹ️  Aucun match trouvé pour cette date.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_displays_games_in_table_format(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023020001,
                                'gameType' => 2,
                                'gameDate' => now()->format('Y-m-d H:i:s'),
                                'homeTeam' => [
                                    'id' => 8,
                                    'abbrev' => 'MTL',
                                    'placeName' => ['default' => 'Montréal'],
                                    'score' => 0,
                                ],
                                'awayTeam' => [
                                    'id' => 10,
                                    'abbrev' => 'TOR',
                                    'placeName' => ['default' => 'Toronto'],
                                    'score' => 0,
                                ],
                                'gameState' => 'FUT',
                                'gameScheduleState' => 'OK',
                            ],
                            [
                                'id' => 2023020002,
                                'gameType' => 2,
                                'gameDate' => now()->format('Y-m-d H:i:s'),
                                'homeTeam' => [
                                    'id' => 1,
                                    'abbrev' => 'NJD',
                                    'placeName' => ['default' => 'New Jersey'],
                                    'score' => 4,
                                ],
                                'awayTeam' => [
                                    'id' => 2,
                                    'abbrev' => 'NYI',
                                    'placeName' => ['default' => 'NY Islanders'],
                                    'score' => 3,
                                ],
                                'gameState' => 'OFF',
                                'gameScheduleState' => 'OK',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutputToContain('ID')
            ->expectsOutputToContain('Match')
            ->expectsOutputToContain('Score')
            ->expectsOutputToContain('Statut')
            ->expectsOutputToContain('MTL')
            ->expectsOutputToContain('TOR')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_handles_api_errors_gracefully(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response(null, 500),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutput('ℹ️  Aucun match trouvé pour cette date.')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_validates_date_format(): void
    {
        // Invalid date format should still run but use current date
        $this->artisan('nhl:fetch-games', ['date' => 'invalid-date'])
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_shows_game_status_correctly(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023020001,
                                'gameType' => 2,
                                'gameDate' => now()->format('Y-m-d H:i:s'),
                                'homeTeam' => [
                                    'id' => 8,
                                    'abbrev' => 'MTL',
                                    'placeName' => ['default' => 'Montréal'],
                                    'score' => 3,
                                ],
                                'awayTeam' => [
                                    'id' => 10,
                                    'abbrev' => 'TOR',
                                    'placeName' => ['default' => 'Toronto'],
                                    'score' => 2,
                                ],
                                'gameState' => 'OFF',
                                'gameScheduleState' => 'OK',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutputToContain('3')
            ->expectsOutputToContain('2')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_displays_total_games_count(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            ['id' => 1],
                            ['id' => 2],
                            ['id' => 3],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutput('✅ 3 match(s) trouvé(s)')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_uses_nhl_api_service(): void
    {
        $mockService = $this->mock(NHLApiService::class);
        $mockService->shouldReceive('getGamesForDate')
            ->once()
            ->andReturn([
                [
                    'id' => 2023020001,
                    'homeTeam' => ['abbrev' => 'MTL', 'score' => 0],
                    'awayTeam' => ['abbrev' => 'TOR', 'score' => 0],
                    'gameState' => 'FUT',
                ],
            ]);

        $this->artisan('nhl:fetch-games')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_shows_helpful_next_steps(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023020001,
                                'homeTeam' => ['abbrev' => 'MTL', 'score' => 0],
                                'awayTeam' => ['abbrev' => 'TOR', 'score' => 0],
                                'gameState' => 'FUT',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutput('💡 Prochaines étapes suggérées:')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function command_signature_is_correct(): void
    {
        $this->artisan('list')
            ->expectsOutputToContain('nhl:fetch-games')
            ->assertExitCode(0);
    }

    /**
     * @test
     */
    public function it_can_handle_playoff_games(): void
    {
        Http::fake([
            '*/schedule/*' => Http::response([
                'gameWeek' => [
                    [
                        'date' => now()->format('Y-m-d'),
                        'games' => [
                            [
                                'id' => 2023030001, // Playoff game ID
                                'gameType' => 3,
                                'gameDate' => now()->format('Y-m-d H:i:s'),
                                'homeTeam' => [
                                    'id' => 8,
                                    'abbrev' => 'MTL',
                                    'placeName' => ['default' => 'Montréal'],
                                    'score' => 2,
                                ],
                                'awayTeam' => [
                                    'id' => 10,
                                    'abbrev' => 'TOR',
                                    'placeName' => ['default' => 'Toronto'],
                                    'score' => 1,
                                ],
                                'gameState' => 'OFF',
                                'gameScheduleState' => 'OK',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $this->artisan('nhl:fetch-games')
            ->expectsOutputToContain('MTL')
            ->expectsOutputToContain('TOR')
            ->assertExitCode(0);
    }
}
