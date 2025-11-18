<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Services;

use App\Domains\ValueBets\Services\EVCalculator;
use PHPUnit\Framework\TestCase;

class EVCalculatorTest extends TestCase
{
    private EVCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new EVCalculator();
    }

    public function test_calculate_returns_positive_ev(): void
    {
        // 60% probability, odds 2.0, stake 100
        // EV = (0.6 * 100) - (0.4 * 100) = 60 - 40 = 20
        $ev = $this->calculator->calculate(0.6, 2.0, 100);
        $this->assertEquals(20, $ev);
    }

    public function test_calculate_returns_negative_ev(): void
    {
        // 40% probability, odds 2.0, stake 100
        // EV = (0.4 * 100) - (0.6 * 100) = 40 - 60 = -20
        $ev = $this->calculator->calculate(0.4, 2.0, 100);
        $this->assertEquals(-20, $ev);
    }

    public function test_calculate_returns_zero_ev_at_fair_odds(): void
    {
        // 50% probability, odds 2.0
        $ev = $this->calculator->calculate(0.5, 2.0, 100);
        $this->assertEquals(0, $ev);
    }

    public function test_calculate_percentage(): void
    {
        // 60% probability, odds 2.0
        // EV% = 20%
        $evPercentage = $this->calculator->calculatePercentage(0.6, 2.0);
        $this->assertEquals(20, $evPercentage);
    }

    public function test_calculate_with_margin(): void
    {
        $result = $this->calculator->calculateWithMargin(0.55, 1.90, 1.90);

        $this->assertArrayHasKey('ev', $result);
        $this->assertArrayHasKey('ev_percentage', $result);
        $this->assertArrayHasKey('margin', $result);
        $this->assertArrayHasKey('fair_odds', $result);
        $this->assertArrayHasKey('fair_probability', $result);

        // Margin should be around 5.26% for 1.90/1.90
        $this->assertGreaterThan(5, $result['margin']);
    }

    public function test_calculate_over_under(): void
    {
        $result = $this->calculator->calculateOverUnder(0.55, 1.90, 1.90);

        $this->assertArrayHasKey('over', $result);
        $this->assertArrayHasKey('under', $result);
        $this->assertArrayHasKey('best_bet', $result);

        $this->assertEquals(0.55, $result['over']['probability']);
        $this->assertEquals(0.45, $result['under']['probability']);
    }

    public function test_calculate_spread(): void
    {
        $result = $this->calculator->calculateSpread(0.6, 1.90, 1.90, -1.5);

        $this->assertArrayHasKey('home', $result);
        $this->assertArrayHasKey('away', $result);
        $this->assertArrayHasKey('best_bet', $result);

        $this->assertEquals(-1.5, $result['home']['spread']);
        $this->assertEquals(1.5, $result['away']['spread']);
    }

    public function test_get_breakeven_odds(): void
    {
        // For 60% probability, breakeven odds = 1/0.6 = 1.667
        $odds = $this->calculator->getBreakevenOdds(0.6);
        $this->assertEqualsWithDelta(1.667, $odds, 0.001);
    }

    public function test_is_positive_ev(): void
    {
        $this->assertTrue($this->calculator->isPositiveEV(0.6, 2.0));
        $this->assertFalse($this->calculator->isPositiveEV(0.4, 2.0));
        $this->assertFalse($this->calculator->isPositiveEV(0.5, 2.0));
    }

    public function test_compare_opportunities(): void
    {
        $opportunities = [
            ['name' => 'Bet A', 'probability' => 0.6, 'odds' => 2.0],
            ['name' => 'Bet B', 'probability' => 0.55, 'odds' => 2.2],
            ['name' => 'Bet C', 'probability' => 0.4, 'odds' => 2.0],
        ];

        $results = $this->calculator->compareOpportunities($opportunities);

        $this->assertCount(3, $results);
        // Should be sorted by EV descending
        $this->assertGreaterThanOrEqual($results[1]['ev'], $results[0]['ev']);
        $this->assertArrayHasKey('is_value', $results[0]);
    }

    public function test_calculate_parlay(): void
    {
        $legs = [
            ['probability' => 0.6, 'odds' => 2.0],
            ['probability' => 0.7, 'odds' => 1.5],
        ];

        $result = $this->calculator->calculateParlay($legs);

        $this->assertArrayHasKey('combined_probability', $result);
        $this->assertArrayHasKey('combined_odds', $result);
        $this->assertArrayHasKey('ev', $result);
        $this->assertArrayHasKey('legs_count', $result);

        // Combined probability = 0.6 * 0.7 = 0.42
        $this->assertEqualsWithDelta(0.42, $result['combined_probability'], 0.001);
        // Combined odds = 2.0 * 1.5 = 3.0
        $this->assertEquals(3.0, $result['combined_odds']);
        $this->assertEquals(2, $result['legs_count']);
    }

    public function test_calculate_expected_roi(): void
    {
        $bets = [
            ['probability' => 0.6, 'odds' => 2.0, 'stake' => 100],
            ['probability' => 0.55, 'odds' => 2.0, 'stake' => 100],
        ];

        $result = $this->calculator->calculateExpectedROI($bets);

        $this->assertArrayHasKey('total_staked', $result);
        $this->assertArrayHasKey('total_ev', $result);
        $this->assertArrayHasKey('expected_roi', $result);
        $this->assertArrayHasKey('bets_count', $result);

        $this->assertEquals(200, $result['total_staked']);
        $this->assertEquals(2, $result['bets_count']);
    }

    public function test_ev_increases_with_higher_probability(): void
    {
        $ev1 = $this->calculator->calculate(0.5, 2.0);
        $ev2 = $this->calculator->calculate(0.6, 2.0);
        $ev3 = $this->calculator->calculate(0.7, 2.0);

        $this->assertLessThan($ev2, $ev1);
        $this->assertLessThan($ev3, $ev2);
    }

    public function test_ev_increases_with_higher_odds(): void
    {
        $ev1 = $this->calculator->calculate(0.6, 1.8);
        $ev2 = $this->calculator->calculate(0.6, 2.0);
        $ev3 = $this->calculator->calculate(0.6, 2.2);

        $this->assertLessThan($ev2, $ev1);
        $this->assertLessThan($ev3, $ev2);
    }
}
