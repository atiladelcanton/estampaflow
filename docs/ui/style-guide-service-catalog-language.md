# Linguagem e simplicidade do catálogo de serviços

## Princípio

A complexidade técnica permanece no sistema. O usuário da estamparia deve configurar apenas o necessário para trabalhar.

## Linguagem da interface

- `schema` é termo interno e não deve aparecer para o usuário operacional;
- usar **Campos**, **Campos do serviço** e **Salvar alterações**;
- não exibir `DRAFT`, `ACTIVE`, `RETIRED`, versão, publicação ou rascunho no fluxo normal;
- explicar com exemplos concretos: largura, altura, cores, tecido, acabamento e pontos;
- não pedir identificadores internos; eles são gerados automaticamente pelo sistema.

## Divulgação progressiva

- serviços padrão devem chegar prontos;
- mostrar sugestões comuns com inclusão em um clique;
- manter nome, tipo de resposta e obrigatoriedade no fluxo principal;
- colocar unidade, valor inicial e impacto no preço dentro de **Mais opções**;
- histórico e versionamento são automáticos e aparecem apenas em ferramentas administrativas futuras;
- permitir serviço sem campos extras quando quantidade e posição forem suficientes.

## Ações recomendadas

- **Campos**: abre a configuração operacional;
- **Adicionar campo personalizado**: cria somente o que não existe nas sugestões;
- **Salvar alterações**: cria, valida e coloca a nova configuração em uso nos bastidores.

O usuário nunca precisa preparar uma versão, publicar uma configuração ou entender a estrutura interna do catálogo.

## Sugestões de campos

- exibir sugestões como chips compactos, nunca como cards altos;
- manter o rótulo em uma única linha;
- usar um sinal de adição pequeno dentro de um círculo, sem SVG dependente de build;
- permitir quebra apenas entre os chips para funcionar bem em telas menores;
- usar linguagem de ação: **Adicionar campos comuns**.
