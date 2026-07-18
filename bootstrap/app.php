<?php

use App\Console\Commands\DocsCheckCommand;
use App\Console\Commands\DocsGenerateCommand;
use App\Console\Commands\ProjectAuditCommand;
use App\Http\Middleware\AttachCorrelationId;
use App\Http\Middleware\EnsureActiveTenantMembership;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantOwner;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            AttachCorrelationId::class,
        ]);

        $middleware->alias([
            'platform.admin' => EnsurePlatformAdmin::class,
            'tenant.member' => EnsureActiveTenantMembership::class,
            'tenant.owner' => EnsureTenantOwner::class,
        ]);
    })
    ->withCommands([
        DocsGenerateCommand::class,
        DocsCheckCommand::class,
        ProjectAuditCommand::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
