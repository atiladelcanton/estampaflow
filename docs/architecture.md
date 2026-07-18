# Arquitetura — Fundação

```text
Livewire / HTTP / Console
          ↓
Application Actions / Queries
          ↓
Domain Models / Value Objects / Domain Services / Contracts
          ↑
Infrastructure implementations
```

A Sprint 0 implementou a fundação transversal. A Sprint 1 adicionou tenancy e usuários:

- autenticação;
- correlação;
- auditoria;
- qualidade;
- documentação;
- ambiente;
- CI.

Os módulos de negócio permanecem planejados.


## Estado após a Sprint 1

```text
Central domain
  → autenticação global
  → seletor de ambientes
  → onboarding / convite
  → tenant domain
  → InitializeTenancyByDomain
  → TenantContext
  → membership ativa
  → operação
```

O pacote `stancl/tenancy` identifica o tenant. O isolamento dos Models operacionais permanece responsabilidade explícita do projeto por meio de `tenant_id`, scopes fail closed, constraints e testes.
