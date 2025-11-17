<?php

namespace App\Domains\AI\Services;

use App\Domains\DataIngestion\Models\Game;

/**
 * Service pour construire les prompts envoyés à Claude.
 */
class PromptBuilder
{
    /**
     * Construire un prompt pour l'analyse d'un match.
     */
    public function buildGameAnalysisPrompt(Game $game): string
    {
        // Charger les relations nécessaires
        $game->load(['homeTeam', 'awayTeam', 'homeTeamStats', 'awayTeamStats']);

        $homeTeam = $game->homeTeam->name;
        $awayTeam = $game->awayTeam->name;
        $gameDate = $game->game_date->format('d/m/Y H:i');

        return <<<PROMPT
Analyse le match NHL suivant et fournis une analyse détaillée :

**Match**: {$homeTeam} vs {$awayTeam}
**Date**: {$gameDate}
**Lieu**: {$game->venue}

**Statistiques {$homeTeam} (Domicile)**:
{$this->formatTeamStats($game->homeTeamStats)}

**Statistiques {$awayTeam} (Extérieur)**:
{$this->formatTeamStats($game->awayTeamStats)}

Fournis une analyse complète incluant :

1. **Forces et faiblesses** de chaque équipe
2. **Facteurs clés** qui pourraient influencer le résultat
3. **Statistiques avancées** (Corsi, Fenwick, xG) et leur signification
4. **Forme récente** et tendances
5. **Head-to-head** si applicable
6. **Prédiction** avec niveau de confiance
7. **Facteurs de risque** ou incertitudes

Sois factuel, précis, et base ton analyse sur les données fournies.
PROMPT;
    }

    /**
     * Construire un prompt pour la comparaison d'équipes.
     */
    public function buildTeamComparisonPrompt(int $team1Id, int $team2Id): string
    {
        // TODO: Charger les données des équipes depuis la DB
        // Pour l'instant, un placeholder

        return <<<PROMPT
Compare les deux équipes NHL suivantes de manière détaillée :

**Équipe 1**: [ID: {$team1Id}]
**Équipe 2**: [ID: {$team2Id}]

Analyse et compare :

1. **Performances offensives** (buts/match, shots, PP%)
2. **Solidité défensive** (buts contre, PK%, save%)
3. **Statistiques avancées** (Corsi%, Fenwick%, PDO)
4. **Forme récente** (10 derniers matchs)
5. **Confrontations directes** cette saison
6. **Avantages/Désavantages** de chaque équipe

Conclus avec quelle équipe a l'avantage global et pourquoi.
PROMPT;
    }

    /**
     * Construire un prompt pour expliquer un value bet.
     */
    public function buildValueBetExplanationPrompt(
        array $predictionData,
        array $oddsData
    ): string {
        $ourProba = $predictionData['home_win_probability'] ?? 0;
        $bookmakerOdds = $oddsData['home_odds'] ?? 0;
        $impliedProba = $bookmakerOdds > 0 ? 1 / $bookmakerOdds : 0;

        $value = (($ourProba * $bookmakerOdds) - 1) * 100;

        return <<<PROMPT
Explique ce value bet de manière claire et pédagogique :

**Notre prédiction**:
- Probabilité de victoire domicile: {$ourProba}%
- Niveau de confiance: {$predictionData['confidence']}

**Cotes bookmaker**:
- Cote domicile: {$bookmakerOdds}
- Probabilité implicite: {$impliedProba}%

**Value calculée**: {$value}%

Explique :

1. **Pourquoi c'est un value bet** (différence entre notre proba et celle du bookmaker)
2. **Le calcul de la value** de manière simple
3. **Les facteurs** qui justifient notre prédiction
4. **Les risques** associés à ce pari
5. **Une recommandation** sur la taille de mise (Kelly Criterion)

Utilise un langage accessible mais précis.
PROMPT;
    }

    /**
     * Construire un prompt pour des conseils stratégiques.
     */
    public function buildStrategyAdvicePrompt(array $context): string
    {
        $bankroll = $context['bankroll'] ?? 1000;
        $roi = $context['roi'] ?? 0;
        $winRate = $context['win_rate'] ?? 0;

        return <<<PROMPT
En tant que conseiller en stratégie de paris NHL, analyse la situation suivante et fournis des conseils :

**Situation actuelle**:
- Bankroll: {$bankroll}€
- ROI actuel: {$roi}%
- Taux de réussite: {$winRate}%

**Contexte**:
{$this->formatContext($context)}

Fournis des conseils sur :

1. **Gestion de bankroll** (taille des mises recommandée)
2. **Stratégie de sélection** (quels types de paris privilégier)
3. **Gestion du risque** (diversification, stop-loss)
4. **Ajustements** basés sur les performances actuelles
5. **Objectifs réalistes** à court et moyen terme

Sois prudent et responsable dans tes recommandations.
PROMPT;
    }

    /**
     * Formater les statistiques d'une équipe.
     */
    private function formatTeamStats($stats): string
    {
        if (!$stats) {
            return "Statistiques non disponibles";
        }

        return sprintf(
            "- Buts/match: %.2f | Buts contre/match: %.2f\n" .
            "- Shots/match: %.1f | Save%%: %.3f\n" .
            "- Power Play: %.1f%% | Penalty Kill: %.1f%%\n" .
            "- Corsi For%%: %.1f%% | Fenwick For%%: %.1f%%\n" .
            "- PDO: %.3f",
            $stats->goals_per_game ?? 0,
            $stats->goals_against_per_game ?? 0,
            $stats->shots_per_game ?? 0,
            $stats->save_percentage ?? 0,
            $stats->power_play_percentage ?? 0,
            $stats->penalty_kill_percentage ?? 0,
            $stats->corsi_for_percentage ?? 0,
            $stats->fenwick_for_percentage ?? 0,
            $stats->pdo ?? 1.000
        );
    }

    /**
     * Formater le contexte pour les prompts.
     */
    private function formatContext(array $context): string
    {
        $formatted = [];

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                continue; // Skip nested arrays
            }

            $label = str_replace('_', ' ', ucfirst($key));
            $formatted[] = "- {$label}: {$value}";
        }

        return implode("\n", $formatted);
    }
}
