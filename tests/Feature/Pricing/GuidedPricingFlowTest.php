<?php

declare(strict_types=1);

use App\Domains\Pricing\Data\ServicePricingInput;
use App\Domains\Pricing\Enums\PricingResultStatus;
use App\Domains\Pricing\Services\DynamicPricingService;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/** @return array{Tenant, User, string} */
function guidedPricingFixture(string $slug): array
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Guided Pricing',
        'slug' => $slug,
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);
    Domain::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'domain' => $slug.'.estamparia.test',
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
        app(DefaultServiceCatalogService::class)->createDefaultsFor($owner);
    });

    return [$tenant, $owner, 'http://'.$slug.'.estamparia.test'];
}

it('apresenta o DTF em um passo a passo por metro inteiro', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-dtf-ui');

    $serviceId = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): string => (string) ServiceType::query()->where('code', 'DTF')->value('id'),
    );

    $this->actingAs($owner)
        ->get($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos')
        ->assertOk()
        ->assertSee('DTF comprado por metro inteiro')
        ->assertSee('Informe somente os dados do fornecedor')
        ->assertSee('Entenda esta tela')
        ->assertSee('Confira com um pedido real')
        ->assertDontSee('Valor por cm²');
});

it('salva DTF comprado e arredonda o consumo para metros inteiros', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-dtf-save');

    $serviceId = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): string => (string) ServiceType::query()->where('code', 'DTF')->value('id'),
    );

    $this->actingAs($owner)
        ->put($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos', [
            'meter_cost' => '40,00',
            'usable_width_cm' => '58',
            'application_price' => '2,00',
            'material_markup_percent' => '0',
            'spacing_cm' => '0',
            'waste_percent' => '0',
            'allow_rotation' => '1',
        ])
        ->assertRedirect($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $serviceId): void {
        $service = ServiceType::query()->findOrFail($serviceId);
        $table = $service->activePriceTable()->firstOrFail();

        expect($table->strategy)->toBe(PricingStrategy::ROLL_LENGTH)
            ->and($table->settings['guided_template'] ?? null)->toBe('DTF_METER')
            ->and($table->settings['meter_cost_minor'] ?? null)->toBe(4000);

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            tenantId: (string) $tenant->getTenantKey(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: (string) $service->active_schema_version_id,
            appliedQuantity: 10,
            parameters: ['width_cm' => '20', 'height_cm' => '30'],
            referenceDate: CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::MATCHED)
            ->and($result->total?->amountMinor)->toBe(10000)
            ->and($result->details['charged_meters'] ?? null)->toBe(2)
            ->and($result->details['material_cost']['amount_minor'] ?? null)->toBe(8000)
            ->and($result->details['application_total']['amount_minor'] ?? null)->toBe(2000);
    });
});

it('salva a tabela guiada do Silk e soma base branca, adicionais e telas', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-silk-save');

    $serviceId = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): string => (string) ServiceType::query()->where('code', 'SILK')->value('id'),
    );

    $this->actingAs($owner)
        ->put($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos', [
            'setup_charge_mode' => 'SEPARATE',
            'setup_per_color' => '30,00',
            'white_base_mode' => 'ADD_COLOR',
            'colors' => [1, 2, 3],
            'ranges' => [[
                'min_quantity' => 10,
                'max_quantity' => null,
                'prices' => [
                    '1' => '9,00',
                    '2' => '11,00',
                    '3' => '13,00',
                ],
            ]],
            'addons_enabled' => '1',
            'ink_addons' => [
                ['option' => 'Plastisol', 'amount' => '1,00'],
            ],
            'effect_addons' => [
                ['option' => 'Puff/relevo', 'amount' => '2,50'],
            ],
        ])
        ->assertRedirect($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $serviceId): void {
        $service = ServiceType::query()->findOrFail($serviceId);
        $table = $service->activePriceTable()->firstOrFail();

        expect($table->settings['guided_template'] ?? null)->toBe('SILK_MATRIX')
            ->and($table->rules()->count())->toBe(3);

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            tenantId: (string) $tenant->getTenantKey(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: (string) $service->active_schema_version_id,
            appliedQuantity: 20,
            parameters: [
                'screen_colors' => '2',
                'white_base' => 'Sim',
                'ink_system' => 'Plastisol',
                'print_effect' => 'Puff/relevo',
            ],
            referenceDate: CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::MATCHED)
            ->and($result->total?->amountMinor)->toBe(42000)
            ->and($result->details['effective_colors'] ?? null)->toBe(3)
            ->and($result->details['final_unit_price']['amount_minor'] ?? null)->toBe(1650)
            ->and($result->details['setup_total']['amount_minor'] ?? null)->toBe(9000);
    });
});

it('salva a tabela guiada da Sublimação por quantidade e tipo', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-sublimation-save');

    $serviceId = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): string => (string) ServiceType::query()->where('code', 'SUBLIMACAO')->value('id'),
    );

    $this->actingAs($owner)
        ->put($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos', [
            'selected_categories' => ['LOCAL_MEDIUM', 'TOTAL'],
            'ranges' => [[
                'min_quantity' => 1,
                'max_quantity' => null,
                'prices' => [
                    'LOCAL_MEDIUM' => '15,00',
                    'TOTAL' => '35,00',
                ],
            ]],
            'sample_category' => 'LOCAL_MEDIUM',
        ])
        ->assertRedirect($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $serviceId): void {
        $service = ServiceType::query()->findOrFail($serviceId);
        $table = $service->activePriceTable()->firstOrFail();

        expect($table->strategy)->toBe(PricingStrategy::MATRIX)
            ->and($table->settings['guided_template'] ?? null)->toBe('SUBLIMATION_MATRIX')
            ->and($table->settings['configured_categories'] ?? null)->toBe(['LOCAL_MEDIUM', 'TOTAL'])
            ->and($table->rules()->count())->toBe(2);

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            tenantId: (string) $tenant->getTenantKey(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: (string) $service->active_schema_version_id,
            appliedQuantity: 10,
            parameters: [
                'modality' => 'Localizada',
                'piece_type' => 'Média',
            ],
            referenceDate: CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::MATCHED)
            ->and($result->total?->amountMinor)->toBe(15000);
    });
});

it('salva a tabela guiada do Bordado por quantidade e faixa de pontos', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-embroidery-save');

    $serviceId = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): string => (string) ServiceType::query()->where('code', 'BORDADO')->value('id'),
    );

    $this->actingAs($owner)
        ->put($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos', [
            'digitizing_charge_mode' => 'SEPARATE',
            'stitch_columns' => [
                ['key' => 'RANGE_0', 'label' => 'Até 5.000'],
                ['key' => 'RANGE_1', 'label' => '5.001 a 10.000'],
            ],
            'digitizing_price' => '50,00',
            'ranges' => [[
                'min_quantity' => 1,
                'max_quantity' => null,
                'prices' => [
                    'RANGE_0' => '8,00',
                    'RANGE_1' => '12,00',
                ],
            ]],
        ])
        ->assertRedirect($baseUrl.'/configuracoes/servicos/'.$serviceId.'/precos');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $serviceId): void {
        $service = ServiceType::query()->findOrFail($serviceId);
        $table = $service->activePriceTable()->firstOrFail();

        expect($table->strategy)->toBe(PricingStrategy::MATRIX)
            ->and($table->settings['guided_template'] ?? null)->toBe('EMBROIDERY_MATRIX')
            ->and($table->settings['digitizing_charge_mode'] ?? null)->toBe('SEPARATE')
            ->and($table->settings['digitizing_amount_minor'] ?? null)->toBe(5000)
            ->and($table->rules()->count())->toBe(2);

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            tenantId: (string) $tenant->getTenantKey(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: (string) $service->active_schema_version_id,
            appliedQuantity: 10,
            parameters: ['stitch_range' => '5.001 a 10.000'],
            referenceDate: CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::MATCHED)
            ->and($result->total?->amountMinor)->toBe(17000);
    });
});

it('apresenta escolhas interativas na primeira etapa de Sublimação e Bordado', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-options-ui');

    $serviceIds = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): array => ServiceType::query()
            ->whereIn('code', ['SUBLIMACAO', 'BORDADO'])
            ->pluck('id', 'code')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all(),
    );

    $this->actingAs($owner)
        ->get($baseUrl.'/configuracoes/servicos/'.$serviceIds['SUBLIMACAO'].'/precos')
        ->assertOk()
        ->assertSee('Quais tipos de sublimação você oferece?')
        ->assertSee('Clique para marcar ou desmarcar')
        ->assertSee('data-pricing-option', false);

    $this->actingAs($owner)
        ->get($baseUrl.'/configuracoes/servicos/'.$serviceIds['BORDADO'].'/precos')
        ->assertOk()
        ->assertSee('Quais faixas de pontos você utiliza?')
        ->assertSee('Como você cobra a matriz/digitalização?')
        ->assertSee('Já incluo no preço por peça')
        ->assertSee('Cobro uma vez por pedido');
});

it('apresenta configuração guiada nos quatro serviços padrão', function (): void {
    [$tenant, $owner, $baseUrl] = guidedPricingFixture('guided-all-defaults');

    $serviceIds = app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn (): array => ServiceType::query()
            ->whereIn('code', ['DTF', 'SILK', 'SUBLIMACAO', 'BORDADO'])
            ->pluck('id', 'code')
            ->map(static fn (mixed $id): string => (string) $id)
            ->all(),
    );

    $expected = [
        'DTF' => 'DTF comprado por metro inteiro',
        'SILK' => 'Silk por quantidade e número de cores',
        'SUBLIMACAO' => 'Sublimação por quantidade e tipo',
        'BORDADO' => 'Bordado por quantidade e pontos',
    ];

    foreach ($expected as $code => $text) {
        $this->actingAs($owner)
            ->get($baseUrl.'/configuracoes/servicos/'.$serviceIds[$code].'/precos')
            ->assertOk()
            ->assertSee($text)
            ->assertSee('Entenda esta tela');
    }
});
