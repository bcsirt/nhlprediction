<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Models;

use App\Domains\DataIngestion\Enums\GameStatus;
use App\Domains\DataIngestion\Enums\GameType;
use App\Domains\DataIngestion\Models\Conference;
use App\Domains\DataIngestion\Models\Game;
use App\Domains\DataIngestion\Models\Season;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour le modèle Game
 */
class GameTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_game(): void
    {
        $conference = Conference::factory()->create();
        $season = Season::factory()->create();
        $homeTeam = Team::factory()->inConference($conference)->create();
        $awayTeam = Team::factory()->inConference($conference)->create();

        $game = Game::factory()->create([
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $this->assertInstanceOf(Game::class, $game);
        $this->assertDatabaseHas('games', [
            'id' => $game->id,
            'season_id' => $season->id,
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);
    }

    /**
     * @test
     */
    public function it_belongs_to_season(): void
    {
        $game = Game::factory()->create();

        $this->assertInstanceOf(Season::class, $game->season);
    }

    /**
     * @test
     */
    public function it_belongs_to_home_team(): void
    {
        $game = Game::factory()->create();

        $this->assertInstanceOf(Team::class, $game->homeTeam);
    }

    /**
     * @test
     */
    public function it_belongs_to_away_team(): void
    {
        $game = Game::factory()->create();

        $this->assertInstanceOf(Team::class, $game->awayTeam);
    }

    /**
     * @test
     */
    public function it_can_determine_if_finished(): void
    {
        $scheduledGame = Game::factory()->scheduled()->create();
        $this->assertFalse($scheduledGame->isFinished());

        $liveGame = Game::factory()->live()->create();
        $this->assertFalse($liveGame->isFinished());

        $finishedGame = Game::factory()->finished()->create();
        $this->assertTrue($finishedGame->isFinished());
    }

    /**
     * @test
     */
    public function it_can_determine_if_live(): void
    {
        $scheduledGame = Game::factory()->scheduled()->create();
        $this->assertFalse($scheduledGame->isLive());

        $liveGame = Game::factory()->live()->create();
        $this->assertTrue($liveGame->isLive());

        $finishedGame = Game::factory()->finished()->create();
        $this->assertFalse($finishedGame->isLive());
    }

    /**
     * @test
     */
    public function it_can_get_status_enum(): void
    {
        $game = Game::factory()->create(['status' => 'scheduled']);

        $status = $game->getStatusEnumAttribute();

        $this->assertInstanceOf(GameStatus::class, $status);
        $this->assertEquals(GameStatus::SCHEDULED, $status);
    }

    /**
     * @test
     */
    public function it_can_get_game_type_enum(): void
    {
        $game = Game::factory()->create(['game_type' => 'R']);

        $gameType = $game->getGameTypeEnumAttribute();

        $this->assertInstanceOf(GameType::class, $gameType);
        $this->assertEquals(GameType::REGULAR, $gameType);
    }

    /**
     * @test
     */
    public function it_can_get_winner(): void
    {
        $homeTeam = Team::factory()->create(['name' => 'Home Team']);
        $awayTeam = Team::factory()->create(['name' => 'Away Team']);

        $game = Game::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
            'home_score' => 4,
            'away_score' => 2,
            'status' => 'final',
        ]);

        $winner = $game->getWinner();

        $this->assertInstanceOf(Team::class, $winner);
        $this->assertEquals($homeTeam->id, $winner->id);
    }

    /**
     * @test
     */
    public function it_returns_null_winner_for_tied_or_unfinished_games(): void
    {
        $scheduledGame = Game::factory()->scheduled()->create();
        $this->assertNull($scheduledGame->getWinner());

        $tiedGame = Game::factory()->create([
            'home_score' => 3,
            'away_score' => 3,
            'status' => 'live',
        ]);
        $this->assertNull($tiedGame->getWinner());
    }

    /**
     * @test
     */
    public function it_can_get_total_score(): void
    {
        $game = Game::factory()->create([
            'home_score' => 5,
            'away_score' => 3,
        ]);

        $this->assertEquals(8, $game->getTotalScore());
    }

    /**
     * @test
     */
    public function total_score_returns_zero_for_scheduled_games(): void
    {
        $game = Game::factory()->scheduled()->create();

        $this->assertEquals(0, $game->getTotalScore());
    }

    /**
     * @test
     */
    public function it_can_scope_to_finished_games(): void
    {
        Game::factory()->scheduled()->create();
        Game::factory()->live()->create();
        Game::factory()->finished()->count(3)->create();

        $finishedGames = Game::finished()->get();

        $this->assertCount(3, $finishedGames);
    }

    /**
     * @test
     */
    public function it_can_scope_to_live_games(): void
    {
        Game::factory()->scheduled()->create();
        Game::factory()->live()->count(2)->create();
        Game::factory()->finished()->create();

        $liveGames = Game::live()->get();

        $this->assertCount(2, $liveGames);
    }

    /**
     * @test
     */
    public function it_can_scope_to_scheduled_games(): void
    {
        Game::factory()->scheduled()->count(4)->create();
        Game::factory()->live()->create();
        Game::factory()->finished()->create();

        $scheduledGames = Game::scheduled()->get();

        $this->assertCount(4, $scheduledGames);
    }

    /**
     * @test
     */
    public function it_can_scope_to_todays_games(): void
    {
        Game::factory()->today()->count(3)->create();
        Game::factory()->create(['game_date' => now()->subDays(1)]);
        Game::factory()->create(['game_date' => now()->addDays(1)]);

        $todayGames = Game::today()->get();

        $this->assertCount(3, $todayGames);
    }

    /**
     * @test
     */
    public function it_can_scope_for_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        // Games involving our team
        Game::factory()->create(['home_team_id' => $team->id]);
        Game::factory()->create(['away_team_id' => $team->id]);

        // Games not involving our team
        Game::factory()->create([
            'home_team_id' => $otherTeam->id,
            'away_team_id' => Team::factory(),
        ]);

        $teamGames = Game::forTeam($team->id)->get();

        $this->assertCount(2, $teamGames);
    }

    /**
     * @test
     */
    public function it_can_scope_by_season(): void
    {
        $season2023 = Season::factory()->year(2023)->create();
        $season2024 = Season::factory()->year(2024)->create();

        Game::factory()->count(3)->create(['season_id' => $season2023->id]);
        Game::factory()->count(2)->create(['season_id' => $season2024->id]);

        $season2023Games = Game::forSeason($season2023->id)->get();

        $this->assertCount(3, $season2023Games);
    }

    /**
     * @test
     */
    public function it_can_find_game_by_nhl_id(): void
    {
        $game = Game::factory()->create(['nhl_id' => 2023020999]);

        $foundGame = Game::findByNHLId(2023020999);

        $this->assertNotNull($foundGame);
        $this->assertEquals($game->id, $foundGame->id);
    }

    /**
     * @test
     */
    public function find_by_nhl_id_returns_null_for_non_existent_game(): void
    {
        $foundGame = Game::findByNHLId(9999999999);

        $this->assertNull($foundGame);
    }

    /**
     * @test
     */
    public function it_detects_overtime_games(): void
    {
        $regularGame = Game::factory()->finished()->create(['overtime' => false]);
        $overtimeGame = Game::factory()->overtime()->create();

        $this->assertFalse($regularGame->overtime);
        $this->assertTrue($overtimeGame->overtime);
    }

    /**
     * @test
     */
    public function it_detects_shootout_games(): void
    {
        $regularGame = Game::factory()->finished()->create(['shootout' => false]);
        $shootoutGame = Game::factory()->shootout()->create();

        $this->assertFalse($regularGame->shootout);
        $this->assertTrue($shootoutGame->shootout);
    }

    /**
     * @test
     */
    public function it_can_get_game_label(): void
    {
        $homeTeam = Team::factory()->create(['abbreviation' => 'MTL']);
        $awayTeam = Team::factory()->create(['abbreviation' => 'TOR']);

        $game = Game::factory()->create([
            'home_team_id' => $homeTeam->id,
            'away_team_id' => $awayTeam->id,
        ]);

        $label = $game->getLabel();

        $this->assertStringContainsString('TOR', $label);
        $this->assertStringContainsString('MTL', $label);
        $this->assertStringContainsString('@', $label);
    }

    /**
     * @test
     */
    public function playoff_games_are_identified_correctly(): void
    {
        $regularGame = Game::factory()->create(['game_type' => 'R']);
        $playoffGame = Game::factory()->playoffs()->create();

        $this->assertFalse($regularGame->getGameTypeEnumAttribute()->isPlayoffs());
        $this->assertTrue($playoffGame->getGameTypeEnumAttribute()->isPlayoffs());
    }
}
