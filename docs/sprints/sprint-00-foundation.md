# Sprint 0 — Fundação, Auditoria e Base Visual

- **Status:** READY FOR LOCAL VALIDATION
- **Objetivo:** estabelecer a fundação técnica e visual antes de qualquer implementação de domínio.

## Escopo

1. inventariar stack e dependências;
2. classificar itens como `IMPLEMENTED`, `PLANNED`, `DIVERGENT`, `DEPRECATED` ou `BLOCKED`;
3. confirmar PHP 8.3, Laravel 13, Livewire 4 e MySQL 8.4;
4. configurar Pint, Rector, PHPStan, Pest, Playwright e build;
5. criar baseline de testes;
6. definir `CorrelationId`, logging estruturado e `AuditLog`;
7. criar pipeline mínima;
8. registrar riscos e dívidas;
9. criar o design system da Delka;
10. implementar login, layout operacional, dashboard e telas demonstrativas;
11. gerar plano das Sprints 1 e 2.

## Base visual

- paleta oficial registrada;
- login dividido;
- sidebar recolhível;
- header com busca e usuário;
- dashboard modular;
- listagem de produto demonstrativa;
- formulário de produto demonstrativo;
- guia visual navegável;
- responsividade e foco visível.

## Fora do escopo

- migrations definitivas dos módulos de negócio;
- implementação completa de tenancy;
- persistência de produtos;
- telas finais de operação;
- billing;
- migração destrutiva de legado.

## Evidências

- `docs/sprints/sprint-00-audit-report.md`;
- `docs/sprints/sprint-00-implementation-matrix.md`;
- `docs/sprints/sprint-00-source-validation.md`;
- `docs/ui/style-guide.md`;
- testes Feature e E2E;
- pipeline CI;
- scripts de setup e qualidade.

## Critérios de saída

- nenhum segredo no repositório;
- ambiente sobe localmente;
- build funciona;
- testes executam ou falhas são documentadas;
- padrão visual é aprovado;
- divergências dos ADRs estão registradas;
- Sprint 1 possui escopo fechado;
- nenhuma migration de domínio foi iniciada.
