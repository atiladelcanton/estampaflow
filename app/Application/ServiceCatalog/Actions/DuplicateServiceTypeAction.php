<?php

declare(strict_types=1);

namespace App\Application\ServiceCatalog\Actions;

use App\Application\ServiceCatalog\Data\CreateServiceTypeData;
use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Str;

final readonly class DuplicateServiceTypeAction
{
    public function __construct(
        private CreateServiceTypeAction $createServiceType,
        private SaveServiceSchemaDraftAction $saveDraft,
    ) {}

    public function execute(User $actor, ServiceType $source): ServiceType
    {
        $source->loadMissing('activeSchemaVersion.parameters');
        $name = $source->name.' — Cópia';
        $code = Str::upper($source->code.'_COPY_'.Str::lower(Str::random(4)));

        $duplicate = $this->createServiceType->execute($actor, new CreateServiceTypeData(
            name: $name,
            code: $code,
            description: $source->description,
            pricingMode: $source->pricing_mode,
            pricingStrategy: $source->pricing_strategy,
            requiresArt: $source->requires_art,
            allowsMultiplePositions: $source->allows_multiple_positions,
            sortOrder: $source->sort_order + 1,
        ));

        $draft = $duplicate->schemaVersions()->where('version', 1)->firstOrFail();
        $parameters = $source->activeSchemaVersion?->parameters->map(fn (ServiceParameterDefinition $parameter): SaveServiceParameterData => new SaveServiceParameterData(
            key: $parameter->key,
            label: $parameter->label,
            fieldType: $parameter->field_type,
            unit: $parameter->unit,
            required: $parameter->required,
            affectsPricing: $parameter->affects_pricing,
            options: $parameter->options,
            validationRules: $parameter->validation_rules,
            defaultValue: $parameter->default_value,
            sortOrder: $parameter->sort_order,
            active: $parameter->active,
        ))->all() ?? [];

        $this->saveDraft->execute($actor, $draft, $parameters);

        return $duplicate->refresh();
    }
}
