<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Support\Correlation\CorrelationContext;
use App\Support\Correlation\CorrelationId;

final readonly class AuditLogger
{
    public function __construct(private CorrelationContext $correlationContext) {}

    public function record(AuditEntryData $entry): AuditLog
    {
        if (! $this->correlationContext->hasCurrent()) {
            $this->correlationContext->set(CorrelationId::generate());
        }

        return AuditLog::query()->create([
            'tenant_id' => $entry->tenantId,
            'actor_id' => $entry->actorId,
            'action' => $entry->action,
            'auditable_type' => $entry->auditableType,
            'auditable_id' => $entry->auditableId,
            'before' => $entry->before,
            'after' => $entry->after,
            'reason' => $entry->reason,
            'source' => $entry->source,
            'correlation_id' => (string) $this->correlationContext->current(),
            'ip_address' => $entry->ipAddress,
            'user_agent' => $entry->userAgent,
        ]);
    }
}
