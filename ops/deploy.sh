#!/bin/bash
set -e

echo "🚀 Starting deployment..."

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Configuration
PROJECT_DIR="${PROJECT_DIR:-/var/www/kanban-project}"
BACKUP_DIR="${BACKUP_DIR:-/var/www/backups}"

log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ] && ! sudo -n true 2>/dev/null; then
    log_warn "Not running as root. Some operations may require sudo."
fi

cd "$PROJECT_DIR" || { log_error "Project directory not found: $PROJECT_DIR"; exit 1; }

log_info "Step 1: Pull latest code from Git"
git fetch origin main
git pull origin main || { log_error "Git pull failed"; exit 1; }

log_info "Step 2: Install/update dependencies"
composer install --no-dev --optimize-autoloader || { log_error "Composer install failed"; exit 1; }

log_info "Step 3: Build Docker images"
docker compose build --pull php || { log_error "Docker build failed"; exit 1; }

log_info "Step 4: Run database migrations"
docker compose run --rm php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true

log_info "Step 5: Clear and warmup cache"
docker compose run --rm php bin/console cache:clear --no-warmup || true
docker compose run --rm php bin/console cache:warmup || true

log_info "Step 6: Generate OpenAPI documentation"
docker compose run --rm php bin/console nelmio:apidoc:dump --format=yaml > public/openapi.yaml || true

log_info "Step 7: Zero-downtime restart"
# Using docker compose up -d ensures minimal downtime
docker compose up -d --no-deps php || { log_error "Docker restart failed"; exit 1; }

log_info "Step 8: Wait for container to be healthy"
sleep 5
HEALTH_CHECK=0
while [ $HEALTH_CHECK -lt 12 ]; do
    if curl -sf http://localhost/health > /dev/null 2>&1; then
        log_info "Container is healthy"
        break
    fi
    HEALTH_CHECK=$((HEALTH_CHECK + 1))
    log_warn "Waiting for container to be healthy... ($HEALTH_CHECK/12)"
    sleep 5
done

if [ $HEALTH_CHECK -eq 12 ]; then
    log_error "Container health check failed"
    docker compose logs php
    exit 1
fi

log_info "Step 9: Cleanup old Docker images"
docker image prune -f > /dev/null 2>&1 || true
docker container prune -f > /dev/null 2>&1 || true

log_info "Step 10: Display deployment info"
echo ""
echo "=========================================="
echo "✅ Deployment completed successfully!"
echo "=========================================="
echo ""
echo "📋 Application URLs:"
echo "   - API: http://localhost/api"
echo "   - Swagger UI: http://localhost/docs"
echo "   - Health Check: http://localhost/health"
echo ""
echo "🐳 Container Status:"
docker compose ps php
echo ""

log_info "Deployment finished!"
