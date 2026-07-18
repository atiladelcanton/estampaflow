<?php

namespace App\Livewire;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class WorkspaceSelector extends Component
{
    public function render(TenantUrlGenerator $urls): View
    {
        $memberships = auth()->user()
            ->memberships()
            ->with(['tenant.domains'])
            ->where('status', MembershipStatus::ACTIVE->value)
            ->orderByDesc('joined_at')
            ->get()
            ->map(fn ($membership) => [
                'membership' => $membership,
                'tenant' => $membership->tenant,
                'url' => $urls->for($membership->tenant),
            ]);

        return view('livewire.workspace-selector', [
            'memberships' => $memberships,
        ]);
    }
}
