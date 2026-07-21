# Campos do serviço — formulário simples

A tela de campos do serviço não usa Livewire.

## Motivo

É uma configuração de baixa frequência. Adicionar, remover e ordenar campos é
feito localmente com Alpine.js, sem requisição ao servidor a cada clique. O
servidor só é acionado ao escolher **Salvar alterações**.

Isso reduz pontos de falha em subdomínios, torna a interação imediata e mantém a
tela compreensível para usuários com pouca familiaridade técnica.

## Padrão visual

As sugestões usam uma grade de cartões compactos e uniformes. Cada opção possui:

- ação `+` visível;
- nome curto;
- explicação de uma linha;
- remoção automática da grade depois de adicionada.

A complexidade de versionamento continua interna.
