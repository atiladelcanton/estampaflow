# EstampaFlow — Sprint 2

SaaS multi-tenant para gestão de estamparias, com Laravel 13, Livewire 4, Tailwind 4, Fortify, MySQL 8.4 e `stancl/tenancy`.

## Instalação limpa

```bash
chmod +x scripts/*.sh
./scripts/setup.sh
```

O script:

1. configura `/etc/hosts`;
2. cria o `.env`;
3. constrói o container PHP;
4. instala dependências;
5. migra e executa seeds;
6. compila o frontend;
7. valida documentação;
8. sobe a aplicação, Mailpit e worker de filas.

## URLs

```text
Central:      http://app.estamparia.test:8000
Tenant Alpha: http://alpha.estamparia.test:8000
Saúde:        http://app.estamparia.test:8000/up
Mailpit:      http://localhost:8025
```

## Acessos

### Owner

```text
admin@delka.local
password
```

### Usuário operacional

```text
operacao@delka.local
password
```

O Owner acessa `/equipe`. O usuário operacional entra no tenant, mas recebe 403 na gestão de equipe.

## Atualização sobre a Sprint 0 já instalada

Extraia o pacote incremental na raiz do repositório e execute:

```bash
chmod +x scripts/*.sh
make upgrade
```

O script cria um backup do `.env`, configura os domínios, atualiza o Composer, executa migrations, build, documentação e testes.

## Comandos

```bash
make hosts
make add-host DOMAIN=minha-estamparia.estamparia.test
make start
make stop
make test
make quality
make logs
make shell
```

## Documentação

- `docs/sprints/sprint-01-tenancy-users.md`;
- `docs/sprints/sprint-01-implementation-report.md`;
- `docs/domains/tenancy/README.md`;
- `docs/ui/style-guide.md`;
- `delka-estamparia-contexto-v2.3.md`.


## Novos subdomínios no ambiente local

O DNS de produção será wildcard. No desenvolvimento local, ao criar um tenant novo, adicione o domínio exibido pelo sistema:

```bash
make add-host DOMAIN=minha-estamparia.estamparia.test
```

Depois abra `http://minha-estamparia.estamparia.test:8000`.


## Correção do fluxo de cadastro e convites

Após aplicar o patch incremental:

```bash
chmod +x scripts/upgrade-sprint-1-flow.sh
./scripts/upgrade-sprint-1-flow.sh
```

O cadastro público agora cria a estamparia automaticamente. O dashboard do domínio central é exclusivo do Platform Admin. Convites aceitam usuários existentes e permitem cadastrar usuários novos diretamente pelo link.


## E-mail local e filas

O ambiente local usa Mailpit:

```text
http://localhost:8025
```

Convites são enviados pela fila `mail`. Domínios são processados pela fila `provisioning`.

```bash
make queue-logs
make queue-failed
make queue-retry
make provision-domains
```

Para aplicar esta evolução em uma instalação existente:

```bash
chmod +x scripts/upgrade-sprint-1-async.sh
./scripts/upgrade-sprint-1-async.sh
```

Em hospedagem compartilhada, consulte `scripts/shared-hosting-queue-cron.example.sh`.


## Sprint 2 — Catálogo de serviços

Após aplicar a atualização, acesse no domínio do tenant:

```text
/configuracoes/servicos
```

O Owner poderá configurar serviços, parâmetros e versões de schema. O motor de preços será implementado na Sprint 3.
