#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose up -d mysql app
echo "Central: http://app.estamparia.test:8000"
echo "Tenant Alpha: http://alpha.estamparia.test:8000"
