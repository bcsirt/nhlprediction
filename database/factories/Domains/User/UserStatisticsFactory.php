<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User;

use App\Domains\User\Enums\StatsPeriod;
use App\Domains\User\Models\UserStatistics;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserStatistics>
 */
class UserStatisticsFactory extends Factory
{
    protected $model = UserStatistics::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $period = $this->faker->randomElement(StatsPeriod::cases());
        $dates = $period->getCurrentPeriod();
        $totalBets = $this->faker->numberBetween(5, 50);
        $winningBets = $this->faker->numberBetween(0, $totalBets);
        $totalStaked = $this->faker->randomFloat(2, 100, 1000);
        $totalProfit = $this->faker->randomFloat(2, -200, 400);

        return [
            'user_id' => User::factory(),
            'period_type' => $period->value,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
            'total_bets' => $totalBets,
            'winning_bets' => $winningBets,
            'losing_bets' => $totalBets - $winningBets,
            'win_rate' => $totalBets > 0 ? $winningBets / $totalBets : 0,
            'total_staked' => $totalStaked,
            'total_profit' => $totalProfit,
            'roi_percentage' => $totalStaked > 0 ? ($totalProfit / $totalStaked) * 100 : 0,
            'average_odds' => $this->faker->randomFloat(3, 1.5, 3.0),
            'average_stake' => $totalBets > 0 ? $totalStaked / $totalBets : 0,
            'stats_by_bet_type' => null,
            'stats_by_confidence' => null,
            'stats_by_team' => null,
            'best_streak' => $this->faker->numberBetween(1, 10),
            'worst_streak' => $this->faker->numberBetween(1, 5),
            'max_drawdown' => $this->faker->randomFloat(2, 0, 30),
            'predictions_viewed' => $this->faker->numberBetween(10, 100),
            'value_bets_found' => $this->faker->numberBetween(5, 30),
            'value_bets_taken' => $this->faker->numberBetween(0, 20),
        ];
    }

    /**
     * Daily period.
     */
    public function daily(): static
    {
        $dates = StatsPeriod::DAILY->getCurrentPeriod();

        return $this->state(fn (array $attributes) => [
            'period_type' => StatsPeriod::DAILY->value,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
        ]);
    }

    /**
     * Weekly period.
     */
    public function weekly(): static
    {
        $dates = StatsPeriod::WEEKLY->getCurrentPeriod();

        return $this->state(fn (array $attributes) => [
            'period_type' => StatsPeriod::WEEKLY->value,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
        ]);
    }

    /**
     * Monthly period.
     */
    public function monthly(): static
    {
        $dates = StatsPeriod::MONTHLY->getCurrentPeriod();

        return $this->state(fn (array $attributes) => [
            'period_type' => StatsPeriod::MONTHLY->value,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
        ]);
    }

    /**
     * All-time period.
     */
    public function allTime(): static
    {
        $dates = StatsPeriod::ALL_TIME->getCurrentPeriod();

        return $this->state(fn (array $attributes) => [
            'period_type' => StatsPeriod::ALL_TIME->value,
            'period_start' => $dates['start']->toDateString(),
            'period_end' => $dates['end']->toDateString(),
        ]);
    }

    /**
     * Profitable period.
     */
    public function profitable(): static
    {
        return $this->state(function (array $attributes) {
            $profit = $this->faker->randomFloat(2, 50, 500);
            $staked = $attributes['total_staked'] ?? 500;

            return [
                'total_profit' => $profit,
                'roi_percentage' => ($profit / $staked) * 100,
            ];
        });
    }

    /**
     * Losing period.
     */
    public function losing(): static
    {
        return $this->state(function (array $attributes) {
            $loss = $this->faker->randomFloat(2, -300, -50);
            $staked = $attributes['total_staked'] ?? 500;

            return [
                'total_profit' => $loss,
                'roi_percentage' => ($loss / $staked) * 100,
            ];
        });
    }

    /**
     * With team stats.
     */
    public function withTeamStats(): static
    {
        return $this->state(fn (array $attributes) => [
            'stats_by_team' => [
                'team_1' => ['bets' => 5, 'wins' => 3, 'roi' => 15.5],
                'team_2' => ['bets' => 8, 'wins' => 5, 'roi' => 8.2],
                'team_3' => ['bets' => 3, 'wins' => 1, 'roi' => -12.3],
            ],
        ]);
    }

    /**
     * With bet type stats.
     */
    public function withBetTypeStats(): static
    {
        return $this->state(fn (array $attributes) => [
            'stats_by_bet_type' => [
                'moneyline' => ['bets' => 10, 'wins' => 6, 'roi' => 12.5],
                'spread' => ['bets' => 5, 'wins' => 2, 'roi' => -8.0],
                'total' => ['bets' => 3, 'wins' => 2, 'roi' => 5.3],
            ],
        ]);
    }

    /**
     * With confidence stats.
     */
    public function withConfidenceStats(): static
    {
        return $this->state(fn (array $attributes) => [
            'stats_by_confidence' => [
                'high' => ['bets' => 5, 'wins' => 4, 'roi' => 22.5],
                'medium' => ['bets' => 10, 'wins' => 5, 'roi' => 3.2],
                'low' => ['bets' => 3, 'wins' => 1, 'roi' => -15.0],
            ],
        ]);
    }

    /**
     * Empty stats (no bets).
     */
    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'total_bets' => 0,
            'winning_bets' => 0,
            'losing_bets' => 0,
            'win_rate' => 0,
            'total_staked' => 0,
            'total_profit' => 0,
            'roi_percentage' => 0,
            'average_odds' => 0,
            'average_stake' => 0,
        ]);
    }

    /**
     * High win rate.
     */
    public function highWinRate(): static
    {
        return $this->state(function (array $attributes) {
            $totalBets = $attributes['total_bets'] ?? 20;
            $winningBets = (int) ($totalBets * 0.7);

            return [
                'winning_bets' => $winningBets,
                'losing_bets' => $totalBets - $winningBets,
                'win_rate' => $winningBets / $totalBets,
            ];
        });
    }
}
