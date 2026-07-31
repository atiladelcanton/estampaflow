# Sprint 3 — Precificação guiada

## Aplicação

```bash
unzip -o estampaflow-sprint-3-guided-all-services-update.zip -d .
chmod +x scripts/validate-sprint-3-guided-pricing.sh
./scripts/validate-sprint-3-guided-pricing.sh
```

Depois:

```bash
make quality
```

## Acesso

No domínio do tenant:

```text
/configuracoes/precos
```

## DTF

- custo por metro;
- largura útil;
- compra somente em metros inteiros;
- melhor encaixe entre posição normal e girada;
- aplicação por peça;
- acréscimo opcional sobre material;
- prévia com comprimento, compra, sobra e preço por peça.

## Silk

- tabela por faixa de quantidade e número de cores;
- preparação incluída ou cobrada por tela/cor;
- base branca configurável;
- adicionais opcionais por tinta e efeito;
- prévia completa antes de salvar.

## Sublimação

- tabela por faixa de quantidade;
- localizada pequena, média e grande;
- sublimação total;
- prévia imediata por peça e total.

## Bordado

- tabela por faixa de quantidade e faixa de pontos;
- matriz/digitalização opcional cobrada uma vez;
- prévia do total e média por peça.

## Compatibilidade

A configuração anterior é preservada. O novo modelo cria outra versão somente ao salvar.

Não há migration neste patch.
