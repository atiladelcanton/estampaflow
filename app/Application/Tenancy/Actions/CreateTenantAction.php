<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\CreateTenantData;
use App\Application\Tenancy\Jobs\ProvisionTenantDomainJob;
use App\Domains\Tenancy\Enums\DomainProvisioningStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Domain;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateTenantAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(CreateTenantData $data): Tenant
    {
        return DB::transaction(function () use ($data): Tenant {
            $tenant = Tenant::query()->create([
                'id' => (string) Str::ulid(),
                'name' => trim($data->name),
                'slug' => Str::slug($data->slug),
                'status' => TenantStatus::ACTIVE,
                'timezone' => $data->timezone,
                'trial_ends_at' => now()->addDays(7),
                'data' => [],
            ]);

            /** @var Domain $domain */
            $domain = $tenant->domains()->create([
                'domain' => mb_strtolower(trim($data->domain)),
                'provisioning_status' => DomainProvisioningStatus::PENDING,
            ]);

            TenantMembership::query()->create([
                'tenant_id' => $tenant->getTenantKey(),
                'user_id' => $data->owner->getKey(),
                'role' => TenantRole::OWNER,
                'status' => MembershipStatus::ACTIVE,
                'joined_at' => now(),
            ]);

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.created',
                tenantId: (string) $tenant->getTenantKey(),
                actorId: (string) $data->owner->getKey(),
                auditableType: Tenant::class,
                auditableId: (string) $tenant->getTenantKey(),
                after: [
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'domain' => $domain->domain,
                ],
            ));

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.domain.provisioning_queued',
                tenantId: (string) $tenant->getTenantKey(),
                actorId: (string) $data->owner->getKey(),
                auditableType: Domain::class,
                auditableId: (string) $domain->getKey(),
                after: [
                    'domain' => $domain->domain,
                    'status' => DomainProvisioningStatus::PENDING->value,
                    'queue' => 'provisioning',
                ],
            ));

            ProvisionTenantDomainJob::dispatch((int) $domain->getKey());

            return $tenant->load('domains');
        });
    }
}
