# Skill: create-tenant-aware-model

1. confirmar que o Model é operacional e tenant-aware;
2. criar `tenant_id` ULID;
3. adicionar índices e uniques compostos;
4. aplicar o trait/scope fail closed do projeto;
5. nunca preencher tenant_id por input;
6. resolver TenantContext;
7. proteger route binding;
8. criar factory;
9. testar CRUD cross-tenant;
10. documentar tabela, relações, invariantes e métodos;
11. executar MySQL, Pest, PHPStan e Pint.
