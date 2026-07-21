<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class CreateServiceSchemaVersionAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, ServiceType $serviceType): ServiceTypeSchemaVersion
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        $existingDraft = $serviceType->schemaVersions()
            ->where('status', ServiceSchemaStatus::DRAFT->value)
            ->first();

        if ($existingDraft instanceof ServiceTypeSchemaVersion) {
            throw ValidationException::withMessages([
                'schema' => 'Já existe uma versão em rascunho para este serviço.',
            ]);
        }

        return DB::transaction(function () use ($actor, $serviceType, $tenantId): ServiceTypeSchemaVersion {
            $nextVersion = ((int) $serviceType->schemaVersions()->max('version')) + 1;
            $source = $serviceType->activeSchemaVersion()->with('parameters')->first();

            $version = ServiceTypeSchemaVersion::query()->create([
                'service_type_id' => $serviceType->getKey(),
                'version' => $nextVersion,
                'status' => ServiceSchemaStatus::DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            if ($source instanceof ServiceTypeSchemaVersion) {
                foreach ($source->parameters as $parameter) {
                    ServiceParameterDefinition::query()->create([
                        'schema_version_id' => $version->getKey(),
                        'key' => $parameter->key,
                        'label' => $parameter->label,
                        'field_type' => $parameter->field_type,
                        'unit' => $parameter->unit,
                        'required' => $parameter->required,
                        'affects_pricing' => $parameter->affects_pricing,
                        'options' => $parameter->options,
                        'validation_rules' => $parameter->validation_rules,
                        'default_value' => $parameter->default_value,
                        'sort_order' => $parameter->sort_order,
                        'active' => $parameter->active,
                    ]);
                }
            }

            $this->auditLogger->record(new AuditEntryData(
                action: 'service_schema.draft_created',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: ServiceTypeSchemaVersion::class,
                auditableId: (string) $version->getKey(),
                after: [
                    'service_type_id' => $serviceType->getKey(),
                    'version' => $nextVersion,
                    'copied_from' => $source?->version,
                ],
            ));

            return $version->load('parameters');
        });
    }
}
