#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app php artisan optimize:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint

docker compose run --rm app composer types:check

docker compose run --rm app php artisan test \
  tests/Feature/Pricing \
  tests/Feature/Onboarding/PricingOnboardingCoverageTest.php

echo "Correção do PHPStan da precificação guiada validada."
