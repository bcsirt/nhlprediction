<?php

namespace App\Console\Commands\DataIngestion;

use App\Domains\DataIngestion\Services\NHLApiService;
use Illuminate\Console\Command;

/**
 * Commande pour récupérer les matchs NHL depuis l'API.
 */
class FetchNHLGames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nhl:fetch-games {date?  : Date au format YYYY-MM-DD (défaut: aujourd\'hui)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Récupère les matchs NHL pour une date donnée';

    /**
     * Execute the console command.
     */
    public function handle(NHLApiService $nhlApi): int
    {
        $date = $this->argument('date') ?? now()->format('Y-m-d');

        $this->info("🏒 Récupération des matchs NHL pour le {$date}...");

        try {
            $games = $date === now()->format('Y-m-d')
                ? $nhlApi->getTodayGames()
                : $nhlApi->getGamesForDate($date);

            if (empty($games)) {
                $this->warn("Aucun match trouvé pour cette date.");
                return self::SUCCESS;
            }

            $count = count($games);
            $this->info("✅ {$count} match(s) trouvé(s)\n");

            // Afficher les matchs dans un tableau
            $tableData = [];
            foreach ($games as $game) {
                $homeTeam = $game['homeTeam']['abbrev'] ?? 'N/A';
                $awayTeam = $game['awayTeam']['abbrev'] ?? 'N/A';
                $homeScore = $game['homeTeam']['score'] ?? '-';
                $awayScore = $game['awayTeam']['score'] ?? '-';
                $status = $game['gameState'] ?? 'N/A';

                $tableData[] = [
                    'ID' => $game['id'] ?? 'N/A',
                    'Match' => "{$awayTeam} @ {$homeTeam}",
                    'Score' => "{$awayScore} - {$homeScore}",
                    'Statut' => $status,
                ];
            }

            $this->table(
                ['ID', 'Match', 'Score', 'Statut'],
                $tableData
            );

            $this->newLine();
            $this->info("💾 Pour sauvegarder ces matchs en base de données, utilisez:");
            $this->line("   php artisan nhl:sync-games {$date}");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Erreur lors de la récupération des matchs:");
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
