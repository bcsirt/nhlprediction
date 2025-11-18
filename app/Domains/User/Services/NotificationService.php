<?php

declare(strict_types=1);

namespace App\Domains\User\Services;

use App\Domains\Prediction\Models\Prediction;
use App\Domains\User\Enums\NotificationChannel;
use App\Domains\User\Enums\NotificationType;
use App\Domains\User\Models\Notification;
use App\Domains\User\Models\UserPreference;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service pour la gestion des notifications.
 */
class NotificationService
{
    /**
     * Envoyer une notification à un utilisateur.
     */
    public function send(
        User $user,
        NotificationType $type,
        string $title,
        string $message,
        array $data = [],
        ?string $actionUrl = null
    ): ?Notification {
        // Vérifier les préférences
        $preferences = $user->preferences ?? $this->getDefaultPreferences($user);

        if (!$preferences->isNotificationEnabled($type)) {
            return null;
        }

        // Créer la notification
        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => $title,
            'message' => $message,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'data' => $data,
            'action_url' => $actionUrl,
            'is_important' => $type->isImportant(),
        ]);

        // Envoyer via les canaux actifs
        $this->sendToChannels($notification, $preferences);

        return $notification;
    }

    /**
     * Envoyer une notification pour une nouvelle prédiction.
     */
    public function notifyPrediction(User $user, Prediction $prediction): ?Notification
    {
        $preferences = $user->preferences;

        if (!$preferences || !$preferences->notify_predictions) {
            return null;
        }

        // Vérifier les seuils
        if (!$preferences->shouldAlertForPrediction(
            $prediction->confidence_score,
            0 // edge sera calculé si disponible
        )) {
            return null;
        }

        /** @var \App\Domains\DataIngestion\Models\Game $game */
        $game = $prediction->game;
        /** @var \App\Domains\DataIngestion\Models\Team $homeTeam */
        $homeTeam = $game->homeTeam;
        /** @var \App\Domains\DataIngestion\Models\Team $awayTeam */
        $awayTeam = $game->awayTeam;

        $winner = $prediction->predicted_outcome === 'home'
            ? $homeTeam->name
            : $awayTeam->name;

        $probability = $prediction->predicted_outcome === 'home'
            ? $prediction->home_win_probability
            : $prediction->away_win_probability;

        $title = "Prédiction: {$awayTeam->abbreviation} @ {$homeTeam->abbreviation}";
        $message = "{$winner} favori ({" . round($probability * 100, 1) . "%}) - Confiance: {$prediction->confidence_level}";

        return $this->send(
            $user,
            NotificationType::PREDICTION,
            $title,
            $message,
            [
                'game_id' => $game->id,
                'prediction_id' => $prediction->id,
                'probability' => $probability,
                'confidence' => $prediction->confidence_score,
            ],
            "/predictions/{$prediction->id}"
        );
    }

    /**
     * Envoyer une notification pour un value bet.
     */
    public function notifyValueBet(
        User $user,
        string $matchup,
        string $selection,
        float $odds,
        float $edge,
        float $ev
    ): ?Notification {
        $preferences = $user->preferences;

        if (!$preferences || !$preferences->notify_value_bets) {
            return null;
        }

        if ($edge < $preferences->min_edge_alert) {
            return null;
        }

        $title = "Value Bet: {$matchup}";
        $message = "{$selection} @ {$odds} - Edge: " . round($edge, 1) . "% - EV: " . round($ev, 2) . "%";

        return $this->send(
            $user,
            NotificationType::VALUE_BET,
            $title,
            $message,
            [
                'odds' => $odds,
                'edge' => $edge,
                'ev' => $ev,
            ]
        );
    }

    /**
     * Envoyer une notification pour un résultat.
     */
    public function notifyResult(
        User $user,
        string $matchup,
        string $score,
        bool $betWon,
        float $profitLoss
    ): ?Notification {
        $preferences = $user->preferences;

        if (!$preferences || !$preferences->notify_results) {
            return null;
        }

        $result = $betWon ? 'Gagné' : 'Perdu';
        $emoji = $betWon ? '✅' : '❌';
        $profitStr = ($profitLoss >= 0 ? '+' : '') . '$' . number_format($profitLoss, 2);

        $title = "{$emoji} {$result}: {$matchup}";
        $message = "Score final: {$score} - P/L: {$profitStr}";

        return $this->send(
            $user,
            NotificationType::RESULT,
            $title,
            $message,
            [
                'won' => $betWon,
                'profit_loss' => $profitLoss,
            ]
        );
    }

    /**
     * Envoyer une alerte bankroll.
     */
    public function notifyBankrollAlert(
        User $user,
        string $alertType,
        float $currentValue,
        float $threshold
    ): ?Notification {
        $preferences = $user->preferences;

        if (!$preferences || !$preferences->notify_bankroll_alerts) {
            return null;
        }

        $title = match ($alertType) {
            'drawdown' => "⚠️ Alerte Drawdown",
            'daily_limit' => "🚫 Limite journalière atteinte",
            'weekly_limit' => "🚫 Limite hebdomadaire atteinte",
            default => "⚠️ Alerte Bankroll",
        };

        $message = match ($alertType) {
            'drawdown' => "Drawdown actuel: " . round($currentValue, 1) . "% (seuil: {$threshold}%)",
            'daily_limit' => "Perte du jour: \$" . number_format($currentValue, 2) . " (limite: \${$threshold})",
            'weekly_limit' => "Perte de la semaine: \$" . number_format($currentValue, 2) . " (limite: \${$threshold})",
            default => "Valeur: {$currentValue}, Seuil: {$threshold}",
        };

        return $this->send(
            $user,
            NotificationType::BANKROLL_ALERT,
            $title,
            $message,
            [
                'alert_type' => $alertType,
                'current_value' => $currentValue,
                'threshold' => $threshold,
            ]
        );
    }

    /**
     * Marquer toutes les notifications comme lues.
     */
    public function markAllAsRead(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Obtenir le nombre de notifications non lues.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Supprimer les anciennes notifications.
     */
    public function cleanOldNotifications(int $daysToKeep = 30): int
    {
        return Notification::where('created_at', '<', now()->subDays($daysToKeep))
            ->whereNotNull('read_at')
            ->delete();
    }

    /**
     * Envoyer via les canaux actifs.
     */
    private function sendToChannels(Notification $notification, UserPreference $preferences): void
    {
        foreach ($preferences->getActiveChannels() as $channel) {
            try {
                match ($channel) {
                    NotificationChannel::EMAIL => $this->sendEmail($notification),
                    NotificationChannel::PUSH => $this->sendPush($notification),
                    NotificationChannel::SMS => $this->sendSMS($notification),
                    default => null,
                };

                $this->markChannelSent($notification, $channel);
            } catch (\Exception $e) {
                Log::error("Failed to send notification via {$channel->value}", [
                    'notification_id' => $notification->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $notification->update(['sent_at' => now()]);
    }

    private function sendEmail(Notification $notification): void
    {
        // TODO: Implémenter l'envoi d'email
        // Mail::to($notification->user)->send(new NotificationMail($notification));
    }

    private function sendPush(Notification $notification): void
    {
        // TODO: Implémenter les notifications push
    }

    private function sendSMS(Notification $notification): void
    {
        // TODO: Implémenter l'envoi SMS
    }

    private function markChannelSent(Notification $notification, NotificationChannel $channel): void
    {
        $field = match ($channel) {
            NotificationChannel::EMAIL => 'sent_email',
            NotificationChannel::PUSH => 'sent_push',
            NotificationChannel::SMS => 'sent_sms',
            default => null,
        };

        if ($field) {
            $notification->update([$field => true]);
        }
    }

    private function getDefaultPreferences(User $user): UserPreference
    {
        return UserPreference::create(array_merge(
            ['user_id' => $user->id],
            UserPreference::getDefaults()
        ));
    }
}
