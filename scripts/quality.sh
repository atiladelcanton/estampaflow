#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."

UID_HOST="$(id -u)"
GID_HOST="$(id -g)"

docker compose up -d mysql
docker compose run --rm app sh -lc 'git config --global --add safe.directory /var/www/html && composer quality'
docker compose run --rm --user "${UID_HOST}:${GID_HOST}" -e HOME=/tmp node npm run build

echo "Qualidade concluída."
