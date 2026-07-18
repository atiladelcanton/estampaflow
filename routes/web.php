<?php

declare(strict_types=1);

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

    Route::middleware('tenant.owner')->group(function (): void {
        Route::view('/equipe', 'tenant.users')->name('tenant.users');
    });
});
