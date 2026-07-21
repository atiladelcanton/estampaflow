<?php

declare(strict_types=1);

namespace App\Domains\Tenancy\Services;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Exceptions\TenantAuthorizationException;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;

final class TenantMembershipService
{
    public function assertActiveMember(User $user, string $tenantId): TenantMembership
    {
        $membership = $user->memberships()
            ->where('tenant_id', $tenantId)
            ->where('status', MembershipStatus::ACTIVE->value)
            ->first();

        if ($membership === null) {
            throw new TenantAuthorizationException('Usuário não possui vínculo ativo com esta estamparia.');
        }

        return $membership;
    }

    public function assertOwner(User $user, string $tenantId): TenantMembership
    {
        $membership = $this->assertActiveMember($user, $tenantId);

        if ($membership->role !== TenantRole::OWNER) {
            throw new TenantAuthorizationException('Somente um proprietário pode executar esta ação.');
        }

        return $membership;
    }

    public function activeOwnerCount(string $tenantId): int
    {
        return TenantMembership::query()
            ->where('tenant_id', $tenantId)
            ->where('role', TenantRole::OWNER->value)
            ->where('status', MembershipStatus::ACTIVE->value)
            ->count();
    }

    public function assertCanRemoveOwner(TenantMembership $membership): void
    {
        if (! $membership->isOwner() || ! $membership->isActive()) {
            return;
        }

        if ($this->activeOwnerCount($membership->tenant_id) <= 1) {
            throw new TenantAuthorizationException(
                'O último proprietário ativo não pode ser removido, suspenso ou rebaixado.',
            );
        }
    }
}
