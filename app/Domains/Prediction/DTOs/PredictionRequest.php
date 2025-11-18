<?php

declare(strict_types=1);

namespace App\Domains\Prediction\DTOs;

use App\Domains\Prediction\Enums\PredictionType;

/**
 * DTO pour une requête de prédiction.
 */
readonly class PredictionRequest
{
    public function __construct(
        public int $gameId,
        public PredictionType $predictionType = PredictionType::WINNER,
        public ?int $modelId = null,
        public bool $includeExplanation = true,
        public bool $useEnsemble = true,
        public ?float $overUnderLine = null,
        public ?float $spreadLine = null,
    ) {}

    /**
     * Créer depuis un tableau.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            gameId: (int) $data['game_id'],
            predictionType: isset($data['prediction_type'])
                ? PredictionType::from($data['prediction_type'])
                : PredictionType::WINNER,
            modelId: isset($data['model_id']) ? (int) $data['model_id'] : null,
            includeExplanation: $data['include_explanation'] ?? true,
            useEnsemble: $data['use_ensemble'] ?? true,
            overUnderLine: isset($data['over_under_line']) ? (float) $data['over_under_line'] : null,
            spreadLine: isset($data['spread_line']) ? (float) $data['spread_line'] : null,
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
            'model_id' => $this->modelId,
            'include_explanation' => $this->includeExplanation,
            'use_ensemble' => $this->useEnsemble,
            'over_under_line' => $this->overUnderLine,
            'spread_line' => $this->spreadLine,
        ];
    }

    /**
     * Valider la requête.
     */
    public function validate(): array
    {
        $errors = [];

        if ($this->gameId <= 0) {
            $errors[] = 'game_id must be a positive integer';
        }

        if ($this->predictionType === PredictionType::OVER_UNDER && $this->overUnderLine === null) {
            $errors[] = 'over_under_line is required for OVER_UNDER prediction type';
        }

        if ($this->predictionType === PredictionType::SPREAD && $this->spreadLine === null) {
            $errors[] = 'spread_line is required for SPREAD prediction type';
        }

        return $errors;
    }

    /**
     * Vérifier si la requête est valide.
     */
    public function isValid(): bool
    {
        return empty($this->validate());
    }
}
