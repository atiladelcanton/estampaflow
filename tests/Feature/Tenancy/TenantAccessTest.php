<?php

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function createTenantForAccessTest(string $slug): Tenant
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => ucfirst($slug),
        'slug' => $slug,
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $tenant->domains()->create(['domain' => $slug.'.estamparia.test']);

    return $tenant;
}

it('allows an active member on the resolved tenant domain', function (): void {
    $tenant = createTenantForAccessTest('alpha-access');
    $user = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::USER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('http://alpha-access.estamparia.test/dashboard')
        ->assertOk()
        ->assertSeeText('Alpha-access')
        ->assertSeeText('Seu papel')
        ->assertDontSeeText('Gerenciar equipe');
});

it('shows team management to the active owner', function (): void {
    $tenant = createTenantForAccessTest('owner-dashboard');
    $owner = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $this->actingAs($owner)
        ->get('http://owner-dashboard.estamparia.test/dashboard')
        ->assertOk()
        ->assertSeeText('Gerenciar equipe')
        ->assertSeeText('Proprietário');
});

it('blocks a user without membership from another tenant', function (): void {
    $alpha = createTenantForAccessTest('alpha-block');
    $beta = createTenantForAccessTest('beta-block');
    $user = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $beta->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('http://alpha-block.estamparia.test/dashboard')
        ->assertForbidden();
});

it('blocks suspended membership', function (): void {
    $tenant = createTenantForAccessTest('suspended');
    $user = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $user->getKey(),
        'role' => TenantRole::USER,
        'status' => MembershipStatus::SUSPENDED,
        'joined_at' => now(),
    ]);

    $this->actingAs($user)
        ->get('http://suspended.estamparia.test/dashboard')
        ->assertForbidden();
});
