# ADR 0010: Laravel 13, Livewire 4, Fortify e billing por gateway

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto e arquitetura
- **Contexto relacionado:** stack, autenticação, UI, Stripe e Pix

## Decisão

### Backend

- Laravel 13;
- PHP 8.3 como runtime inicial;
- Composer, container e CI fixam a versão;
- compatibilidade com PHP 8.4 pode ser validada em matriz futura.

### Frontend

- Livewire 4;
- Blade;
- Tailwind CSS 4;
- starter kit Livewire oficial;
- Flux UI pode ser usado como base, com identidade visual customizada;
- Alpine somente quando necessário;
- Teams do starter kit desabilitado.

### Autenticação

Laravel Fortify por meio do starter kit oficial.

Não adicionar Breeze ao projeto novo.

Fluxos:

- login e logout;
- recuperação de senha;
- verificação de e-mail;
- convites;
- seleção de tenant;
- proteção de assinatura;
- 2FA obrigatório para Platform Admin;
- 2FA opcional e recomendado para Owner.

### Testes

- Pest para unit, feature e component;
- testes cross-tenant em todos os módulos;
- Playwright para E2E dos fluxos críticos;
- no mínimo: onboarding, serviço, orçamento, aprovação e transição da OS.

### Billing

O domínio usa contrato `SubscriptionGateway`.

Primeira implementação:

- Stripe;
- Laravel Cashier dentro do adapter;
- domínio não importa classes Stripe/Cashier.

Pix recorrente fica `PLANNED` e exige gateway escolhido, ADR próprio e testes de contrato.

### Webhooks

- assinatura validada;
- idempotência;
- proteção contra evento atrasado;
- auditoria;
- tenant localizado por vínculo interno;
- retry seguro;
- reconciliação agendada.

### Acesso

Tenant sem trial/assinatura ativa:

- Owner acessa billing e dados mínimos da conta;
- módulos operacionais ficam bloqueados;
- dados não são apagados;
- Platform Admin usa política própria.

### Retenção

A plataforma preservará dados por no mínimo 30 dias após cancelamento/expiração.

Purge automático não será implementado no MVP até existir ADR de retenção cobrindo backup, anexos, invoices, restauração, anonimização e auditoria.

## Consequências

### Positivas

- stack alinhada ao Laravel 13;
- autenticação oficial;
- billing desacoplado;
- Pix não atrasa o MVP;
- E2E cobre riscos principais.

### Negativas

- Flux exige customização visual;
- Playwright adiciona tooling Node;
- adapter de billing adiciona uma camada;
- purge fica adiado.

## Critérios de conformidade

- versões fixadas;
- Livewire 4 nos exemplos e skills;
- Fortify como base;
- Teams desabilitado;
- Stripe isolado no adapter;
- webhook idempotente;
- 2FA obrigatório para Platform Admin;
- fluxos críticos com E2E.

## Referências técnicas verificadas em 18/07/2026

- documentação oficial do Laravel 13;
- starter kit Livewire oficial;
- documentação oficial do Livewire 4;
- documentação oficial do MySQL 8.4.

## Histórico

- 18/07/2026 — decisão aceita com PHP 8.3, Livewire 4, Fortify, Playwright e Stripe no MVP.
