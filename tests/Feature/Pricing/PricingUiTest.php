<?php

declare(strict_types=1);

use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Services\DefaultServiceCatalogService;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Domain;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('permite ao owner configurar preços e bloqueia usuário comum', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(), 'name' => 'Pricing UI', 'slug' => 'pricing-ui',
        'status' => TenantStatus::ACTIVE, 'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7), 'data' => [],
    ]);
    Domain::query()->create(['tenant_id' => $tenant->getTenantKey(), 'domain' => 'pricing-ui.estamparia.test']);
    $owner = User::factory()->create();
    $user = User::factory()->create();
    foreach ([[$owner, TenantRole::OWNER], [$user, TenantRole::USER]] as [$member, $role]) {
        TenantMembership::query()->create([
            'tenant_id' => $tenant->getTenantKey(), 'user_id' => $member->getKey(),
            'role' => $role, 'status' => MembershipStatus::ACTIVE, 'joined_at' => now(),
        ]);
    }

    $serviceId = app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($owner): string {
        app(DefaultServiceCatalogService::class)->createDefaultsFor($owner);

        return (string) ServiceType::query()->where('code', 'SILK')->value('id');
    });

    $this->actingAs($owner)
        ->get('http://pricing-ui.estamparia.test/configuracoes/precos')
        ->assertOk()
        ->assertSee('Precificação')
        ->assertSee('Silk Screen');

    $this->actingAs($owner)
        ->get('http://pricing-ui.estamparia.test/configuracoes/servicos/'.$serviceId.'/precos')
        ->assertOk()
        ->assertSee('Preços de Silk Screen')
        ->assertSee('Configuração guiada')
        ->assertSee('Teste um pedido antes de salvar');

    $this->actingAs($user)
        ->get('http://pricing-ui.estamparia.test/configuracoes/precos')
        ->assertForbidden();
});
