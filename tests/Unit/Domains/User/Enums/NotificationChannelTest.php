<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Enums;

use App\Domains\User\Enums\NotificationChannel;
use PHPUnit\Framework\TestCase;

class NotificationChannelTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = NotificationChannel::cases();

        $this->assertCount(4, $cases);
        $this->assertEquals('email', NotificationChannel::EMAIL->value);
        $this->assertEquals('push', NotificationChannel::PUSH->value);
        $this->assertEquals('sms', NotificationChannel::SMS->value);
        $this->assertEquals('in_app', NotificationChannel::IN_APP->value);
    }

    public function test_label_returns_labels(): void
    {
        $this->assertEquals('Email', NotificationChannel::EMAIL->label());
        $this->assertEquals('Push', NotificationChannel::PUSH->label());
        $this->assertEquals('SMS', NotificationChannel::SMS->label());
        $this->assertEquals('In-App', NotificationChannel::IN_APP->label());
    }

    public function test_icon_returns_emoji(): void
    {
        $this->assertEquals('📧', NotificationChannel::EMAIL->icon());
        $this->assertEquals('🔔', NotificationChannel::PUSH->icon());
        $this->assertEquals('📱', NotificationChannel::SMS->icon());
        $this->assertEquals('💻', NotificationChannel::IN_APP->icon());
    }

    public function test_preference_key(): void
    {
        $this->assertEquals('email_enabled', NotificationChannel::EMAIL->preferenceKey());
        $this->assertEquals('push_enabled', NotificationChannel::PUSH->preferenceKey());
        $this->assertEquals('sms_enabled', NotificationChannel::SMS->preferenceKey());
    }

    public function test_supports_rich_content(): void
    {
        $this->assertTrue(NotificationChannel::EMAIL->supportsRichContent());
        $this->assertTrue(NotificationChannel::PUSH->supportsRichContent());
        $this->assertTrue(NotificationChannel::IN_APP->supportsRichContent());
        $this->assertFalse(NotificationChannel::SMS->supportsRichContent());
    }

    public function test_max_message_length(): void
    {
        $this->assertEquals(160, NotificationChannel::SMS->maxMessageLength());
        $this->assertEquals(256, NotificationChannel::PUSH->maxMessageLength());
        $this->assertEquals(1000, NotificationChannel::IN_APP->maxMessageLength());
        $this->assertEquals(10000, NotificationChannel::EMAIL->maxMessageLength());
    }

    public function test_sms_has_shortest_max_length(): void
    {
        $minLength = PHP_INT_MAX;
        $shortestChannel = null;

        foreach (NotificationChannel::cases() as $channel) {
            if ($channel->maxMessageLength() < $minLength) {
                $minLength = $channel->maxMessageLength();
                $shortestChannel = $channel;
            }
        }

        $this->assertEquals(NotificationChannel::SMS, $shortestChannel);
    }
}
