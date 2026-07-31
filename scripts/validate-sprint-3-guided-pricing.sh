#!/usr/bin/env bash
set -euo pipefail

UID_HOST="$(id -u)"
GID_HOST="$(id -g)"

mkdir -p public/build node_modules
sudo chown -R "${UID_HOST}:${GID_HOST}" public/build node_modules 2>/dev/null || true

docker compose up -d mysql mailpit
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app composer dump-autoload
docker compose run --rm --user "${UID_HOST}:${GID_HOST}" app ./vendor/bin/pint
docker compose run --rm app php artisan docs:check
docker compose run --rm app php artisan test \
    tests/Feature/Pricing/GuidedPricingFlowTest.php \
    tests/Feature/Pricing/PricingEngineTest.php \
    tests/Feature/Pricing/PricingUiTest.php \
    tests/Feature/Onboarding/PricingOnboardingCoverageTest.php \
    tests/Unit/Pricing
docker compose run --rm app composer types:check
docker compose run --rm --user "${UID_HOST}:${GID_HOST}" -e HOME=/tmp node npm run build
docker compose up -d app queue mailpit

echo "Precificação guiada instalada. Teste DTF, Silk, Sublimação e Bordado em /configuracoes/precos."
