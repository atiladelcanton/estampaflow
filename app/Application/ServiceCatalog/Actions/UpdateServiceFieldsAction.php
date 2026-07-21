<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\ServiceSchemaStatus;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Models\ServiceTypeSchemaVersion;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Tenancy\TenantContext;

final readonly class UpdateServiceFieldsAction
{
    public function __construct(
        private TenantContext $tenantContext,
        private TenantMembershipService $memberships,
        private CreateServiceSchemaVersionAction $createVersion,
        private SaveServiceSchemaDraftAction $saveDraft,
        private ActivateServiceSchemaVersionAction $activateVersion,
    ) {}

    /**
     * Salva os campos em uma nova versão e coloca a alteração em uso.
     *
     * A interface não expõe rascunhos ou publicação. Essa complexidade
     * permanece interna para preservar orçamentos antigos.
     *
     * @param  list<SaveServiceParameterData>  $fields
     */
    public function execute(User $actor, ServiceType $serviceType, array $fields): ServiceTypeSchemaVersion
    {
        $tenantId = (string) $this->tenantContext->currentId();
        $this->memberships->assertOwner($actor, $tenantId);

        $draft = $serviceType->schemaVersions()
            ->where('status', ServiceSchemaStatus::DRAFT->value)
            ->first();

        if (! $draft instanceof ServiceTypeSchemaVersion) {
            $draft = $this->createVersion->execute($actor, $serviceType);
        }

        $draft = $this->saveDraft->execute($actor, $draft, $fields);

        return $this->activateVersion->execute($actor, $draft);
    }
}
