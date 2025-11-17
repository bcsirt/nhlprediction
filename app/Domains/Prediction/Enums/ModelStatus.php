<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Enums;

enum ModelStatus: string
{
    case TRAINING = 'training';
    case VALIDATING = 'validating';
    case TESTING = 'testing';
    case PRODUCTION = 'production';
    case DEPRECATED = 'deprecated';
    case FAILED = 'failed';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::TRAINING => 'En entraînement',
            self::VALIDATING => 'En validation',
            self::TESTING => 'En test',
            self::PRODUCTION => 'En production',
            self::DEPRECATED => 'Obsolète',
            self::FAILED => 'Échec',
        };
    }

    /**
     * Obtenir la couleur pour l'affichage.
     */
    public function color(): string
    {
        return match($this) {
            self::TRAINING => 'blue',
            self::VALIDATING => 'cyan',
            self::TESTING => 'yellow',
            self::PRODUCTION => 'green',
            self::DEPRECATED => 'gray',
            self::FAILED => 'red',
        };
    }

    /**
     * Obtenir l'icône pour l'affichage.
     */
    public function icon(): string
    {
        return match($this) {
            self::TRAINING => '🔨',
            self::VALIDATING => '🔍',
            self::TESTING => '🧪',
            self::PRODUCTION => '✅',
            self::DEPRECATED => '⚠️',
            self::FAILED => '❌',
        };
    }

    /**
     * Vérifier si le modèle peut être utilisé pour des prédictions.
     */
    public function canPredict(): bool
    {
        return in_array($this, [self::TESTING, self::PRODUCTION]);
    }

    /**
     * Vérifier si le modèle est actif.
     */
    public function isActive(): bool
    {
        return !in_array($this, [self::DEPRECATED, self::FAILED]);
    }

    /**
     * Vérifier si le modèle est en production.
     */
    public function isProduction(): bool
    {
        return $this === self::PRODUCTION;
    }

    /**
     * Obtenir le statut suivant dans le workflow.
     */
    public function nextStatus(): ?self
    {
        return match($this) {
            self::TRAINING => self::VALIDATING,
            self::VALIDATING => self::TESTING,
            self::TESTING => self::PRODUCTION,
            self::PRODUCTION, self::DEPRECATED, self::FAILED => null,
        };
    }

    /**
     * Description du statut.
     */
    public function description(): string
    {
        return match($this) {
            self::TRAINING => 'Le modèle est en cours d\'entraînement sur les données historiques',
            self::VALIDATING => 'Le modèle est en cours de validation (cross-validation)',
            self::TESTING => 'Le modèle est en phase de test sur données réelles',
            self::PRODUCTION => 'Le modèle est déployé et utilisé pour les prédictions',
            self::DEPRECATED => 'Le modèle est obsolète et ne doit plus être utilisé',
            self::FAILED => 'L\'entraînement ou la validation du modèle a échoué',
        };
    }
}
