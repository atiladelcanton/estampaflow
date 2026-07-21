<?php

declare(strict_types=1);

namespace App\Domains\ServiceCatalog\Services;

use App\Application\ServiceCatalog\Actions\ActivateServiceSchemaVersionAction;
use App\Application\ServiceCatalog\Actions\CreateServiceTypeAction;
use App\Application\ServiceCatalog\Actions\SaveServiceSchemaDraftAction;
use App\Application\ServiceCatalog\Data\CreateServiceTypeData;
use App\Application\ServiceCatalog\Data\SaveServiceParameterData;
use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Enums\ServiceParameterFieldType;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use Illuminate\Support\Collection;

final readonly class DefaultServiceCatalogService
{
    public function __construct(
        private CreateServiceTypeAction $createServiceType,
        private SaveServiceSchemaDraftAction $saveSchemaDraft,
        private ActivateServiceSchemaVersionAction $activateSchema,
    ) {}

    /** @return Collection<int, ServiceType> */
    public function createDefaultsFor(User $actor): Collection
    {
        return collect($this->definitions())->map(function (array $definition) use ($actor): ServiceType {
            $serviceType = $this->createServiceType->execute($actor, new CreateServiceTypeData(
                name: $definition['name'],
                code: $definition['code'],
                description: $definition['description'],
                pricingMode: PricingMode::AUTOMATIC,
                pricingStrategy: $definition['strategy'],
                requiresArt: true,
                allowsMultiplePositions: true,
                isDefault: true,
                sortOrder: $definition['order'],
            ), returnExisting: true);

            if (! $serviceType->wasRecentlyCreated) {
                return $serviceType;
            }

            $draft = $serviceType->schemaVersions()->where('version', 1)->firstOrFail();
            $this->saveSchemaDraft->execute($actor, $draft, $definition['parameters']);
            $this->activateSchema->execute($actor, $draft);

            return $serviceType->refresh();
        });
    }

    /** @return list<array<string, mixed>> */
    private function definitions(): array
    {
        return [
            [
                'code' => 'DTF',
                'name' => 'DTF',
                'description' => 'Impressão e aplicação de transfer DTF.',
                'strategy' => PricingStrategy::AREA,
                'order' => 10,
                'parameters' => [
                    $this->parameter('width_cm', 'Largura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, 10),
                    $this->parameter('height_cm', 'Altura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, 20),
                    $this->select('print_size', 'Tamanho de referência', ['A6', 'A5', 'A4', 'A3', 'Personalizado'], false, true, 30),
                ],
            ],
            [
                'code' => 'SILK',
                'name' => 'Silk Screen',
                'description' => 'Impressão serigráfica por cores e matriz.',
                'strategy' => PricingStrategy::MATRIX,
                'order' => 20,
                'parameters' => [
                    $this->select('print_size', 'Tamanho da impressão', ['A6', 'A5', 'A4', 'A3', 'Personalizado'], true, true, 10),
                    $this->parameter('screen_colors', 'Quantidade de cores', ServiceParameterFieldType::INTEGER, 'cores', true, true, 20),
                ],
            ],
            [
                'code' => 'SUBLIMACAO',
                'name' => 'Sublimação',
                'description' => 'Sublimação localizada ou total.',
                'strategy' => PricingStrategy::AREA,
                'order' => 30,
                'parameters' => [
                    $this->select('modality', 'Modalidade', ['Localizada', 'Total'], true, true, 10),
                    $this->parameter('covered_area', 'Área aproximada', ServiceParameterFieldType::DECIMAL, 'cm²', false, true, 20),
                    $this->parameter('piece_type', 'Tipo da peça', ServiceParameterFieldType::TEXT, null, false, true, 30),
                ],
            ],
            [
                'code' => 'BORDADO',
                'name' => 'Bordado',
                'description' => 'Bordado calculado por tamanho e faixa de pontos.',
                'strategy' => PricingStrategy::STITCH_RANGE,
                'order' => 40,
                'parameters' => [
                    $this->parameter('width_cm', 'Largura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, 10),
                    $this->parameter('height_cm', 'Altura', ServiceParameterFieldType::DECIMAL, 'cm', true, true, 20),
                    $this->select('stitch_range', 'Faixa de pontos', ['Até 5.000', '5.001 a 10.000', '10.001 a 20.000', 'Acima de 20.000'], true, true, 30),
                    $this->parameter('thread_colors', 'Cores de linha', ServiceParameterFieldType::INTEGER, 'cores', false, false, 40),
                ],
            ],
        ];
    }

    private function parameter(
        string $key,
        string $label,
        ServiceParameterFieldType $type,
        ?string $unit,
        bool $required,
        bool $affectsPricing,
        int $sortOrder,
    ): SaveServiceParameterData {
        return new SaveServiceParameterData(
            key: $key,
            label: $label,
            fieldType: $type,
            unit: $unit,
            required: $required,
            affectsPricing: $affectsPricing,
            options: null,
            validationRules: null,
            defaultValue: null,
            sortOrder: $sortOrder,
        );
    }

    /** @param list<string> $options */
    private function select(
        string $key,
        string $label,
        array $options,
        bool $required,
        bool $affectsPricing,
        int $sortOrder,
    ): SaveServiceParameterData {
        return new SaveServiceParameterData(
            key: $key,
            label: $label,
            fieldType: ServiceParameterFieldType::SELECT,
            unit: null,
            required: $required,
            affectsPricing: $affectsPricing,
            options: $options,
            validationRules: null,
            defaultValue: null,
            sortOrder: $sortOrder,
        );
    }
}
