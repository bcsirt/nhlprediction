<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Enums;

use App\Domains\ValueBets\Enums\BettingStrategy;
use PHPUnit\Framework\TestCase;

class BettingStrategyTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = BettingStrategy::cases();

        $this->assertCount(7, $cases);
        $this->assertEquals('kelly', BettingStrategy::KELLY->value);
        $this->assertEquals('half_kelly', BettingStrategy::HALF_KELLY->value);
        $this->assertEquals('quarter_kelly', BettingStrategy::QUARTER_KELLY->value);
        $this->assertEquals('flat', BettingStrategy::FLAT->value);
        $this->assertEquals('percentage', BettingStrategy::PERCENTAGE->value);
        $this->assertEquals('fibonacci', BettingStrategy::FIBONACCI->value);
        $this->assertEquals('martingale', BettingStrategy::MARTINGALE->value);
    }

    public function test_label_returns_labels(): void
    {
        $this->assertEquals('Kelly Criterion', BettingStrategy::KELLY->label());
        $this->assertEquals('Demi-Kelly', BettingStrategy::HALF_KELLY->label());
        $this->assertEquals('Quart-Kelly', BettingStrategy::QUARTER_KELLY->label());
        $this->assertEquals('Mise fixe', BettingStrategy::FLAT->label());
    }

    public function test_description_returns_non_empty_string(): void
    {
        foreach (BettingStrategy::cases() as $strategy) {
            $this->assertNotEmpty($strategy->description());
        }
    }

    public function test_risk_level_returns_valid_range(): void
    {
        foreach (BettingStrategy::cases() as $strategy) {
            $risk = $strategy->riskLevel();
            $this->assertGreaterThanOrEqual(1, $risk);
            $this->assertLessThanOrEqual(5, $risk);
        }
    }

    public function test_martingale_has_highest_risk(): void
    {
        $this->assertEquals(5, BettingStrategy::MARTINGALE->riskLevel());
    }

    public function test_quarter_kelly_has_lowest_risk(): void
    {
        $this->assertEquals(1, BettingStrategy::QUARTER_KELLY->riskLevel());
    }

    public function test_is_recommended(): void
    {
        $this->assertTrue(BettingStrategy::KELLY->isRecommended());
        $this->assertTrue(BettingStrategy::HALF_KELLY->isRecommended());
        $this->assertTrue(BettingStrategy::QUARTER_KELLY->isRecommended());
        $this->assertTrue(BettingStrategy::FLAT->isRecommended());
        $this->assertFalse(BettingStrategy::MARTINGALE->isRecommended());
        $this->assertFalse(BettingStrategy::FIBONACCI->isRecommended());
    }

    public function test_uses_progression(): void
    {
        $this->assertFalse(BettingStrategy::KELLY->usesProgression());
        $this->assertFalse(BettingStrategy::FLAT->usesProgression());
        $this->assertTrue(BettingStrategy::FIBONACCI->usesProgression());
        $this->assertTrue(BettingStrategy::MARTINGALE->usesProgression());
    }

    public function test_kelly_multiplier(): void
    {
        $this->assertEquals(1.0, BettingStrategy::KELLY->kellyMultiplier());
        $this->assertEquals(0.5, BettingStrategy::HALF_KELLY->kellyMultiplier());
        $this->assertEquals(0.25, BettingStrategy::QUARTER_KELLY->kellyMultiplier());
        $this->assertEquals(0.0, BettingStrategy::FLAT->kellyMultiplier());
    }

    public function test_calculate_stake_kelly(): void
    {
        $bankroll = 1000;
        $probability = 0.6;  // 60% chance de gagner
        $odds = 2.0;         // Cotes de 2.0

        // Kelly = (bp - q) / b = (1 * 0.6 - 0.4) / 1 = 0.2
        // Stake = 1000 * 0.2 = 200

        $stake = BettingStrategy::KELLY->calculateStake($bankroll, $probability, $odds);
        $this->assertEquals(200, $stake);
    }

    public function test_calculate_stake_half_kelly(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;

        // Half Kelly = 200 * 0.5 = 100
        $stake = BettingStrategy::HALF_KELLY->calculateStake($bankroll, $probability, $odds);
        $this->assertEquals(100, $stake);
    }

    public function test_calculate_stake_quarter_kelly(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;

        // Quarter Kelly = 200 * 0.25 = 50
        $stake = BettingStrategy::QUARTER_KELLY->calculateStake($bankroll, $probability, $odds);
        $this->assertEquals(50, $stake);
    }

    public function test_calculate_stake_flat(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;
        $percentage = 0.02; // 2%

        // Flat = 1000 * 0.02 = 20
        $stake = BettingStrategy::FLAT->calculateStake($bankroll, $probability, $odds, $percentage);
        $this->assertEquals(20, $stake);
    }

    public function test_calculate_stake_returns_zero_for_negative_ev(): void
    {
        $bankroll = 1000;
        $probability = 0.4;  // 40% - pas de valeur avec cotes 2.0
        $odds = 2.0;

        $stake = BettingStrategy::KELLY->calculateStake($bankroll, $probability, $odds);
        $this->assertEquals(0, $stake);
    }
}
