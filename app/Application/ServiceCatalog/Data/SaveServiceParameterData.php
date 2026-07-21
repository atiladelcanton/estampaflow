<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Data;

use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;

final readonly class SaveServiceParameterData
{
    /**
     * @param  list<string>|null  $options
     * @param  array<string, mixed>|null  $validationRules
     */
    public function __construct(
        public string $key,
        public string $label,
        public ServiceParameterFieldType $fieldType,
        public ?string $unit,
        public bool $required,
        public bool $affectsPricing,
        public ?array $options,
        public ?array $validationRules,
        public mixed $defaultValue,
        public int $sortOrder,
        public bool $active = true,
    ) {}
}
