<?php

declare(strict_types=1);

use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;

it('creates the owner and tenant during public registration', function (): void {
    $response = $this->post('http://app.estamparia.test/register', [
        'business_name' => 'Estamparia Horizonte',
        'name' => 'Maria Horizonte',
        'email' => 'maria@horizonte.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()->where('email', 'maria@horizonte.test')->firstOrFail();
    $tenant = Tenant::query()->where('slug', 'estamparia-horizonte')->firstOrFail();

    $response->assertRedirect('http://estamparia-horizonte.estamparia.test/dashboard');
    expect($user->memberships()->where('tenant_id', $tenant->getTenantKey())->firstOrFail()->role)
        ->toBe(TenantRole::OWNER);
});

it('allows only platform admins on the central dashboard', function (): void {
    $regular = User::factory()->create(['is_platform_admin' => false]);
    $admin = User::factory()->create(['is_platform_admin' => true]);

    $this->actingAs($regular)->get('http://app.estamparia.test/dashboard')->assertForbidden();
    $this->actingAs($admin)->get('http://app.estamparia.test/dashboard')->assertOk();
});
