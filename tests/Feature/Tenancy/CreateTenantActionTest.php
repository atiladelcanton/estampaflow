<?php

use App\Application\Tenancy\Actions\CreateTenantAction;
use App\Application\Tenancy\Data\CreateTenantData;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates tenant domain and owner membership atomically', function (): void {
    $owner = User::factory()->create();

    $tenant = app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Estamparia Teste',
        slug: 'estamparia-teste',
        domain: 'teste.estamparia.test',
        owner: $owner,
    ));

    expect((string) $tenant->getTenantKey())->toBeUlid()
        ->and($tenant->primaryDomain())->toBe('teste.estamparia.test');

    $this->assertDatabaseHas('tenant_memberships', [
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER->value,
        'status' => MembershipStatus::ACTIVE->value,
    ]);

    expect(AuditLog::query()->where('action', 'tenant.created')->exists())->toBeTrue();
});
