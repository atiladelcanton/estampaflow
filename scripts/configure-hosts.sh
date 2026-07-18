#!/usr/bin/env bash
set -euo pipefail

HOSTS_LINE="127.0.0.1 app.estamparia.test alpha.estamparia.test beta.estamparia.test"

if grep -q "app.estamparia.test" /etc/hosts; then
    echo "Domínios locais já configurados em /etc/hosts."
    exit 0
fi

echo "Será adicionada esta linha a /etc/hosts:"
echo "  ${HOSTS_LINE}"
echo
sudo sh -c "printf '\n%s\n' '${HOSTS_LINE}' >> /etc/hosts"
echo "Domínios locais configurados."
