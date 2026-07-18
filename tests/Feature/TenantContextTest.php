<?php

use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Support\Tenancy\MissingTenantContextException;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('tenant context falha fechado sem tenant', function (): void {
    expect(fn () => app(TenantContext::class)->currentId())
        ->toThrow(MissingTenantContextException::class);
});

test('tenant context inicializa e restaura o estado após execução', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Context Test',
        'slug' => 'context-test',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $context = app(TenantContext::class);
    $tenantId = new TenantId((string) $tenant->getTenantKey());

    $result = $context->run($tenantId, function () use ($context): string {
        return (string) $context->currentId();
    });

    expect($result)->toBe((string) $tenantId)
        ->and($context->hasTenant())->toBeFalse();
});
