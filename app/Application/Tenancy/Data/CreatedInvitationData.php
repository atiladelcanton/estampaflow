<?php

declare(strict_types=1);

namespace App\Application\Tenancy\Data;

use App\Domains\Tenancy\Models\TenantInvitation;

final readonly class CreatedInvitationData
{
    public function __construct(
        public TenantInvitation $invitation,
        public string $plainToken,
        public string $acceptUrl,
        public bool $emailDispatched,
        public ?string $deliveryError = null,
    ) {}
}
