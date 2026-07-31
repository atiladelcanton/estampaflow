<?php

declare(strict_types=1);

namespace App\Application\Pricing\Services;

use App\Application\Pricing\Data\SavePriceRuleData;
use App\Application\Pricing\Data\ServicePricingSetupData;
use App\Domains\Pricing\Enums\GuidedPricingTemplate;
use App\Domains\Pricing\Models\ServicePriceRule;
use App\Domains\Pricing\Models\ServicePriceTable;
use App\Domains\Pricing\ValueObjects\MoneyParser;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class AdditionalGuidedPricingDataService
{
    public function __construct(private MoneyParser $moneyParser) {}

    /** @return list<array{key: string, label: string, modality: string, piece_type: string}> */
    public function sublimationCategories(): array
    {
        return [
            ['key' => 'LOCAL_SMALL', 'label' => 'Localizada pequena', 'modality' => 'Localizada', 'piece_type' => 'Pequena'],
            ['key' => 'LOCAL_MEDIUM', 'label' => 'Localizada média', 'modality' => 'Localizada', 'piece_type' => 'Média'],
            ['key' => 'LOCAL_LARGE', 'label' => 'Localizada grande', 'modality' => 'Localizada', 'piece_type' => 'Grande'],
            ['key' => 'TOTAL', 'label' => 'Sublimação total', 'modality' => 'Total', 'piece_type' => 'Total'],
        ];
    }

    /** @return array<string, string> */
    public function sublimationParameters(string $categoryKey): array
    {
        foreach ($this->sublimationCategories() as $category) {
            if ($category['key'] === $categoryKey) {
                return [
                    'modality' => $category['modality'],
                    'piece_type' => $category['piece_type'],
                ];
            }
        }

        return [];
    }

    /** @return array<string, mixed> */
    public function initialSublimation(ServiceType $service, ?ServicePriceTable $table): array
    {
        $settings = $this->compatibleSettings($table, GuidedPricingTemplate::SUBLIMATION_MATRIX);
        $categories = $this->sublimationCategories();

        return [
            'categories' => $categories,
            'ranges' => $this->matrixRanges(
                $table,
                GuidedPricingTemplate::SUBLIMATION_MATRIX,
                array_column($categories, 'key'),
                fn (ServicePriceRule $rule): ?string => $this->sublimationCategoryFromRule($rule),
                [[1, 9], [10, 29], [30, null]],
            ),
            'valid_from' => $table?->valid_from?->format('Y-m-d') ?? '',
            'valid_until' => $table?->valid_until?->format('Y-m-d') ?? '',
            'sample_quantity' => 10,
            'sample_category' => (string) ($settings['sample_category'] ?? 'LOCAL_MEDIUM'),
        ];
    }

    /** @return array<string, mixed> */
    public function initialEmbroidery(ServiceType $service, ?ServicePriceTable $table): array
    {
        $settings = $this->compatibleSettings($table, GuidedPricingTemplate::EMBROIDERY_MATRIX);
        $stitchRanges = $this->parameterOptions($service, 'stitch_range');

        if ($stitchRanges === []) {
            $stitchRanges = ['Até 5.000', '5.001 a 10.000', '10.001 a 20.000', 'Acima de 20.000'];
        }

        $stitchColumns = array_map(
            static fn (string $label, int $index): array => ['key' => 'RANGE_'.$index, 'label' => $label],
            $stitchRanges,
            array_keys($stitchRanges),
        );
        $labelToKey = [];

        foreach ($stitchColumns as $column) {
            $labelToKey[$column['label']] = $column['key'];
        }

        return [
            'stitch_columns' => $stitchColumns,
            'digitizing_price' => $this->moneyFromSetting($settings, 'digitizing_amount_minor'),
            'ranges' => $this->matrixRanges(
                $table,
                GuidedPricingTemplate::EMBROIDERY_MATRIX,
                array_column($stitchColumns, 'key'),
                function (ServicePriceRule $rule) use ($labelToKey): ?string {
                    $label = $this->conditionValue($rule, 'stitch_range');

                    return $label !== null ? ($labelToKey[$label] ?? null) : null;
                },
                [[1, 9], [10, 29], [30, null]],
            ),
            'valid_from' => $table?->valid_from?->format('Y-m-d') ?? '',
            'valid_until' => $table?->valid_until?->format('Y-m-d') ?? '',
            'sample_quantity' => 10,
            'sample_stitch_range' => $stitchRanges[0],
        ];
    }

    /** @param array<string, mixed> $validated */
    public function sublimationSetup(array $validated): ServicePricingSetupData
    {
        $categories = $this->sublimationCategories();
        $categoryKeys = array_column($categories, 'key');
        $ranges = $this->validatedRanges($validated['ranges'] ?? null, $categoryKeys);
        $categoryMap = [];

        foreach ($categories as $category) {
            $categoryMap[$category['key']] = $category;
        }

        $rules = [];
        $sort = 10;

        foreach ($ranges as $rangeIndex => $range) {
            $min = (int) $range['min_quantity'];
            $max = $this->nullablePositiveInt($range['max_quantity'] ?? null);
            $prices = is_array($range['prices'] ?? null) ? $range['prices'] : [];

            foreach ($categoryKeys as $categoryKey) {
                $unitAmount = $this->priceFromMatrix($prices, $categoryKey, "ranges.{$rangeIndex}.prices.{$categoryKey}");
                $category = $categoryMap[$categoryKey];

                $rules[] = new SavePriceRuleData(
                    name: $this->rangeRuleName($min, $max, $category['label']),
                    minQuantity: $min,
                    maxQuantity: $max,
                    conditions: [
                        ['parameter' => 'modality', 'operator' => 'eq', 'value' => $category['modality']],
                        ['parameter' => 'piece_type', 'operator' => 'eq', 'value' => $category['piece_type']],
                    ],
                    unitAmountMinor: $unitAmount,
                    rateValue: null,
                    rateUnit: null,
                    setupAmountMinor: 0,
                    minimumAmountMinor: 0,
                    priority: 100,
                    sortOrder: $sort,
                );
                $sort += 10;
            }
        }

        return new ServicePricingSetupData(
            strategy: PricingStrategy::MATRIX,
            rules: $rules,
            settings: [
                'guided_template' => GuidedPricingTemplate::SUBLIMATION_MATRIX->value,
                'configured_categories' => $categoryKeys,
                'sample_category' => (string) ($validated['sample_category'] ?? 'LOCAL_MEDIUM'),
            ],
            validFrom: $this->nullableString($validated['valid_from'] ?? null),
            validUntil: $this->nullableString($validated['valid_until'] ?? null),
        );
    }

    /** @param array<string, mixed> $validated */
    public function embroiderySetup(array $validated): ServicePricingSetupData
    {
        $rawColumns = $validated['stitch_columns'] ?? [];
        $columns = is_array($rawColumns) ? array_values(array_filter($rawColumns, 'is_array')) : [];
        $stitchColumns = [];

        foreach ($columns as $column) {
            $key = trim((string) ($column['key'] ?? ''));
            $label = trim((string) ($column['label'] ?? ''));

            if ($key !== '' && $label !== '') {
                $stitchColumns[] = ['key' => $key, 'label' => $label];
            }
        }

        if ($stitchColumns === []) {
            throw ValidationException::withMessages(['stitch_columns' => 'Adicione pelo menos uma faixa de pontos.']);
        }

        try {
            $digitizing = $this->moneyParser->majorToMinor((string) ($validated['digitizing_price'] ?? '0'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages(['digitizing_price' => 'Revise o valor da criação da matriz.']);
        }

        $ranges = $this->validatedRanges($validated['ranges'] ?? null, array_column($stitchColumns, 'key'));
        $rules = [];
        $sort = 10;

        foreach ($ranges as $rangeIndex => $range) {
            $min = (int) $range['min_quantity'];
            $max = $this->nullablePositiveInt($range['max_quantity'] ?? null);
            $prices = is_array($range['prices'] ?? null) ? $range['prices'] : [];

            foreach ($stitchColumns as $column) {
                $columnKey = $column['key'];
                $stitchRange = $column['label'];
                $unitAmount = $this->priceFromMatrix($prices, $columnKey, "ranges.{$rangeIndex}.prices");

                $rules[] = new SavePriceRuleData(
                    name: $this->rangeRuleName($min, $max, $stitchRange),
                    minQuantity: $min,
                    maxQuantity: $max,
                    conditions: [[
                        'parameter' => 'stitch_range',
                        'operator' => 'eq',
                        'value' => $stitchRange,
                    ]],
                    unitAmountMinor: $unitAmount,
                    rateValue: null,
                    rateUnit: null,
                    setupAmountMinor: max(0, $digitizing),
                    minimumAmountMinor: 0,
                    priority: 100,
                    sortOrder: $sort,
                );
                $sort += 10;
            }
        }

        return new ServicePricingSetupData(
            strategy: PricingStrategy::MATRIX,
            rules: $rules,
            settings: [
                'guided_template' => GuidedPricingTemplate::EMBROIDERY_MATRIX->value,
                'configured_stitch_ranges' => array_column($stitchColumns, 'label'),
                'digitizing_amount_minor' => max(0, $digitizing),
            ],
            validFrom: $this->nullableString($validated['valid_from'] ?? null),
            validUntil: $this->nullableString($validated['valid_until'] ?? null),
        );
    }

    /** @return list<string> */
    private function parameterOptions(ServiceType $service, string $key): array
    {
        if ($service->active_schema_version_id === null) {
            return [];
        }

        $parameter = ServiceParameterDefinition::query()
            ->where('schema_version_id', $service->active_schema_version_id)
            ->where('key', $key)
            ->where('active', true)
            ->first();

        return $parameter instanceof ServiceParameterDefinition && is_array($parameter->options)
            ? array_values(array_map('strval', $parameter->options))
            : [];
    }

    /**
     * @param  list<string>  $columns
     * @param  callable(ServicePriceRule): ?string  $columnResolver
     * @param  list<array{0: int, 1: ?int}>  $defaults
     * @return list<array<string, mixed>>
     */
    private function matrixRanges(
        ?ServicePriceTable $table,
        GuidedPricingTemplate $template,
        array $columns,
        callable $columnResolver,
        array $defaults,
    ): array {
        if (! $table instanceof ServicePriceTable || ($table->settings['guided_template'] ?? null) !== $template->value) {
            return $this->emptyRanges($columns, $defaults);
        }

        /** @var array<string, array<string, mixed>> $grouped */
        $grouped = [];

        foreach ($table->rules as $rule) {
            $column = $columnResolver($rule);

            if ($column === null || ! in_array($column, $columns, true)) {
                continue;
            }

            $key = ($rule->min_quantity ?? 1).'|'.($rule->max_quantity ?? '');
            $grouped[$key] ??= [
                'min_quantity' => $rule->min_quantity ?? 1,
                'max_quantity' => $rule->max_quantity,
                'prices' => [],
            ];
            $grouped[$key]['prices'][$column] = $rule->unit_amount_minor !== null
                ? $this->moneyParser->minorToMajor($rule->unit_amount_minor)
                : '';
        }

        if ($grouped === []) {
            return $this->emptyRanges($columns, $defaults);
        }

        $ranges = array_values($grouped);
        usort($ranges, static fn (array $left, array $right): int => ((int) $left['min_quantity']) <=> ((int) $right['min_quantity']));

        foreach ($ranges as &$range) {
            foreach ($columns as $column) {
                $range['prices'][$column] ??= '';
            }
        }
        unset($range);

        return $ranges;
    }

    /** @param list<string> $columns @param list<array{0: int, 1: ?int}> $definitions @return list<array<string, mixed>> */
    private function emptyRanges(array $columns, array $definitions): array
    {
        return array_map(static function (array $definition) use ($columns): array {
            $prices = [];

            foreach ($columns as $column) {
                $prices[$column] = '';
            }

            return [
                'min_quantity' => $definition[0],
                'max_quantity' => $definition[1],
                'prices' => $prices,
            ];
        }, $definitions);
    }

    /** @param mixed $rawRanges @param list<string> $columns @return list<array<string, mixed>> */
    private function validatedRanges(mixed $rawRanges, array $columns): array
    {
        $ranges = is_array($rawRanges) ? array_values(array_filter($rawRanges, 'is_array')) : [];

        if ($ranges === []) {
            throw ValidationException::withMessages(['ranges' => 'Adicione pelo menos uma faixa de quantidade.']);
        }

        usort($ranges, static fn (array $left, array $right): int => ((int) ($left['min_quantity'] ?? 0)) <=> ((int) ($right['min_quantity'] ?? 0)));
        $previousMax = null;
        $openEndedSeen = false;

        foreach ($ranges as $index => $range) {
            $min = (int) ($range['min_quantity'] ?? 0);
            $max = $this->nullablePositiveInt($range['max_quantity'] ?? null);
            $prices = is_array($range['prices'] ?? null) ? $range['prices'] : [];

            if ($min < 1) {
                throw ValidationException::withMessages(["ranges.{$index}.min_quantity" => 'A quantidade inicial deve ser maior que zero.']);
            }

            if ($max !== null && $max < $min) {
                throw ValidationException::withMessages(["ranges.{$index}.max_quantity" => 'A quantidade final deve ser maior ou igual à inicial.']);
            }

            if ($openEndedSeen) {
                throw ValidationException::withMessages(['ranges' => 'A faixa sem quantidade final precisa ser a última da tabela.']);
            }

            if ($previousMax !== null && $min <= $previousMax) {
                throw ValidationException::withMessages(['ranges' => 'As faixas de quantidade não podem se sobrepor.']);
            }

            foreach ($columns as $column) {
                $this->priceFromMatrix($prices, $column, "ranges.{$index}.prices");
            }

            if ($max === null) {
                $openEndedSeen = true;
            } else {
                $previousMax = $max;
            }
        }

        return $ranges;
    }

    /** @param array<mixed> $prices */
    private function priceFromMatrix(array $prices, string $column, string $field): int
    {
        $raw = trim((string) ($prices[$column] ?? ''));

        try {
            $amount = $this->moneyParser->majorToMinor($raw);
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([$field => 'Use valores com no máximo duas casas decimais, como 12,50.']);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([$field => 'Preencha todos os preços visíveis ou remova a faixa que não utiliza.']);
        }

        return $amount;
    }

    private function sublimationCategoryFromRule(ServicePriceRule $rule): ?string
    {
        $modality = $this->conditionValue($rule, 'modality');
        $pieceType = $this->conditionValue($rule, 'piece_type');

        foreach ($this->sublimationCategories() as $category) {
            if ($category['modality'] === $modality && $category['piece_type'] === $pieceType) {
                return $category['key'];
            }
        }

        return null;
    }

    private function conditionValue(ServicePriceRule $rule, string $parameter): ?string
    {
        foreach ($rule->conditions ?? [] as $condition) {
            if (($condition['parameter'] ?? null) === $parameter && ($condition['operator'] ?? null) === 'eq') {
                $value = trim((string) ($condition['value'] ?? ''));

                return $value !== '' ? $value : null;
            }
        }

        return null;
    }

    /** @return array<string, mixed> */
    private function compatibleSettings(?ServicePriceTable $table, GuidedPricingTemplate $template): array
    {
        $settings = $table?->settings;

        if (! is_array($settings) || ($settings['guided_template'] ?? null) !== $template->value) {
            return [];
        }

        return $settings;
    }

    /** @param array<string, mixed> $settings */
    private function moneyFromSetting(array $settings, string $key): string
    {
        $minor = max(0, (int) ($settings[$key] ?? 0));

        return $minor > 0 ? $this->moneyParser->minorToMajor($minor) : '';
    }

    private function rangeRuleName(int $min, ?int $max, string $columnLabel): string
    {
        $range = $max === null ? "{$min}+ peças" : "{$min} a {$max} peças";

        return "{$range} — {$columnLabel}";
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $integer = (int) $value;

        return $integer > 0 ? $integer : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
