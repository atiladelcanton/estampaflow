<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('about:delka', function (): void {
    $contextVersion = (string) config('project.context_version', 'não configurada');

    $this->info('EstampaFlow — Sprint 1');
    $this->line("Contexto Mestre: v{$contextVersion}");
    $this->line('Status: tenancy, usuários, Mailpit, filas database e e-mail de boas-vindas implementados');
})->purpose('Exibe informações resumidas do projeto.');

Schedule::command('queue:prune-failed --hours=168')->dailyAt('03:00');
Schedule::command('queue:prune-batches --hours=48')->dailyAt('03:10');
