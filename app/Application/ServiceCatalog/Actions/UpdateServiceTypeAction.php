<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\UpdateServiceTypeData;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class UpdateServiceTypeAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, ServiceType $serviceType, UpdateServiceTypeData $data): ServiceType
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        $slug = Str::slug($data->name);

        if (ServiceType::query()->where('slug', $slug)->where($serviceType->getKeyName(), '!=', $serviceType->getKey())->exists()) {
            throw ValidationException::withMessages(['name' => 'Já existe um serviço com este nome.']);
        }

        $before = $serviceType->only([
            'name', 'description', 'pricing_mode', 'pricing_strategy',
            'requires_art', 'allows_multiple_positions', 'sort_order',
        ]);

        $serviceType->update([
            'name' => trim($data->name),
            'slug' => $slug,
            'description' => $data->description === null ? null : trim($data->description),
            'pricing_mode' => $data->pricingMode,
            'pricing_strategy' => $data->pricingStrategy,
            'requires_art' => $data->requiresArt,
            'allows_multiple_positions' => $data->allowsMultiplePositions,
            'sort_order' => $data->sortOrder,
        ]);

        $this->auditLogger->record(new AuditEntryData(
            action: 'service_type.updated',
            tenantId: $tenantId,
            actorId: (string) $actor->getKey(),
            auditableType: ServiceType::class,
            auditableId: (string) $serviceType->getKey(),
            before: $before,
            after: $serviceType->fresh()?->only(array_keys($before)),
        ));

        return $serviceType->refresh();
    }
}
