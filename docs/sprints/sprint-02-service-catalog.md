# Sprint 2 — Catálogo Dinâmico de Serviços

- **Status:** READY FOR VALIDATION
- **Objetivo:** permitir que cada tenant configure serviços produtivos e schemas versionados sem código específico por técnica.

## Entregas

- tabelas `service_types`, `service_type_schema_versions` e `service_parameter_definitions`;
- isolamento tenant-aware fail closed;
- defaults DTF, Silk, Sublimação e Bordado no onboarding;
- criação, edição, ativação, desativação e duplicação de serviços;
- schemas em `DRAFT`, `ACTIVE` e `RETIRED`;
- editor de parâmetros text, integer, decimal, boolean, select e multiselect;
- imutabilidade das versões ativas;
- AuditLog;
- testes cross-tenant, versionamento, defaults e autorização;
- interface seguindo o design system EstampaFlow.

## Fora do escopo

- tabelas e regras de preço, previstas para a Sprint 3;
- uso dos parâmetros no orçamento;
- migração de legado DTF/Silk, caso ainda não exista legado real;
- exclusão física de serviços utilizados.

## Validação manual

1. acessar `/configuracoes/servicos` como Owner;
2. confirmar os quatro serviços padrão;
3. criar Cromia em modo híbrido;
4. adicionar parâmetros de cores e complexidade;
5. ativar o schema;
6. criar uma nova versão e confirmar que a anterior permanece imutável;
7. duplicar um serviço;
8. entrar como usuário comum e confirmar resposta 403.

## Rollback

```bash
php artisan migrate:rollback --step=1
```

O rollback remove somente as tabelas do catálogo da Sprint 2.
