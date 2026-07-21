# ADR 0012: Fila database, Mailpit e provisionamento de subdomínios

- **Status:** ACCEPTED
- **Data da decisão:** 21/07/2026
- **Decisores:** Produto, arquitetura e infraestrutura

## Contexto

O envio síncrono de e-mails aumenta o tempo de resposta e dificulta validar mensagens localmente. A hospedagem inicial pode ser compartilhada e não oferece Redis ou processos permanentes gerenciados.

Criar um registro DNS para cada tenant também não é necessário quando o ambiente de produção utiliza DNS wildcard.

## Decisão

- usar `database` como driver inicial de filas;
- separar filas `provisioning`, `mail` e `default`;
- usar Mailpit somente em desenvolvimento local;
- executar worker permanente no Docker;
- em hospedagem compartilhada, executar `queue:work --stop-when-empty` pelo cron;
- enfileirar convites após commit;
- criptografar o job de convite porque contém temporariamente o token em texto puro;
- manter apenas o hash do token na tabela de convites;
- criar o registro de domínio na transação do onboarding e provisioná-lo assincronamente;
- em produção, usar DNS wildcard para `*.dominio`;
- em desenvolvimento, o job gera um artefato `.hosts` e o comando `make add-host` continua sendo a etapa que altera o host local.

## Consequências

### Positivas

- respostas HTTP mais rápidas;
- e-mails inspecionáveis no Mailpit;
- arquitetura compatível com hospedagem compartilhada;
- retries e failed jobs auditáveis;
- provisionamento possui estado explícito.

### Negativas

- cron pode adicionar atraso de até um minuto na hospedagem compartilhada;
- `/etc/hosts` do computador não pode ser alterado com segurança por um container;
- o worker precisa ser monitorado;
- o banco passa a armazenar a fila.

## Critérios de conformidade

- convite entra na fila `mail`;
- payload sensível do convite é criptografado;
- domínio entra na fila `provisioning` após commit;
- Mailpit recebe mensagens localmente;
- falhas aparecem em `failed_jobs` e AuditLog;
- produção não depende de criação individual de DNS.
