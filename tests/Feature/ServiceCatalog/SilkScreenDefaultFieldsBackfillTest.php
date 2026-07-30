<?php

declare(strict_types=1);

use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('adiciona somente os campos ausentes ao Silk já existente', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Silk existente',
        'slug' => 'silk-existente',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function (): void {
        $serviceType = ServiceType::query()->create([
            'code' => 'SILK',
            'name' => 'Silk personalizado',
            'slug' => 'silk',
            'description' => 'Configuração já usada pelo tenant.',
            'pricing_mode' => PricingMode::AUTOMATIC,
            'pricing_strategy' => PricingStrategy::MATRIX,
            'requires_art' => true,
            'allows_multiple_positions' => true,
            'active' => true,
            'is_default' => true,
            'sort_order' => 20,
        ]);

        $version = ServiceTypeSchemaVersion::query()->create([
            'service_type_id' => $serviceType->getKey(),
            'version' => 1,
            'status' => ServiceSchemaStatus::ACTIVE,
            'created_by' => null,
            'activated_at' => now(),
        ]);

        ServiceParameterDefinition::query()->create([
            'schema_version_id' => $version->getKey(),
            'key' => 'screen_colors',
            'label' => 'Número de cores personalizado',
            'field_type' => ServiceParameterFieldType::INTEGER,
            'unit' => 'cores',
            'required' => true,
            'affects_pricing' => true,
            'sort_order' => 10,
            'active' => true,
        ]);

        ServiceParameterDefinition::query()->create([
            'schema_version_id' => $version->getKey(),
            'key' => 'customer_reference',
            'label' => 'Referência do cliente',
            'field_type' => ServiceParameterFieldType::TEXT,
            'required' => false,
            'affects_pricing' => false,
            'sort_order' => 20,
            'active' => true,
        ]);

        $serviceType->forceFill(['active_schema_version_id' => $version->getKey()])->save();
    });

    /** @var Migration $migration */
    $migration = require database_path('migrations/2026_07_30_000200_add_silk_screen_default_fields.php');
    $migration->up();
    $migration->up();

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function (): void {
        $serviceType = ServiceType::query()->where('code', 'SILK')->firstOrFail();
        $parameters = $serviceType->activeSchemaVersion()
            ->firstOrFail()
            ->parameters()
            ->get()
            ->keyBy('key');

        expect($serviceType->schemaVersions()->count())->toBe(2)
            ->and($parameters->keys()->all())->toContain(
                'screen_colors',
                'customer_reference',
                'ink_system',
                'print_effect',
                'width_cm',
                'height_cm',
                'white_base',
                'technical_notes',
            )
            ->and($parameters->get('screen_colors')?->label)->toBe('Número de cores personalizado')
            ->and($parameters->get('customer_reference')?->label)->toBe('Referência do cliente')
            ->and($parameters->get('white_base')?->default_value)->toBe('Automático');
    });
});
