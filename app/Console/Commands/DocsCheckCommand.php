<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class DocsCheckCommand extends Command
{
    protected $signature = 'docs:check';

    protected $description = 'Valida o contexto, ADRs e documentos obrigatórios das sprints concluídas.';

    public function handle(): int
    {
        $errors = [];
        $contextVersion = config('project.context_version');
        $contextFile = config('project.context_file');

        if (! is_string($contextVersion) || $contextVersion === '') {
            $errors[] = 'A versão do contexto não foi definida em config/project.php.';
        }

        if (! is_string($contextFile) || $contextFile === '') {
            $errors[] = 'O arquivo do contexto não foi definido em config/project.php.';
            $contextFile = '__contexto_nao_configurado__.md';
        }

        $requiredFiles = [
            $contextFile,
            'docs/adr/README.md',
            'docs/sprints/sprint-00-foundation.md',
            'docs/sprints/sprint-00-audit-report.md',
            'docs/sprints/sprint-00-implementation-matrix.md',
            'docs/sprints/sprint-01-tenancy-users.md',
            'docs/sprints/sprint-01-implementation-report.md',
            'docs/sprints/sprint-01-source-validation.md',
            'docs/sprints/sprint-01-flow-correction.md',
            'docs/sprints/sprint-01-async-infrastructure.md',
            'docs/sprints/sprint-01-welcome-email.md',
            'docs/domains/tenancy/README.md',
            'docs/sprints/sprint-02-service-catalog.md',
            'docs/sprints/sprint-02-implementation-report.md',
            'docs/domains/service-catalog/README.md',
            'docs/ui/style-guide.md',
        ];

        foreach ($requiredFiles as $file) {
            if (! File::exists(base_path($file))) {
                $errors[] = "Arquivo obrigatório ausente: {$file}";
            }
        }

        $adrReadmePath = base_path('docs/adr/README.md');

        if (
            is_string($contextVersion)
            && File::exists($adrReadmePath)
            && ! str_contains((string) File::get($adrReadmePath), "Contexto Mestre v{$contextVersion}")
        ) {
            $errors[] = "O índice de ADRs não referencia o Contexto Mestre v{$contextVersion}.";
        }

        $adrFiles = collect(File::glob(base_path('docs/adr/????-*.md')) ?: []);

        if ($adrFiles->count() !== 12) {
            $errors[] = 'Devem existir exatamente 12 ADRs numerados de 0001 a 0012.';
        }

        foreach ($adrFiles as $adr) {
            if (! str_contains((string) File::get($adr), '**Status:** ACCEPTED')) {
                $errors[] = 'ADR sem status ACCEPTED: '.basename($adr);
            }
        }

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $this->info("Documentação obrigatória das Sprints 0, 1 e 2 validada com o Contexto Mestre v{$contextVersion}.");

        return self::SUCCESS;
    }
}
