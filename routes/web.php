<?php

declare(strict_types=1);

use App\Http\Controllers\Tenant\HelpCenterController;
use App\Http\Controllers\Tenant\OnboardingProgressController;
use App\Http\Controllers\Tenant\OnboardingWizardController;
use App\Http\Controllers\Tenant\ServiceFieldsController;
use App\Http\Controllers\Tenant\ServicePricingController;
use App\Http\Middleware\InitializeTenancyForRequest;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

$centralDomain = config('tenancy.central_domains.0', 'app.estamparia.test');

Route::domain($centralDomain)->group(function (): void {
    Route::view('/', 'welcome')->name('home');

    Route::get('/convites/{token}', static fn (string $token) => view('invitations.accept', compact('token')))
        ->name('invitations.accept');

    Route::middleware(['auth', 'platform.admin'])->group(function (): void {
        Route::view('/dashboard', 'central.dashboard')->name('platform.dashboard');
        Route::view('/ui/produtos', 'ui.products')->name('ui.products');
        Route::view('/ui/produtos/novo', 'ui.product-form')->name('ui.products.create');
        Route::view('/ui/guia-visual', 'ui.style-guide')->name('ui.style-guide');
    });
});

Route::middleware([
    InitializeTenancyForRequest::class,
    PreventAccessFromCentralDomains::class,
    'auth',
    'tenant.member',
])->group(function (): void {
    Route::redirect('/', '/dashboard')->name('tenant.home');
    Route::view('/dashboard', 'tenant.dashboard')->name('tenant.dashboard');

    Route::get('/ajuda', [HelpCenterController::class, 'index'])->name('tenant.help.index');
    Route::get('/ajuda/{article}', [HelpCenterController::class, 'show'])->name('tenant.help.show');
    Route::post('/tutoriais/{tutorialKey}/concluir', [OnboardingProgressController::class, 'complete'])
        ->where('tutorialKey', '[A-Za-z0-9\-]+')
        ->name('tenant.tutorials.complete');
    Route::post('/tutoriais/{tutorialKey}/dispensar', [OnboardingProgressController::class, 'dismiss'])
        ->where('tutorialKey', '[A-Za-z0-9\-]+')
        ->name('tenant.tutorials.dismiss');

    Route::middleware('tenant.owner')->group(function (): void {
        Route::get('/primeiros-passos', [OnboardingWizardController::class, 'show'])->name('tenant.onboarding.show');
        Route::post('/primeiros-passos/concluir', [OnboardingWizardController::class, 'complete'])->name('tenant.onboarding.complete');
        Route::post('/primeiros-passos/pular', [OnboardingWizardController::class, 'skip'])->name('tenant.onboarding.skip');

        Route::view('/equipe', 'tenant.users')->name('tenant.users');
        Route::get('/configuracoes/precos', [ServicePricingController::class, 'index'])->name('tenant.pricing.index');
        Route::get('/configuracoes/servicos/{serviceType}/precos', [ServicePricingController::class, 'edit'])->name('tenant.pricing.edit');
        Route::put('/configuracoes/servicos/{serviceType}/precos', [ServicePricingController::class, 'update'])->name('tenant.pricing.update');
        Route::post('/configuracoes/servicos/{serviceType}/precos/simular', [ServicePricingController::class, 'simulate'])->name('tenant.pricing.simulate');

        Route::view('/configuracoes/servicos', 'tenant.service-types.index')->name('tenant.service-types.index');
        Route::view('/configuracoes/servicos/novo', 'tenant.service-types.form')->name('tenant.service-types.create');
        Route::get('/configuracoes/servicos/{serviceType}/editar', static fn (string $serviceType) => view('tenant.service-types.form', [
            'serviceTypeId' => $serviceType,
        ]))->name('tenant.service-types.edit');
        Route::get('/configuracoes/servicos/{serviceType}/campos', [ServiceFieldsController::class, 'show'])
            ->name('tenant.service-types.fields');
        Route::patch('/configuracoes/servicos/{serviceType}/campos', [ServiceFieldsController::class, 'update'])
            ->name('tenant.service-types.fields.update');

        Route::get('/configuracoes/servicos/{serviceType}/schema', [ServiceFieldsController::class, 'show'])
            ->name('tenant.service-types.schema');
    });
});
