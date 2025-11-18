<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Enums;

use App\Domains\Prediction\Enums\ConfidenceLevel;
use PHPUnit\Framework\TestCase;

class ConfidenceLevelTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = ConfidenceLevel::cases();

        $this->assertCount(5, $cases);
        $this->assertEquals('very_low', ConfidenceLevel::VERY_LOW->value);
        $this->assertEquals('low', ConfidenceLevel::LOW->value);
        $this->assertEquals('medium', ConfidenceLevel::MEDIUM->value);
        $this->assertEquals('high', ConfidenceLevel::HIGH->value);
        $this->assertEquals('very_high', ConfidenceLevel::VERY_HIGH->value);
    }

    public function test_label_returns_french_label(): void
    {
        $this->assertEquals('Très faible', ConfidenceLevel::VERY_LOW->label());
        $this->assertEquals('Faible', ConfidenceLevel::LOW->label());
        $this->assertEquals('Moyenne', ConfidenceLevel::MEDIUM->label());
        $this->assertEquals('Élevée', ConfidenceLevel::HIGH->label());
        $this->assertEquals('Très élevée', ConfidenceLevel::VERY_HIGH->label());
    }

    public function test_from_score_returns_correct_level(): void
    {
        $this->assertEquals(ConfidenceLevel::VERY_HIGH, ConfidenceLevel::fromScore(95));
        $this->assertEquals(ConfidenceLevel::VERY_HIGH, ConfidenceLevel::fromScore(90));
        $this->assertEquals(ConfidenceLevel::HIGH, ConfidenceLevel::fromScore(80));
        $this->assertEquals(ConfidenceLevel::HIGH, ConfidenceLevel::fromScore(75));
        $this->assertEquals(ConfidenceLevel::MEDIUM, ConfidenceLevel::fromScore(60));
        $this->assertEquals(ConfidenceLevel::MEDIUM, ConfidenceLevel::fromScore(55));
        $this->assertEquals(ConfidenceLevel::LOW, ConfidenceLevel::fromScore(45));
        $this->assertEquals(ConfidenceLevel::LOW, ConfidenceLevel::fromScore(40));
        $this->assertEquals(ConfidenceLevel::VERY_LOW, ConfidenceLevel::fromScore(30));
        $this->assertEquals(ConfidenceLevel::VERY_LOW, ConfidenceLevel::fromScore(0));
    }

    public function test_from_probability_converts_correctly(): void
    {
        $this->assertEquals(ConfidenceLevel::VERY_HIGH, ConfidenceLevel::fromProbability(0.95));
        $this->assertEquals(ConfidenceLevel::HIGH, ConfidenceLevel::fromProbability(0.8));
        $this->assertEquals(ConfidenceLevel::MEDIUM, ConfidenceLevel::fromProbability(0.6));
        $this->assertEquals(ConfidenceLevel::LOW, ConfidenceLevel::fromProbability(0.45));
        $this->assertEquals(ConfidenceLevel::VERY_LOW, ConfidenceLevel::fromProbability(0.2));
    }

    public function test_score_range_returns_correct_ranges(): void
    {
        $this->assertEquals([90, 100], ConfidenceLevel::VERY_HIGH->scoreRange());
        $this->assertEquals([75, 89], ConfidenceLevel::HIGH->scoreRange());
        $this->assertEquals([55, 74], ConfidenceLevel::MEDIUM->scoreRange());
        $this->assertEquals([40, 54], ConfidenceLevel::LOW->scoreRange());
        $this->assertEquals([0, 39], ConfidenceLevel::VERY_LOW->scoreRange());
    }

    public function test_color_returns_correct_colors(): void
    {
        $this->assertEquals('green', ConfidenceLevel::VERY_HIGH->color());
        $this->assertEquals('lime', ConfidenceLevel::HIGH->color());
        $this->assertEquals('yellow', ConfidenceLevel::MEDIUM->color());
        $this->assertEquals('orange', ConfidenceLevel::LOW->color());
        $this->assertEquals('red', ConfidenceLevel::VERY_LOW->color());
    }

    public function test_icon_returns_correct_icons(): void
    {
        $this->assertEquals('🟢', ConfidenceLevel::VERY_HIGH->icon());
        $this->assertEquals('🟡', ConfidenceLevel::HIGH->icon());
        $this->assertEquals('🟠', ConfidenceLevel::MEDIUM->icon());
        $this->assertEquals('🔴', ConfidenceLevel::LOW->icon());
        $this->assertEquals('⚫', ConfidenceLevel::VERY_LOW->icon());
    }

    public function test_kelly_fraction_returns_correct_values(): void
    {
        $this->assertEquals(1.0, ConfidenceLevel::VERY_HIGH->kellyFraction());
        $this->assertEquals(0.5, ConfidenceLevel::HIGH->kellyFraction());
        $this->assertEquals(0.25, ConfidenceLevel::MEDIUM->kellyFraction());
        $this->assertEquals(0.1, ConfidenceLevel::LOW->kellyFraction());
        $this->assertEquals(0.0, ConfidenceLevel::VERY_LOW->kellyFraction());
    }

    public function test_should_bet_returns_true_for_high_confidence(): void
    {
        $this->assertTrue(ConfidenceLevel::VERY_HIGH->shouldBet());
        $this->assertTrue(ConfidenceLevel::HIGH->shouldBet());
        $this->assertFalse(ConfidenceLevel::MEDIUM->shouldBet());
        $this->assertFalse(ConfidenceLevel::LOW->shouldBet());
        $this->assertFalse(ConfidenceLevel::VERY_LOW->shouldBet());
    }

    public function test_description_returns_non_empty_string(): void
    {
        foreach (ConfidenceLevel::cases() as $level) {
            $this->assertNotEmpty($level->description());
            $this->assertIsString($level->description());
        }
    }

    public function test_risk_multiplier_returns_correct_values(): void
    {
        $this->assertEquals(0.5, ConfidenceLevel::VERY_HIGH->riskMultiplier());
        $this->assertEquals(0.75, ConfidenceLevel::HIGH->riskMultiplier());
        $this->assertEquals(1.0, ConfidenceLevel::MEDIUM->riskMultiplier());
        $this->assertEquals(1.5, ConfidenceLevel::LOW->riskMultiplier());
        $this->assertEquals(2.0, ConfidenceLevel::VERY_LOW->riskMultiplier());
    }

    public function test_kelly_fraction_decreases_with_lower_confidence(): void
    {
        $previous = PHP_FLOAT_MAX;
        $levels = [
            ConfidenceLevel::VERY_HIGH,
            ConfidenceLevel::HIGH,
            ConfidenceLevel::MEDIUM,
            ConfidenceLevel::LOW,
            ConfidenceLevel::VERY_LOW,
        ];

        foreach ($levels as $level) {
            $current = $level->kellyFraction();
            $this->assertLessThanOrEqual($previous, $current);
            $previous = $current;
        }
    }

    public function test_risk_multiplier_increases_with_lower_confidence(): void
    {
        $previous = 0;
        $levels = [
            ConfidenceLevel::VERY_HIGH,
            ConfidenceLevel::HIGH,
            ConfidenceLevel::MEDIUM,
            ConfidenceLevel::LOW,
            ConfidenceLevel::VERY_LOW,
        ];

        foreach ($levels as $level) {
            $current = $level->riskMultiplier();
            $this->assertGreaterThanOrEqual($previous, $current);
            $previous = $current;
        }
    }
}
