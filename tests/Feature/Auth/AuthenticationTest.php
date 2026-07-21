<?php

declare(strict_types=1);

use App\Application\Tenancy\Actions\CreateTenantAction;
use App\Application\Tenancy\Data\CreateTenantData;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exibe a tela de login no domínio central', function (): void {
    $this->get('http://app.estamparia.test/login')
        ->assertOk();
});

it('autentica o Platform Admin e redireciona para a gestão central', function (): void {
    $admin = User::factory()->create([
        'email' => 'admin-auth@estampaflow.test',
        'password' => 'password',
        'is_platform_admin' => true,
    ]);

    $response = $this->post('http://app.estamparia.test/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect('http://app.estamparia.test/dashboard');
});

it('autentica o Owner e redireciona diretamente para o tenant', function (): void {
    $owner = User::factory()->create([
        'email' => 'owner-auth@estampaflow.test',
        'password' => 'password',
    ]);

    app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Estamparia Login',
        slug: 'estamparia-login',
        domain: 'estamparia-login.estamparia.test',
        owner: $owner,
    ));

    $response = $this->post('http://app.estamparia.test/login', [
        'email' => $owner->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($owner);
    $response->assertRedirect('http://estamparia-login.estamparia.test/dashboard');
});

it('permite encerrar a sessão', function (): void {
    $admin = User::factory()->create([
        'is_platform_admin' => true,
    ]);

    $response = $this->actingAs($admin)
        ->post('http://app.estamparia.test/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
