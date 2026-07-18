# ADR 0001: Tipos de serviço produtivo dinâmicos

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto e arquitetura
- **Contexto relacionado:** Service Catalog, Pricing, Quote, Production e Reporting

## Contexto

A Delka Estamparia precisa atender empresas que trabalham com combinações diferentes de técnicas e serviços, como DTF, Silk Screen, Sublimação, Bordado, Cromia, Simulado, Transfer, Corte a Laser e serviços próprios.

Representar essas técnicas com enums fixos, colunas booleanas ou tabelas exclusivas faria cada serviço novo exigir alteração de código e migration.

## Decisão

Os serviços produtivos serão registros tenant-aware de `ServiceType`.

`DTF`, `SILK`, `SUBLIMACAO` e `BORDADO` serão registros padrão criados no onboarding de cada tenant. Eles não são valores rígidos do domínio.

Cada `ServiceType` poderá possuir:

- código estável dentro do tenant;
- nome e descrição;
- modo de precificação;
- versão ativa do schema;
- parâmetros configuráveis;
- tabelas e regras de preço;
- indicação de arte obrigatória;
- ordenação e estado ativo;
- snapshots comerciais e produtivos.

## Regras obrigatórias

1. Não criar enum contendo a lista de serviços oferecidos.
2. Não criar colunas como `has_dtf`, `has_silk` ou equivalentes.
3. Não criar tabela de preço exclusiva por técnica como estratégia principal.
4. Não espalhar condicionais por código de serviço.
5. Não excluir fisicamente serviço utilizado.
6. Serviço desativado não entra em novos orçamentos.
7. Orçamentos e ordens existentes preservam snapshots.
8. Relatórios filtram por `service_type_id` ou snapshots.
9. Componentes especializados são opcionais e devem possuir fallback dinâmico.

## Modelo conceitual mínimo

```text
Tenant
 └── ServiceType
      ├── ServiceTypeSchemaVersion
      │    └── ServiceParameterDefinition
      ├── ServicePriceTable
      │    └── ServicePriceRule
      └── LotService
           └── Production service snapshots
```

## Consequências

### Positivas

- novos serviços não exigem deploy;
- preços podem variar por serviço e tenant;
- relatórios permanecem genéricos;
- reduz condicionais e duplicação.

### Negativas

- formulários e validações são dinâmicos;
- schemas precisam de versionamento;
- snapshots tornam-se obrigatórios;
- o motor de preços precisa ser determinístico.

## Alternativas rejeitadas

- enum de técnicas;
- tabelas separadas por técnica;
- JSON livre sem definição e validação de parâmetros.

## Critérios de conformidade

- tenant cria serviço customizado sem código novo;
- parâmetros são renderizados dinamicamente;
- preço é calculado ou solicitado manualmente conforme configuração;
- aprovação cria snapshots;
- Kanban e relatórios exibem qualquer serviço;
- testes comprovam isolamento entre tenants.

## Histórico

- 18/07/2026 — decisão aceita.
- 18/07/2026 — modelo conceitual atualizado para não depender de um nome concreto de snapshot.
