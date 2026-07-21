#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app php artisan optimize:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app composer types:check
docker compose run --rm app php artisan test tests/Feature/ServiceCatalog/ServiceCatalogUiTest.php
docker compose run --rm node npm run build

echo "Botões de sugestões e tipagem do editor validados."
