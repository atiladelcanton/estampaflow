# Service: ServiceParameterSchemaService

## Objetivo
Validar definições, gerar regras dinâmicas e normalizar valores.

## Métodos

### validateDefinitions(array $parameters): void
Valida chave, duplicidade, label e opções.

### buildValidationRules(ServiceTypeSchemaVersion $version): array
Converte o schema em regras Laravel.

### normalize(ServiceTypeSchemaVersion $version, array $values): array
Converte valores para os tipos definidos.
