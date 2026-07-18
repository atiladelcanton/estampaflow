<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a tela de cadastro pode ser acessada no domínio central', function (): void {
    $this->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/register')
        ->assertOk();
});

test('novo usuário pode se cadastrar', function (): void {
    $response = $this->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->post('/register', [
            'name' => 'Usuário Delka',
            'email' => 'usuario@delka.local',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');
    $this->assertDatabaseHas('users', ['email' => 'usuario@delka.local']);
});
