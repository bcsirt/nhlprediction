<?php

declare(strict_types=1);

namespace Tests\Unit\Domains\User\Enums;

use App\Domains\User\Enums\NotificationType;
use PHPUnit\Framework\TestCase;

class NotificationTypeTest extends TestCase
{
    public function test_it_has_all_expected_cases(): void
    {
        $cases = NotificationType::cases();

        $this->assertCount(7, $cases);
        $this->assertEquals('prediction', NotificationType::PREDICTION->value);
        $this->assertEquals('value_bet', NotificationType::VALUE_BET->value);
        $this->assertEquals('result', NotificationType::RESULT->value);
        $this->assertEquals('bankroll_alert', NotificationType::BANKROLL_ALERT->value);
        $this->assertEquals('streak', NotificationType::STREAK->value);
        $this->assertEquals('system', NotificationType::SYSTEM->value);
        $this->assertEquals('favorite_team', NotificationType::FAVORITE_TEAM->value);
    }

    public function test_label_returns_french_labels(): void
    {
        $this->assertEquals('Nouvelle prédiction', NotificationType::PREDICTION->label());
        $this->assertEquals('Value bet détecté', NotificationType::VALUE_BET->label());
        $this->assertEquals('Résultat de match', NotificationType::RESULT->label());
        $this->assertEquals('Alerte bankroll', NotificationType::BANKROLL_ALERT->label());
    }

    public function test_icon_returns_emoji(): void
    {
        $this->assertEquals('🎯', NotificationType::PREDICTION->icon());
        $this->assertEquals('💰', NotificationType::VALUE_BET->icon());
        $this->assertEquals('📊', NotificationType::RESULT->icon());
        $this->assertEquals('⚠️', NotificationType::BANKROLL_ALERT->icon());
        $this->assertEquals('🔥', NotificationType::STREAK->icon());
    }

    public function test_color_returns_valid_colors(): void
    {
        $this->assertEquals('blue', NotificationType::PREDICTION->color());
        $this->assertEquals('green', NotificationType::VALUE_BET->color());
        $this->assertEquals('red', NotificationType::BANKROLL_ALERT->color());
    }

    public function test_priority_returns_valid_range(): void
    {
        foreach (NotificationType::cases() as $type) {
            $priority = $type->priority();
            $this->assertGreaterThanOrEqual(1, $priority);
            $this->assertLessThanOrEqual(5, $priority);
        }
    }

    public function test_bankroll_alert_has_highest_priority(): void
    {
        $this->assertEquals(5, NotificationType::BANKROLL_ALERT->priority());
    }

    public function test_is_important(): void
    {
        $this->assertTrue(NotificationType::BANKROLL_ALERT->isImportant());
        $this->assertTrue(NotificationType::VALUE_BET->isImportant());
        $this->assertFalse(NotificationType::PREDICTION->isImportant());
        $this->assertFalse(NotificationType::SYSTEM->isImportant());
    }

    public function test_preference_key(): void
    {
        $this->assertEquals('notify_predictions', NotificationType::PREDICTION->preferenceKey());
        $this->assertEquals('notify_value_bets', NotificationType::VALUE_BET->preferenceKey());
        $this->assertEquals('notify_results', NotificationType::RESULT->preferenceKey());
        $this->assertEquals('notify_bankroll_alerts', NotificationType::BANKROLL_ALERT->preferenceKey());
    }
}
