<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Jobs;

use App\Domains\Tenancy\Models\TenantInvitation;
use App\Notifications\TenantInvitationNotification;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

final class SendTenantInvitationEmailJob implements ShouldBeEncrypted, ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(
        public readonly string $invitationId,
        private readonly string $plainToken,
    ) {
        $this->onConnection('database');
        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->invitationId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(TenantUrlGenerator $urls, AuditLogger $auditLogger): void
    {
        $invitation = TenantInvitation::query()
            ->with('tenant')
            ->find($this->invitationId);

        if ($invitation === null || ! $invitation->isPending()) {
            return;
        }

        $acceptUrl = $urls->central('/convites/'.$this->plainToken);

        Notification::route('mail', $invitation->email_normalized)->notifyNow(
            new TenantInvitationNotification(
                tenantName: $invitation->tenant->name,
                roleLabel: $invitation->role->label(),
                acceptUrl: $acceptUrl,
            ),
        );

        Log::info('tenant.invitation.email_sent', [
            'tenant_id' => $invitation->tenant_id,
            'invitation_id' => (string) $invitation->getKey(),
            'to' => $invitation->email_normalized,
        ]);

        $auditLogger->record(new AuditEntryData(
            action: 'tenant.invitation.email_sent',
            tenantId: $invitation->tenant_id,
            actorId: $invitation->invited_by,
            auditableType: TenantInvitation::class,
            auditableId: (string) $invitation->getKey(),
            after: [
                'channel' => 'mail',
                'recipient' => $invitation->email_normalized,
            ],
            source: 'QUEUE',
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $invitation = TenantInvitation::query()->find($this->invitationId);

        if ($invitation === null) {
            return;
        }

        $message = $exception?->getMessage() ?? 'Falha desconhecida no envio do convite.';

        Log::error('tenant.invitation.email_failed', [
            'tenant_id' => $invitation->tenant_id,
            'invitation_id' => (string) $invitation->getKey(),
            'to' => $invitation->email_normalized,
            'error' => $message,
        ]);

        app(AuditLogger::class)->record(new AuditEntryData(
            action: 'tenant.invitation.email_failed',
            tenantId: $invitation->tenant_id,
            actorId: $invitation->invited_by,
            auditableType: TenantInvitation::class,
            auditableId: (string) $invitation->getKey(),
            after: [
                'channel' => 'mail',
                'recipient' => $invitation->email_normalized,
                'error' => $message,
            ],
            source: 'QUEUE',
        ));
    }
}
