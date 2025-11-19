<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User;

use App\Domains\User\Models\UserPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    protected $model = UserPreference::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notify_predictions' => true,
            'notify_value_bets' => true,
            'notify_results' => true,
            'notify_bankroll_alerts' => true,
            'email_enabled' => true,
            'push_enabled' => fake()->boolean(),
            'sms_enabled' => false,
            'min_confidence_alert' => fake()->randomElement([60, 70, 80]),
            'min_edge_alert' => fake()->randomElement([3, 5, 7, 10]),
            'favorite_teams' => fake()->randomElements([1, 2, 3, 4, 5], 2),
            'excluded_teams' => [],
            'default_bet_type' => 'moneyline',
            'preferred_odds_format' => fake()->randomElement(['decimal', 'american']),
            'default_stake_percentage' => fake()->randomElement([1, 2, 3, 5]),
            'preferred_bookmakers' => ['Bet365', 'DraftKings'],
            'timezone' => fake()->randomElement(['America/New_York', 'America/Los_Angeles', 'Europe/Paris']),
            'language' => 'fr',
            'theme' => fake()->randomElement(['light', 'dark']),
            'show_advanced_stats' => fake()->boolean(),
            'daily_loss_limit' => fake()->optional()->randomFloat(2, 50, 200),
            'weekly_loss_limit' => fake()->optional()->randomFloat(2, 200, 500),
            'monthly_loss_limit' => fake()->optional()->randomFloat(2, 500, 2000),
            'max_drawdown_alert' => 20,
        ];
    }

    /**
     * All notifications disabled.
     */
    public function notificationsDisabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'notify_predictions' => false,
            'notify_value_bets' => false,
            'notify_results' => false,
            'notify_bankroll_alerts' => false,
        ]);
    }

    /**
     * All channels enabled.
     */
    public function allChannels(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => true,
        ]);
    }

    /**
     * High confidence threshold.
     */
    public function highThreshold(): static
    {
        return $this->state(fn (array $attributes) => [
            'min_confidence_alert' => 85,
            'min_edge_alert' => 10,
        ]);
    }

    /**
     * With loss limits.
     */
    public function withLimits(): static
    {
        return $this->state(fn (array $attributes) => [
            'daily_loss_limit' => 100,
            'weekly_loss_limit' => 300,
            'monthly_loss_limit' => 1000,
        ]);
    }
}
