<?php

declare(strict_types=1);

namespace Database\Factories\Domains\ValueBets;

use App\Domains\DataIngestion\Models\Game;
use App\Domains\ValueBets\Enums\BetStatus;
use App\Domains\ValueBets\Enums\BetType;
use App\Domains\ValueBets\Models\Bankroll;
use App\Domains\ValueBets\Models\Bet;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bet>
 */
class BetFactory extends Factory
{
    protected $model = Bet::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $odds = fake()->randomFloat(3, 1.5, 3.5);
        $stake = fake()->randomFloat(2, 10, 200);
        $potentialPayout = $stake * $odds;
        $impliedProb = 1 / $odds;
        $estimatedProb = $impliedProb + fake()->randomFloat(4, -0.1, 0.15);
        $estimatedProb = max(0.1, min(0.9, $estimatedProb));

        return [
            'bankroll_id' => Bankroll::factory(),
            'game_id' => Game::factory(),
            'prediction_id' => null,
            'bet_type' => fake()->randomElement(BetType::cases())->value,
            'selection' => fake()->randomElement(['home', 'away']),
            'description' => fake()->sentence(),
            'odds_decimal' => $odds,
            'odds_american' => $odds >= 2.0 ? round(($odds - 1) * 100) : round(-100 / ($odds - 1)),
            'stake' => $stake,
            'potential_payout' => $potentialPayout,
            'actual_payout' => null,
            'implied_probability' => $impliedProb,
            'estimated_probability' => $estimatedProb,
            'expected_value' => ($estimatedProb * ($odds - 1) * $stake) - ((1 - $estimatedProb) * $stake),
            'edge_percentage' => (($estimatedProb - $impliedProb) / $impliedProb) * 100,
            'kelly_fraction' => fake()->randomFloat(4, 0, 0.15),
            'kelly_stake' => fake()->randomFloat(2, 20, 300),
            'confidence_score' => fake()->randomFloat(2, 50, 90),
            'confidence_level' => fake()->randomElement(['medium', 'high', 'very_high']),
            'line' => null,
            'bookmaker' => fake()->randomElement(['Bet365', 'DraftKings', 'FanDuel', 'BetMGM']),
            'bet_slip_id' => fake()->uuid(),
            'status' => BetStatus::PENDING->value,
            'placed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'settled_at' => null,
            'profit_loss' => null,
            'is_correct' => null,
            'metadata' => null,
            'notes' => null,
        ];
    }

    /**
     * Pari gagné.
     */
    public function won(): static
    {
        return $this->state(function (array $attributes) {
            $profit = $attributes['potential_payout'] - $attributes['stake'];
            return [
                'status' => BetStatus::WON->value,
                'settled_at' => fake()->dateTimeBetween($attributes['placed_at'], 'now'),
                'actual_payout' => $attributes['potential_payout'],
                'profit_loss' => $profit,
                'is_correct' => true,
            ];
        });
    }

    /**
     * Pari perdu.
     */
    public function lost(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BetStatus::LOST->value,
                'settled_at' => fake()->dateTimeBetween($attributes['placed_at'], 'now'),
                'actual_payout' => 0,
                'profit_loss' => -$attributes['stake'],
                'is_correct' => false,
            ];
        });
    }

    /**
     * Pari push (égalité).
     */
    public function push(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => BetStatus::PUSH->value,
                'settled_at' => fake()->dateTimeBetween($attributes['placed_at'], 'now'),
                'actual_payout' => $attributes['stake'],
                'profit_loss' => 0,
                'is_correct' => null,
            ];
        });
    }

    /**
     * Pari moneyline.
     */
    public function moneyline(): static
    {
        return $this->state(fn (array $attributes) => [
            'bet_type' => BetType::MONEYLINE->value,
            'line' => null,
        ]);
    }

    /**
     * Pari spread.
     */
    public function spread(): static
    {
        return $this->state(fn (array $attributes) => [
            'bet_type' => BetType::SPREAD->value,
            'line' => fake()->randomElement([-2.5, -1.5, 1.5, 2.5]),
        ]);
    }

    /**
     * Pari over/under.
     */
    public function overUnder(): static
    {
        return $this->state(fn (array $attributes) => [
            'bet_type' => BetType::OVER_UNDER->value,
            'selection' => fake()->randomElement(['over', 'under']),
            'line' => fake()->randomElement([5.5, 6.0, 6.5]),
        ]);
    }

    /**
     * Pari avec valeur (+EV).
     */
    public function withValue(): static
    {
        return $this->state(function (array $attributes) {
            $impliedProb = $attributes['implied_probability'];
            $estimatedProb = $impliedProb + fake()->randomFloat(4, 0.05, 0.15);
            $estimatedProb = min(0.9, $estimatedProb);

            return [
                'estimated_probability' => $estimatedProb,
                'expected_value' => ($estimatedProb * ($attributes['odds_decimal'] - 1) * $attributes['stake']) - ((1 - $estimatedProb) * $attributes['stake']),
                'edge_percentage' => (($estimatedProb - $impliedProb) / $impliedProb) * 100,
            ];
        });
    }

    /**
     * Pari haute confiance.
     */
    public function highConfidence(): static
    {
        return $this->state(fn (array $attributes) => [
            'confidence_score' => fake()->randomFloat(2, 75, 95),
            'confidence_level' => fake()->randomElement(['high', 'very_high']),
        ]);
    }
}
