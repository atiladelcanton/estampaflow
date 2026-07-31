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
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class GuidedPricingDataService
{
    public function __construct(private MoneyParser $moneyParser) {}

    /** @return array<string, mixed> */
    public function initialDtf(ServiceType $service, ?ServicePriceTable $table): array
    {
        $settings = $this->compatibleSettings($table, GuidedPricingTemplate::DTF_METER);

        return [
            'procurement_mode' => 'PURCHASED',
            'meter_cost' => $this->moneyFromSetting($settings, 'meter_cost_minor'),
            'usable_width_cm' => (string) ($settings['usable_width_cm'] ?? '58'),
            'application_price' => $this->moneyFromSetting($settings, 'application_amount_minor'),
            'material_markup_percent' => $this->percentFromBasisPoints($settings['material_markup_basis_points'] ?? 0),
            'spacing_cm' => (string) ($settings['spacing_cm'] ?? '0,50'),
            'waste_percent' => $this->percentFromBasisPoints($settings['waste_basis_points'] ?? 0),
            'allow_rotation' => (bool) ($settings['allow_rotation'] ?? true),
            'valid_from' => $table?->valid_from?->format('Y-m-d') ?? '',
            'valid_until' => $table?->valid_until?->format('Y-m-d') ?? '',
            'sample_quantity' => 10,
            'sample_width_cm' => 20,
            'sample_height_cm' => 30,
        ];
    }

    /** @return array<string, mixed> */
    public function initialSilk(ServiceType $service, ?ServicePriceTable $table): array
    {
        $settings = $this->compatibleSettings($table, GuidedPricingTemplate::SILK_MATRIX);
        $colors = $this->silkColors($table, $settings);
        $ranges = $this->silkRanges($table, $colors);
        $options = $this->parameterOptions($service);

        return [
            'setup_charge_mode' => (string) ($settings['setup_charge_mode'] ?? 'INCLUDED'),
            'setup_per_color' => $this->moneyFromSetting($settings, 'setup_per_color_minor'),
            'white_base_mode' => (string) ($settings['white_base_mode'] ?? 'ADD_COLOR'),
            'white_base_addon' => $this->moneyFromSetting($settings, 'white_base_addon_minor'),
            'colors' => $colors,
            'ranges' => $ranges,
            'addons_enabled' => $this->hasConfiguredAddons($settings),
            'ink_addons' => $this->addonRows($options['ink_system'] ?? [], $settings, 'ink_system'),
            'effect_addons' => $this->addonRows($options['print_effect'] ?? [], $settings, 'print_effect'),
            'valid_from' => $table?->valid_from?->format('Y-m-d') ?? '',
            'valid_until' => $table?->valid_until?->format('Y-m-d') ?? '',
            'sample_quantity' => 20,
            'sample_colors' => 2,
            'sample_white_base' => 'Sim',
            'sample_ink_system' => $this->defaultSampleOption($options['ink_system'] ?? [], 'Plastisol'),
            'sample_print_effect' => $this->defaultSampleOption($options['print_effect'] ?? [], 'Puff/relevo'),
        ];
    }

    /** @param array<string, mixed> $validated */
    public function dtfSetup(array $validated): ServicePricingSetupData
    {
        try {
            $meterCost = $this->moneyParser->majorToMinor((string) $validated['meter_cost']);
            $application = $this->moneyParser->majorToMinor((string) ($validated['application_price'] ?? '0'));
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'meter_cost' => 'Revise o valor do metro e o valor de aplicação.',
            ]);
        }

        if ($meterCost <= 0) {
            throw ValidationException::withMessages([
                'meter_cost' => 'Informe quanto você paga por um metro de DTF.',
            ]);
        }

        $usableWidth = $this->normalizeDecimal((string) $validated['usable_width_cm']);
        $spacing = $this->normalizeDecimal((string) ($validated['spacing_cm'] ?? '0.50'));
        $markupBasisPoints = $this->percentToBasisPoints((string) ($validated['material_markup_percent'] ?? '0'));
        $wasteBasisPoints = $this->percentToBasisPoints((string) ($validated['waste_percent'] ?? '0'));

        if (bccomp($usableWidth, '0', 4) <= 0) {
            throw ValidationException::withMessages([
                'usable_width_cm' => 'A largura útil precisa ser maior que zero.',
            ]);
        }

        if (bccomp($spacing, '0', 4) < 0) {
            throw ValidationException::withMessages([
                'spacing_cm' => 'O espaço entre as artes não pode ser negativo.',
            ]);
        }

        if ($markupBasisPoints > 50000) {
            throw ValidationException::withMessages([
                'material_markup_percent' => 'Use um acréscimo de até 500%.',
            ]);
        }

        if ($wasteBasisPoints > 5000) {
            throw ValidationException::withMessages([
                'waste_percent' => 'Use uma perda de segurança de até 50%.',
            ]);
        }

        return new ServicePricingSetupData(
            strategy: PricingStrategy::ROLL_LENGTH,
            rules: [new SavePriceRuleData(
                name: 'Cálculo por metro inteiro',
                minQuantity: 1,
                maxQuantity: null,
                conditions: [],
                unitAmountMinor: null,
                rateValue: null,
                rateUnit: null,
                setupAmountMinor: 0,
                minimumAmountMinor: 0,
                priority: 100,
                sortOrder: 10,
            )],
            settings: [
                'guided_template' => GuidedPricingTemplate::DTF_METER->value,
                'procurement_mode' => 'PURCHASED',
                'width_parameter' => 'width_cm',
                'height_parameter' => 'height_cm',
                'meter_cost_minor' => $meterCost,
                'usable_width_cm' => $usableWidth,
                'application_amount_minor' => $application,
                'material_markup_basis_points' => $markupBasisPoints,
                'spacing_cm' => $spacing,
                'waste_basis_points' => $wasteBasisPoints,
                'purchase_step_meters' => 1,
                'minimum_purchase_meters' => 1,
                'allow_rotation' => (bool) ($validated['allow_rotation'] ?? true),
            ],
            validFrom: $this->nullableString($validated['valid_from'] ?? null),
            validUntil: $this->nullableString($validated['valid_until'] ?? null),
        );
    }

    /** @param array<string, mixed> $validated */
    public function silkSetup(array $validated): ServicePricingSetupData
    {
        $rawColors = $validated['colors'] ?? [];

        if (! is_array($rawColors)) {
            $rawColors = [];
        }

        /** @var list<int> $colors */
        $colors = collect($rawColors)
            ->map(static fn (mixed $color): int => (int) $color)
            ->filter(static fn (int $color): bool => $color > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();

        if ($colors === []) {
            throw ValidationException::withMessages(['colors' => 'Adicione pelo menos uma quantidade de cores.']);
        }

        $rawRanges = $validated['ranges'] ?? [];

        if (! is_array($rawRanges)) {
            $rawRanges = [];
        }

        /** @var list<array<string, mixed>> $ranges */
        $ranges = array_values(array_filter($rawRanges, 'is_array'));
        $this->validateRanges($ranges, $colors);

        $rules = [];
        $sort = 10;

        foreach ($ranges as $rangeIndex => $range) {
            $min = (int) ($range['min_quantity'] ?? 0);
            $max = $this->nullablePositiveInt($range['max_quantity'] ?? null);
            $prices = is_array($range['prices'] ?? null) ? $range['prices'] : [];

            foreach ($colors as $color) {
                $raw = trim((string) ($prices[(string) $color] ?? $prices[$color] ?? ''));

                try {
                    $unitAmount = $this->moneyParser->majorToMinor($raw);
                } catch (InvalidArgumentException) {
                    throw ValidationException::withMessages([
                        "ranges.{$rangeIndex}.prices.{$color}" => "Revise o preço para {$color} cor(es).",
                    ]);
                }

                if ($unitAmount <= 0) {
                    throw ValidationException::withMessages([
                        "ranges.{$rangeIndex}.prices.{$color}" => "Informe o preço por peça para {$color} cor(es).",
                    ]);
                }

                $rules[] = new SavePriceRuleData(
                    name: $this->silkRuleName($min, $max, $color),
                    minQuantity: $min,
                    maxQuantity: $max,
                    conditions: [[
                        'parameter' => 'screen_colors',
                        'operator' => 'eq',
                        'value' => (string) $color,
                    ]],
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

        $setupMode = (string) ($validated['setup_charge_mode'] ?? 'INCLUDED');
        $whiteBaseMode = (string) ($validated['white_base_mode'] ?? 'ADD_COLOR');

        try {
            $setupPerColor = $setupMode === 'SEPARATE'
                ? $this->moneyParser->majorToMinor((string) ($validated['setup_per_color'] ?? '0'))
                : 0;
            $whiteBaseAddon = $whiteBaseMode === 'ADD_PER_ITEM'
                ? $this->moneyParser->majorToMinor((string) ($validated['white_base_addon'] ?? '0'))
                : 0;
        } catch (InvalidArgumentException) {
            throw ValidationException::withMessages([
                'setup_per_color' => 'Revise os valores de tela e base branca.',
            ]);
        }

        $perItemAddons = [];

        if ((bool) ($validated['addons_enabled'] ?? false)) {
            $perItemAddons['ink_system'] = $this->addonMap($validated['ink_addons'] ?? [], 'ink_addons');
            $perItemAddons['print_effect'] = $this->addonMap($validated['effect_addons'] ?? [], 'effect_addons');
        }

        $perItemAddons = array_filter($perItemAddons, static fn (array $values): bool => $values !== []);

        return new ServicePricingSetupData(
            strategy: PricingStrategy::MATRIX,
            rules: $rules,
            settings: [
                'guided_template' => GuidedPricingTemplate::SILK_MATRIX->value,
                'matrix_parameter' => 'screen_colors',
                'setup_charge_mode' => $setupMode,
                'setup_per_color_minor' => $setupPerColor,
                'white_base_parameter' => 'white_base',
                'white_base_mode' => $whiteBaseMode,
                'white_base_addon_minor' => $whiteBaseAddon,
                'per_item_addons' => $perItemAddons,
                'configured_colors' => $colors,
            ],
            validFrom: $this->nullableString($validated['valid_from'] ?? null),
            validUntil: $this->nullableString($validated['valid_until'] ?? null),
        );
    }

    /** @return array<string, list<string>> */
    public function parameterOptions(ServiceType $service): array
    {
        if ($service->active_schema_version_id === null) {
            return [];
        }

        /** @var Collection<int, ServiceParameterDefinition> $parameters */
        $parameters = ServiceParameterDefinition::query()
            ->where('schema_version_id', $service->active_schema_version_id)
            ->whereIn('key', ['ink_system', 'print_effect', 'white_base'])
            ->where('active', true)
            ->get();

        $result = [];

        foreach ($parameters as $parameter) {
            if (! is_array($parameter->options)) {
                $result[$parameter->key] = [];

                continue;
            }

            $result[$parameter->key] = array_map('strval', $parameter->options);
        }

        return $result;
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

    /**
     * @param  array<string, mixed>  $settings
     * @return list<int>
     */
    private function silkColors(?ServicePriceTable $table, array $settings): array
    {
        $configured = $settings['configured_colors'] ?? null;

        if (is_array($configured)) {
            $colors = collect($configured)
                ->map(static fn (mixed $color): int => (int) $color)
                ->filter(static fn (int $color): bool => $color > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($colors !== []) {
                return $colors;
            }
        }

        if ($table instanceof ServicePriceTable && ($table->settings['guided_template'] ?? null) === GuidedPricingTemplate::SILK_MATRIX->value) {
            $colors = $table->rules
                ->flatMap(static fn (ServicePriceRule $rule): array => $rule->conditions ?? [])
                ->filter(static fn (array $condition): bool => $condition['parameter'] === 'screen_colors' && $condition['operator'] === 'eq')
                ->map(static fn (array $condition): int => (int) ($condition['value'] ?? 0))
                ->filter(static fn (int $color): bool => $color > 0)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($colors !== []) {
                return $colors;
            }
        }

        return [1, 2, 3, 4];
    }

    /**
     * @param  list<int>  $colors
     * @return list<array<string, mixed>>
     */
    private function silkRanges(?ServicePriceTable $table, array $colors): array
    {
        if (! $table instanceof ServicePriceTable || ($table->settings['guided_template'] ?? null) !== GuidedPricingTemplate::SILK_MATRIX->value) {
            return $this->defaultSilkRanges($colors);
        }

        /** @var array<string, array<string, mixed>> $grouped */
        $grouped = [];

        foreach ($table->rules as $rule) {
            $color = $this->conditionColor($rule);

            if ($color === null || ! in_array($color, $colors, true)) {
                continue;
            }

            $key = ($rule->min_quantity ?? 1).'|'.($rule->max_quantity ?? '');
            $grouped[$key] ??= [
                'min_quantity' => $rule->min_quantity ?? 1,
                'max_quantity' => $rule->max_quantity,
                'prices' => [],
            ];
            $grouped[$key]['prices'][(string) $color] = $rule->unit_amount_minor !== null
                ? $this->moneyParser->minorToMajor($rule->unit_amount_minor)
                : '';
        }

        if ($grouped === []) {
            return $this->defaultSilkRanges($colors);
        }

        $ranges = array_values($grouped);
        usort($ranges, static fn (array $left, array $right): int => ((int) $left['min_quantity']) <=> ((int) $right['min_quantity']));

        foreach ($ranges as &$range) {
            foreach ($colors as $color) {
                $range['prices'][(string) $color] ??= '';
            }
        }
        unset($range);

        return $ranges;
    }

    /**
     * @param  list<int>  $colors
     * @return list<array<string, mixed>>
     */
    private function defaultSilkRanges(array $colors): array
    {
        $definitions = [[10, 19], [20, 49], [50, 99], [100, null]];

        return array_map(static function (array $definition) use ($colors): array {
            $prices = [];

            foreach ($colors as $color) {
                $prices[(string) $color] = '';
            }

            return [
                'min_quantity' => $definition[0],
                'max_quantity' => $definition[1],
                'prices' => $prices,
            ];
        }, $definitions);
    }

    private function conditionColor(ServicePriceRule $rule): ?int
    {
        foreach ($rule->conditions ?? [] as $condition) {
            if ($condition['parameter'] === 'screen_colors' && $condition['operator'] === 'eq') {
                $color = (int) ($condition['value'] ?? 0);

                return $color > 0 ? $color : null;
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $options
     * @param  array<string, mixed>  $settings
     * @return list<array{option: string, amount: string}>
     */
    private function addonRows(array $options, array $settings, string $parameter): array
    {
        $allConfigured = $settings['per_item_addons'] ?? [];
        $configured = is_array($allConfigured)
            && is_array($allConfigured[$parameter] ?? null)
                ? $allConfigured[$parameter]
                : [];

        return array_map(function (string $option) use ($configured): array {
            $minor = (int) ($configured[$option] ?? 0);

            return [
                'option' => $option,
                'amount' => $minor > 0 ? $this->moneyParser->minorToMajor($minor) : '',
            ];
        }, $options);
    }

    /**
     * @return array<string, int>
     */
    private function addonMap(mixed $rows, string $field): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $option = trim((string) ($row['option'] ?? ''));
            $rawAmount = trim((string) ($row['amount'] ?? ''));

            if ($option === '' || $rawAmount === '') {
                continue;
            }

            try {
                $minor = $this->moneyParser->majorToMinor($rawAmount);
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    "{$field}.{$index}.amount" => "Revise o adicional de {$option}.",
                ]);
            }

            if ($minor > 0) {
                $map[$option] = $minor;
            }
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $ranges
     * @param  list<int>  $colors
     */
    private function validateRanges(array $ranges, array $colors): void
    {
        if ($ranges === []) {
            throw ValidationException::withMessages(['ranges' => 'Adicione pelo menos uma faixa de quantidade.']);
        }

        usort($ranges, static fn (array $left, array $right): int => ((int) ($left['min_quantity'] ?? 0)) <=> ((int) ($right['min_quantity'] ?? 0)));
        $previousMax = null;
        $openEndedSeen = false;

        foreach ($ranges as $index => $range) {
            $min = (int) ($range['min_quantity'] ?? 0);
            $max = $this->nullablePositiveInt($range['max_quantity'] ?? null);

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

            if (! is_array($range['prices'] ?? null)) {
                throw ValidationException::withMessages(["ranges.{$index}.prices" => 'Preencha os preços desta faixa.']);
            }

            $prices = $range['prices'];

            foreach ($colors as $color) {
                $value = trim((string) ($prices[(string) $color] ?? $prices[$color] ?? ''));

                if ($value === '') {
                    throw ValidationException::withMessages([
                        "ranges.{$index}.prices.{$color}" => "Preencha o preço de {$color} cor(es) nesta faixa.",
                    ]);
                }
            }

            $previousMax = $max;
            $openEndedSeen = $max === null;
        }
    }

    private function silkRuleName(int $min, ?int $max, int $colors): string
    {
        $range = $max === null ? "{$min} ou mais" : "{$min} a {$max}";
        $colorLabel = $colors === 1 ? '1 cor' : "{$colors} cores";

        return "{$range} peças · {$colorLabel}";
    }

    /** @param array<string, mixed> $settings */
    private function hasConfiguredAddons(array $settings): bool
    {
        $addons = $settings['per_item_addons'] ?? null;

        if (! is_array($addons)) {
            return false;
        }

        foreach ($addons as $values) {
            if (is_array($values) && $values !== []) {
                return true;
            }
        }

        return false;
    }

    /** @param list<string> $options */
    private function defaultSampleOption(array $options, string $preferred): string
    {
        if (in_array($preferred, $options, true)) {
            return $preferred;
        }

        return $options[0] ?? '';
    }

    /** @param array<string, mixed> $settings */
    private function moneyFromSetting(array $settings, string $key): string
    {
        $minor = (int) ($settings[$key] ?? 0);

        return $minor > 0 ? $this->moneyParser->minorToMajor($minor) : '';
    }

    private function percentFromBasisPoints(mixed $basisPoints): string
    {
        $value = max(0, (int) $basisPoints);
        $whole = intdiv($value, 100);
        $decimal = $value % 100;

        return $decimal === 0 ? (string) $whole : $whole.','.str_pad((string) $decimal, 2, '0', STR_PAD_LEFT);
    }

    private function percentToBasisPoints(string $value): int
    {
        $normalized = $this->normalizeDecimal($value === '' ? '0' : $value);

        return max(0, (int) bcadd(bcmul($normalized, '100', 2), '0.5', 0));
    }

    private function normalizeDecimal(string $value): string
    {
        return str_replace(',', '.', trim($value));
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
