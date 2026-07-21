<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->platformAdmin = User::factory()->create([
        'is_platform_admin' => true,
    ]);
});

it('Platform Admin visualiza a gestão central', function (): void {
    $this->actingAs($this->platformAdmin)
        ->get('http://app.estamparia.test/dashboard')
        ->assertOk()
        ->assertSee('Administração da plataforma')
        ->assertSee('Clientes da plataforma');
});

it('Platform Admin visualiza a demonstração de produtos', function (): void {
    $this->actingAs($this->platformAdmin)
        ->get('http://app.estamparia.test/ui/produtos')
        ->assertOk()
        ->assertSee('Demonstração visual');
});

it('Platform Admin visualiza o formulário demonstrativo de produto', function (): void {
    $this->actingAs($this->platformAdmin)
        ->get('http://app.estamparia.test/ui/produtos/novo')
        ->assertOk()
        ->assertSee('Adicionar produto');
});

it('Platform Admin visualiza o guia visual', function (): void {
    $this->actingAs($this->platformAdmin)
        ->get('http://app.estamparia.test/ui/guia-visual')
        ->assertOk()
        ->assertSee('Guia visual do EstampaFlow');
});

it('usuário comum não acessa as telas centrais', function (): void {
    $user = User::factory()->create([
        'is_platform_admin' => false,
    ]);

    $this->actingAs($user)
        ->get('http://app.estamparia.test/dashboard')
        ->assertForbidden();
});
