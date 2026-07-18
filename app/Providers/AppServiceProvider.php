<?php

namespace App\Providers;

use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Http\Middleware\EnsureActiveTenantMembership;
use App\Http\Middleware\EnsureTenantOwner;
use App\Http\Middleware\InitializeTenancyForRequest;
use App\Support\Audit\AuditLogger;
use App\Support\Auth\AuthenticatedDestinationResolver;
use App\Support\Correlation\CorrelationContext;
use App\Support\Tenancy\StanclTenantContext;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CorrelationContext::class);
        $this->app->scoped(TenantContext::class, StanclTenantContext::class);
        $this->app->scoped(TenantMembershipService::class);
        $this->app->scoped(TenantUrlGenerator::class);
        $this->app->scoped(AuditLogger::class);
        $this->app->scoped(AuthenticatedDestinationResolver::class);
    }

    public function boot(): void
    {
        /*
         * O endpoint de update do Livewire é uma nova requisição HTTP. Ele não
         * passa automaticamente pelo grupo de rotas do tenant, portanto precisa
         * resolver o tenant pelo Host antes de hidratar e executar o componente.
         * O $path mantém o endpoint com hash exigido pelo Livewire 4.
         */
        Livewire::setUpdateRoute(function ($handle, string $path) {
            return Route::post($path, $handle)
                ->middleware([
                    'web',
                    InitializeTenancyForRequest::class,
                ])
                ->name('livewire.tenant-aware-update');
        });

        /*
         * Estes middlewares só são reaplicados quando estavam presentes na rota
         * que carregou o componente. Assim a área central continua funcionando,
         * enquanto ações do tenant revalidam membership e papel a cada update.
         */
        Livewire::addPersistentMiddleware([
            PreventAccessFromCentralDomains::class,
            EnsureActiveTenantMembership::class,
            EnsureTenantOwner::class,
        ]);

        View::composer('components.layouts.app', function ($view): void {
            $tenantContext = app(TenantContext::class);
            $currentTenant = null;
            $currentMembership = null;

            if ($tenantContext->hasTenant()) {
                $resolved = tenant();
                $currentTenant = $resolved instanceof Tenant ? $resolved : null;

                if ($currentTenant !== null && auth()->check()) {
                    $currentMembership = auth()->user()
                        ->activeMembershipFor((string) $currentTenant->getTenantKey());
                }
            }

            $view->with([
                'currentTenant' => $currentTenant,
                'currentMembership' => $currentMembership,
            ]);
        });
    }
}
