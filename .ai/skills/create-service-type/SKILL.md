# Skill — create-service-type

## Entrada

- nome e código;
- descrição;
- modo e estratégia de preço;
- necessidade de arte;
- múltiplas posições;
- parâmetros iniciais.

## Execução

1. confirmar TenantContext;
2. confirmar Owner;
3. normalizar código e slug;
4. criar ServiceType inativo;
5. criar schema v1 em DRAFT;
6. salvar parâmetros;
7. ativar versão somente após validação;
8. registrar AuditLog;
9. criar testes e documentação.

## Restrições

- não criar enum para técnicas;
- não aceitar tenant_id da interface;
- não editar versão ativa;
- não criar fórmula livre.
