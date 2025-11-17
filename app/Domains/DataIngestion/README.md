# 🏒 Domaine DataIngestion

> Collecte et gestion des données NHL (équipes, joueurs, matchs, statistiques)

## 📋 Vue d'ensemble

Le domaine DataIngestion est responsable de la récupération et du stockage de toutes les données NHL nécessaires au projet :
- Équipes et conférences
- Joueurs et rosters
- Matchs (calendrier, scores, boxscores)
- Statistiques (équipes, joueurs, gardiens, matchs)

## 🏗️ Structure

```
app/Domains/DataIngestion/
├── Models/                      # 9 modèles Eloquent
│   ├── Team.php
│   ├── Player.php
│   ├── Game.php
│   ├── Season.php
│   ├── Conference.php
│   ├── TeamStats.php
│   ├── PlayerStats.php
│   ├── GoalieStats.php
│   └── GameStats.php
├── Services/
│   └── NHLApiService.php        # Service principal API NHL
├── Enums/
│   ├── GameStatus.php           # Statuts de matchs
│   ├── PlayerPosition.php       # Positions de joueurs
│   └── GameType.php             # Types de matchs
├── Jobs/                        # À implémenter
├── DTOs/                        # À implémenter
└── Repositories/                # À implémenter
```

## 🗄️ Base de Données

### Tables Principales

**conferences** - Conférences NHL
```sql
- id, name, abbreviation, nhl_id, active
```

**seasons** - Saisons NHL
```sql
- id, season_id (20232024), start_year, end_year, dates, is_current
```

**teams** - Équipes NHL (32 équipes)
```sql
- id, nhl_id, name, abbreviation, venue, conference_id, division, active
```

**players** - Joueurs NHL
```sql
- id, nhl_id, first_name, last_name, current_team_id, position
- birth_date, nationality, height_cm, weight_kg, shoots_catches
- draft_year, draft_round, draft_pick, active, rookie
```

**games** - Matchs NHL
```sql
- id, nhl_id, season_id, game_type, game_date, venue
- home_team_id, away_team_id, home_score, away_score
- status, period, winning_team_id, overtime, shootout
```

### Tables de Statistiques

**team_stats** - Stats d'équipes
```sql
- Statistiques de base: wins, losses, points, goals_for/against
- Special teams: power_play%, penalty_kill%
- Advanced stats: corsi%, fenwick%, xG, PDO, faceoff%
- Split: overall, home, away
```

**player_stats** - Stats de joueurs
```sql
- Scoring: goals, assists, points, +/-
- Shooting: shots, shooting%
- Time on Ice: total, even strength, PP, SH
- Advanced: hits, blocks, faceoffs, takeaways, giveaways
```

**goalie_stats** - Stats de gardiens
```sql
- Record: games_played, wins, losses, shutouts
- Saves: shots_against, saves, save%, GAA
- Quality starts, GSAA
- Splits: even strength, PP, SH
```

**game_stats** - Stats de matchs
```sql
- Par équipe et par match
- Shots, goals, penalties, faceoffs, hits
- Corsi, Fenwick, xG
```

## 🔧 NHLApiService

Service principal pour communiquer avec l'API NHL officielle.

### Méthodes Principales

```php
use App\Domains\DataIngestion\Services\NHLApiService;

$nhlApi = app(NHLApiService::class);

// Équipes
$teams = $nhlApi->getTeams();
$team = $nhlApi->getTeam('MTL');
$teamStats = $nhlApi->getTeamStats('MTL', '20242025');
$roster = $nhlApi->getTeamRoster('MTL', '20242025');
$schedule = $nhlApi->getTeamSchedule('MTL', '20242025');

// Matchs
$todayGames = $nhlApi->getTodayGames();
$games = $nhlApi->getGamesForDate('2024-11-17');
$game = $nhlApi->getGame(2024020123);
$boxscore = $nhlApi->getGameBoxscore(2024020123);
$playByPlay = $nhlApi->getGamePlayByPlay(2024020123);

// Joueurs
$playerStats = $nhlApi->getPlayerStats(8478402); // Connor McDavid

// Classement
$standings = $nhlApi->getStandings();
```

### Features

- **Retry logic** : 3 tentatives avec délai exponentiel
- **Caching** : Cache Redis avec TTL configurables
- **Logging** : Logs automatiques des erreurs
- **Timeout** : 30s par défaut

## 📦 Modèles Eloquent

### Team

```php
use App\Domains\DataIngestion\Models\Team;

// Trouver une équipe
$team = Team::findByNHLId(8); // Canadiens
$team = Team::where('abbreviation', 'MTL')->first();

// Relations
$team->conference;
$team->players;
$team->homeGames;
$team->awayGames;
$team->stats;

// Stats pour une saison
$stats = $team->statsForSeason('20242025');

// Scopes
Team::active()->get();
```

### Player

```php
use App\Domains\DataIngestion\Models\Player;

// Trouver un joueur
$player = Player::findByNHLId(8478402);

// Relations
$player->currentTeam;
$player->stats;
$player->goalieStats; // Si gardien

// Méthodes
$player->isGoalie(); // bool
$player->isForward(); // bool
$player->isDefense(); // bool
$player->age; // int

// Scopes
Player::active()->get();
Player::forTeam(1)->get();
```

### Game

```php
use App\Domains\DataIngestion\Models\Game;

// Trouver un match
$game = Game::findByNHLId(2024020123);

// Relations
$game->season;
$game->homeTeam;
$game->awayTeam;
$game->winningTeam;
$game->gameStats;
$game->homeTeamStats;
$game->awayTeamStats;

// Méthodes
$game->isFinished(); // bool
$game->isLive(); // bool
$game->isScheduled(); // bool
$game->totalScore; // int
$game->scoreDifference; // int

// Scopes
Game::today()->get();
Game::finished()->get();
Game::live()->get();
Game::scheduled()->get();
Game::forTeam(1)->get();
```

## 🎯 Enums

### GameStatus

```php
use App\Domains\DataIngestion\Enums\GameStatus;

GameStatus::SCHEDULED->label(); // "Programmé"
GameStatus::LIVE->label(); // "En cours"
GameStatus::FINAL->label(); // "Terminé"

GameStatus::FINAL->isFinished(); // true
GameStatus::LIVE->isLive(); // true
GameStatus::SCHEDULED->color(); // "gray"
```

### PlayerPosition

```php
use App\Domains\DataIngestion\Enums\PlayerPosition;

PlayerPosition::CENTER->label(); // "Centre"
PlayerPosition::GOALIE->label(); // "Gardien"

PlayerPosition::CENTER->category(); // "F" (Forward)
PlayerPosition::CENTER->isForward(); // true

PlayerPosition::fromNHLCode('C'); // PlayerPosition::CENTER
```

### GameType

```php
use App\Domains\DataIngestion\Enums\GameType;

GameType::REGULAR->label(); // "Saison régulière"
GameType::PLAYOFF->label(); // "Séries éliminatoires"

GameType::REGULAR->isRegularSeason(); // true
GameType::PLAYOFF->weight(); // 1.2 (compte plus)
GameType::PRESEASON->countsForStats(); // false
```

## 🖥️ Commandes Artisan

### Fetch Games

```bash
# Matchs d'aujourd'hui
php artisan nhl:fetch-games

# Matchs d'une date spécifique
php artisan nhl:fetch-games 2024-11-17

# Output:
# 🏒 Récupération des matchs NHL pour le 2024-11-17...
# ✅ 8 match(s) trouvé(s)
#
# ┌──────────┬─────────────┬─────────┬──────────┐
# │ ID       │ Match       │ Score   │ Statut   │
# ├──────────┼─────────────┼─────────┼──────────┤
# │ 2024020  │ BOS @ MTL   │ 3 - 2   │ final    │
# │ 2024021  │ TOR @ OTT   │ 4 - 1   │ final    │
# └──────────┴─────────────┴─────────┴──────────┘
```

## 🧪 Tests

```bash
# Tests du domaine
php artisan test --filter=DataIngestion

# Tests spécifiques
php artisan test tests/Feature/DataIngestion/NHLApiServiceTest.php
php artisan test tests/Unit/Models/TeamTest.php
```

## 🔄 Workflow Typique

```php
// 1. Récupérer les données de l'API
$nhlApi = app(NHLApiService::class);
$gamesData = $nhlApi->getTodayGames();

// 2. Créer/mettre à jour les matchs en base
foreach ($gamesData as $gameData) {
    Game::updateOrCreate(
        ['nhl_id' => $gameData['id']],
        [
            'home_team_id' => Team::findByNHLId($gameData['homeTeam']['id'])->id,
            'away_team_id' => Team::findByNHLId($gameData['awayTeam']['id'])->id,
            'game_date' => $gameData['startTimeUTC'],
            'status' => GameStatus::from($gameData['gameState'])->value,
            // ...
        ]
    );
}

// 3. Récupérer les stats détaillées
$game = Game::findByNHLId(2024020123);
$boxscore = $nhlApi->getGameBoxscore($game->nhl_id);

// 4. Sauvegarder les stats
GameStats::updateOrCreate([
    'game_id' => $game->id,
    'team_id' => $game->home_team_id,
], [
    'shots' => $boxscore['homeTeam']['sog'],
    'goals' => $boxscore['homeTeam']['score'],
    // ...
]);
```

## 📚 API NHL Endpoints Utilisés

- `GET /standings/now` - Classement actuel
- `GET /score/{date}` - Matchs du jour
- `GET /gamecenter/{gameId}/landing` - Détails match
- `GET /gamecenter/{gameId}/boxscore` - Boxscore
- `GET /gamecenter/{gameId}/play-by-play` - Play-by-play
- `GET /club-stats/{team}/{season}` - Stats d'équipe
- `GET /roster/{team}/{season}` - Roster
- `GET /player/{playerId}/landing` - Stats joueur
- `GET /club-schedule/{team}/{season}` - Calendrier

## 🚀 Prochaines Étapes

- [ ] Implémenter les Jobs asynchrones
- [ ] Créer les DTOs pour structurer les données
- [ ] Ajouter les Repositories
- [ ] Créer les autres commandes (fetch-teams, fetch-players, etc.)
- [ ] Ajouter les tests unitaires et features
- [ ] Implémenter la validation des données
- [ ] Ajouter le transformer de données

---

**Domaine créé par** : Laurent - IT Director @ CARA ÉNERGIE
**Dernière mise à jour** : Novembre 2025
