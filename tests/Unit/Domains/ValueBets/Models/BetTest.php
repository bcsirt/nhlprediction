<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Models;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BetType;
use App\Domains\ValueBets\Models\Bankroll;
use App\Domains\ValueBets\Models\Bet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_bankroll(): void
    {
        $bankroll = Bankroll::factory()->create();
        $bet = Bet::factory()->create(['bankroll_id' => $bankroll->id]);

        $this->assertInstanceOf(Bankroll::class, $bet->bankroll);
        $this->assertEquals($bankroll->id, $bet->bankroll->id);
    }

    public function test_it_belongs_to_game(): void
    {
        $game = Game::factory()->create();
        $bet = Bet::factory()->create(['game_id' => $game->id]);

        $this->assertInstanceOf(Game::class, $bet->game);
    }

    public function test_get_status_enum(): void
    {
        $bet = Bet::factory()->create(['status' => BetStatus::PENDING->value]);

        $this->assertEquals(BetStatus::PENDING, $bet->getStatusEnum());
    }

    public function test_get_bet_type_enum(): void
    {
        $bet = Bet::factory()->create(['bet_type' => BetType::MONEYLINE->value]);

        $this->assertEquals(BetType::MONEYLINE, $bet->getBetTypeEnum());
    }

    public function test_has_value_returns_true_for_positive_edge(): void
    {
        $bet = Bet::factory()->create(['edge_percentage' => 5.5]);

        $this->assertTrue($bet->hasValue());
    }

    public function test_has_value_returns_false_for_negative_edge(): void
    {
        $bet = Bet::factory()->create(['edge_percentage' => -2.0]);

        $this->assertFalse($bet->hasValue());
    }

    public function test_decimal_to_american_positive(): void
    {
        // 2.50 décimal = +150 américain
        $american = Bet::decimalToAmerican(2.50);
        $this->assertEquals(150, $american);
    }

    public function test_decimal_to_american_negative(): void
    {
        // 1.50 décimal = -200 américain
        $american = Bet::decimalToAmerican(1.50);
        $this->assertEquals(-200, $american);
    }

    public function test_calculate_implied_probability(): void
    {
        $prob = Bet::calculateImpliedProbability(2.0);
        $this->assertEquals(0.5, $prob);
    }

    public function test_calculate_expected_value(): void
    {
        $ev = Bet::calculateExpectedValue(0.6, 2.0, 100);
        // EV = (0.6 * 100) - (0.4 * 100) = 20
        $this->assertEquals(20, $ev);
    }

    public function test_calculate_edge(): void
    {
        $edge = Bet::calculateEdge(0.6, 0.5);
        // Edge = ((0.6 - 0.5) / 0.5) * 100 = 20%
        $this->assertEquals(20, $edge);
    }

    public function test_scope_pending(): void
    {
        Bet::factory()->create(['status' => BetStatus::PENDING->value]);
        Bet::factory()->create(['status' => BetStatus::WON->value]);

        $pending = Bet::pending()->get();

        $this->assertCount(1, $pending);
    }

    public function test_scope_settled(): void
    {
        Bet::factory()->create(['status' => BetStatus::PENDING->value]);
        Bet::factory()->create(['status' => BetStatus::WON->value]);
        Bet::factory()->create(['status' => BetStatus::LOST->value]);

        $settled = Bet::settled()->get();

        $this->assertCount(2, $settled);
    }

    public function test_scope_won(): void
    {
        Bet::factory()->create(['status' => BetStatus::WON->value]);
        Bet::factory()->create(['status' => BetStatus::LOST->value]);

        $won = Bet::won()->get();

        $this->assertCount(1, $won);
    }

    public function test_scope_lost(): void
    {
        Bet::factory()->create(['status' => BetStatus::WON->value]);
        Bet::factory()->create(['status' => BetStatus::LOST->value]);

        $lost = Bet::lost()->get();

        $this->assertCount(1, $lost);
    }

    public function test_scope_with_value(): void
    {
        Bet::factory()->create(['edge_percentage' => 5]);
        Bet::factory()->create(['edge_percentage' => -3]);

        $withValue = Bet::withValue()->get();

        $this->assertCount(1, $withValue);
    }

    public function test_scope_of_type(): void
    {
        Bet::factory()->create(['bet_type' => BetType::MONEYLINE->value]);
        Bet::factory()->create(['bet_type' => BetType::SPREAD->value]);

        $moneyline = Bet::ofType(BetType::MONEYLINE)->get();

        $this->assertCount(1, $moneyline);
    }

    public function test_settle_win(): void
    {
        $bankroll = Bankroll::factory()->create([
            'pending_bets' => 1,
            'winning_bets' => 0,
        ]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::PENDING->value,
            'stake' => 100,
            'potential_payout' => 200,
        ]);

        $bet->settle(BetStatus::WON);

        $this->assertEquals(BetStatus::WON->value, $bet->status);
        $this->assertEquals(200, (float) $bet->actual_payout);
        $this->assertEquals(100, (float) $bet->profit_loss);
        $this->assertTrue($bet->is_correct);
        $this->assertNotNull($bet->settled_at);
    }

    public function test_settle_loss(): void
    {
        $bankroll = Bankroll::factory()->create([
            'pending_bets' => 1,
            'losing_bets' => 0,
        ]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::PENDING->value,
            'stake' => 100,
        ]);

        $bet->settle(BetStatus::LOST);

        $this->assertEquals(BetStatus::LOST->value, $bet->status);
        $this->assertEquals(0, (float) $bet->actual_payout);
        $this->assertEquals(-100, (float) $bet->profit_loss);
        $this->assertFalse($bet->is_correct);
    }

    public function test_settle_push(): void
    {
        $bankroll = Bankroll::factory()->create(['pending_bets' => 1]);

        $bet = Bet::factory()->create([
            'bankroll_id' => $bankroll->id,
            'status' => BetStatus::PENDING->value,
            'stake' => 100,
        ]);

        $bet->settle(BetStatus::PUSH);

        $this->assertEquals(BetStatus::PUSH->value, $bet->status);
        $this->assertEquals(100, (float) $bet->actual_payout);
        $this->assertEquals(0, (float) $bet->profit_loss);
        $this->assertNull($bet->is_correct);
    }

    public function test_casts_are_correct(): void
    {
        $bet = Bet::factory()->create([
            'metadata' => ['key' => 'value'],
        ]);

        $this->assertIsArray($bet->metadata);
        $this->assertEquals('value', $bet->metadata['key']);
    }
}
