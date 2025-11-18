<?php

declare(strict_types=1);

namespace App\Domains\User\Enums;

enum NotificationChannel: string
{
    case EMAIL = 'email';
    case PUSH = 'push';
    case SMS = 'sms';
    case IN_APP = 'in_app';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::EMAIL => 'Email',
            self::PUSH => 'Push',
            self::SMS => 'SMS',
            self::IN_APP => 'In-App',
        };
    }

    /**
     * Obtenir l'icône.
     */
    public function icon(): string
    {
        return match($this) {
            self::EMAIL => '📧',
            self::PUSH => '🔔',
            self::SMS => '📱',
            self::IN_APP => '💻',
        };
    }

    /**
     * Clé de préférence pour activer ce canal.
     */
    public function preferenceKey(): string
    {
        return match($this) {
            self::EMAIL => 'email_enabled',
            self::PUSH => 'push_enabled',
            self::SMS => 'sms_enabled',
            self::IN_APP => 'in_app_enabled',
        };
    }

    /**
     * Vérifier si le canal supporte le contenu riche.
     */
    public function supportsRichContent(): bool
    {
        return in_array($this, [self::EMAIL, self::PUSH, self::IN_APP]);
    }

    /**
     * Longueur max du message pour ce canal.
     */
    public function maxMessageLength(): int
    {
        return match($this) {
            self::SMS => 160,
            self::PUSH => 256,
            self::IN_APP => 1000,
            self::EMAIL => 10000,
        };
    }
}
