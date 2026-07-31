#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app php artisan optimize:clear
docker compose run --rm app php artisan view:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint

docker compose run --rm app php artisan test     tests/Feature/Pricing/PricingUiTest.php     tests/Feature/Onboarding/PricingOnboardingCoverageTest.php

docker compose run --rm app composer types:check
docker compose restart app

echo "Interface guiada de precificação validada."
