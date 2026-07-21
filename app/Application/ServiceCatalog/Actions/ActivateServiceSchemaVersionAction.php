<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\ServiceCatalog\Services\ServiceParameterSchemaService;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ActivateServiceSchemaVersionAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private ServiceParameterSchemaService $schemaService,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, ServiceTypeSchemaVersion $version): ServiceTypeSchemaVersion
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        if (! $version->isDraft()) {
            throw ValidationException::withMessages(['schema' => 'Apenas um rascunho pode ser ativado.']);
        }

        $definitions = $version->parameters()->get()->map(fn (ServiceParameterDefinition $parameter): SaveServiceParameterData => new SaveServiceParameterData(
            key: $parameter->key,
            label: $parameter->label,
            fieldType: $parameter->field_type,
            unit: $parameter->unit,
            required: $parameter->required,
            affectsPricing: $parameter->affects_pricing,
            options: $parameter->options,
            validationRules: $parameter->validation_rules,
            defaultValue: $parameter->default_value,
            sortOrder: $parameter->sort_order,
            active: $parameter->active,
        ))->all();

        $this->schemaService->validateDefinitions($definitions);

        return DB::transaction(function () use ($actor, $version, $tenantId): ServiceTypeSchemaVersion {
            $serviceType = $version->serviceType()->lockForUpdate()->firstOrFail();
            $previousActiveId = $serviceType->active_schema_version_id;

            $serviceType->schemaVersions()
                ->where('status', ServiceSchemaStatus::ACTIVE->value)
                ->update(['status' => ServiceSchemaStatus::RETIRED->value]);

            $version->forceFill([
                'status' => ServiceSchemaStatus::ACTIVE,
                'activated_at' => now(),
            ])->save();

            $serviceType->forceFill([
                'active_schema_version_id' => $version->getKey(),
                'active' => true,
            ])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'service_schema.activated',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: ServiceTypeSchemaVersion::class,
                auditableId: (string) $version->getKey(),
                before: ['active_schema_version_id' => $previousActiveId],
                after: [
                    'active_schema_version_id' => $version->getKey(),
                    'version' => $version->version,
                ],
            ));

            return $version->refresh()->load(['parameters', 'serviceType']);
        });
    }
}
