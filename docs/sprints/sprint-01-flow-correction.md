# Sprint 1 — Correção do onboarding, convites e domínio central

## Motivo

A validação de runtime identificou três divergências de produto:

- convite sem evidência clara de envio;
- link de convite protegido por login e sem cadastro contextual;
- cadastro comum exigindo criação manual posterior do tenant;
- domínio central exposto a usuários operacionais.

## Correções

- cadastro cria automaticamente tenant, Owner e trial;
- redirect direto ao subdomínio;
- central dashboard exclusivo do Platform Admin;
- convite público para usuário novo ou existente;
- login retorna ao convite;
- conta criada pelo convite não cria tenant adicional;
- log estruturado e AuditLog do envio;
- testes de onboarding, fronteira central e convite.

## Fora do escopo

- cobrança real;
- painel financeiro completo;
- RBAC granular;
- seleção visual para usuário com múltiplos tenants.
