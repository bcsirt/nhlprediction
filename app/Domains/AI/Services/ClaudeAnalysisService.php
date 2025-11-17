<?php

namespace App\Domains\AI\Services;

use App\Domains\AI\DTOs\AnalysisResult;
use App\Domains\AI\DTOs\ClaudeRequest;
use App\Domains\AI\Enums\AnalysisType;
use App\Domains\DataIngestion\Models\Game;

/**
 * Service pour générer des analyses via Claude.
 */
class ClaudeAnalysisService
{
    public function __construct(
        private ClaudeService $claudeService,
        private PromptBuilder $promptBuilder,
    ) {}

    /**
     * Analyser un match NHL.
     */
    public function analyzeGame(Game $game): AnalysisResult
    {
        // Construire le prompt avec les données du match
        $prompt = $this->promptBuilder->buildGameAnalysisPrompt($game);

        // Créer la requête
        $request = ClaudeRequest::forGameAnalysis($prompt, [
            'type' => 'game_analysis',
            'game_id' => $game->id,
        ]);

        // Envoyer à Claude
        $response = $this->claudeService->sendRequest($request);

        if ($response->isError()) {
            throw new \RuntimeException('Erreur lors de l\'analyse: ' . $response->error);
        }

        // Créer le résultat d'analyse
        return AnalysisResult::gameAnalysis(
            content: $response->content,
            data: $this->extractGameInsights($response->content),
            response: $response,
            gameId: $game->id
        );
    }

    /**
     * Comparer deux équipes.
     */
    public function compareTeams(int $team1Id, int $team2Id): AnalysisResult
    {
        $prompt = $this->promptBuilder->buildTeamComparisonPrompt($team1Id, $team2Id);

        $request = ClaudeRequest::forGameAnalysis($prompt, [
            'type' => 'team_comparison',
            'team_ids' => [$team1Id, $team2Id],
        ]);

        $response = $this->claudeService->sendRequest($request);

        if ($response->isError()) {
            throw new \RuntimeException($response->error);
        }

        return new AnalysisResult(
            type: AnalysisType::TEAM_COMPARISON,
            content: $response->content,
            data: [],
            claudeResponse: $response,
            generatedAt: now(),
        );
    }

    /**
     * Expliquer un value bet.
     */
    public function explainValueBet(
        int $valueBetId,
        array $predictionData,
        array $oddsData
    ): AnalysisResult {
        $prompt = $this->promptBuilder->buildValueBetExplanationPrompt(
            $predictionData,
            $oddsData
        );

        $request = ClaudeRequest::forChat($prompt, null, [
            'type' => 'value_bet_explanation',
            'value_bet_id' => $valueBetId,
        ]);

        $response = $this->claudeService->sendRequest($request);

        if ($response->isError()) {
            throw new \RuntimeException($response->error);
        }

        return new AnalysisResult(
            type: AnalysisType::VALUE_BET_EXPLANATION,
            content: $response->content,
            data: compact('predictionData', 'oddsData'),
            claudeResponse: $response,
            generatedAt: now(),
            relatedId: $valueBetId,
        );
    }

    /**
     * Générer des conseils stratégiques.
     */
    public function generateStrategyAdvice(array $context): AnalysisResult
    {
        $prompt = $this->promptBuilder->buildStrategyAdvicePrompt($context);

        $request = ClaudeRequest::forStrategyAdvice($prompt, [
            'type' => 'strategy_advice',
        ]);

        $response = $this->claudeService->sendRequest($request);

        if ($response->isError()) {
            throw new \RuntimeException($response->error);
        }

        return AnalysisResult::strategyAdvice(
            content: $response->content,
            data: $context,
            response: $response
        );
    }

    /**
     * Extraire les insights clés du contenu de l'analyse.
     */
    private function extractGameInsights(string $content): array
    {
        // TODO: Parser le contenu pour extraire des insights structurés
        // Par exemple: score prédit, facteurs clés, niveau de confiance, etc.

        return [
            'raw_analysis' => $content,
            'extracted_at' => now()->toIso8601String(),
        ];
    }
}
