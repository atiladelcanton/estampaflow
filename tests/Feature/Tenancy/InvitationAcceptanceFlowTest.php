<?php

declare(strict_types=1);

use App\Application\Tenancy\Actions\InviteTenantUserAction;
use App\Application\Tenancy\Actions\RegisterInvitedUserAction;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function createInvitationAcceptanceFixture(): array
{
    $owner = User::factory()->create();
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Convites Teste',
        'slug' => 'convites-'.Str::lower(Str::random(5)),
        'status' => 'ACTIVE',
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);
    $tenant->domains()->create(['domain' => $tenant->slug.'.estamparia.test']);
    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    return [$tenant, $owner];
}

it('keeps the invitation page public', function (): void {
    Notification::fake();
    [$tenant, $owner] = createInvitationAcceptanceFixture();
    $result = app(InviteTenantUserAction::class)->execute($tenant, $owner, 'nova@convite.test', TenantRole::USER);

    $this->get($result->acceptUrl)->assertOk()->assertSee('Criar conta e entrar');
});

it('registers a new invited user without creating another tenant', function (): void {
    Notification::fake();
    [$tenant, $owner] = createInvitationAcceptanceFixture();
    $before = Tenant::query()->count();
    $result = app(InviteTenantUserAction::class)->execute($tenant, $owner, 'nova2@convite.test', TenantRole::USER);

    $registered = app(RegisterInvitedUserAction::class)->execute($result->plainToken, 'Nova Pessoa', 'password');

    expect(Tenant::query()->count())->toBe($before)
        ->and($registered->membership->tenant_id)->toBe($tenant->getTenantKey())
        ->and($registered->membership->status)->toBe(MembershipStatus::ACTIVE)
        ->and($result->invitation->fresh()->status)->toBe(InvitationStatus::ACCEPTED);
});
