#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose up -d mysql mailpit app queue
echo "Central: http://app.estamparia.test:8000"
echo "Tenant Alpha: http://alpha.estamparia.test:8000"
echo "Mailpit: http://localhost:8025"
