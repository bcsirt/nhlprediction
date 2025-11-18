<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Résultats de backtesting pour un modèle de prédiction.
 *
 * @property int $id
 * @property int $prediction_model_id
 * @property \Carbon\Carbon $start_date
 * @property \Carbon\Carbon $end_date
 * @property int $total_games
 * @property int $correct_predictions
 * @property int $incorrect_predictions
 * @property float $accuracy
 * @property float $precision_score
 * @property float $recall
 * @property float $f1_score
 * @property float $log_loss
 * @property float $brier_score
 * @property float $roi_percentage
 * @property float $simulated_profit
 * @property float $max_drawdown
 * @property float $sharpe_ratio
 * @property array|null $confusion_matrix
 * @property array|null $calibration_data
 * @property array|null $feature_importance
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BacktestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'prediction_model_id',
        'start_date',
        'end_date',
        'total_games',
        'correct_predictions',
        'incorrect_predictions',
        'accuracy',
        'precision_score',
        'recall',
        'f1_score',
        'log_loss',
        'brier_score',
        'roi_percentage',
        'simulated_profit',
        'initial_bankroll',
        'final_bankroll',
        'max_drawdown',
        'sharpe_ratio',
        'winning_streak',
        'losing_streak',
        'average_odds',
        'confusion_matrix',
        'calibration_data',
        'feature_importance',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'accuracy' => 'decimal:4',
        'precision_score' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'log_loss' => 'decimal:6',
        'brier_score' => 'decimal:6',
        'roi_percentage' => 'decimal:2',
        'simulated_profit' => 'decimal:2',
        'initial_bankroll' => 'decimal:2',
        'final_bankroll' => 'decimal:2',
        'max_drawdown' => 'decimal:2',
        'sharpe_ratio' => 'decimal:4',
        'average_odds' => 'decimal:3',
        'confusion_matrix' => 'array',
        'calibration_data' => 'array',
        'feature_importance' => 'array',
    ];

    /**
     * Relation avec le modèle de prédiction.
     */
    public function predictionModel(): BelongsTo
    {
        return $this->belongsTo(PredictionModel::class);
    }

    /**
     * Obtenir la durée de la période de test en jours.
     */
    public function getPeriodDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Obtenir le taux de croissance du bankroll.
     */
    public function getBankrollGrowthAttribute(): float
    {
        if (!$this->initial_bankroll || $this->initial_bankroll <= 0) {
            return 0;
        }

        return round((($this->final_bankroll - $this->initial_bankroll) / $this->initial_bankroll) * 100, 2);
    }

    /**
     * Obtenir le ratio wins/losses.
     */
    public function getWinLossRatioAttribute(): float
    {
        if ($this->incorrect_predictions <= 0) {
            return $this->correct_predictions > 0 ? 999.99 : 0;
        }

        return round($this->correct_predictions / $this->incorrect_predictions, 2);
    }

    /**
     * Vérifier si le backtest est profitable.
     */
    public function isProfitable(): bool
    {
        return $this->roi_percentage > 0;
    }

    /**
     * Vérifier si le backtest est statistiquement significatif.
     */
    public function isStatisticallySignificant(): bool
    {
        // Minimum 100 jeux pour la significativité
        return $this->total_games >= 100;
    }

    /**
     * Vérifier si le modèle est bien calibré (Brier score < 0.25).
     */
    public function isWellCalibrated(): bool
    {
        return $this->brier_score < 0.25;
    }

    /**
     * Obtenir le grade global du backtest.
     */
    public function getGradeAttribute(): string
    {
        $score = 0;

        // Accuracy (max 30 points)
        $score += min($this->accuracy * 30, 30);

        // ROI (max 25 points)
        if ($this->roi_percentage > 0) {
            $score += min($this->roi_percentage * 2.5, 25);
        }

        // F1 Score (max 20 points)
        $score += $this->f1_score * 20;

        // Calibration (max 15 points)
        if ($this->brier_score < 0.25) {
            $score += (0.25 - $this->brier_score) * 60;
        }

        // Sharpe Ratio (max 10 points)
        if ($this->sharpe_ratio > 0) {
            $score += min($this->sharpe_ratio * 5, 10);
        }

        return match (true) {
            $score >= 90 => 'A+',
            $score >= 85 => 'A',
            $score >= 80 => 'A-',
            $score >= 75 => 'B+',
            $score >= 70 => 'B',
            $score >= 65 => 'B-',
            $score >= 60 => 'C+',
            $score >= 55 => 'C',
            $score >= 50 => 'C-',
            $score >= 45 => 'D',
            default => 'F',
        };
    }

    /**
     * Obtenir les top features par importance.
     */
    public function getTopFeatures(int $limit = 10): array
    {
        if (!$this->feature_importance) {
            return [];
        }

        $features = $this->feature_importance;
        arsort($features);

        return array_slice($features, 0, $limit, true);
    }

    /**
     * Obtenir le résumé du backtest.
     */
    public function getSummary(): array
    {
        return [
            'period' => $this->start_date->format('Y-m-d') . ' to ' . $this->end_date->format('Y-m-d'),
            'total_games' => $this->total_games,
            'accuracy' => round($this->accuracy * 100, 2) . '%',
            'f1_score' => round($this->f1_score, 4),
            'roi' => round($this->roi_percentage, 2) . '%',
            'profit' => $this->simulated_profit,
            'max_drawdown' => round($this->max_drawdown, 2) . '%',
            'sharpe_ratio' => round($this->sharpe_ratio, 4),
            'grade' => $this->grade,
            'is_profitable' => $this->isProfitable(),
            'is_significant' => $this->isStatisticallySignificant(),
        ];
    }

    /**
     * Scope: Backtests profitables.
     */
    public function scopeProfitable($query)
    {
        return $query->where('roi_percentage', '>', 0);
    }

    /**
     * Scope: Backtests significatifs.
     */
    public function scopeSignificant($query)
    {
        return $query->where('total_games', '>=', 100);
    }

    /**
     * Scope: Backtests récents.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Obtenir le meilleur backtest pour un modèle.
     */
    public static function bestForModel(int $modelId): ?self
    {
        return self::where('prediction_model_id', $modelId)
            ->where('total_games', '>=', 50)
            ->orderBy('f1_score', 'desc')
            ->orderBy('roi_percentage', 'desc')
            ->first();
    }
}
