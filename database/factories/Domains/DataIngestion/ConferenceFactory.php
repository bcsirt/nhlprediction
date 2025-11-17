<?php

declare(strict_types=1);

namespace Database\Factories\Domains\DataIngestion;

use App\Domains\DataIngestion\Models\Conference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conference>
 */
class ConferenceFactory extends Factory
{
    protected $model = Conference::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nhl_id' => $this->faker->unique()->numberBetween(1, 100),
            'name' => $this->faker->randomElement(['Eastern', 'Western']),
            'abbreviation' => fn(array $attributes) => substr($attributes['name'], 0, 1),
        ];
    }

    /**
     * Indicate that this is the Eastern Conference.
     */
    public function eastern(): static
    {
        return $this->state(fn(array $attributes) => [
            'nhl_id' => 6,
            'name' => 'Eastern',
            'abbreviation' => 'E',
        ]);
    }

    /**
     * Indicate that this is the Western Conference.
     */
    public function western(): static
    {
        return $this->state(fn(array $attributes) => [
            'nhl_id' => 5,
            'name' => 'Western',
            'abbreviation' => 'W',
        ]);
    }
}
