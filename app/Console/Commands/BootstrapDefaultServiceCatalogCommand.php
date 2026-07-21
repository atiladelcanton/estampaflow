<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domains\ServiceCatalog\Services\DefaultServiceCatalogService;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

final class BootstrapDefaultServiceCatalogCommand extends Command
{
    protected $signature = 'service-catalog:bootstrap-defaults {--tenant= : ULID de um tenant específico}';

    protected $description = 'Cria os serviços produtivos padrão nos tenants sem sobrescrever configurações existentes.';

    public function handle(
        TenantContext $tenantContext,
        DefaultServiceCatalogService $defaultCatalog,
    ): int {
        $tenantId = $this->option('tenant');
        $query = Tenant::query()->orderBy('created_at');

        if (is_string($tenantId) && $tenantId !== '') {
            $query->whereKey($tenantId);
        }

        $processed = 0;

        /** @var Collection<int, Tenant> $tenants */
        $tenants = $query->get();

        foreach ($tenants as $tenant) {
            $membership = $tenant->memberships()
                ->with('user')
                ->where('role', TenantRole::OWNER->value)
                ->where('status', MembershipStatus::ACTIVE->value)
                ->oldest('joined_at')
                ->first();

            $owner = $membership?->user;

            if (! $owner instanceof User) {
                $this->warn("Tenant {$tenant->name} ignorado: nenhum Owner ativo.");

                continue;
            }

            $tenantContext->run(new TenantId((string) $tenant->getTenantKey()), function () use ($defaultCatalog, $owner): void {
                $defaultCatalog->createDefaultsFor($owner);
            });

            $processed++;
            $this->line("Catálogo verificado: {$tenant->name}");
        }

        $this->info("{$processed} tenant(s) processado(s).");

        return self::SUCCESS;
    }
}
