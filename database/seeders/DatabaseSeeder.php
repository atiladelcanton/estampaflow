<?php

namespace Database\Seeders;

use App\Domains\Tenancy\Enums\MembershipStatus;
use App\Domains\Tenancy\Enums\TenantRole;
use App\Domains\Tenancy\Enums\TenantStatus;
use App\Domains\Tenancy\Models\Tenant;
use App\Domains\Tenancy\Models\TenantMembership;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@delka.local'],
            [
                'name' => 'Administrador Delka',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_platform_admin' => true,
            ],
        );

        $operator = User::query()->updateOrCreate(
            ['email' => 'operacao@delka.local'],
            [
                'name' => 'Operação Delka',
                'password' => 'password',
                'email_verified_at' => now(),
                'is_platform_admin' => false,
            ],
        );

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'alpha'],
            [
                'id' => (string) Str::ulid(),
                'name' => 'Estamparia Alpha',
                'status' => TenantStatus::ACTIVE,
                'timezone' => 'America/Sao_Paulo',
                'trial_ends_at' => now()->addDays(30),
                'data' => [],
            ],
        );

        $tenant->domains()->updateOrCreate(
            ['domain' => 'alpha.'.config('tenancy.tenant_base_domain')],
            [],
        );

        TenantMembership::query()->updateOrCreate(
            ['tenant_id' => $tenant->getTenantKey(), 'user_id' => $admin->getKey()],
            [
                'role' => TenantRole::OWNER,
                'status' => MembershipStatus::ACTIVE,
                'joined_at' => now(),
            ],
        );

        TenantMembership::query()->updateOrCreate(
            ['tenant_id' => $tenant->getTenantKey(), 'user_id' => $operator->getKey()],
            [
                'role' => TenantRole::USER,
                'status' => MembershipStatus::ACTIVE,
                'invited_by' => $admin->getKey(),
                'joined_at' => now(),
            ],
        );
    }
}
