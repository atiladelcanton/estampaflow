<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Application\Tenancy\Actions\ChangeTenantMembershipAction;
use App\Application\Tenancy\Actions\InviteTenantUserAction;
use App\Application\Tenancy\Actions\RevokeTenantInvitationAction;
use App\Application\Tenancy\Actions\TransferTenantOwnershipAction;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

final class TenantUsers extends Component
{
    public string $email = '';

    public string $role = 'USER';

    public ?string $inviteUrl = null;

    public function invite(
        TenantContext $context,
        InviteTenantUserAction $action,
    ): void {
        $validated = $this->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'role' => ['required', Rule::enum(TenantRole::class)],
        ]);

        $tenant = Tenant::query()->findOrFail((string) $context->currentId());

        $result = $action->execute(
            $tenant,
            $this->authenticatedUser(),
            $validated['email'],
            TenantRole::from($validated['role']),
        );

        $this->inviteUrl = $result->acceptUrl;
        $this->reset('email', 'role');
        $this->role = TenantRole::USER->value;

        session()->flash(
            $result->emailQueued ? 'success' : 'warning',
            $result->emailQueued
                ? 'Convite enfileirado. Abra o Mailpit para acompanhar o envio; o link também aparece abaixo.'
                : 'Convite criado, mas não foi possível enfileirar o e-mail. Use o link abaixo e consulte o log.',
        );
    }

    public function toggleStatus(
        string $membershipId,
        TenantContext $context,
        ChangeTenantMembershipAction $action,
    ): void {
        $membership = TenantMembership::query()
            ->where('tenant_id', (string) $context->currentId())
            ->findOrFail($membershipId);

        $next = $membership->status === MembershipStatus::ACTIVE
            ? MembershipStatus::SUSPENDED
            : MembershipStatus::ACTIVE;

        $action->execute(
            $this->authenticatedUser(),
            $membership,
            status: $next,
            reason: 'Alteração realizada pela tela de equipe.',
        );

        session()->flash('success', 'Status do usuário atualizado.');
    }

    public function changeRole(
        string $membershipId,
        string $role,
        TenantContext $context,
        ChangeTenantMembershipAction $action,
    ): void {
        $membership = TenantMembership::query()
            ->where('tenant_id', (string) $context->currentId())
            ->findOrFail($membershipId);

        $action->execute(
            $this->authenticatedUser(),
            $membership,
            role: TenantRole::from($role),
            reason: 'Papel alterado pela tela de equipe.',
        );

        session()->flash('success', 'Papel atualizado.');
    }

    public function transferOwnership(
        string $membershipId,
        TenantContext $context,
        TransferTenantOwnershipAction $action,
    ): mixed {
        $membership = TenantMembership::query()
            ->where('tenant_id', (string) $context->currentId())
            ->findOrFail($membershipId);

        $action->execute(
            $this->authenticatedUser(),
            $membership,
            'Transferência confirmada pela tela de equipe.',
        );

        session()->flash('success', 'Propriedade transferida. Seu papel agora é Usuário.');

        return redirect()->route('tenant.dashboard');
    }

    public function revokeInvitation(
        string $invitationId,
        TenantContext $context,
        RevokeTenantInvitationAction $action,
    ): void {
        $invitation = TenantInvitation::query()
            ->where('tenant_id', (string) $context->currentId())
            ->findOrFail($invitationId);

        $action->execute($this->authenticatedUser(), $invitation);

        session()->flash('success', 'Convite revogado.');
    }

    public function render(TenantContext $context): View
    {
        $tenant = Tenant::query()->findOrFail((string) $context->currentId());

        return view('livewire.tenant-users', [
            'tenant' => $tenant,
            'memberships' => $tenant->memberships()
                ->with('user')
                ->orderByRaw("FIELD(role, 'OWNER', 'USER')")
                ->orderBy('created_at')
                ->get(),
            'invitations' => $tenant->invitations()
                ->where('status', InvitationStatus::PENDING->value)
                ->latest()
                ->get(),
            'roles' => TenantRole::cases(),
        ]);
    }

    private function authenticatedUser(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(401);
        }

        return $user;
    }
}
