# Correção — sugestões de campos e PHPStan

## Interface

As sugestões deixam de usar botões altos com ícones SVG e passam a usar chips compactos:

- altura uniforme;
- texto em uma linha;
- pequeno `+` circular;
- quebra responsiva entre itens;
- estados de hover e foco consistentes.

## PHPStan

- remove `array_values()` de uma propriedade já declarada como `list`;
- separa corretamente as anotações `@param` e `@return` de `selectPreset()`.

Não há migration nem alteração de dados.
