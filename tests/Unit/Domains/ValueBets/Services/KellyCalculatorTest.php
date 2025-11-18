<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Services;

use App\Domains\ValueBets\Services\KellyCalculator;
use PHPUnit\Framework\TestCase;

class KellyCalculatorTest extends TestCase
{
    private KellyCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new KellyCalculator();
    }

    public function test_calculate_returns_correct_kelly_fraction(): void
    {
        // 60% probability, odds 2.0
        // Kelly = (1 * 0.6 - 0.4) / 1 = 0.2
        $result = $this->calculator->calculate(0.6, 2.0);
        $this->assertEquals(0.2, $result);
    }

    public function test_calculate_returns_zero_for_no_edge(): void
    {
        // 50% probability, odds 2.0 = no edge
        $result = $this->calculator->calculate(0.5, 2.0);
        $this->assertEquals(0, $result);
    }

    public function test_calculate_returns_zero_for_negative_edge(): void
    {
        // 40% probability, odds 2.0 = negative edge
        $result = $this->calculator->calculate(0.4, 2.0);
        $this->assertEquals(0, $result);
    }

    public function test_calculate_returns_zero_for_invalid_probability(): void
    {
        $this->assertEquals(0, $this->calculator->calculate(0, 2.0));
        $this->assertEquals(0, $this->calculator->calculate(1, 2.0));
        $this->assertEquals(0, $this->calculator->calculate(-0.5, 2.0));
        $this->assertEquals(0, $this->calculator->calculate(1.5, 2.0));
    }

    public function test_calculate_returns_zero_for_invalid_odds(): void
    {
        $this->assertEquals(0, $this->calculator->calculate(0.6, 1.0));
        $this->assertEquals(0, $this->calculator->calculate(0.6, 0.5));
    }

    public function test_calculate_fractional_applies_fraction(): void
    {
        $fullKelly = $this->calculator->calculate(0.6, 2.0);
        $halfKelly = $this->calculator->calculateFractional(0.6, 2.0, 0.5);
        $quarterKelly = $this->calculator->calculateFractional(0.6, 2.0, 0.25);

        $this->assertEquals($fullKelly * 0.5, $halfKelly);
        $this->assertEquals($fullKelly * 0.25, $quarterKelly);
    }

    public function test_calculate_stake_returns_correct_amount(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;

        $stake = $this->calculator->calculateStake($bankroll, $probability, $odds);
        $this->assertEquals(200, $stake);
    }

    public function test_calculate_stake_respects_max_stake(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;
        $maxStake = 50;

        $stake = $this->calculator->calculateStake($bankroll, $probability, $odds, 1.0, $maxStake);
        $this->assertEquals(50, $stake);
    }

    public function test_calculate_stake_with_fraction(): void
    {
        $bankroll = 1000;
        $probability = 0.6;
        $odds = 2.0;
        $fraction = 0.25;

        $stake = $this->calculator->calculateStake($bankroll, $probability, $odds, $fraction);
        $this->assertEquals(50, $stake);
    }

    public function test_calculate_multiple_bets(): void
    {
        $bets = [
            'bet1' => ['probability' => 0.6, 'odds' => 2.0],
            'bet2' => ['probability' => 0.55, 'odds' => 2.2],
        ];

        $results = $this->calculator->calculateMultiple($bets);

        $this->assertArrayHasKey('bet1', $results);
        $this->assertArrayHasKey('bet2', $results);
        $this->assertArrayHasKey('kelly_fraction', $results['bet1']);
        $this->assertArrayHasKey('edge', $results['bet1']);
    }

    public function test_calculate_multiple_normalizes_when_sum_exceeds_one(): void
    {
        // Create bets that would sum to more than 1
        $bets = [
            'bet1' => ['probability' => 0.7, 'odds' => 2.0],
            'bet2' => ['probability' => 0.7, 'odds' => 2.0],
            'bet3' => ['probability' => 0.7, 'odds' => 2.0],
        ];

        $results = $this->calculator->calculateMultiple($bets);

        $totalKelly = array_sum(array_column($results, 'kelly_fraction'));
        $this->assertLessThanOrEqual(1.0, $totalKelly);
    }

    public function test_calculate_edge(): void
    {
        // 60% probability, odds 2.0 (implied 50%)
        // Edge = (0.6 - 0.5) / 0.5 * 100 = 20%
        $edge = $this->calculator->calculateEdge(0.6, 2.0);
        $this->assertEquals(20, $edge);
    }

    public function test_has_value(): void
    {
        $this->assertTrue($this->calculator->hasValue(0.6, 2.0));
        $this->assertFalse($this->calculator->hasValue(0.4, 2.0));
        $this->assertFalse($this->calculator->hasValue(0.5, 2.0));
    }

    public function test_get_minimum_odds(): void
    {
        // For 60% probability, fair odds = 1/0.6 = 1.667
        $minOdds = $this->calculator->getMinimumOdds(0.6);
        $this->assertEqualsWithDelta(1.667, $minOdds, 0.001);
    }

    public function test_get_minimum_probability(): void
    {
        // For odds 2.0, min probability = 1/2 = 0.5
        $minProb = $this->calculator->getMinimumProbability(2.0);
        $this->assertEquals(0.5, $minProb);
    }

    public function test_simulate_growth(): void
    {
        $initialBankroll = 1000;
        $bets = [
            ['probability' => 0.6, 'odds' => 2.0, 'won' => true],
            ['probability' => 0.6, 'odds' => 2.0, 'won' => false],
            ['probability' => 0.6, 'odds' => 2.0, 'won' => true],
        ];

        $result = $this->calculator->simulateGrowth($initialBankroll, $bets, 0.25);

        $this->assertArrayHasKey('final_bankroll', $result);
        $this->assertArrayHasKey('growth', $result);
        $this->assertArrayHasKey('history', $result);
        $this->assertCount(4, $result['history']); // Initial + 3 bets
    }

    public function test_get_risk_level(): void
    {
        $this->assertEquals('no_bet', $this->calculator->getRiskLevel(0));
        $this->assertEquals('very_low', $this->calculator->getRiskLevel(0.01));
        $this->assertEquals('low', $this->calculator->getRiskLevel(0.03));
        $this->assertEquals('medium', $this->calculator->getRiskLevel(0.07));
        $this->assertEquals('high', $this->calculator->getRiskLevel(0.15));
        $this->assertEquals('very_high', $this->calculator->getRiskLevel(0.25));
    }
}
