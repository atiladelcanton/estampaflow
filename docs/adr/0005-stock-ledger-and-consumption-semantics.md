# ADR 0005: Ledger de estoque e consumo na aprovação

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e operação
- **Contexto relacionado:** Product, ProductVariant, Quote Approval e Work Order

## Contexto

O contexto anterior misturava reserva e baixa de estoque. Também permitia métodos diretos que poderiam alterar saldo sem movimento auditável.

## Decisão

No MVP não haverá reserva antecipada.

A aprovação do orçamento consome estoque definitivamente, na mesma transação que cria a OS.

Estoque insuficiente bloqueia a aprovação. Backorder não fará parte do MVP.

## Itens controlados

- produto sem variantes: saldo no produto;
- produto com variantes: saldo apenas nas variantes;
- item fornecido pelo cliente: sem movimento interno;
- mudança entre “com variante” e “sem variante” é bloqueada quando houver estoque ou histórico.

## Fonte de verdade

`StockMovement` é o ledger auditável.

Tipos:

- `IN`;
- `OUT`;
- `RETURN`;
- `ADJUSTMENT`.

A coluna de saldo é uma projeção de performance e só pode ser alterada pelo serviço transacional de estoque.

## Ajuste manual

Um ajuste registra:

- `quantity_delta`;
- `balance_before`;
- `balance_after`;
- motivo;
- usuário;
- data;
- correlação.

## Aprovação concorrente

`ApproveQuoteAction`:

1. bloqueia o orçamento;
2. verifica idempotência;
3. ordena IDs de estoque;
4. bloqueia produtos/variantes com `FOR UPDATE`;
5. recalcula necessidade;
6. valida saldo;
7. cria movimentos `OUT`;
8. atualiza projeções;
9. cria OS e snapshots;
10. commit;
11. eventos after commit.

## Idempotência

Movimentos automáticos possuem `idempotency_key` com índice único por tenant.

Exemplos:

```text
QUOTE:{quote_id}:APPROVAL_OUT:{stock_item_id}
WORK_ORDER:{work_order_id}:CANCEL_RETURN:{stock_item_id}
```

## Cancelamento

- Quote antes da aprovação: sem estoque.
- Após aprovação: apenas cancelamento da OS pode criar `RETURN`.
- Repetição não duplica retorno.

## Reconciliação

Criar comando:

```bash
php artisan stock:reconcile
```

Ele compara a projeção com a soma do ledger e reporta divergências. Correção automática não ocorre sem autorização explícita.

## Métodos de Model

Models não expõem alteração pública de saldo sem movimento.

## Critérios de conformidade

- toda alteração cria movimento;
- saldo negativo é bloqueado;
- concorrência não gera overselling;
- falha ao criar OS reverte movimentos;
- aprovação e retorno são idempotentes;
- ajuste manual é auditado;
- reconciliação detecta divergências.

## Histórico

- 18/07/2026 — decisão aceita sem reserva e sem backorder no MVP.
