<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Enums\StatsPeriod;
use App\Domains\User\Models\UserStatistics;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $stats = UserStatistics::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $stats->user);
        $this->assertEquals($user->id, $stats->user->id);
    }

    public function test_get_period_enum(): void
    {
        $stats = UserStatistics::factory()->monthly()->create();

        $this->assertEquals(StatsPeriod::MONTHLY, $stats->getPeriodEnum());
    }

    public function test_is_profitable(): void
    {
        $profitable = UserStatistics::factory()->profitable()->create();
        $losing = UserStatistics::factory()->losing()->create();

        $this->assertTrue($profitable->isProfitable());
        $this->assertFalse($losing->isProfitable());
    }

    public function test_losing_bets_attribute(): void
    {
        $stats = UserStatistics::factory()->create([
            'total_bets' => 20,
            'winning_bets' => 12,
        ]);

        $this->assertEquals(8, $stats->losing_bets);
    }

    public function test_record_attribute(): void
    {
        $stats = UserStatistics::factory()->create([
            'total_bets' => 15,
            'winning_bets' => 10,
        ]);

        $this->assertEquals('10W-5L', $stats->record);
    }

    public function test_get_summary(): void
    {
        $stats = UserStatistics::factory()->monthly()->create([
            'total_bets' => 20,
            'winning_bets' => 12,
            'win_rate' => 0.6,
            'total_profit' => 150.50,
            'roi_percentage' => 15.05,
            'average_odds' => 1.95,
        ]);

        $summary = $stats->getSummary();

        $this->assertArrayHasKey('period', $summary);
        $this->assertArrayHasKey('dates', $summary);
        $this->assertArrayHasKey('total_bets', $summary);
        $this->assertArrayHasKey('record', $summary);
        $this->assertArrayHasKey('win_rate', $summary);
        $this->assertArrayHasKey('profit', $summary);
        $this->assertArrayHasKey('roi', $summary);
        $this->assertEquals(20, $summary['total_bets']);
        $this->assertEquals('12W-8L', $summary['record']);
        $this->assertStringContainsString('+$150.50', $summary['profit']);
    }

    public function test_scope_for_period(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->daily()->create(['user_id' => $user->id]);
        UserStatistics::factory()->weekly()->create(['user_id' => $user->id]);
        UserStatistics::factory()->monthly()->create(['user_id' => $user->id]);

        $monthly = UserStatistics::where('user_id', $user->id)
            ->forPeriod(StatsPeriod::MONTHLY)
            ->get();

        $this->assertCount(1, $monthly);
    }

    public function test_scope_profitable(): void
    {
        $user = User::factory()->create();
        UserStatistics::factory()->profitable()->create(['user_id' => $user->id]);
        UserStatistics::factory()->losing()->create(['user_id' => $user->id]);

        $profitable = UserStatistics::where('user_id', $user->id)->profitable()->get();

        $this->assertCount(1, $profitable);
    }

    public function test_get_or_create_for_period(): void
    {
        $user = User::factory()->create();

        $stats = UserStatistics::getOrCreateForPeriod($user->id, StatsPeriod::DAILY);

        $this->assertInstanceOf(UserStatistics::class, $stats);
        $this->assertEquals($user->id, $stats->user_id);
        $this->assertEquals(StatsPeriod::DAILY->value, $stats->period_type);
        $this->assertEquals(0, $stats->total_bets);
    }

    public function test_get_or_create_returns_existing(): void
    {
        $user = User::factory()->create();
        $existing = UserStatistics::factory()->daily()->create([
            'user_id' => $user->id,
            'total_bets' => 10,
        ]);

        $retrieved = UserStatistics::getOrCreateForPeriod($user->id, StatsPeriod::DAILY);

        $this->assertEquals($existing->id, $retrieved->id);
        $this->assertEquals(10, $retrieved->total_bets);
    }

    public function test_record_bet_winning(): void
    {
        $stats = UserStatistics::factory()->empty()->create();

        $stats->recordBet(100.00, 2.00, true, 100.00);

        $this->assertEquals(1, $stats->total_bets);
        $this->assertEquals(1, $stats->winning_bets);
        $this->assertEquals(1.0, $stats->win_rate);
        $this->assertEquals(100.00, $stats->total_staked);
        $this->assertEquals(100.00, $stats->total_profit);
        $this->assertEquals(100.0, $stats->roi_percentage);
    }

    public function test_record_bet_losing(): void
    {
        $stats = UserStatistics::factory()->empty()->create();

        $stats->recordBet(100.00, 2.00, false, -100.00);

        $this->assertEquals(1, $stats->total_bets);
        $this->assertEquals(0, $stats->winning_bets);
        $this->assertEquals(0.0, $stats->win_rate);
        $this->assertEquals(-100.00, $stats->total_profit);
        $this->assertEquals(-100.0, $stats->roi_percentage);
    }

    public function test_record_multiple_bets(): void
    {
        $stats = UserStatistics::factory()->empty()->create();

        $stats->recordBet(100.00, 2.00, true, 100.00);
        $stats->recordBet(100.00, 1.90, false, -100.00);
        $stats->recordBet(100.00, 2.10, true, 110.00);

        $this->assertEquals(3, $stats->total_bets);
        $this->assertEquals(2, $stats->winning_bets);
        $this->assertEquals(300.00, $stats->total_staked);
        $this->assertEquals(110.00, $stats->total_profit);
        $this->assertEqualsWithDelta(0.667, $stats->win_rate, 0.01);
        $this->assertEqualsWithDelta(36.67, $stats->roi_percentage, 0.1);
    }

    public function test_casts_dates_correctly(): void
    {
        $stats = UserStatistics::factory()->create();

        $this->assertInstanceOf(\Carbon\Carbon::class, $stats->period_start);
        $this->assertInstanceOf(\Carbon\Carbon::class, $stats->period_end);
    }

    public function test_casts_arrays_correctly(): void
    {
        $stats = UserStatistics::factory()->withTeamStats()->create();

        $this->assertIsArray($stats->stats_by_team);
        $this->assertArrayHasKey('team_1', $stats->stats_by_team);
    }

    public function test_with_bet_type_stats(): void
    {
        $stats = UserStatistics::factory()->withBetTypeStats()->create();

        $this->assertIsArray($stats->stats_by_bet_type);
        $this->assertArrayHasKey('moneyline', $stats->stats_by_bet_type);
        $this->assertArrayHasKey('spread', $stats->stats_by_bet_type);
    }

    public function test_with_confidence_stats(): void
    {
        $stats = UserStatistics::factory()->withConfidenceStats()->create();

        $this->assertIsArray($stats->stats_by_confidence);
        $this->assertArrayHasKey('high', $stats->stats_by_confidence);
        $this->assertArrayHasKey('medium', $stats->stats_by_confidence);
        $this->assertArrayHasKey('low', $stats->stats_by_confidence);
    }
}
