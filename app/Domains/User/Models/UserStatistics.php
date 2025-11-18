<?php

declare(strict_types=1);

namespace App\Domains\User\Models;

use App\Domains\User\Enums\StatsPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property string $period_type
 * @property \Carbon\Carbon $period_start
 * @property \Carbon\Carbon $period_end
 * @property int $total_bets
 * @property int $winning_bets
 * @property float $win_rate
 * @property float $total_profit
 * @property float $roi_percentage
 */
class UserStatistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'total_bets',
        'winning_bets',
        'losing_bets',
        'win_rate',
        'total_staked',
        'total_profit',
        'roi_percentage',
        'average_odds',
        'average_stake',
        'stats_by_bet_type',
        'stats_by_confidence',
        'stats_by_team',
        'best_streak',
        'worst_streak',
        'max_drawdown',
        'predictions_viewed',
        'value_bets_found',
        'value_bets_taken',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'win_rate' => 'decimal:4',
        'total_staked' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'roi_percentage' => 'decimal:4',
        'average_odds' => 'decimal:3',
        'average_stake' => 'decimal:2',
        'stats_by_bet_type' => 'array',
        'stats_by_confidence' => 'array',
        'stats_by_team' => 'array',
        'max_drawdown' => 'decimal:2',
    ];

    /**
     * Relation avec l'utilisateur.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Obtenir le type de période en enum.
     */
    public function getPeriodEnum(): StatsPeriod
    {
        return StatsPeriod::from($this->period_type);
    }

    /**
     * Vérifier si la période est profitable.
     */
    public function isProfitable(): bool
    {
        return $this->total_profit > 0;
    }

    /**
     * Obtenir le nombre de paris perdus.
     */
    public function getLosingBetsAttribute(): int
    {
        return $this->total_bets - $this->winning_bets;
    }

    /**
     * Obtenir le record formaté.
     */
    public function getRecordAttribute(): string
    {
        return "{$this->winning_bets}W-{$this->losing_bets}L";
    }

    /**
     * Obtenir le résumé des statistiques.
     */
    public function getSummary(): array
    {
        return [
            'period' => $this->getPeriodEnum()->label(),
            'dates' => $this->period_start->format('d/m') . ' - ' . $this->period_end->format('d/m/Y'),
            'total_bets' => $this->total_bets,
            'record' => $this->record,
            'win_rate' => round($this->win_rate * 100, 1) . '%',
            'profit' => ($this->total_profit >= 0 ? '+' : '') . '$' . number_format($this->total_profit, 2),
            'roi' => round($this->roi_percentage, 2) . '%',
            'avg_odds' => number_format($this->average_odds, 2),
        ];
    }

    /**
     * Scope: Par type de période.
     */
    public function scopeForPeriod($query, StatsPeriod $period)
    {
        return $query->where('period_type', $period->value);
    }

    /**
     * Scope: Périodes rentables.
     */
    public function scopeProfitable($query)
    {
        return $query->where('total_profit', '>', 0);
    }

    /**
     * Obtenir ou créer les stats pour une période.
     */
    public static function getOrCreateForPeriod(int $userId, StatsPeriod $period): self
    {
        $dates = $period->getCurrentPeriod();

        return self::firstOrCreate(
            [
                'user_id' => $userId,
                'period_type' => $period->value,
                'period_start' => $dates['start']->toDateString(),
            ],
            [
                'period_end' => $dates['end']->toDateString(),
                'total_bets' => 0,
                'winning_bets' => 0,
                'losing_bets' => 0,
                'win_rate' => 0,
                'total_staked' => 0,
                'total_profit' => 0,
                'roi_percentage' => 0,
                'average_odds' => 0,
                'average_stake' => 0,
            ]
        );
    }

    /**
     * Mettre à jour les statistiques après un pari.
     */
    public function recordBet(float $stake, float $odds, bool $won, float $profitLoss): void
    {
        $this->total_bets++;
        $this->total_staked += $stake;

        if ($won) {
            $this->winning_bets++;
        }

        $this->total_profit += $profitLoss;

        // Recalculer les moyennes
        if ($this->total_bets > 0) {
            $this->win_rate = $this->winning_bets / $this->total_bets;
            $this->roi_percentage = ($this->total_profit / $this->total_staked) * 100;
            $this->average_stake = $this->total_staked / $this->total_bets;

            // Moyenne pondérée des cotes
            $this->average_odds = (($this->average_odds * ($this->total_bets - 1)) + $odds) / $this->total_bets;
        }

        $this->save();
    }
}
