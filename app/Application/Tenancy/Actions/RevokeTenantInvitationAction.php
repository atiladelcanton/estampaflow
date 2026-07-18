<?php

namespace App\Application\Tenancy\Actions;

use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final readonly class RevokeTenantInvitationAction
{
    public function __construct(
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, TenantInvitation $invitation): TenantInvitation
    {
        return DB::transaction(function () use ($actor, $invitation): TenantInvitation {
            $locked = TenantInvitation::query()->lockForUpdate()->findOrFail($invitation->getKey());

            $this->memberships->assertOwner($actor, (string) $locked->tenant_id);

            if ($locked->status !== InvitationStatus::PENDING) {
                return $locked;
            }

            $locked->forceFill([
                'status' => InvitationStatus::REVOKED,
                'pending_email_key' => null,
                'revoked_at' => now(),
            ])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.invitation.revoked',
                tenantId: (string) $locked->tenant_id,
                actorId: (string) $actor->getKey(),
                auditableType: TenantInvitation::class,
                auditableId: (string) $locked->getKey(),
                before: ['status' => InvitationStatus::PENDING->value],
                after: ['status' => InvitationStatus::REVOKED->value],
            ));

            return $locked->refresh();
        });
    }
}
