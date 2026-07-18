# ADR 0007: Responsabilidade única pelo cancelamento após aprovação

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e operação
- **Contexto relacionado:** Quote, WorkOrder, estoque e relatórios

## Contexto

Permitir cancelamento e estorno tanto no orçamento quanto na OS cria risco de retorno duplicado e histórico contraditório.

## Decisão

A responsabilidade muda no marco da aprovação.

## Antes da aprovação

`CancelQuoteAction` pode cancelar `DRAFT` ou `SENT`.

Ele:

- registra motivo e usuário;
- não movimenta estoque;
- não cria OS;
- torna o orçamento não editável.

## Depois da aprovação

O orçamento permanece `APPROVED`.

Somente `CancelWorkOrderAction` pode cancelar o processo operacional e retornar estoque.

A UI apresenta o estado da OS associada sem apagar a aprovação comercial.

## Política de retorno no MVP

Modos:

- `FULL`;
- `NONE`.

Retorno parcial fica fora do MVP.

Regras:

- `WAITING_ART`: padrão sugerido `FULL`;
- `IN_PRODUCTION`, `WAITING_CUSTOMER` ou `READY`: usuário escolhe `FULL` ou `NONE` e informa motivo;
- `DELIVERED`: não cancela pelo fluxo comum;
- repetição da Action é idempotente.

## Transação

`CancelWorkOrderAction`:

1. bloqueia a OS;
2. retorna estado atual se já cancelada;
3. valida transição;
4. cria retornos idempotentes;
5. altera status;
6. registra histórico;
7. commit;
8. eventos after commit.

## Limite do ADR

Pagamentos, reembolsos, notas fiscais e estornos financeiros não fazem parte deste ADR e terão decisão própria.

## Relatórios

Devem distinguir:

- Quote cancelada antes da aprovação;
- venda aprovada com OS cancelada;
- estoque retornado ou não;
- valor comercial aprovado;
- valor operacional cancelado.

## Critérios de conformidade

- Quote aprovada não cancela diretamente;
- retorno nunca duplica;
- Quote cancelada antes da aprovação não altera estoque;
- OS entregue exige pós-venda separado;
- toda mudança registra ator, motivo e data.

## Histórico

- 18/07/2026 — decisão aceita com retorno total ou nenhum no MVP.
