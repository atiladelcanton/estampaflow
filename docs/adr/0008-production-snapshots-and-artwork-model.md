# ADR 0008: Snapshots detalhados de produção e arte por aplicação

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e operação
- **Contexto relacionado:** QuoteLot, LotService, Artwork e WorkOrder

## Contexto

Uma mesma peça pode possuir aplicações diferentes em posições diferentes. Um resumo agregado não contém informação suficiente para produzir e arte no nível do lote pode aprovar itens indevidos.

## Decisão

Cada `LotService` representa uma aplicação física específica.

Exemplos:

- DTF na frente;
- DTF nas costas;
- Bordado na manga esquerda;
- Silk no bolso;
- Sublimação total.

## Campos estruturais de `LotService`

- `applied_quantity`;
- `position_code` nullable;
- `position_label_snapshot` nullable;
- serviço e versão do schema;
- parâmetros completos;
- preço e fonte;
- notas de produção.

`applied_quantity` e posição não são duplicados como parâmetros dinâmicos.

Quando o serviço permitir várias posições, a UI adiciona vários `LotService`.

## Artes

### `Artwork`

Identidade lógica do arquivo/arte, independente de uma única aplicação.

### `ArtworkVersion`

- storage e path privado;
- checksum;
- mime type;
- tamanho;
- dimensões e metadados;
- autor e data;
- observações.

### `ArtworkAssignment`

Relaciona uma arte a um `LotService`.

Contém:

- `artwork_id`;
- `lot_service_id`;
- status;
- `approved_artwork_version_id`;
- aprovador;
- data e observação.

A mesma arte pode ser associada a várias aplicações sem duplicar o arquivo.

Status:

- `PENDING`;
- `RECEIVED`;
- `IN_REVIEW`;
- `APPROVED`;
- `REJECTED`.

Nova versão invalida aprovação apenas das assignments que dependiam da versão anterior.

## Snapshot da OS

### `WorkOrderLotSnapshot`

Preserva produto, variante, SKU, atributos, grade, origem, quantidade e observações.

### `WorkOrderServiceApplication`

Preserva:

- lote snapshot;
- serviço e código/nome snapshot;
- versão do schema;
- parâmetros completos;
- posição;
- quantidade;
- arte/version aprovada;
- notas;
- ordenação.

### `WorkOrderServiceSummary`

Read model opcional para badges, filtros e totais. Não é fonte canônica da produção.

## Imutabilidade e revisões

Dados aprovados não são alterados diretamente.

Correção operacional usa `WorkOrderRevision` append-only com:

- antes e depois;
- motivo;
- ator;
- data;
- correlação.

A revisão não recalcula automaticamente preço ou estoque. Alterações com impacto comercial exigem novo fluxo comercial.

## Estado inicial

A OS inicia em `WAITING_ART` quando alguma assignment obrigatória não está aprovada. Caso contrário, inicia em `IN_PRODUCTION`.

## Arquivos

- storage privado e tenant-aware;
- links temporários;
- nome original não define path;
- checksum identifica versão;
- arquivo referenciado por OS não é excluído fisicamente;
- política global de retenção terá ADR próprio.

## Critérios de conformidade

- aplicação possui quantidade, posição e arte independentes;
- uma arte pode ser compartilhada;
- OS preserva instruções completas;
- catálogo alterado não muda OS;
- início é bloqueado por arte pendente;
- revisão é auditável e append-only.

## Histórico

- 18/07/2026 — decisão aceita com ArtworkAssignment, posição estrutural e WorkOrderRevision.
