<?php

declare(strict_types=1);

use App\Application\Tenancy\Actions\CreateTenantAction;
use App\Application\Tenancy\Data\CreateTenantData;
use App\Domains\Onboarding\Models\UserOnboardingProgress;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Domains\ServiceCatalog\Services\DefaultServiceCatalogService;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use App\Support\Tenancy\TenantId;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOnboardingTenant(string $slug, User $owner): Tenant
{
    $tenant = app(CreateTenantAction::class)->execute(new CreateTenantData(
        name: 'Estamparia '.ucfirst($slug),
        slug: $slug,
        domain: $slug.'.estamparia.test',
        owner: $owner,
    ));

    app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        fn () => app(DefaultServiceCatalogService::class)->createDefaultsFor($owner),
    );

    return $tenant;
}

it('envia owner sem progresso para os primeiros passos ao autenticar', function (): void {
    $owner = User::factory()->create([
        'email' => 'owner-onboarding@estampaflow.test',
        'password' => 'password',
    ]);
    createOnboardingTenant('onboarding-login', $owner);

    $this->post('http://app.estamparia.test/login', [
        'email' => $owner->email,
        'password' => 'password',
    ])->assertRedirect('http://onboarding-login.estamparia.test/primeiros-passos');
});

it('permite pular e retomar os primeiros passos', function (): void {
    $owner = User::factory()->create();
    $tenant = createOnboardingTenant('onboarding-skip', $owner);

    $this->actingAs($owner)
        ->post('http://onboarding-skip.estamparia.test/primeiros-passos/pular')
        ->assertRedirect('http://onboarding-skip.estamparia.test/dashboard');

    $this->assertDatabaseHas('user_onboarding_progress', [
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'tutorial_key' => 'owner-first-steps',
        'version' => 1,
    ]);

    expect(UserOnboardingProgress::query()->firstOrFail()->dismissed_at)->not->toBeNull();
});

it('conclui o wizard e mantém apenas os serviços selecionados ativos', function (): void {
    $owner = User::factory()->create();
    $tenant = createOnboardingTenant('onboarding-complete', $owner);
    $dtfId = null;

    app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        function () use (&$dtfId): void {
            $dtfId = ServiceType::query()->where('code', 'DTF')->value('id');
        },
    );

    expect($dtfId)->toBeString();

    $this->actingAs($owner)
        ->post('http://onboarding-complete.estamparia.test/primeiros-passos/concluir', [
            'business_name' => 'Ateliê Pronto',
            'timezone' => 'America/Sao_Paulo',
            'services' => [$dtfId],
            'invitation_email' => '',
        ])
        ->assertRedirect('http://onboarding-complete.estamparia.test/dashboard');

    expect($tenant->refresh()->name)->toBe('Ateliê Pronto');

    app(TenantContext::class)->run(
        new TenantId((string) $tenant->getTenantKey()),
        function (): void {
            expect(ServiceType::query()->where('active', true)->pluck('code')->all())
                ->toBe(['DTF']);
        },
    );

    $this->assertDatabaseHas('user_onboarding_progress', [
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'tutorial_key' => 'owner-first-steps',
    ]);
});

it('não força wizard para usuário comum', function (): void {
    $owner = User::factory()->create();
    $tenant = createOnboardingTenant('onboarding-user', $owner);
    $user = User::factory()->create([
        'email' => 'equipe-onboarding@estampaflow.test',
        'password' => 'password',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::USER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $this->post('http://app.estamparia.test/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect('http://onboarding-user.estamparia.test/dashboard');
});
