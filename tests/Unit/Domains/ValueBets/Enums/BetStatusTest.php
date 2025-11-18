<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\ValueBets\Enums;

use App\Domains\ValueBets\Enums\BetStatus;
use PHPUnit\Framework\TestCase;

class BetStatusTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = BetStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertEquals('pending', BetStatus::PENDING->value);
        $this->assertEquals('won', BetStatus::WON->value);
        $this->assertEquals('lost', BetStatus::LOST->value);
        $this->assertEquals('push', BetStatus::PUSH->value);
        $this->assertEquals('cancelled', BetStatus::CANCELLED->value);
        $this->assertEquals('void', BetStatus::VOID->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('En attente', BetStatus::PENDING->label());
        $this->assertEquals('Gagné', BetStatus::WON->label());
        $this->assertEquals('Perdu', BetStatus::LOST->label());
        $this->assertEquals('Égalité', BetStatus::PUSH->label());
        $this->assertEquals('Annulé', BetStatus::CANCELLED->label());
        $this->assertEquals('Nul', BetStatus::VOID->label());
    }

    public function test_color_returns_correct_colors(): void
    {
        $this->assertEquals('yellow', BetStatus::PENDING->color());
        $this->assertEquals('green', BetStatus::WON->color());
        $this->assertEquals('red', BetStatus::LOST->color());
        $this->assertEquals('gray', BetStatus::PUSH->color());
    }

    public function test_icon_returns_correct_icons(): void
    {
        $this->assertEquals('⏳', BetStatus::PENDING->icon());
        $this->assertEquals('✅', BetStatus::WON->icon());
        $this->assertEquals('❌', BetStatus::LOST->icon());
        $this->assertEquals('🔄', BetStatus::PUSH->icon());
    }

    public function test_is_settled(): void
    {
        $this->assertFalse(BetStatus::PENDING->isSettled());
        $this->assertTrue(BetStatus::WON->isSettled());
        $this->assertTrue(BetStatus::LOST->isSettled());
        $this->assertTrue(BetStatus::PUSH->isSettled());
        $this->assertFalse(BetStatus::CANCELLED->isSettled());
        $this->assertTrue(BetStatus::VOID->isSettled());
    }

    public function test_counts_for_stats(): void
    {
        $this->assertFalse(BetStatus::PENDING->countsForStats());
        $this->assertTrue(BetStatus::WON->countsForStats());
        $this->assertTrue(BetStatus::LOST->countsForStats());
        $this->assertFalse(BetStatus::PUSH->countsForStats());
        $this->assertFalse(BetStatus::CANCELLED->countsForStats());
    }

    public function test_is_win(): void
    {
        $this->assertTrue(BetStatus::WON->isWin());
        $this->assertFalse(BetStatus::LOST->isWin());
        $this->assertFalse(BetStatus::PENDING->isWin());
        $this->assertFalse(BetStatus::PUSH->isWin());
    }

    public function test_profit_multiplier(): void
    {
        $this->assertEquals(1.0, BetStatus::WON->profitMultiplier());
        $this->assertEquals(-1.0, BetStatus::LOST->profitMultiplier());
        $this->assertEquals(0.0, BetStatus::PUSH->profitMultiplier());
        $this->assertEquals(0.0, BetStatus::VOID->profitMultiplier());
        $this->assertEquals(0.0, BetStatus::PENDING->profitMultiplier());
    }
}
