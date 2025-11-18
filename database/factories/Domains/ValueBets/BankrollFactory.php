<?php

declare(strict_types=1);

namespace Database\Factories\Domains\ValueBets;

use App\Domains\ValueBets\Enums\BettingStrategy;
use App\Domains\ValueBets\Models\Bankroll;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bankroll>
 */
class BankrollFactory extends Factory
{
    protected $model = Bankroll::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $initial = fake()->randomFloat(2, 500, 10000);
        $profit = fake()->randomFloat(2, -500, 1000);
        $current = $initial + $profit;
        $peak = max($initial, $current);

        $totalBets = fake()->numberBetween(10, 200);
        $winRate = fake()->randomFloat(2, 0.45, 0.60);
        $wins = (int) round($totalBets * $winRate);
        $losses = $totalBets - $wins;

        return [
            'name' => fake()->words(2, true) . ' Bankroll',
            'description' => fake()->sentence(),
            'initial_amount' => $initial,
            'current_amount' => $current,
            'currency' => 'USD',
            'betting_strategy' => fake()->randomElement(BettingStrategy::cases())->value,
            'kelly_fraction' => fake()->randomElement([0.25, 0.5, 1.0]),
            'max_bet_percentage' => fake()->randomElement([2, 3, 5]),
            'min_bet_amount' => 10,
            'max_bet_amount' => 500,
            'total_bets' => $totalBets,
            'winning_bets' => $wins,
            'losing_bets' => $losses,
            'pending_bets' => 0,
            'total_wagered' => fake()->randomFloat(2, 1000, 20000),
            'total_profit' => $profit,
            'roi_percentage' => fake()->randomFloat(4, -10, 15),
            'max_drawdown' => fake()->randomFloat(2, 5, 25),
            'current_drawdown' => fake()->randomFloat(2, 0, 10),
            'peak_amount' => $peak,
            'current_streak' => fake()->numberBetween(-5, 5),
            'best_streak' => fake()->numberBetween(3, 10),
            'worst_streak' => fake()->numberBetween(-8, -3),
            'is_active' => true,
        ];
    }

    /**
     * Bankroll profitable.
     */
    public function profitable(): static
    {
        return $this->state(function (array $attributes) {
            $profit = fake()->randomFloat(2, 200, 2000);
            return [
                'current_amount' => $attributes['initial_amount'] + $profit,
                'total_profit' => $profit,
                'roi_percentage' => fake()->randomFloat(4, 5, 20),
                'peak_amount' => $attributes['initial_amount'] + $profit,
            ];
        });
    }

    /**
     * Bankroll en perte.
     */
    public function losing(): static
    {
        return $this->state(function (array $attributes) {
            $loss = fake()->randomFloat(2, 100, 500);
            return [
                'current_amount' => $attributes['initial_amount'] - $loss,
                'total_profit' => -$loss,
                'roi_percentage' => fake()->randomFloat(4, -20, -5),
                'current_drawdown' => fake()->randomFloat(2, 10, 25),
            ];
        });
    }

    /**
     * Nouveau bankroll sans historique.
     */
    public function fresh(): static
    {
        return $this->state(fn (array $attributes) => [
            'current_amount' => $attributes['initial_amount'],
            'total_bets' => 0,
            'winning_bets' => 0,
            'losing_bets' => 0,
            'total_wagered' => 0,
            'total_profit' => 0,
            'roi_percentage' => 0,
            'current_streak' => 0,
            'peak_amount' => $attributes['initial_amount'],
        ]);
    }

    /**
     * Bankroll utilisant stratégie Kelly.
     */
    public function kelly(): static
    {
        return $this->state(fn (array $attributes) => [
            'betting_strategy' => BettingStrategy::KELLY->value,
            'kelly_fraction' => 0.25,
        ]);
    }

    /**
     * Bankroll inactif.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
