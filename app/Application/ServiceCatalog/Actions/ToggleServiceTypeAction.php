<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use Illuminate\Validation\ValidationException;

final readonly class ToggleServiceTypeAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, ServiceType $serviceType): ServiceType
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        if (! $serviceType->active && $serviceType->active_schema_version_id === null) {
            throw ValidationException::withMessages([
                'serviceType' => 'Ative uma versão do schema antes de disponibilizar o serviço.',
            ]);
        }

        $before = $serviceType->active;
        $serviceType->forceFill(['active' => ! $serviceType->active])->save();

        $this->auditLogger->record(new AuditEntryData(
            action: $serviceType->active ? 'service_type.activated' : 'service_type.deactivated',
            tenantId: $tenantId,
            actorId: (string) $actor->getKey(),
            auditableType: ServiceType::class,
            auditableId: (string) $serviceType->getKey(),
            before: ['active' => $before],
            after: ['active' => $serviceType->active],
        ));

        return $serviceType;
    }
}
