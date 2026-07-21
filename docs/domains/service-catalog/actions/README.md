# Actions do Service Catalog

## CreateServiceTypeAction
Autoriza Owner, normaliza código/slug, cria serviço inativo e schema v1 DRAFT.

## UpdateServiceTypeAction
Atualiza metadados sem alterar código ou schemas publicados.

## ToggleServiceTypeAction
Ativa somente quando existe schema ativo; desativação preserva histórico.

## DuplicateServiceTypeAction
Cria novo serviço e copia parâmetros da versão ativa para um draft.

## CreateServiceSchemaVersionAction
Cria uma nova versão e copia o schema ativo. Rejeita segundo draft simultâneo.

## SaveServiceSchemaDraftAction
Substitui parâmetros apenas em versão DRAFT, dentro de transação.

## ActivateServiceSchemaVersionAction
Valida, aposenta a versão anterior e publica a nova versão atomicamente.

## UpdateServiceFieldsAction
Orquestra criação ou reaproveitamento do draft, salvamento e ativação em uma única ação operacional. É a API usada pela interface simplificada; o versionamento continua interno e automático.

Todas recebem ator explícito, revalidam Owner e escrevem AuditLog.
