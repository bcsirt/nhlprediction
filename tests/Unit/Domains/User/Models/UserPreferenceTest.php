<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Enums\NotificationChannel;
use App\Domains\User\Enums\NotificationType;
use App\Domains\User\Models\UserPreference;
use App\Domains\ValueBets\Enums\OddsFormat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $preference = UserPreference::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $preference->user);
        $this->assertEquals($user->id, $preference->user->id);
    }

    public function test_is_notification_enabled(): void
    {
        $preference = UserPreference::factory()->create([
            'notify_predictions' => true,
            'notify_value_bets' => false,
        ]);

        $this->assertTrue($preference->isNotificationEnabled(NotificationType::PREDICTION));
        $this->assertFalse($preference->isNotificationEnabled(NotificationType::VALUE_BET));
    }

    public function test_is_channel_enabled(): void
    {
        $preference = UserPreference::factory()->create([
            'email_enabled' => true,
            'push_enabled' => false,
            'sms_enabled' => false,
        ]);

        $this->assertTrue($preference->isChannelEnabled(NotificationChannel::EMAIL));
        $this->assertFalse($preference->isChannelEnabled(NotificationChannel::PUSH));
        $this->assertFalse($preference->isChannelEnabled(NotificationChannel::SMS));
    }

    public function test_get_active_channels(): void
    {
        $preference = UserPreference::factory()->create([
            'email_enabled' => true,
            'push_enabled' => true,
            'sms_enabled' => false,
        ]);

        $channels = $preference->getActiveChannels();

        $this->assertCount(2, $channels);
        $this->assertContains(NotificationChannel::EMAIL, $channels);
        $this->assertContains(NotificationChannel::PUSH, $channels);
        $this->assertNotContains(NotificationChannel::SMS, $channels);
    }

    public function test_is_favorite_team(): void
    {
        $preference = UserPreference::factory()->create([
            'favorite_teams' => [1, 5, 10],
        ]);

        $this->assertTrue($preference->isFavoriteTeam(1));
        $this->assertTrue($preference->isFavoriteTeam(5));
        $this->assertFalse($preference->isFavoriteTeam(2));
    }

    public function test_is_excluded_team(): void
    {
        $preference = UserPreference::factory()->create([
            'excluded_teams' => [3, 7],
        ]);

        $this->assertTrue($preference->isExcludedTeam(3));
        $this->assertFalse($preference->isExcludedTeam(1));
    }

    public function test_add_favorite_team(): void
    {
        $preference = UserPreference::factory()->create([
            'favorite_teams' => [1],
        ]);

        $preference->addFavoriteTeam(5);

        $this->assertTrue($preference->isFavoriteTeam(5));
        $this->assertCount(2, $preference->favorite_teams);
    }

    public function test_add_favorite_team_does_not_duplicate(): void
    {
        $preference = UserPreference::factory()->create([
            'favorite_teams' => [1, 5],
        ]);

        $preference->addFavoriteTeam(5);

        $this->assertCount(2, $preference->favorite_teams);
    }

    public function test_remove_favorite_team(): void
    {
        $preference = UserPreference::factory()->create([
            'favorite_teams' => [1, 5, 10],
        ]);

        $preference->removeFavoriteTeam(5);

        $this->assertFalse($preference->isFavoriteTeam(5));
        $this->assertCount(2, $preference->favorite_teams);
    }

    public function test_get_odds_format(): void
    {
        $preference = UserPreference::factory()->create([
            'preferred_odds_format' => 'american',
        ]);

        $this->assertEquals(OddsFormat::AMERICAN, $preference->getOddsFormat());
    }

    public function test_get_odds_format_defaults_to_decimal(): void
    {
        $preference = UserPreference::factory()->create([
            'preferred_odds_format' => 'invalid',
        ]);

        $this->assertEquals(OddsFormat::DECIMAL, $preference->getOddsFormat());
    }

    public function test_should_alert_for_prediction(): void
    {
        $preference = UserPreference::factory()->create([
            'notify_predictions' => true,
            'min_confidence_alert' => 70,
            'min_edge_alert' => 5,
        ]);

        // Confidence above threshold
        $this->assertTrue($preference->shouldAlertForPrediction(75, 3));

        // Edge above threshold
        $this->assertTrue($preference->shouldAlertForPrediction(60, 8));

        // Neither above threshold
        $this->assertFalse($preference->shouldAlertForPrediction(60, 3));
    }

    public function test_should_alert_returns_false_when_disabled(): void
    {
        $preference = UserPreference::factory()->notificationsDisabled()->create();

        $this->assertFalse($preference->shouldAlertForPrediction(90, 15));
    }

    public function test_get_defaults(): void
    {
        $defaults = UserPreference::getDefaults();

        $this->assertArrayHasKey('notify_predictions', $defaults);
        $this->assertArrayHasKey('email_enabled', $defaults);
        $this->assertArrayHasKey('min_confidence_alert', $defaults);
        $this->assertArrayHasKey('preferred_odds_format', $defaults);
        $this->assertTrue($defaults['notify_predictions']);
        $this->assertEquals('decimal', $defaults['preferred_odds_format']);
    }

    public function test_casts_arrays_correctly(): void
    {
        $preference = UserPreference::factory()->create([
            'favorite_teams' => [1, 2, 3],
            'preferred_bookmakers' => ['Bet365', 'DraftKings'],
        ]);

        $this->assertIsArray($preference->favorite_teams);
        $this->assertIsArray($preference->preferred_bookmakers);
    }
}
