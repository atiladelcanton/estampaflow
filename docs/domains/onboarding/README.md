# Domínio de Onboarding

## Estrutura

- `UserOnboardingProgress`: progresso por usuário e tenant;
- `OnboardingProgressService`: conclusão, dispensa e reinício;
- `OnboardingRegistry`: tutoriais e artigos definidos em `config/onboarding.php`;
- wizard inicial do Owner;
- tutorial contextual sem dependência de pacote externo;
- Central de Ajuda orientada por tarefas.

## Princípios

- curto e opcional;
- não repetir depois de concluído ou dispensado;
- específico por papel;
- recursos avançados ficam fora do fluxo principal;
- pode ser revisto a qualquer momento.

## Versionamento

Cada tutorial possui `key` e `version`. Uma nova versão só deve ser criada quando a mudança justificar apresentar novamente a orientação. Alterações normais de conteúdo não devem interromper usuários existentes.
