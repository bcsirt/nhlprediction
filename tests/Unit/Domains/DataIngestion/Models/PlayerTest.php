<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\DataIngestion\Models;

use App\Domains\DataIngestion\Enums\PlayerPosition;
use App\Domains\DataIngestion\Models\Player;
use App\Domains\DataIngestion\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour le modèle Player
 */
class PlayerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_create_a_player(): void
    {
        $team = Team::factory()->create();
        $player = Player::factory()->create(['team_id' => $team->id]);

        $this->assertInstanceOf(Player::class, $player);
        $this->assertDatabaseHas('players', [
            'id' => $player->id,
            'team_id' => $team->id,
        ]);
    }

    /**
     * @test
     */
    public function it_belongs_to_team(): void
    {
        $player = Player::factory()->create();

        $this->assertInstanceOf(Team::class, $player->team);
    }

    /**
     * @test
     */
    public function it_can_get_full_name(): void
    {
        $player = Player::factory()->create([
            'first_name' => 'Connor',
            'last_name' => 'McDavid',
        ]);

        $this->assertEquals('Connor McDavid', $player->getFullName());
    }

    /**
     * @test
     */
    public function it_can_get_position_enum(): void
    {
        $player = Player::factory()->center()->create();

        $position = $player->getPositionEnumAttribute();

        $this->assertInstanceOf(PlayerPosition::class, $position);
        $this->assertEquals(PlayerPosition::CENTER, $position);
    }

    /**
     * @test
     */
    public function it_can_calculate_age(): void
    {
        $player = Player::factory()->create([
            'birth_date' => now()->subYears(25)->format('Y-m-d'),
        ]);

        $age = $player->getAge();

        $this->assertEquals(25, $age);
    }

    /**
     * @test
     */
    public function it_can_determine_if_player_is_forward(): void
    {
        $center = Player::factory()->center()->create();
        $leftWing = Player::factory()->leftWing()->create();
        $defenseman = Player::factory()->defense()->create();
        $goalie = Player::factory()->goalie()->create();

        $this->assertTrue($center->isForward());
        $this->assertTrue($leftWing->isForward());
        $this->assertFalse($defenseman->isForward());
        $this->assertFalse($goalie->isForward());
    }

    /**
     * @test
     */
    public function it_can_determine_if_player_is_defenseman(): void
    {
        $center = Player::factory()->center()->create();
        $defenseman = Player::factory()->defense()->create();

        $this->assertFalse($center->isDefense());
        $this->assertTrue($defenseman->isDefense());
    }

    /**
     * @test
     */
    public function it_can_determine_if_player_is_goalie(): void
    {
        $center = Player::factory()->center()->create();
        $goalie = Player::factory()->goalie()->create();

        $this->assertFalse($center->isGoalie());
        $this->assertTrue($goalie->isGoalie());
    }

    /**
     * @test
     */
    public function it_can_scope_to_active_players(): void
    {
        Player::factory()->count(5)->create(['is_active' => true]);
        Player::factory()->count(2)->inactive()->create();

        $activePlayers = Player::active()->get();

        $this->assertCount(5, $activePlayers);
    }

    /**
     * @test
     */
    public function it_can_scope_by_team(): void
    {
        $team = Team::factory()->create();
        $otherTeam = Team::factory()->create();

        Player::factory()->count(3)->create(['team_id' => $team->id]);
        Player::factory()->count(2)->create(['team_id' => $otherTeam->id]);

        $teamPlayers = Player::forTeam($team->id)->get();

        $this->assertCount(3, $teamPlayers);
    }

    /**
     * @test
     */
    public function it_can_scope_by_position(): void
    {
        Player::factory()->center()->count(3)->create();
        Player::factory()->defense()->count(2)->create();
        Player::factory()->goalie()->create();

        $centers = Player::byPosition('C')->get();
        $defensemen = Player::byPosition('D')->get();

        $this->assertCount(3, $centers);
        $this->assertCount(2, $defensemen);
    }

    /**
     * @test
     */
    public function it_can_scope_to_forwards(): void
    {
        Player::factory()->center()->count(2)->create();
        Player::factory()->leftWing()->count(2)->create();
        Player::factory()->rightWing()->count(2)->create();
        Player::factory()->defense()->count(2)->create();
        Player::factory()->goalie()->create();

        $forwards = Player::forwards()->get();

        $this->assertCount(6, $forwards);
    }

    /**
     * @test
     */
    public function it_can_scope_to_defensemen(): void
    {
        Player::factory()->center()->count(3)->create();
        Player::factory()->defense()->count(4)->create();
        Player::factory()->goalie()->create();

        $defensemen = Player::defensemen()->get();

        $this->assertCount(4, $defensemen);
    }

    /**
     * @test
     */
    public function it_can_scope_to_goalies(): void
    {
        Player::factory()->center()->count(5)->create();
        Player::factory()->goalie()->count(2)->create();

        $goalies = Player::goalies()->get();

        $this->assertCount(2, $goalies);
    }

    /**
     * @test
     */
    public function it_can_find_player_by_nhl_id(): void
    {
        $player = Player::factory()->create(['nhl_id' => 8478402]);

        $foundPlayer = Player::findByNHLId(8478402);

        $this->assertNotNull($foundPlayer);
        $this->assertEquals($player->id, $foundPlayer->id);
    }

    /**
     * @test
     */
    public function find_by_nhl_id_returns_null_for_non_existent_player(): void
    {
        $foundPlayer = Player::findByNHLId(9999999);

        $this->assertNull($foundPlayer);
    }

    /**
     * @test
     */
    public function it_can_get_height_in_feet_and_inches(): void
    {
        // 183 cm = 6'0"
        $player = Player::factory()->create(['height_cm' => 183]);

        $height = $player->getHeightInFeetInches();

        $this->assertStringContainsString('6', $height);
        $this->assertStringContainsString('0', $height);
    }

    /**
     * @test
     */
    public function it_can_get_weight_in_pounds(): void
    {
        // 90 kg ≈ 198 lbs
        $player = Player::factory()->create(['weight_kg' => 90]);

        $weight = $player->getWeightInPounds();

        $this->assertEqualsWithDelta(198, $weight, 1);
    }

    /**
     * @test
     */
    public function it_can_scope_by_nationality(): void
    {
        Player::factory()->count(3)->create(['nationality' => 'CAN']);
        Player::factory()->count(2)->create(['nationality' => 'USA']);
        Player::factory()->count(1)->create(['nationality' => 'SWE']);

        $canadianPlayers = Player::byNationality('CAN')->get();

        $this->assertCount(3, $canadianPlayers);
    }

    /**
     * @test
     */
    public function canadian_players_have_correct_attributes(): void
    {
        $player = Player::factory()->canadian()->create();

        $this->assertEquals('CAN', $player->nationality);
        $this->assertEquals('CAN', $player->birth_country);
        $this->assertContains($player->birth_city, [
            'Toronto', 'Montreal', 'Vancouver', 'Calgary', 'Edmonton', 'Ottawa'
        ]);
    }

    /**
     * @test
     */
    public function goalies_typically_have_higher_jersey_numbers(): void
    {
        $goalie = Player::factory()->goalie()->create();

        $this->assertGreaterThanOrEqual(30, $goalie->jersey_number);
        $this->assertLessThanOrEqual(40, $goalie->jersey_number);
    }

    /**
     * @test
     */
    public function it_can_determine_handedness(): void
    {
        $lefty = Player::factory()->create(['shoots_catches' => 'L']);
        $righty = Player::factory()->create(['shoots_catches' => 'R']);

        $this->assertEquals('L', $lefty->shoots_catches);
        $this->assertEquals('R', $righty->shoots_catches);
    }

    /**
     * @test
     */
    public function it_maintains_unique_nhl_id_constraint(): void
    {
        Player::factory()->create(['nhl_id' => 8478402]);

        $this->expectException(\Exception::class);

        Player::factory()->create(['nhl_id' => 8478402]);
    }

    /**
     * @test
     */
    public function it_can_get_display_name_with_number(): void
    {
        $player = Player::factory()->create([
            'first_name' => 'Connor',
            'last_name' => 'McDavid',
            'jersey_number' => 97,
        ]);

        $displayName = $player->getDisplayName();

        $this->assertStringContainsString('97', $displayName);
        $this->assertStringContainsString('McDavid', $displayName);
    }

    /**
     * @test
     */
    public function players_have_realistic_physical_attributes(): void
    {
        $player = Player::factory()->create();

        // Height between 170-200 cm (5'7" - 6'7")
        $this->assertGreaterThanOrEqual(170, $player->height_cm);
        $this->assertLessThanOrEqual(200, $player->height_cm);

        // Weight between 70-110 kg (154-242 lbs)
        $this->assertGreaterThanOrEqual(70, $player->weight_kg);
        $this->assertLessThanOrEqual(110, $player->weight_kg);
    }

    /**
     * @test
     */
    public function players_are_between_18_and_45_years_old(): void
    {
        $player = Player::factory()->create();

        $age = $player->getAge();

        $this->assertGreaterThanOrEqual(18, $age);
        $this->assertLessThanOrEqual(45, $age);
    }
}
