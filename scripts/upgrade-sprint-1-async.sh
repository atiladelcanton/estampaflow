#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if [ ! -f .env ]; then
    echo "Arquivo .env não encontrado. Execute ./scripts/setup.sh em uma instalação limpa."
    exit 1
fi

set_env() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

cp .env ".env.backup-async-$(date +%Y%m%d%H%M%S)"

set_env QUEUE_CONNECTION database
set_env DB_QUEUE default
set_env DB_QUEUE_RETRY_AFTER 120
set_env QUEUE_FAILED_DRIVER database-uuids
set_env MAIL_MAILER smtp
set_env MAIL_HOST mailpit
set_env MAIL_PORT 1025
set_env MAIL_USERNAME null
set_env MAIL_PASSWORD null
set_env MAIL_SCHEME null

docker compose build app
docker compose up -d mysql mailpit

until docker compose exec -T mysql mysqladmin ping -h localhost -proot --silent >/dev/null 2>&1; do
    sleep 2
done

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan migrate --force
docker compose run --rm app php artisan domain:provision-pending --include-failed
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app php artisan docs:generate
docker compose run --rm app php artisan docs:check
docker compose run --rm app php artisan test tests/Feature/Tenancy/AsyncInfrastructureTest.php tests/Feature/Tenancy/InvitationFlowTest.php
docker compose up -d app queue mailpit

cat <<'EOF'

Infraestrutura assíncrona atualizada.

Aplicação:
  http://app.estamparia.test:8000

Mailpit:
  http://localhost:8025

Fila:
  docker compose logs -f queue

Falhas:
  make queue-failed
EOF
