<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Jobs;

use App\Domains\Tenancy\Models\Tenant;
use App\Mail\TenantWelcomeMail;
use App\Models\User;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

final class SendTenantWelcomeEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 3600;

    public function __construct(
        public readonly string $userId,
        public readonly string $tenantId,
    ) {
        $this->onConnection('database');
        $this->onQueue('mail');
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return $this->tenantId.':'.$this->userId;
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
        $user = User::query()->find($this->userId);
        $tenant = Tenant::query()->with('domains')->find($this->tenantId);

        if ($user === null || $tenant === null) {
            Log::warning('tenant.welcome_email.skipped', [
                'tenant_id' => $this->tenantId,
                'user_id' => $this->userId,
                'reason' => 'user_or_tenant_not_found',
            ]);

            return;
        }

        $tenantUrl = $urls->for($tenant);
        $loginUrl = $urls->for($tenant, '/login');
        $passwordResetUrl = $urls->central('/forgot-password');
        $trialEndsAt = $tenant->trial_ends_at
            ?->timezone($tenant->timezone)
            ->format('d/m/Y') ?? 'não informado';

        Mail::to($user->email)->send(new TenantWelcomeMail(
            userName: $user->name,
            tenantName: $tenant->name,
            loginEmail: $user->email,
            tenantUrl: $tenantUrl,
            loginUrl: $loginUrl,
            passwordResetUrl: $passwordResetUrl,
            trialEndsAt: $trialEndsAt,
        ));

        Log::info('tenant.welcome_email.sent', [
            'tenant_id' => (string) $tenant->getTenantKey(),
            'user_id' => (string) $user->getKey(),
            'to' => $user->email,
            'tenant_url' => $tenantUrl,
        ]);

        $auditLogger->record(new AuditEntryData(
            action: 'tenant.welcome_email.sent',
            tenantId: (string) $tenant->getTenantKey(),
            actorId: (string) $user->getKey(),
            auditableType: User::class,
            auditableId: (string) $user->getKey(),
            after: [
                'channel' => 'mail',
                'recipient' => $user->email,
                'tenant_url' => $tenantUrl,
                'queue' => 'mail',
            ],
            source: 'QUEUE',
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $message = $exception?->getMessage() ?? 'Falha desconhecida no envio do e-mail de boas-vindas.';

        Log::error('tenant.welcome_email.failed', [
            'tenant_id' => $this->tenantId,
            'user_id' => $this->userId,
            'error' => $message,
        ]);

        app(AuditLogger::class)->record(new AuditEntryData(
            action: 'tenant.welcome_email.failed',
            tenantId: $this->tenantId,
            actorId: $this->userId,
            auditableType: User::class,
            auditableId: $this->userId,
            after: [
                'queue' => 'mail',
                'error' => $message,
            ],
            source: 'QUEUE',
        ));
    }
}
