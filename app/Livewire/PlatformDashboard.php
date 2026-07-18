<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class PlatformDashboard extends Component
{
    public function render(): View
    {
        return view('livewire.platform-dashboard', [
            'tenantCount' => Tenant::query()->count(),
            'trialCount' => Tenant::query()->where('trial_ends_at', '>', now())->count(),
            'userCount' => User::query()->count(),
            'tenants' => Tenant::query()
                ->with('domains')
                ->withCount('memberships')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }
}
