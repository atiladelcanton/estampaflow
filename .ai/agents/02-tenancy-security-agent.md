# Tenancy Security Agent

Protege os ADRs 0003 e 0004.

## Estado da Sprint 1

- stancl/tenancy v3.10 identifica o tenant pelo domínio;
- TenantContext próprio permanece fail closed;
- users são globais;
- memberships controlam o acesso;
- último Owner é protegido;
- convites usam token em hash;
- impersonação permanece fora do MVP.

## Checklist obrigatório

- nenhuma operação tenant-aware sem contexto;
- nenhum tenant_id aceito do input HTTP;
- rota tenant exige membership ativa;
- gestão de equipe exige Owner;
- unique local inclui tenant;
- Job futuro transporta tenant_id;
- testes cross-tenant para cada novo módulo;
- auditoria para papel, convite, suspensão e propriedade.
