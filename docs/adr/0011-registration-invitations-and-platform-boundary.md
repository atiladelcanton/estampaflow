# ADR 0011: Cadastro automático, convites completos e fronteira do Platform Admin

- **Status:** ACCEPTED
- **Data da decisão:** 18/07/2026
- **Decisores:** Produto e arquitetura

## Decisão

1. O cadastro público solicita nome do responsável, nome da estamparia, e-mail e senha.
2. O mesmo caso de uso cria `User`, `Tenant`, domínio, membership `OWNER`, trial e auditoria.
3. O usuário é direcionado diretamente ao domínio da estamparia.
4. Usuários comuns não possuem tela central para criar ou selecionar ambientes.
5. O dashboard de `app.*` é exclusivo do Platform Admin para clientes SaaS, cobrança, métricas e suporte.
6. Login comum redireciona para um tenant ativo; Platform Admin vai para o dashboard central.
7. Convites são acessíveis sem autenticação.
8. Usuário existente autentica com o e-mail convidado e aceita.
9. Usuário novo cria sua conta dentro do convite, sem criar outro tenant, e entra no tenant convidante.
10. Envio de convite registra sucesso ou falha em log e auditoria. Em ambiente local, a URL fica visível para teste.

## Consequências

- onboarding reduzido a uma etapa;
- separação clara entre cliente e operador SaaS;
- convite deixa de depender de cadastro prévio;
- contas órfãs não recebem acesso ao domínio central;
- múltiplos tenants por usuário continuam suportados pelo modelo, mas a seleção visual será definida quando houver necessidade real.
