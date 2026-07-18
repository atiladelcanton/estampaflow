#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

docker compose up -d mysql
docker compose run --rm app composer quality
docker compose run --rm node npm run build

echo "Qualidade concluída."
