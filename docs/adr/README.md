# ADRs — Delka Estamparia

Os ADRs deste diretório complementam o Contexto Mestre v2.3.

## Fontes normativas

Definem o comportamento desejado:

1. ADR mais recente com status `ACCEPTED`;
2. Contexto Mestre;
3. especificação da sprint;
4. documentação funcional.

## Fontes de implementação

Definem o estado real encontrado:

1. código, migrations e testes executáveis;
2. documentação técnica correspondente ao código;
3. relatório de auditoria da sprint.

Uma funcionalidade descrita no contexto não deve ser chamada de implementada sem evidência no repositório.

## Status

- `PROPOSED`;
- `ACCEPTED`;
- `SUPERSEDED`;
- `DEPRECATED`;
- `REJECTED`.

## Índice

| ADR | Título | Status |
|---|---|---|
| [0001](0001-dynamic-service-types.md) | Tipos de serviço dinâmicos | ACCEPTED |
| [0002](0002-pragmatic-ddd-and-eloquent.md) | DDD pragmático com Eloquent | ACCEPTED |
| [0003](0003-single-database-tenancy-fail-closed.md) | Tenancy fail closed | ACCEPTED |
| [0004](0004-global-users-and-tenant-memberships.md) | Usuários e memberships | ACCEPTED |
| [0005](0005-stock-ledger-and-consumption-semantics.md) | Ledger e consumo de estoque | ACCEPTED |
| [0006](0006-pricing-rule-language-and-versioning.md) | Motor de preços e versionamento | ACCEPTED |
| [0007](0007-quote-work-order-cancellation-ownership.md) | Cancelamento comercial e operacional | ACCEPTED |
| [0008](0008-production-snapshots-and-artwork-model.md) | Snapshots e artes | ACCEPTED |
| [0009](0009-database-id-and-money-strategy.md) | Banco, IDs, Money e Rate | ACCEPTED |
| [0010](0010-livewire-authentication-and-billing-stack.md) | Stack e billing | ACCEPTED |

## Fluxo mínimo validado pela arquitetura

```text
Tenant
  → Membership
  → ServiceType
  → SchemaVersion
  → PriceRule
  → Quote
  → Approval
  → StockMovement
  → WorkOrder
  → ServiceApplication
  → ArtworkAssignment
```

## Alteração futura

Mudança incompatível não edita silenciosamente um ADR aceito. Deve criar novo ADR que marque o anterior como `SUPERSEDED`.
