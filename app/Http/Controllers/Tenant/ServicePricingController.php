<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Application\Pricing\Actions\SaveServicePricingAction;
use App\Application\Pricing\Data\SavePriceRuleData;
use App\Application\Pricing\Data\ServicePricingSetupData;
use App\Application\Pricing\Services\AdditionalGuidedPricingDataService;
use App\Application\Pricing\Services\GuidedPricingDataService;
use App\Domains\Pricing\Data\ServicePricingInput;
use App\Domains\Pricing\Enums\GuidedPricingTemplate;
use App\Domains\Pricing\Models\ServicePriceRule;
use App\Domains\Pricing\Models\ServicePriceTable;
use App\Domains\Pricing\Services\DynamicPricingService;
use App\Domains\Pricing\Services\GuidedPricingTemplateResolver;
use App\Domains\Pricing\ValueObjects\MoneyParser;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceParameterDefinition;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

final readonly class ServicePricingController
{
    public function __construct(
        private MoneyParser $moneyParser,
        private GuidedPricingTemplateResolver $templateResolver,
        private GuidedPricingDataService $guidedData,
        private AdditionalGuidedPricingDataService $additionalGuidedData,
    ) {}

    public function index(): View
    {
        $services = ServiceType::query()
            ->with(['activePriceTable.rules'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('tenant.pricing.index', compact('services'));
    }

    public function edit(string $serviceType): View
    {
        $service = ServiceType::query()
            ->with(['activeSchemaVersion.parameters', 'activePriceTable.rules'])
            ->findOrFail($serviceType);
        $table = $service->activePriceTable;
        $template = $this->templateResolver->resolve($service);

        return view('tenant.pricing.edit', [
            'serviceType' => $service,
            'table' => $table,
            'pricingTemplate' => $template,
            'guidedInitial' => match ($template) {
                GuidedPricingTemplate::DTF_METER => $this->guidedData->initialDtf($service, $table),
                GuidedPricingTemplate::SILK_MATRIX => $this->guidedData->initialSilk($service, $table),
                GuidedPricingTemplate::SUBLIMATION_MATRIX => $this->additionalGuidedData->initialSublimation($service, $table),
                GuidedPricingTemplate::EMBROIDERY_MATRIX => $this->additionalGuidedData->initialEmbroidery($service, $table),
                GuidedPricingTemplate::GENERIC => [],
            },
            'silkOptions' => $template === GuidedPricingTemplate::SILK_MATRIX
                ? $this->guidedData->parameterOptions($service)
                : [],
            'legacyConfiguration' => $this->isLegacyConfiguration($table, $template),
            'strategies' => array_filter(
                PricingStrategy::cases(),
                static fn (PricingStrategy $strategy): bool => $strategy !== PricingStrategy::ROLL_LENGTH,
            ),
            'pricingParameters' => $this->pricingParameters($service),
            'initialRules' => $this->ruleRows($table),
            'initialSettings' => $table instanceof ServicePriceTable
                ? ($table->settings ?? $this->defaultSettings($service))
                : $this->defaultSettings($service),
        ]);
    }

    public function update(
        Request $request,
        string $serviceType,
        SaveServicePricingAction $action,
    ): RedirectResponse {
        $service = ServiceType::query()->findOrFail($serviceType);
        $template = $this->templateResolver->resolve($service);
        $setup = match ($template) {
            GuidedPricingTemplate::DTF_METER => $this->guidedData->dtfSetup($this->validateDtf($request)),
            GuidedPricingTemplate::SILK_MATRIX => $this->guidedData->silkSetup($this->validateSilk($request)),
            GuidedPricingTemplate::SUBLIMATION_MATRIX => $this->additionalGuidedData->sublimationSetup($this->validateSublimation($request)),
            GuidedPricingTemplate::EMBROIDERY_MATRIX => $this->additionalGuidedData->embroiderySetup($this->validateEmbroidery($request)),
            GuidedPricingTemplate::GENERIC => $this->genericSetup($request),
        };
        $actor = $request->user();
        abort_unless($actor instanceof User, 401);

        $action->execute(
            $actor,
            $service,
            $setup->strategy,
            $setup->rules,
            $setup->settings,
            $setup->validFrom,
            $setup->validUntil,
        );

        return redirect()
            ->route('tenant.pricing.edit', ['serviceType' => $service->getKey()])
            ->with('success', 'Configuração salva e pronta para uso. A versão anterior foi preservada.');
    }

    public function simulate(
        Request $request,
        string $serviceType,
        DynamicPricingService $pricing,
        TenantContext $tenantContext,
    ): RedirectResponse {
        $service = ServiceType::query()->with('activeSchemaVersion.parameters')->findOrFail($serviceType);
        $template = $this->templateResolver->resolve($service);
        $validated = match ($template) {
            GuidedPricingTemplate::DTF_METER => $request->validate([
                'simulation_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'simulation_width_cm' => ['required', 'regex:/^\d+(?:[.,]\d{1,4})?$/'],
                'simulation_height_cm' => ['required', 'regex:/^\d+(?:[.,]\d{1,4})?$/'],
            ]),
            GuidedPricingTemplate::SILK_MATRIX => $request->validate([
                'simulation_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'simulation_screen_colors' => ['required', 'integer', 'min:1', 'max:20'],
                'simulation_white_base' => ['nullable', 'string', Rule::in(['Sim', 'Não', 'Automático'])],
                'simulation_ink_system' => ['nullable', 'string', 'max:100'],
                'simulation_print_effect' => ['nullable', 'string', 'max:100'],
            ]),
            GuidedPricingTemplate::SUBLIMATION_MATRIX => $request->validate([
                'simulation_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'simulation_category' => ['required', 'string', Rule::in(array_column($this->additionalGuidedData->sublimationCategories(), 'key'))],
            ]),
            GuidedPricingTemplate::EMBROIDERY_MATRIX => $request->validate([
                'simulation_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'simulation_stitch_range' => ['required', 'string', 'max:100'],
            ]),
            GuidedPricingTemplate::GENERIC => $request->validate([
                'simulation_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
                'simulation_parameters' => ['sometimes', 'array'],
                'simulation_parameters.*' => ['nullable'],
            ]),
        };

        if ($service->active_schema_version_id === null) {
            return back()->with('simulation', [
                'status' => 'INVALID_INPUT',
                'status_label' => 'Dados inválidos',
                'explanation' => 'Defina os campos do serviço antes de simular.',
                'total' => null,
                'errors' => [],
                'details' => [],
            ])->withInput();
        }

        $parameters = match ($template) {
            GuidedPricingTemplate::DTF_METER => [
                'width_cm' => (string) $validated['simulation_width_cm'],
                'height_cm' => (string) $validated['simulation_height_cm'],
            ],
            GuidedPricingTemplate::SILK_MATRIX => [
                'screen_colors' => (string) $validated['simulation_screen_colors'],
                'white_base' => (string) ($validated['simulation_white_base'] ?? 'Não'),
                'ink_system' => (string) ($validated['simulation_ink_system'] ?? ''),
                'print_effect' => (string) ($validated['simulation_print_effect'] ?? ''),
            ],
            GuidedPricingTemplate::SUBLIMATION_MATRIX => $this->additionalGuidedData->sublimationParameters((string) $validated['simulation_category']),
            GuidedPricingTemplate::EMBROIDERY_MATRIX => [
                'stitch_range' => (string) $validated['simulation_stitch_range'],
            ],
            GuidedPricingTemplate::GENERIC => is_array($validated['simulation_parameters'] ?? null)
                ? $validated['simulation_parameters']
                : [],
        };

        /** @var array<string, mixed> $parameters */
        $result = $pricing->calculate(new ServicePricingInput(
            tenantId: (string) $tenantContext->currentId(),
            serviceTypeId: (string) $service->getKey(),
            schemaVersionId: $service->active_schema_version_id,
            appliedQuantity: (int) $validated['simulation_quantity'],
            parameters: $parameters,
            referenceDate: CarbonImmutable::now(),
        ));

        return back()->with('simulation', $result->toArray())->withInput();
    }

    /** @return array<string, mixed> */
    private function validateDtf(Request $request): array
    {
        return $request->validate([
            'meter_cost' => ['required', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'usable_width_cm' => ['required', 'regex:/^\d+(?:[.,]\d{1,4})?$/'],
            'application_price' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'material_markup_percent' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'spacing_cm' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,4})?$/'],
            'waste_percent' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'allow_rotation' => ['nullable', 'boolean'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], [
            'meter_cost.required' => 'Informe quanto você paga por metro.',
            'meter_cost.regex' => 'Use um valor como 40,00 para o metro.',
            'usable_width_cm.required' => 'Informe a largura útil do material.',
            'usable_width_cm.regex' => 'Use uma medida como 58 ou 57,5 cm.',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateSilk(Request $request): array
    {
        return $request->validate([
            'setup_charge_mode' => ['required', Rule::in(['INCLUDED', 'SEPARATE'])],
            'setup_per_color' => ['nullable', 'required_if:setup_charge_mode,SEPARATE', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'white_base_mode' => ['required', Rule::in(['ADD_COLOR', 'ADD_PER_ITEM', 'NONE'])],
            'white_base_addon' => ['nullable', 'required_if:white_base_mode,ADD_PER_ITEM', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'colors' => ['required', 'array', 'min:1', 'max:12'],
            'colors.*' => ['required', 'integer', 'min:1', 'max:20', 'distinct'],
            'ranges' => ['required', 'array', 'min:1', 'max:30'],
            'ranges.*.min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.prices' => ['required', 'array'],
            'ranges.*.prices.*' => ['required', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'addons_enabled' => ['nullable', 'boolean'],
            'ink_addons' => ['nullable', 'array', 'max:30'],
            'ink_addons.*.option' => ['required', 'string', 'max:100'],
            'ink_addons.*.amount' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'effect_addons' => ['nullable', 'array', 'max:30'],
            'effect_addons.*.option' => ['required', 'string', 'max:100'],
            'effect_addons.*.amount' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], [
            'ranges.*.prices.*.required' => 'Preencha todos os preços visíveis ou remova a faixa/coluna que não utiliza.',
            'ranges.*.prices.*.regex' => 'Use valores com no máximo duas casas decimais, como 12,50.',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateSublimation(Request $request): array
    {
        $categoryKeys = array_column($this->additionalGuidedData->sublimationCategories(), 'key');

        return $request->validate([
            'selected_categories' => ['required', 'array', 'min:1', 'max:20'],
            'selected_categories.*' => ['required', 'string', 'distinct', Rule::in($categoryKeys)],
            'ranges' => ['required', 'array', 'min:1', 'max:30'],
            'ranges.*.min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.prices' => ['required', 'array'],
            'ranges.*.prices.*' => ['required', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'sample_category' => ['nullable', 'string', Rule::in($categoryKeys)],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], [
            'selected_categories.required' => 'Escolha pelo menos um tipo de sublimação.',
            'selected_categories.min' => 'Escolha pelo menos um tipo de sublimação.',
            'ranges.*.prices.*.required' => 'Preencha todos os preços visíveis.',
            'ranges.*.prices.*.regex' => 'Use valores com no máximo duas casas decimais, como 12,50.',
        ]);
    }

    /** @return array<string, mixed> */
    private function validateEmbroidery(Request $request): array
    {
        return $request->validate([
            'digitizing_charge_mode' => ['required', 'string', Rule::in(['INCLUDED', 'SEPARATE'])],
            'stitch_columns' => ['required', 'array', 'min:1', 'max:20'],
            'stitch_columns.*.key' => ['required', 'string', 'max:40', 'distinct'],
            'stitch_columns.*.label' => ['required', 'string', 'max:100', 'distinct'],
            'digitizing_price' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'ranges' => ['required', 'array', 'min:1', 'max:30'],
            'ranges.*.min_quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.max_quantity' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'ranges.*.prices' => ['required', 'array'],
            'ranges.*.prices.*' => ['required', 'regex:/^\d+(?:[.,]\d{1,2})?$/'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
        ], [
            'digitizing_charge_mode.required' => 'Escolha como você cobra a criação da matriz.',
            'stitch_columns.required' => 'Escolha pelo menos uma faixa de pontos.',
            'stitch_columns.min' => 'Escolha pelo menos uma faixa de pontos.',
            'ranges.*.prices.*.required' => 'Preencha todos os preços visíveis.',
            'ranges.*.prices.*.regex' => 'Use valores com no máximo duas casas decimais, como 12,50.',
        ]);
    }

    private function genericSetup(Request $request): ServicePricingSetupData
    {
        $validated = $this->validateGenericPricing($request);
        $rawRules = $validated['rules'] ?? [];

        if (! is_array($rawRules)) {
            $rawRules = [];
        }

        /** @var list<array<string, mixed>> $ruleRows */
        $ruleRows = array_values(array_filter($rawRules, 'is_array'));

        return new ServicePricingSetupData(
            strategy: PricingStrategy::from((string) $validated['strategy']),
            rules: $this->toRuleData($ruleRows),
            settings: $this->settings($validated),
            validFrom: $this->nullableString($validated['valid_from'] ?? null),
            validUntil: $this->nullableString($validated['valid_until'] ?? null),
        );
    }

    /** @return array<string, mixed> */
    private function validateGenericPricing(Request $request): array
    {
        return $request->validate([
            'strategy' => ['required', 'string', Rule::enum(PricingStrategy::class)],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'width_parameter' => ['nullable', 'string', 'max:100'],
            'height_parameter' => ['nullable', 'string', 'max:100'],
            'stitch_parameter' => ['nullable', 'string', 'max:100'],
            'rules' => ['required', 'array', 'min:1', 'max:100'],
            'rules.*.name' => ['required', 'string', 'max:140'],
            'rules.*.min_quantity' => ['nullable', 'integer', 'min:1'],
            'rules.*.max_quantity' => ['nullable', 'integer', 'min:1'],
            'rules.*.unit_price' => ['nullable', 'string', 'max:30'],
            'rules.*.rate_value' => ['nullable', 'regex:/^\d+(?:[.,]\d{1,8})?$/'],
            'rules.*.setup_price' => ['nullable', 'string', 'max:30'],
            'rules.*.minimum_price' => ['nullable', 'string', 'max:30'],
            'rules.*.priority' => ['nullable', 'integer', 'min:-10000', 'max:10000'],
            'rules.*.conditions' => ['sometimes', 'array', 'max:10'],
            'rules.*.conditions.*.parameter' => ['nullable', 'string', 'max:100'],
            'rules.*.conditions.*.operator' => ['nullable', Rule::in(['eq', 'in', 'gte', 'lte', 'between', 'contains_all'])],
            'rules.*.conditions.*.value' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<SavePriceRuleData>
     */
    private function toRuleData(array $rows): array
    {
        $rules = [];

        foreach ($rows as $index => $row) {
            try {
                $unitAmount = trim((string) ($row['unit_price'] ?? '')) !== ''
                    ? $this->moneyParser->majorToMinor((string) $row['unit_price'])
                    : null;
                $setup = $this->moneyParser->majorToMinor((string) ($row['setup_price'] ?? '0'));
                $minimum = $this->moneyParser->majorToMinor((string) ($row['minimum_price'] ?? '0'));
            } catch (InvalidArgumentException) {
                throw ValidationException::withMessages([
                    "rules.{$index}" => 'Revise os valores monetários informados.',
                ]);
            }

            $rules[] = new SavePriceRuleData(
                name: trim((string) $row['name']),
                minQuantity: $this->nullableInt($row['min_quantity'] ?? null),
                maxQuantity: $this->nullableInt($row['max_quantity'] ?? null),
                conditions: $this->conditions($this->conditionRows($row['conditions'] ?? [])),
                unitAmountMinor: $unitAmount,
                rateValue: $this->nullableDecimal($row['rate_value'] ?? null),
                rateUnit: null,
                setupAmountMinor: $setup,
                minimumAmountMinor: $minimum,
                priority: $this->nullableInt($row['priority'] ?? null) ?? 100,
                sortOrder: $index * 10,
            );
        }

        return $rules;
    }

    /** @return list<array<string, mixed>> */
    private function conditionRows(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        /** @var list<array<string, mixed>> $rows */
        $rows = array_values(array_filter($value, 'is_array'));

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{parameter: string, operator: string, value: mixed}>
     */
    private function conditions(array $rows): array
    {
        $conditions = [];

        foreach ($rows as $row) {
            $parameter = trim((string) ($row['parameter'] ?? ''));
            $operator = trim((string) ($row['operator'] ?? ''));
            $raw = trim((string) ($row['value'] ?? ''));

            if ($parameter === '' || $operator === '' || $raw === '') {
                continue;
            }

            $value = match ($operator) {
                'in', 'contains_all' => array_values(array_filter(array_map('trim', explode(',', $raw)))),
                'between' => array_map('trim', explode('|', $raw, 2)),
                default => $raw,
            };

            $conditions[] = ['parameter' => $parameter, 'operator' => $operator, 'value' => $value];
        }

        return $conditions;
    }

    /** @return Collection<int, ServiceParameterDefinition> */
    private function pricingParameters(ServiceType $service): Collection
    {
        if ($service->active_schema_version_id === null) {
            /** @var Collection<int, ServiceParameterDefinition> $empty */
            $empty = collect();

            return $empty;
        }

        return ServiceParameterDefinition::query()
            ->where('schema_version_id', $service->active_schema_version_id)
            ->where('affects_pricing', true)
            ->where('active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /** @return list<array<string, mixed>> */
    private function ruleRows(?ServicePriceTable $table): array
    {
        if (! $table instanceof ServicePriceTable) {
            return [[
                'name' => 'Preço padrão',
                'min_quantity' => 1,
                'max_quantity' => null,
                'unit_price' => '',
                'rate_value' => '',
                'setup_price' => '0,00',
                'minimum_price' => '0,00',
                'priority' => 100,
                'conditions' => [],
            ]];
        }

        return $table->rules->map(fn (ServicePriceRule $rule): array => [
            'name' => $rule->name,
            'min_quantity' => $rule->min_quantity,
            'max_quantity' => $rule->max_quantity,
            'unit_price' => $rule->unit_amount_minor !== null ? $this->moneyParser->minorToMajor($rule->unit_amount_minor) : '',
            'rate_value' => $rule->rate_value ?? '',
            'setup_price' => $this->moneyParser->minorToMajor($rule->setup_amount_minor),
            'minimum_price' => $this->moneyParser->minorToMajor($rule->minimum_amount_minor),
            'priority' => $rule->priority,
            'conditions' => collect($rule->conditions ?? [])->map(static function (array $condition): array {
                $value = $condition['value'];

                if (is_array($value)) {
                    $value = $condition['operator'] === 'between'
                        ? implode('|', array_map('strval', $value))
                        : implode(', ', array_map('strval', $value));
                }

                return [
                    'parameter' => $condition['parameter'],
                    'operator' => $condition['operator'],
                    'value' => (string) $value,
                ];
            })->values()->all(),
        ])->values()->all();
    }

    /** @return array<string, mixed> */
    private function defaultSettings(ServiceType $service): array
    {
        $parameters = $this->pricingParameters($service)->keyBy('key');

        return [
            'width_parameter' => $parameters->has('width_cm') ? 'width_cm' : '',
            'height_parameter' => $parameters->has('height_cm') ? 'height_cm' : '',
            'stitch_parameter' => $parameters->has('stitch_count') ? 'stitch_count' : '',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function settings(array $validated): array
    {
        return array_filter([
            'width_parameter' => $this->nullableString($validated['width_parameter'] ?? null),
            'height_parameter' => $this->nullableString($validated['height_parameter'] ?? null),
            'stitch_parameter' => $this->nullableString($validated['stitch_parameter'] ?? null),
        ], static fn (?string $value): bool => $value !== null);
    }

    private function isLegacyConfiguration(?ServicePriceTable $table, GuidedPricingTemplate $template): bool
    {
        return $template->isGuided()
            && $table instanceof ServicePriceTable
            && ($table->settings['guided_template'] ?? null) !== $template->value;
    }

    private function nullableInt(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function nullableDecimal(mixed $value): ?string
    {
        $string = $this->nullableString($value);

        return $string === null ? null : str_replace(',', '.', $string);
    }

    private function nullableString(mixed $value): ?string
    {
        $string = trim((string) ($value ?? ''));

        return $string === '' ? null : $string;
    }
}
