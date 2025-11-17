<?php

declare(strict_types=1);

namespace Database\Factories\Domains\DataIngestion;

use App\Domains\DataIngestion\Enums\PlayerPosition;
use App\Domains\DataIngestion\Models\Player;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Player>
 */
class PlayerFactory extends Factory
{
    protected $model = Player::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nhl_id' => $this->faker->unique()->numberBetween(1000000, 9999999),
            'team_id' => Team::factory(),
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'jersey_number' => $this->faker->numberBetween(1, 99),
            'position' => $this->faker->randomElement(['C', 'LW', 'RW', 'D', 'G']),
            'shoots_catches' => $this->faker->randomElement(['L', 'R']),
            'height_cm' => $this->faker->numberBetween(170, 200),
            'weight_kg' => $this->faker->numberBetween(70, 110),
            'birth_date' => $this->faker->dateTimeBetween('-35 years', '-18 years'),
            'birth_city' => $this->faker->city(),
            'birth_country' => $this->faker->randomElement(['CAN', 'USA', 'SWE', 'FIN', 'RUS', 'CZE']),
            'nationality' => fn(array $attributes) => $attributes['birth_country'],
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this player is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a player with a specific position.
     */
    public function position(PlayerPosition $position): static
    {
        return $this->state(fn(array $attributes) => [
            'position' => $position->value,
        ]);
    }

    /**
     * Create a center.
     */
    public function center(): static
    {
        return $this->position(PlayerPosition::CENTER);
    }

    /**
     * Create a left wing.
     */
    public function leftWing(): static
    {
        return $this->position(PlayerPosition::LEFT_WING);
    }

    /**
     * Create a right wing.
     */
    public function rightWing(): static
    {
        return $this->position(PlayerPosition::RIGHT_WING);
    }

    /**
     * Create a defenseman.
     */
    public function defense(): static
    {
        return $this->position(PlayerPosition::DEFENSE);
    }

    /**
     * Create a goalie.
     */
    public function goalie(): static
    {
        return $this->state(fn(array $attributes) => [
            'position' => PlayerPosition::GOALIE->value,
            'jersey_number' => $this->faker->numberBetween(30, 40),
        ]);
    }

    /**
     * Create a Canadian player.
     */
    public function canadian(): static
    {
        return $this->state(fn(array $attributes) => [
            'birth_country' => 'CAN',
            'nationality' => 'CAN',
            'birth_city' => $this->faker->randomElement([
                'Toronto', 'Montreal', 'Vancouver', 'Calgary', 'Edmonton', 'Ottawa'
            ]),
        ]);
    }

    /**
     * Create a player on a specific team.
     */
    public function forTeam(Team $team): static
    {
        return $this->state(fn(array $attributes) => [
            'team_id' => $team->id,
        ]);
    }
}
