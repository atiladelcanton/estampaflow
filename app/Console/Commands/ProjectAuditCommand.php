<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class ProjectAuditCommand extends Command
{
    protected $signature = 'project:audit {--write : Atualiza o relatório de runtime da Sprint 1}';

    protected $description = 'Audita a fundação e o módulo de tenancy do projeto.';

    public function handle(): int
    {
        $checks = [
            ['Laravel app', File::exists(base_path('artisan'))],
            ['Fortify', File::exists(app_path('Providers/FortifyServiceProvider.php'))],
            ['AuditLog', File::exists(app_path('Models/AuditLog.php'))],
            ['Correlation middleware', File::exists(app_path('Http/Middleware/AttachCorrelationId.php'))],
            ['Tenant model', File::exists(app_path('Domains/Tenancy/Models/Tenant.php'))],
            ['Membership model', File::exists(app_path('Domains/Tenancy/Models/TenantMembership.php'))],
            ['Invitation model', File::exists(app_path('Domains/Tenancy/Models/TenantInvitation.php'))],
            ['Stancl TenantContext', File::exists(app_path('Support/Tenancy/StanclTenantContext.php'))],
            ['Tenant middleware', File::exists(app_path('Http/Middleware/EnsureActiveTenantMembership.php'))],
            ['Owner middleware', File::exists(app_path('Http/Middleware/EnsureTenantOwner.php'))],
            ['Onboarding', File::exists(app_path('Livewire/TenantOnboarding.php'))],
            ['Equipe', File::exists(app_path('Livewire/TenantUsers.php'))],
            ['MySQL 8.4', str_contains((string) File::get(base_path('compose.yaml')), 'mysql:8.4')],
            ['ADRs', count(File::glob(base_path('docs/adr/????-*.md')) ?: []) === 10],
            ['Design system', File::exists(base_path('docs/ui/style-guide.md'))],
        ];

        $this->table(['Item', 'Estado'], array_map(
            fn (array $check): array => [$check[0], $check[1] ? 'IMPLEMENTED' : 'BLOCKED'],
            $checks,
        ));

        if ($this->option('write')) {
            $path = base_path('docs/sprints/sprint-01-runtime-audit.md');
            $lines = [
                '# Auditoria de Runtime — Sprint 1',
                '',
                '- Gerado em: '.now()->utc()->toIso8601String(),
                '',
                '| Item | Estado |',
                '|---|---|',
            ];

            foreach ($checks as [$item, $ok]) {
                $lines[] = "| {$item} | ".($ok ? 'IMPLEMENTED' : 'BLOCKED').' |';
            }

            File::put($path, implode(PHP_EOL, $lines).PHP_EOL);
            $this->info('Relatório escrito em docs/sprints/sprint-01-runtime-audit.md.');
        }

        return collect($checks)->every(fn (array $check): bool => $check[1])
            ? self::SUCCESS
            : self::FAILURE;
    }
}
