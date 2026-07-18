<?php

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Livewire\TenantUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('creates an invitation from the tenant users Livewire component', function (): void {
    Notification::fake();

    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Tenant Livewire',
        'slug' => 'tenant-livewire',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);
    $tenant->domains()->create(['domain' => 'tenant-livewire.estamparia.test']);

    $owner = User::factory()->create();

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    tenancy()->initialize($tenant);

    Livewire::actingAs($owner)
        ->test(TenantUsers::class)
        ->set('email', 'convidado@example.com')
        ->set('role', TenantRole::USER->value)
        ->call('invite')
        ->assertHasNoErrors()
        ->assertSet('email', '')
        ->assertSet('role', TenantRole::USER->value);

    expect(TenantInvitation::query()
        ->where('tenant_id', $tenant->getTenantKey())
        ->where('email_normalized', 'convidado@example.com')
        ->exists())->toBeTrue();
});
