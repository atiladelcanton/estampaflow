# Model: ServiceType

## Objetivo
Representar um serviço produtivo configurado pelo tenant.

## Tabela
`service_types`.

## Tenant-aware
Sim, por `BelongsToTenant`. Consulta sem contexto lança exceção.

## Métodos públicos

### schemaVersions(): HasMany
Retorna todas as versões, da mais recente para a mais antiga.

### activeSchemaVersion(): BelongsTo
Retorna a versão usada em novos documentos.

### scopeAvailableForNewQuotes(Builder $query): void
Filtra serviços ativos com schema ativo.

### activate(): void / deactivate(): void
Altera disponibilidade. As Actions são a fronteira preferencial porque auditam e autorizam.

### isAvailableForNewQuotes(): bool
Confirma estado ativo e schema publicado.
