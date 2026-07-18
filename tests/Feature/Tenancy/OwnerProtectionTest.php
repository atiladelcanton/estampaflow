<?php

use App\Application\Tenancy\Actions\ChangeTenantMembershipAction;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Exceptions\TenantAuthorizationException;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('does not allow the last active owner to be downgraded', function (): void {
    $owner = User::factory()->create();
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Owner Guard',
        'slug' => 'owner-guard',
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

    expect(fn () => app(ChangeTenantMembershipAction::class)->execute(
        $owner,
        $membership,
        role: TenantRole::USER,
    ))->toThrow(TenantAuthorizationException::class);
});
