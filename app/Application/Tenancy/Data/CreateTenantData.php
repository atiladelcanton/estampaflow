<?php

namespace App\Application\Tenancy\Data;

use App\Models\User;

final readonly class CreateTenantData
{
    public function __construct(
        public string $name,
        public string $slug,
        public string $domain,
        public User $owner,
        public string $timezone = 'America/Sao_Paulo',
    ) {}
}
