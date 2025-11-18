<?php

declare(strict_types=1);

namespace App\Domains\User\Services;

use App\Domains\User\Enums\StatsPeriod;
use App\Domains\User\Models\UserStatistics;
use App\Domains\ValueBets\Models\Bet;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service pour la gestion des statistiques utilisateur.
 */
class UserStatsService
{
    /**
     * Enregistrer un pari dans les statistiques.
     */
    public function recordBet(User $user, Bet $bet): void
    {
        // Mettre à jour les stats pour chaque période
        foreach (StatsPeriod::cases() as $period) {
            $stats = UserStatistics::getOrCreateForPeriod($user->id, $period);

            $won = $bet->status === 'won';
            $stats->recordBet(
                (float) $bet->stake,
                (float) $bet->odds_decimal,
                $won,
                (float) $bet->profit_loss
            );
        }
    }

    /**
     * Obtenir les statistiques pour une période.
     */
    public function getStatsForPeriod(User $user, StatsPeriod $period): ?UserStatistics
    {
        $dates = $period->getCurrentPeriod();

        return UserStatistics::where('user_id', $user->id)
            ->where('period_type', $period->value)
            ->where('period_start', $dates['start']->toDateString())
            ->first();
    }

    /**
     * Obtenir toutes les statistiques actuelles.
     */
    public function getAllCurrentStats(User $user): array
    {
        $stats = [];

        foreach (StatsPeriod::cases() as $period) {
            $periodStats = $this->getStatsForPeriod($user, $period);
            if ($periodStats) {
                $stats[$period->value] = $periodStats->getSummary();
            }
        }

        return $stats;
    }

    /**
     * Obtenir l'historique des statistiques.
     */
    public function getStatsHistory(
        User $user,
        StatsPeriod $period,
        int $limit = 12
    ): Collection {
        return UserStatistics::where('user_id', $user->id)
            ->where('period_type', $period->value)
            ->orderBy('period_start', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Comparer avec la période précédente.
     */
    public function compareToPreviousPeriod(User $user, StatsPeriod $period): array
    {
        $currentDates = $period->getCurrentPeriod();
        $previousDates = $period->getPreviousPeriod();

        $current = UserStatistics::where('user_id', $user->id)
            ->where('period_type', $period->value)
            ->where('period_start', $currentDates['start']->toDateString())
            ->first();

        $previous = UserStatistics::where('user_id', $user->id)
            ->where('period_type', $period->value)
            ->where('period_start', $previousDates['start']->toDateString())
            ->first();

        if (!$current || !$previous) {
            return [];
        }

        return [
            'bets_change' => $current->total_bets - $previous->total_bets,
            'win_rate_change' => round(($current->win_rate - $previous->win_rate) * 100, 2),
            'profit_change' => round($current->total_profit - $previous->total_profit, 2),
            'roi_change' => round($current->roi_percentage - $previous->roi_percentage, 2),
        ];
    }

    /**
     * Obtenir les meilleures performances par équipe.
     */
    public function getBestPerformingTeams(User $user, int $limit = 5): array
    {
        $allTimeStats = UserStatistics::where('user_id', $user->id)
            ->where('period_type', StatsPeriod::ALL_TIME->value)
            ->first();

        if (!$allTimeStats || !$allTimeStats->stats_by_team) {
            return [];
        }

        $teams = $allTimeStats->stats_by_team;

        // Trier par ROI
        uasort($teams, fn($a, $b) => ($b['roi'] ?? 0) <=> ($a['roi'] ?? 0));

        return array_slice($teams, 0, $limit, true);
    }

    /**
     * Obtenir les performances par type de pari.
     */
    public function getPerformanceByBetType(User $user, StatsPeriod $period): array
    {
        $stats = $this->getStatsForPeriod($user, $period);

        if (!$stats || !$stats->stats_by_bet_type) {
            return [];
        }

        return $stats->stats_by_bet_type;
    }

    /**
     * Obtenir les performances par niveau de confiance.
     */
    public function getPerformanceByConfidence(User $user, StatsPeriod $period): array
    {
        $stats = $this->getStatsForPeriod($user, $period);

        if (!$stats || !$stats->stats_by_confidence) {
            return [];
        }

        return $stats->stats_by_confidence;
    }

    /**
     * Recalculer les statistiques depuis les paris.
     */
    public function recalculateStats(User $user, StatsPeriod $period): UserStatistics
    {
        $dates = $period->getCurrentPeriod();

        // Supprimer les anciennes stats
        UserStatistics::where('user_id', $user->id)
            ->where('period_type', $period->value)
            ->where('period_start', $dates['start']->toDateString())
            ->delete();

        // Récupérer les paris de la période
        $bets = Bet::whereHas('bankroll', function ($q) use ($user) {
            // Assuming bankroll has user_id or similar relationship
        })
            ->whereBetween('placed_at', [$dates['start'], $dates['end']])
            ->whereIn('status', ['won', 'lost'])
            ->get();

        // Créer les nouvelles stats
        $stats = UserStatistics::getOrCreateForPeriod($user->id, $period);

        foreach ($bets as $bet) {
            $won = $bet->status === 'won';
            $stats->recordBet(
                (float) $bet->stake,
                (float) $bet->odds_decimal,
                $won,
                (float) $bet->profit_loss
            );
        }

        return $stats;
    }

    /**
     * Obtenir le résumé du dashboard.
     */
    public function getDashboardSummary(User $user): array
    {
        $daily = $this->getStatsForPeriod($user, StatsPeriod::DAILY);
        $weekly = $this->getStatsForPeriod($user, StatsPeriod::WEEKLY);
        $monthly = $this->getStatsForPeriod($user, StatsPeriod::MONTHLY);
        $allTime = $this->getStatsForPeriod($user, StatsPeriod::ALL_TIME);

        return [
            'today' => $daily ? [
                'bets' => $daily->total_bets,
                'profit' => $daily->total_profit,
            ] : ['bets' => 0, 'profit' => 0],

            'this_week' => $weekly ? [
                'bets' => $weekly->total_bets,
                'profit' => $weekly->total_profit,
                'win_rate' => round($weekly->win_rate * 100, 1),
            ] : ['bets' => 0, 'profit' => 0, 'win_rate' => 0],

            'this_month' => $monthly ? [
                'bets' => $monthly->total_bets,
                'profit' => $monthly->total_profit,
                'roi' => round($monthly->roi_percentage, 2),
            ] : ['bets' => 0, 'profit' => 0, 'roi' => 0],

            'all_time' => $allTime ? [
                'bets' => $allTime->total_bets,
                'profit' => $allTime->total_profit,
                'win_rate' => round($allTime->win_rate * 100, 1),
                'roi' => round($allTime->roi_percentage, 2),
            ] : ['bets' => 0, 'profit' => 0, 'win_rate' => 0, 'roi' => 0],
        ];
    }
}
