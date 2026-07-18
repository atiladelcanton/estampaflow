# ADR 0002: DDD pragmático com Eloquent

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto e arquitetura
- **Contexto relacionado:** Models, Actions, Services, Queries, DTOs e Infrastructure

## Contexto

O projeto precisa de organização por domínio, mas não deve criar interfaces, Services e Repositories cerimoniais para cada operação CRUD.

## Decisão

Adotar DDD pragmático apoiado em Eloquent.

### Namespaces

```text
App\Domains\<Domain>
App\Application\<Domain>\Actions
App\Application\<Domain>\Queries
App\Infrastructure
App\Livewire
App\Support
```

### Model

Models Eloquent podem conter:

- casts e relacionamentos;
- scopes simples;
- mudanças de estado;
- pequenas invariantes locais;
- helpers ligados à própria entidade.

Models não conhecem Livewire, Request, Blade, Stripe, clientes HTTP ou implementações concretas de infraestrutura.

### Action

Uma Action representa um caso de uso completo e prefere um único método público `execute()`.

Responsabilidades:

- revalidar autorização crítica;
- abrir transação;
- aplicar locks;
- coordenar Models e Domain Services;
- garantir idempotência;
- persistir;
- disparar eventos after commit.

### Domain Service

Criar quando a regra:

- combina várias entidades;
- representa algoritmo relevante;
- é reutilizada por vários casos de uso;
- não pertence naturalmente a um único Model.

Não criar Service apenas para encapsular `Model::create()`.

### Query Object

Usar para dashboards, relatórios, agregações, paginação complexa e consultas otimizadas sem mudança de estado.

### Repository

Não é obrigatório para todo Model.

Criar somente quando houver:

- múltiplas implementações;
- reconstrução complexa de agregado;
- consulta especializada compartilhada;
- contrato necessário para infraestrutura externa.

Actions podem usar Eloquent diretamente, respeitando tenancy e testes.

### DTO e Value Object

- DTOs `readonly` nas fronteiras dos casos de uso;
- Value Objects para conceitos com regras próprias;
- arrays validados são permitidos em operações internas simples.

## Autorização

- Policies controlam acesso a recursos e ações na camada de aplicação/UI.
- Actions críticas revalidam o ator e as invariantes.
- O ator é recebido explicitamente em casos de uso auditáveis.
- Nenhuma segurança depende somente de esconder botão na interface.

## Testes

Pest será o estilo principal de escrita dos testes. PHPUnit continua como base de execução e testes legados em PHPUnit podem coexistir durante migração.

## Direção das dependências

```text
Livewire / HTTP / Console
          ↓
Application Actions / Queries
          ↓
Domain Models / Value Objects / Domain Services / Contracts
          ↑
Infrastructure implementations
```

Infrastructure implementa contratos internos. O domínio não depende de implementações concretas.

## Regra de propriedade

Cada regra possui um único proprietário.

Exemplos:

- transição de OS: `WorkOrderStatusService`;
- validação de parâmetros: `ServiceParameterSchemaService`;
- aprovação: `ApproveQuoteAction`;
- preço: `DynamicPricingService`;
- dashboards: Query Objects.

## Consequências

### Positivas

- menos classes cerimoniais;
- arquitetura familiar para Laravel;
- transações explícitas;
- responsabilidades testáveis.

### Negativas

- domínio permanece acoplado ao Eloquent;
- exige disciplina para evitar Models gigantes;
- novos Repositories precisam ser justificados.

## Critérios de conformidade

- não existem Actions que apenas repassam chamadas equivalentes;
- regras centrais não estão duplicadas;
- transações críticas começam em Actions;
- UI não chama infraestrutura diretamente;
- domínio não importa classes de interface ou gateways concretos.

## Histórico

- 18/07/2026 — decisão aceita com Eloquent nos Domains, Policies + validação nas Actions e Pest como padrão.
