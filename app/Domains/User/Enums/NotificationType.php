<?php

declare(strict_types=1);

namespace App\Domains\User\Enums;

enum NotificationType: string
{
    case PREDICTION = 'prediction';
    case VALUE_BET = 'value_bet';
    case RESULT = 'result';
    case BANKROLL_ALERT = 'bankroll_alert';
    case STREAK = 'streak';
    case SYSTEM = 'system';
    case FAVORITE_TEAM = 'favorite_team';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::PREDICTION => 'Nouvelle prédiction',
            self::VALUE_BET => 'Value bet détecté',
            self::RESULT => 'Résultat de match',
            self::BANKROLL_ALERT => 'Alerte bankroll',
            self::STREAK => 'Série en cours',
            self::SYSTEM => 'Système',
            self::FAVORITE_TEAM => 'Équipe favorite',
        };
    }

    /**
     * Obtenir l'icône.
     */
    public function icon(): string
    {
        return match($this) {
            self::PREDICTION => '🎯',
            self::VALUE_BET => '💰',
            self::RESULT => '📊',
            self::BANKROLL_ALERT => '⚠️',
            self::STREAK => '🔥',
            self::SYSTEM => '⚙️',
            self::FAVORITE_TEAM => '⭐',
        };
    }

    /**
     * Obtenir la couleur.
     */
    public function color(): string
    {
        return match($this) {
            self::PREDICTION => 'blue',
            self::VALUE_BET => 'green',
            self::RESULT => 'purple',
            self::BANKROLL_ALERT => 'red',
            self::STREAK => 'orange',
            self::SYSTEM => 'gray',
            self::FAVORITE_TEAM => 'yellow',
        };
    }

    /**
     * Priorité de la notification (1=basse, 5=haute).
     */
    public function priority(): int
    {
        return match($this) {
            self::BANKROLL_ALERT => 5,
            self::VALUE_BET => 4,
            self::RESULT => 3,
            self::PREDICTION => 3,
            self::STREAK => 2,
            self::FAVORITE_TEAM => 2,
            self::SYSTEM => 1,
        };
    }

    /**
     * Vérifier si la notification est importante.
     */
    public function isImportant(): bool
    {
        return $this->priority() >= 4;
    }

    /**
     * Obtenir la clé de préférence associée.
     */
    public function preferenceKey(): string
    {
        return match($this) {
            self::PREDICTION => 'notify_predictions',
            self::VALUE_BET => 'notify_value_bets',
            self::RESULT => 'notify_results',
            self::BANKROLL_ALERT => 'notify_bankroll_alerts',
            default => 'notify_predictions',
        };
    }
}
