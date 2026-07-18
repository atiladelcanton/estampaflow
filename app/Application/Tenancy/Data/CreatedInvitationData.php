<?php

namespace App\Application\Tenancy\Data;

use App\Domains\Tenancy\Models\TenantInvitation;

final readonly class CreatedInvitationData
{
    public function __construct(
        public TenantInvitation $invitation,
        public string $plainToken,
        public string $acceptUrl,
    ) {}
}
