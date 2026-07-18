#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker não encontrado. Instale Docker Engine + Compose Plugin."
    exit 1
fi

if [ ! -f .env ]; then
    cp .env.docker.example .env
    echo ".env criado a partir de .env.docker.example"
else
    echo ".env existente preservado."
fi

mkdir -p storage/app/private storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

echo "Configurando domínios locais..."
./scripts/configure-hosts.sh

echo "Construindo imagem PHP..."
docker compose build app

echo "Subindo MySQL 8.4..."
docker compose up -d mysql

echo "Aguardando banco..."
until docker compose exec -T mysql mysqladmin ping -h localhost -proot --silent >/dev/null 2>&1; do
    sleep 2
done

echo "Instalando dependências PHP..."
docker compose run --rm app composer install --no-interaction --prefer-dist

echo "Gerando chave quando necessário..."
if ! grep -q '^APP_KEY=base64:' .env; then
    docker compose run --rm app php artisan key:generate --force
fi

echo "Executando migrations e seed..."
docker compose run --rm app php artisan migrate --seed --force

echo "Instalando dependências frontend..."
docker compose run --rm node npm install

echo "Gerando assets..."
docker compose run --rm node npm run build

echo "Gerando e validando documentação..."
docker compose run --rm app php artisan docs:generate
docker compose run --rm app php artisan docs:check
docker compose run --rm app php artisan project:audit --write

echo "Subindo aplicação..."
docker compose up -d app

cat <<'EOF'

Delka Estamparia — Sprint 1 disponível em:

  Central:
    http://app.estamparia.test:8000

  Tenant de demonstração:
    http://alpha.estamparia.test:8000

Acessos:
  Owner:
    E-mail: admin@delka.local
    Senha:  password

  Usuário operacional:
    E-mail: operacao@delka.local
    Senha:  password

Saúde:
  http://app.estamparia.test:8000/up

Logs:
  docker compose logs -f app
EOF
