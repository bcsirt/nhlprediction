<?php

declare(strict_types=1);

namespace App\Domains\Prediction\Services;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Team;
use App\Domains\Prediction\Models\GameFeatures;
use App\Domains\Prediction\Models\TeamFeatures;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service pour extraire les features des équipes et matchs.
 */
class FeatureExtractor
{
    /**
     * Extraire les features pour une équipe à une date donnée.
     */
    public function extractTeamFeatures(Team $team, int $seasonId, ?Carbon $date = null): TeamFeatures
    {
        $date = $date ?? now();

        // Récupérer les matchs précédents
        $games = $this->getTeamGames($team->id, $seasonId, $date);

        if ($games->isEmpty()) {
            return $this->createEmptyTeamFeatures($team->id, $seasonId, $date);
        }

        // Calculer rolling averages
        $last5 = $games->take(5);
        $last10 = $games->take(10);

        $features = new TeamFeatures([
            'team_id' => $team->id,
            'season_id' => $seasonId,
            'calculated_at' => $date->toDateString(),

            // Rolling averages 5 matchs
            'rolling_goals_for_5' => $this->avgGoalsFor($last5, $team->id),
            'rolling_goals_against_5' => $this->avgGoalsAgainst($last5, $team->id),
            'rolling_shots_for_5' => $this->avgShotsFor($last5, $team->id),
            'rolling_shots_against_5' => $this->avgShotsAgainst($last5, $team->id),

            // Rolling averages 10 matchs
            'rolling_goals_for_10' => $this->avgGoalsFor($last10, $team->id),
            'rolling_goals_against_10' => $this->avgGoalsAgainst($last10, $team->id),

            // Form indicators
            'wins_last_5' => $this->countWins($last5, $team->id),
            'wins_last_10' => $this->countWins($last10, $team->id),
            'current_streak' => $this->calculateStreak($games, $team->id),
            'streak_type' => $this->getStreakType($games, $team->id),
            'points_percentage' => $this->calculatePointsPercentage($games, $team->id),

            // Home/Away splits
            'home_win_percentage' => $this->homeWinPercentage($games, $team->id),
            'away_win_percentage' => $this->awayWinPercentage($games, $team->id),
            'home_goals_avg' => $this->homeGoalsAvg($games, $team->id),
            'away_goals_avg' => $this->awayGoalsAvg($games, $team->id),

            // Rest
            'days_since_last_game' => $this->daysSinceLastGame($games, $date),
            'is_back_to_back' => $this->isBackToBack($games, $date),
        ]);

        $features->save();

        return $features;
    }

    /**
     * Extraire les features pour un match.
     */
    public function extractGameFeatures(Game $game): GameFeatures
    {
        $date = Carbon::parse($game->game_date);

        // Obtenir les features des deux équipes
        $homeFeatures = TeamFeatures::latestForTeam($game->home_team_id, $game->season_id)
            ?? $this->extractTeamFeatures($game->homeTeam, $game->season_id, $date);

        $awayFeatures = TeamFeatures::latestForTeam($game->away_team_id, $game->season_id)
            ?? $this->extractTeamFeatures($game->awayTeam, $game->season_id, $date);

        // Calculer les différences et avantages
        $features = new GameFeatures([
            'game_id' => $game->id,
            'home_team_id' => $game->home_team_id,
            'away_team_id' => $game->away_team_id,

            // Différences relatives
            'goal_diff_advantage' => ($homeFeatures->rolling_goals_for_5 ?? 0) - ($awayFeatures->rolling_goals_for_5 ?? 0),
            'shot_diff_advantage' => ($homeFeatures->rolling_shots_for_5 ?? 0) - ($awayFeatures->rolling_shots_for_5 ?? 0),
            'expected_goals_diff' => ($homeFeatures->avg_expected_goals_for ?? 0) - ($awayFeatures->avg_expected_goals_for ?? 0),

            // Form comparison
            'form_diff_5' => ($homeFeatures->wins_last_5 ?? 0) - ($awayFeatures->wins_last_5 ?? 0),
            'form_diff_10' => ($homeFeatures->wins_last_10 ?? 0) - ($awayFeatures->wins_last_10 ?? 0),
            'streak_advantage' => ($homeFeatures->current_streak ?? 0) - ($awayFeatures->current_streak ?? 0),

            // Special teams
            'pp_advantage' => ($homeFeatures->power_play_percentage ?? 0) - ($awayFeatures->power_play_percentage ?? 0),
            'pk_advantage' => ($homeFeatures->penalty_kill_percentage ?? 0) - ($awayFeatures->penalty_kill_percentage ?? 0),

            // Rest advantage
            'rest_advantage' => ($homeFeatures->days_since_last_game ?? 2) - ($awayFeatures->days_since_last_game ?? 2),
            'home_back_to_back' => $homeFeatures->is_back_to_back ?? false,
            'away_back_to_back' => $awayFeatures->is_back_to_back ?? false,

            // Home ice (avantage typique NHL ~54%)
            'home_ice_factor' => 0.54,

            // H2H
            'h2h_wins_home' => $this->getH2HWins($game->home_team_id, $game->away_team_id, $game->season_id),
            'h2h_wins_away' => $this->getH2HWins($game->away_team_id, $game->home_team_id, $game->season_id),
            'h2h_games_count' => $this->getH2HGamesCount($game->home_team_id, $game->away_team_id, $game->season_id),

            // Momentum scores
            'home_momentum_score' => $homeFeatures->form_score ?? 50,
            'away_momentum_score' => $awayFeatures->form_score ?? 50,

            // Matchup scores
            'offense_matchup_score' => $this->calculateOffenseMatchup($homeFeatures, $awayFeatures),
            'defense_matchup_score' => $this->calculateDefenseMatchup($homeFeatures, $awayFeatures),
            'overall_matchup_score' => $this->calculateOverallMatchup($homeFeatures, $awayFeatures),
        ]);

        $features->save();

        return $features;
    }

    /**
     * Extraire les features pour tous les matchs d'une date.
     */
    public function extractFeaturesForDate(Carbon $date): int
    {
        $games = Game::whereDate('game_date', $date)
            ->where('status', 'scheduled')
            ->get();

        $count = 0;
        foreach ($games as $game) {
            try {
                $this->extractGameFeatures($game);
                $count++;
            } catch (\Exception $e) {
                Log::error("Feature extraction failed for game {$game->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $count;
    }

    // ========== PRIVATE HELPER METHODS ==========

    private function getTeamGames(int $teamId, int $seasonId, Carbon $beforeDate)
    {
        return Game::where('season_id', $seasonId)
            ->where('status', 'final')
            ->where('game_date', '<', $beforeDate)
            ->where(function ($q) use ($teamId) {
                $q->where('home_team_id', $teamId)
                    ->orWhere('away_team_id', $teamId);
            })
            ->orderBy('game_date', 'desc')
            ->limit(20)
            ->get();
    }

    private function avgGoalsFor($games, int $teamId): float
    {
        if ($games->isEmpty()) {
            return 0;
        }

        $total = $games->sum(function ($game) use ($teamId) {
            return $game->home_team_id === $teamId ? $game->home_score : $game->away_score;
        });

        return round($total / $games->count(), 2);
    }

    private function avgGoalsAgainst($games, int $teamId): float
    {
        if ($games->isEmpty()) {
            return 0;
        }

        $total = $games->sum(function ($game) use ($teamId) {
            return $game->home_team_id === $teamId ? $game->away_score : $game->home_score;
        });

        return round($total / $games->count(), 2);
    }

    private function avgShotsFor($games, int $teamId): float
    {
        // Pour l'instant, estimation basée sur les buts (ratio moyen ~10%)
        return $this->avgGoalsFor($games, $teamId) * 10;
    }

    private function avgShotsAgainst($games, int $teamId): float
    {
        return $this->avgGoalsAgainst($games, $teamId) * 10;
    }

    private function countWins($games, int $teamId): int
    {
        return $games->filter(function ($game) use ($teamId) {
            if ($game->home_team_id === $teamId) {
                return $game->home_score > $game->away_score;
            }
            return $game->away_score > $game->home_score;
        })->count();
    }

    private function calculateStreak($games, int $teamId): int
    {
        $streak = 0;
        $lastResult = null;

        foreach ($games as $game) {
            $isWin = $game->home_team_id === $teamId
                ? $game->home_score > $game->away_score
                : $game->away_score > $game->home_score;

            if ($lastResult === null) {
                $lastResult = $isWin;
                $streak = $isWin ? 1 : -1;
            } elseif ($isWin === $lastResult) {
                $streak += $isWin ? 1 : -1;
            } else {
                break;
            }
        }

        return $streak;
    }

    private function getStreakType($games, int $teamId): ?string
    {
        if ($games->isEmpty()) {
            return null;
        }

        $game = $games->first();
        $isWin = $game->home_team_id === $teamId
            ? $game->home_score > $game->away_score
            : $game->away_score > $game->home_score;

        return $isWin ? 'W' : 'L';
    }

    private function calculatePointsPercentage($games, int $teamId): float
    {
        if ($games->isEmpty()) {
            return 0;
        }

        $points = $games->sum(function ($game) use ($teamId) {
            $isHome = $game->home_team_id === $teamId;
            $teamScore = $isHome ? $game->home_score : $game->away_score;
            $oppScore = $isHome ? $game->away_score : $game->home_score;

            if ($teamScore > $oppScore) {
                return 2;
            }
            if ($teamScore === $oppScore || $game->overtime) {
                return 1;
            }
            return 0;
        });

        $maxPoints = $games->count() * 2;
        return round(($points / $maxPoints) * 100, 2);
    }

    private function homeWinPercentage($games, int $teamId): float
    {
        $homeGames = $games->where('home_team_id', $teamId);
        if ($homeGames->isEmpty()) {
            return 50;
        }

        $wins = $homeGames->filter(fn($g) => $g->home_score > $g->away_score)->count();
        return round(($wins / $homeGames->count()) * 100, 2);
    }

    private function awayWinPercentage($games, int $teamId): float
    {
        $awayGames = $games->where('away_team_id', $teamId);
        if ($awayGames->isEmpty()) {
            return 50;
        }

        $wins = $awayGames->filter(fn($g) => $g->away_score > $g->home_score)->count();
        return round(($wins / $awayGames->count()) * 100, 2);
    }

    private function homeGoalsAvg($games, int $teamId): float
    {
        $homeGames = $games->where('home_team_id', $teamId);
        if ($homeGames->isEmpty()) {
            return 3;
        }

        return round($homeGames->avg('home_score'), 2);
    }

    private function awayGoalsAvg($games, int $teamId): float
    {
        $awayGames = $games->where('away_team_id', $teamId);
        if ($awayGames->isEmpty()) {
            return 2.5;
        }

        return round($awayGames->avg('away_score'), 2);
    }

    private function daysSinceLastGame($games, Carbon $date): int
    {
        if ($games->isEmpty()) {
            return 3;
        }

        $lastGame = $games->first();
        return $date->diffInDays(Carbon::parse($lastGame->game_date));
    }

    private function isBackToBack($games, Carbon $date): bool
    {
        return $this->daysSinceLastGame($games, $date) <= 1;
    }

    private function getH2HWins(int $teamId, int $opponentId, int $seasonId): int
    {
        return Game::where('season_id', $seasonId)
            ->where('status', 'final')
            ->where(function ($q) use ($teamId, $opponentId) {
                $q->where(function ($q2) use ($teamId, $opponentId) {
                    $q2->where('home_team_id', $teamId)
                        ->where('away_team_id', $opponentId)
                        ->whereColumn('home_score', '>', 'away_score');
                })->orWhere(function ($q2) use ($teamId, $opponentId) {
                    $q2->where('away_team_id', $teamId)
                        ->where('home_team_id', $opponentId)
                        ->whereColumn('away_score', '>', 'home_score');
                });
            })
            ->count();
    }

    private function getH2HGamesCount(int $team1Id, int $team2Id, int $seasonId): int
    {
        return Game::where('season_id', $seasonId)
            ->where('status', 'final')
            ->where(function ($q) use ($team1Id, $team2Id) {
                $q->where(function ($q2) use ($team1Id, $team2Id) {
                    $q2->where('home_team_id', $team1Id)->where('away_team_id', $team2Id);
                })->orWhere(function ($q2) use ($team1Id, $team2Id) {
                    $q2->where('home_team_id', $team2Id)->where('away_team_id', $team1Id);
                });
            })
            ->count();
    }

    private function calculateOffenseMatchup(TeamFeatures $home, TeamFeatures $away): float
    {
        $homeOffense = ($home->rolling_goals_for_5 ?? 2.5);
        $awayDefense = ($away->rolling_goals_against_5 ?? 2.5);

        return round(($homeOffense - $awayDefense) * 10, 2);
    }

    private function calculateDefenseMatchup(TeamFeatures $home, TeamFeatures $away): float
    {
        $homeDefense = ($home->rolling_goals_against_5 ?? 2.5);
        $awayOffense = ($away->rolling_goals_for_5 ?? 2.5);

        return round(($awayOffense - $homeDefense) * -10, 2);
    }

    private function calculateOverallMatchup(TeamFeatures $home, TeamFeatures $away): float
    {
        $formDiff = (($home->wins_last_5 ?? 2.5) - ($away->wins_last_5 ?? 2.5)) * 5;
        $goalDiff = (($home->rolling_goals_for_5 ?? 2.5) - ($away->rolling_goals_for_5 ?? 2.5)) * 3;
        $homeAdvantage = 3; // Points bonus pour jouer à domicile

        return round($formDiff + $goalDiff + $homeAdvantage, 2);
    }

    private function createEmptyTeamFeatures(int $teamId, int $seasonId, Carbon $date): TeamFeatures
    {
        $features = new TeamFeatures([
            'team_id' => $teamId,
            'season_id' => $seasonId,
            'calculated_at' => $date->toDateString(),
        ]);
        $features->save();
        return $features;
    }
}
