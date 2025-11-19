<?php

declare(strict_types=1);

namespace Database\Factories\Domains\User;

use App\Domains\User\Enums\NotificationType;
use App\Domains\User\Models\Notification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        $type = fake()->randomElement(NotificationType::cases());

        return [
            'user_id' => User::factory(),
            'type' => $type->value,
            'title' => fake()->sentence(4),
            'message' => fake()->paragraph(),
            'icon' => $type->icon(),
            'color' => $type->color(),
            'notifiable_type' => null,
            'notifiable_id' => null,
            'data' => ['key' => 'value'],
            'action_url' => fake()->optional()->url(),
            'action_text' => fake()->optional()->words(2, true),
            'sent_email' => false,
            'sent_push' => false,
            'sent_sms' => false,
            'read_at' => null,
            'sent_at' => fake()->optional()->dateTimeBetween('-1 hour', 'now'),
            'is_important' => $type->isImportant(),
        ];
    }

    /**
     * Read notification.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => fake()->dateTimeBetween('-1 day', 'now'),
        ]);
    }

    /**
     * Unread notification.
     */
    public function unread(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => null,
        ]);
    }

    /**
     * Important notification.
     */
    public function important(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_important' => true,
        ]);
    }

    /**
     * Prediction notification.
     */
    public function prediction(): static
    {
        $type = NotificationType::PREDICTION;
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'is_important' => $type->isImportant(),
        ]);
    }

    /**
     * Value bet notification.
     */
    public function valueBet(): static
    {
        $type = NotificationType::VALUE_BET;
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'is_important' => $type->isImportant(),
        ]);
    }

    /**
     * Bankroll alert notification.
     */
    public function bankrollAlert(): static
    {
        $type = NotificationType::BANKROLL_ALERT;
        return $this->state(fn (array $attributes) => [
            'type' => $type->value,
            'icon' => $type->icon(),
            'color' => $type->color(),
            'is_important' => true,
        ]);
    }

    /**
     * Sent via email.
     */
    public function sentViaEmail(): static
    {
        return $this->state(fn (array $attributes) => [
            'sent_email' => true,
            'sent_at' => now(),
        ]);
    }

    /**
     * Old notification.
     */
    public function old(): static
    {
        return $this->state(fn (array $attributes) => [
            'created_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
            'read_at' => fake()->dateTimeBetween('-60 days', '-30 days'),
        ]);
    }
}
