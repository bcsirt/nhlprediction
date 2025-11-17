# 🚀 CI/CD Workflows - NHL Prediction Platform

Cette documentation décrit les workflows d'Intégration Continue et de Déploiement Continu (CI/CD) configurés pour le projet.

## 📋 Vue d'ensemble des Workflows

### 1. Tests (`tests.yml`)

**Déclencheurs:**
- Push sur `main`, `develop`, branches `claude/**`
- Pull Requests vers `main`, `develop`

**Fonctionnalités:**
- ✅ Exécution sur PHP 8.4 et 8.3
- ✅ Tests avec PostgreSQL 15 et Redis 7
- ✅ Suite de tests unitaires et fonctionnels
- ✅ Génération de code coverage (PCOV)
- ✅ Upload des rapports vers Codecov
- ✅ Artefacts de coverage conservés 7 jours

**Commandes executées:**
```bash
./vendor/bin/phpunit --testsuite=Unit --coverage-clover coverage-unit.xml
./vendor/bin/phpunit --testsuite=Feature --coverage-clover coverage-feature.xml
```

---

### 2. Analyse Statique (`static-analysis.yml`)

**Déclencheurs:**
- Push sur `main`, `develop`, branches `claude/**`
- Pull Requests vers `main`, `develop`

**Fonctionnalités:**
- 🔍 PHPStan niveau 5 et 6
- 🔍 Larastan (PHPStan pour Laravel)
- 🔍 Analyse de types et détection d'erreurs potentielles

**Commandes exécutées:**
```bash
./vendor/bin/phpstan analyse --level=5 --memory-limit=2G
./vendor/bin/phpstan analyse --level=6 --memory-limit=2G
./vendor/bin/phpstan analyse --configuration=phpstan.neon --memory-limit=2G
```

---

### 3. Style de Code (`code-style.yml`)

**Déclencheurs:**
- Push sur `main`, `develop`, branches `claude/**`
- Pull Requests vers `main`, `develop`

**Fonctionnalités:**
- 🎨 PHP CS Fixer pour formatage PSR-12 + règles custom
- 🎨 Laravel Pint pour style Laravel
- 🤖 Auto-commit des corrections sur push (optionnel)

**Commandes exécutées:**
```bash
./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose
./vendor/bin/pint --test -v
```

---

### 4. Sécurité (`security.yml`)

**Déclencheurs:**
- Push sur `main`, `develop`
- Pull Requests vers `main`, `develop`
- Cron: Tous les lundis à minuit

**Fonctionnalités:**
- 🔒 Audit de sécurité Composer
- 🔒 Revue des dépendances GitHub
- 🔒 Audit NPM
- 🔒 Alertes sur vulnérabilités modérées et hautes

**Commandes exécutées:**
```bash
composer audit
npm audit --audit-level=moderate
```

---

## 🎯 Quality Gates

### Niveaux de Qualité Requis

| Métrique | Objectif | Critique |
|----------|----------|----------|
| Coverage Unitaire | ≥ 70% | ≥ 50% |
| Coverage Fonctionnel | ≥ 60% | ≥ 40% |
| PHPStan Level | 5 | 4 |
| Vulnérabilités | 0 high/critical | 0 critical |

### Critères de Passage

Pour qu'une PR soit mergeable:
1. ✅ Tous les tests doivent passer (PHP 8.4 ET 8.3)
2. ✅ PHPStan niveau 5 doit passer sans erreur
3. ✅ Aucune vulnérabilité critique détectée
4. ✅ Code formaté selon PSR-12 (Pint/CS Fixer)

---

## 🔧 Configuration Locale

### Installer les dépendances

```bash
composer install
npm install
```

### Exécuter les outils en local

**Tests:**
```bash
# Tous les tests
./vendor/bin/phpunit

# Avec coverage
./vendor/bin/phpunit --coverage-html coverage-html

# Suite spécifique
./vendor/bin/phpunit --testsuite=Unit
```

**PHPStan:**
```bash
# Niveau 5
./vendor/bin/phpstan analyse --level=5

# Avec configuration
./vendor/bin/phpstan analyse
```

**PHP CS Fixer:**
```bash
# Vérifier (dry-run)
./vendor/bin/php-cs-fixer fix --dry-run --diff

# Appliquer les corrections
./vendor/bin/php-cs-fixer fix
```

**Laravel Pint:**
```bash
# Vérifier
./vendor/bin/pint --test

# Appliquer
./vendor/bin/pint
```

**Audit de sécurité:**
```bash
# Composer
composer audit

# NPM
npm audit
```

---

## 📊 Badges de Statut

Ajoutez ces badges dans votre `README.md`:

```markdown
[![Tests](https://github.com/bcsirt/nhlprediction/actions/workflows/tests.yml/badge.svg)](https://github.com/bcsirt/nhlprediction/actions/workflows/tests.yml)
[![Static Analysis](https://github.com/bcsirt/nhlprediction/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/bcsirt/nhlprediction/actions/workflows/static-analysis.yml)
[![Code Style](https://github.com/bcsirt/nhlprediction/actions/workflows/code-style.yml/badge.svg)](https://github.com/bcsirt/nhlprediction/actions/workflows/code-style.yml)
[![Security](https://github.com/bcsirt/nhlprediction/actions/workflows/security.yml/badge.svg)](https://github.com/bcsirt/nhlprediction/actions/workflows/security.yml)
[![codecov](https://codecov.io/gh/bcsirt/nhlprediction/branch/main/graph/badge.svg)](https://codecov.io/gh/bcsirt/nhlprediction)
```

---

## 🔐 Secrets Requis

Configurez ces secrets dans GitHub Settings > Secrets:

| Secret | Description | Requis pour |
|--------|-------------|-------------|
| `CODECOV_TOKEN` | Token Codecov.io | Upload coverage |
| `GITHUB_TOKEN` | Token GitHub (auto) | Dependency Review |

---

## 📝 Fichiers de Configuration

### PHPStan (`phpstan.neon`)
Configuration de l'analyse statique:
- Niveau 5 par défaut
- Larastan activé
- Exclusions: migrations, middlewares générés
- Règles personnalisées Laravel

### PHP CS Fixer (`.php-cs-fixer.php`)
Règles de formatage:
- PSR-12 baseline
- PHP 8.4 Migration rules
- Ordre des éléments de classe
- Imports auto-organisés
- Declare strict types

### PHPUnit (`phpunit.xml`)
Configuration des tests:
- SQLite en mémoire
- Variables ENV spécifiques tests
- Suites: Unit et Feature
- Coverage: app/

---

## 🚀 Workflow de Développement Recommandé

### Avant de commiter

```bash
# 1. Formater le code
./vendor/bin/pint

# 2. Vérifier avec PHPStan
./vendor/bin/phpstan analyse --level=5

# 3. Exécuter les tests
./vendor/bin/phpunit

# 4. Audit de sécurité
composer audit
```

### Créer une Pull Request

1. Créer une branche feature: `git checkout -b feature/ma-fonctionnalite`
2. Développer avec tests
3. Formater et analyser
4. Commit et push
5. Créer la PR sur GitHub
6. Attendre que tous les checks passent ✅
7. Demander une revue de code
8. Merger après approbation

---

## 📈 Métriques et Monitoring

### Codecov.io
- Coverage global du projet
- Coverage par fichier
- Diff coverage sur PRs
- Graphiques de tendance

### GitHub Actions
- Temps d'exécution des workflows
- Taux de succès/échec
- Historique des builds
- Notifications par email

---

## 🛠️ Maintenance

### Mettre à jour PHPStan

Pour augmenter progressivement le niveau:

1. Tester localement:
   ```bash
   ./vendor/bin/phpstan analyse --level=6
   ```

2. Corriger les erreurs

3. Mettre à jour `phpstan.neon`:
   ```yaml
   parameters:
       level: 6
   ```

4. Mettre à jour le workflow si nécessaire

### Ajouter de nouvelles règles CS Fixer

Éditer `.php-cs-fixer.php`:

```php
->setRules([
    // Ajouter vos règles ici
    'nouvelle_regle' => true,
])
```

Tester:
```bash
./vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 🆘 Troubleshooting

### Les tests échouent en CI mais passent en local

- Vérifier les versions PHP (local vs CI)
- Vérifier les variables d'environnement
- Vérifier les dépendances (composer.lock)

### PHPStan trouve des erreurs en CI mais pas en local

- Exécuter avec `--memory-limit=2G`
- Vider le cache: `./vendor/bin/phpstan clear-result-cache`
- Vérifier la version de PHPStan

### Code style échoue

- Exécuter `./vendor/bin/pint` en local
- Commit les changements
- Push à nouveau

---

## 📚 Ressources

- [PHPStan Documentation](https://phpstan.org/user-guide/getting-started)
- [Larastan Documentation](https://github.com/larastan/larastan)
- [PHP CS Fixer Documentation](https://cs.symfony.com/)
- [Laravel Pint Documentation](https://laravel.com/docs/pint)
- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [Codecov Documentation](https://docs.codecov.com/)
