# ADR 0006: Linguagem declarativa e versionamento do motor de preços

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e operação
- **Contexto relacionado:** ServiceTypeSchemaVersion, ServiceParameterDefinition, ServicePriceTable e DynamicPricingService

## Contexto

Condições JSON, prioridade e uma coluna livre de fórmula não garantem cálculo determinístico, seguro ou historicamente reproduzível.

## Decisão

O MVP usará motor declarativo, determinístico e sem execução de código configurado pelo tenant.

Não serão permitidos PHP, JavaScript, SQL, expression language genérica ou `eval()`.

A coluna livre `formula` não existirá no MVP.

## Schema versionado

Criar:

### `service_type_schema_versions`

- `id`;
- `tenant_id`;
- `service_type_id`;
- `version`;
- `status`: `DRAFT`, `ACTIVE`, `RETIRED`;
- `created_by`;
- timestamps.

### `service_parameter_definitions`

Cada definição pertence a `service_type_schema_version_id`.

`ServiceType` guarda `active_schema_version_id`.

Versões ativas são imutáveis. Alteração incompatível cria nova versão.

`LotService` e `ServicePriceRule` apontam para a versão usada.

## Entrada

`ServicePricingInput` contém:

- tenant;
- serviço;
- versão do schema;
- quantidade aplicada;
- parâmetros normalizados;
- data de referência;
- moeda;
- contexto permitido.

`applied_quantity` é campo estrutural do `LotService`, não parâmetro dinâmico.

## Operadores

- `eq`;
- `in`;
- `gte`;
- `lte`;
- `between`;
- `contains_all`.

Cada parâmetro usado deve existir na mesma versão e possuir `affects_pricing = true`.

## Estratégias conhecidas

O sistema oferece chaves implementadas e versionadas:

- `UNIT`;
- `QUANTITY_TIER`;
- `AREA`;
- `MATRIX`;
- `STITCH_RANGE`.

Nenhuma estratégia depende do código DTF, Silk, Sublimação ou Bordado.

Defaults sugeridos:

- DTF: `AREA` ou `MATRIX`;
- Silk: `MATRIX`;
- Sublimação: `AREA` ou `MATRIX`;
- Bordado: `STITCH_RANGE`.

## Money e Rate

- valores finais, setup e mínimo usam `Money` em unidade mínima;
- taxas e coeficientes usam `Rate`;
- `Rate` é persistido como `DECIMAL(18,8)`, com moeda e unidade;
- resultado é arredondado para `Money` conforme ADR 0009.

## Seleção da regra

1. validar tenant, serviço e versão;
2. normalizar parâmetros;
3. selecionar tabelas ativas e vigentes;
4. ordenar por prioridade da tabela;
5. filtrar quantidade e condições;
6. ordenar regras por tupla de especificidade;
7. aplicar prioridade da regra;
8. detectar empate restante;
9. calcular;
10. retornar explicação.

## Tupla de especificidade

Ordem decrescente, nesta sequência:

1. quantidade de condições `eq`;
2. quantidade de condições `in`;
3. quantidade de faixas fechadas;
4. quantidade total de condições;
5. menor largura normalizada das faixas;
6. `rule.priority`.

Empate após todos os critérios resulta em `AMBIGUOUS`. Nunca há desempate silencioso por ID.

## Resultado tipado

`DynamicPricingService::calculate()` sempre retorna `ServicePriceResult`.

Estados:

- `MATCHED`;
- `MANUAL_REQUIRED`;
- `UNAVAILABLE`;
- `AMBIGUOUS`;
- `INVALID_INPUT`.

Para `AUTOMATIC`, qualquer estado diferente de `MATCHED` bloqueia aprovação.

Para `HYBRID`, ausência de regra permite preço manual.

Para `MANUAL`, o motor solicita valor manual.

## Evolução de drafts

- novos orçamentos usam a versão ativa;
- drafts existentes permanecem fixados na versão original;
- migração de draft é ação explícita, com preview;
- aprovados nunca são recalculados.

## Validação antes de ativar

- parâmetros inexistentes;
- tipos incompatíveis;
- sobreposição;
- ambiguidade;
- gaps;
- casos de teste configurados.

## Auditoria

Registrar autor, antes/depois, vigência, versão e impacto estimado.

## Critérios de conformidade

- mesma entrada produz mesmo resultado;
- versão antiga permanece reproduzível;
- empate é explícito;
- não há execução arbitrária;
- taxas sub-centavo são preservadas;
- motor e matcher possuem cobertura integral de branches críticos.

## Histórico

- 18/07/2026 — decisão aceita com tabelas reais de versão, estratégias conhecidas e separação Money/Rate.
