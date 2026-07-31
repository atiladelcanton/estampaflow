<?php

declare(strict_types=1);

use App\Application\Pricing\Actions\SaveServicePricingAction;
use App\Application\Pricing\Data\SavePriceRuleData;
use App\Domains\Pricing\Data\ServicePricingInput;
use App\Domains\Pricing\Enums\PricingResultStatus;
use App\Domains\Pricing\Models\ServicePriceRule;
use App\Domains\Pricing\Models\ServicePriceTable;
use App\Domains\Pricing\Services\DynamicPricingService;
use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function pricingEngineFixture(string $slug = 'pricing'): array
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Pricing '.$slug,
        'slug' => $slug,
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

    return [$tenant, $owner];
}

it('calcula preço por quantidade e escolhe a regra mais específica', function (): void {
    [$tenant, $owner] = pricingEngineFixture();

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $owner): void {
        $service = ServiceType::query()->create([
            'code' => 'SILK', 'name' => 'Silk', 'slug' => 'silk',
            'pricing_mode' => PricingMode::AUTOMATIC,
            'pricing_strategy' => PricingStrategy::MATRIX,
            'requires_art' => true, 'allows_multiple_positions' => true,
            'active' => true, 'is_default' => true, 'sort_order' => 10,
        ]);
        $schema = ServiceTypeSchemaVersion::query()->create([
            'service_type_id' => $service->getKey(), 'version' => 1,
            'status' => ServiceSchemaStatus::ACTIVE, 'created_by' => $owner->getKey(), 'activated_at' => now(),
        ]);
        ServiceParameterDefinition::query()->create([
            'schema_version_id' => $schema->getKey(), 'key' => 'ink_system', 'label' => 'Sistema de tinta',
            'field_type' => ServiceParameterFieldType::SELECT, 'required' => true, 'affects_pricing' => true,
            'options' => ['Base água', 'Plastisol'], 'sort_order' => 10, 'active' => true,
        ]);
        $service->forceFill(['active_schema_version_id' => $schema->getKey()])->save();

        app(SaveServicePricingAction::class)->execute($owner, $service, PricingStrategy::MATRIX, [
            new SavePriceRuleData('Preço padrão', 1, null, [], 1000, null, null, 0, 0, 100, 10),
            new SavePriceRuleData('Plastisol', 1, null, [['parameter' => 'ink_system', 'operator' => 'eq', 'value' => 'Plastisol']], 1500, null, null, 5000, 0, 100, 20),
        ], [], null, null);

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            tenantId: (string) $tenant->getTenantKey(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: (string) $schema->getKey(),
            appliedQuantity: 10,
            parameters: ['ink_system' => 'Plastisol'],
            referenceDate: CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::MATCHED)
            ->and($result->total?->amountMinor)->toBe(20000)
            ->and($result->explanation)->toContain('Plastisol');
    });
});

it('detecta empate sem escolher regra silenciosamente', function (): void {
    [$tenant, $owner] = pricingEngineFixture('ambiguous');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($tenant, $owner): void {
        $service = ServiceType::query()->create([
            'code' => 'CUSTOM', 'name' => 'Custom', 'slug' => 'custom',
            'pricing_mode' => PricingMode::AUTOMATIC, 'pricing_strategy' => PricingStrategy::UNIT,
            'requires_art' => false, 'allows_multiple_positions' => false,
            'active' => true, 'is_default' => false, 'sort_order' => 10,
        ]);
        $schema = ServiceTypeSchemaVersion::query()->create([
            'service_type_id' => $service->getKey(), 'version' => 1,
            'status' => ServiceSchemaStatus::ACTIVE, 'created_by' => $owner->getKey(), 'activated_at' => now(),
        ]);
        $service->forceFill(['active_schema_version_id' => $schema->getKey()])->save();

        $table = ServicePriceTable::query()->create([
            'service_type_id' => $service->getKey(), 'schema_version_id' => $schema->getKey(), 'version' => 1,
            'status' => 'ACTIVE', 'strategy' => PricingStrategy::UNIT, 'currency' => 'BRL',
            'priority' => 100, 'created_by' => $owner->getKey(), 'activated_at' => now(),
        ]);
        foreach (['Regra A', 'Regra B'] as $name) {
            ServicePriceRule::query()->create([
                'price_table_id' => $table->getKey(), 'name' => $name, 'min_quantity' => 1,
                'unit_amount_minor' => 1000, 'setup_amount_minor' => 0, 'minimum_amount_minor' => 0,
                'priority' => 100, 'sort_order' => 10, 'active' => true,
            ]);
        }
        $service->forceFill(['active_price_table_id' => $table->getKey()])->save();

        $result = app(DynamicPricingService::class)->calculate(new ServicePricingInput(
            (string) $tenant->getTenantKey(), (string) $service->getKey(), (string) $schema->getKey(),
            5, [], CarbonImmutable::now(),
        ));

        expect($result->status)->toBe(PricingResultStatus::AMBIGUOUS);
    });
});
