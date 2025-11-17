<?php

namespace App\Domains\AI\Enums;

enum ClaudeModel: string
{
    case OPUS = 'claude-3-opus-20240229';
    case SONNET = 'claude-3-5-sonnet-20241022';
    case HAIKU = 'claude-3-5-haiku-20241022';

    /**
     * Obtenir le nom lisible du modèle.
     */
    public function label(): string
    {
        return match($this) {
            self::OPUS => 'Claude 3 Opus',
            self::SONNET => 'Claude 3.5 Sonnet',
            self::HAIKU => 'Claude 3.5 Haiku',
        };
    }

    /**
     * Obtenir le coût approximatif par 1M tokens (input).
     */
    public function inputCostPer1M(): float
    {
        return match($this) {
            self::OPUS => 15.00,
            self::SONNET => 3.00,
            self::HAIKU => 0.80,
        };
    }

    /**
     * Obtenir le coût approximatif par 1M tokens (output).
     */
    public function outputCostPer1M(): float
    {
        return match($this) {
            self::OPUS => 75.00,
            self::SONNET => 15.00,
            self::HAIKU => 4.00,
        };
    }

    /**
     * Obtenir le nombre maximum de tokens.
     */
    public function maxTokens(): int
    {
        return match($this) {
            self::OPUS => 4096,
            self::SONNET => 8192,
            self::HAIKU => 4096,
        };
    }

    /**
     * Vérifier si c'est le modèle le plus puissant.
     */
    public function isPremium(): bool
    {
        return $this === self::OPUS;
    }

    /**
     * Vérifier si c'est le modèle le plus rapide/économique.
     */
    public function isFast(): bool
    {
        return $this === self::HAIKU;
    }
}
