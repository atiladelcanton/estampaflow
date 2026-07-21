<?php

declare(strict_types=1);

use App\Application\Tenancy\Actions\AcceptTenantInvitationAction;
use App\Application\Tenancy\Actions\InviteTenantUserAction;
use App\Application\Tenancy\Jobs\SendTenantInvitationEmailJob;
use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Notification::fake();
    Queue::fake();
});

function createInvitationFlowTenant(User $owner): Tenant
{
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Convites',
        'slug' => 'convites-'.Str::lower(Str::random(6)),
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $tenant->domains()->create([
        'domain' => $tenant->slug.'.estamparia.test',
    ]);

    TenantMembership::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'user_id' => $owner->getKey(),
        'role' => TenantRole::OWNER,
        'status' => MembershipStatus::ACTIVE,
        'joined_at' => now(),
    ]);

    return $tenant;
}

it('stores invitation token only as hash, audits delivery and accepts it for matching email', function (): void {
    $owner = User::factory()->create();
    $invited = User::factory()->create(['email' => 'convite@example.com']);
    $tenant = createInvitationFlowTenant($owner);

    $created = app(InviteTenantUserAction::class)->execute(
        $tenant,
        $owner,
        'Convite@Example.com',
        TenantRole::USER,
    );

    expect($created->invitation->token_hash)
        ->toBe(hash('sha256', $created->plainToken))
        ->not->toContain($created->plainToken)
        ->and($created->emailQueued)->toBeTrue()
        ->and(AuditLog::query()->where('action', 'tenant.invitation.created')->exists())->toBeTrue()
        ->and(AuditLog::query()->where('action', 'tenant.invitation.email_queued')->exists())->toBeTrue();

    Queue::assertPushed(SendTenantInvitationEmailJob::class);

    $membership = app(AcceptTenantInvitationAction::class)->execute(
        $created->plainToken,
        $invited,
    );

    expect($membership->status)->toBe(MembershipStatus::ACTIVE)
        ->and($membership->tenant_id)->toBe($tenant->getTenantKey())
        ->and(AuditLog::query()->where('action', 'tenant.invitation.accepted')->exists())->toBeTrue();
});

it('rejects acceptance by a different email', function (): void {
    $owner = User::factory()->create();
    $wrongUser = User::factory()->create(['email' => 'other@example.com']);
    $tenant = createInvitationFlowTenant($owner);

    $created = app(InviteTenantUserAction::class)->execute(
        $tenant,
        $owner,
        'expected@example.com',
        TenantRole::USER,
    );

    expect(fn () => app(AcceptTenantInvitationAction::class)->execute(
        $created->plainToken,
        $wrongUser,
    ))->toThrow(ValidationException::class);
});

it('prevents duplicate pending invitation for the same tenant and email', function (): void {
    $owner = User::factory()->create();
    $tenant = createInvitationFlowTenant($owner);
    $action = app(InviteTenantUserAction::class);

    $action->execute($tenant, $owner, 'same@example.com', TenantRole::USER);

    expect(fn () => $action->execute(
        $tenant,
        $owner,
        'SAME@example.com',
        TenantRole::USER,
    ))->toThrow(ValidationException::class);
});
