# Executables (local)
DOCKER_COMP = docker compose

# The dev image runs as a user with the host uid/gid (see Dockerfile, frankenphp_dev stage)
BUILD_ARGS = UID=$(shell id -u) GID=$(shell id -g)

# Docker containers
PHP_CONT = $(DOCKER_COMP) exec php

# Executables
PHP      = $(PHP_CONT) php
COMPOSER = $(PHP_CONT) composer
CONSOLE  = $(PHP) bin/console

# Misc
.DEFAULT_GOAL = help
.PHONY        : help build up down logs sh composer console cc css db-reset test qa fix

## —— Docker 🐳 ————————————————————————————————————————————————————————————————
help: ## Show this help
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'

build: ## Build fresh Docker images
	@$(BUILD_ARGS) $(DOCKER_COMP) build --pull --no-cache

up: ## Start the containers and wait until they are healthy
	@$(DOCKER_COMP) up --wait

down: ## Stop the containers
	@$(DOCKER_COMP) down --remove-orphans

logs: ## Show live logs
	@$(DOCKER_COMP) logs --tail=0 --follow

sh: ## Open a bash shell in the php container
	@$(PHP_CONT) bash

## —— Composer 🧙 ——————————————————————————————————————————————————————————————
composer: ## Run composer, example: make composer c='require symfony/lock'
	@$(eval c ?=)
	@$(COMPOSER) $(c)

## —— Symfony 🎵 ———————————————————————————————————————————————————————————————
console: ## Run bin/console, example: make console c='debug:router'
	@$(eval c ?=)
	@$(CONSOLE) $(c)

cc: c=cache:clear ## Clear the cache
cc: console

css: ## Rebuild Tailwind CSS on every template change
	@$(CONSOLE) tailwind:build --watch

db-reset: ## Recreate the dev database: drop, create, migrate, load fixtures
	@$(CONSOLE) doctrine:database:drop --force --if-exists
	@$(CONSOLE) doctrine:database:create
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration
	@$(CONSOLE) foundry:load-fixtures --append --no-interaction

## —— Quality ✅ ———————————————————————————————————————————————————————————————
test: ## Prepare the test database and run PHPUnit, example: make test c='--testsuite unit'
	@$(eval c ?=)
	@$(CONSOLE) doctrine:database:create --env=test --if-not-exists
	@$(CONSOLE) doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration
	@# Pages link styles/app.css: without the Tailwind output AssetMapper fails on @import "tailwindcss"
	@$(CONSOLE) tailwind:build
	@$(PHP_CONT) bin/phpunit $(c)

qa: ## Run all static checks (no changes to the code)
	@$(CONSOLE) cache:warmup
	@$(PHP_CONT) vendor/bin/phpstan analyse --no-progress
	@$(PHP_CONT) vendor/bin/php-cs-fixer fix --dry-run --diff
	@$(PHP_CONT) vendor/bin/rector process --dry-run
	@$(PHP_CONT) vendor/bin/deptrac analyse --no-progress
	@$(CONSOLE) lint:container
	@$(CONSOLE) lint:twig templates/
	@$(CONSOLE) lint:yaml config/ --parse-tags
	@$(CONSOLE) doctrine:schema:validate

fix: ## Apply Rector, then PHP-CS-Fixer (Rector output is not code-style aware)
	@$(PHP_CONT) vendor/bin/rector process
	@$(PHP_CONT) vendor/bin/php-cs-fixer fix
