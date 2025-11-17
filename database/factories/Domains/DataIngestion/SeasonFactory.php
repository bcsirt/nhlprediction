<?php

declare(strict_types=1);

namespace Database\Factories\Domains\DataIngestion;

use App\Domains\DataIngestion\Models\Season;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Season>
 */
class SeasonFactory extends Factory
{
    protected $model = Season::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startYear = $this->faker->numberBetween(2020, 2024);
        $endYear = $startYear + 1;

        return [
            'season_id' => $startYear . $endYear,
            'start_date' => now()->setYear($startYear)->setMonth(10)->setDay(1),
            'end_date' => now()->setYear($endYear)->setMonth(4)->setDay(30),
            'regular_season_end_date' => now()->setYear($endYear)->setMonth(4)->setDay(15),
            'is_active' => false,
        ];
    }

    /**
     * Indicate that this is the current/active season.
     */
    public function active(): static
    {
        return $this->state(fn(array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Create a specific season by year.
     */
    public function year(int $startYear): static
    {
        $endYear = $startYear + 1;

        return $this->state(fn(array $attributes) => [
            'season_id' => $startYear . $endYear,
            'start_date' => now()->setYear($startYear)->setMonth(10)->setDay(1),
            'end_date' => now()->setYear($endYear)->setMonth(4)->setDay(30),
            'regular_season_end_date' => now()->setYear($endYear)->setMonth(4)->setDay(15),
        ]);
    }
}
