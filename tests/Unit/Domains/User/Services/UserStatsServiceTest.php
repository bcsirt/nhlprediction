<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Services;

use App\Domains\User\Enums\StatsPeriod;
use App\Domains\User\Models\UserStatistics;
use App\Domains\User\Services\UserStatsService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatsServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserStatsService();
    }

    public function test_get_stats_for_period(): void
    {
        $user = User::factory()->create();
        $stats = UserStatistics::factory()->monthly()->create([
            'user_id' => $user->id,
        ]);

        $retrieved = $this->service->getStatsForPeriod($user, StatsPeriod::MONTHLY);

        $this->assertInstanceOf(UserStatistics::class, $retrieved);
        $this->assertEquals($stats->id, $retrieved->id);
    }

    public function test_get_stats_for_period_returns_null_when_not_found(): void
    {
        $user = User::factory()->create();

        $stats = $this->service->getStatsForPeriod($user, StatsPeriod::MONTHLY);

        $this->assertNull($stats);
    }

    public function test_get_all_current_stats(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->daily()->create(['user_id' => $user->id]);
        UserStatistics::factory()->weekly()->create(['user_id' => $user->id]);
        UserStatistics::factory()->monthly()->create(['user_id' => $user->id]);

        $stats = $this->service->getAllCurrentStats($user);

        $this->assertArrayHasKey('daily', $stats);
        $this->assertArrayHasKey('weekly', $stats);
        $this->assertArrayHasKey('monthly', $stats);
    }

    public function test_get_stats_history(): void
    {
        $user = User::factory()->create();

        // Create multiple monthly stats
        for ($i = 0; $i < 5; $i++) {
            UserStatistics::factory()->create([
                'user_id' => $user->id,
                'period_type' => StatsPeriod::MONTHLY->value,
                'period_start' => now()->subMonths($i)->startOfMonth()->toDateString(),
                'period_end' => now()->subMonths($i)->endOfMonth()->toDateString(),
            ]);
        }

        $history = $this->service->getStatsHistory($user, StatsPeriod::MONTHLY, 3);

        $this->assertCount(3, $history);
    }

    public function test_compare_to_previous_period(): void
    {
        $user = User::factory()->create();

        // Current month stats
        $currentDates = StatsPeriod::MONTHLY->getCurrentPeriod();
        UserStatistics::factory()->create([
            'user_id' => $user->id,
            'period_type' => StatsPeriod::MONTHLY->value,
            'period_start' => $currentDates['start']->toDateString(),
            'period_end' => $currentDates['end']->toDateString(),
            'total_bets' => 20,
            'win_rate' => 0.65,
            'total_profit' => 200.00,
            'roi_percentage' => 10.0,
        ]);

        // Previous month stats
        $previousDates = StatsPeriod::MONTHLY->getPreviousPeriod();
        UserStatistics::factory()->create([
            'user_id' => $user->id,
            'period_type' => StatsPeriod::MONTHLY->value,
            'period_start' => $previousDates['start']->toDateString(),
            'period_end' => $previousDates['end']->toDateString(),
            'total_bets' => 15,
            'win_rate' => 0.55,
            'total_profit' => 100.00,
            'roi_percentage' => 5.0,
        ]);

        $comparison = $this->service->compareToPreviousPeriod($user, StatsPeriod::MONTHLY);

        $this->assertArrayHasKey('bets_change', $comparison);
        $this->assertArrayHasKey('win_rate_change', $comparison);
        $this->assertArrayHasKey('profit_change', $comparison);
        $this->assertArrayHasKey('roi_change', $comparison);
        $this->assertEquals(5, $comparison['bets_change']);
        $this->assertEquals(10.0, $comparison['win_rate_change']);
        $this->assertEquals(100.00, $comparison['profit_change']);
        $this->assertEquals(5.0, $comparison['roi_change']);
    }

    public function test_compare_to_previous_period_returns_empty_when_no_data(): void
    {
        $user = User::factory()->create();

        $comparison = $this->service->compareToPreviousPeriod($user, StatsPeriod::MONTHLY);

        $this->assertEmpty($comparison);
    }

    public function test_get_best_performing_teams(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->allTime()->withTeamStats()->create([
            'user_id' => $user->id,
        ]);

        $teams = $this->service->getBestPerformingTeams($user, 2);

        $this->assertCount(2, $teams);
    }

    public function test_get_best_performing_teams_returns_empty_when_no_stats(): void
    {
        $user = User::factory()->create();

        $teams = $this->service->getBestPerformingTeams($user);

        $this->assertEmpty($teams);
    }

    public function test_get_performance_by_bet_type(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->monthly()->withBetTypeStats()->create([
            'user_id' => $user->id,
        ]);

        $performance = $this->service->getPerformanceByBetType($user, StatsPeriod::MONTHLY);

        $this->assertArrayHasKey('moneyline', $performance);
        $this->assertArrayHasKey('spread', $performance);
    }

    public function test_get_performance_by_bet_type_returns_empty_when_no_stats(): void
    {
        $user = User::factory()->create();

        $performance = $this->service->getPerformanceByBetType($user, StatsPeriod::MONTHLY);

        $this->assertEmpty($performance);
    }

    public function test_get_performance_by_confidence(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->monthly()->withConfidenceStats()->create([
            'user_id' => $user->id,
        ]);

        $performance = $this->service->getPerformanceByConfidence($user, StatsPeriod::MONTHLY);

        $this->assertArrayHasKey('high', $performance);
        $this->assertArrayHasKey('medium', $performance);
        $this->assertArrayHasKey('low', $performance);
    }

    public function test_get_dashboard_summary(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->daily()->create([
            'user_id' => $user->id,
            'total_bets' => 5,
            'total_profit' => 50.00,
        ]);
        UserStatistics::factory()->weekly()->create([
            'user_id' => $user->id,
            'total_bets' => 15,
            'total_profit' => 150.00,
            'win_rate' => 0.65,
        ]);
        UserStatistics::factory()->monthly()->create([
            'user_id' => $user->id,
            'total_bets' => 40,
            'total_profit' => 300.00,
            'roi_percentage' => 12.5,
        ]);
        UserStatistics::factory()->allTime()->create([
            'user_id' => $user->id,
            'total_bets' => 200,
            'total_profit' => 1500.00,
            'win_rate' => 0.58,
            'roi_percentage' => 8.5,
        ]);

        $summary = $this->service->getDashboardSummary($user);

        $this->assertArrayHasKey('today', $summary);
        $this->assertArrayHasKey('this_week', $summary);
        $this->assertArrayHasKey('this_month', $summary);
        $this->assertArrayHasKey('all_time', $summary);

        $this->assertEquals(5, $summary['today']['bets']);
        $this->assertEquals(50.00, $summary['today']['profit']);

        $this->assertEquals(15, $summary['this_week']['bets']);
        $this->assertEquals(65.0, $summary['this_week']['win_rate']);

        $this->assertEquals(40, $summary['this_month']['bets']);
        $this->assertEquals(12.5, $summary['this_month']['roi']);

        $this->assertEquals(200, $summary['all_time']['bets']);
        $this->assertEquals(58.0, $summary['all_time']['win_rate']);
    }

    public function test_get_dashboard_summary_with_no_stats(): void
    {
        $user = User::factory()->create();

        $summary = $this->service->getDashboardSummary($user);

        $this->assertEquals(0, $summary['today']['bets']);
        $this->assertEquals(0, $summary['this_week']['bets']);
        $this->assertEquals(0, $summary['this_month']['bets']);
        $this->assertEquals(0, $summary['all_time']['bets']);
    }

    public function test_recalculate_stats(): void
    {
        $user = User::factory()->create();

        // Create existing stats
        $existingStats = UserStatistics::factory()->monthly()->create([
            'user_id' => $user->id,
            'total_bets' => 100,
        ]);

        $newStats = $this->service->recalculateStats($user, StatsPeriod::MONTHLY);

        // Old stats should be deleted
        $this->assertDatabaseMissing('user_statistics', [
            'id' => $existingStats->id,
        ]);

        // New stats should be created
        $this->assertInstanceOf(UserStatistics::class, $newStats);
        $this->assertEquals(0, $newStats->total_bets);
    }
}
