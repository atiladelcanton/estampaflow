<?php

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

        $requiredFiles = [
            'delka-estamparia-contexto-v2.4.md',
            'docs/adr/README.md',
            'docs/sprints/sprint-00-foundation.md',
            'docs/sprints/sprint-00-audit-report.md',
            'docs/sprints/sprint-00-implementation-matrix.md',
            'docs/sprints/sprint-01-tenancy-users.md',
            'docs/sprints/sprint-01-implementation-report.md',
            'docs/sprints/sprint-01-source-validation.md',
            'docs/sprints/sprint-01-flow-correction.md',
            'docs/domains/tenancy/README.md',
            'docs/ui/style-guide.md',
        ];

        foreach ($requiredFiles as $file) {
            if (! File::exists(base_path($file))) {
                $errors[] = "Arquivo obrigatório ausente: {$file}";
            }
        }

        $adrFiles = collect(File::glob(base_path('docs/adr/????-*.md')) ?: []);

        if ($adrFiles->count() !== 11) {
            $errors[] = 'Devem existir exatamente 11 ADRs numerados de 0001 a 0011.';
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

        $this->info('Documentação obrigatória das Sprints 0 e 1 validada.');

        return self::SUCCESS;
    }
}
