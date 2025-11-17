<?php

namespace App\Domains\AI\DTOs;

use App\Domains\AI\Enums\ClaudeModel;

/**
 * DTO pour les requêtes à l'API Claude.
 */
class ClaudeRequest
{
    public function __construct(
        public readonly string $prompt,
        public readonly ClaudeModel $model,
        public readonly ?string $systemPrompt = null,
        public readonly int $maxTokens = 4096,
        public readonly float $temperature = 0.7,
        public readonly float $topP = 1.0,
        public readonly ?int $topK = null,
        public readonly bool $stream = false,
        public readonly array $metadata = [],
        public readonly ?array $conversationHistory = null,
    ) {}

    /**
     * Créer une requête pour l'analyse de match.
     */
    public static function forGameAnalysis(
        string $prompt,
        array $metadata = []
    ): self {
        return new self(
            prompt: $prompt,
            model: ClaudeModel::SONNET,
            systemPrompt: config('claude.prompts.system.game_analyst'),
            maxTokens: 4096,
            temperature: 0.5,
            metadata: $metadata,
        );
    }

    /**
     * Créer une requête pour les conseils de stratégie.
     */
    public static function forStrategyAdvice(
        string $prompt,
        array $metadata = []
    ): self {
        return new self(
            prompt: $prompt,
            model: ClaudeModel::OPUS,
            systemPrompt: config('claude.prompts.system.strategy_advisor'),
            maxTokens: 4096,
            temperature: 0.7,
            metadata: $metadata,
        );
    }

    /**
     * Créer une requête pour la génération de rapports.
     */
    public static function forReportGeneration(
        string $prompt,
        array $metadata = []
    ): self {
        return new self(
            prompt: $prompt,
            model: ClaudeModel::SONNET,
            systemPrompt: config('claude.prompts.system.report_generator'),
            maxTokens: 8192,
            temperature: 0.6,
            metadata: $metadata,
        );
    }

    /**
     * Créer une requête pour le chat.
     */
    public static function forChat(
        string $prompt,
        ?array $conversationHistory = null,
        array $metadata = []
    ): self {
        return new self(
            prompt: $prompt,
            model: ClaudeModel::HAIKU,
            systemPrompt: config('claude.prompts.system.chat_assistant'),
            maxTokens: 2048,
            temperature: 0.8,
            conversationHistory: $conversationHistory,
            metadata: $metadata,
        );
    }

    /**
     * Convertir en tableau pour l'API.
     */
    public function toArray(): array
    {
        $data = [
            'model' => $this->model->value,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'top_p' => $this->topP,
            'messages' => [],
        ];

        // Ajouter l'historique de conversation si présent
        if ($this->conversationHistory) {
            $data['messages'] = $this->conversationHistory;
        }

        // Ajouter le prompt utilisateur
        $data['messages'][] = [
            'role' => 'user',
            'content' => $this->prompt,
        ];

        // Ajouter le system prompt si présent
        if ($this->systemPrompt) {
            $data['system'] = $this->systemPrompt;
        }

        // Ajouter top_k si présent
        if ($this->topK !== null) {
            $data['top_k'] = $this->topK;
        }

        // Ajouter stream si activé
        if ($this->stream) {
            $data['stream'] = true;
        }

        // Ajouter metadata si présent
        if (!empty($this->metadata)) {
            $data['metadata'] = $this->metadata;
        }

        return $data;
    }
}
