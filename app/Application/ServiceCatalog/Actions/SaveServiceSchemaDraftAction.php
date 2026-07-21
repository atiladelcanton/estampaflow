<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\ServiceCatalog\Services\ServiceParameterSchemaService;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class SaveServiceSchemaDraftAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private ServiceParameterSchemaService $schemaService,
        private AuditLogger $auditLogger,
    ) {}

    /** @param list<SaveServiceParameterData> $parameters */
    public function execute(User $actor, ServiceTypeSchemaVersion $version, array $parameters): ServiceTypeSchemaVersion
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        if (! $version->isDraft()) {
            throw ValidationException::withMessages([
                'schema' => 'Somente versões em rascunho podem ser alteradas.',
            ]);
        }

        $this->schemaService->validateDefinitions($parameters);

        return DB::transaction(function () use ($actor, $version, $parameters, $tenantId): ServiceTypeSchemaVersion {
            $before = $version->parameters()->get()->map(fn (ServiceParameterDefinition $parameter): array => [
                'key' => $parameter->key,
                'label' => $parameter->label,
                'field_type' => $parameter->field_type->value,
            ])->all();

            $version->parameters()->delete();

            foreach ($parameters as $parameter) {
                ServiceParameterDefinition::query()->create([
                    'schema_version_id' => $version->getKey(),
                    'key' => Str::snake(trim($parameter->key)),
                    'label' => trim($parameter->label),
                    'field_type' => $parameter->fieldType,
                    'unit' => $parameter->unit === null ? null : trim($parameter->unit),
                    'required' => $parameter->required,
                    'affects_pricing' => $parameter->affectsPricing,
                    'options' => $parameter->options,
                    'validation_rules' => $parameter->validationRules,
                    'default_value' => $parameter->defaultValue,
                    'sort_order' => $parameter->sortOrder,
                    'active' => $parameter->active,
                ]);
            }

            $this->auditLogger->record(new AuditEntryData(
                action: 'service_schema.draft_saved',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: ServiceTypeSchemaVersion::class,
                auditableId: (string) $version->getKey(),
                before: ['parameters' => $before],
                after: ['parameters' => collect($parameters)->map(fn (SaveServiceParameterData $parameter): array => [
                    'key' => Str::snake(trim($parameter->key)),
                    'label' => trim($parameter->label),
                    'field_type' => $parameter->fieldType->value,
                ])->all()],
            ));

            return $version->refresh()->load('parameters');
        });
    }
}
