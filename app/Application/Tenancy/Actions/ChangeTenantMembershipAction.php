<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final readonly class ChangeTenantMembershipAction
{
    public function __construct(
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(
        User $actor,
        TenantMembership $target,
        ?TenantRole $role = null,
        ?MembershipStatus $status = null,
        ?string $reason = null,
    ): TenantMembership {
        return DB::transaction(function () use ($actor, $target, $role, $status, $reason): TenantMembership {
            $locked = TenantMembership::query()->lockForUpdate()->findOrFail($target->getKey());
            $this->memberships->assertOwner($actor, $locked->tenant_id);

            $removesActiveOwner = $locked->isOwner()
                && $locked->isActive()
                && (($role !== null && $role !== TenantRole::OWNER)
                    || ($status !== null && $status !== MembershipStatus::ACTIVE));

            if ($removesActiveOwner) {
                $this->memberships->assertCanRemoveOwner($locked);
            }

            if ($locked->user_id === (string) $actor->getKey()
                && $status !== null
                && $status !== MembershipStatus::ACTIVE) {
                throw new \DomainException('Você não pode suspender ou revogar o próprio acesso.');
            }

            $before = [
                'role' => $locked->role->value,
                'status' => $locked->status->value,
            ];

            $locked->forceFill([
                'role' => $role ?? $locked->role,
                'status' => $status ?? $locked->status,
            ])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.membership.changed',
                tenantId: $locked->tenant_id,
                actorId: (string) $actor->getKey(),
                auditableType: TenantMembership::class,
                auditableId: (string) $locked->getKey(),
                before: $before,
                after: [
                    'role' => $locked->role->value,
                    'status' => $locked->status->value,
                ],
                reason: $reason,
            ));

            return $locked->refresh();
        });
    }
}
