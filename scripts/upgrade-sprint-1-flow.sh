#!/usr/bin/env bash
set -euo pipefail

rm -f \
    app/Livewire/TenantOnboarding.php \
    app/Livewire/WorkspaceSelector.php \
    resources/views/central/onboarding.blade.php \
    resources/views/livewire/tenant-onboarding.blade.php \
    resources/views/livewire/workspace-selector.blade.php

if ! grep -q '^MAIL_LOG_CHANNEL=' .env; then
    printf '\nMAIL_LOG_CHANNEL=single\n' >> .env
fi

docker compose run --rm app php artisan optimize:clear
docker compose run --rm --user "$(id -u):$(id -g)" app ./vendor/bin/pint
docker compose run --rm app php artisan test tests/Feature/Tenancy

docker compose up -d app

echo 'Fluxo da Sprint 1 atualizado.'
