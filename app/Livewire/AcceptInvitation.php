<?php

namespace App\Livewire;

use App\Application\Tenancy\Actions\AcceptTenantInvitationAction;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Livewire\Component;

final class AcceptInvitation extends Component
{
    public string $token;

    public function mount(string $token): void
    {
        $this->token = $token;
    }

    public function accept(
        AcceptTenantInvitationAction $action,
        TenantUrlGenerator $urls,
    ): mixed {
        $membership = $action->execute($this->token, auth()->user());

        return redirect()->away($urls->for($membership->tenant));
    }

    public function render(): View
    {
        $invitation = TenantInvitation::query()
            ->with('tenant')
            ->where('token_hash', hash('sha256', $this->token))
            ->first();

        return view('livewire.accept-invitation', [
            'invitation' => $invitation,
        ]);
    }
}
