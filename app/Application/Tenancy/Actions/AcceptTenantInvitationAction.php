<?php

namespace App\Application\Tenancy\Actions;

use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class AcceptTenantInvitationAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(string $plainToken, User $user): TenantMembership
    {
        return DB::transaction(function () use ($plainToken, $user): TenantMembership {
            $invitation = TenantInvitation::query()
                ->where('token_hash', hash('sha256', $plainToken))
                ->lockForUpdate()
                ->firstOrFail();

            if ($invitation->status !== InvitationStatus::PENDING) {
                throw ValidationException::withMessages([
                    'invitation' => 'Este convite não está mais disponível.',
                ]);
            }

            if ($invitation->expires_at->isPast()) {
                $invitation->markExpired();

                throw ValidationException::withMessages([
                    'invitation' => 'Este convite expirou.',
                ]);
            }

            if (mb_strtolower($user->email) !== $invitation->email_normalized) {
                throw ValidationException::withMessages([
                    'invitation' => 'Entre com o mesmo e-mail que recebeu o convite.',
                ]);
            }

            $membership = TenantMembership::query()->updateOrCreate(
                [
                    'tenant_id' => $invitation->tenant_id,
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => $invitation->role,
                    'status' => MembershipStatus::ACTIVE,
                    'invited_by' => $invitation->invited_by,
                    'joined_at' => now(),
                ],
            );

            $invitation->forceFill([
                'status' => InvitationStatus::ACCEPTED,
                'pending_email_key' => null,
                'accepted_at' => now(),
                'accepted_by' => $user->getKey(),
            ])->save();

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.invitation.accepted',
                tenantId: (string) $invitation->tenant_id,
                actorId: (string) $user->getKey(),
                auditableType: TenantMembership::class,
                auditableId: (string) $membership->getKey(),
                after: [
                    'role' => $membership->role->value,
                    'status' => $membership->status->value,
                ],
            ));

            return $membership;
        });
    }
}
