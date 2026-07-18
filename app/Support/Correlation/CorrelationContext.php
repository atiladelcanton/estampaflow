<?php

namespace App\Support\Correlation;

use LogicException;

final class CorrelationContext
{
    private ?CorrelationId $current = null;

    public function set(CorrelationId $correlationId): void
    {
        $this->current = $correlationId;
    }

    public function current(): CorrelationId
    {
        return $this->current ?? throw new LogicException('CorrelationContext não inicializado.');
    }

    public function hasCurrent(): bool
    {
        return $this->current !== null;
    }
}
