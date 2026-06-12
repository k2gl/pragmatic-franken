.PHONY: help shell e d install env-create composer-install \
        up run down build rebuild ps logs composer-chown \
        db-migrate db-rollback db-seed db-console db-fresh db-reset test-db \
        test test-unit test-integration test-e2e test-coverage coverage-html \
        lint lint-check analyze check ci smoke docs-check \
        clean metrics docker-stats stats \
        open-api xdebug-on xdebug-off slice adr deploy

# Variables (local)
USER_ID := $(shell id -u)
GROUP_ID := $(shell id -g)

# Executables (local)
DC = UID=$(USER_ID) GID=$(GROUP_ID) docker compose --progress=plain

# Docker container name (matches docker-compose service "app")
DC_APP = app

# Colors
RED    := $(shell tput setaf 1)
GREEN  := $(shell tput setaf 2)
YELLOW := $(shell tput setaf 3)
BLUE   := $(shell tput setaf 4)
CYAN   := $(shell tput setaf 6)
RESET  := $(shell tput sgr0)

##—————— Pragmatic FrankenPHP ——————
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

init: ## 🪪 Fork identity: rename + secrets (name=my-app [vendor=acme prune=1 reset-git=1])
	@./dev/init.sh --name=$(name) $(if $(vendor),--vendor=$(vendor)) $(if $(prune),--prune-examples) $(if $(reset-git),--reset-git)

env-create: ## Create .env from .env.dist
	@if [ ! -f .env.dist ]; then echo "$(RED)Error: .env.dist not found!$(RESET)"; exit 1; fi
	cp -n .env.dist .env
	@sed -i "s|^UID=.*|UID=$(USER_ID)|g" .env
	@sed -i "s|^GID=.*|GID=$(GROUP_ID)|g" .env
	@echo "$(GREEN).env created with UID:$(USER_ID) and GID:$(GROUP_ID)$(RESET)"

install: env-create build up composer-install db-migrate ## 🚀 Full setup: containers, dependencies, database
	@echo ""
	@echo "🐘 $(BLUE)Pragmatic FrankenPHP is igniting...$(RESET)"
	@echo "🔥 $(GREEN)Done! Open https://pragmatic-franken.localhost:$${HTTPS_PORT:-4750} (set in .env).$(RESET)"

##—————— 🐳 Docker ——————
build: ## Build Docker images
	@echo "$(RED)Building Docker images...$(RESET)"
	DOCKER_BUILDKIT=1 $(DC) build --pull

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

composer-install: ## Install PHP dependencies into the mounted project
	@echo "$(BLUE)Installing composer dependencies...$(RESET)"
	$(DC) exec $(DC_APP) composer install --no-interaction --prefer-dist
	$(DC) exec $(DC_APP) sh -c 'chown -R $(USER_ID):$(GROUP_ID) vendor var composer.lock 2>/dev/null || true'

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
	$(DC) exec $(DC_APP) bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

db-rollback: ## Rollback last migration
	@echo "$(BLUE)Rolling back last migration...$(RESET)"
	$(DC) exec $(DC_APP) bin/console doctrine:migrations:migrate prev --no-interaction

db-seed: ## Seed demo data (app:seed)
	@echo "$(BLUE)Seeding database...$(RESET)"
	$(DC) exec $(DC_APP) bin/console app:seed --no-interaction

db-console: ## Connect to PostgreSQL console
	@echo "$(CYAN)Connecting to PostgreSQL...$(RESET)"
	$(DC) exec db sh -c 'psql -U $$POSTGRES_USER -d $$POSTGRES_DB'

db-fresh: db-rollback db-migrate db-seed ## Full reset: rollback, migrate, seed
	@echo "$(GREEN)Database freshened!$(RESET)"

db-reset: down ## Destroy and rebuild database
	@echo "$(RED)Resetting database volumes...$(RESET)"
	$(DC) down -v
	$(DC) up -d $(DC_APP)
	$(MAKE) db-migrate db-seed

##—————— ✅ Tests ——————
test-db: ## Create + migrate the dedicated test database (app_test)
	@$(DC) exec $(DC_APP) bin/console doctrine:database:create --env=test --if-not-exists
	@$(DC) exec $(DC_APP) bin/console doctrine:migrations:migrate --env=test --no-interaction --allow-no-migration

test: test-db ## Run all PHPUnit tests (fail-fast)
	@echo "$(GREEN)Running all tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --stop-on-failure

test-unit: test-db ## Run unit tests only (#[Group('unit')])
	@echo "$(GREEN)Running unit tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=unit --stop-on-failure

test-integration: test-db ## Run integration tests only (#[Group('integration')])
	@echo "$(GREEN)Running integration tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=integration --stop-on-failure

test-e2e: test-db ## Run API/E2E tests only (#[Group('e2e')])
	@echo "$(GREEN)Running E2E tests...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --group=e2e --stop-on-failure

test-coverage: test-db ## Run tests with text coverage report
	@echo "$(GREEN)Running tests with coverage...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-text

coverage-html: test-db ## Generate HTML coverage report
	@echo "$(GREEN)Generating HTML coverage report...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpunit --coverage-html=coverage

##—————— 🛡 Code Quality ——————
lint: ## 🚀 Auto-fix code style (Laravel Pint)
	@echo "$(GREEN)Fixing code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint

lint-check: ## Check code style without fixing
	@echo "$(YELLOW)Checking code style with Pint...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/pint --test

analyze: ## 🔍 Run PHPStan static analysis (Level 10)
	@echo "$(YELLOW)Running PHPStan (Level 10)...$(RESET)"
	$(DC) exec $(DC_APP) ./vendor/bin/phpstan analyze --memory-limit=1G

check: lint analyze ## ✅ Run all checks before commit (lint + analyze)
	@echo "$(GREEN)✨ All checks passed!$(RESET)"

ci: lint-check analyze test ## Simulate CI pipeline (no auto-fix)
	@echo "$(GREEN)CI pipeline complete!$(RESET)"

smoke: ## End-to-end smoke check (console boots, /ready responds)
	@echo "$(YELLOW)Running smoke check...$(RESET)"
	$(DC) exec -T $(DC_APP) bin/console list >/dev/null
	@$(DC) exec -T $(DC_APP) sh -c 'curl -fsS -k --resolve "$$SERVER_NAME:443:127.0.0.1" "https://$$SERVER_NAME/ready"' | tee /dev/stderr | grep -q '"ok":true'
	@echo "$(GREEN)Smoke check passed!$(RESET)"

docs-check: ## Lint ADR front-matter and AGENTS.md token budget
	@./dev/check-docs.sh

agent-smoke: ## Prove scaffolded code passes Pint + PHPStan + tests untouched
	$(DC) exec $(DC_APP) bash ./dev/agent-smoke.sh

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
	@echo "$(GREEN)Fetching FrankenPHP metrics (admin endpoint, in-container)...$(RESET)"
	@$(DC) exec $(DC_APP) curl -s http://localhost:2019/metrics | head -20

##—————— 🛠 Dev Utilities ——————
open-api: ## Generate OpenAPI spec
	@echo "$(GREEN)Generating OpenAPI documentation...$(RESET)"
	$(DC) exec $(DC_APP) bin/console nelmio:apidoc:dump > docs/openapi.yaml
	@echo "$(GREEN)OpenAPI spec written to docs/openapi.yaml$(RESET)"

xdebug-on: ## Enable Xdebug at runtime
	@$(DC) exec $(DC_APP) bash -c 'echo "xdebug.mode=debug" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini'
	@echo "$(GREEN)Xdebug enabled. Restart container to apply.$(RESET)"

xdebug-off: ## Disable Xdebug at runtime
	@$(DC) exec $(DC_APP) rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
	@echo "$(GREEN)Xdebug disabled. Restart container to apply.$(RESET)"

##—————— 🤖 Scaffolding & Docs ——————
slice: ## 🚀 Generate a new feature slice (context=Foo feature=Bar)
	@echo "$(GREEN)Creating slice...$(RESET)"
	@./dev/create-slice.sh $(context) $(feature)

adr: ## 📝 Create a new ADR (title="My Decision")
	@./dev/new-adr.sh "$(title)"

deploy: ## 🚢 Run deployment script (ops/deploy.sh)
	@./ops/deploy.sh
