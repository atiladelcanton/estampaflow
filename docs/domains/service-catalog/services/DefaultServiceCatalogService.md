# Service: DefaultServiceCatalogService

## Objetivo
Criar DTF, Silk, Sublimação e Bordado no onboarding de cada tenant.

## Idempotência
A criação usa a `CreateServiceTypeAction` em modo idempotente. O código é verificado dentro da própria Action, eliminando a janela entre uma consulta prévia e a criação.

Quando um serviço com o mesmo código já existe no tenant:

- o registro existente é retornado;
- nenhum novo schema é criado;
- nome, descrição, ordem e parâmetros não são sobrescritos;
- o cadastro manual continua rejeitando código duplicado.

## Transação
É chamado no cadastro do tenant ou pelo comando de bootstrap. A criação inicial e a auditoria permanecem sob responsabilidade das Actions.

## Método

### createDefaultsFor(User $actor): Collection
Cria, configura e ativa os quatro serviços padrão quando ainda não existem. Chamadas posteriores retornam os registros existentes sem alterar personalizações.
