<?php

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\CreatedInvitationData;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class InviteTenantUserAction
{
    public function __construct(
        private TenantMembershipService $memberships,
        private TenantUrlGenerator $urls,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(Tenant $tenant, User $actor, string $email, TenantRole $role): CreatedInvitationData
    {
        $this->memberships->assertOwner($actor, (string) $tenant->getTenantKey());

        $normalizedEmail = mb_strtolower(trim($email));

        $alreadyMember = $tenant->memberships()
            ->whereHas('user', fn ($query) => $query->where('email', $normalizedEmail))
            ->where('status', '!=', 'REVOKED')
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já possui vínculo com a estamparia.',
            ]);
        }

        $plainToken = Str::random(64);
        $acceptUrl = $this->urls->central('/convites/'.$plainToken);

        $invitation = DB::transaction(function () use ($tenant, $actor, $email, $normalizedEmail, $role, $plainToken): TenantInvitation {
            $existing = TenantInvitation::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('pending_email_key', $normalizedEmail)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->expires_at->isFuture()) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe um convite pendente para este e-mail.',
                ]);
            }

            if ($existing !== null) {
                $existing->markExpired();
            }

            $invitation = TenantInvitation::query()->create([
                'tenant_id' => $tenant->getTenantKey(),
                'email' => trim($email),
                'email_normalized' => $normalizedEmail,
                'pending_email_key' => $normalizedEmail,
                'role' => $role,
                'status' => InvitationStatus::PENDING,
                'token_hash' => hash('sha256', $plainToken),
                'invited_by' => $actor->getKey(),
                'expires_at' => now()->addDays(7),
            ]);

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.invitation.created',
                tenantId: (string) $tenant->getTenantKey(),
                actorId: (string) $actor->getKey(),
                auditableType: TenantInvitation::class,
                auditableId: (string) $invitation->getKey(),
                after: [
                    'email' => $normalizedEmail,
                    'role' => $role->value,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                ],
            ));

            return $invitation;
        });

        DB::afterCommit(function () use ($email, $tenant, $role, $acceptUrl): void {
            Notification::route('mail', $email)
                ->notify(new TenantInvitationNotification($tenant->name, $role->label(), $acceptUrl));
        });

        return new CreatedInvitationData($invitation, $plainToken, $acceptUrl);
    }
}
