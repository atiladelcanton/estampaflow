# ADR 0003: Multi-tenancy single database com isolamento fail closed

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e segurança
- **Contexto relacionado:** Models, autenticação, Jobs, Commands, webhooks e route binding

## Contexto

Várias estamparias compartilharão a mesma aplicação e o mesmo banco. Vazamento entre tenants é falha crítica.

Global Scope isolado não é suficiente porque Jobs, Commands, listeners, SQL raw e rotinas globais podem executar sem contexto.

## Decisão

Usar single database com `tenant_id`, `stancl/tenancy` para infraestrutura de inicialização e um `TenantContext` próprio com comportamento fail closed.

Operação tenant-aware sem tenant inicializado deve lançar exceção.

## Fonte canônica do tenant

- produção: subdomínio, como `{tenant}.dominio`;
- domínio central: cadastro, login e Platform Admin;
- custom domains: evolução futura;
- sessão pode guardar o último tenant apenas como conveniência;
- sessão nunca substitui o tenant resolvido pela URL;
- APIs resolvem tenant por credencial vinculada, não por header livre.

## TenantContext

```php
interface TenantContext
{
    public function currentId(): TenantId;
    public function hasTenant(): bool;
    public function run(TenantId $tenantId, Closure $callback): mixed;
}
```

`currentId()` lança exceção sem tenant.

## Models tenant-aware

Devem:

- possuir `tenant_id`;
- preencher pelo contexto;
- impedir troca de tenant após criação;
- falhar ao criar sem contexto;
- nunca aceitar `tenant_id` vindo de input HTTP;
- usar scope/trait padronizado.

## Tabelas globais

- `users`;
- `tenants`;
- `tenant_memberships`;
- `tenant_invitations`;
- `tenant_subscriptions`;
- `payments`;
- auditoria da plataforma;
- identificadores de provedores.

## Tabelas tenant-aware

Todos os dados operacionais, incluindo clientes, produtos, estoque, serviços, preços, orçamentos, artes, ordens, settings e históricos.

## Route model binding

Todo binding tenant-aware busca pelo tenant atual. Buscar apenas pelo ID é proibido.

## Constraints

Toda unicidade local inclui `tenant_id`.

Para relacionamentos críticos, usar:

- índice único do pai em `(tenant_id, id)`;
- foreign key composta `(tenant_id, parent_id)` quando praticável;
- validação de aplicação e testes de isolamento nos demais casos.

## Jobs, Commands e listeners

- carregam o ID do tenant;
- inicializam o contexto;
- validam estado do tenant;
- executam;
- limpam o contexto em `finally`.

Commands globais iteram tenants explicitamente.

## Webhooks

- validam assinatura;
- localizam tenant por vínculo interno;
- inicializam tenancy;
- aplicam idempotência;
- não confiam em tenant enviado livremente pelo provedor.

## Platform Admin

Impersonação não fará parte do MVP.

O Platform Admin utiliza telas globais. Uma futura impersonação exigirá ADR próprio, motivo, tempo limitado, auditoria e política de escrita.

## SQL raw

Toda consulta raw em tabela tenant-aware aplica `tenant_id` explicitamente.

## Testes obrigatórios

- leitura, criação, update e delete cross-tenant bloqueados;
- route binding protegido;
- `unique` local permite mesmo valor em tenants diferentes;
- Job sem tenant falha;
- Command isola cada tenant;
- webhook não escolhe tenant arbitrariamente;
- SQL raw possui escopo.

## Consequências

### Positivas

- falhas de inicialização não viram consultas globais;
- isolamento pode ser testado;
- Jobs e webhooks ficam previsíveis.

### Negativas

- seeds e scripts precisam inicializar contexto;
- operações globais exigem caminho explícito;
- migrations possuem mais índices e FKs compostas.

## Histórico

- 18/07/2026 — decisão aceita com subdomínio como fonte canônica, stancl/tenancy + TenantContext e impersonação fora do MVP.
