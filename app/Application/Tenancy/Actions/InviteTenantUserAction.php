<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\CreatedInvitationData;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

final readonly class InviteTenantUserAction
{
    public function __construct(
        private TenantMembershipService $memberships,
        private TenantUrlGenerator $urls,
        private AuditLogger $auditLogger,
    ) {}

    public function execute(Tenant $tenant, User $actor, string $email, TenantRole $role): CreatedInvitationData
    {
        $tenantId = (string) $tenant->getTenantKey();
        $this->memberships->assertOwner($actor, $tenantId);

        $normalizedEmail = mb_strtolower(trim($email));

        $alreadyMember = $tenant->memberships()
            ->whereHas('user', fn ($query) => $query->where('email', $normalizedEmail))
            ->where('status', '!=', MembershipStatus::REVOKED->value)
            ->exists();

        if ($alreadyMember) {
            throw ValidationException::withMessages([
                'email' => 'Este e-mail já possui vínculo com a estamparia.',
            ]);
        }

        $plainToken = Str::random(64);
        $acceptUrl = $this->urls->central('/convites/'.$plainToken);

        $invitation = DB::transaction(function () use (

            $tenantId,
            $actor,
            $email,
            $normalizedEmail,
            $role,
            $plainToken,
        ): TenantInvitation {
            $existing = TenantInvitation::query()
                ->where('tenant_id', $tenantId)
                ->where('pending_email_key', $normalizedEmail)
                ->lockForUpdate()
                ->first();

            if ($existing?->isPending()) {
                throw ValidationException::withMessages([
                    'email' => 'Já existe um convite pendente para este e-mail.',
                ]);
            }

            if ($existing !== null) {
                $existing->markExpired();
            }

            $invitation = TenantInvitation::query()->create([
                'tenant_id' => $tenantId,
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
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: TenantInvitation::class,
                auditableId: (string) $invitation->getKey(),
                after: [
                    'email' => $normalizedEmail,
                    'role' => $role->value,
                    'expires_at' => $invitation->expires_at->toIso8601String(),
                ],
            ));

            return $invitation;
        });

        $emailDispatched = false;
        $deliveryError = null;

        try {
            Notification::route('mail', $normalizedEmail)
                ->notify(new TenantInvitationNotification($tenant->name, $role->label(), $acceptUrl));

            $emailDispatched = true;

            Log::channel('single')->info('tenant.invitation.email_dispatched', [
                'tenant_id' => $tenantId,
                'invitation_id' => (string) $invitation->getKey(),
                'to' => $normalizedEmail,
                'accept_url' => app()->environment('local') ? $acceptUrl : '[hidden]',
            ]);

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.invitation.email_dispatched',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: TenantInvitation::class,
                auditableId: (string) $invitation->getKey(),
                after: [
                    'channel' => 'mail',
                    'recipient' => $normalizedEmail,
                ],
            ));
        } catch (Throwable $exception) {
            $deliveryError = $exception->getMessage();
            report($exception);

            Log::channel('single')->error('tenant.invitation.email_failed', [
                'tenant_id' => $tenantId,
                'invitation_id' => (string) $invitation->getKey(),
                'to' => $normalizedEmail,
                'exception' => $exception::class,
                'error' => $deliveryError,
            ]);

            $this->auditLogger->record(new AuditEntryData(
                action: 'tenant.invitation.email_failed',
                tenantId: $tenantId,
                actorId: (string) $actor->getKey(),
                auditableType: TenantInvitation::class,
                auditableId: (string) $invitation->getKey(),
                after: [
                    'channel' => 'mail',
                    'recipient' => $normalizedEmail,
                    'exception' => $exception::class,
                ],
            ));
        }

        return new CreatedInvitationData(
            invitation: $invitation,
            plainToken: $plainToken,
            acceptUrl: $acceptUrl,
            emailDispatched: $emailDispatched,
            deliveryError: $deliveryError,
        );
    }
}
