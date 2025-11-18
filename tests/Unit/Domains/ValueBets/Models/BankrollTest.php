<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Models;

use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BettingStrategy;
use App\Domains\ValueBets\Models\Bankroll;
use App\Domains\ValueBets\Models\Bet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_bets(): void
    {
        $bankroll = Bankroll::factory()->create();
        Bet::factory()->count(3)->create(['bankroll_id' => $bankroll->id]);

        $this->assertCount(3, $bankroll->bets);
    }

    public function test_get_strategy_enum(): void
    {
        $bankroll = Bankroll::factory()->create([
            'betting_strategy' => BettingStrategy::KELLY->value,
        ]);

        $this->assertEquals(BettingStrategy::KELLY, $bankroll->getStrategyEnum());
    }

    public function test_calculate_stake_with_kelly(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 1000,
            'betting_strategy' => BettingStrategy::KELLY->value,
            'kelly_fraction' => 0.25,
            'max_bet_percentage' => 10,
            'min_bet_amount' => 10,
        ]);

        $stake = $bankroll->calculateStake(0.6, 2.0);

        // Kelly = 0.2, with 0.25 fraction = 0.05
        // Stake = 1000 * 0.05 = 50
        $this->assertGreaterThan(0, $stake);
        $this->assertLessThanOrEqual(100, $stake); // Max 10% of 1000
    }

    public function test_calculate_stake_respects_min_amount(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 1000,
            'betting_strategy' => BettingStrategy::KELLY->value,
            'min_bet_amount' => 50,
        ]);

        // Very small Kelly would be below min
        $stake = $bankroll->calculateStake(0.51, 2.0);

        $this->assertGreaterThanOrEqual(50, $stake);
    }

    public function test_calculate_stake_respects_max_amount(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 10000,
            'betting_strategy' => BettingStrategy::KELLY->value,
            'max_bet_amount' => 100,
        ]);

        $stake = $bankroll->calculateStake(0.7, 2.0);

        $this->assertLessThanOrEqual(100, $stake);
    }

    public function test_calculate_stake_respects_max_percentage(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 1000,
            'betting_strategy' => BettingStrategy::KELLY->value,
            'max_bet_percentage' => 5,
            'max_bet_amount' => null,
        ]);

        $stake = $bankroll->calculateStake(0.8, 2.0);

        // Max should be 5% of 1000 = 50
        $this->assertLessThanOrEqual(50, $stake);
    }

    public function test_record_bet_updates_stats(): void
    {
        $bankroll = Bankroll::factory()->fresh()->create([
            'total_bets' => 0,
            'pending_bets' => 0,
            'total_wagered' => 0,
        ]);

        $bet = Bet::factory()->make([
            'bankroll_id' => $bankroll->id,
            'stake' => 100,
        ]);
        $bet->save();

        $bankroll->recordBet($bet);

        $this->assertEquals(1, $bankroll->total_bets);
        $this->assertEquals(1, $bankroll->pending_bets);
        $this->assertEquals(100, $bankroll->total_wagered);
    }

    public function test_settle_bet_updates_stats_on_win(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 1000,
            'winning_bets' => 0,
            'total_profit' => 0,
            'pending_bets' => 1,
            'current_streak' => 0,
            'peak_amount' => 1000,
        ]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::WON->value,
            'profit_loss' => 100,
        ]);

        $bankroll->settleBet($bet);

        $this->assertEquals(1, $bankroll->winning_bets);
        $this->assertEquals(0, $bankroll->pending_bets);
        $this->assertEquals(1100, (float) $bankroll->current_amount);
        $this->assertEquals(1, $bankroll->current_streak);
    }

    public function test_settle_bet_updates_stats_on_loss(): void
    {
        $bankroll = Bankroll::factory()->create([
            'current_amount' => 1000,
            'losing_bets' => 0,
            'total_profit' => 0,
            'pending_bets' => 1,
            'current_streak' => 0,
            'peak_amount' => 1000,
        ]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::LOST->value,
            'profit_loss' => -100,
        ]);

        $bankroll->settleBet($bet);

        $this->assertEquals(1, $bankroll->losing_bets);
        $this->assertEquals(900, (float) $bankroll->current_amount);
        $this->assertEquals(-1, $bankroll->current_streak);
    }

    public function test_win_rate_attribute(): void
    {
        $bankroll = Bankroll::factory()->create([
            'winning_bets' => 60,
            'losing_bets' => 40,
        ]);

        $this->assertEquals(60.0, $bankroll->win_rate);
    }

    public function test_win_rate_returns_zero_when_no_bets(): void
    {
        $bankroll = Bankroll::factory()->create([
            'winning_bets' => 0,
            'losing_bets' => 0,
        ]);

        $this->assertEquals(0, $bankroll->win_rate);
    }

    public function test_get_summary(): void
    {
        $bankroll = Bankroll::factory()->create();

        $summary = $bankroll->getSummary();

        $this->assertArrayHasKey('bankroll', $summary);
        $this->assertArrayHasKey('initial', $summary);
        $this->assertArrayHasKey('profit', $summary);
        $this->assertArrayHasKey('roi', $summary);
        $this->assertArrayHasKey('win_rate', $summary);
        $this->assertArrayHasKey('record', $summary);
        $this->assertArrayHasKey('streak', $summary);
        $this->assertArrayHasKey('max_drawdown', $summary);
    }

    public function test_scope_active(): void
    {
        Bankroll::factory()->create(['is_active' => true]);
        Bankroll::factory()->create(['is_active' => false]);

        $active = Bankroll::active()->get();

        $this->assertCount(1, $active);
    }

    public function test_roi_calculation_on_settle(): void
    {
        $bankroll = Bankroll::factory()->create([
            'total_wagered' => 1000,
            'total_profit' => 0,
            'pending_bets' => 1,
        ]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::WON->value,
            'profit_loss' => 100,
        ]);

        $bankroll->settleBet($bet);

        // ROI = (100 / 1000) * 100 = 10%
        $this->assertEqualsWithDelta(10, $bankroll->roi_percentage, 0.1);
    }
}
