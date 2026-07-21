<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Application\Tenancy\Jobs\ProvisionTenantDomainJob;
use App\Domains\Tenancy\Enums\DomainProvisioningStatus;
use App\Domains\Tenancy\Models\Domain;
use Illuminate\Console\Command;

final class ProvisionPendingDomainsCommand extends Command
{
    protected $signature = 'domain:provision-pending {--include-failed : Reenvia também provisionamentos com falha}';

    protected $description = 'Enfileira domínios pendentes para provisionamento assíncrono.';

    public function handle(): int
    {
        $statuses = [DomainProvisioningStatus::PENDING->value];

        if ($this->option('include-failed')) {
            $statuses[] = DomainProvisioningStatus::FAILED->value;
        }

        $count = 0;

        Domain::query()
            ->whereIn('provisioning_status', $statuses)
            ->orderBy('id')
            ->eachById(function (Domain $domain) use (&$count): void {
                ProvisionTenantDomainJob::dispatch((int) $domain->getKey());
                $count++;
            });

        $this->info("{$count} domínio(s) enviado(s) para a fila provisioning.");

        return self::SUCCESS;
    }
}
