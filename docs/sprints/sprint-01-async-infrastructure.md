# Sprint 1 — Mailpit, filas e provisionamento de domínio

## Implementado

- Mailpit no Docker local em `http://localhost:8025`;
- SMTP local na porta 1025;
- fila `database` com `after_commit`;
- container `queue` permanente no Docker;
- filas separadas `provisioning`, `mail` e `default`;
- job criptografado para envio de convites;
- estados de provisionamento no Model Domain;
- retries, backoff, failed jobs e auditoria;
- comando `domain:provision-pending`;
- exemplo de cron para hospedagem compartilhada.

## Limite técnico

O container não altera automaticamente o `/etc/hosts` do computador. No ambiente local, o job gera um arquivo em `storage/app/private/domain-provisioning` como evidência e o desenvolvedor executa `make add-host DOMAIN=...`.

Em produção, não haverá criação individual de DNS: um registro wildcard direcionará todos os subdomínios ao EstampaFlow.

## Cron sugerido

```cron
* * * * * cd /home/USUARIO/estampaflow && php artisan queue:work database --queue=provisioning,mail,default --stop-when-empty --tries=3 --timeout=90 --no-interaction >> /dev/null 2>&1
* * * * * cd /home/USUARIO/estampaflow && php artisan schedule:run --no-interaction >> /dev/null 2>&1
```
