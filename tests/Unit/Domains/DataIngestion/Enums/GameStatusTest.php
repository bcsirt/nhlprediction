<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Enums;

use App\Domains\DataIngestion\Enums\GameStatus;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'enum GameStatus
 */
class GameStatusTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_all_expected_cases(): void
    {
        $cases = GameStatus::cases();

        $this->assertCount(7, $cases);

        $values = array_map(fn($case) => $case->value, $cases);

        $this->assertContains('scheduled', $values);
        $this->assertContains('postponed', $values);
        $this->assertContains('live', $values);
        $this->assertContains('final', $values);
        $this->assertContains('final_ot', $values);
        $this->assertContains('final_so', $values);
        $this->assertContains('cancelled', $values);
    }

    /**
     * @test
     */
    public function it_can_be_created_from_string(): void
    {
        $status = GameStatus::from('scheduled');

        $this->assertInstanceOf(GameStatus::class, $status);
        $this->assertEquals(GameStatus::SCHEDULED, $status);
    }

    /**
     * @test
     */
    public function it_can_try_from_string(): void
    {
        $validStatus = GameStatus::tryFrom('live');
        $this->assertEquals(GameStatus::LIVE, $validStatus);

        $invalidStatus = GameStatus::tryFrom('invalid_status');
        $this->assertNull($invalidStatus);
    }

    /**
     * @test
     */
    public function is_finished_returns_true_for_final_statuses(): void
    {
        $this->assertTrue(GameStatus::FINAL->isFinished());
        $this->assertTrue(GameStatus::FINAL_OT->isFinished());
        $this->assertTrue(GameStatus::FINAL_SO->isFinished());
    }

    /**
     * @test
     */
    public function is_finished_returns_false_for_non_final_statuses(): void
    {
        $this->assertFalse(GameStatus::SCHEDULED->isFinished());
        $this->assertFalse(GameStatus::POSTPONED->isFinished());
        $this->assertFalse(GameStatus::LIVE->isFinished());
        $this->assertFalse(GameStatus::CANCELLED->isFinished());
    }

    /**
     * @test
     */
    public function is_live_returns_true_only_for_live_status(): void
    {
        $this->assertTrue(GameStatus::LIVE->isLive());

        $this->assertFalse(GameStatus::SCHEDULED->isLive());
        $this->assertFalse(GameStatus::FINAL->isLive());
        $this->assertFalse(GameStatus::POSTPONED->isLive());
    }

    /**
     * @test
     */
    public function is_scheduled_returns_true_only_for_scheduled_status(): void
    {
        $this->assertTrue(GameStatus::SCHEDULED->isScheduled());

        $this->assertFalse(GameStatus::LIVE->isScheduled());
        $this->assertFalse(GameStatus::FINAL->isScheduled());
        $this->assertFalse(GameStatus::POSTPONED->isScheduled());
    }

    /**
     * @test
     */
    public function label_returns_correct_french_labels(): void
    {
        $this->assertEquals('Programmé', GameStatus::SCHEDULED->label());
        $this->assertEquals('Reporté', GameStatus::POSTPONED->label());
        $this->assertEquals('En cours', GameStatus::LIVE->label());
        $this->assertEquals('Terminé', GameStatus::FINAL->label());
        $this->assertEquals('Terminé (Prolongation)', GameStatus::FINAL_OT->label());
        $this->assertEquals('Terminé (Tirs de barrage)', GameStatus::FINAL_SO->label());
        $this->assertEquals('Annulé', GameStatus::CANCELLED->label());
    }

    /**
     * @test
     */
    public function color_returns_appropriate_colors(): void
    {
        $this->assertEquals('gray', GameStatus::SCHEDULED->color());
        $this->assertEquals('yellow', GameStatus::POSTPONED->color());
        $this->assertEquals('green', GameStatus::LIVE->color());
        $this->assertEquals('blue', GameStatus::FINAL->color());
        $this->assertEquals('blue', GameStatus::FINAL_OT->color());
        $this->assertEquals('blue', GameStatus::FINAL_SO->color());
        $this->assertEquals('red', GameStatus::CANCELLED->color());
    }

}
