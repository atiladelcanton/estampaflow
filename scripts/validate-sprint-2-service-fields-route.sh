#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan route:clear
docker compose run --rm app php artisan view:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app php artisan route:list --name=tenant.service-types
docker compose run --rm app php artisan test tests/Feature/ServiceCatalog/ServiceCatalogUiTest.php
docker compose restart app

echo "Rotas de campos do serviço corrigidas."
