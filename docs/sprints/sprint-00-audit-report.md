# Sprint 0 — Relatório de auditoria

## Resultado

A base foi criada como projeto novo. Não existe legado de domínio para migrar nesta etapa.

## Implementado

- Laravel 13 e PHP 8.3;
- Livewire 4 e Tailwind 4;
- Fortify;
- MySQL 8.4 em Docker;
- Pest, Pint, PHPStan/Larastan, Rector e Playwright;
- Correlation ID;
- AuditLog append-only;
- TenantContext fail closed como fundação;
- comandos de documentação e auditoria;
- CI;
- design system Delka;
- login dividido, dashboard e telas demonstrativas.

## Limites

As telas de produtos não representam domínio implementado. Elas existem para aprovação visual. Não há migrations de Product, Tenant, Membership, ServiceType, Quote ou WorkOrder.

## Validação pendente no computador do projeto

1. executar `./scripts/setup.sh`;
2. abrir login, dashboard, produtos, novo produto e guia visual;
3. executar `make quality`;
4. registrar divergências de runtime;
5. fechar o escopo da Sprint 1.

## Risco conhecido

A validação completa depende do download das dependências Composer/NPM e da execução Docker no ambiente local.
