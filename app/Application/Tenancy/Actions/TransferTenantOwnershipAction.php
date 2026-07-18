<?php

namespace App\Application\Tenancy\Actions;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

final readonly class TransferTenantOwnershipAction
{
    public function __construct(
        private TenantMembershipService $memberships,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(User $actor, TenantMembership $target, string $reason): void
    {
        DB::transaction(function () use ($actor, $target, $reason): void {
            $actorMembership = $this->memberships->assertOwner($actor, (string) $target->tenant_id);

            $locked = TenantMembership::query()
                ->whereIn('id', [$actorMembership->getKey(), $target->getKey()])
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $currentOwner = $locked->get($actorMembership->getKey());
            $newOwner = $locked->get($target->getKey());

            if ($currentOwner === null || $newOwner === null) {
                throw new \DomainException('Não foi possível bloquear os vínculos para transferência.');
            }

            if ((string) $currentOwner->user_id === (string) $newOwner->user_id) {
                throw new \DomainException('Selecione outro usuário para receber a propriedade.');
            }

            if ($newOwner->status !== MembershipStatus::ACTIVE) {
                throw new \DomainException('O novo proprietário precisa estar ativo.');
            }

            $newOwner->forceFill(['role' => TenantRole::OWNER])->save();
            $currentOwner->forceFill(['role' => TenantRole::USER])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.ownership.transferred',
                tenantId: (string) $target->tenant_id,
                actorId: (string) $actor->getKey(),
                auditableType: TenantMembership::class,
                auditableId: (string) $newOwner->getKey(),
                before: ['owner_user_id' => (string) $currentOwner->user_id],
                after: ['owner_user_id' => (string) $newOwner->user_id],
                reason: $reason,
            ));
        });
    }
}
