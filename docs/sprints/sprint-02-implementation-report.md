# Relatório de Implementação — Sprint 2

## Classificação

| Item | Estado |
|---|---|
| ServiceType tenant-aware | IMPLEMENTED |
| Schema versionado | IMPLEMENTED |
| Parâmetros configuráveis | IMPLEMENTED |
| Defaults por tenant | IMPLEMENTED |
| UI de catálogo | IMPLEMENTED |
| Auditoria | IMPLEMENTED |
| Motor de preços | PLANNED — Sprint 3 |
| Uso em orçamento | PLANNED — Sprint 5 |

## Decisões aplicadas

- nenhum enum contém a lista de serviços;
- código do serviço é estável e único por tenant;
- versão ativa é imutável;
- alteração incompatível cria nova versão;
- `applied_quantity` e posição não pertencem ao schema dinâmico;
- serviço só pode ser ativado quando possui schema ativo;
- novo tenant recebe quatro serviços padrão.

## Riscos restantes

- ainda não existe bloqueio por uso real, pois Quote e Production ainda não foram implementados;
- a Sprint 3 deverá validar compatibilidade entre `affects_pricing` e regras de preço;
- schemas complexos poderão exigir UI adicional no futuro, sem quebrar o modelo atual.

## Refinamento visual pós-validação

A listagem do catálogo foi convertida de cards para tabela operacional, por ser uma tela administrativa de baixa frequência. Também foram corrigidos o redimensionamento do conteúdo após recolher a sidebar e os tooltips dos itens exibidos somente por ícone.

## Simplificação de adesão

Após validação com o produto, o editor técnico foi substituído por uma experiência operacional:

- serviços padrão chegam com campos prontos;
- sugestões comuns podem ser adicionadas em um clique;
- o identificador interno é gerado automaticamente;
- detalhes pouco usados ficam em **Mais opções**;
- existe somente a ação **Salvar alterações**;
- versionamento, rascunho e ativação continuam preservados internamente;
- a rota pública da tela passou de `/schema` para `/campos`, mantendo redirecionamento de compatibilidade.

A decisão preserva flexibilidade arquitetural sem transferir complexidade para a estamparia.

