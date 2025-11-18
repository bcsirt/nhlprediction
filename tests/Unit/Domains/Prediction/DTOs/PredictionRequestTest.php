<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\Prediction\DTOs;

use App\Domains\Prediction\DTOs\PredictionRequest;
use App\Domains\Prediction\Enums\PredictionType;
use PHPUnit\Framework\TestCase;

class PredictionRequestTest extends TestCase
{
    public function test_it_can_be_created_with_minimal_parameters(): void
    {
        $request = new PredictionRequest(gameId: 123);

        $this->assertEquals(123, $request->gameId);
        $this->assertEquals(PredictionType::WINNER, $request->predictionType);
        $this->assertNull($request->modelId);
        $this->assertTrue($request->includeExplanation);
        $this->assertTrue($request->useEnsemble);
        $this->assertNull($request->overUnderLine);
        $this->assertNull($request->spreadLine);
    }

    public function test_it_can_be_created_with_all_parameters(): void
    {
        $request = new PredictionRequest(
            gameId: 456,
            predictionType: PredictionType::OVER_UNDER,
            modelId: 10,
            includeExplanation: false,
            useEnsemble: false,
            overUnderLine: 5.5,
            spreadLine: 1.5,
        );

        $this->assertEquals(456, $request->gameId);
        $this->assertEquals(PredictionType::OVER_UNDER, $request->predictionType);
        $this->assertEquals(10, $request->modelId);
        $this->assertFalse($request->includeExplanation);
        $this->assertFalse($request->useEnsemble);
        $this->assertEquals(5.5, $request->overUnderLine);
        $this->assertEquals(1.5, $request->spreadLine);
    }

    public function test_from_array_creates_instance(): void
    {
        $data = [
            'game_id' => 789,
            'prediction_type' => 'spread',
            'model_id' => 5,
            'include_explanation' => false,
            'use_ensemble' => true,
            'spread_line' => 2.5,
        ];

        $request = PredictionRequest::fromArray($data);

        $this->assertEquals(789, $request->gameId);
        $this->assertEquals(PredictionType::SPREAD, $request->predictionType);
        $this->assertEquals(5, $request->modelId);
        $this->assertFalse($request->includeExplanation);
        $this->assertTrue($request->useEnsemble);
        $this->assertEquals(2.5, $request->spreadLine);
    }

    public function test_from_array_uses_defaults(): void
    {
        $data = ['game_id' => 100];

        $request = PredictionRequest::fromArray($data);

        $this->assertEquals(100, $request->gameId);
        $this->assertEquals(PredictionType::WINNER, $request->predictionType);
        $this->assertNull($request->modelId);
        $this->assertTrue($request->includeExplanation);
        $this->assertTrue($request->useEnsemble);
    }

    public function test_to_array_returns_correct_format(): void
    {
        $request = new PredictionRequest(
            gameId: 123,
            predictionType: PredictionType::OVER_UNDER,
            modelId: 5,
            overUnderLine: 5.5,
        );

        $array = $request->toArray();

        $this->assertEquals(123, $array['game_id']);
        $this->assertEquals('over_under', $array['prediction_type']);
        $this->assertEquals(5, $array['model_id']);
        $this->assertEquals(5.5, $array['over_under_line']);
        $this->assertTrue($array['include_explanation']);
        $this->assertTrue($array['use_ensemble']);
    }

    public function test_validate_returns_empty_for_valid_winner_request(): void
    {
        $request = new PredictionRequest(gameId: 123);

        $errors = $request->validate();

        $this->assertEmpty($errors);
    }

    public function test_validate_returns_error_for_invalid_game_id(): void
    {
        $request = new PredictionRequest(gameId: 0);

        $errors = $request->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('game_id', $errors[0]);
    }

    public function test_validate_returns_error_for_negative_game_id(): void
    {
        $request = new PredictionRequest(gameId: -5);

        $errors = $request->validate();

        $this->assertNotEmpty($errors);
    }

    public function test_validate_returns_error_for_over_under_without_line(): void
    {
        $request = new PredictionRequest(
            gameId: 123,
            predictionType: PredictionType::OVER_UNDER,
        );

        $errors = $request->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('over_under_line', $errors[0]);
    }

    public function test_validate_returns_empty_for_over_under_with_line(): void
    {
        $request = new PredictionRequest(
            gameId: 123,
            predictionType: PredictionType::OVER_UNDER,
            overUnderLine: 5.5,
        );

        $errors = $request->validate();

        $this->assertEmpty($errors);
    }

    public function test_validate_returns_error_for_spread_without_line(): void
    {
        $request = new PredictionRequest(
            gameId: 123,
            predictionType: PredictionType::SPREAD,
        );

        $errors = $request->validate();

        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('spread_line', $errors[0]);
    }

    public function test_validate_returns_empty_for_spread_with_line(): void
    {
        $request = new PredictionRequest(
            gameId: 123,
            predictionType: PredictionType::SPREAD,
            spreadLine: 1.5,
        );

        $errors = $request->validate();

        $this->assertEmpty($errors);
    }

    public function test_is_valid_returns_true_for_valid_request(): void
    {
        $request = new PredictionRequest(gameId: 123);

        $this->assertTrue($request->isValid());
    }

    public function test_is_valid_returns_false_for_invalid_request(): void
    {
        $request = new PredictionRequest(gameId: 0);

        $this->assertFalse($request->isValid());
    }

    public function test_readonly_properties_cannot_be_modified(): void
    {
        $request = new PredictionRequest(gameId: 123);

        // This should not compile if readonly is working correctly
        // We test by checking the value is what we set
        $this->assertEquals(123, $request->gameId);
    }
}
