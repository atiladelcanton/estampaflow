<?php

declare(strict_types=1);

namespace App\Domains\Pricing\ValueObjects;

use InvalidArgumentException;

final readonly class Money
{
    public function __construct(
        public int $amountMinor,
        public string $currency = 'BRL',
    ) {
        if ($amountMinor < 0) {
            throw new InvalidArgumentException('Money não aceita valor negativo.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException('Moeda inválida.');
        }
    }

    public static function zero(string $currency = 'BRL'): self
    {
        return new self(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amountMinor + $other->amountMinor, $this->currency);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('A quantidade não pode ser negativa.');
        }

        return new self($this->amountMinor * $quantity, $this->currency);
    }

    public function max(self $other): self
    {
        $this->assertSameCurrency($other);

        return $this->amountMinor >= $other->amountMinor ? $this : $other;
    }

    public function format(): string
    {
        $major = intdiv($this->amountMinor, 100);
        $cents = $this->amountMinor % 100;

        return 'R$ '.number_format($major, 0, ',', '.').','.str_pad((string) $cents, 2, '0', STR_PAD_LEFT);
    }

    /** @return array{amount_minor: int, currency: string, formatted: string} */
    public function toArray(): array
    {
        return [
            'amount_minor' => $this->amountMinor,
            'currency' => $this->currency,
            'formatted' => $this->format(),
        ];
    }

    private function assertSameCurrency(self $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Não é possível operar moedas diferentes.');
        }
    }
}
