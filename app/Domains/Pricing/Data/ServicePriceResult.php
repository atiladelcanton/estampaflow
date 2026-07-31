<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Data;

use App\Domains\Pricing\Enums\PricingResultStatus;
use App\Domains\Pricing\ValueObjects\Money;

final readonly class ServicePriceResult
{
    /**
     * @param  list<string>  $errors
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public PricingResultStatus $status,
        public ?Money $total,
        public ?string $priceTableId,
        public ?string $priceRuleId,
        public string $explanation,
        public array $errors = [],
        public array $details = [],
    ) {}

    /** @param array<string, mixed> $details */
    public static function matched(
        Money $total,
        string $tableId,
        string $ruleId,
        string $explanation,
        array $details = [],
    ): self {
        return new self(
            PricingResultStatus::MATCHED,
            $total,
            $tableId,
            $ruleId,
            $explanation,
            [],
            $details,
        );
    }

    /** @param list<string> $errors */
    public static function failure(PricingResultStatus $status, string $explanation, array $errors = []): self
    {
        return new self($status, null, null, null, $explanation, $errors);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'total' => $this->total?->toArray(),
            'price_table_id' => $this->priceTableId,
            'price_rule_id' => $this->priceRuleId,
            'explanation' => $this->explanation,
            'errors' => $this->errors,
            'details' => $this->details,
        ];
    }
}
