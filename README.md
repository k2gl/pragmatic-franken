# 🚀 Symfony + FrankenPHP 8.5 Enterprise Stack

Modern stack for high-performance Symfony applications, packaged in Docker with maximum security and monitoring.

## 🛠 Technologies
- **PHP 8.5 (Alpine)**: Latest features (Pipe operator, URI extension).
- **FrankenPHP**: Go-based application server with Worker Mode support.
- **PostgreSQL 16**: Primary database.
- **Redis 7**: Cache, sessions, and Messenger.
- **Caddy**: Automatic HTTPS and HTTP/3.

## 🚀 Quick Start

1. **Start the project:**
   ```bash
   docker compose up -d
   ```

2. **Install dependencies:**
   ```bash
   docker compose exec app composer install
   ```

3. **Run migrations:**
   ```bash
   docker compose exec app php bin/console doctrine:migrations:migrate
   ```

Project will be available at: https://localhost (or http://localhost).

## 🏗 Docker Architecture

Multi-stage build is used:
- **php_base**: Base layer with extensions (intl, bcmath, pdo_pgsql, apcu).
- **php_dev**: Development layer (Xdebug, dev dependencies).
- **php_prod**: Optimized layer for production (Worker Mode, Preload, AssetMapper).

## 🛡 Security and CI/CD

On each push to main, GitHub Actions performs:
1. **Gitleaks**: Search for secrets in code.
2. **Composer Audit**: Check for vulnerabilities in PHP packages.
3. **Trivy**: Scan image for system vulnerabilities.
4. **PHPStan**: Static analysis (Level 5).
5. **PHPUnit**: Run tests.

## 📊 Monitoring and Metrics

- **Prometheus**: Collects FrankenPHP metrics on port 2019.
- **Grafana**: Visualization (port 3000).
- **Healthcheck**: Container automatically restarts if /healthz endpoint is unavailable.

## ⏰ Scheduler (Cron)

Tasks are executed via Symfony Scheduler inside the main FrankenPHP container. Process management is handled via exec in Caddyfile.

## 🐞 Debugging (Xdebug)

- Xdebug configured on port 9003.
- Host: host.docker.internal
- IDE Key: PHPSTORM or VS Code "PHP Debug" extension.
