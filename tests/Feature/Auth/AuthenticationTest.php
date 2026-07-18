<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tela de login pode ser acessada no domínio central', function (): void {
    $this->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/login')
        ->assertOk();
});

test('usuário pode autenticar', function (): void {
    $user = User::factory()->create([
        'password' => 'password',
    ]);

    $response = $this->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');
});

test('usuário pode sair', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
