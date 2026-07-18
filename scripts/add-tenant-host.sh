#!/usr/bin/env bash
set -euo pipefail

DOMAIN="${1:-}"

if [[ -z "$DOMAIN" ]]; then
    echo "Uso: ./scripts/add-tenant-host.sh nome.estamparia.test"
    exit 1
fi

if [[ ! "$DOMAIN" =~ ^[a-z0-9][a-z0-9.-]*\.estamparia\.test$ ]]; then
    echo "Domínio inválido: $DOMAIN"
    echo "Use um domínio como minha-estamparia.estamparia.test"
    exit 1
fi

if grep -Eq "(^|[[:space:]])${DOMAIN//./\.}([[:space:]]|$)" /etc/hosts; then
    echo "$DOMAIN já está configurado em /etc/hosts."
    exit 0
fi

echo "Adicionando 127.0.0.1 $DOMAIN a /etc/hosts..."
sudo sh -c "printf '%s\n' '127.0.0.1 $DOMAIN' >> /etc/hosts"
echo "Domínio configurado: http://${DOMAIN}:8000"
