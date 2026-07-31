# Domínio de Precificação

A Sprint 3 implementa um motor declarativo, versionado e uma experiência guiada para o Owner.

## Princípios

- não executa PHP, JavaScript, SQL ou fórmulas fornecidas pelo tenant;
- usa `Money` em centavos e `Rate` em `DECIMAL(18,8)` quando houver coeficientes;
- mantém a versão anterior ao salvar uma nova configuração;
- a mesma entrada produz o mesmo resultado;
- empate entre regras retorna `AMBIGUOUS`;
- preço automático ausente bloqueia o fluxo; modo híbrido solicita valor manual;
- toda consulta é isolada pelo `TenantContext`;
- a interface não expõe estratégias, prioridades, condições JSON ou taxas difíceis de interpretar.

## Estratégias internas

- `UNIT`: preço fixo por unidade;
- `QUANTITY_TIER`: faixas de quantidade;
- `AREA`: largura × altura × taxa conhecida;
- `MATRIX`: regras por quantidade e parâmetros;
- `STITCH_RANGE`: quantidade de pontos × taxa por mil pontos;
- `ROLL_LENGTH`: material comprado por comprimento, com encaixe e arredondamento por incremento de compra.

## DTF comprado por metro

A configuração guiada solicita:

- custo do metro;
- largura útil;
- valor de aplicação por peça;
- acréscimo opcional sobre o material;
- espaçamento e perda opcional.

O `RollMaterialPricingCalculator`:

1. valida largura e altura da arte;
2. calcula o encaixe normal;
3. testa a arte girada quando permitido;
4. escolhe o menor comprimento;
5. aplica espaçamento e perda;
6. arredonda para metros inteiros, com mínimo de um metro;
7. soma material, acréscimo e aplicação;
8. retorna breakdown explicável.

Insumos de produção própria — filme, tinta, poliamida, energia e perdas — pertencem à Sprint 4.

## Silk por quantidade e cores

A configuração guiada cria regras `MATRIX` para cada célula da tabela:

```text
faixa de quantidade × número de cores = preço por peça
```

Settings adicionais controlam:

- preparação incluída ou cobrada por tela/cor;
- base branca como cor adicional, adicional por peça ou sem ajuste;
- adicionais por sistema de tinta;
- adicionais por efeito/acabamento.

O motor normaliza o número efetivo de cores antes de escolher a regra e soma componentes opcionais de forma determinística.

## Sublimação por quantidade e tipo

A configuração guiada usa regras `MATRIX` para combinar faixa de quantidade com categorias comerciais de aplicação localizada ou total. O usuário informa preços por peça; o motor mantém modalidade e tipo da peça como condições internas.

## Bordado por quantidade e pontos

A configuração guiada usa regras `MATRIX` para combinar faixa de quantidade com as faixas de pontos existentes no schema do serviço. A matriz/digitalização opcional é armazenada como preparação única da regra.

## UX

- quatro etapas curtas;
- uma decisão por etapa;
- opções avançadas recolhidas;
- ajuda curta próxima dos campos;
- modal opcional com exemplos;
- prévia imediata antes de salvar;
- alerta visual para resultados muito discrepantes;
- versão anterior preservada.

Os quatro serviços padrão — DTF, Silk, Sublimação e Bordado — possuem fluxo guiado. Serviços customizados continuam usando o editor genérico como fallback.

### Seleções da configuração guiada

Os assistentes de Sublimação e Bordado filtram a matriz comercial conforme as opções escolhidas na primeira etapa. Essas seleções são convertidas em regras declarativas e persistidas em `settings`, sem expor a estratégia interna ao usuário.
