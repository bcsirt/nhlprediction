<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Models;

use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BettingStrategy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property float $initial_amount
 * @property float $current_amount
 * @property string $currency
 * @property string $betting_strategy
 * @property float $kelly_fraction
 * @property float $max_bet_percentage
 * @property int $total_bets
 * @property int $winning_bets
 * @property int $losing_bets
 * @property float $total_wagered
 * @property float $total_profit
 * @property float $roi_percentage
 * @property float $max_drawdown
 * @property float $peak_amount
 * @property int $current_streak
 */
class Bankroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'initial_amount',
        'current_amount',
        'currency',
        'betting_strategy',
        'kelly_fraction',
        'max_bet_percentage',
        'min_bet_amount',
        'max_bet_amount',
        'total_bets',
        'winning_bets',
        'losing_bets',
        'pending_bets',
        'total_wagered',
        'total_profit',
        'roi_percentage',
        'max_drawdown',
        'current_drawdown',
        'peak_amount',
        'current_streak',
        'best_streak',
        'worst_streak',
        'is_active',
    ];

    protected $casts = [
        'initial_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'kelly_fraction' => 'decimal:2',
        'max_bet_percentage' => 'decimal:2',
        'min_bet_amount' => 'decimal:2',
        'max_bet_amount' => 'decimal:2',
        'total_wagered' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'roi_percentage' => 'decimal:4',
        'max_drawdown' => 'decimal:2',
        'current_drawdown' => 'decimal:2',
        'peak_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Obtenir tous les paris associés.
     */
    public function bets(): HasMany
    {
        return $this->hasMany(Bet::class);
    }

    /**
     * Obtenir la stratégie de mise en enum.
     */
    public function getStrategyEnum(): BettingStrategy
    {
        return BettingStrategy::from($this->betting_strategy);
    }

    /**
     * Calculer la mise recommandée pour un pari.
     */
    public function calculateStake(float $probability, float $odds): float
    {
        $strategy = $this->getStrategyEnum();
        $stake = $strategy->calculateStake(
            $this->current_amount,
            $probability,
            $odds,
            $this->max_bet_percentage / 100
        );

        // Appliquer les limites
        $stake = max($stake, $this->min_bet_amount);
        if ($this->max_bet_amount) {
            $stake = min($stake, $this->max_bet_amount);
        }

        // Max percentage du bankroll
        $maxStake = $this->current_amount * ($this->max_bet_percentage / 100);
        $stake = min($stake, $maxStake);

        return round($stake, 2);
    }

    /**
     * Enregistrer un pari et mettre à jour les statistiques.
     */
    public function recordBet(Bet $bet): void
    {
        $this->total_bets++;
        $this->total_wagered += $bet->stake;
        $this->pending_bets++;
        $this->save();
    }

    /**
     * Résoudre un pari et mettre à jour les statistiques.
     */
    public function settleBet(Bet $bet): void
    {
        $this->pending_bets--;

        $status = BetStatus::from($bet->status);

        if ($status->isWin()) {
            $this->winning_bets++;
            $this->current_amount += $bet->profit_loss;
            $this->total_profit += $bet->profit_loss;
            $this->current_streak = $this->current_streak > 0 ? $this->current_streak + 1 : 1;
            $this->best_streak = max($this->best_streak, $this->current_streak);
        } elseif ($status === BetStatus::LOST) {
            $this->losing_bets++;
            $this->current_amount += $bet->profit_loss; // profit_loss est négatif
            $this->total_profit += $bet->profit_loss;
            $this->current_streak = $this->current_streak < 0 ? $this->current_streak - 1 : -1;
            $this->worst_streak = min($this->worst_streak, $this->current_streak);
        }
        // PUSH et VOID ne changent pas le streak

        // Mettre à jour le ROI
        if ($this->total_wagered > 0) {
            $this->roi_percentage = ($this->total_profit / $this->total_wagered) * 100;
        }

        // Mettre à jour le drawdown
        if ($this->current_amount > $this->peak_amount) {
            $this->peak_amount = $this->current_amount;
            $this->current_drawdown = 0;
        } else {
            $this->current_drawdown = (($this->peak_amount - $this->current_amount) / $this->peak_amount) * 100;
            $this->max_drawdown = max($this->max_drawdown, $this->current_drawdown);
        }

        $this->save();
    }

    /**
     * Obtenir le taux de victoire.
     */
    public function getWinRateAttribute(): float
    {
        $settled = $this->winning_bets + $this->losing_bets;
        if ($settled === 0) {
            return 0;
        }
        return round(($this->winning_bets / $settled) * 100, 2);
    }

    /**
     * Obtenir le ratio profit/perte.
     */
    public function getProfitFactorAttribute(): float
    {
        if ($this->losing_bets === 0) {
            return $this->winning_bets > 0 ? 999.99 : 0;
        }

        $avgWin = $this->winning_bets > 0
            ? $this->total_profit / $this->winning_bets
            : 0;
        $avgLoss = abs($this->total_profit) / $this->losing_bets;

        return $avgLoss > 0 ? round($avgWin / $avgLoss, 2) : 0;
    }

    /**
     * Obtenir le résumé des performances.
     */
    public function getSummary(): array
    {
        return [
            'bankroll' => $this->current_amount,
            'initial' => $this->initial_amount,
            'profit' => $this->total_profit,
            'roi' => round($this->roi_percentage, 2) . '%',
            'win_rate' => $this->win_rate . '%',
            'total_bets' => $this->total_bets,
            'record' => "{$this->winning_bets}W-{$this->losing_bets}L",
            'streak' => $this->current_streak,
            'max_drawdown' => round($this->max_drawdown, 2) . '%',
        ];
    }

    /**
     * Scope: Bankrolls actifs.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
