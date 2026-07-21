<?php

declare(strict_types=1);

use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use App\Support\Tenancy\UlidTenantIdGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Stancl\Tenancy\Contracts\UniqueIdentifierGenerator;

uses(RefreshDatabase::class);

it('hydrates tenancy enums and immutable dates with their domain types', function (): void {
    $owner = User::factory()->create();

    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Casts',
        'slug' => 'casts-'.Str::lower(Str::random(5)),
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $membership = TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    $invitation = TenantInvitation::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'email' => 'casts@example.com',
        'email_normalized' => 'casts@example.com',
        'pending_email_key' => 'casts@example.com',
        'role' => TenantRole::USER,
        'status' => InvitationStatus::PENDING,
        'token_hash' => hash('sha256', 'casts-token'),
        'invited_by' => $owner->getKey(),
        'expires_at' => now()->addDay(),
    ]);

    expect($tenant->fresh()->status)->toBe(TenantStatus::ACTIVE)
        ->and($tenant->fresh()->trial_ends_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($membership->fresh()->role)->toBe(TenantRole::OWNER)
        ->and($membership->fresh()->status)->toBe(MembershipStatus::ACTIVE)
        ->and($membership->fresh()->joined_at)->toBeInstanceOf(CarbonImmutable::class)
        ->and($invitation->fresh()->role)->toBe(TenantRole::USER)
        ->and($invitation->fresh()->status)->toBe(InvitationStatus::PENDING)
        ->and($invitation->fresh()->expires_at)->toBeInstanceOf(CarbonImmutable::class);
});

it('exposes the domains relation and uses the supported tenancy id generator contract', function (): void {
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Domínios',
        'slug' => 'dominios-'.Str::lower(Str::random(5)),
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $tenant->domains()->create([
        'domain' => $tenant->slug.'.estamparia.test',
    ]);

    expect($tenant->load('domains')->domains)->toHaveCount(1)
        ->and(is_subclass_of(UlidTenantIdGenerator::class, UniqueIdentifierGenerator::class))->toBeTrue()
        ->and(UlidTenantIdGenerator::generate($tenant))->toBeUlid();
});
