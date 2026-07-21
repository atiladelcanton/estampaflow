#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "[1/7] Limpando caches..."
docker compose run --rm app php artisan optimize:clear

echo "[2/7] Atualizando autoload..."
docker compose run --rm app composer dump-autoload

echo "[3/7] Executando migrations da Sprint 2..."
docker compose run --rm app php artisan migrate --force

echo "[4/7] Criando catálogo padrão nos tenants existentes..."
docker compose run --rm app php artisan service-catalog:bootstrap-defaults

echo "[5/7] Formatando arquivos..."
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint

echo "[6/7] Executando testes da Sprint 2..."
docker compose run --rm app php artisan test tests/Feature/ServiceCatalog

echo "[7/7] Construindo frontend e subindo serviços..."
docker compose run --rm node npm run build
docker compose up -d app mysql mailpit queue

echo "Sprint 2 aplicada. Acesse /configuracoes/servicos no domínio do tenant."
