<?php

namespace App\Support\Correlation;

use Illuminate\Support\Str;
use InvalidArgumentException;
use Stringable;

final readonly class CorrelationId implements Stringable
{
    public function __construct(public string $value)
    {
        if ($value === '' || mb_strlen($value) > 64) {
            throw new InvalidArgumentException('Correlation ID inválido.');
        }
    }

    public static function generate(): self
    {
        return new self((string) Str::ulid());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
