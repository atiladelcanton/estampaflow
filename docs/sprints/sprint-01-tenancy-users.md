# Sprint 1 — Tenancy e Usuários

- **Status:** IMPLEMENTED — aguardando validação local
- **Data:** 18/07/2026

## Objetivo

Transformar a fundação da Sprint 0 em uma aplicação multi-tenant operacional, com identidade global, ambientes por domínio, memberships, convites e proteção do último Owner.

## Escopo implementado

- `stancl/tenancy` v3.10 como infraestrutura de identificação;
- single database sem troca de conexão;
- `TenantContext` próprio e fail closed;
- tenant por domínio;
- `users` globais;
- `tenant_memberships`;
- `tenant_invitations`;
- papéis `OWNER` e `USER`;
- status de membership;
- onboarding transacional;
- seletor de ambientes;
- gestão de equipe;
- convite com token em hash;
- aceite do convite;
- suspensão e reativação;
- transferência de propriedade;
- auditoria;
- testes de acesso e isolamento.

## Fora do escopo

- RBAC granular;
- impersonação;
- custom domains;
- billing completo;
- serviço produtivo;
- produto, estoque e orçamento;
- expiração automatizada de convites por scheduler;
- 2FA, que permanece vinculado ao fechamento de segurança/billing.

## Domínios locais

```text
app.estamparia.test
alpha.estamparia.test
beta.estamparia.test
```

O script `make hosts` adiciona os hosts necessários.

## Critérios de aceite

- login central funciona;
- seletor lista apenas memberships ativas;
- Alpha abre por domínio;
- usuário sem membership recebe 403;
- Owner acessa equipe;
- User não acessa equipe;
- convite cria link e e-mail em log;
- aceite exige o mesmo e-mail;
- último Owner permanece protegido;
- testes e build passam.

## Rollback

1. restaurar o commit da Sprint 0;
2. executar `php artisan migrate:rollback --step=4`;
3. remover `stancl/tenancy` do Composer;
4. restaurar `.env` sem os domínios locais.
