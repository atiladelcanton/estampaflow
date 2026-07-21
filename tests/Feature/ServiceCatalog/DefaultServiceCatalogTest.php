<?php

declare(strict_types=1);

use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Services\DefaultServiceCatalogService;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('cria os quatro serviços padrão de forma idempotente', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Defaults',
        'slug' => 'defaults',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);
    $owner = User::factory()->create();
    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($owner): void {
        $service = app(DefaultServiceCatalogService::class);
        $service->createDefaultsFor($owner);

        expect(ServiceType::query()->orderBy('sort_order')->pluck('code')->all())->toBe([
            'DTF',
            'SILK',
            'SUBLIMACAO',
            'BORDADO',
        ]);

        $dtf = ServiceType::query()->where('code', 'DTF')->firstOrFail();
        $dtf->forceFill([
            'name' => 'DTF personalizado',
            'description' => 'Configuração personalizada pelo tenant.',
            'sort_order' => 99,
        ])->save();

        $service->createDefaultsFor($owner);

        expect(ServiceType::query()->count())->toBe(4)
            ->and(ServiceType::query()->whereIn('code', ['DTF', 'SILK', 'SUBLIMACAO', 'BORDADO'])->count())->toBe(4)
            ->and(ServiceType::query()->whereIn('code', ['D_T_F', 'S_I_L_K', 'S_U_B_L_I_M_A_C_A_O', 'B_O_R_D_A_D_O'])->count())->toBe(0)
            ->and(ServiceType::query()->where('active', true)->count())->toBe(4)
            ->and($dtf->fresh()?->name)->toBe('DTF personalizado')
            ->and($dtf->fresh()?->description)->toBe('Configuração personalizada pelo tenant.')
            ->and($dtf->fresh()?->sort_order)->toBe(99);
    });
});
