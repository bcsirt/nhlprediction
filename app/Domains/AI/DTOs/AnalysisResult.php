<?php

namespace App\Domains\AI\DTOs;

use App\Domains\AI\Enums\AnalysisType;
use Carbon\Carbon;

/**
 * DTO pour les résultats d'analyse Claude.
 */
class AnalysisResult
{
    public function __construct(
        public readonly AnalysisType $type,
        public readonly string $content,
        public readonly array $data,
        public readonly ClaudeResponse $claudeResponse,
        public readonly Carbon $generatedAt,
        public readonly ?int $relatedId = null,
        public readonly array $metadata = [],
    ) {}

    /**
     * Créer un résultat d'analyse de match.
     */
    public static function gameAnalysis(
        string $content,
        array $data,
        ClaudeResponse $response,
        int $gameId
    ): self {
        return new self(
            type: AnalysisType::GAME_ANALYSIS,
            content: $content,
            data: $data,
            claudeResponse: $response,
            generatedAt: now(),
            relatedId: $gameId,
        );
    }

    /**
     * Créer un résultat de conseil stratégique.
     */
    public static function strategyAdvice(
        string $content,
        array $data,
        ClaudeResponse $response
    ): self {
        return new self(
            type: AnalysisType::STRATEGY_ADVICE,
            content: $content,
            data: $data,
            claudeResponse: $response,
            generatedAt: now(),
        );
    }

    /**
     * Créer un rapport de performance.
     */
    public static function performanceReport(
        string $content,
        array $data,
        ClaudeResponse $response,
        ?int $userId = null
    ): self {
        return new self(
            type: AnalysisType::PERFORMANCE_REPORT,
            content: $content,
            data: $data,
            claudeResponse: $response,
            generatedAt: now(),
            relatedId: $userId,
        );
    }

    /**
     * Convertir en tableau.
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'content' => $this->content,
            'data' => $this->data,
            'claude_response' => $this->claudeResponse->toArray(),
            'generated_at' => $this->generatedAt->toIso8601String(),
            'related_id' => $this->relatedId,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Obtenir un résumé court.
     */
    public function getSummary(int $maxLength = 200): string
    {
        return strlen($this->content) > $maxLength
            ? substr($this->content, 0, $maxLength) . '...'
            : $this->content;
    }
}
