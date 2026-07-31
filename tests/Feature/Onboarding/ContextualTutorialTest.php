<?php

declare(strict_types=1);

use App\Application\Tenancy\Actions\CreateTenantAction;
use App\Application\Tenancy\Data\CreateTenantData;
use App\Domains\Onboarding\Models\UserOnboardingProgress;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exibe tutorial contextual e salva conclusão por usuário e tenant', function (): void {
    $owner = User::factory()->create();
    $tenant = app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Tutorial Contextual',
        slug: 'tutorial-contextual',
        domain: 'tutorial-contextual.estamparia.test',
        owner: $owner,
    ));

    $this->actingAs($owner)
        ->get('http://tutorial-contextual.estamparia.test/dashboard')
        ->assertOk()
        ->assertSee('dashboard-owner')
        ->assertSee('Tutorial desta página');

    $this->actingAs($owner)
        ->postJson('http://tutorial-contextual.estamparia.test/tutoriais/dashboard-owner/concluir')
        ->assertOk()
        ->assertJson(['ok' => true]);

    $progress = UserOnboardingProgress::query()->firstOrFail();

    expect($progress->tenant_id)->toBe((string) $tenant->getTenantKey())
        ->and($progress->user_id)->toBe((string) $owner->getKey())
        ->and($progress->tutorial_key)->toBe('dashboard-owner')
        ->and($progress->completed_at)->not->toBeNull();
});

it('mostra somente artigos permitidos pelo papel', function (): void {
    $owner = User::factory()->create();
    app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Ajuda Owner',
        slug: 'ajuda-owner',
        domain: 'ajuda-owner.estamparia.test',
        owner: $owner,
    ));

    $this->actingAs($owner)
        ->get('http://ajuda-owner.estamparia.test/ajuda')
        ->assertOk()
        ->assertSee('Primeiros passos')
        ->assertSee('Tipos de serviço')
        ->assertSee('Convidando e gerenciando a equipe');
});

it('esconde tutoriais administrativos de usuário comum', function (): void {
    $owner = User::factory()->create();
    $tenant = app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Ajuda da Equipe',
        slug: 'ajuda-equipe',
        domain: 'ajuda-equipe.estamparia.test',
        owner: $owner,
    ));
    $user = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::USER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('http://ajuda-equipe.estamparia.test/ajuda')
        ->assertOk()
        ->assertSee('Entendendo a visão geral')
        ->assertDontSee('Tipos de serviço')
        ->assertDontSee('Convidando e gerenciando a equipe');

    $this->actingAs($user)
        ->get('http://ajuda-equipe.estamparia.test/ajuda/tipos-de-servico')
        ->assertNotFound();
});
