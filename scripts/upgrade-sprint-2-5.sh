#!/usr/bin/env bash
set -euo pipefail

UID_HOST="$(id -u)"
GID_HOST="$(id -g)"

run_app() {
    docker compose run --rm app "$@"
}

run_app php artisan optimize:clear
run_app php artisan migrate --force
run_app composer dump-autoload

docker compose run --rm --user "${UID_HOST}:${GID_HOST}" app ./vendor/bin/pint
run_app php artisan docs:check
run_app php artisan test tests/Feature/Onboarding
run_app composer types:check

docker compose run --rm --user "${UID_HOST}:${GID_HOST}" -e HOME=/tmp node npm run build

docker compose up -d app queue mailpit

echo "Sprint 2.5 aplicada. Acesse /primeiros-passos e /ajuda no domínio do tenant."
