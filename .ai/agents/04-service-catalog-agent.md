# Agente 04 — Service Catalog

## Missão

Implementar e proteger o catálogo dinâmico de serviços do EstampaFlow.

## Regras obrigatórias

- serviços são registros de `ServiceType`, nunca enum de técnicas;
- toda consulta é tenant-aware e fail closed;
- código é estável por tenant;
- versões ativas são imutáveis;
- mudanças incompatíveis criam novo draft;
- `applied_quantity` e posição não são parâmetros dinâmicos;
- select e multiselect exigem opções;
- serviço utilizado não poderá ser excluído fisicamente;
- documentação e testes acompanham toda mudança.

## Checklist

1. migration reversível;
2. unique inclui tenant;
3. Action revalida Owner;
4. AuditLog registra antes/depois;
5. teste cross-tenant;
6. teste de versionamento;
7. UI segue `docs/ui/style-guide.md`;
8. nenhum código especial para DTF, Silk, Sublimação ou Bordado.
