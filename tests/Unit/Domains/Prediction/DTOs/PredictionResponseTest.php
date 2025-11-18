<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\DTOs;

use App\Domains\Prediction\DTOs\PredictionResponse;
use App\Domains\Prediction\Enums\ConfidenceLevel;
use App\Domains\Prediction\Enums\PredictionType;
use PHPUnit\Framework\TestCase;

class PredictionResponseTest extends TestCase
{
    public function test_it_can_be_created_with_required_parameters(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.65,
            confidenceScore: 75,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $this->assertEquals(123, $response->gameId);
        $this->assertEquals(PredictionType::WINNER, $response->predictionType);
        $this->assertEquals('home', $response->predictedOutcome);
        $this->assertEquals(0.65, $response->probability);
        $this->assertEquals(75, $response->confidenceScore);
        $this->assertEquals(ConfidenceLevel::HIGH, $response->confidenceLevel);
    }

    public function test_it_can_be_created_with_all_parameters(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
            explanation: 'Team A is favored',
            probabilities: ['home' => 0.7, 'away' => 0.3],
            modelScores: ['model1' => ['outcome' => 'home', 'probability' => 0.7]],
            factors: [['name' => 'Home Ice', 'impact' => 5]],
            expectedValue: 0.15,
            kellyFraction: 0.25,
            predictionId: 456,
            modelName: 'ensemble',
        );

        $this->assertEquals('Team A is favored', $response->explanation);
        $this->assertEquals(0.15, $response->expectedValue);
        $this->assertEquals(456, $response->predictionId);
    }

    public function test_error_creates_error_response(): void
    {
        $response = PredictionResponse::error(
            123,
            PredictionType::WINNER,
            'Game not found'
        );

        $this->assertEquals(123, $response->gameId);
        $this->assertEquals('unknown', $response->predictedOutcome);
        $this->assertEquals(0.5, $response->probability);
        $this->assertEquals(0, $response->confidenceScore);
        $this->assertEquals(ConfidenceLevel::VERY_LOW, $response->confidenceLevel);
        $this->assertEquals('Game not found', $response->error);
    }

    public function test_from_array_creates_instance(): void
    {
        $data = [
            'game_id' => 789,
            'prediction_type' => 'winner',
            'predicted_outcome' => 'away',
            'probability' => 0.55,
            'confidence_score' => 60,
            'confidence_level' => 'medium',
            'explanation' => 'Close game',
        ];

        $response = PredictionResponse::fromArray($data);

        $this->assertEquals(789, $response->gameId);
        $this->assertEquals('away', $response->predictedOutcome);
        $this->assertEquals(0.55, $response->probability);
        $this->assertEquals(ConfidenceLevel::MEDIUM, $response->confidenceLevel);
    }

    public function test_to_array_returns_complete_data(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $array = $response->toArray();

        $this->assertEquals(123, $array['game_id']);
        $this->assertEquals('winner', $array['prediction_type']);
        $this->assertEquals('home', $array['predicted_outcome']);
        $this->assertEquals(0.7, $array['probability']);
        $this->assertEquals(80, $array['confidence_score']);
        $this->assertEquals('high', $array['confidence_level']);
        $this->assertArrayHasKey('confidence_label', $array);
        $this->assertArrayHasKey('should_bet', $array);
    }

    public function test_is_successful_returns_true_without_error(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $this->assertTrue($response->isSuccessful());
    }

    public function test_is_successful_returns_false_with_error(): void
    {
        $response = PredictionResponse::error(123, PredictionType::WINNER, 'Error');

        $this->assertFalse($response->isSuccessful());
    }

    public function test_should_bet_returns_true_for_high_confidence_positive_ev(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
            expectedValue: 0.1,
        );

        $this->assertTrue($response->shouldBet());
    }

    public function test_should_bet_returns_false_for_low_confidence(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.55,
            confidenceScore: 40,
            confidenceLevel: ConfidenceLevel::LOW,
            expectedValue: 0.1,
        );

        $this->assertFalse($response->shouldBet());
    }

    public function test_should_bet_returns_false_without_expected_value(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $this->assertFalse($response->shouldBet());
    }

    public function test_should_bet_returns_false_for_negative_ev(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
            expectedValue: -0.05,
        );

        $this->assertFalse($response->shouldBet());
    }

    public function test_get_recommended_bet_fraction(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
            expectedValue: 0.1,
            kellyFraction: 0.25,
        );

        $this->assertEquals(0.25, $response->getRecommendedBetFraction());
    }

    public function test_get_recommended_bet_fraction_returns_zero_when_should_not_bet(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.55,
            confidenceScore: 40,
            confidenceLevel: ConfidenceLevel::LOW,
        );

        $this->assertEquals(0, $response->getRecommendedBetFraction());
    }

    public function test_get_summary(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $summary = $response->getSummary();

        $this->assertStringContainsString('home', $summary);
        $this->assertStringContainsString('70', $summary);
        $this->assertStringContainsString(ConfidenceLevel::HIGH->label(), $summary);
    }

    public function test_get_summary_with_error(): void
    {
        $response = PredictionResponse::error(123, PredictionType::WINNER, 'Test error');

        $summary = $response->getSummary();

        $this->assertStringContainsString('Erreur', $summary);
        $this->assertStringContainsString('Test error', $summary);
    }

    public function test_get_key_factors_returns_sorted_by_impact(): void
    {
        $factors = [
            ['name' => 'Factor A', 'impact' => 3],
            ['name' => 'Factor B', 'impact' => 8],
            ['name' => 'Factor C', 'impact' => -10],
            ['name' => 'Factor D', 'impact' => 5],
        ];

        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
            factors: $factors,
        );

        $keyFactors = $response->getKeyFactors(3);

        $this->assertCount(3, $keyFactors);
        // Should be sorted by absolute impact: C(10), B(8), D(5)
        $this->assertEquals('Factor C', $keyFactors[0]['name']);
        $this->assertEquals('Factor B', $keyFactors[1]['name']);
        $this->assertEquals('Factor D', $keyFactors[2]['name']);
    }

    public function test_get_key_factors_returns_empty_without_factors(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $this->assertEmpty($response->getKeyFactors());
    }

    public function test_to_json_returns_valid_json(): void
    {
        $response = new PredictionResponse(
            gameId: 123,
            predictionType: PredictionType::WINNER,
            predictedOutcome: 'home',
            probability: 0.7,
            confidenceScore: 80,
            confidenceLevel: ConfidenceLevel::HIGH,
        );

        $json = $response->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertEquals(123, $decoded['game_id']);
    }
}
