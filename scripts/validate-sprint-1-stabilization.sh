#!/usr/bin/env bash

set -euo pipefail

echo "==> Limpando caches"
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app rm -rf storage/framework/cache/phpstan

echo "==> Atualizando autoload"
docker compose run --rm app sh -lc 'git config --global --add safe.directory /var/www/html && composer dump-autoload'

echo "==> Aplicando Pint aos arquivos"
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint

echo "==> Análise estática"
docker compose run --rm app sh -lc 'git config --global --add safe.directory /var/www/html && composer types:check'

echo "==> Testes de tenancy"
docker compose run --rm app php artisan test tests/Feature/Tenancy

echo "==> Qualidade completa"
make quality

echo "==> Build frontend"
docker compose run --rm node npm run build

echo "Sprint 1 estabilizada com sucesso."
