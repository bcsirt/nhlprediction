<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\Enums;

use App\Domains\Prediction\Enums\PredictionType;
use PHPUnit\Framework\TestCase;

class PredictionTypeTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = PredictionType::cases();

        $this->assertCount(7, $cases);
        $this->assertEquals('winner', PredictionType::WINNER->value);
        $this->assertEquals('over_under', PredictionType::OVER_UNDER->value);
        $this->assertEquals('spread', PredictionType::SPREAD->value);
        $this->assertEquals('exact_score', PredictionType::EXACT_SCORE->value);
        $this->assertEquals('total_goals', PredictionType::TOTAL_GOALS->value);
        $this->assertEquals('both_teams_score', PredictionType::BOTH_TEAMS_SCORE->value);
        $this->assertEquals('first_goal', PredictionType::FIRST_GOAL->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('Vainqueur', PredictionType::WINNER->label());
        $this->assertEquals('Plus/Moins', PredictionType::OVER_UNDER->label());
        $this->assertEquals('Handicap', PredictionType::SPREAD->label());
        $this->assertEquals('Score exact', PredictionType::EXACT_SCORE->label());
        $this->assertEquals('Total de buts', PredictionType::TOTAL_GOALS->label());
        $this->assertEquals('Les deux équipes marquent', PredictionType::BOTH_TEAMS_SCORE->label());
        $this->assertEquals('Premier but', PredictionType::FIRST_GOAL->label());
    }

    public function test_description_returns_non_empty_string(): void
    {
        foreach (PredictionType::cases() as $type) {
            $this->assertNotEmpty($type->description());
            $this->assertIsString($type->description());
        }
    }

    public function test_difficulty_returns_valid_range(): void
    {
        foreach (PredictionType::cases() as $type) {
            $difficulty = $type->difficulty();
            $this->assertGreaterThanOrEqual(1, $difficulty);
            $this->assertLessThanOrEqual(5, $difficulty);
        }
    }

    public function test_difficulty_values(): void
    {
        $this->assertEquals(2, PredictionType::WINNER->difficulty());
        $this->assertEquals(3, PredictionType::OVER_UNDER->difficulty());
        $this->assertEquals(3, PredictionType::SPREAD->difficulty());
        $this->assertEquals(5, PredictionType::EXACT_SCORE->difficulty());
        $this->assertEquals(3, PredictionType::TOTAL_GOALS->difficulty());
        $this->assertEquals(2, PredictionType::BOTH_TEAMS_SCORE->difficulty());
        $this->assertEquals(4, PredictionType::FIRST_GOAL->difficulty());
    }

    public function test_requires_continuous_probability(): void
    {
        $this->assertTrue(PredictionType::EXACT_SCORE->requiresContinuousProbability());
        $this->assertTrue(PredictionType::TOTAL_GOALS->requiresContinuousProbability());
        $this->assertFalse(PredictionType::WINNER->requiresContinuousProbability());
        $this->assertFalse(PredictionType::OVER_UNDER->requiresContinuousProbability());
        $this->assertFalse(PredictionType::SPREAD->requiresContinuousProbability());
    }

    public function test_is_binary(): void
    {
        $this->assertTrue(PredictionType::OVER_UNDER->isBinary());
        $this->assertTrue(PredictionType::BOTH_TEAMS_SCORE->isBinary());
        $this->assertFalse(PredictionType::WINNER->isBinary());
        $this->assertFalse(PredictionType::EXACT_SCORE->isBinary());
        $this->assertFalse(PredictionType::SPREAD->isBinary());
    }

    public function test_supported_by_model_regression(): void
    {
        $types = PredictionType::supportedByModel('regression');

        $this->assertContains(PredictionType::WINNER, $types);
        $this->assertContains(PredictionType::TOTAL_GOALS, $types);
        $this->assertContains(PredictionType::EXACT_SCORE, $types);
        $this->assertNotContains(PredictionType::OVER_UNDER, $types);
    }

    public function test_supported_by_model_classification(): void
    {
        $types = PredictionType::supportedByModel('classification');

        $this->assertContains(PredictionType::WINNER, $types);
        $this->assertContains(PredictionType::OVER_UNDER, $types);
        $this->assertContains(PredictionType::SPREAD, $types);
        $this->assertNotContains(PredictionType::EXACT_SCORE, $types);
    }

    public function test_supported_by_model_ensemble(): void
    {
        $types = PredictionType::supportedByModel('ensemble');

        // Ensemble supporte tous les types
        foreach (PredictionType::cases() as $type) {
            $this->assertContains($type, $types);
        }
    }

    public function test_supported_by_model_default(): void
    {
        $types = PredictionType::supportedByModel('unknown');

        $this->assertCount(1, $types);
        $this->assertContains(PredictionType::WINNER, $types);
    }

    public function test_exact_score_is_most_difficult(): void
    {
        $maxDifficulty = 0;
        $mostDifficult = null;

        foreach (PredictionType::cases() as $type) {
            if ($type->difficulty() > $maxDifficulty) {
                $maxDifficulty = $type->difficulty();
                $mostDifficult = $type;
            }
        }

        $this->assertEquals(PredictionType::EXACT_SCORE, $mostDifficult);
    }
}
