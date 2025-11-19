<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Services;

use App\Domains\User\Enums\NotificationType;
use App\Domains\User\Models\Notification;
use App\Domains\User\Models\UserPreference;
use App\Domains\User\Services\NotificationService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NotificationService();
    }

    public function test_send_creates_notification(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_predictions' => true,
        ]);

        $notification = $this->service->send(
            $user,
            NotificationType::PREDICTION,
            'Test Title',
            'Test Message',
            ['key' => 'value'],
            '/test/url'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals('Test Title', $notification->title);
        $this->assertEquals('Test Message', $notification->message);
        $this->assertEquals('/test/url', $notification->action_url);
        $this->assertEquals('value', $notification->data['key']);
    }

    public function test_send_returns_null_when_notification_disabled(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_predictions' => false,
        ]);

        $notification = $this->service->send(
            $user,
            NotificationType::PREDICTION,
            'Test Title',
            'Test Message'
        );

        $this->assertNull($notification);
    }

    public function test_send_creates_default_preferences_if_none_exist(): void
    {
        $user = User::factory()->create();

        $notification = $this->service->send(
            $user,
            NotificationType::PREDICTION,
            'Test Title',
            'Test Message'
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertDatabaseHas('user_preferences', [
            'user_id' => $user->id,
        ]);
    }

    public function test_send_sets_icon_and_color_from_type(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_value_bets' => true,
        ]);

        $notification = $this->service->send(
            $user,
            NotificationType::VALUE_BET,
            'Value Bet',
            'Found a value bet'
        );

        $this->assertEquals('💰', $notification->icon);
        $this->assertEquals('green', $notification->color);
    }

    public function test_send_marks_important_notifications(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_bankroll_alerts' => true,
        ]);

        $notification = $this->service->send(
            $user,
            NotificationType::BANKROLL_ALERT,
            'Alert',
            'Bankroll alert'
        );

        $this->assertTrue($notification->is_important);
    }

    public function test_notify_value_bet(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_value_bets' => true,
            'min_edge_alert' => 5,
        ]);

        $notification = $this->service->notifyValueBet(
            $user,
            'TOR @ BOS',
            'Toronto Maple Leafs',
            2.15,
            8.5,
            12.3
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertStringContainsString('TOR @ BOS', $notification->title);
        $this->assertStringContainsString('8.5%', $notification->message);
        $this->assertEquals(8.5, $notification->data['edge']);
    }

    public function test_notify_value_bet_returns_null_below_threshold(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_value_bets' => true,
            'min_edge_alert' => 10,
        ]);

        $notification = $this->service->notifyValueBet(
            $user,
            'TOR @ BOS',
            'Toronto Maple Leafs',
            2.15,
            5.0, // Below threshold
            8.3
        );

        $this->assertNull($notification);
    }

    public function test_notify_value_bet_returns_null_when_disabled(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_value_bets' => false,
        ]);

        $notification = $this->service->notifyValueBet(
            $user,
            'TOR @ BOS',
            'Toronto Maple Leafs',
            2.15,
            15.0,
            12.3
        );

        $this->assertNull($notification);
    }

    public function test_notify_result(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_results' => true,
        ]);

        $notification = $this->service->notifyResult(
            $user,
            'TOR @ BOS',
            '4-2',
            true,
            150.00
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertStringContainsString('Gagné', $notification->title);
        $this->assertStringContainsString('+$150.00', $notification->message);
        $this->assertTrue($notification->data['won']);
    }

    public function test_notify_result_losing_bet(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_results' => true,
        ]);

        $notification = $this->service->notifyResult(
            $user,
            'TOR @ BOS',
            '2-4',
            false,
            -100.00
        );

        $this->assertStringContainsString('Perdu', $notification->title);
        $this->assertEquals(-100.00, $notification->data['profit_loss']);
    }

    public function test_notify_bankroll_alert_drawdown(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_bankroll_alerts' => true,
        ]);

        $notification = $this->service->notifyBankrollAlert(
            $user,
            'drawdown',
            25.0,
            20.0
        );

        $this->assertInstanceOf(Notification::class, $notification);
        $this->assertStringContainsString('Drawdown', $notification->title);
        $this->assertStringContainsString('25%', $notification->message);
    }

    public function test_notify_bankroll_alert_daily_limit(): void
    {
        $user = User::factory()->create();
        UserPreference::factory()->create([
            'user_id' => $user->id,
            'notify_bankroll_alerts' => true,
        ]);

        $notification = $this->service->notifyBankrollAlert(
            $user,
            'daily_limit',
            150.00,
            100.00
        );

        $this->assertStringContainsString('journalière', $notification->title);
    }

    public function test_mark_all_as_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->unread()->count(3)->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $count = $this->service->markAllAsRead($user);

        $this->assertEquals(3, $count);
        $this->assertEquals(0, $this->service->getUnreadCount($user));
    }

    public function test_get_unread_count(): void
    {
        $user = User::factory()->create();
        Notification::factory()->unread()->count(5)->create(['user_id' => $user->id]);
        Notification::factory()->read()->count(3)->create(['user_id' => $user->id]);

        $count = $this->service->getUnreadCount($user);

        $this->assertEquals(5, $count);
    }

    public function test_clean_old_notifications(): void
    {
        $user = User::factory()->create();

        // Old read notifications (should be deleted)
        Notification::factory()->read()->count(3)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(31),
        ]);

        // Old unread notifications (should NOT be deleted)
        Notification::factory()->unread()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(31),
        ]);

        // Recent notifications (should NOT be deleted)
        Notification::factory()->read()->count(2)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        $deleted = $this->service->cleanOldNotifications(30);

        $this->assertEquals(3, $deleted);
        $this->assertEquals(4, Notification::where('user_id', $user->id)->count());
    }

    public function test_clean_old_notifications_custom_days(): void
    {
        $user = User::factory()->create();

        Notification::factory()->read()->count(5)->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(8),
        ]);

        $deleted = $this->service->cleanOldNotifications(7);

        $this->assertEquals(5, $deleted);
    }
}
