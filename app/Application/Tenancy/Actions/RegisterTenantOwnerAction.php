<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Actions;

use App\Application\Tenancy\Data\CreateTenantData;
use App\Application\Tenancy\Data\RegisteredTenantOwnerData;
use App\Application\Tenancy\Data\RegisterTenantOwnerData;
use App\Models\User;
use App\Support\Tenancy\UniqueTenantSlugGenerator;
use Illuminate\Support\Facades\DB;

final readonly class RegisterTenantOwnerAction
{
    public function __construct(
        private CreateTenantAction $createTenant,
        private UniqueTenantSlugGenerator $slugGenerator,
    ) {}

    public function execute(RegisterTenantOwnerData $data): RegisteredTenantOwnerData
    {
        return DB::transaction(function () use ($data): RegisteredTenantOwnerData {
            $user = User::query()->create([
                'name' => trim($data->ownerName),
                'email' => mb_strtolower(trim($data->email)),
                'password' => $data->password,
            ]);

            $slug = $this->slugGenerator->generate($data->businessName);
            $domain = $slug.'.'.config('tenancy.tenant_base_domain');

            $tenant = $this->createTenant->execute(new CreateTenantData(
                name: trim($data->businessName),
                slug: $slug,
                domain: $domain,
                owner: $user,
            ));

            return new RegisteredTenantOwnerData($user, $tenant);
        });
    }
}
