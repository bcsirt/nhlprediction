<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Models;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\Prediction\Enums\ConfidenceLevel;
use App\Domains\Prediction\Enums\ModelType;
use App\Domains\Prediction\Enums\PredictionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Prédiction pour un match.
 *
 * @property int $id
 * @property int $game_id
 * @property string $prediction_type
 * @property string $model_type
 * @property string|null $predicted_winner
 * @property float|null $home_win_probability
 * @property string $confidence_level
 * @property bool|null $prediction_correct
 */
class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_id',
        'prediction_model_id',
        'prediction_type',
        'model_type',
        'predicted_winner',
        'predicted_home_score',
        'predicted_away_score',
        'predicted_total_goals',
        'home_win_probability',
        'away_win_probability',
        'draw_probability',
        'over_probability',
        'under_probability',
        'confidence_level',
        'confidence_score',
        'expected_value',
        'kelly_criterion',
        'feature_importance_score',
        'features_used_count',
        'model_accuracy_estimate',
        'actual_winner',
        'actual_home_score',
        'actual_away_score',
        'actual_total_goals',
        'prediction_correct',
        'prediction_error',
        'predicted_at',
        'game_starts_at',
        'prediction_explanation',
        'feature_values',
        'model_metadata',
    ];

    protected $casts = [
        'predicted_home_score' => 'decimal:2',
        'predicted_away_score' => 'decimal:2',
        'predicted_total_goals' => 'decimal:2',
        'home_win_probability' => 'decimal:4',
        'away_win_probability' => 'decimal:4',
        'draw_probability' => 'decimal:4',
        'over_probability' => 'decimal:4',
        'under_probability' => 'decimal:4',
        'confidence_score' => 'decimal:2',
        'expected_value' => 'decimal:4',
        'kelly_criterion' => 'decimal:4',
        'prediction_correct' => 'boolean',
        'predicted_at' => 'datetime',
        'game_starts_at' => 'datetime',
        'feature_values' => 'array',
        'model_metadata' => 'array',
    ];

    /**
     * Relation avec le match.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Relation avec le modèle de prédiction.
     */
    public function predictionModel(): BelongsTo
    {
        return $this->belongsTo(PredictionModel::class);
    }

    /**
     * Obtenir le type de prédiction comme enum.
     */
    public function getPredictionTypeEnumAttribute(): PredictionType
    {
        return PredictionType::from($this->prediction_type);
    }

    /**
     * Obtenir le type de modèle comme enum.
     */
    public function getModelTypeEnumAttribute(): ModelType
    {
        return ModelType::from($this->model_type);
    }

    /**
     * Obtenir le niveau de confiance comme enum.
     */
    public function getConfidenceLevelEnumAttribute(): ConfidenceLevel
    {
        return ConfidenceLevel::from($this->confidence_level);
    }

    /**
     * Vérifier si la prédiction recommande de parier.
     */
    public function shouldBet(): bool
    {
        return $this->confidence_level_enum->shouldBet()
            && ($this->expected_value ?? 0) > 0;
    }

    /**
     * Obtenir le montant de mise recommandé (fraction de bankroll).
     */
    public function getRecommendedBetFraction(): float
    {
        if (!$this->shouldBet()) {
            return 0;
        }

        $kellyFraction = $this->confidence_level_enum->kellyFraction();
        return ($this->kelly_criterion ?? 0) * $kellyFraction;
    }

    /**
     * Vérifier si la prédiction a été évaluée.
     */
    public function isEvaluated(): bool
    {
        return $this->prediction_correct !== null;
    }

    /**
     * Évaluer la prédiction avec le résultat réel.
     */
    public function evaluate(string $actualWinner, int $homeScore, int $awayScore): void
    {
        $this->actual_winner = $actualWinner;
        $this->actual_home_score = $homeScore;
        $this->actual_away_score = $awayScore;
        $this->actual_total_goals = $homeScore + $awayScore;

        // Évaluer selon le type
        $this->prediction_correct = match($this->prediction_type) {
            'winner' => $this->predicted_winner === $actualWinner,
            'over_under' => $this->evaluateOverUnder($homeScore + $awayScore),
            'total_goals' => abs($this->predicted_total_goals - ($homeScore + $awayScore)) <= 0.5,
            default => $this->predicted_winner === $actualWinner,
        };

        // Calculer l'erreur
        if ($this->predicted_total_goals !== null) {
            $this->prediction_error = abs($this->predicted_total_goals - ($homeScore + $awayScore));
        }

        $this->save();
    }

    /**
     * Évaluer over/under.
     */
    private function evaluateOverUnder(int $totalGoals): bool
    {
        $predictedOver = ($this->over_probability ?? 0) > 0.5;
        $actualOver = $totalGoals > 5.5; // Ligne standard
        return $predictedOver === $actualOver;
    }

    /**
     * Scope: Prédictions correctes.
     */
    public function scopeCorrect($query)
    {
        return $query->where('prediction_correct', true);
    }

    /**
     * Scope: Prédictions incorrectes.
     */
    public function scopeIncorrect($query)
    {
        return $query->where('prediction_correct', false);
    }

    /**
     * Scope: Prédictions évaluées.
     */
    public function scopeEvaluated($query)
    {
        return $query->whereNotNull('prediction_correct');
    }

    /**
     * Scope: Prédictions non évaluées.
     */
    public function scopePending($query)
    {
        return $query->whereNull('prediction_correct');
    }

    /**
     * Scope: Par type de prédiction.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('prediction_type', $type);
    }

    /**
     * Scope: Haute confiance.
     */
    public function scopeHighConfidence($query)
    {
        return $query->whereIn('confidence_level', ['high', 'very_high']);
    }

    /**
     * Scope: Recommandées pour paris.
     */
    public function scopeRecommendedBets($query)
    {
        return $query->highConfidence()
            ->where('expected_value', '>', 0);
    }

    /**
     * Obtenir les prédictions pour un match.
     */
    public static function forGame(int $gameId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('game_id', $gameId)
            ->orderBy('predicted_at', 'desc')
            ->get();
    }

    /**
     * Calculer l'accuracy pour une période.
     */
    public static function calculateAccuracy(
        ?string $type = null,
        ?string $modelType = null,
        ?string $startDate = null,
        ?string $endDate = null
    ): float {
        $query = self::evaluated();

        if ($type) {
            $query->ofType($type);
        }
        if ($modelType) {
            $query->where('model_type', $modelType);
        }
        if ($startDate) {
            $query->where('predicted_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('predicted_at', '<=', $endDate);
        }

        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $correct = (clone $query)->correct()->count();
        return round(($correct / $total) * 100, 2);
    }
}
