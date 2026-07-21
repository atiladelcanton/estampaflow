<?php

declare(strict_types=1);

use App\Application\ServiceCatalog\Actions\ActivateServiceSchemaVersionAction;
use App\Application\ServiceCatalog\Actions\CreateServiceSchemaVersionAction;
use App\Application\ServiceCatalog\Actions\CreateServiceTypeAction;
use App\Application\ServiceCatalog\Actions\SaveServiceSchemaDraftAction;
use App\Application\ServiceCatalog\Actions\UpdateServiceFieldsAction;
use App\Application\ServiceCatalog\Data\CreateServiceTypeData;
use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\MissingTenantContextException;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function serviceCatalogFixture(string $slug): array
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Tenant '.$slug,
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

it('falha fechado ao consultar catálogo sem tenant', function (): void {
    expect(fn () => ServiceType::query()->count())->toThrow(MissingTenantContextException::class);
});

it('isola serviços entre tenants e permite o mesmo código', function (): void {
    [$tenantA, $ownerA] = serviceCatalogFixture('catalog-a');
    [$tenantB, $ownerB] = serviceCatalogFixture('catalog-b');
    $context = app(TenantContext::class);

    $context->run(new TenantId((string) $tenantA->getTenantKey()), function () use ($ownerA): void {
        app(CreateServiceTypeAction::class)->execute($ownerA, new CreateServiceTypeData(
            name: 'Cromia', code: 'CROMIA', description: null,
            pricingMode: PricingMode::AUTOMATIC, pricingStrategy: PricingStrategy::MATRIX,
            requiresArt: true, allowsMultiplePositions: true,
        ));
        expect(ServiceType::query()->count())->toBe(1);
    });

    $context->run(new TenantId((string) $tenantB->getTenantKey()), function () use ($ownerB): void {
        expect(ServiceType::query()->count())->toBe(0);
        app(CreateServiceTypeAction::class)->execute($ownerB, new CreateServiceTypeData(
            name: 'Cromia', code: 'CROMIA', description: null,
            pricingMode: PricingMode::AUTOMATIC, pricingStrategy: PricingStrategy::MATRIX,
            requiresArt: true, allowsMultiplePositions: true,
        ));
        expect(ServiceType::query()->count())->toBe(1);
    });
});

it('ativa nova versão sem alterar a versão anterior', function (): void {
    [$tenant, $owner] = serviceCatalogFixture('schema-version');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($owner): void {
        $service = app(CreateServiceTypeAction::class)->execute($owner, new CreateServiceTypeData(
            name: 'Simulado', code: 'SIMULADO', description: null,
            pricingMode: PricingMode::HYBRID, pricingStrategy: PricingStrategy::MATRIX,
            requiresArt: true, allowsMultiplePositions: true,
        ));

        $v1 = $service->schemaVersions()->firstOrFail();
        app(SaveServiceSchemaDraftAction::class)->execute($owner, $v1, [
            new SaveServiceParameterData('colors', 'Cores', ServiceParameterFieldType::INTEGER, 'cores', true, true, null, null, null, 10),
        ]);
        app(ActivateServiceSchemaVersionAction::class)->execute($owner, $v1);

        $v2 = app(CreateServiceSchemaVersionAction::class)->execute($owner, $service->refresh());
        app(SaveServiceSchemaDraftAction::class)->execute($owner, $v2, [
            new SaveServiceParameterData('colors', 'Cores', ServiceParameterFieldType::INTEGER, 'cores', true, true, null, null, null, 10),
            new SaveServiceParameterData('complexity', 'Complexidade', ServiceParameterFieldType::SELECT, null, true, true, ['Baixa', 'Alta'], null, null, 20),
        ]);
        app(ActivateServiceSchemaVersionAction::class)->execute($owner, $v2);

        expect($v1->refresh()->status)->toBe(ServiceSchemaStatus::RETIRED)
            ->and($v1->parameters()->count())->toBe(1)
            ->and($v2->refresh()->status)->toBe(ServiceSchemaStatus::ACTIVE)
            ->and($v2->parameters()->count())->toBe(2)
            ->and($service->refresh()->active_schema_version_id)->toBe($v2->getKey());
    });
});

it('salva campos com versionamento automático sem expor rascunho ao usuário', function (): void {
    [$tenant, $owner] = serviceCatalogFixture('simple-fields');

    app(TenantContext::class)->run(new TenantId((string) $tenant->getTenantKey()), function () use ($owner): void {
        $service = app(CreateServiceTypeAction::class)->execute($owner, new CreateServiceTypeData(
            name: 'Aplicação de Patch', code: 'PATCH', description: null,
            pricingMode: PricingMode::HYBRID, pricingStrategy: PricingStrategy::MATRIX,
            requiresArt: true, allowsMultiplePositions: true,
        ));

        $first = app(UpdateServiceFieldsAction::class)->execute($owner, $service, [
            new SaveServiceParameterData('width_cm', 'Largura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, null, null, null, 10),
        ]);

        $second = app(UpdateServiceFieldsAction::class)->execute($owner, $service->refresh(), [
            new SaveServiceParameterData('width_cm', 'Largura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, null, null, null, 10),
            new SaveServiceParameterData('finishing', 'Acabamento', ServiceParameterFieldType::TEXT, null, false, false, null, null, null, 20),
        ]);

        expect($first->refresh()->status)->toBe(ServiceSchemaStatus::RETIRED)
            ->and($first->parameters()->count())->toBe(1)
            ->and($second->refresh()->status)->toBe(ServiceSchemaStatus::ACTIVE)
            ->and($second->parameters()->count())->toBe(2)
            ->and($service->refresh()->active_schema_version_id)->toBe($second->getKey());
    });
});
