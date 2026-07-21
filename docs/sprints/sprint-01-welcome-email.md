# Sprint 1 — E-mail de boas-vindas

## Objetivo

Confirmar ao novo Owner que a conta e a estamparia foram criadas, entregando o endereço exclusivo e orientando o primeiro acesso.

## Fluxo

1. cadastro público cria usuário, tenant, domínio, membership e trial;
2. `SendTenantWelcomeEmailJob` entra na fila `mail` após commit;
3. o worker envia `TenantWelcomeMail` pelo SMTP configurado;
4. localmente, o e-mail aparece no Mailpit;
5. sucesso ou falha gera log estruturado e `AuditLog`.

## Conteúdo

- nome do usuário;
- nome da estamparia;
- URL exclusiva;
- e-mail de acesso;
- data final do trial;
- botão de login;
- link para redefinir senha.

## Segurança

A senha nunca é armazenada em texto legível, recuperada ou enviada por e-mail. A mensagem informa que deve ser usada a senha escolhida no cadastro e fornece recuperação de senha.

## Eventos de auditoria

- `tenant.welcome_email.queued`;
- `tenant.welcome_email.sent`;
- `tenant.welcome_email.failed`.

## Validação local

1. abrir `http://app.estamparia.test:8000/register`;
2. cadastrar uma nova estamparia;
3. acompanhar o worker com `make queue-logs`;
4. abrir `http://localhost:8025`;
5. validar HTML, texto, links e destinatário.
