<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Enums;

use App\Domains\DataIngestion\Enums\PlayerPosition;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'enum PlayerPosition
 */
class PlayerPositionTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_all_expected_cases(): void
    {
        $cases = PlayerPosition::cases();

        $this->assertCount(5, $cases);

        $values = array_map(fn($case) => $case->value, $cases);

        $this->assertContains('C', $values);
        $this->assertContains('LW', $values);
        $this->assertContains('RW', $values);
        $this->assertContains('D', $values);
        $this->assertContains('G', $values);
    }

    /**
     * @test
     */
    public function it_can_be_created_from_string(): void
    {
        $position = PlayerPosition::from('C');

        $this->assertInstanceOf(PlayerPosition::class, $position);
        $this->assertEquals(PlayerPosition::CENTER, $position);
    }

    /**
     * @test
     */
    public function label_returns_correct_french_labels(): void
    {
        $this->assertEquals('Centre', PlayerPosition::CENTER->label());
        $this->assertEquals('Ailier gauche', PlayerPosition::LEFT_WING->label());
        $this->assertEquals('Ailier droit', PlayerPosition::RIGHT_WING->label());
        $this->assertEquals('Défenseur', PlayerPosition::DEFENSE->label());
        $this->assertEquals('Gardien', PlayerPosition::GOALIE->label());
    }

    /**
     * @test
     */
    public function is_forward_returns_true_for_forwards(): void
    {
        $this->assertTrue(PlayerPosition::CENTER->isForward());
        $this->assertTrue(PlayerPosition::LEFT_WING->isForward());
        $this->assertTrue(PlayerPosition::RIGHT_WING->isForward());
    }

    /**
     * @test
     */
    public function is_forward_returns_false_for_non_forwards(): void
    {
        $this->assertFalse(PlayerPosition::DEFENSE->isForward());
        $this->assertFalse(PlayerPosition::GOALIE->isForward());
    }

    /**
     * @test
     */
    public function is_defense_returns_true_only_for_defense(): void
    {
        $this->assertTrue(PlayerPosition::DEFENSE->isDefense());

        $this->assertFalse(PlayerPosition::CENTER->isDefense());
        $this->assertFalse(PlayerPosition::LEFT_WING->isDefense());
        $this->assertFalse(PlayerPosition::RIGHT_WING->isDefense());
        $this->assertFalse(PlayerPosition::GOALIE->isDefense());
    }

    /**
     * @test
     */
    public function is_goalie_returns_true_only_for_goalie(): void
    {
        $this->assertTrue(PlayerPosition::GOALIE->isGoalie());

        $this->assertFalse(PlayerPosition::CENTER->isGoalie());
        $this->assertFalse(PlayerPosition::LEFT_WING->isGoalie());
        $this->assertFalse(PlayerPosition::RIGHT_WING->isGoalie());
        $this->assertFalse(PlayerPosition::DEFENSE->isGoalie());
    }


    /**
     * @test
     */
    public function from_nhl_code_maps_api_values_correctly(): void
    {
        // Forwards
        $this->assertEquals(PlayerPosition::CENTER, PlayerPosition::fromNHLCode('C'));
        $this->assertEquals(PlayerPosition::LEFT_WING, PlayerPosition::fromNHLCode('L'));
        $this->assertEquals(PlayerPosition::LEFT_WING, PlayerPosition::fromNHLCode('LW'));
        $this->assertEquals(PlayerPosition::RIGHT_WING, PlayerPosition::fromNHLCode('R'));
        $this->assertEquals(PlayerPosition::RIGHT_WING, PlayerPosition::fromNHLCode('RW'));

        // Defense
        $this->assertEquals(PlayerPosition::DEFENSE, PlayerPosition::fromNHLCode('D'));

        // Goalie
        $this->assertEquals(PlayerPosition::GOALIE, PlayerPosition::fromNHLCode('G'));
    }

    /**
     * @test
     */
    public function from_nhl_code_returns_center_for_unknown_values(): void
    {
        $this->assertEquals(PlayerPosition::CENTER, PlayerPosition::fromNHLCode('X'));
        $this->assertEquals(PlayerPosition::CENTER, PlayerPosition::fromNHLCode(''));
        $this->assertEquals(PlayerPosition::CENTER, PlayerPosition::fromNHLCode('Unknown'));
    }

    /**
     * @test
     */
    public function from_nhl_code_is_case_insensitive(): void
    {
        $this->assertEquals(PlayerPosition::CENTER, PlayerPosition::fromNHLCode('c'));
        $this->assertEquals(PlayerPosition::LEFT_WING, PlayerPosition::fromNHLCode('lw'));
        $this->assertEquals(PlayerPosition::DEFENSE, PlayerPosition::fromNHLCode('d'));
        $this->assertEquals(PlayerPosition::GOALIE, PlayerPosition::fromNHLCode('g'));
    }

}
