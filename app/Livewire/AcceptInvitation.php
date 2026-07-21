<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\Tenancy\Actions\AcceptTenantInvitationAction;
use App\Application\Tenancy\Actions\RegisterInvitedUserAction;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Models\User;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

final class AcceptInvitation extends Component
{
    public string $token;

    public string $name = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        session()->put('url.intended', route('invitations.accept', ['token' => $token]));
    }

    public function accept(AcceptTenantInvitationAction $action, TenantUrlGenerator $urls): mixed
    {
        $user = auth()->user();

        if ($user === null) {
            return redirect()->route('login');
        }

        $membership = $action->execute($this->token, $user);
        session()->forget('url.intended');

        return redirect()->away($urls->for($membership->tenant));
    }

    public function registerAndAccept(RegisterInvitedUserAction $action, TenantUrlGenerator $urls): mixed
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $result = $action->execute($this->token, $validated['name'], $validated['password']);

        Auth::login($result->user);
        request()->session()->regenerate();
        session()->forget('url.intended');

        return redirect()->away($urls->for($result->membership->tenant));
    }

    public function render(): View
    {
        $invitation = TenantInvitation::query()
            ->with('tenant.domains')
            ->where('token_hash', hash('sha256', $this->token))
            ->first();

        $available = false;
        $existingUser = false;

        if ($invitation !== null) {
            $available = $invitation->isPending();

            if ($available) {
                $existingUser = User::query()
                    ->where('email', $invitation->email_normalized)
                    ->exists();
            }
        }

        return view('livewire.accept-invitation', [
            'invitation' => $invitation,
            'available' => $available,
            'existingUser' => $existingUser,
        ]);
    }
}
