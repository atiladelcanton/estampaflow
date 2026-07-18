# ADR 0009: MySQL, ULID, UTC, Money e Rate

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e infraestrutura
- **Contexto relacionado:** migrations, índices, datas, valores e concorrência

## Decisão

### Banco

Usar MySQL 8.4 LTS, InnoDB e `utf8mb4`.

Collation padrão:

```text
utf8mb4_0900_ai_ci
```

Códigos, tokens e chaves normalizadas usam comparação binária/case-sensitive quando necessário.

### IDs

ULID textual em `CHAR(26)` é o padrão para entidades de negócio.

Não usar representação binária no MVP.

Entidades técnicas de altíssima cardinalidade só podem usar bigint com justificativa em ADR.

### Números comerciais

Quote e OS possuem sequência anual por tenant.

Tabela de sequência usa:

```text
tenant_id
document_type
year
next_number
```

A geração é transacional e concorrente.

Exemplos:

```text
ORC-2026-000123
OP-2026-000087
```

### Datas

- persistir timestamps em UTC;
- tenant possui timezone;
- padrão inicial `America/Sao_Paulo`;
- interface converte para timezone do tenant;
- domínio usa `CarbonImmutable`;
- datas sem horário usam `date`.

### Money

Valores finais persistem em unidade mínima:

```text
amount_minor BIGINT
currency CHAR(3)
```

BRL é a única moeda operacional do MVP, mas a moeda permanece estrutural.

`Money` não aceita float.

### Rate

Taxas e coeficientes persistem como:

```text
value DECIMAL(18,8)
currency CHAR(3)
unit VARCHAR(...)
```

Exemplos: por peça, cm², metro ou mil pontos.

Rate é convertido para Money somente no fechamento da linha.

### Percentuais

Usar basis points:

```text
discount_basis_points INT
```

`100` representa 1%.

### Arredondamento

- `HALF_UP`;
- arredondamento no total de cada linha;
- total geral soma linhas arredondadas;
- rateio distribui centavos restantes deterministicamente;
- política centralizada em Value Objects.

### Quantidades e medidas

- peças e pontos: inteiros;
- dimensões: `DECIMAL(12,4)`;
- áreas e taxas: escala explícita;
- unidade nunca fica apenas em texto de descrição.

### JSON

Usar para snapshots, condições, parâmetros e metadados.

Campo usado frequentemente em filtro, join ou unique deve ser normalizado ou possuir generated column indexada.

### Soft delete

Códigos estáveis preferem desativação.

Soft delete não é considerado mecanismo de liberação automática de unique constraint.

## Testes

O ambiente de integração e concorrência usa MySQL, não SQLite.

## Critérios de conformidade

- migrations seguem ULID textual;
- Money e Rate não usam float;
- sequência concorrente não duplica;
- filtros de período respeitam timezone;
- valores sub-centavo são preservados até o arredondamento final;
- índices JSON existem apenas para consultas reais.

## Histórico

- 18/07/2026 — decisão aceita com MySQL 8.4, ULID CHAR(26), BRL no MVP e Rate DECIMAL.
