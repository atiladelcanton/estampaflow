<?php

declare(strict_types=1);

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exibe o cadastro público no domínio central', function (): void {
    $this->get('http://app.estamparia.test/register')
        ->assertOk()
        ->assertSee('Nome da estamparia');
});

it('cadastra Owner e estamparia e redireciona diretamente para o tenant', function (): void {
    $response = $this->post('http://app.estamparia.test/register', [
        'business_name' => 'Estamparia Horizonte',
        'name' => 'Maria Horizonte',
        'email' => 'maria-cadastro@estampaflow.test',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $user = User::query()
        ->where('email', 'maria-cadastro@estampaflow.test')
        ->firstOrFail();

    $tenant = Tenant::query()
        ->where('slug', 'estamparia-horizonte')
        ->firstOrFail();

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('http://estamparia-horizonte.estamparia.test/dashboard');

    $this->assertDatabaseHas('domains', [
        'tenant_id' => $tenant->getTenantKey(),
        'domain' => 'estamparia-horizonte.estamparia.test',
    ]);

    $this->assertDatabaseHas('tenant_memberships', [
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::OWNER->value,
        'status' => MembershipStatus::ACTIVE->value,
    ]);
});
