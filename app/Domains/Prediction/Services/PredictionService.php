<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Services;

use App\Domains\AI\Services\ClaudeService;
use App\Domains\DataIngestion\Models\Game;
use App\Domains\Prediction\DTOs\PredictionRequest;
use App\Domains\Prediction\DTOs\PredictionResponse;
use App\Domains\Prediction\Enums\ConfidenceLevel;
use App\Domains\Prediction\Enums\ModelStatus;
use App\Domains\Prediction\Enums\PredictionType;
use App\Domains\Prediction\Models\GameFeatures;
use App\Domains\Prediction\Models\Prediction;
use App\Domains\Prediction\Models\PredictionModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service principal pour les prédictions de matchs NHL.
 */
class PredictionService
{
    public function __construct(
        private readonly FeatureExtractor $featureExtractor,
        private readonly ClaudeService $claudeService,
    ) {}

    /**
     * Prédire le résultat d'un match.
     */
    public function predict(PredictionRequest $request): PredictionResponse
    {
        // Valider la requête
        $errors = $request->validate();
        if (!empty($errors)) {
            return PredictionResponse::error(
                $request->gameId,
                $request->predictionType,
                implode(', ', $errors)
            );
        }

        // Récupérer le match
        $game = Game::with(['homeTeam', 'awayTeam'])->find($request->gameId);
        if (!$game) {
            return PredictionResponse::error(
                $request->gameId,
                $request->predictionType,
                'Game not found'
            );
        }

        try {
            // Extraire les features du match
            $gameFeatures = $this->getOrCreateGameFeatures($game);

            // Obtenir les prédictions des différents modèles
            $predictions = [];

            if ($request->useEnsemble) {
                $predictions = $this->getEnsemblePredictions($game, $gameFeatures, $request);
            } else {
                $model = $this->getModel($request->modelId);
                if ($model) {
                    $predictions[$model->name] = $this->predictWithModel($model, $game, $gameFeatures, $request);
                }
            }

            // Combiner les prédictions
            $combinedPrediction = $this->combinePredictions($predictions, $game, $request);

            // Sauvegarder la prédiction
            $savedPrediction = $this->savePrediction($game, $combinedPrediction, $request);

            return new PredictionResponse(
                gameId: $game->id,
                predictionType: $request->predictionType,
                predictedOutcome: $combinedPrediction['outcome'],
                probability: $combinedPrediction['probability'],
                confidenceScore: $combinedPrediction['confidence_score'],
                confidenceLevel: ConfidenceLevel::fromScore($combinedPrediction['confidence_score']),
                explanation: $request->includeExplanation ? $combinedPrediction['explanation'] : null,
                probabilities: $combinedPrediction['probabilities'],
                modelScores: $predictions,
                factors: $combinedPrediction['factors'],
                expectedValue: $combinedPrediction['expected_value'] ?? null,
                kellyFraction: $combinedPrediction['kelly_fraction'] ?? null,
                predictionId: $savedPrediction->id,
                modelName: $combinedPrediction['model_name'],
            );
        } catch (\Exception $e) {
            Log::error('Prediction failed', [
                'game_id' => $request->gameId,
                'error' => $e->getMessage(),
            ]);

            return PredictionResponse::error(
                $request->gameId,
                $request->predictionType,
                'Prediction failed: ' . $e->getMessage()
            );
        }
    }

    /**
     * Prédire plusieurs matchs.
     */
    public function predictBatch(array $requests): array
    {
        $responses = [];
        foreach ($requests as $request) {
            $responses[] = $this->predict($request);
        }
        return $responses;
    }

    /**
     * Prédire tous les matchs d'une date.
     */
    public function predictGamesForDate(Carbon $date, PredictionType $type = PredictionType::WINNER): array
    {
        $games = Game::whereDate('game_date', $date)
            ->where('status', 'scheduled')
            ->get();

        $responses = [];
        foreach ($games as $game) {
            $request = new PredictionRequest(
                gameId: $game->id,
                predictionType: $type,
            );
            $responses[] = $this->predict($request);
        }

        return $responses;
    }

    /**
     * Évaluer les prédictions passées.
     */
    public function evaluatePredictions(Carbon $date): array
    {
        $games = Game::whereDate('game_date', $date)
            ->where('status', 'final')
            ->get();

        $results = [
            'total' => 0,
            'evaluated' => 0,
            'correct' => 0,
            'incorrect' => 0,
        ];

        foreach ($games as $game) {
            $predictions = Prediction::where('game_id', $game->id)
                ->whereNull('is_correct')
                ->get();

            foreach ($predictions as $prediction) {
                $results['total']++;

                if ($this->evaluatePrediction($prediction, $game)) {
                    $results['evaluated']++;
                    if ($prediction->is_correct) {
                        $results['correct']++;
                    } else {
                        $results['incorrect']++;
                    }
                }
            }
        }

        $results['accuracy'] = $results['evaluated'] > 0
            ? round($results['correct'] / $results['evaluated'], 4)
            : 0;

        return $results;
    }

    // ========== PRIVATE METHODS ==========

    private function getOrCreateGameFeatures(Game $game): GameFeatures
    {
        $existing = GameFeatures::where('game_id', $game->id)->first();

        if ($existing) {
            return $existing;
        }

        return $this->featureExtractor->extractGameFeatures($game);
    }

    private function getModel(?int $modelId): ?PredictionModel
    {
        if ($modelId) {
            return PredictionModel::find($modelId);
        }

        // Retourner le modèle en production par défaut
        return PredictionModel::where('status', ModelStatus::PRODUCTION)
            ->orderBy('accuracy', 'desc')
            ->first();
    }

    private function getEnsemblePredictions(Game $game, GameFeatures $features, PredictionRequest $request): array
    {
        $predictions = [];

        // 1. Modèle statistique simple
        $predictions['statistical'] = $this->statisticalPrediction($game, $features);

        // 2. Prédiction basée sur les features
        $predictions['feature_based'] = $this->featureBasedPrediction($features);

        // 3. Prédiction AI (Claude) si configuré
        if (config('prediction.ai.enabled', true)) {
            try {
                $aiPrediction = $this->aiPrediction($game, $features);
                if ($aiPrediction) {
                    $predictions['ai_claude'] = $aiPrediction;
                }
            } catch (\Exception $e) {
                Log::warning('AI prediction failed', ['error' => $e->getMessage()]);
            }
        }

        return $predictions;
    }

    private function predictWithModel(PredictionModel $model, Game $game, GameFeatures $features, PredictionRequest $request): array
    {
        // TODO: Implémenter l'appel au modèle Python via API ou file
        // Pour l'instant, utiliser la prédiction basée sur les features
        return $this->featureBasedPrediction($features);
    }

    private function statisticalPrediction(Game $game, GameFeatures $features): array
    {
        // Prédiction basée sur des statistiques simples
        $homeAdvantage = 0.54; // Avantage domicile NHL
        $formFactor = ($features->form_diff_5 ?? 0) * 0.02;
        $goalFactor = ($features->goal_diff_advantage ?? 0) * 0.03;

        $homeWinProb = min(max($homeAdvantage + $formFactor + $goalFactor, 0.2), 0.8);

        $predictedWinner = $homeWinProb > 0.5 ? 'home' : 'away';
        $probability = $predictedWinner === 'home' ? $homeWinProb : (1 - $homeWinProb);

        return [
            'outcome' => $predictedWinner,
            'probability' => round($probability, 4),
            'confidence' => min(abs($homeWinProb - 0.5) * 200, 100),
        ];
    }

    private function featureBasedPrediction(GameFeatures $features): array
    {
        // Prédiction basée sur l'ensemble des features
        $score = 50; // Score de base

        // Avantage domicile
        $score += 4;

        // Form (séries récentes)
        $score += ($features->form_diff_5 ?? 0) * 3;
        $score += ($features->form_diff_10 ?? 0) * 1;

        // Goals
        $score += ($features->goal_diff_advantage ?? 0) * 5;

        // Tirs
        $score += ($features->shot_diff_advantage ?? 0) * 0.1;

        // Special teams
        $score += ($features->pp_advantage ?? 0) * 0.5;
        $score += ($features->pk_advantage ?? 0) * 0.5;

        // Rest
        $score += ($features->rest_advantage ?? 0) * 2;
        if ($features->home_back_to_back ?? false) {
            $score -= 3;
        }
        if ($features->away_back_to_back ?? false) {
            $score += 3;
        }

        // H2H
        $h2hDiff = ($features->h2h_wins_home ?? 0) - ($features->h2h_wins_away ?? 0);
        $score += $h2hDiff * 2;

        // Momentum
        $momentumDiff = ($features->home_momentum_score ?? 50) - ($features->away_momentum_score ?? 50);
        $score += $momentumDiff * 0.2;

        // Convertir en probabilité
        $homeWinProb = $this->scoreToProba($score);
        $predictedWinner = $homeWinProb > 0.5 ? 'home' : 'away';
        $probability = $predictedWinner === 'home' ? $homeWinProb : (1 - $homeWinProb);

        return [
            'outcome' => $predictedWinner,
            'probability' => round($probability, 4),
            'confidence' => min(abs($score - 50) * 2, 100),
            'raw_score' => $score,
        ];
    }

    private function aiPrediction(Game $game, GameFeatures $features): ?array
    {
        // Utiliser Claude pour une prédiction
        $prompt = $this->buildAIPrompt($game, $features);

        $response = $this->claudeService->analyzeGame($game);

        if (!$response || !isset($response['prediction'])) {
            return null;
        }

        return [
            'outcome' => $response['prediction']['winner'] ?? 'home',
            'probability' => $response['prediction']['probability'] ?? 0.5,
            'confidence' => $response['prediction']['confidence'] ?? 50,
            'explanation' => $response['analysis'] ?? null,
        ];
    }

    private function buildAIPrompt(Game $game, GameFeatures $features): string
    {
        // Le prompt est géré par ClaudeService
        return '';
    }

    private function combinePredictions(array $predictions, Game $game, PredictionRequest $request): array
    {
        if (empty($predictions)) {
            return [
                'outcome' => 'home',
                'probability' => 0.54,
                'confidence_score' => 30,
                'probabilities' => ['home' => 0.54, 'away' => 0.46],
                'factors' => [],
                'explanation' => 'Default prediction (no models available)',
                'model_name' => 'default',
            ];
        }

        // Poids pour chaque type de modèle
        $weights = [
            'statistical' => config('prediction.ensemble.weights.statistical', 0.2),
            'feature_based' => config('prediction.ensemble.weights.feature_based', 0.5),
            'ai_claude' => config('prediction.ensemble.weights.ai', 0.3),
        ];

        // Calculer la probabilité combinée (weighted average)
        $totalWeight = 0;
        $weightedProb = 0;
        $weightedConfidence = 0;

        foreach ($predictions as $modelName => $pred) {
            $weight = $weights[$modelName] ?? 0.2;

            // Normaliser la probabilité pour home
            $homeProb = $pred['outcome'] === 'home' ? $pred['probability'] : (1 - $pred['probability']);

            $weightedProb += $homeProb * $weight;
            $weightedConfidence += ($pred['confidence'] ?? 50) * $weight;
            $totalWeight += $weight;
        }

        if ($totalWeight > 0) {
            $weightedProb /= $totalWeight;
            $weightedConfidence /= $totalWeight;
        }

        $predictedWinner = $weightedProb > 0.5 ? 'home' : 'away';
        $probability = $predictedWinner === 'home' ? $weightedProb : (1 - $weightedProb);

        // Construire les facteurs
        $factors = $this->buildFactors($game, $predictions);

        // Construire l'explication
        $explanation = $this->buildExplanation($game, $predictedWinner, $probability, $factors, $predictions);

        return [
            'outcome' => $predictedWinner,
            'probability' => round($probability, 4),
            'confidence_score' => round($weightedConfidence, 2),
            'probabilities' => [
                'home' => round($weightedProb, 4),
                'away' => round(1 - $weightedProb, 4),
            ],
            'factors' => $factors,
            'explanation' => $explanation,
            'model_name' => 'ensemble',
            'expected_value' => null, // TODO: calculer avec les cotes
            'kelly_fraction' => ConfidenceLevel::fromScore($weightedConfidence)->kellyFraction(),
        ];
    }

    private function buildFactors(Game $game, array $predictions): array
    {
        $factors = [];

        // Home ice advantage
        $factors[] = [
            'name' => 'Home Ice Advantage',
            'impact' => 4,
            'description' => 'Playing at home provides ~54% win rate advantage',
        ];

        // Consensus des modèles
        $homeVotes = 0;
        foreach ($predictions as $pred) {
            if ($pred['outcome'] === 'home') {
                $homeVotes++;
            }
        }

        $consensus = $homeVotes / count($predictions);
        if ($consensus >= 0.8) {
            $factors[] = [
                'name' => 'Model Consensus',
                'impact' => 5,
                'description' => 'Strong agreement among prediction models',
            ];
        }

        return $factors;
    }

    private function buildExplanation(Game $game, string $winner, float $probability, array $factors, array $predictions): string
    {
        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $winnerName = $winner === 'home' ? $homeTeam->name : $awayTeam->name;
        $loserName = $winner === 'home' ? $awayTeam->name : $homeTeam->name;
        $probPercent = round($probability * 100, 1);

        $explanation = "{$winnerName} is predicted to beat {$loserName} with {$probPercent}% probability. ";

        // Ajouter les facteurs clés
        if (!empty($factors)) {
            $explanation .= "Key factors: ";
            $factorNames = array_column(array_slice($factors, 0, 3), 'name');
            $explanation .= implode(', ', $factorNames) . '.';
        }

        // Ajouter l'explication AI si disponible
        if (isset($predictions['ai_claude']['explanation'])) {
            $explanation .= "\n\nAI Analysis: " . $predictions['ai_claude']['explanation'];
        }

        return $explanation;
    }

    private function savePrediction(Game $game, array $combinedPrediction, PredictionRequest $request): Prediction
    {
        $prediction = new Prediction([
            'game_id' => $game->id,
            'prediction_model_id' => $request->modelId,
            'prediction_type' => $request->predictionType->value,
            'predicted_outcome' => $combinedPrediction['outcome'],
            'home_win_probability' => $combinedPrediction['probabilities']['home'],
            'away_win_probability' => $combinedPrediction['probabilities']['away'],
            'confidence_score' => $combinedPrediction['confidence_score'],
            'confidence_level' => ConfidenceLevel::fromScore($combinedPrediction['confidence_score'])->value,
            'model_outputs' => $combinedPrediction,
            'explanation' => $combinedPrediction['explanation'] ?? null,
        ]);

        $prediction->save();

        return $prediction;
    }

    private function evaluatePrediction(Prediction $prediction, Game $game): bool
    {
        if ($game->status !== 'final') {
            return false;
        }

        $actualWinner = $game->home_score > $game->away_score ? 'home' : 'away';

        $isCorrect = match ($prediction->prediction_type) {
            'winner' => $prediction->predicted_outcome === $actualWinner,
            'over_under' => $this->evaluateOverUnder($prediction, $game),
            'spread' => $this->evaluateSpread($prediction, $game),
            default => $prediction->predicted_outcome === $actualWinner,
        };

        $prediction->update([
            'actual_outcome' => $actualWinner,
            'is_correct' => $isCorrect,
            'evaluated_at' => now(),
        ]);

        return true;
    }

    private function evaluateOverUnder(Prediction $prediction, Game $game): bool
    {
        $total = $game->home_score + $game->away_score;
        $line = $prediction->over_under_line ?? 5.5;

        $actualOutcome = $total > $line ? 'over' : 'under';
        return $prediction->predicted_outcome === $actualOutcome;
    }

    private function evaluateSpread(Prediction $prediction, Game $game): bool
    {
        $spread = $game->home_score - $game->away_score;
        $line = $prediction->spread_line ?? 0;

        $actualOutcome = $spread > $line ? 'home' : 'away';
        return $prediction->predicted_outcome === $actualOutcome;
    }

    private function scoreToProba(float $score): float
    {
        // Convertir un score (0-100) en probabilité avec sigmoid
        $normalized = ($score - 50) / 25;
        return 1 / (1 + exp(-$normalized));
    }
}
