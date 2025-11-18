<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Models;

use App\Domains\Prediction\Enums\ModelStatus;
use App\Domains\Prediction\Enums\ModelType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Métadonnées d'un modèle ML de prédiction.
 *
 * @property int $id
 * @property string $name
 * @property string $version
 * @property string $model_type
 * @property string $status
 * @property bool $is_active
 * @property bool $is_production
 * @property float|null $accuracy
 */
class PredictionModel extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'version',
        'model_type',
        'framework',
        'description',
        'hyperparameters',
        'trained_at',
        'training_samples_count',
        'training_season',
        'training_duration_seconds',
        'accuracy',
        'precision',
        'recall',
        'f1_score',
        'auc_roc',
        'log_loss',
        'brier_score',
        'cv_folds',
        'cv_mean_score',
        'cv_std_score',
        'features_used',
        'features_count',
        'feature_importance',
        'model_file_path',
        'scaler_file_path',
        'encoder_file_path',
        'is_active',
        'is_production',
        'status',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'trained_at' => 'date',
        'accuracy' => 'decimal:4',
        'precision' => 'decimal:4',
        'recall' => 'decimal:4',
        'f1_score' => 'decimal:4',
        'auc_roc' => 'decimal:4',
        'log_loss' => 'decimal:6',
        'brier_score' => 'decimal:4',
        'cv_mean_score' => 'decimal:4',
        'cv_std_score' => 'decimal:4',
        'hyperparameters' => 'array',
        'features_used' => 'array',
        'feature_importance' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_production' => 'boolean',
    ];

    /**
     * Relation avec les prédictions.
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Relation avec les backtests.
     */
    public function backtestResults(): HasMany
    {
        return $this->hasMany(BacktestResult::class);
    }

    /**
     * Obtenir le type de modèle comme enum.
     */
    public function getModelTypeEnumAttribute(): ModelType
    {
        return ModelType::from($this->model_type);
    }

    /**
     * Obtenir le statut comme enum.
     */
    public function getStatusEnumAttribute(): ModelStatus
    {
        return ModelStatus::from($this->status);
    }

    /**
     * Vérifier si le modèle peut faire des prédictions.
     */
    public function canPredict(): bool
    {
        return $this->status_enum->canPredict() && $this->model_file_path !== null;
    }

    /**
     * Obtenir le chemin complet du fichier modèle.
     */
    public function getModelFilePathAttribute($value): ?string
    {
        return $value ? storage_path("app/{$value}") : null;
    }

    /**
     * Obtenir le nom complet (name + version).
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} v{$this->version}";
    }

    /**
     * Obtenir le F1 score formaté.
     */
    public function getFormattedF1Attribute(): string
    {
        return $this->f1_score ? number_format($this->f1_score * 100, 2) . '%' : 'N/A';
    }

    /**
     * Vérifier si le modèle est performant.
     */
    public function isPerformant(): bool
    {
        return ($this->accuracy ?? 0) >= 0.6 && ($this->f1_score ?? 0) >= 0.5;
    }

    /**
     * Promouvoir le modèle au statut suivant.
     */
    public function promote(): bool
    {
        $nextStatus = $this->status_enum->nextStatus();
        if ($nextStatus === null) {
            return false;
        }

        $this->status = $nextStatus->value;

        if ($nextStatus === ModelStatus::PRODUCTION) {
            // Désactiver les autres modèles en production
            self::where('is_production', true)
                ->where('id', '!=', $this->id)
                ->update(['is_production' => false]);

            $this->is_production = true;
        }

        return $this->save();
    }

    /**
     * Scope: Modèles actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Modèle en production.
     */
    public function scopeProduction($query)
    {
        return $query->where('is_production', true);
    }

    /**
     * Scope: Par type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('model_type', $type);
    }

    /**
     * Scope: Modèles performants.
     */
    public function scopePerformant($query)
    {
        return $query->where('accuracy', '>=', 0.6)
            ->where('f1_score', '>=', 0.5);
    }

    /**
     * Obtenir le modèle en production.
     */
    public static function getProductionModel(): ?self
    {
        return self::production()->first();
    }

    /**
     * Obtenir le meilleur modèle par accuracy.
     */
    public static function getBestByAccuracy(?string $type = null): ?self
    {
        $query = self::active()->whereNotNull('accuracy');

        if ($type) {
            $query->ofType($type);
        }

        return $query->orderBy('accuracy', 'desc')->first();
    }
}
