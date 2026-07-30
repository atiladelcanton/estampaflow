#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan migrate --force
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app php artisan test \
    tests/Feature/ServiceCatalog/DefaultServiceCatalogTest.php \
    tests/Feature/ServiceCatalog/SilkScreenDefaultFieldsBackfillTest.php
docker compose run --rm app composer types:check

echo "Campos padrão do Silk Screen atualizados com sucesso."
