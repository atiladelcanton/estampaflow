<?php

declare(strict_types=1);

use App\Application\Tenancy\Jobs\SendTenantWelcomeEmailJob;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Mail\TenantWelcomeMail;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Audit\AuditLogger;
use App\Support\Tenancy\TenantUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

it('queues a welcome email after public tenant registration', function (): void {
    Queue::fake();

    $this->post('http://app.estamparia.test/register', [
        'business_name' => 'Estamparia Boas Vindas',
        'name' => 'Maria Boas Vindas',
        'email' => 'maria.boasvindas@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect('http://estamparia-boas-vindas.estamparia.test/dashboard');

    $user = User::query()->where('email', 'maria.boasvindas@example.com')->firstOrFail();
    $tenant = Tenant::query()->where('slug', 'estamparia-boas-vindas')->firstOrFail();

    Queue::assertPushed(SendTenantWelcomeEmailJob::class, fn (SendTenantWelcomeEmailJob $job): bool => $job->userId === (string) $user->getKey()
        && $job->tenantId === (string) $tenant->getTenantKey());

    expect(AuditLog::query()
        ->where('action', 'tenant.welcome_email.queued')
        ->where('tenant_id', $tenant->getTenantKey())
        ->exists())->toBeTrue();
});

it('sends a branded welcome email without exposing the password', function (): void {
    Mail::fake();

    $user = User::factory()->create([
        'name' => 'João da Estampa',
        'email' => 'joao@example.com',
        'password' => 'never-send-this-password',
    ]);

    $tenant = Tenant::query()->create([
        'id' => (string) Str::ulid(),
        'name' => 'Estamparia João',
        'slug' => 'estamparia-joao',
        'status' => TenantStatus::ACTIVE,
        'timezone' => 'America/Sao_Paulo',
        'trial_ends_at' => now()->addDays(7),
        'data' => [],
    ]);

    $tenant->domains()->create([
        'domain' => 'estamparia-joao.estamparia.test',
    ]);

    $job = new SendTenantWelcomeEmailJob(
        userId: (string) $user->getKey(),
        tenantId: (string) $tenant->getTenantKey(),
    );

    $job->handle(
        app(TenantUrlGenerator::class),
        app(AuditLogger::class),
    );

    Mail::assertSent(TenantWelcomeMail::class, function (TenantWelcomeMail $mail) use ($user): bool {
        $html = $mail->render();

        expect($mail->hasTo($user->email))->toBeTrue()
            ->and($html)->toContain('EstampaFlow')
            ->and($html)->toContain('Estamparia João')
            ->and($html)->toContain('joao@example.com')
            ->and($html)->not->toContain('never-send-this-password');

        return true;
    });

    expect(AuditLog::query()
        ->where('action', 'tenant.welcome_email.sent')
        ->where('tenant_id', $tenant->getTenantKey())
        ->exists())->toBeTrue();
});
