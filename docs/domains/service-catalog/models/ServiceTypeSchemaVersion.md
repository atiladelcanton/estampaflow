# Model: ServiceTypeSchemaVersion

## Objetivo
Preservar a evolução imutável dos parâmetros de um serviço.

## Tabela
`service_type_schema_versions`.

## Estados
DRAFT, ACTIVE e RETIRED.

## Métodos

### parameters(): HasMany
Definições ordenadas do schema.

### isDraft(): bool
Indica se a versão pode ser editada.

### isActive(): bool
Indica se é a versão vigente.

## Invariante
A aplicação só altera parâmetros de uma versão DRAFT por meio de `SaveServiceSchemaDraftAction`.
