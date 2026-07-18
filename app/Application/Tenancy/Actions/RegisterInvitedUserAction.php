<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\RegisteredInvitedUserData;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class RegisterInvitedUserAction
{
    public function __construct(
        private AcceptTenantInvitationAction $acceptInvitation,
    ) {}

    public function execute(string $plainToken, string $name, string $password): RegisteredInvitedUserData
    {
        return DB::transaction(function () use ($plainToken, $name, $password): RegisteredInvitedUserData {
            $invitation = TenantInvitation::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->status !== InvitationStatus::PENDING || $invitation->expires_at->isPast()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Este convite não está mais disponível.',
                ]);
            }

            if (User::query()->where('email', $invitation->email_normalized)->exists()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Já existe uma conta para este e-mail. Entre com ela para aceitar o convite.',
                ]);
            }

            $user = User::query()->create([
                'name' => trim($name),
                'email' => $invitation->email_normalized,
                'password' => $password,
            ]);

            $membership = $this->acceptInvitation->execute($plainToken, $user);

            return new RegisteredInvitedUserData($user, $membership->load('tenant.domains'));
        });
    }
}
