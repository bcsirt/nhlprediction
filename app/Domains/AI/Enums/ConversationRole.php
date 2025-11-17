<?php

namespace App\Domains\AI\Enums;

enum ConversationRole: string
{
    case USER = 'user';
    case ASSISTANT = 'assistant';
    case SYSTEM = 'system';

    /**
     * Obtenir le libellé.
     */
    public function label(): string
    {
        return match($this) {
            self::USER => 'Utilisateur',
            self::ASSISTANT => 'Assistant IA',
            self::SYSTEM => 'Système',
        };
    }

    /**
     * Vérifier si c'est un message utilisateur.
     */
    public function isUser(): bool
    {
        return $this === self::USER;
    }

    /**
     * Vérifier si c'est un message assistant.
     */
    public function isAssistant(): bool
    {
        return $this === self::ASSISTANT;
    }

    /**
     * Vérifier si c'est un message système.
     */
    public function isSystem(): bool
    {
        return $this === self::SYSTEM;
    }
}
