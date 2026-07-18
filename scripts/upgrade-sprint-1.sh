#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Arquivo .env não encontrado. Para instalação limpa use ./scripts/setup.sh."
    exit 1
fi

cp .env ".env.backup-sprint-0-$(date +%Y%m%d%H%M%S)"

set_env() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

set_env APP_URL 'http://app.estamparia.test:8000'
set_env CENTRAL_DOMAIN 'app.estamparia.test'
set_env CENTRAL_DOMAINS 'app.estamparia.test'
set_env TENANT_BASE_DOMAIN 'estamparia.test'
set_env SESSION_DOMAIN '.estamparia.test'

# Remove artefatos da dashboard temporária da Sprint 0.
rm -f app/Support/Tenancy/InMemoryTenantContext.php \
    app/Livewire/SprintDashboard.php \
    resources/views/livewire/sprint-dashboard.blade.php \
    resources/views/dashboard.blade.php

./scripts/configure-hosts.sh

docker compose build app
docker compose up -d mysql

until docker compose exec -T mysql mysqladmin ping -h localhost -proot --silent >/dev/null 2>&1; do
    sleep 2
done

docker compose run --rm app composer update stancl/tenancy --with-all-dependencies --no-interaction
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan migrate --seed --force
docker compose run --rm node npm install
docker compose run --rm node npm run build
docker compose run --rm app php artisan docs:generate
docker compose run --rm app php artisan docs:check
docker compose run --rm app php artisan project:audit --write
docker compose run --rm app php artisan test
docker compose up -d app

cat <<'EOF'

Sprint 1 aplicada.

Central:
  http://app.estamparia.test:8000

Tenant Alpha:
  http://alpha.estamparia.test:8000

Owner:
  admin@delka.local / password
EOF
