# 🎨 DELKA ESTAMPARIA — Contexto Mestre do Projeto

> Documento de transferência de contexto, arquitetura, regras de negócio, módulos, testes, sprints e padrão de manutenção.

- **Versão:** 2.3
- **Data:** 18/07/2026
- **Status:** Base arquitetural aceita; implementação a auditar
- **Stack:** Laravel 13 + PHP 8.3 + Livewire 4 + Tailwind CSS 4 + Fortify + MySQL 8.4
- **Arquitetura:** DDD pragmático com Eloquent
- **Tenancy:** single database, fail closed

---

## 0. GOVERNANÇA E FONTES DE VERDADE

### 0.1 Fontes normativas

1. ADR mais recente com status `ACCEPTED`;
2. este Contexto Mestre;
3. especificação da sprint;
4. documentação funcional.

### 0.2 Fontes de implementação

1. código, migrations e testes executáveis;
2. documentação técnica correspondente;
3. relatório de auditoria da sprint.

A presença de uma classe neste documento significa `PLANNED`, salvo evidência no repositório.

Classificações obrigatórias durante auditoria:

- `IMPLEMENTED`;
- `PLANNED`;
- `DIVERGENT`;
- `DEPRECATED`;
- `BLOCKED`.

### 0.3 Manutenção

Código e documentação divergentes são defeitos. Mudança incompatível em decisão aceita exige novo ADR.

---

## 1. VISÃO DO PRODUTO

A Delka Estamparia é uma plataforma SaaS multi-tenant para gestão de estamparias e empresas de personalização.

Cada tenant possui:

- usuários e memberships;
- clientes;
- produtos, variantes e estoque;
- catálogo dinâmico de serviços;
- schemas e parâmetros;
- preços;
- orçamentos;
- artes;
- ordens de produção;
- configurações;
- relatórios;
- assinatura.

### 1.1 Serviços produtivos

Serviços não são enums fixos.

Defaults do onboarding:

- DTF;
- Silk Screen;
- Sublimação;
- Bordado.

O tenant pode criar Cromia, Simulado, Sublimação Total, Transfer, Corte a Laser ou outro serviço sem alteração no núcleo.

### 1.2 SaaS

- trial: 7 dias;
- plano inicial: R$ 50/mês por tenant;
- Stripe no MVP;
- Pix recorrente planejado;
- Owner gerencia assinatura;
- dados preservados por pelo menos 30 dias após cancelamento/expiração;
- purge automático fora do MVP.

### 1.3 Idioma e moeda

- interface pt-BR;
- código em inglês;
- BRL no MVP;
- timezone padrão `America/Sao_Paulo`.

### 1.4 Direção visual

A interface segue o guia normativo em `docs/ui/style-guide.md`.

Princípios:

- visual claro, espaçoso e minimalista;
- sidebar recolhível, header limpo e dashboard modular;
- tabelas amplas e formulários em cards;
- paleta `#FFFFFF`, `#EFFFFA`, `#E5ECF4`, `#C3BEF7` e `#8A4FFF`;
- `#8A4FFF` como ação principal;
- neutros escuros para leitura e acessibilidade;
- loading, empty, validation, error e disabled em toda tela operacional;
- alterações em classes reutilizáveis exigem atualização do style guide.

---

## 2. ADRS ACEITOS

- ADR 0001 — serviços dinâmicos;
- ADR 0002 — DDD pragmático;
- ADR 0003 — tenancy fail closed;
- ADR 0004 — usuários globais e memberships;
- ADR 0005 — ledger e consumo de estoque;
- ADR 0006 — pricing declarativo e versionado;
- ADR 0007 — cancelamento;
- ADR 0008 — snapshots e artes;
- ADR 0009 — MySQL, ULID, Money e Rate;
- ADR 0010 — Laravel, Livewire, autenticação e billing.

Os arquivos em `docs/adr/` possuem precedência sobre este resumo.

---

## 3. STACK DEFINITIVA

| Item | Decisão |
|---|---|
| Backend | Laravel 13 |
| PHP | 8.3 |
| Frontend | Livewire 4 + Blade |
| CSS | Tailwind CSS 4 |
| UI base | Flux UI opcional e customizável |
| Banco | MySQL 8.4 LTS |
| Auth | Laravel Fortify / starter kit Livewire |
| Teams do starter | Desabilitado |
| Tenancy | stancl/tenancy + TenantContext fail closed |
| Billing MVP | Stripe + Cashier dentro de adapter |
| Testes | Pest |
| E2E | Playwright |
| Formatação | Laravel Pint |
| Refatoração | Rector |
| Análise estática | PHPStan/Larastan progressivo até nível 8 |
| PDF | adapter por contrato |
| API pública | OpenAPI/Scribe quando existir |

### 3.1 Qualidade

```bash
./vendor/bin/pint --test
./vendor/bin/rector --dry-run
./vendor/bin/phpstan analyse
php artisan test --parallel
php artisan docs:check
npm run build
npx playwright test
```

Cobertura percentual é indicador, não substitui testes de branches, concorrência, idempotência e isolamento.

---

## 4. ARQUITETURA

```text
Livewire / HTTP / Console
          ↓
Application Actions / Queries
          ↓
Domain Models / Value Objects / Domain Services / Contracts
          ↑
Infrastructure implementations
```

### 4.1 Diretórios

```text
app/
├── Domains/
│   ├── Tenancy/
│   ├── Customer/
│   ├── Product/
│   ├── ServiceCatalog/
│   ├── Pricing/
│   ├── Quote/
│   ├── Artwork/
│   ├── Production/
│   ├── Reporting/
│   └── Settings/
├── Application/<Domain>/Actions
├── Application/<Domain>/Queries
├── Infrastructure/
├── Livewire/
└── Support/
```

### 4.2 Responsabilidades

- Model: estado, casts, relações e pequenas invariantes.
- Action: caso de uso, transação, locks, idempotência e auditoria.
- Domain Service: regra reutilizável entre entidades.
- Query: leitura complexa e relatórios.
- Repository: somente com justificativa.
- DTO: fronteira tipada.
- Value Object: conceito com regras.
- UI: valida entrada, autoriza e chama Action.
- Infrastructure: integrações concretas.

### 4.3 Autorização

- Policy controla acesso ao recurso;
- Action crítica revalida ator e invariantes;
- esconder botão não é segurança;
- ator explícito em casos auditáveis.

---

## 5. LOGS, AUDITORIA E CORRELAÇÃO

Toda Action relevante recebe ou cria `correlation_id`.

Criar `AuditLog` append-only para ações de negócio críticas.

Campos mínimos:

- tenant nullable para eventos globais;
- ator;
- ação;
- entidade e ID;
- estado anterior e posterior em JSON;
- motivo;
- origem: UI, API, Job, Command ou webhook;
- IP/user agent quando aplicável;
- correlation ID;
- timestamp UTC.

Eventos técnicos usam logging estruturado. AuditLog não substitui logs de aplicação.

Obrigatório auditar:

- memberships e papéis;
- mudanças de preço;
- ajustes de estoque;
- aprovação e cancelamento;
- transições da OS;
- arte e versões;
- billing e webhooks;
- revisões de OS.

---

## 6. TENANCY E USUÁRIOS

### 6.1 Tenant

Fonte canônica: subdomínio.

Sessão é apenas conveniência.

Operação tenant-aware sem TenantContext falha.

### 6.2 Identidade

`users` é global.

`tenant_memberships` vincula usuário e tenant.

Papéis MVP:

- `OWNER`;
- `USER`.

Status:

- `ACTIVE`;
- `SUSPENDED`;
- `REVOKED`.

Platform Admin usa `users.is_platform_admin` e não recebe acesso automático aos dados de tenants.

Impersonação não existe no MVP.

### 6.3 Convites

Tabela própria com token em hash, expiração, papel e estados `PENDING`, `ACCEPTED`, `EXPIRED`, `REVOKED`.

### 6.4 Testes

Todo módulo testa create/read/update/delete cross-tenant, bindings, Jobs, Commands e validações unique.

---

## 7. CATÁLOGO DE SERVIÇOS

### 7.1 ServiceType

Campos conceituais:

- ULID;
- tenant;
- code e slug únicos no tenant;
- name e description;
- pricing mode: `AUTOMATIC`, `MANUAL`, `HYBRID`;
- calculation strategy;
- requires art;
- allows multiple positions;
- active;
- default;
- sort order;
- active schema version;
- timestamps.

Serviço usado não é excluído fisicamente.

### 7.2 Versionamento do schema

`ServiceTypeSchemaVersion`:

- versão;
- `DRAFT`, `ACTIVE`, `RETIRED`;
- autor;
- imutável após ativação.

`ServiceParameterDefinition` pertence a uma versão.

Tipos:

- TEXT;
- INTEGER;
- DECIMAL;
- BOOLEAN;
- SELECT;
- MULTISELECT.

Campos:

- key;
- label;
- unit;
- required;
- affects pricing;
- options;
- validation;
- default;
- order.

`applied_quantity` e posição são estruturais do `LotService`, não parâmetros.

---

## 8. MOTOR DE PREÇOS

### 8.1 Tabelas

`ServicePriceTable` pertence a serviço e versão de schema.

Possui prioridade, vigência, moeda e estado.

### 8.2 Regras

`ServicePriceRule` contém:

- faixa de quantidade;
- condições declarativas;
- strategy key;
- rate;
- setup em Money;
- mínimo em Money;
- prioridade;
- estado.

Não existe fórmula livre no MVP.

### 8.3 Operadores

- eq;
- in;
- gte;
- lte;
- between;
- contains_all.

### 8.4 Estratégias

- UNIT;
- QUANTITY_TIER;
- AREA;
- MATRIX;
- STITCH_RANGE.

### 8.5 Resultado

`ServicePriceResult` sempre retorna estado:

- MATCHED;
- MANUAL_REQUIRED;
- UNAVAILABLE;
- AMBIGUOUS;
- INVALID_INPUT.

Inclui preço, setup, total, regra, versão, origem, explicação e avisos.

### 8.6 Money e Rate

- Money: BIGINT em centavos;
- Rate: DECIMAL(18,8) com moeda e unidade;
- arredondamento HALF_UP no total da linha.

### 8.7 Drafts

Draft permanece fixado na versão usada. Migração é explícita e mostra diferenças. Aprovado nunca recalcula.

---

## 9. PRODUTOS E ESTOQUE

### 9.1 Product

Produto pode usar ou não variantes.

- sem variantes: saldo no produto;
- com variantes: saldo apenas nas variantes;
- troca de estratégia bloqueada com estoque/histórico.

### 9.2 StockMovement

Tipos:

- IN;
- OUT;
- RETURN;
- ADJUSTMENT.

É o ledger.

A projeção de saldo só muda pelo serviço de estoque.

### 9.3 Aprovação

Não há reserva antes da aprovação.

Aprovação consome estoque com locks de produtos/variantes.

Estoque insuficiente bloqueia. Backorder não existe no MVP.

### 9.4 Ajuste

Registra delta, antes, depois, motivo, ator e correlação.

### 9.5 Reconciliação

```bash
php artisan stock:reconcile
```

Detecta divergências sem corrigir automaticamente.

---

## 10. CUSTOMER E QUOTE

### 10.1 Customer

CRUD tenant-aware, nome obrigatório, contato opcional e soft delete.

### 10.2 Quote

Status:

- DRAFT;
- SENT;
- APPROVED;
- CANCELED.

DRAFT e SENT são editáveis.

APPROVED congela dados, consome estoque e cria OS.

### 10.3 QuoteLot

Agrupa produto/origem, variante, grade, quantidade e observações.

Arte não fica no lote.

### 10.4 LotService

Cada registro é uma aplicação física.

Campos essenciais:

- quote e lot;
- service type;
- schema version;
- snapshots de código/nome;
- applied quantity;
- position code/label;
- parameters;
- pricing result;
- production notes.

`service_type_id` é obrigatório em novos registros. Exclusão física do serviço é proibida.

### 10.5 Extras

`QuoteExtraItem` representa criação de arte, vetorização, urgência, embalagem ou entrega especial.

### 10.6 Totais

```text
subtotal =
    products_total
  + production_services_total
  + extra_items_total

percentage_discount =
    round(subtotal × discount_basis_points / 10000)

discount_total =
    min(subtotal, percentage_discount + fixed_discount)

final_total =
    max(0, subtotal - discount_total + freight)
```

### 10.7 Aprovação transacional

1. lock da Quote;
2. idempotência;
3. validação de preços e versões;
4. locks de estoque;
5. recálculo;
6. snapshots;
7. movimentos OUT;
8. criação da OS;
9. históricos;
10. commit;
11. eventos after commit.

---

## 11. ARTWORK E PRODUCTION

### 11.1 Artwork

- Artwork;
- ArtworkVersion;
- ArtworkAssignment.

A arte pode ser compartilhada por várias aplicações.

A aprovação ocorre por assignment e versão.

### 11.2 WorkOrder

Status:

- WAITING_ART;
- IN_PRODUCTION;
- WAITING_CUSTOMER;
- READY;
- DELIVERED;
- CANCELED.

Transições:

| Atual | Permitidos |
|---|---|
| WAITING_ART | IN_PRODUCTION, CANCELED |
| IN_PRODUCTION | WAITING_CUSTOMER, READY, CANCELED |
| WAITING_CUSTOMER | IN_PRODUCTION, READY, CANCELED |
| READY | DELIVERED, CANCELED |
| DELIVERED | nenhum |
| CANCELED | nenhum |

### 11.3 Snapshots

- WorkOrderLotSnapshot;
- WorkOrderServiceApplication;
- WorkOrderServiceSummary opcional.

O summary serve para leitura rápida, não para instrução de produção.

### 11.4 Revisões

`WorkOrderRevision` é append-only e exige ator, motivo e antes/depois.

### 11.5 Cancelamento

Antes da aprovação, CancelQuote.

Depois da aprovação, apenas CancelWorkOrder.

Retorno de estoque MVP:

- FULL;
- NONE.

Parcial fica planejado.

---

## 12. BILLING

### 12.1 Gateway

O domínio depende de `SubscriptionGateway`.

Stripe/Cashier é a primeira implementação.

### 12.2 Trial e assinatura

- trial 7 dias;
- assinatura ativa libera operação;
- Owner ainda acessa billing quando bloqueado;
- webhooks são verificados, idempotentes e auditados;
- reconciliação periódica corrige divergência com o provedor.

### 12.3 Segurança

- 2FA obrigatório para Platform Admin;
- recomendado para Owner;
- tenant de webhook é localizado por vínculo interno.

### 12.4 Fora do MVP

- Pix recorrente;
- purge automático;
- impersonação;
- múltiplos planos;
- cupons complexos.

---

## 13. REPORTING E SETTINGS

Relatórios usam Query Objects e filtros por `service_type_id`.

Relatórios iniciais:

- vendas por período;
- vendas por cliente;
- ticket médio;
- OS por status;
- OS por serviço;
- lead time;
- cancelamentos comerciais e operacionais;
- estoque e divergências.

Settings incluem dados da empresa, logo, validade padrão, prazo de produção e timezone.

---

## 14. CATÁLOGO TÉCNICO

### Tenancy

Models:

- Tenant;
- User;
- TenantMembership;
- TenantInvitation;
- TenantSubscription.

Actions:

- CreateTenantAction;
- BootstrapTenantAction;
- InviteTenantUserAction;
- AcceptTenantInvitationAction;
- ChangeTenantMembershipAction;
- TransferTenantOwnershipAction.

Services:

- TenantContext;
- TrialService;
- TenantMembershipService;
- SubscriptionService.

### Customer

- Customer;
- CreateCustomerAction;
- UpdateCustomerAction;
- DeleteCustomerAction;
- RestoreCustomerAction;
- CustomerDuplicateService.

### Product

- Product;
- ProductVariant;
- StockMovement;
- StockMovementService;
- StockAvailabilityService;
- CreateProductAction;
- UpdateProductAction;
- CreateProductVariantAction;
- AdjustStockAction;
- ConsumeStockForApprovedQuoteAction;
- ReturnStockFromCanceledWorkOrderAction.

### Service Catalog

- ServiceType;
- ServiceTypeSchemaVersion;
- ServiceParameterDefinition;
- ServiceParameterSchemaService;
- DefaultServiceCatalogService;
- CreateServiceTypeAction;
- UpdateServiceTypeAction;
- DuplicateServiceTypeAction;
- CreateServiceSchemaVersionAction;
- ActivateServiceSchemaVersionAction.

### Pricing

- ServicePriceTable;
- ServicePriceRule;
- PriceRuleMatcher;
- DynamicPricingService;
- PricingConfigurationValidator;
- CreateServicePriceTableAction;
- AddServicePriceRuleAction;
- PreviewServicePriceAction.

### Quote

- Quote;
- QuoteLot;
- LotService;
- QuoteExtraItem;
- QuotePricingService;
- QuoteApprovalService;
- QuotePdfService;
- CreateQuoteAction;
- AddQuoteLotAction;
- AddLotServiceAction;
- RecalculateQuoteAction;
- SendQuoteAction;
- ApproveQuoteAction;
- CancelQuoteAction;
- DuplicateQuoteAction.

### Artwork

- Artwork;
- ArtworkVersion;
- ArtworkAssignment;
- UploadArtworkVersionAction;
- AssignArtworkAction;
- ReviewArtworkAssignmentAction.

### Production

- WorkOrder;
- WorkOrderLotSnapshot;
- WorkOrderServiceApplication;
- WorkOrderServiceSummary;
- WorkOrderStatusHistory;
- WorkOrderRevision;
- WorkOrderFactoryService;
- WorkOrderStatusService;
- ProductionBoardService;
- TransitionWorkOrderAction;
- DeliverWorkOrderAction;
- CancelWorkOrderAction;
- ReviseWorkOrderAction.

### Reporting

- GetSalesDashboardQuery;
- GetProductionDashboardQuery;
- GetStockReconciliationQuery.

### Settings

- SystemSettings;
- UpdateCompanySettingsAction;
- UpdateQuoteDefaultsAction.

---

## 15. VALUE OBJECTS, DTOS E ENUMS

### Value Objects

- TenantId;
- Money;
- Rate;
- Quantity;
- DateRange;
- ServiceParameterBag;
- QuoteNumber;
- WorkOrderNumber;
- CorrelationId.

### DTOs

- CreateTenantData;
- CreateCustomerData;
- CreateProductData;
- CreateServiceTypeData;
- CreateServiceSchemaVersionData;
- CreateServicePriceRuleData;
- ServicePricingInput;
- ServicePriceResult;
- CreateQuoteData;
- CreateQuoteLotData;
- AddLotServiceData;
- QuoteTotalsData;
- DeliveryData;
- ProductionFiltersData;
- ReportFiltersData.

### Enums

- SubscriptionStatus;
- TenantRole;
- MembershipStatus;
- InvitationStatus;
- MovementType;
- PricingMode;
- PricingStrategy;
- ServiceSchemaStatus;
- ServiceParameterFieldType;
- ServicePriceResultStatus;
- PriceSource;
- QuoteStatus;
- SupplyType;
- ArtStatus;
- WorkOrderStatus;
- StockReturnMode;
- DeliveryMethod.

Não existe enum da lista de serviços.

---

## 16. EVENTOS

- TenantCreated;
- TenantTrialStarted;
- TenantSubscriptionActivated;
- TenantMembershipChanged;
- ServiceTypeCreated;
- ServiceSchemaActivated;
- PricingConfigurationChanged;
- QuoteCreated;
- QuoteSent;
- QuoteApproved;
- QuoteCanceled;
- StockConsumedForQuote;
- StockReturnedFromWorkOrder;
- ArtworkVersionUploaded;
- ArtworkAssignmentReviewed;
- WorkOrderCreated;
- WorkOrderStatusChanged;
- WorkOrderRevised;
- WorkOrderDelivered;
- WorkOrderCanceled.

Eventos dependentes de persistência disparam after commit.

---

## 17. LIVEWIRE

Componentes previstos:

### Service Catalog

- ServiceTypesTable;
- ServiceTypeForm;
- ServiceSchemaVersionEditor;
- ServiceParameterBuilder.

### Pricing

- ServicePriceTables;
- ServicePriceTableEditor;
- ServicePriceRuleForm;
- ServicePricePreview.

### Quote

- QuotesTable;
- QuoteEditor;
- QuoteLots;
- LotServiceEditor;
- DynamicServiceParametersForm;
- QuoteTotals;
- QuotePdf.

### Artwork

- ArtworkLibrary;
- ArtworkVersionUploader;
- ArtworkAssignmentReview.

### Production

- ProductionKanban;
- ProductionList;
- WorkOrderDetail;
- WorkOrderTimeline;
- WorkOrderRevisionModal;
- WorkOrderDeliveryModal.

Requisitos:

- loading, empty, validation e error;
- autorização;
- responsividade;
- origem do preço visível;
- nenhum formulário fixo obrigatório por técnica.

---

## 18. DOCUMENTAÇÃO

Estrutura:

```text
docs/
├── adr/
├── sprints/
├── domains/
├── generated/
└── runbooks/
```

Cada Domain documenta objetivo, linguagem, agregados, Models, Services, Actions, regras, tabelas, fluxos, riscos e testes.

Métodos públicos centrais possuem documentação de assinatura, propósito, autorização, tenant, transação, exceções, efeitos e testes.

Comandos:

```bash
php artisan docs:generate
php artisan docs:check
```

Reflection gera índice de assinaturas, mas não substitui explicação humana.

---

## 19. AGENTES E SKILLS

Agentes principais:

- project orchestrator;
- domain architect;
- tenancy/security;
- database;
- service catalog;
- pricing;
- product/stock;
- quote;
- artwork/production;
- Livewire UI;
- test engineer;
- documentation;
- quality/refactor;
- billing;
- reporting.

Skills principais:

- create-domain-module;
- create-tenant-aware-model;
- create-service-type;
- create-service-schema-version;
- create-dynamic-service-form;
- create-service-pricing;
- calculate-service-price;
- create-quote-flow;
- approve-quote-atomically;
- create-stock-movement;
- create-work-order-status-machine;
- create-livewire-crud;
- create-report;
- document-codebase;
- audit-module;
- refactor-with-safety.

Skills e agentes devem ser criados conforme o módulo entrar na sprint, não todos antecipadamente.

---

## 20. MIGRAÇÃO DE LEGADO

Quando houver código DTF/Silk fixo:

1. characterization tests;
2. novas tabelas;
3. defaults por tenant;
4. migração de preços;
5. LotPrint para LotService;
6. snapshots detalhados;
7. adapters `@deprecated`;
8. comparação de totais;
9. feature flag;
10. remoção somente após zero uso e rollback validado.

A Sprint 0 deve confirmar se existe legado real.

---

## 21. TESTES OBRIGATÓRIOS

### Tenancy

CRUD cross-tenant, bindings, Jobs, Commands, webhooks e unique.

### Pricing

versão, operadores, estratégia, especificidade, empate, Money/Rate, manual, híbrido e automático.

### Stock

concorrência, idempotência, saldo negativo, variante, rollback e reconciliação.

### Quote

múltiplos lotes, múltiplas aplicações, descontos, snapshots, aprovação concorrente e duplicação.

### Artwork

versões, sharing, aprovação por assignment, invalidação controlada e autorização.

### Production

snapshots, status, arte obrigatória, revisão, cancelamento e entrega.

### Billing

trial, webhook repetido, webhook atrasado, bloqueio e reconciliação.

Testes de concorrência usam MySQL.

---

## 22. SPRINTS

### Sprint 0 — Fundação e auditoria

- inventário do repositório;
- matriz IMPLEMENTED/PLANNED/DIVERGENT/DEPRECATED/BLOCKED;
- confirmação do runtime;
- baseline de testes;
- CI;
- audit log e correlation ID;
- documentação inicial;
- sem migrations de domínio antes do fechamento.

### Sprint 1 — Tenancy e usuários

### Sprint 2 — Catálogo e schemas

### Sprint 3 — Pricing

### Sprint 4 — Produtos e estoque

### Sprint 5 — Orçamentos

### Sprint 6 — Aprovação atômica

### Sprint 7 — Artes e produção

### Sprint 8 — Cancelamento e entrega

### Sprint 9 — Billing

### Sprint 10 — Relatórios e estabilização

Cada sprint possui escopo, fora do escopo, migrations, logs, testes, validação manual, evidências, riscos e rollback.

---

## 23. DEFINITION OF DONE

Uma sprint termina quando:

- escopo concluído;
- migrations reversíveis;
- isolamento tenant testado;
- auditoria definida;
- testes automatizados aprovados;
- validação manual documentada;
- documentação atualizada;
- Pint, Rector, PHPStan e build aprovados;
- E2E crítico aprovado quando aplicável;
- riscos e dívidas registrados;
- commit/tag de fechamento identificado.

---

## 24. PROMPT BASE

> Implemente apenas o escopo da sprint atual seguindo o Contexto Mestre v2.3 e os ADRs ACCEPTED. Antes de alterar código, audite o estado real e classifique itens como IMPLEMENTED, PLANNED, DIVERGENT, DEPRECATED ou BLOCKED. Use Laravel 13, PHP 8.3, Livewire 4, Tailwind 4, Fortify, MySQL 8.4, DDD pragmático, tenancy fail closed, Actions transacionais, AuditLog, Pest, Playwright e documentação. Serviços são dinâmicos por ServiceType. Não use técnicas fixas, float monetário, fórmula livre, reserva implícita de estoque ou acesso tenant sem contexto. Entregue arquivos alterados, testes, evidências, riscos e rollback.

---

## 25. REGRA FINAL

Toda mudança que altere Model, método público, schema, preço, status, tenancy, aprovação, estoque, arte, billing, agente ou skill deve atualizar a documentação no mesmo trabalho.

Nada descrito aqui deve ser afirmado como implementado antes da auditoria do repositório.


---

## ATUALIZAÇÃO v2.3 — SPRINT 1

Estado real implementado:

- `stancl/tenancy` v3.10;
- domínio central `app.estamparia.test`;
- tenants locais por `<slug>.estamparia.test`;
- `users` global;
- `Tenant`, `TenantMembership` e `TenantInvitation`;
- papéis Owner/User;
- statuses Active/Suspended/Revoked;
- onboarding transacional;
- TenantContext stancl fail closed;
- middleware de member e owner;
- seletor de ambientes;
- equipe e convites;
- transferência de propriedade;
- testes de acesso, convite e proteção do último Owner.

Itens ainda planejados:

- escopo automático dos Models operacionais será aplicado junto ao primeiro módulo tenant-aware da Sprint 2;
- custom domains;
- RBAC granular;
- billing;
- 2FA;
- impersonação continua fora do MVP.
