<?php

declare(strict_types=1);

namespace App\Domains\ValueBets\Models;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\Prediction\Models\Prediction;
use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BetType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $bankroll_id
 * @property int $game_id
 * @property string $bet_type
 * @property string $selection
 * @property float $odds_decimal
 * @property float $stake
 * @property float $potential_payout
 * @property float $implied_probability
 * @property float $estimated_probability
 * @property float $expected_value
 * @property float $edge_percentage
 * @property string $status
 * @property float|null $profit_loss
 */
class Bet extends Model
{
    use HasFactory;

    protected $fillable = [
        'bankroll_id',
        'game_id',
        'prediction_id',
        'bet_type',
        'selection',
        'description',
        'odds_decimal',
        'odds_american',
        'stake',
        'potential_payout',
        'actual_payout',
        'implied_probability',
        'estimated_probability',
        'expected_value',
        'edge_percentage',
        'kelly_fraction',
        'kelly_stake',
        'confidence_score',
        'confidence_level',
        'line',
        'bookmaker',
        'bet_slip_id',
        'status',
        'placed_at',
        'settled_at',
        'profit_loss',
        'is_correct',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'odds_decimal' => 'decimal:3',
        'odds_american' => 'decimal:2',
        'stake' => 'decimal:2',
        'potential_payout' => 'decimal:2',
        'actual_payout' => 'decimal:2',
        'implied_probability' => 'decimal:4',
        'estimated_probability' => 'decimal:4',
        'expected_value' => 'decimal:4',
        'edge_percentage' => 'decimal:4',
        'kelly_fraction' => 'decimal:4',
        'kelly_stake' => 'decimal:2',
        'confidence_score' => 'decimal:2',
        'line' => 'decimal:2',
        'placed_at' => 'datetime',
        'settled_at' => 'datetime',
        'profit_loss' => 'decimal:2',
        'is_correct' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Relation avec le bankroll.
     */
    public function bankroll(): BelongsTo
    {
        return $this->belongsTo(Bankroll::class);
    }

    /**
     * Relation avec le match.
     */
    public function game(): BelongsTo
    {
        return $this->belongsTo(Game::class);
    }

    /**
     * Relation avec la prédiction.
     */
    public function prediction(): BelongsTo
    {
        return $this->belongsTo(Prediction::class);
    }

    /**
     * Obtenir le statut en enum.
     */
    public function getStatusEnum(): BetStatus
    {
        return BetStatus::from($this->status);
    }

    /**
     * Obtenir le type en enum.
     */
    public function getBetTypeEnum(): BetType
    {
        return BetType::from($this->bet_type);
    }

    /**
     * Vérifier si le pari a de la valeur.
     */
    public function hasValue(): bool
    {
        return $this->edge_percentage > 0;
    }

    /**
     * Résoudre le pari.
     */
    public function settle(BetStatus $result): void
    {
        $this->status = $result->value;
        $this->settled_at = now();

        $profitMultiplier = $result->profitMultiplier();

        if ($result === BetStatus::WON) {
            $this->actual_payout = $this->potential_payout;
            $this->profit_loss = $this->potential_payout - $this->stake;
            $this->is_correct = true;
        } elseif ($result === BetStatus::LOST) {
            $this->actual_payout = 0;
            $this->profit_loss = -$this->stake;
            $this->is_correct = false;
        } else {
            // PUSH, VOID, CANCELLED
            $this->actual_payout = $this->stake;
            $this->profit_loss = 0;
            $this->is_correct = null;
        }

        $this->save();

        // Mettre à jour le bankroll
        $this->bankroll->settleBet($this);
    }

    /**
     * Calculer les cotes américaines depuis décimales.
     */
    public static function decimalToAmerican(float $decimal): int
    {
        if ($decimal >= 2.0) {
            return (int) round(($decimal - 1) * 100);
        }
        return (int) round(-100 / ($decimal - 1));
    }

    /**
     * Calculer la probabilité implicite.
     */
    public static function calculateImpliedProbability(float $odds): float
    {
        return 1 / $odds;
    }

    /**
     * Calculer l'Expected Value.
     */
    public static function calculateExpectedValue(float $probability, float $odds, float $stake): float
    {
        $potentialProfit = $stake * ($odds - 1);
        $expectedWin = $probability * $potentialProfit;
        $expectedLoss = (1 - $probability) * $stake;

        return $expectedWin - $expectedLoss;
    }

    /**
     * Calculer le Edge (avantage).
     */
    public static function calculateEdge(float $estimatedProb, float $impliedProb): float
    {
        return (($estimatedProb - $impliedProb) / $impliedProb) * 100;
    }

    /**
     * Scope: Paris en attente.
     */
    public function scopePending($query)
    {
        return $query->where('status', BetStatus::PENDING->value);
    }

    /**
     * Scope: Paris résolus.
     */
    public function scopeSettled($query)
    {
        return $query->whereIn('status', [
            BetStatus::WON->value,
            BetStatus::LOST->value,
            BetStatus::PUSH->value,
        ]);
    }

    /**
     * Scope: Paris gagnants.
     */
    public function scopeWon($query)
    {
        return $query->where('status', BetStatus::WON->value);
    }

    /**
     * Scope: Paris perdants.
     */
    public function scopeLost($query)
    {
        return $query->where('status', BetStatus::LOST->value);
    }

    /**
     * Scope: Paris avec valeur.
     */
    public function scopeWithValue($query)
    {
        return $query->where('edge_percentage', '>', 0);
    }

    /**
     * Scope: Paris par type.
     */
    public function scopeOfType($query, BetType $type)
    {
        return $query->where('bet_type', $type->value);
    }
}
