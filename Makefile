.PHONY: help shell install start up down build rebuild ps logs \
         db-migrate db-rollback db-seed db-console db-fresh db-reset \
         test test-coverage coverage-html \
         lint phpstan cs-fix cs-check format check ci \
         clean metrics docker-stats stats \
         env-create \
         open-api xdebug-on xdebug-off slice

# Variables (local)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

# Executables (local)
DC = UID=$(USER_ID) GID=$(GROUP_ID) docker compose

# Docker container name
DC_APP = frankenphp

# Colors
RED    := $(shell tput setaf 1)
GREEN  := $(shell tput setaf 2)
YELLOW  := $(shell tput setaf 3)
BLUE    := $(shell tput setaf 4)
CYAN    := $(shell tput setaf 6)
RESET   := $(shell tput sgr0)

##—————— Pragmatic Franken ——————
help: ## Show this help message
	@grep -E '(^[a-zA-Z0-9_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | \
	awk -v c1="$(YELLOW)" -v c2="$(CYAN)" -v c3="$(BLUE)" -v rst="$(RESET)" \
	'BEGIN {FS = ":.*?## "}; \
	{ \
		if ($$1 ~ /^##/) { \
			printf "\n%s%s%s\n", c2, substr($$1, 3), rst \
		} else { \
			printf "  %s%-19s%s %s %s\n", c1, $$1, c3, $$2, rst \
		} \
	}'

env-create:
	@if [ ! -f .env.dist ]; then echo "$(RED)Error: .env.dist not found!$(RESET)"; exit 1; fi
	cp -i .env.dist .env
	@echo "" >> .env
	@echo "# Auto-generated IDs" >> .env
	@echo "UID=$(USER_ID)" >> .env
	@echo "GID=$(GROUP_ID)" >> .env
	@echo "$(GREEN).env created with UID:$(USER_ID) and GID:$(GROUP_ID)$(RESET)"

setup: env-create up install db-migrate ## 🚀 Full setup: Container, Dependencies, Database
	@echo ""
	@echo "🐘 $(BLUE)Pragmatic Franken is igniting...$(RESET)"
	@echo ""
	@echo "📦 Installing dependencies..."
	@echo ""
	@echo "💾 Running migrations..."
	@echo ""
	@echo "🔥 $(GREEN)Done! Application live at https://localhost$(RESET)"

install: setup ## 🚀 Full setup: Container, Dependencies, Database

start: rebuild up

##—————— 🐳 Docker ——————
build: ## Build Docker images
	@echo "$(RED)Building Docker images...$(RESET)"
	$(DC) build --pull

rebuild: ## Rebuild Docker images (no cache)
	@echo "$(RED)Rebuilding Docker images...$(RESET)"
	$(DC) build --pull --no-cache

ps: ## List running containers
	@echo "$(YELLOW)Listing containers...$(RESET)"
	$(DC) ps

up: ## Start containers in detached mode
	@echo "$(YELLOW)Starting containers...$(RESET)"
	$(DC) up --detach

down: ## Stop and remove containers
	@echo "$(RED)Stopping containers...$(RESET)"
	$(DC) down --remove-orphans

logs: ## Follow container logs
	@echo "$(YELLOW)Showing and following logs...$(RESET)"
	$(DC) logs --tail=20 --follow

composer-chown: ## Fix composer cache permissions
	@echo "$(YELLOW)Fixing composer cache permissions...$(RESET)"
	$(DC) exec $(DC_APP) chown -R $(USER_ID):$(GROUP_ID) /var/www/.composer 2>/dev/null || \
	$(DC) exec $(DC_APP) bash -c 'chown -R 1000:1000 /var/www/.composer' || true
	@echo "$(GREEN)Composer cache permissions fixed!$(RESET)"

##—————— FrankenPHP ——————
shell: ## Connect to FrankenPHP container shell
	@if [ -z "$$(docker ps -q -f name=$(DC_APP))" ]; then \
		echo "$(YELLOW)Container $(DC_APP) not running. Starting...$(RESET)"; \
		$(DC) up -d $(DC_APP); \
		echo "$(GREEN)Container started.$(RESET)"; \
	fi
	$(DC) exec $(DC_APP) bash

##—————— Database ——————
db-migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(RESET)"
	$(DC) exec $(DC_APP) bin/console doctrine:migrations:migrate --no-interaction

db-rollback: ## Rollback last migration
	@echo "$(BLUE)Rolling back last migration...$(RESET)"
	$(DC) exec $(DC_APP) bin/console doctrine:migrations:migrate prev --no-interaction

db-seed: ## Load fixtures
	@echo "$(BLUE)Seeding database...$(RESET)"
	$(DC) exec $(DC_APP) bin/console doctrine:fixtures:load --no-interaction

db-console: ## Connect to PostgreSQL console
	@echo "$(CYAN)Connecting to PostgreSQL...$(RESET)"
	$(DC) exec postgres psql -U postgres -d app

db-fresh: db-rollback db-migrate db-seed ## Full reset: rollback, migrate, seed
	@echo "$(GREEN)Database freshened!$(RESET)"

db-reset: down ## Destroy and rebuild database
	@echo "$(RED)Resetting database volumes...$(RESET)"
	$(DC) down -v
	$(DC) up -d $(DC_APP)
	$(MAKE) db-migrate db-seed

##—————— Tests ——————
test: ## Run PHPUnit tests
	@echo "$(GREEN)Running PHPUnit tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --fail-fast

test-coverage: ## Run tests with coverage report
	@echo "$(GREEN)Running tests with coverage...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-text

coverage-html: ## Generate HTML coverage report
	@echo "$(GREEN)Generating HTML coverage report...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-html=coverage

##—————— Code Quality ——————

# Quick commands for developers
lint: ## 🚀 Auto-fix code style (Laravel Pint)
	@echo "$(GREEN)Fixing code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint

analyze: ## 🔍 Run PHPStan static analysis (Level 9)
	@echo "$(YELLOW)Running PHPStan (Level 9)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --memory-limit=1G

check: lint analyze ## ✅ Run all checks before commit
	@echo "$(GREEN)✨ All checks passed! Code is bulletproof.$(RESET)"

# Advanced commands
lint-check: ## Check code style without fixing
	@echo "$(YELLOW)Checking code style...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint --test

phpstan: ## Run PHPStan (level 5 - medium strictness)
	@echo "$(YELLOW)Running PHPStan (level 5)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --level=5

phpstan-level-8: ## Run PHPStan (level 8 - maximum strictness)
	@echo "$(YELLOW)Running PHPStan (level 8)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --level=8

phpstan-level-9: ## Run PHPStan (level 9 - bleeding edge)
	@echo "$(YELLOW)Running PHPStan (Level 9 - maximum)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --memory-limit=1G

phpstan-baseline: ## Generate PHPStan baseline
	@echo "$(YELLOW)Generating PHPStan baseline...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --level=9 --generate-baseline

cs-fix: ## Fix code style with PHP-CS-Fixer (legacy)
	@echo "$(YELLOW)Fixing code style with PHP-CS-Fixer...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/php-cs-fixer fix

cs-check: ## Check code style with PHP-CS-Fixer (legacy)
	@echo "$(YELLOW)Checking code style...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/php-cs-fixer check --dry-run --diff

pint: ## Fix code style with Laravel Pint (modern PSR-12)
	@echo "$(GREEN)Fixing code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint

pint-check: ## Check code style with Laravel Pint
	@echo "$(YELLOW)Checking code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint --test

format: lint ## Auto-format code (alias)
	@echo "$(GREEN)Code formatted!$(RESET)"

ci: lint-check phpstan-level-9 test-coverage ## Simulate CI pipeline
	@echo "$(GREEN)CI pipeline complete!$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint --test

format: cs-fix ## Auto-format code (alias)
	@echo "$(GREEN)Code formatted!$(RESET)"

check: phpstan-level-8 cs-check ## Run all checks before commit
	@echo "$(GREEN)All checks passed!$(RESET)"

ci: cs-check phpstan-level-8 test-coverage ## Simulate CI pipeline
	@echo "$(GREEN)CI pipeline complete!$(RESET)"

##—————— Static Analysis ——————
sa: phpstan ## Static analysis (alias)
	@echo "$(GREEN)Static analysis complete!$(RESET)"

sa-full: phpstan-level-8 cs-check ## Full static analysis
	@echo "$(GREEN)Full static analysis complete!$(RESET)"

##—————— Maintenance ——————
clean: ## Clean cache and temporary files
	@echo "Cleaning cache and temporary files..."
	rm -rf build/ .phpunit.result.cache coverage/ var/cache/*
	@echo "$(GREEN)Clean complete!$(RESET)"

metrics: ## Show project metrics
	@echo "$(GREEN)Project metrics:$(RESET)"
	@echo "  PHP files: $$(find src -name '*.php' 2>/dev/null | wc -l)"
	@echo "  Test files: $$(find tests -name '*.php' 2>/dev/null | wc -l)"
	@echo "  Docs: $$(find docs -name '*.md' 2>/dev/null | wc -l)"

docker-stats: ## Show container resource usage
	@echo "$(GREEN)Container stats:$(RESET)"
	@docker stats --no-color $$(docker compose ps -q)

##—————— Dev Utilities ——————
open-api: ## Generate OpenAPI spec
	@echo "$(GREEN)Generating OpenAPI documentation...$(RESET)"
	$(DC) exec $(DC_APP) bin/console nelmio:api-doc:dump > docs/openapi.yaml
	@echo "$(GREEN)OpenAPI spec written to docs/openapi.yaml$(RESET)"

xdebug-on: ## Enable Xdebug
	@echo "$(YELLOW)Enabling Xdebug...$(RESET)"
	$(DC) exec $(DC_APP) bash -c 'echo "xdebug.mode=debug" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'
	@echo "$(GREEN)Xdebug enabled. Restart container to apply.$(RESET)"

xdebug-off: ## Disable Xdebug
	@echo "$(YELLOW)Disabling Xdebug...$(RESET)"
	@$(DC) exec $(DC_APP) rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	@echo "$(GREEN)Xdebug disabled. Restart container to apply.$(RESET)"

##—————— 📊 Metrics ——————
stats: ## 📊 Check FrankenPHP metrics
	@echo "$(GREEN)Fetching FrankenPHP metrics...$(RESET)"
	@curl -s http://localhost:2019/metrics | head -20

##—————— 🤖 AI & Development ——————
slice: ## 🚀 Generate a new feature slice
	@echo "$(GREEN)Creating slice...$(RESET)"
	@chmod +x scripts/create-slice.sh
	@./scripts/create-slice.sh $(module) $(feature)
