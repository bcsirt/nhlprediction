<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Enums;

use App\Domains\DataIngestion\Enums\GameType;
use PHPUnit\Framework\TestCase;

/**
 * Tests pour l'enum GameType
 */
class GameTypeTest extends TestCase
{
    /**
     * @test
     */
    public function it_has_all_expected_cases(): void
    {
        $cases = GameType::cases();

        $this->assertCount(4, $cases);

        $values = array_map(fn($case) => $case->value, $cases);

        $this->assertContains('PR', $values);
        $this->assertContains('R', $values);
        $this->assertContains('P', $values);
        $this->assertContains('A', $values);
    }

    /**
     * @test
     */
    public function it_can_be_created_from_string(): void
    {
        $gameType = GameType::from('R');

        $this->assertInstanceOf(GameType::class, $gameType);
        $this->assertEquals(GameType::REGULAR, $gameType);
    }

    /**
     * @test
     */
    public function label_returns_correct_french_labels(): void
    {
        $this->assertEquals('Pré-saison', GameType::PRESEASON->label());
        $this->assertEquals('Saison régulière', GameType::REGULAR->label());
        $this->assertEquals('Séries éliminatoires', GameType::PLAYOFF->label());
        $this->assertEquals('Match des étoiles', GameType::ALL_STAR->label());
    }

    /**
     * @test
     */
    public function weight_returns_correct_importance_weights(): void
    {
        $this->assertEquals(0.5, GameType::PRESEASON->weight());
        $this->assertEquals(1.0, GameType::REGULAR->weight());
        $this->assertEquals(1.2, GameType::PLAYOFF->weight());
        $this->assertEquals(0.0, GameType::ALL_STAR->weight());
    }

    /**
     * @test
     */
    public function counts_for_stats_returns_true_for_counted_game_types(): void
    {
        $this->assertTrue(GameType::REGULAR->countsForStats());
        $this->assertTrue(GameType::PLAYOFF->countsForStats());
    }

    /**
     * @test
     */
    public function counts_for_stats_returns_false_for_non_counted_game_types(): void
    {
        $this->assertFalse(GameType::PRESEASON->countsForStats());
        $this->assertFalse(GameType::ALL_STAR->countsForStats());
    }

    /**
     * @test
     */
    public function is_regular_season_returns_true_only_for_regular(): void
    {
        $this->assertTrue(GameType::REGULAR->isRegularSeason());

        $this->assertFalse(GameType::PRESEASON->isRegularSeason());
        $this->assertFalse(GameType::PLAYOFF->isRegularSeason());
        $this->assertFalse(GameType::ALL_STAR->isRegularSeason());
    }

    /**
     * @test
     */
    public function is_playoff_returns_true_only_for_playoff(): void
    {
        $this->assertTrue(GameType::PLAYOFF->isPlayoff());

        $this->assertFalse(GameType::PRESEASON->isPlayoff());
        $this->assertFalse(GameType::REGULAR->isPlayoff());
        $this->assertFalse(GameType::ALL_STAR->isPlayoff());
    }

    /**
     * @test
     */
    public function game_types_are_ordered_by_weight(): void
    {
        $types = [
            GameType::PLAYOFF,
            GameType::REGULAR,
            GameType::PRESEASON,
            GameType::ALL_STAR,
        ];

        usort($types, fn($a, $b) => $b->weight() <=> $a->weight());

        $this->assertEquals(GameType::PLAYOFF, $types[0]);
        $this->assertEquals(GameType::REGULAR, $types[1]);
        $this->assertEquals(GameType::PRESEASON, $types[2]);
        $this->assertEquals(GameType::ALL_STAR, $types[3]);
    }
}
