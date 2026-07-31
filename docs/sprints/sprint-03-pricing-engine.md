# Sprint 3 — Motor de Precificação Guiado

## Objetivo

Permitir que cada tenant registre sua forma de cobrança sem estudar fórmulas, mantendo cálculo determinístico, histórico e flexibilidade para serviços customizados.

## Entregas

- tabelas versionadas de preço e regras;
- `Money`, `Rate` e arredondamento centralizado;
- condições declarativas e detecção de ambiguidade;
- nova estratégia `ROLL_LENGTH`;
- fluxo guiado de DTF comprado por metro inteiro;
- fluxo guiado de Silk por quantidade e cores;
- fluxo guiado de Sublimação por quantidade e tipo;
- fluxo guiado de Bordado por quantidade e faixa de pontos;
- preparação por tela/cor;
- base branca configurável;
- adicionais de tinta e efeito;
- prévia imediata antes de salvar;
- ajuda inline e modal contextual;
- fallback avançado para serviços customizados;
- auditoria `service_pricing.activated`;
- isolamento por tenant e acesso de Owner;
- onboarding, tutorial e Central de Ajuda atualizados.

## DTF

O usuário informa somente custo do metro, largura útil, aplicação e acréscimo opcional. O sistema calcula encaixe, rotação, comprimento, arredondamento para metros inteiros, sobra e preço por peça.

## Silk

O usuário preenche uma tabela comercial por faixa de quantidade e número de cores. Preparação, base branca, sistema de tinta e efeitos são opcionais e aparecem em linguagem operacional.

## Sublimação

O usuário preenche uma tabela comercial por faixa de quantidade e tipo: localizada pequena, média, grande ou sublimação total. O sistema transforma cada célula em regra versionada sem expor parâmetros técnicos.

## Bordado

O usuário preenche uma tabela por faixa de quantidade e faixa de pontos. A criação/digitalização da matriz pode ser cobrada uma vez por pedido. Linha, entretela e tempo de máquina permanecem para a Sprint 4.

## Compatibilidade

Uma configuração criada pelo editor anterior é preservada. A nova interface mostra um aviso e cria uma nova versão somente após o Owner revisar e salvar.

## Fora do escopo

- custo detalhado de filme, tintas, poliamida, telas e emulsão;
- ledger de insumos e estoque, previsto na Sprint 4;
- termômetro e insights com IA, previstos após a base de custos;
- orçamento e aprovação, previstos nas Sprints 5 e 6;
- fórmulas livres configuradas pelo tenant.

## Validação manual

### DTF

1. informe R$ 40,00 por metro e 58 cm úteis;
2. teste 10 artes de 20 × 30 cm;
3. confirme compra de 2 metros;
4. confirme material de R$ 80,00;
5. adicione aplicação e confira o valor por peça.

### Silk

1. escolha preparação separada;
2. informe R$ 30,00 por tela;
3. preencha uma faixa com 1, 2 e 3 cores;
4. configure base branca como cor adicional;
5. adicione Plastisol e Puff;
6. teste 20 peças, 2 cores e base branca;
7. confira a conta completa antes de salvar.

### Sublimação

1. preencha os preços por quantidade e tipo;
2. teste uma aplicação localizada;
3. teste uma peça de sublimação total;
4. confirme o total antes de salvar.

### Bordado

1. preencha os preços por quantidade e faixa de pontos;
2. informe a matriz/digitalização quando cobrada separadamente;
3. teste um pedido;
4. confirme preço por peça e total.

## Correção de interação — Sublimação e Bordado

A primeira etapa dos assistentes de Sublimação e Bordado passou a usar opções realmente selecionáveis.

- Sublimação permite marcar somente os tipos oferecidos pelo tenant; a tabela exibe apenas essas colunas.
- Bordado permite marcar as faixas de pontos utilizadas e escolher se a matriz/digitalização está incluída ou é cobrada separadamente.
- Pelo menos uma opção deve permanecer marcada.
- As escolhas são persistidas nas configurações versionadas da tabela.
