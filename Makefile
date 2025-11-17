.PHONY: help install test test-unit test-feature coverage analyse phpstan pint cs-fix cs-check audit security format quality ci setup sail-up sail-down sail-fresh

# Couleurs pour l'output
GREEN  := $(shell tput -Txterm setaf 2)
YELLOW := $(shell tput -Txterm setaf 3)
WHITE  := $(shell tput -Txterm setaf 7)
CYAN   := $(shell tput -Txterm setaf 6)
RESET  := $(shell tput -Txterm sgr0)

## Afficher cette aide
help:
	@echo ''
	@echo '${CYAN}Utilisation:${RESET}'
	@echo '  ${YELLOW}make${RESET} ${GREEN}<target>${RESET}'
	@echo ''
	@echo '${CYAN}Targets disponibles:${RESET}'
	@awk 'BEGIN {FS = ":.*?## "} { \
		if (/^[a-zA-Z_-]+:.*?##.*$$/) {printf "  ${YELLOW}%-20s${GREEN}%s${RESET}\n", $$1, $$2} \
		else if (/^## .*$$/) {printf "  ${CYAN}%s${RESET}\n", substr($$1,4)} \
		}' $(MAKEFILE_LIST)

## 📦 Installation et Configuration
install: ## Installer toutes les dépendances (Composer + NPM)
	@echo "${CYAN}Installation des dépendances Composer...${RESET}"
	composer install --no-interaction
	@echo "${CYAN}Installation des dépendances NPM...${RESET}"
	npm install
	@echo "${GREEN}✓ Installation terminée${RESET}"

setup: install ## Configuration complète du projet
	@echo "${CYAN}Copie du fichier .env...${RESET}"
	cp -n .env.example .env || true
	@echo "${CYAN}Génération de la clé d'application...${RESET}"
	php artisan key:generate
	@echo "${CYAN}Permissions des dossiers...${RESET}"
	chmod -R 777 storage bootstrap/cache
	@echo "${GREEN}✓ Configuration terminée${RESET}"
	@echo "${YELLOW}N'oubliez pas de configurer votre .env !${RESET}"

## 🐳 Docker (Laravel Sail)
sail-up: ## Démarrer les conteneurs Docker
	./vendor/bin/sail up -d

sail-down: ## Arrêter les conteneurs Docker
	./vendor/bin/sail down

sail-fresh: ## Recréer complètement la base de données
	./vendor/bin/sail artisan migrate:fresh --seed

## 🧪 Tests
test: ## Exécuter tous les tests
	@echo "${CYAN}Exécution de tous les tests...${RESET}"
	./vendor/bin/phpunit
	@echo "${GREEN}✓ Tests terminés${RESET}"

test-unit: ## Exécuter uniquement les tests unitaires
	@echo "${CYAN}Exécution des tests unitaires...${RESET}"
	./vendor/bin/phpunit --testsuite=Unit
	@echo "${GREEN}✓ Tests unitaires terminés${RESET}"

test-feature: ## Exécuter uniquement les tests fonctionnels
	@echo "${CYAN}Exécution des tests fonctionnels...${RESET}"
	./vendor/bin/phpunit --testsuite=Feature
	@echo "${GREEN}✓ Tests fonctionnels terminés${RESET}"

coverage: ## Générer le rapport de couverture de code
	@echo "${CYAN}Génération du rapport de couverture...${RESET}"
	./vendor/bin/phpunit --coverage-html coverage-html --coverage-text
	@echo "${GREEN}✓ Rapport généré dans coverage-html/${RESET}"

## 🔍 Analyse Statique
analyse: phpstan ## Alias pour phpstan

phpstan: ## Exécuter PHPStan (analyse statique)
	@echo "${CYAN}Analyse statique avec PHPStan...${RESET}"
	./vendor/bin/phpstan analyse --memory-limit=2G
	@echo "${GREEN}✓ Analyse terminée${RESET}"

phpstan-level: ## Exécuter PHPStan avec niveau spécifique (usage: make phpstan-level LEVEL=6)
	@echo "${CYAN}Analyse statique niveau ${LEVEL}...${RESET}"
	./vendor/bin/phpstan analyse --level=${LEVEL} --memory-limit=2G

## 🎨 Formatage de Code
format: pint cs-fix ## Formater tout le code (Pint + CS Fixer)

pint: ## Formater le code avec Laravel Pint
	@echo "${CYAN}Formatage avec Laravel Pint...${RESET}"
	./vendor/bin/pint
	@echo "${GREEN}✓ Code formaté${RESET}"

pint-test: ## Vérifier le formatage avec Pint (sans modifier)
	@echo "${CYAN}Vérification du formatage...${RESET}"
	./vendor/bin/pint --test

cs-fix: ## Formater le code avec PHP CS Fixer
	@echo "${CYAN}Formatage avec PHP CS Fixer...${RESET}"
	./vendor/bin/php-cs-fixer fix
	@echo "${GREEN}✓ Code formaté${RESET}"

cs-check: ## Vérifier le formatage avec PHP CS Fixer (sans modifier)
	@echo "${CYAN}Vérification du formatage...${RESET}"
	./vendor/bin/php-cs-fixer fix --dry-run --diff

## 🔒 Sécurité
security: audit ## Alias pour audit

audit: ## Vérifier les vulnérabilités de sécurité
	@echo "${CYAN}Audit de sécurité Composer...${RESET}"
	composer audit
	@echo "${CYAN}Audit de sécurité NPM...${RESET}"
	npm audit --audit-level=moderate || true
	@echo "${GREEN}✓ Audit terminé${RESET}"

## ✨ Qualité Globale
quality: cs-check phpstan test ## Vérifier la qualité complète du code
	@echo "${GREEN}✓ Vérifications de qualité terminées${RESET}"

## 🚀 CI/CD (Simule les checks GitHub Actions)
ci: ## Exécuter tous les checks CI localement
	@echo "${CYAN}════════════════════════════════════════${RESET}"
	@echo "${CYAN}  Simulation des checks CI/CD${RESET}"
	@echo "${CYAN}════════════════════════════════════════${RESET}"
	@echo ""
	@echo "${YELLOW}[1/5]${RESET} Formatage du code..."
	@make pint-test
	@echo ""
	@echo "${YELLOW}[2/5]${RESET} Analyse statique..."
	@make phpstan
	@echo ""
	@echo "${YELLOW}[3/5]${RESET} Tests unitaires..."
	@make test-unit
	@echo ""
	@echo "${YELLOW}[4/5]${RESET} Tests fonctionnels..."
	@make test-feature
	@echo ""
	@echo "${YELLOW}[5/5]${RESET} Audit de sécurité..."
	@make audit
	@echo ""
	@echo "${GREEN}════════════════════════════════════════${RESET}"
	@echo "${GREEN}  ✓ Tous les checks sont passés !${RESET}"
	@echo "${GREEN}════════════════════════════════════════${RESET}"

## 🧹 Nettoyage
clean: ## Nettoyer les fichiers de cache
	@echo "${CYAN}Nettoyage des caches...${RESET}"
	rm -rf .php-cs-fixer.cache
	rm -rf .phpstan.cache
	rm -rf coverage-html
	rm -f coverage-*.xml
	./vendor/bin/phpstan clear-result-cache
	php artisan cache:clear
	php artisan config:clear
	php artisan route:clear
	php artisan view:clear
	@echo "${GREEN}✓ Nettoyage terminé${RESET}"

## 📊 Statistiques
stats: ## Afficher les statistiques du projet
	@echo "${CYAN}Statistiques du projet:${RESET}"
	@echo ""
	@echo "${YELLOW}Lignes de code PHP:${RESET}"
	@find app -name "*.php" | xargs wc -l | tail -1
	@echo ""
	@echo "${YELLOW}Lignes de code de tests:${RESET}"
	@find tests -name "*.php" | xargs wc -l | tail -1
	@echo ""
	@echo "${YELLOW}Nombre de migrations:${RESET}"
	@ls -1 database/migrations/*.php | wc -l
	@echo ""
	@echo "${YELLOW}Nombre de modèles:${RESET}"
	@find app/Domains -name "*.php" -path "*/Models/*" | wc -l

.DEFAULT_GOAL := help
