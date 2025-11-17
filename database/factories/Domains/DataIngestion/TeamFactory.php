<?php

declare(strict_types=1);

namespace Database\Factories\Domains\DataIngestion;

use App\Domains\DataIngestion\Models\Conference;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    protected $model = Team::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $city = $this->faker->city();
        $teamName = $this->faker->randomElement([
            'Bears', 'Wolves', 'Eagles', 'Tigers', 'Lions',
            'Sharks', 'Dragons', 'Knights', 'Warriors', 'Rangers'
        ]);

        return [
            'nhl_id' => $this->faker->unique()->numberBetween(1, 100),
            'name' => $city . ' ' . $teamName,
            'abbreviation' => strtoupper(substr($city, 0, 3)),
            'city' => $city,
            'conference_id' => Conference::factory(),
            'division' => $this->faker->randomElement(['Atlantic', 'Metropolitan', 'Central', 'Pacific']),
            'venue_name' => $city . ' Arena',
            'is_active' => true,
        ];
    }

    /**
     * Indicate that this team is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create a team in a specific conference.
     */
    public function inConference(Conference $conference): static
    {
        return $this->state(fn(array $attributes) => [
            'conference_id' => $conference->id,
        ]);
    }

    /**
     * Create the Montreal Canadiens.
     */
    public function canadiens(): static
    {
        return $this->state(fn(array $attributes) => [
            'nhl_id' => 8,
            'name' => 'Montréal Canadiens',
            'abbreviation' => 'MTL',
            'city' => 'Montréal',
            'division' => 'Atlantic',
            'venue_name' => 'Centre Bell',
            'is_active' => true,
        ]);
    }

    /**
     * Create the Toronto Maple Leafs.
     */
    public function mapleLeafs(): static
    {
        return $this->state(fn(array $attributes) => [
            'nhl_id' => 10,
            'name' => 'Toronto Maple Leafs',
            'abbreviation' => 'TOR',
            'city' => 'Toronto',
            'division' => 'Atlantic',
            'venue_name' => 'Scotiabank Arena',
            'is_active' => true,
        ]);
    }
}
