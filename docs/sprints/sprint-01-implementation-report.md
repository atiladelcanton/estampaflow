# Relatório de Implementação — Sprint 1

## Classificação

| Item | Estado |
|---|---|
| Tenant persistido | IMPLEMENTED |
| Domínio por tenant | IMPLEMENTED |
| Memberships | IMPLEMENTED |
| Convites | IMPLEMENTED |
| TenantContext stancl | IMPLEMENTED |
| Isolamento de rotas | IMPLEMENTED |
| Gestão visual da equipe | IMPLEMENTED |
| Custom domains | PLANNED |
| RBAC granular | PLANNED |
| Impersonação | DEPRECATED no MVP |
| Billing | PLANNED — Sprint 9 |

## Segurança

- tenant não vem do formulário;
- host é resolvido na middleware;
- membership ativa é obrigatória;
- rota de Owner possui middleware adicional;
- token de convite não é persistido em texto puro;
- duplicidade pendente usa unique constraint;
- último Owner é protegido;
- alterações são auditadas.

## Validação pendente no ambiente local

```bash
composer install
php artisan migrate --seed
npm run build
php artisan test
composer quality
```

## Riscos conhecidos

- o desenvolvimento local depende das entradas em `/etc/hosts`;
- envio de e-mail utiliza Mailpit local e fila database;
- trial é persistido, mas o gate completo de assinatura pertence à Sprint 9;
- o pacote é usado para identificação; o isolamento de Models operacionais será aplicado a partir da Sprint 2.

## Correções de validação local — Livewire e tenancy

- o endpoint hash-based de update do Livewire 4 passou a executar `InitializeTenancyForRequest`;
- middlewares de membership e Owner foram registrados como persistentes;
- o middleware encerra contexto residual antes de resolver o domínio atual;
- testes de domínio passaram a usar URL absoluta, evitando que o host seja sobrescrito pelo cliente de testes;
- incluído teste do fluxo de convite pelo componente Livewire.


## Infraestrutura assíncrona

- Mailpit local implementado;
- filas database implementadas;
- convites e provisionamento separados por queue;
- cron compatível com hospedagem compartilhada documentado;
- DNS de produção definido como wildcard.
