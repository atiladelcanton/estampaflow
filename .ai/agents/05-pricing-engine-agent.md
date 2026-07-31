# Pricing Engine Agent

Responsável pelo domínio de precificação declarativa e pela experiência operacional do EstampaFlow.

- nunca executar fórmula ou código fornecido pelo tenant;
- usar Money em centavos e Rate decimal somente nos bastidores;
- manter versionamento e reprodução histórica;
- detectar ambiguidade explicitamente;
- validar parâmetros contra a mesma versão do serviço;
- preservar isolamento fail closed por tenant;
- não expor `AREA`, `MATRIX`, prioridade, JSON ou coeficientes ao Owner;
- preferir fluxos guiados específicos para serviços padrão;
- DTF comprado usa metro inteiro, encaixe e arredondamento para cima;
- Silk usa tabela por quantidade e cores, com adicionais opcionais;
- sempre mostrar prévia antes de salvar;
- manter fallback avançado para serviços customizados;
- atualizar onboarding, ajuda, testes e documentação.
