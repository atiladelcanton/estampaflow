# Sprint 3 — Correção das opções de Sublimação e Bordado

Este patch é cumulativo: inclui a correção anterior do PHPStan e a correção das opções que pareciam clicáveis, mas eram apenas cartões informativos.

## Sublimação

- A primeira etapa agora permite marcar e desmarcar os tipos oferecidos.
- A tabela exibe somente as categorias escolhidas.
- Pelo menos uma categoria deve permanecer selecionada.
- A escolha é persistida na versão da tabela.

## Bordado

- A primeira etapa permite escolher as faixas de pontos utilizadas.
- A tabela exibe somente as faixas escolhidas.
- O usuário escolhe se a matriz/digitalização está incluída no preço por peça ou é cobrada separadamente.
- A cobrança separada aparece no teste e é persistida.

## Aplicação

```bash
unzip -o estampaflow-sprint-3-guided-options-fix.zip -d .
chmod +x scripts/validate-sprint-3-guided-options-fix.sh
./scripts/validate-sprint-3-guided-options-fix.sh
make quality
```

Não há migration e os preços existentes são preservados.
