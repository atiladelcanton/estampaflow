<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->user = User::factory()->create();
});

test('usuário autenticado visualiza seletor de ambientes', function (): void {
    $this->actingAs($this->user)
        ->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Seus ambientes');
});

test('usuário autenticado visualiza demonstração de produtos', function (): void {
    $this->actingAs($this->user)
        ->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/ui/produtos')
        ->assertOk()
        ->assertSee('Demonstração visual');
});

test('usuário autenticado visualiza formulário de produto', function (): void {
    $this->actingAs($this->user)
        ->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/ui/produtos/novo')
        ->assertOk()
        ->assertSee('Adicionar produto');
});

test('usuário autenticado visualiza guia visual', function (): void {
    $this->actingAs($this->user)
        ->withServerVariables(['HTTP_HOST' => 'app.estamparia.test'])
        ->get('/ui/guia-visual')
        ->assertOk()
        ->assertSee('Guia visual da Delka');
});
