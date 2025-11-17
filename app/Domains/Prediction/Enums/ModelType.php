<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Enums;

enum ModelType: string
{
    case RANDOM_FOREST = 'random_forest';
    case GRADIENT_BOOSTING = 'gradient_boosting';
    case XGBOOST = 'xgboost';
    case NEURAL_NETWORK = 'neural_network';
    case LOGISTIC_REGRESSION = 'logistic_regression';
    case SVM = 'svm';
    case ENSEMBLE = 'ensemble';
    case STATISTICAL = 'statistical';
    case AI_POWERED = 'ai_powered'; // Claude AI

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::RANDOM_FOREST => 'Forêt Aléatoire',
            self::GRADIENT_BOOSTING => 'Gradient Boosting',
            self::XGBOOST => 'XGBoost',
            self::NEURAL_NETWORK => 'Réseau de Neurones',
            self::LOGISTIC_REGRESSION => 'Régression Logistique',
            self::SVM => 'SVM',
            self::ENSEMBLE => 'Ensemble',
            self::STATISTICAL => 'Statistique',
            self::AI_POWERED => 'IA (Claude)',
        };
    }

    /**
     * Obtenir le framework Python recommandé.
     */
    public function framework(): string
    {
        return match($this) {
            self::RANDOM_FOREST, self::GRADIENT_BOOSTING, self::LOGISTIC_REGRESSION, self::SVM
                => 'scikit-learn',
            self::XGBOOST => 'xgboost',
            self::NEURAL_NETWORK => 'pytorch',
            self::ENSEMBLE => 'scikit-learn',
            self::STATISTICAL => 'statsmodels',
            self::AI_POWERED => 'anthropic',
        };
    }

    /**
     * Complexité du modèle (1=simple, 5=très complexe).
     */
    public function complexity(): int
    {
        return match($this) {
            self::LOGISTIC_REGRESSION, self::STATISTICAL => 1,
            self::RANDOM_FOREST, self::SVM => 2,
            self::GRADIENT_BOOSTING, self::XGBOOST => 3,
            self::NEURAL_NETWORK => 4,
            self::ENSEMBLE, self::AI_POWERED => 5,
        };
    }

    /**
     * Temps d'entraînement estimé (relatif).
     */
    public function trainingTime(): string
    {
        return match($this) {
            self::LOGISTIC_REGRESSION, self::STATISTICAL => 'Très rapide (secondes)',
            self::RANDOM_FOREST, self::SVM => 'Rapide (minutes)',
            self::GRADIENT_BOOSTING, self::XGBOOST => 'Moyen (dizaines de minutes)',
            self::NEURAL_NETWORK => 'Long (heures)',
            self::ENSEMBLE => 'Très long (plusieurs heures)',
            self::AI_POWERED => 'Instantané (API)',
        };
    }

    /**
     * Interprétabilité du modèle.
     */
    public function interpretability(): string
    {
        return match($this) {
            self::LOGISTIC_REGRESSION, self::STATISTICAL => 'Haute',
            self::RANDOM_FOREST, self::GRADIENT_BOOSTING, self::XGBOOST => 'Moyenne',
            self::SVM => 'Faible',
            self::NEURAL_NETWORK => 'Très faible',
            self::ENSEMBLE => 'Faible',
            self::AI_POWERED => 'Moyenne (explications textuelles)',
        };
    }

    /**
     * Vérifier si le modèle supporte les probabilités.
     */
    public function supportsProbabilities(): bool
    {
        return !in_array($this, [self::SVM]); // SVM de base ne supporte pas
    }

    /**
     * Vérifier si le modèle nécessite une normalisation des features.
     */
    public function requiresScaling(): bool
    {
        return in_array($this, [
            self::LOGISTIC_REGRESSION,
            self::SVM,
            self::NEURAL_NETWORK,
        ]);
    }

    /**
     * Features importantes supportées.
     */
    public function supportsFeatureImportance(): bool
    {
        return in_array($this, [
            self::RANDOM_FOREST,
            self::GRADIENT_BOOSTING,
            self::XGBOOST,
        ]);
    }

    /**
     * Obtenir les hyperparamètres par défaut.
     */
    public function defaultHyperparameters(): array
    {
        return match($this) {
            self::RANDOM_FOREST => [
                'n_estimators' => 100,
                'max_depth' => 10,
                'min_samples_split' => 5,
                'random_state' => 42,
            ],
            self::GRADIENT_BOOSTING => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 3,
                'random_state' => 42,
            ],
            self::XGBOOST => [
                'n_estimators' => 100,
                'learning_rate' => 0.1,
                'max_depth' => 6,
                'random_state' => 42,
            ],
            self::LOGISTIC_REGRESSION => [
                'C' => 1.0,
                'max_iter' => 1000,
                'random_state' => 42,
            ],
            default => [],
        };
    }
}
