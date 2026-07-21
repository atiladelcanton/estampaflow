#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan route:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app composer types:check
docker compose run --rm app php artisan test tests/Feature/ServiceCatalog/ServiceCatalogUiTest.php
npm run build
docker compose restart app

echo "Tela de campos validada como formulário normal, sem Livewire."
