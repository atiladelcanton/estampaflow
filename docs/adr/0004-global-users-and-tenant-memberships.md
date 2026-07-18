# ADR 0004: Usuários globais e vínculos com tenants

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto, arquitetura e segurança
- **Contexto relacionado:** autenticação, convites, papéis e troca de tenant

## Contexto

Duplicar a identidade da mesma pessoa por tenant dificulta login, recuperação de senha, auditoria e suporte a múltiplas empresas.

## Decisão

Usar identidade global em `users` e acesso a tenants por `tenant_memberships`.

## Modelo

### `users`

- identidade e autenticação;
- e-mail normalizado e globalmente único;
- não possui `tenant_id`;
- pode participar de vários tenants.

### `tenant_memberships`

- `tenant_id`;
- `user_id`;
- `role`: `OWNER` ou `USER`;
- `status`: `ACTIVE`, `SUSPENDED` ou `REVOKED`;
- `invited_by`;
- `joined_at`;
- timestamps.

Constraint:

```text
unique(tenant_id, user_id)
```

### `tenant_invitations`

Estados:

- `PENDING`;
- `ACCEPTED`;
- `EXPIRED`;
- `REVOKED`.

O convite contém e-mail normalizado, papel, token com hash, expiração e autor.

Não pode existir mais de um convite `PENDING` para o mesmo tenant e e-mail.

## Platform Admin

No MVP, o papel global será um campo controlado `users.is_platform_admin`.

Ele não concede membership automática nem acesso operacional ao tenant.

## Autenticação

Um único guard web.

O acesso ao ambiente exige:

1. usuário autenticado;
2. tenant resolvido;
3. membership `ACTIVE`;
4. trial ou assinatura permitindo acesso;
5. Policy do recurso.

## Papéis no tenant

MVP:

- `OWNER`;
- `USER`.

RBAC granular fica fora do MVP e exigirá ADR próprio.

## Regras do Owner

- todo tenant possui ao menos um Owner ativo;
- último Owner não pode ser removido, suspenso ou rebaixado;
- transferência de propriedade é Action auditada;
- billing e usuários são controlados pelo Owner.

## Convites

- usuário existente autentica e aceita;
- usuário novo cria conta, verifica e-mail e aceita;
- token é de uso único, com hash e expiração;
- e-mail do aceite deve corresponder ao convite.

## Exclusão e anonimização

- membership é revogada, não apagada quando houver auditoria;
- usuário com histórico não é fisicamente removido pelo fluxo comum;
- pedido legal de exclusão usa anonimização controlada e preserva integridade mínima de auditoria;
- política detalhada de retenção terá ADR próprio.

## Starter kit

O recurso opcional de Teams do starter kit permanecerá desabilitado para não criar estrutura paralela a `tenants` e `tenant_memberships`.

## Auditoria

Registrar convite, aceite, suspensão, revogação, mudança de papel e transferência de propriedade.

## Critérios de conformidade

- um login participa de vários tenants;
- último Owner é protegido;
- suspensão em um tenant não afeta outro;
- Platform Admin não recebe acesso operacional automático;
- convite funciona para usuário novo e existente.

## Histórico

- 18/07/2026 — decisão aceita com usuário global, memberships, dois papéis no MVP e Teams desabilitado.
