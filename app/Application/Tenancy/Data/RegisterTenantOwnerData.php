<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Data;

final readonly class RegisterTenantOwnerData
{
    public function __construct(
        public string $ownerName,
        public string $businessName,
        public string $email,
        public string $password,
    ) {}
}
