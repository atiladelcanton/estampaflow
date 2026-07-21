#!/usr/bin/env bash
set -euo pipefail

cd /CAMINHO/ABSOLUTO/DO/ESTAMPAFLOW

/usr/local/bin/php artisan queue:work database \
    --queue=provisioning,mail,default \
    --stop-when-empty \
    --tries=3 \
    --timeout=90 \
    --no-interaction

/usr/local/bin/php artisan schedule:run --no-interaction
