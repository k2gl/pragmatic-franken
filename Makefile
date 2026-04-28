.PHONY: help shell e d install env-create \
        up run down build rebuild ps logs composer-chown \
        db-migrate db-rollback db-seed db-console db-fresh db-reset \
        test test-unit test-integration test-e2e test-coverage coverage-html \
        lint lint-check analyze check ci smoke docs-check \
        clean metrics docker-stats stats \
        open-api xdebug-on xdebug-off slice adr deploy

# Variables (local)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

# Executables (local)
DC = UID=$(USER_ID) GID=$(GROUP_ID) docker compose

# Docker container name (matches docker-compose service "app")
DC_APP = app

# Postgres credentials (must match docker-compose.yml)
DB_USER = user
DB_NAME = app_db

# Colors
RED    := $(shell tput setaf 1)
GREEN  := $(shell tput setaf 2)
YELLOW := $(shell tput setaf 3)
BLUE   := $(shell tput setaf 4)
CYAN   := $(shell tput setaf 6)
RESET  := $(shell tput sgr0)

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

env-create: ## Create .env from .env.dist
	@if [ ! -f .env.dist ]; then echo "$(RED)Error: .env.dist not found!$(RESET)"; exit 1; fi
	cp -n .env.dist .env
	@sed -i "s|^UID=.*|UID=$(USER_ID)|g" .env
	@sed -i "s|^GID=.*|GID=$(GROUP_ID)|g" .env
	@echo "$(GREEN).env created with UID:$(USER_ID) and GID:$(GROUP_ID)$(RESET)"

install: env-create build up db-migrate ## 🚀 Full setup: containers, dependencies, database
	@echo ""
	@echo "🐘 $(BLUE)Pragmatic Franken is igniting...$(RESET)"
	@echo "🔥 $(GREEN)Done! Open https://pragmatic-franken.localhost:$${HTTPS_PORT:-4750} (set in .env).$(RESET)"

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

run: ## Start containers with logs (foreground)
	@echo "$(YELLOW)Starting containers with logs...$(RESET)"
	$(DC) up

down: ## Stop and remove containers
	@echo "$(RED)Stopping containers...$(RESET)"
	$(DC) down --remove-orphans

d: down

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
	@if [ -z "$$($(DC) ps -q $(DC_APP))" ]; then \
		echo "$(YELLOW)Container $(DC_APP) not running. Starting...$(RESET)"; \
		$(DC) up -d $(DC_APP); \
	fi
	$(DC) exec $(DC_APP) bash

e: shell

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
	@echo "$(CYAN)Connecting to PostgreSQL ($(DB_USER)@$(DB_NAME))...$(RESET)"
	$(DC) exec db psql -U $(DB_USER) -d $(DB_NAME)

db-fresh: db-rollback db-migrate db-seed ## Full reset: rollback, migrate, seed
	@echo "$(GREEN)Database freshened!$(RESET)"

db-reset: down ## Destroy and rebuild database
	@echo "$(RED)Resetting database volumes...$(RESET)"
	$(DC) down -v
	$(DC) up -d $(DC_APP)
	$(MAKE) db-migrate db-seed

##—————— ✅ Tests ——————
test: ## Run all PHPUnit tests (fail-fast)
	@echo "$(GREEN)Running all tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --fail-fast

test-unit: ## Run unit tests only (#[Group('unit')])
	@echo "$(GREEN)Running unit tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=unit --fail-fast

test-integration: ## Run integration tests only (#[Group('integration')])
	@echo "$(GREEN)Running integration tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=integration --fail-fast

test-e2e: ## Run API/E2E tests only (#[Group('e2e')])
	@echo "$(GREEN)Running E2E tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=e2e --fail-fast

test-coverage: ## Run tests with text coverage report
	@echo "$(GREEN)Running tests with coverage...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-text

coverage-html: ## Generate HTML coverage report
	@echo "$(GREEN)Generating HTML coverage report...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-html=coverage

##—————— 🛡 Code Quality ——————
lint: ## 🚀 Auto-fix code style (Laravel Pint)
	@echo "$(GREEN)Fixing code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint

lint-check: ## Check code style without fixing
	@echo "$(YELLOW)Checking code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint --test

analyze: ## 🔍 Run PHPStan static analysis (Level 9)
	@echo "$(YELLOW)Running PHPStan (Level 9)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --memory-limit=1G

check: lint analyze ## ✅ Run all checks before commit (lint + analyze)
	@echo "$(GREEN)✨ All checks passed!$(RESET)"

ci: lint-check analyze test ## Simulate CI pipeline (no auto-fix)
	@echo "$(GREEN)CI pipeline complete!$(RESET)"

smoke: ## End-to-end smoke check (console boots, /healthz responds)
	@echo "$(YELLOW)Running smoke check...$(RESET)"
	$(DC) exec $(DC_APP) bin/console list >/dev/null
	@curl -fsS http://localhost:$${HTTP_PORT:-8750}/healthz | tee /dev/stderr | grep -q '"ok":true'
	@echo "$(GREEN)Smoke check passed!$(RESET)"

docs-check: ## Lint ADR front-matter and AGENTS.md token budget
	@./dev/check-docs.sh

##—————— 🧹 Maintenance ——————
clean: ## Clean cache and temporary files
	@echo "$(YELLOW)Cleaning cache and temporary files...$(RESET)"
	rm -rf build/ .phpunit.result.cache coverage/ var/cache/*
	@echo "$(GREEN)Clean complete!$(RESET)"

metrics: ## Show project metrics
	@echo "$(GREEN)Project metrics:$(RESET)"
	@echo "  PHP files: $$(find src -name '*.php' 2>/dev/null | wc -l)"
	@echo "  Test files: $$(find tests -name '*.php' 2>/dev/null | wc -l)"
	@echo "  Docs: $$(find docs -name '*.md' 2>/dev/null | wc -l)"

docker-stats: ## Show container resource usage
	@echo "$(GREEN)Container stats:$(RESET)"
	@docker stats --no-stream $$($(DC) ps -q)

stats: ## 📊 Check FrankenPHP metrics
	@echo "$(GREEN)Fetching FrankenPHP metrics (port 2019)...$(RESET)"
	@curl -s http://localhost:2019/metrics | head -20

##—————— 🛠 Dev Utilities ——————
open-api: ## Generate OpenAPI spec
	@echo "$(GREEN)Generating OpenAPI documentation...$(RESET)"
	$(DC) exec $(DC_APP) bin/console nelmio:api-doc:dump > docs/openapi.yaml
	@echo "$(GREEN)OpenAPI spec written to docs/openapi.yaml$(RESET)"

xdebug-on: ## Enable Xdebug at runtime
	@$(DC) exec $(DC_APP) bash -c 'echo "xdebug.mode=debug" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'
	@echo "$(GREEN)Xdebug enabled. Restart container to apply.$(RESET)"

xdebug-off: ## Disable Xdebug at runtime
	@$(DC) exec $(DC_APP) rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	@echo "$(GREEN)Xdebug disabled. Restart container to apply.$(RESET)"

##—————— 🤖 Scaffolding & Docs ——————
slice: ## 🚀 Generate a new feature slice (module=Foo feature=Bar)
	@echo "$(GREEN)Creating slice...$(RESET)"
	@./dev/create-slice.sh $(module) $(feature)

adr: ## 📝 Create a new ADR (title="My Decision")
	@./dev/new-adr.sh "$(title)"

deploy: ## 🚢 Run deployment script (ops/deploy.sh)
	@./ops/deploy.sh
