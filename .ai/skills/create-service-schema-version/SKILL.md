# Skill — create-service-schema-version

## Objetivo

Criar uma nova versão editável do schema de um ServiceType sem alterar documentos que utilizam versões anteriores.

## Passos

1. carregar ServiceType no tenant atual;
2. rejeitar se já existir DRAFT;
3. incrementar versão;
4. copiar parâmetros da versão ativa;
5. permitir edição apenas no draft;
6. validar chaves, tipos e opções;
7. ativar em transação;
8. marcar a versão anterior como RETIRED;
9. atualizar `active_schema_version_id`;
10. auditar e testar.
