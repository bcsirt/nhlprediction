<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\User\Enums\NotificationChannel;
use App\Domains\User\Enums\NotificationType;
use App\Domains\ValueBets\Enums\OddsFormat;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property bool $notify_predictions
 * @property bool $notify_value_bets
 * @property bool $notify_results
 * @property bool $email_enabled
 * @property bool $push_enabled
 * @property float $min_confidence_alert
 * @property float $min_edge_alert
 * @property array|null $favorite_teams
 * @property string $preferred_odds_format
 * @property string $timezone
 */
class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'notify_predictions',
        'notify_value_bets',
        'notify_results',
        'notify_bankroll_alerts',
        'email_enabled',
        'push_enabled',
        'sms_enabled',
        'min_confidence_alert',
        'min_edge_alert',
        'favorite_teams',
        'excluded_teams',
        'default_bet_type',
        'preferred_odds_format',
        'default_stake_percentage',
        'preferred_bookmakers',
        'timezone',
        'language',
        'theme',
        'show_advanced_stats',
        'daily_loss_limit',
        'weekly_loss_limit',
        'monthly_loss_limit',
        'max_drawdown_alert',
    ];

    protected $casts = [
        'notify_predictions' => 'boolean',
        'notify_value_bets' => 'boolean',
        'notify_results' => 'boolean',
        'notify_bankroll_alerts' => 'boolean',
        'email_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'min_confidence_alert' => 'decimal:2',
        'min_edge_alert' => 'decimal:2',
        'favorite_teams' => 'array',
        'excluded_teams' => 'array',
        'default_stake_percentage' => 'decimal:2',
        'preferred_bookmakers' => 'array',
        'show_advanced_stats' => 'boolean',
        'daily_loss_limit' => 'decimal:2',
        'weekly_loss_limit' => 'decimal:2',
        'monthly_loss_limit' => 'decimal:2',
        'max_drawdown_alert' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Vérifier si un type de notification est activé.
     */
    public function isNotificationEnabled(NotificationType $type): bool
    {
        $key = $type->preferenceKey();
        return $this->$key ?? true;
    }

    /**
     * Vérifier si un canal de notification est activé.
     */
    public function isChannelEnabled(NotificationChannel $channel): bool
    {
        $key = $channel->preferenceKey();
        return $this->$key ?? false;
    }

    /**
     * Obtenir les canaux actifs.
     */
    public function getActiveChannels(): array
    {
        $channels = [];

        if ($this->email_enabled) {
            $channels[] = NotificationChannel::EMAIL;
        }
        if ($this->push_enabled) {
            $channels[] = NotificationChannel::PUSH;
        }
        if ($this->sms_enabled) {
            $channels[] = NotificationChannel::SMS;
        }

        return $channels;
    }

    /**
     * Vérifier si une équipe est favorite.
     */
    public function isFavoriteTeam(int $teamId): bool
    {
        return in_array($teamId, $this->favorite_teams ?? []);
    }

    /**
     * Vérifier si une équipe est exclue.
     */
    public function isExcludedTeam(int $teamId): bool
    {
        return in_array($teamId, $this->excluded_teams ?? []);
    }

    /**
     * Ajouter une équipe favorite.
     */
    public function addFavoriteTeam(int $teamId): void
    {
        $favorites = $this->favorite_teams ?? [];
        if (!in_array($teamId, $favorites)) {
            $favorites[] = $teamId;
            $this->favorite_teams = $favorites;
            $this->save();
        }
    }

    /**
     * Retirer une équipe favorite.
     */
    public function removeFavoriteTeam(int $teamId): void
    {
        $favorites = $this->favorite_teams ?? [];
        $this->favorite_teams = array_values(array_diff($favorites, [$teamId]));
        $this->save();
    }

    /**
     * Obtenir le format de cotes préféré en enum.
     */
    public function getOddsFormat(): OddsFormat
    {
        return OddsFormat::tryFrom($this->preferred_odds_format) ?? OddsFormat::DECIMAL;
    }

    /**
     * Vérifier si une prédiction doit générer une alerte.
     */
    public function shouldAlertForPrediction(float $confidence, float $edge): bool
    {
        if (!$this->notify_predictions && !$this->notify_value_bets) {
            return false;
        }

        return $confidence >= $this->min_confidence_alert || $edge >= $this->min_edge_alert;
    }

    /**
     * Obtenir les préférences par défaut.
     */
    public static function getDefaults(): array
    {
        return [
            'notify_predictions' => true,
            'notify_value_bets' => true,
            'notify_results' => true,
            'notify_bankroll_alerts' => true,
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
            'min_confidence_alert' => 70,
            'min_edge_alert' => 5,
            'default_bet_type' => 'moneyline',
            'preferred_odds_format' => 'decimal',
            'default_stake_percentage' => 2,
            'timezone' => 'America/New_York',
            'language' => 'fr',
            'theme' => 'light',
            'show_advanced_stats' => false,
            'max_drawdown_alert' => 20,
        ];
    }
}
