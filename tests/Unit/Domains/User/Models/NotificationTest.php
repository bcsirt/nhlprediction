<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Models;

use App\Domains\User\Enums\NotificationType;
use App\Domains\User\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $notification = Notification::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $notification->user);
    }

    public function test_get_type_enum(): void
    {
        $notification = Notification::factory()->create([
            'type' => NotificationType::VALUE_BET->value,
        ]);

        $this->assertEquals(NotificationType::VALUE_BET, $notification->getTypeEnum());
    }

    public function test_is_read(): void
    {
        $unread = Notification::factory()->unread()->create();
        $read = Notification::factory()->read()->create();

        $this->assertFalse($unread->isRead());
        $this->assertTrue($read->isRead());
    }

    public function test_mark_as_read(): void
    {
        $notification = Notification::factory()->unread()->create();

        $this->assertFalse($notification->isRead());

        $notification->markAsRead();

        $this->assertTrue($notification->isRead());
        $this->assertNotNull($notification->read_at);
    }

    public function test_mark_as_read_does_not_update_if_already_read(): void
    {
        $notification = Notification::factory()->read()->create();
        $originalReadAt = $notification->read_at;

        $notification->markAsRead();

        $this->assertEquals($originalReadAt->toDateTimeString(), $notification->read_at->toDateTimeString());
    }

    public function test_mark_as_unread(): void
    {
        $notification = Notification::factory()->read()->create();

        $notification->markAsUnread();

        $this->assertFalse($notification->isRead());
        $this->assertNull($notification->read_at);
    }

    public function test_time_ago_attribute(): void
    {
        $notification = Notification::factory()->create([
            'created_at' => now()->subHours(2),
        ]);

        $this->assertStringContainsString('2 hours', $notification->time_ago);
    }

    public function test_scope_unread(): void
    {
        $user = User::factory()->create();
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $unread = Notification::where('user_id', $user->id)->unread()->get();

        $this->assertCount(2, $unread);
    }

    public function test_scope_read(): void
    {
        $user = User::factory()->create();
        Notification::factory()->unread()->create(['user_id' => $user->id]);
        Notification::factory()->read()->create(['user_id' => $user->id]);

        $read = Notification::where('user_id', $user->id)->read()->get();

        $this->assertCount(1, $read);
    }

    public function test_scope_important(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create(['user_id' => $user->id, 'is_important' => true]);
        Notification::factory()->create(['user_id' => $user->id, 'is_important' => false]);

        $important = Notification::where('user_id', $user->id)->important()->get();

        $this->assertCount(1, $important);
    }

    public function test_scope_of_type(): void
    {
        $user = User::factory()->create();
        Notification::factory()->prediction()->create(['user_id' => $user->id]);
        Notification::factory()->valueBet()->create(['user_id' => $user->id]);

        $predictions = Notification::where('user_id', $user->id)
            ->ofType(NotificationType::PREDICTION)
            ->get();

        $this->assertCount(1, $predictions);
    }

    public function test_scope_recent(): void
    {
        $user = User::factory()->create();
        Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(3),
        ]);
        Notification::factory()->create([
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        $recent = Notification::where('user_id', $user->id)->recent(7)->get();

        $this->assertCount(1, $recent);
    }

    public function test_create_for_prediction(): void
    {
        $user = User::factory()->create();

        $notification = Notification::createForPrediction(
            $user->id,
            'Test Title',
            'Test Message',
            123,
            ['probability' => 0.65]
        );

        $this->assertEquals($user->id, $notification->user_id);
        $this->assertEquals(NotificationType::PREDICTION->value, $notification->type);
        $this->assertEquals('Test Title', $notification->title);
        $this->assertEquals(0.65, $notification->data['probability']);
    }

    public function test_create_for_value_bet(): void
    {
        $user = User::factory()->create();

        $notification = Notification::createForValueBet(
            $user->id,
            'Value Bet Found',
            'Great opportunity',
            ['edge' => 8.5]
        );

        $this->assertEquals(NotificationType::VALUE_BET->value, $notification->type);
        $this->assertTrue($notification->is_important);
    }

    public function test_create_bankroll_alert(): void
    {
        $user = User::factory()->create();

        $notification = Notification::createBankrollAlert(
            $user->id,
            'Drawdown Alert',
            'Your drawdown is 25%',
            ['current' => 25]
        );

        $this->assertEquals(NotificationType::BANKROLL_ALERT->value, $notification->type);
        $this->assertTrue($notification->is_important);
    }

    public function test_uses_uuid(): void
    {
        $notification = Notification::factory()->create();

        $this->assertIsString($notification->id);
        $this->assertEquals(36, strlen($notification->id)); // UUID length
    }

    public function test_casts_data_as_array(): void
    {
        $notification = Notification::factory()->create([
            'data' => ['key' => 'value', 'number' => 123],
        ]);

        $this->assertIsArray($notification->data);
        $this->assertEquals('value', $notification->data['key']);
    }
}
