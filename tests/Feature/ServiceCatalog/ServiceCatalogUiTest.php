<?php

declare(strict_types=1);

use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
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

it('mantém o catálogo simples para o owner e bloqueia usuário comum', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Catálogo UI',
        'slug' => 'catalogo-ui',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);
    $tenant->domains()->create(['domain' => 'catalogo-ui.estamparia.test']);

    $owner = User::factory()->create();
    $user = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(), 'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER, 'status' => MembershipStatus::ACTIVE, 'joined_at' => now(),
    ]);
    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(), 'user_id' => $user->getKey(),
        'role' => TenantRole::USER, 'status' => MembershipStatus::ACTIVE, 'joined_at' => now(),
    ]);

    $serviceTypeId = null;

    app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        function () use ($owner, &$serviceTypeId): void {
            app(DefaultServiceCatalogService::class)->createDefaultsFor($owner);
            $serviceTypeId = ServiceType::query()->where('code', 'DTF')->value('id');
        },
    );

    expect($serviceTypeId)->toBeString();

    $this->actingAs($owner)
        ->get('http://catalogo-ui.estamparia.test/configuracoes/servicos')
        ->assertOk()
        ->assertSee('Tipos de serviço')
        ->assertSee('<table', false)
        ->assertSee('Serviço')
        ->assertSee('Descrição')
        ->assertSee('Campos')
        ->assertSee('Ações');

    $this->actingAs($owner)
        ->get('http://catalogo-ui.estamparia.test/configuracoes/servicos/'.$serviceTypeId.'/campos')
        ->assertOk()
        ->assertSee('Campos de DTF')
        ->assertSee('A maior parte já vem pronta')
        ->assertSee('Adicionar campos comuns')
        ->assertSee('data-testid="suggested-fields"', false)
        ->assertSee('data-testid="suggested-field-button"', false)
        ->assertSee('aria-label="Adicionar ', false)
        ->assertSee('Adicionar campo personalizado')
        ->assertDontSee('wire:click=', false)
        ->assertSee('serviceFieldsEditor', false)
        ->assertSee('Salvar alterações')
        ->assertDontSee('Publicar configuração')
        ->assertDontSee('Preparar nova configuração')
        ->assertDontSee('Histórico de configurações');

    $this->actingAs($owner)
        ->get('http://catalogo-ui.estamparia.test/configuracoes/servicos/'.$serviceTypeId.'/schema')
        ->assertOk()
        ->assertSee('Campos de DTF');

    $this->actingAs($owner)
        ->patch('http://catalogo-ui.estamparia.test/configuracoes/servicos/'.$serviceTypeId.'/campos', [
            'fields' => [
                [
                    'key' => 'width_cm',
                    'label' => 'Largura',
                    'field_type' => ServiceParameterFieldType::DECIMAL->value,
                    'unit' => 'cm',
                    'required' => '1',
                    'affects_pricing' => '1',
                    'options_text' => '',
                    'default_value' => '',
                    'active' => '1',
                ],
            ],
        ])
        ->assertRedirect();

    app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        function () use ($serviceTypeId): void {
            $service = ServiceType::query()->findOrFail($serviceTypeId);
            $activeVersion = $service->activeSchemaVersion()->firstOrFail();
            $firstParameter = $activeVersion->parameters()->firstOrFail();

            expect($activeVersion->parameters()->count())->toBe(1)
                ->and($firstParameter->key)->toBe('width_cm');
        },
    );

    $this->actingAs($user)
        ->get('http://catalogo-ui.estamparia.test/configuracoes/servicos')
        ->assertForbidden();

    $this->actingAs($user)
        ->patch('http://catalogo-ui.estamparia.test/configuracoes/servicos/'.$serviceTypeId.'/campos', ['fields' => []])
        ->assertForbidden();
});
