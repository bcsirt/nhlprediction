<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Enums;

use App\Domains\ValueBets\Enums\BetType;
use PHPUnit\Framework\TestCase;

class BetTypeTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = BetType::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('moneyline', BetType::MONEYLINE->value);
        $this->assertEquals('spread', BetType::SPREAD->value);
        $this->assertEquals('over_under', BetType::OVER_UNDER->value);
        $this->assertEquals('prop', BetType::PROP->value);
        $this->assertEquals('parlay', BetType::PARLAY->value);
        $this->assertEquals('teaser', BetType::TEASER->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('Moneyline', BetType::MONEYLINE->label());
        $this->assertEquals('Handicap', BetType::SPREAD->label());
        $this->assertEquals('Plus/Moins', BetType::OVER_UNDER->label());
        $this->assertEquals('Proposition', BetType::PROP->label());
        $this->assertEquals('Combiné', BetType::PARLAY->label());
    }

    public function test_description_returns_non_empty_string(): void
    {
        foreach (BetType::cases() as $type) {
            $this->assertNotEmpty($type->description());
            $this->assertIsString($type->description());
        }
    }

    public function test_requires_line(): void
    {
        $this->assertFalse(BetType::MONEYLINE->requiresLine());
        $this->assertTrue(BetType::SPREAD->requiresLine());
        $this->assertTrue(BetType::OVER_UNDER->requiresLine());
        $this->assertFalse(BetType::PROP->requiresLine());
        $this->assertFalse(BetType::PARLAY->requiresLine());
    }

    public function test_is_single_bet(): void
    {
        $this->assertTrue(BetType::MONEYLINE->isSingleBet());
        $this->assertTrue(BetType::SPREAD->isSingleBet());
        $this->assertTrue(BetType::OVER_UNDER->isSingleBet());
        $this->assertTrue(BetType::PROP->isSingleBet());
        $this->assertFalse(BetType::PARLAY->isSingleBet());
        $this->assertFalse(BetType::TEASER->isSingleBet());
    }

    public function test_is_multiple_bet(): void
    {
        $this->assertFalse(BetType::MONEYLINE->isMultipleBet());
        $this->assertTrue(BetType::PARLAY->isMultipleBet());
        $this->assertTrue(BetType::TEASER->isMultipleBet());
    }

    public function test_risk_level_returns_valid_range(): void
    {
        foreach (BetType::cases() as $type) {
            $risk = $type->riskLevel();
            $this->assertGreaterThanOrEqual(1, $risk);
            $this->assertLessThanOrEqual(5, $risk);
        }
    }

    public function test_parlay_has_highest_risk(): void
    {
        $this->assertEquals(5, BetType::PARLAY->riskLevel());
    }

    public function test_possible_selections(): void
    {
        $moneylineSelections = BetType::MONEYLINE->possibleSelections();
        $this->assertContains('home', $moneylineSelections);
        $this->assertContains('away', $moneylineSelections);

        $overUnderSelections = BetType::OVER_UNDER->possibleSelections();
        $this->assertContains('over', $overUnderSelections);
        $this->assertContains('under', $overUnderSelections);
    }
}
