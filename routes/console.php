<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:delka', function (): void {
    $this->info('Delka Estamparia — Sprint 1');
    $this->line('Contexto Mestre: v2.3');
    $this->line('Status: tenancy e usuários implementados');
})->purpose('Exibe informações resumidas do projeto.');
