<?php

namespace App\Providers;

use App\Domains\Onboarding\Services\OnboardingProgressService;
use App\Domains\Onboarding\Services\OnboardingRegistry;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Services\TenantMembershipService;
use App\Http\Middleware\EnsureActiveTenantMembership;
use App\Http\Middleware\EnsureTenantOwner;
use App\Http\Middleware\InitializeTenancyForRequest;
use App\Models\User;
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
        $this->app->scoped(OnboardingProgressService::class);
        $this->app->singleton(OnboardingRegistry::class);
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

            $currentTutorial = null;
            $currentTutorialAcknowledged = true;
            $onboardingWizardPending = false;

            $user = auth()->user();

            if ($currentTenant !== null && $currentMembership !== null && $user instanceof User) {
                $registry = app(OnboardingRegistry::class);
                $progress = app(OnboardingProgressService::class);
                $tenantId = (string) $currentTenant->getTenantKey();
                $routeName = request()->route()?->getName();
                $currentTutorial = $registry->tutorialForRoute($routeName, $currentMembership->role);

                if (is_array($currentTutorial)) {
                    $currentTutorialAcknowledged = $progress->isAcknowledged(
                        $user,
                        $tenantId,
                        (string) ($currentTutorial['key'] ?? ''),
                        (int) ($currentTutorial['version'] ?? 1),
                    );
                }

                if ($currentMembership->isOwner()) {
                    $wizard = $registry->wizard();
                    $onboardingWizardPending = ! $progress->isAcknowledged(
                        $user,
                        $tenantId,
                        (string) ($wizard['key'] ?? 'owner-first-steps'),
                        (int) ($wizard['version'] ?? 1),
                    );
                }
            }

            $view->with([
                'currentTenant' => $currentTenant,
                'currentMembership' => $currentMembership,
                'currentTutorial' => $currentTutorial,
                'currentTutorialAcknowledged' => $currentTutorialAcknowledged,
                'onboardingWizardPending' => $onboardingWizardPending,
            ]);
        });
    }
}
