# Sprint 1 — Alinhamento dos testes de autenticação

## Motivo

Os testes herdados da Sprint 0 ainda esperavam:

- cadastro sem nome da estamparia;
- redirecionamento genérico para `/dashboard`;
- seletor de ambientes para usuário comum;
- acesso de qualquer usuário autenticado às telas do domínio central.

Essas expectativas foram substituídas pelo fluxo aprovado na Sprint 1.

## Comportamento validado

- Platform Admin autentica e entra no domínio central;
- Owner autentica no domínio central e segue para o próprio tenant;
- cadastro público cria usuário, tenant, domínio e membership Owner;
- cadastro redireciona diretamente ao tenant;
- telas centrais são exclusivas do Platform Admin;
- usuário comum recebe 403 no dashboard central.

## Banco de dados

Nenhuma migration ou alteração de dados foi adicionada por este patch.
