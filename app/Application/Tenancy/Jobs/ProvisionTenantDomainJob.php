<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Jobs;

use App\Domains\Tenancy\Models\Domain;
use App\Support\Audit\AuditEntryData;
use App\Support\Audit\AuditLogger;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class ProvisionTenantDomainJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public int $uniqueFor = 300;

    public function __construct(public readonly int $domainId)
    {
        $this->onConnection('database');
        $this->onQueue('provisioning');
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->domainId;
    }

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [5, 30, 120];
    }

    public function handle(AuditLogger $auditLogger): void
    {
        $domain = Domain::query()->with('tenant')->find($this->domainId);

        if ($domain === null || $domain->isProvisioned()) {
            return;
        }

        $domain->markProcessing();

        $baseDomain = trim((string) config('tenancy.tenant_base_domain'));
        $centralDomains = config('tenancy.central_domains', []);

        if ($baseDomain === '' || ! Str::endsWith($domain->domain, '.'.$baseDomain)) {
            throw new RuntimeException('O domínio não pertence ao domínio-base configurado.');
        }

        if (in_array($domain->domain, $centralDomains, true)) {
            throw new RuntimeException('Um domínio central não pode ser provisionado como tenant.');
        }

        if (app()->environment('local', 'testing')) {
            Storage::disk('local')->put(
                'domain-provisioning/'.$domain->domain.'.hosts',
                '127.0.0.1\t'.$domain->domain.PHP_EOL,
            );
        }

        $domain->markProvisioned();

        $auditLogger->record(new AuditEntryData(
            action: 'tenant.domain.provisioned',
            tenantId: $domain->tenant_id,
            auditableType: Domain::class,
            auditableId: (string) $domain->getKey(),
            after: [
                'domain' => $domain->domain,
                'status' => $domain->provisioning_status->value,
                'mode' => app()->environment('production') ? 'WILDCARD_DNS' : 'LOCAL_SIMULATION',
            ],
            source: 'QUEUE',
        ));
    }

    public function failed(?Throwable $exception): void
    {
        $domain = Domain::query()->find($this->domainId);

        if ($domain === null) {
            return;
        }

        $message = $exception?->getMessage() ?? 'Falha desconhecida no provisionamento.';
        $domain->markFailed($message);

        app(AuditLogger::class)->record(new AuditEntryData(
            action: 'tenant.domain.provisioning_failed',
            tenantId: $domain->tenant_id,
            auditableType: Domain::class,
            auditableId: (string) $domain->getKey(),
            after: [
                'domain' => $domain->domain,
                'status' => $domain->provisioning_status->value,
                'error' => $message,
            ],
            source: 'QUEUE',
        ));
    }
}
