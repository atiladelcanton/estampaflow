<?php

namespace App\Support\Audit;

final readonly class AuditEntryData
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     */
    public function __construct(
        public string $action,
        public ?string $tenantId = null,
        public ?string $actorId = null,
        public ?string $auditableType = null,
        public ?string $auditableId = null,
        public ?array $before = null,
        public ?array $after = null,
        public ?string $reason = null,
        public string $source = 'APPLICATION',
        public ?string $ipAddress = null,
        public ?string $userAgent = null,
    ) {}
}
