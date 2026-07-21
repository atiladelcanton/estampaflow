<?php

namespace App\Livewire;

use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class TenantDashboard extends Component
{
    public function render(TenantContext $context): View
    {
        $tenantId = (string) $context->currentId();
        $tenant = Tenant::query()->findOrFail($tenantId);
        $currentMembership = auth()->user()?->activeMembershipFor($tenantId);

        abort_if($currentMembership === null, 403, 'Você não possui acesso ativo a esta estamparia.');

        return view('livewire.tenant-dashboard', [
            'tenant' => $tenant,
            'currentMembership' => $currentMembership,
            'membersCount' => $tenant->memberships()->where('status', MembershipStatus::ACTIVE->value)->count(),
            'pendingInvitations' => $tenant->invitations()->where('status', InvitationStatus::PENDING->value)->count(),
            'serviceTypesCount' => ServiceType::query()->count(),
            'activeServiceTypesCount' => ServiceType::query()->where('active', true)->count(),
        ]);
    }
}
