<?php

declare(strict_types=1);

namespace App\Domains\Prediction\DTOs;

use App\Domains\Prediction\Enums\ConfidenceLevel;
use App\Domains\Prediction\Enums\PredictionType;

/**
 * DTO pour une réponse de prédiction.
 */
readonly class PredictionResponse
{
    public function __construct(
        public int $gameId,
        public PredictionType $predictionType,
        public string $predictedOutcome,
        public float $probability,
        public float $confidenceScore,
        public ConfidenceLevel $confidenceLevel,
        public ?string $explanation = null,
        public array $probabilities = [],
        public array $modelScores = [],
        public array $factors = [],
        public ?float $expectedValue = null,
        public ?float $kellyFraction = null,
        public ?int $predictionId = null,
        public ?string $modelName = null,
        public ?string $error = null,
    ) {}

    /**
     * Créer une réponse d'erreur.
     */
    public static function error(int $gameId, PredictionType $type, string $error): self
    {
        return new self(
            gameId: $gameId,
            predictionType: $type,
            predictedOutcome: 'unknown',
            probability: 0.5,
            confidenceScore: 0,
            confidenceLevel: ConfidenceLevel::VERY_LOW,
            error: $error,
        );
    }

    /**
     * Créer depuis un tableau.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            gameId: (int) $data['game_id'],
            predictionType: PredictionType::from($data['prediction_type']),
            predictedOutcome: $data['predicted_outcome'],
            probability: (float) $data['probability'],
            confidenceScore: (float) $data['confidence_score'],
            confidenceLevel: ConfidenceLevel::from($data['confidence_level']),
            explanation: $data['explanation'] ?? null,
            probabilities: $data['probabilities'] ?? [],
            modelScores: $data['model_scores'] ?? [],
            factors: $data['factors'] ?? [],
            expectedValue: isset($data['expected_value']) ? (float) $data['expected_value'] : null,
            kellyFraction: isset($data['kelly_fraction']) ? (float) $data['kelly_fraction'] : null,
            predictionId: isset($data['prediction_id']) ? (int) $data['prediction_id'] : null,
            modelName: $data['model_name'] ?? null,
            error: $data['error'] ?? null,
        );
    }

    /**
     * Convertir en tableau.
     */
    public function toArray(): array
    {
        return [
            'game_id' => $this->gameId,
            'prediction_type' => $this->predictionType->value,
            'predicted_outcome' => $this->predictedOutcome,
            'probability' => $this->probability,
            'confidence_score' => $this->confidenceScore,
            'confidence_level' => $this->confidenceLevel->value,
            'confidence_label' => $this->confidenceLevel->label(),
            'explanation' => $this->explanation,
            'probabilities' => $this->probabilities,
            'model_scores' => $this->modelScores,
            'factors' => $this->factors,
            'expected_value' => $this->expectedValue,
            'kelly_fraction' => $this->kellyFraction,
            'prediction_id' => $this->predictionId,
            'model_name' => $this->modelName,
            'should_bet' => $this->shouldBet(),
            'error' => $this->error,
        ];
    }

    /**
     * Vérifier si la prédiction a réussi.
     */
    public function isSuccessful(): bool
    {
        return $this->error === null;
    }

    /**
     * Vérifier si un pari est recommandé.
     */
    public function shouldBet(): bool
    {
        return $this->confidenceLevel->shouldBet() && $this->expectedValue !== null && $this->expectedValue > 0;
    }

    /**
     * Obtenir la fraction de Kelly recommandée.
     */
    public function getRecommendedBetFraction(): float
    {
        if (!$this->shouldBet()) {
            return 0;
        }

        return $this->kellyFraction ?? $this->confidenceLevel->kellyFraction();
    }

    /**
     * Obtenir un résumé court de la prédiction.
     */
    public function getSummary(): string
    {
        if ($this->error) {
            return "Erreur: {$this->error}";
        }

        $probability = round($this->probability * 100, 1);
        return "{$this->predictedOutcome} ({$probability}% - {$this->confidenceLevel->label()})";
    }

    /**
     * Obtenir les facteurs clés triés par importance.
     */
    public function getKeyFactors(int $limit = 5): array
    {
        if (empty($this->factors)) {
            return [];
        }

        $sorted = $this->factors;
        usort($sorted, fn($a, $b) => abs($b['impact'] ?? 0) <=> abs($a['impact'] ?? 0));

        return array_slice($sorted, 0, $limit);
    }

    /**
     * Convertir en JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
