<?php

namespace App\Domains\AI\Enums;

enum AnalysisType: string
{
    case GAME_ANALYSIS = 'game_analysis';
    case STRATEGY_ADVICE = 'strategy_advice';
    case PERFORMANCE_REPORT = 'performance_report';
    case VALUE_BET_EXPLANATION = 'value_bet_explanation';
    case TEAM_COMPARISON = 'team_comparison';
    case PLAYER_ANALYSIS = 'player_analysis';
    case TREND_ANALYSIS = 'trend_analysis';
    case RISK_ASSESSMENT = 'risk_assessment';

    /**
     * Obtenir le libellé en français.
     */
    public function label(): string
    {
        return match($this) {
            self::GAME_ANALYSIS => 'Analyse de Match',
            self::STRATEGY_ADVICE => 'Conseil Stratégique',
            self::PERFORMANCE_REPORT => 'Rapport de Performance',
            self::VALUE_BET_EXPLANATION => 'Explication Value Bet',
            self::TEAM_COMPARISON => 'Comparaison d\'Équipes',
            self::PLAYER_ANALYSIS => 'Analyse de Joueur',
            self::TREND_ANALYSIS => 'Analyse de Tendances',
            self::RISK_ASSESSMENT => 'Évaluation des Risques',
        };
    }

    /**
     * Obtenir le modèle Claude recommandé pour ce type d'analyse.
     */
    public function recommendedModel(): ClaudeModel
    {
        return match($this) {
            self::GAME_ANALYSIS => ClaudeModel::SONNET,
            self::STRATEGY_ADVICE => ClaudeModel::OPUS,
            self::PERFORMANCE_REPORT => ClaudeModel::SONNET,
            self::VALUE_BET_EXPLANATION => ClaudeModel::HAIKU,
            self::TEAM_COMPARISON => ClaudeModel::SONNET,
            self::PLAYER_ANALYSIS => ClaudeModel::HAIKU,
            self::TREND_ANALYSIS => ClaudeModel::SONNET,
            self::RISK_ASSESSMENT => ClaudeModel::OPUS,
        };
    }

    /**
     * Obtenir le nombre de tokens max recommandé.
     */
    public function maxTokens(): int
    {
        return match($this) {
            self::GAME_ANALYSIS => 4096,
            self::STRATEGY_ADVICE => 4096,
            self::PERFORMANCE_REPORT => 8192,
            self::VALUE_BET_EXPLANATION => 2048,
            self::TEAM_COMPARISON => 3072,
            self::PLAYER_ANALYSIS => 2048,
            self::TREND_ANALYSIS => 4096,
            self::RISK_ASSESSMENT => 3072,
        };
    }

    /**
     * Vérifier si cette analyse nécessite des données historiques.
     */
    public function requiresHistoricalData(): bool
    {
        return in_array($this, [
            self::PERFORMANCE_REPORT,
            self::TREND_ANALYSIS,
            self::TEAM_COMPARISON,
        ]);
    }
}
