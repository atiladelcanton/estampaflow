<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\CreateTenantData;
use App\Application\Tenancy\Data\RegisteredTenantOwnerData;
use App\Application\Tenancy\Data\RegisterTenantOwnerData;
use App\Application\Tenancy\Jobs\SendTenantWelcomeEmailJob;
use App\Domains\ServiceCatalog\Services\DefaultServiceCatalogService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use App\Support\Tenancy\UniqueTenantSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class RegisterTenantOwnerAction
{
    public function __construct(
        private CreateTenantAction $createTenant,
        private UniqueTenantSlugGenerator $slugGenerator,
        private TenantContext $tenantContext,
        private DefaultServiceCatalogService $defaultCatalog,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(RegisterTenantOwnerData $data): RegisteredTenantOwnerData
    {
        return DB::transaction(function () use ($data): RegisteredTenantOwnerData {
            $user = User::query()->create([
                'name' => trim($data->ownerName),
                'email' => mb_strtolower(trim($data->email)),
                'password' => $data->password,
            ]);

            $slug = $this->slugGenerator->generate($data->businessName);
            $domain = $slug.'.'.config('tenancy.tenant_base_domain');

            $tenant = $this->createTenant->execute(new CreateTenantData(
                name: trim($data->businessName),
                slug: $slug,
                domain: $domain,
                owner: $user,
            ));

            $this->tenantContext->run(
                new TenantId((string) $tenant->getTenantKey()),
                fn () => $this->defaultCatalog->createDefaultsFor($user),
            );

            SendTenantWelcomeEmailJob::dispatch(
                userId: (string) $user->getKey(),
                tenantId: (string) $tenant->getTenantKey(),
            );

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.welcome_email.queued',
                tenantId: (string) $tenant->getTenantKey(),
                actorId: (string) $user->getKey(),
                auditableType: User::class,
                auditableId: (string) $user->getKey(),
                after: [
                    'channel' => 'mail',
                    'recipient' => $user->email,
                    'queue' => 'mail',
                ],
            ));

            return new RegisteredTenantOwnerData($user, $tenant);
        });
    }
}
