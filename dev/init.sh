#!/usr/bin/env bash
# Fork identity — the rite of passage for a new project built on this skeleton:
#
#   ./dev/init.sh --name=my-app [--vendor=acme] [--prune-examples] [--reset-git]
#   make init name=my-app
#
# What it does (in the FORK — the skeleton itself keeps its branded defaults):
#   * renames the project: COMPOSE_PROJECT_NAME / IMAGES_PREFIX / SERVER_NAME
#     (<name>.localhost) in .env.dist and .env, composer "name" (vendor/name)
#   * generates real secrets into .env (gitignored): APP_SECRET,
#     MERCURE_JWT_SECRET, POSTGRES_PASSWORD — .env.dist keeps !ChangeMe!
#   * --prune-examples: removes the Notification and Task example
#     contexts (Health, Home and Platform stay), stubs app:seed, clears the
#     example migration
#   * --reset-git: starts a fresh git history (single "init from template" commit)
set -euo pipefail

cd "$(dirname "${BASH_SOURCE[0]}")/.."

NAME="" VENDOR="app" PRUNE=0 RESET_GIT=0
for arg in "$@"; do
    case "$arg" in
        --name=*)   NAME="${arg#*=}" ;;
        --vendor=*) VENDOR="${arg#*=}" ;;
        --prune-examples) PRUNE=1 ;;
        --reset-git) RESET_GIT=1 ;;
        *) echo "init: unknown option '$arg'" >&2; exit 1 ;;
    esac
done

[[ "$NAME" =~ ^[a-z][a-z0-9-]+$ ]] || { echo "init: --name=<kebab-case> is required (e.g. --name=my-app)" >&2; exit 1; }

log() { printf '\033[36m[init]\033[0m %s\n' "$*"; }
secret() { openssl rand -hex 32; }

# --- rename ------------------------------------------------------------------
log "Renaming project to '$NAME' (composer: $VENDOR/$NAME)..."
for f in .env.dist .env; do
    [ -f "$f" ] || continue
    sed -i \
        -e "s|^COMPOSE_PROJECT_NAME=.*|COMPOSE_PROJECT_NAME=${NAME}|" \
        -e "s|^IMAGES_PREFIX=.*|IMAGES_PREFIX=${NAME}|" \
        -e "s|^SERVER_NAME=.*|SERVER_NAME=${NAME}.localhost|" \
        -e "s|^MERCURE_PUBLIC_URL=https://[^:]*|MERCURE_PUBLIC_URL=https://${NAME}.localhost|" \
        "$f"
done
sed -i "s|\"name\": \".*\"|\"name\": \"${VENDOR}/${NAME}\"|" composer.json
sed -i "s|\"description\": \"[^\"]*\"|\"description\": \"${NAME} — bootstrapped from Pragmatic FrankenPHP (k2gl/pragmatic-franken)\"|" composer.json

# --- secrets (only the gitignored .env) ---------------------------------------
if [ -f .env ]; then
    log "Generating secrets into .env..."
    sed -i \
        -e "s|^APP_SECRET=.*|APP_SECRET=$(secret)|" \
        -e "s|^MERCURE_JWT_SECRET=.*|MERCURE_JWT_SECRET=$(secret)|" \
        -e "s|^POSTGRES_PASSWORD=.*|POSTGRES_PASSWORD=$(secret)|" \
        .env
else
    log "No .env yet — run 'make env-create' then re-run init to generate secrets."
fi

# --- prune examples ------------------------------------------------------------
if [ "$PRUNE" = 1 ]; then
    log "Pruning example contexts (Notification, Task)..."
    rm -rf src/Context/Notification src/Context/Task \
           tests/Context/Notification tests/Context/Task
    rm -f tests/Support/Factory/TaskFactory.php
    # Template example migrations only (keep in sync when the template ships
    # new ones) — a fork's own migrations must survive a late prune.
    rm -f migrations/Version20260611032948.php

    # Drop their async routes; the file keeps the documented pattern.
    sed -i '/App\\Context\\Task\\/d' config/packages/messenger.yaml

    # app:seed referenced the Task example — reset it to a stub awaiting the
    # first real entity.
    cat > src/SharedKernel/Infrastructure/Cli/SeedCliCommand.php <<'PHP'
<?php

declare(strict_types=1);

namespace App\SharedKernel\Infrastructure\Cli;

use App\SharedKernel\Domain\Env;
use K2gl\Component\AppEnv\Services\AppEnv;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/** Demo data for manual testing (`make db-seed`). Wire your first entities here. */
#[AsCommand(name: 'app:seed', description: 'Seed demo data for manual testing (dev only)')]
final class SeedCliCommand extends Command
{
    public function __construct(
        private readonly AppEnv $appEnv,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->appEnv->is(Env::Prod)) {
            $io->error('Refusing to seed the prod environment.');

            return Command::FAILURE;
        }

        $io->note('Nothing to seed yet — add your project demo data here.');

        return Command::SUCCESS;
    }
}
PHP
    log "Pruned. Regenerate the schema baseline once you add entities:"
    log "  make shell → bin/console doctrine:migrations:diff"
fi

# --- fresh git history ----------------------------------------------------------
if [ "$RESET_GIT" = 1 ]; then
    log "Resetting git history..."
    rm -rf .git
    git init -q -b main
    git add -A
    git commit -q -m "init: project from pragmatic-franken template"
fi

echo
log "✅ Done. Next steps:"
log "   make install   # containers, deps, migrations"
log "   make smoke     # bin/console + /ready"
[ "$PRUNE" = 1 ] && log "   (examples pruned — make ci validates the slimmed tree)"
log "   Update README.md title/badges for '$NAME' when you publish the fork."
