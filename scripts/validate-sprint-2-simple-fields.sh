#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

docker compose run --rm app php artisan optimize:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app php artisan test \
  tests/Feature/ServiceCatalog/ServiceCatalogTest.php \
  tests/Feature/ServiceCatalog/ServiceCatalogUiTest.php
docker compose run --rm app composer types:check

echo "Campos de serviço simplificados e validados."
