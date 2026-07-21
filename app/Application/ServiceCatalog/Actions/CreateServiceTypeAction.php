<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\CreateServiceTypeData;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class CreateServiceTypeAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, CreateServiceTypeData $data, bool $returnExisting = false): ServiceType
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        $asciiCode = Str::upper(Str::ascii(trim($data->code)));
        $code = trim((string) preg_replace('/[^A-Z0-9]+/', '_', $asciiCode), '_');

        if ($code === '') {
            throw ValidationException::withMessages(['code' => 'Informe um código válido.']);
        }

        $slug = Str::slug($data->name);

        $existing = ServiceType::query()
            ->where('code', $code)
            ->first();

        if ($existing instanceof ServiceType) {
            if ($returnExisting) {
                return $existing->load('schemaVersions');
            }

            throw ValidationException::withMessages(['code' => 'Este código já está em uso.']);
        }

        if (ServiceType::query()->where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Já existe um serviço com este nome.']);
        }

        return DB::transaction(function () use ($actor, $data, $tenantId, $code, $slug): ServiceType {
            $serviceType = ServiceType::query()->create([
                'code' => $code,
                'name' => trim($data->name),
                'slug' => $slug,
                'description' => $data->description === null ? null : trim($data->description),
                'pricing_mode' => $data->pricingMode,
                'pricing_strategy' => $data->pricingStrategy,
                'requires_art' => $data->requiresArt,
                'allows_multiple_positions' => $data->allowsMultiplePositions,
                'active' => false,
                'is_default' => $data->isDefault,
                'sort_order' => $data->sortOrder,
            ]);

            ServiceTypeSchemaVersion::query()->create([
                'service_type_id' => $serviceType->getKey(),
                'version' => 1,
                'status' => ServiceSchemaStatus::DRAFT,
                'created_by' => $actor->getKey(),
            ]);

            $this->auditLogger->record(new AuditEntryData(
                action: 'service_type.created',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: ServiceType::class,
                auditableId: (string) $serviceType->getKey(),
                after: [
                    'code' => $serviceType->code,
                    'name' => $serviceType->name,
                    'pricing_mode' => $serviceType->pricing_mode->value,
                    'pricing_strategy' => $serviceType->pricing_strategy?->value,
                ],
            ));

            return $serviceType->load('schemaVersions');
        });
    }
}
