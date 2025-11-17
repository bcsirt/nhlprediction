<?php

declare(strict_types=1);

namespace Database\Factories\Domains\DataIngestion;

use App\Domains\DataIngestion\Enums\GameStatus;
use App\Domains\DataIngestion\Enums\GameType;
use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Season;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Game>
 */
class GameFactory extends Factory
{
    protected $model = Game::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nhl_id' => $this->faker->unique()->numberBetween(2000000000, 2099999999),
            'season_id' => Season::factory(),
            'game_type' => GameType::REGULAR->value,
            'game_date' => $this->faker->dateTimeBetween('-30 days', '+30 days'),
            'home_team_id' => Team::factory(),
            'away_team_id' => Team::factory(),
            'home_score' => null,
            'away_score' => null,
            'status' => GameStatus::SCHEDULED->value,
            'overtime' => false,
            'shootout' => false,
            'venue' => fn(array $attributes) => Team::find($attributes['home_team_id'])?->venue_name ?? 'Arena',
        ];
    }

    /**
     * Indicate that this game is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => GameStatus::SCHEDULED->value,
            'home_score' => null,
            'away_score' => null,
        ]);
    }

    /**
     * Indicate that this game is live.
     */
    public function live(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => GameStatus::LIVE->value,
            'home_score' => $this->faker->numberBetween(0, 5),
            'away_score' => $this->faker->numberBetween(0, 5),
        ]);
    }

    /**
     * Indicate that this game is finished.
     */
    public function finished(): static
    {
        $homeScore = $this->faker->numberBetween(0, 7);
        $awayScore = $this->faker->numberBetween(0, 7);

        // Ensure there's a winner
        if ($homeScore === $awayScore) {
            $homeScore++;
        }

        return $this->state(fn(array $attributes) => [
            'status' => GameStatus::FINAL->value,
            'home_score' => $homeScore,
            'away_score' => $awayScore,
        ]);
    }

    /**
     * Indicate that this game went to overtime.
     */
    public function overtime(): static
    {
        $homeScore = $this->faker->numberBetween(2, 5);
        $awayScore = $homeScore; // Tied after regulation
        $winner = $this->faker->boolean();

        return $this->state(fn(array $attributes) => [
            'status' => GameStatus::FINAL_OT->value,
            'home_score' => $winner ? $homeScore + 1 : $homeScore,
            'away_score' => $winner ? $awayScore : $awayScore + 1,
            'overtime' => true,
            'shootout' => false,
        ]);
    }

    /**
     * Indicate that this game went to a shootout.
     */
    public function shootout(): static
    {
        $score = $this->faker->numberBetween(2, 4);
        $winner = $this->faker->boolean();

        return $this->state(fn(array $attributes) => [
            'status' => GameStatus::FINAL_SO->value,
            'home_score' => $winner ? $score + 1 : $score,
            'away_score' => $winner ? $score : $score + 1,
            'overtime' => true,
            'shootout' => true,
        ]);
    }

    /**
     * Create a playoff game.
     */
    public function playoffs(): static
    {
        return $this->state(fn(array $attributes) => [
            'game_type' => GameType::PLAYOFFS->value,
        ]);
    }

    /**
     * Create a preseason game.
     */
    public function preseason(): static
    {
        return $this->state(fn(array $attributes) => [
            'game_type' => GameType::PRESEASON->value,
        ]);
    }

    /**
     * Create a game between specific teams.
     */
    public function between(Team $homeTeam, Team $awayTeam): static
    {
        return $this->state(fn(array $attributes) => [
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'venue' => $homeTeam->venue_name,
        ]);
    }

    /**
     * Create a game today.
     */
    public function today(): static
    {
        return $this->state(fn(array $attributes) => [
            'game_date' => now(),
        ]);
    }
}
