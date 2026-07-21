# Domain: Service Catalog

## Objetivo

Manter o catálogo produtivo configurável de cada estamparia.

## Linguagem ubíqua

- **ServiceType:** serviço oferecido, como DTF ou Cromia.
- **SchemaVersion:** versão imutável da estrutura de parâmetros.
- **ParameterDefinition:** campo configurado pelo tenant.
- **Draft:** versão editável.
- **Active:** versão usada por novos documentos.
- **Retired:** versão histórica substituída.

## Models

### ServiceType

Tenant-aware, ULID, código e slug únicos por tenant. Possui modo e estratégia de precificação, flags operacionais e referência para o schema ativo.

### ServiceTypeSchemaVersion

Pertence a um serviço. Somente `DRAFT` é editável. Uma ativação descontinua a versão ativa anterior.

### ServiceParameterDefinition

Pertence a uma versão. Suporta TEXT, INTEGER, DECIMAL, BOOLEAN, SELECT e MULTISELECT.

## Actions

- `CreateServiceTypeAction`;
- `UpdateServiceTypeAction`;
- `ToggleServiceTypeAction`;
- `DuplicateServiceTypeAction`;
- `CreateServiceSchemaVersionAction`;
- `SaveServiceSchemaDraftAction`;
- `ActivateServiceSchemaVersionAction`.

## Services

- `DefaultServiceCatalogService`;
- `ServiceParameterSchemaService`.

## Invariantes

1. consultas sem TenantContext falham;
2. tenant_id não vem do formulário;
3. código não muda após criação;
4. versão ativa não é editada;
5. somente um draft por serviço;
6. chaves de parâmetros são únicas por versão;
7. select e multiselect exigem opções;
8. serviços ativos exigem schema ativo.

## Auditoria

- `service_type.created`;
- `service_type.updated`;
- `service_type.activated`;
- `service_type.deactivated`;
- `service_schema.draft_created`;
- `service_schema.draft_saved`;
- `service_schema.activated`.

## Experiência operacional simplificada

A UI não expõe schema, draft, publicação ou número de versão. O Owner vê somente os campos usados pelo serviço, sugestões prontas e o botão **Salvar alterações**. A `UpdateServiceFieldsAction` cria a nova versão, salva e ativa nos bastidores, preservando o histórico necessário para orçamentos antigos.

Campos personalizados recebem identificador interno automaticamente a partir do nome exibido. Opções menos usadas, como unidade, valor inicial e impacto na precificação, ficam recolhidas em **Mais opções**.
