#!/usr/bin/env bash
set -euo pipefail

docker compose run --rm app git config --global --add safe.directory /var/www/html || true
docker compose run --rm app php artisan optimize:clear
docker compose run --rm app ./vendor/bin/pint tests/Feature/Auth tests/Feature/VisualFoundationTest.php
docker compose run --rm app php artisan test tests/Feature/Auth tests/Feature/VisualFoundationTest.php tests/Feature/Tenancy/RegistrationOnboardingTest.php

echo "Testes de autenticação, cadastro e acesso central alinhados com o fluxo atual."
