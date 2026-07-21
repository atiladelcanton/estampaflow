# Sprint 1 — Relatório de estabilização

- **Data:** 18/07/2026
- **Status:** pronto para validação local
- **Objetivo:** eliminar inconsistências reveladas por PHPStan antes da Sprint 2.

## Causas corrigidas

1. propriedades Eloquent com casts de enum e data eram inferidas como `string`;
2. a relação `domains` vinha apenas de trait dinâmica e não era reconhecida pelo Larastan;
3. o gerador ULID implementava um contrato inexistente;
4. o PHPStan mantinha uma exceção obsoleta;
5. o fluxo de convite não auditava falha de entrega;
6. helpers globais duplicados causavam colisão no Pest.

## Alterações

- propriedades de tenancy documentadas com tipos reais;
- casts mantidos como fonte de hidratação;
- `Tenant::domains()` declarado explicitamente;
- `UniqueIdentifierGenerator` usado conforme API do stancl/tenancy 3.x;
- `phpstan.neon` limpo;
- memória do PHPStan fixada em 1 GB no Composer;
- Actions de convite, membership e ownership ajustadas;
- AuditLog de criação, entrega, falha e aceite de convite;
- testes de casts, domínios, gerador ULID e auditoria;
- helpers dos testes de convite com nomes exclusivos.

## Evidências esperadas

```bash
docker compose run --rm app composer types:check
docker compose run --rm app php artisan test tests/Feature/Tenancy
make quality
docker compose run --rm node npm run build
```

## Critério de fechamento

A Sprint 1 só deve ser marcada como concluída quando todos os comandos acima estiverem verdes.
