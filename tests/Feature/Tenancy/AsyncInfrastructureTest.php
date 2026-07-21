<?php

declare(strict_types=1);

use App\Application\Tenancy\Jobs\ProvisionTenantDomainJob;
use App\Application\Tenancy\Jobs\SendTenantInvitationEmailJob;
use App\Domains\Tenancy\Enums\DomainProvisioningStatus;
use App\Domains\Tenancy\Enums\InvitationStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Domain;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantInvitation;
use App\Models\AuditLog;
use App\Models\User;
use App\Notifications\TenantInvitationNotification;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('provisions a pending tenant domain and records the local simulation', function (): void {
    Storage::fake('local');

    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Fila Domain',
        'slug' => 'fila-domain',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    /** @var Domain $domain */
    $domain = $tenant->domains()->create([
        'domain' => 'fila-domain.estamparia.test',
        'provisioning_status' => DomainProvisioningStatus::PENDING,
    ]);

    $job = new ProvisionTenantDomainJob((int) $domain->getKey());
    $job->handle(app(AuditLogger::class));

    $domain->refresh();

    expect($domain->provisioning_status)->toBe(DomainProvisioningStatus::PROVISIONED)
        ->and($domain->provisioned_at)->not->toBeNull()
        ->and(AuditLog::query()->where('action', 'tenant.domain.provisioned')->exists())->toBeTrue();

    Storage::disk('local')->assertExists('domain-provisioning/fila-domain.estamparia.test.hosts');
});

it('encrypts invitation email jobs and sends them from the worker', function (): void {
    Notification::fake();

    $owner = User::factory()->create();
    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Mail Queue',
        'slug' => 'mail-queue',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $invitation = TenantInvitation::query()->create([
        'tenant_id' => $tenant->getTenantKey(),
        'email' => 'fila@example.com',
        'email_normalized' => 'fila@example.com',
        'pending_email_key' => 'fila@example.com',
        'role' => TenantRole::USER,
        'status' => InvitationStatus::PENDING,
        'token_hash' => hash('sha256', 'plain-token'),
        'invited_by' => $owner->getKey(),
        'expires_at' => now()->addDays(7),
    ]);

    $job = new SendTenantInvitationEmailJob((string) $invitation->getKey(), 'plain-token');

    expect($job)->toBeInstanceOf(ShouldBeEncrypted::class);

    $job->handle(app(TenantUrlGenerator::class), app(AuditLogger::class));

    Notification::assertSentOnDemand(TenantInvitationNotification::class);
    expect(AuditLog::query()->where('action', 'tenant.invitation.email_sent')->exists())->toBeTrue();
});
