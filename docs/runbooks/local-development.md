# Runbook — Desenvolvimento Local

## Preparação

```bash
./scripts/setup.sh
```

## Diagnóstico

```bash
docker compose ps
docker compose logs -f app
docker compose exec mysql mysql -udelka -psecret delka_estamparia
docker compose run --rm app php artisan about
docker compose run --rm app php artisan project:audit
```

## Reset completo

```bash
make reset
make setup
```

O reset apaga o volume local do MySQL.


## Adicionar tenant criado pelo onboarding

```bash
make add-host DOMAIN=minha-estamparia.estamparia.test
```

Em produção, os subdomínios serão cobertos por DNS wildcard. A alteração de `/etc/hosts` é somente para o ambiente local.
