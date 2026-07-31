#!/usr/bin/env bash
set -euo pipefail

UID_HOST="$(id -u)"
GID_HOST="$(id -g)"

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan view:clear
docker compose run --rm --user "${UID_HOST}:${GID_HOST}" app ./vendor/bin/pint

docker compose run --rm app php artisan test \
  tests/Feature/Pricing/GuidedPricingFlowTest.php \
  tests/Feature/Pricing/PricingUiTest.php \
  tests/Feature/Onboarding/PricingOnboardingCoverageTest.php

docker compose run --rm app composer types:check

docker compose run --rm --user "${UID_HOST}:${GID_HOST}" -e HOME=/tmp node npm run build

docker compose restart app

echo "Seleções guiadas de Sublimação e Bordado validadas."
