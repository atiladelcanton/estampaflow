# Domain: Tenancy

## Objetivo

Garantir que identidade, resolução de ambiente e autorização de acesso sejam consistentes antes da implementação dos módulos operacionais.

## Linguagem

- **User:** identidade global;
- **Tenant:** estamparia;
- **Membership:** vínculo do usuário com a estamparia;
- **Owner:** proprietário com gestão de usuários;
- **Invitation:** convite pendente;
- **Central domain:** login e seletor;
- **Tenant domain:** operação isolada.

## Models

### Tenant

Extende o model de infraestrutura do `stancl/tenancy`, usa `UlidTenantIdGenerator` e mantém colunas de negócio explícitas.

Métodos públicos:

- `memberships()`;
- `users()`;
- `invitations()`;
- `isActive()`;
- `isTrialActive()`;
- `primaryDomain()`.

### TenantMembership

- `tenant()`;
- `user()`;
- `inviter()`;
- `isActive()`;
- `isOwner()`.

### TenantInvitation

- `tenant()`;
- `inviter()`;
- `acceptedBy()`;
- `isPending()`;
- `markExpired()`.

## Actions

### CreateTenantAction

Cria tenant, domínio, membership Owner e AuditLog em uma transação.

### InviteTenantUserAction

Valida Owner, impede duplicidade, gera token, persiste apenas hash e envia notificação after commit.

### AcceptTenantInvitationAction

Valida token, expiração e e-mail; cria membership e consome o convite.

### RevokeTenantInvitationAction

Revoga convite pendente, libera nova emissão e registra auditoria.

### ChangeTenantMembershipAction

Altera papel/status e protege o último Owner.

### TransferTenantOwnershipAction

Promove o novo Owner e rebaixa o Owner atual em uma transação auditada.

## Middleware

- `InitializeTenancyByDomain`;
- `PreventAccessFromCentralDomains`;
- `EnsureActiveTenantMembership`;
- `EnsureTenantOwner`.

## Invariantes

1. usuário é global;
2. tenant é resolvido pelo host;
3. membership ativa é obrigatória;
4. último Owner não pode ser removido;
5. convite pendente é único por tenant/e-mail;
6. token é armazenado em hash;
7. Platform Admin não recebe membership automaticamente.

## Testes

- `CreateTenantActionTest`;
- `TenantAccessTest`;
- `InvitationFlowTest`;
- `OwnerProtectionTest`.
