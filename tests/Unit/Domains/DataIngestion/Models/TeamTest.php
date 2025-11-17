<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Models;

use App\Domains\DataIngestion\Models\Conference;
use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Player;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour le modèle Team
 */
class TeamTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_team(): void
    {
        $conference = Conference::factory()->create();
        $team = Team::factory()->create(['conference_id' => $conference->id]);

        $this->assertInstanceOf(Team::class, $team);
        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'conference_id' => $conference->id,
        ]);
    }

    /**
     * @test
     */
    public function it_belongs_to_conference(): void
    {
        $team = Team::factory()->create();

        $this->assertInstanceOf(Conference::class, $team->conference);
    }

    /**
     * @test
     */
    public function it_has_many_players(): void
    {
        $team = Team::factory()->create();
        Player::factory()->count(3)->create(['team_id' => $team->id]);

        $this->assertCount(3, $team->players);
        $this->assertInstanceOf(Player::class, $team->players->first());
    }

    /**
     * @test
     */
    public function it_has_many_home_games(): void
    {
        $team = Team::factory()->create();
        Game::factory()->count(2)->create(['home_team_id' => $team->id]);

        $this->assertCount(2, $team->homeGames);
        $this->assertInstanceOf(Game::class, $team->homeGames->first());
    }

    /**
     * @test
     */
    public function it_has_many_away_games(): void
    {
        $team = Team::factory()->create();
        Game::factory()->count(3)->create(['away_team_id' => $team->id]);

        $this->assertCount(3, $team->awayGames);
        $this->assertInstanceOf(Game::class, $team->awayGames->first());
    }

    /**
     * @test
     */
    public function it_can_get_all_games(): void
    {
        $team = Team::factory()->create();

        // Create home and away games
        Game::factory()->count(2)->create(['home_team_id' => $team->id]);
        Game::factory()->count(3)->create(['away_team_id' => $team->id]);

        // Create games for other teams
        Game::factory()->create();

        $allGames = $team->getAllGames();

        $this->assertCount(5, $allGames);
    }

    /**
     * @test
     */
    public function it_can_scope_to_active_teams(): void
    {
        Team::factory()->count(3)->create(['is_active' => true]);
        Team::factory()->count(2)->inactive()->create();

        $activeTeams = Team::active()->get();

        $this->assertCount(3, $activeTeams);
        $this->assertTrue($activeTeams->every(fn($team) => $team->is_active));
    }

    /**
     * @test
     */
    public function it_can_scope_by_conference(): void
    {
        $eastern = Conference::factory()->eastern()->create();
        $western = Conference::factory()->western()->create();

        Team::factory()->count(3)->create(['conference_id' => $eastern->id]);
        Team::factory()->count(2)->create(['conference_id' => $western->id]);

        $easternTeams = Team::inConference($eastern->id)->get();

        $this->assertCount(3, $easternTeams);
    }

    /**
     * @test
     */
    public function it_can_scope_by_division(): void
    {
        Team::factory()->count(4)->create(['division' => 'Atlantic']);
        Team::factory()->count(2)->create(['division' => 'Pacific']);

        $atlanticTeams = Team::inDivision('Atlantic')->get();

        $this->assertCount(4, $atlanticTeams);
    }

    /**
     * @test
     */
    public function it_can_find_team_by_nhl_id(): void
    {
        $team = Team::factory()->create(['nhl_id' => 8]);

        $foundTeam = Team::findByNHLId(8);

        $this->assertNotNull($foundTeam);
        $this->assertEquals($team->id, $foundTeam->id);
    }

    /**
     * @test
     */
    public function find_by_nhl_id_returns_null_for_non_existent_team(): void
    {
        $foundTeam = Team::findByNHLId(9999);

        $this->assertNull($foundTeam);
    }

    /**
     * @test
     */
    public function it_can_find_team_by_abbreviation(): void
    {
        $team = Team::factory()->create(['abbreviation' => 'MTL']);

        $foundTeam = Team::findByAbbreviation('MTL');

        $this->assertNotNull($foundTeam);
        $this->assertEquals($team->id, $foundTeam->id);
    }

    /**
     * @test
     */
    public function find_by_abbreviation_is_case_insensitive(): void
    {
        $team = Team::factory()->create(['abbreviation' => 'MTL']);

        $foundTeam = Team::findByAbbreviation('mtl');

        $this->assertNotNull($foundTeam);
        $this->assertEquals($team->id, $foundTeam->id);
    }

    /**
     * @test
     */
    public function it_can_get_full_name(): void
    {
        $team = Team::factory()->create([
            'city' => 'Montréal',
            'name' => 'Montréal Canadiens',
        ]);

        $fullName = $team->getFullName();

        $this->assertEquals('Montréal Canadiens', $fullName);
    }

    /**
     * @test
     */
    public function it_can_get_record_from_stats(): void
    {
        $team = Team::factory()->create([
            'wins' => 30,
            'losses' => 20,
            'overtime_losses' => 10,
        ]);

        $record = $team->getRecord();

        $this->assertEquals('30-20-10', $record);
    }

    /**
     * @test
     */
    public function it_can_calculate_points(): void
    {
        $team = Team::factory()->create([
            'wins' => 30,      // 30 * 2 = 60
            'overtime_losses' => 10, // 10 * 1 = 10
        ]);

        $points = $team->getPoints();

        $this->assertEquals(70, $points);
    }

    /**
     * @test
     */
    public function it_can_calculate_points_percentage(): void
    {
        $team = Team::factory()->create([
            'games_played' => 50,
            'wins' => 30,           // 60 points
            'overtime_losses' => 10, // 10 points
            // Total: 70 points out of possible 100 = 70%
        ]);

        $pointsPercentage = $team->getPointsPercentage();

        $this->assertEquals(70.0, $pointsPercentage);
    }

    /**
     * @test
     */
    public function points_percentage_returns_zero_for_no_games_played(): void
    {
        $team = Team::factory()->create(['games_played' => 0]);

        $pointsPercentage = $team->getPointsPercentage();

        $this->assertEquals(0.0, $pointsPercentage);
    }

    /**
     * @test
     */
    public function it_can_determine_if_team_is_winning(): void
    {
        $winningTeam = Team::factory()->create([
            'games_played' => 50,
            'wins' => 35,
            'losses' => 15,
        ]);

        $losingTeam = Team::factory()->create([
            'games_played' => 50,
            'wins' => 15,
            'losses' => 35,
        ]);

        $this->assertTrue($winningTeam->getPointsPercentage() > 50);
        $this->assertFalse($losingTeam->getPointsPercentage() > 50);
    }

    /**
     * @test
     */
    public function it_can_get_upcoming_games(): void
    {
        $team = Team::factory()->create();

        // Past games
        Game::factory()->create([
            'home_team_id' => $team->id,
            'game_date' => now()->subDays(1),
        ]);

        // Future games
        Game::factory()->count(3)->create([
            'home_team_id' => $team->id,
            'game_date' => now()->addDays(1),
            'status' => 'scheduled',
        ]);

        $upcomingGames = $team->getUpcomingGames();

        $this->assertCount(3, $upcomingGames);
    }

    /**
     * @test
     */
    public function it_can_get_recent_games(): void
    {
        $team = Team::factory()->create();

        // Recent finished games
        Game::factory()->count(5)->finished()->create([
            'away_team_id' => $team->id,
            'game_date' => now()->subDays(rand(1, 10)),
        ]);

        // Future games
        Game::factory()->create([
            'home_team_id' => $team->id,
            'game_date' => now()->addDays(1),
        ]);

        $recentGames = $team->getRecentGames(5);

        $this->assertCount(5, $recentGames);
        $this->assertTrue($recentGames->every(fn($game) => $game->isFinished()));
    }

    /**
     * @test
     */
    public function canadian_teams_are_in_correct_divisions(): void
    {
        $canadiens = Team::factory()->canadiens()->create();
        $mapleLeafs = Team::factory()->mapleLeafs()->create();

        $this->assertEquals('Atlantic', $canadiens->division);
        $this->assertEquals('Atlantic', $mapleLeafs->division);
    }

    /**
     * @test
     */
    public function it_maintains_unique_nhl_id_constraint(): void
    {
        Team::factory()->create(['nhl_id' => 8]);

        $this->expectException(\Exception::class);

        Team::factory()->create(['nhl_id' => 8]);
    }
}
