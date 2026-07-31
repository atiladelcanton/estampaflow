<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Data\RulePriceCalculation;
use App\Domains\Pricing\Data\ServicePriceResult;
use App\Domains\Pricing\Data\ServicePricingInput;
use App\Domains\Pricing\Enums\GuidedPricingTemplate;
use App\Domains\Pricing\Enums\PricingResultStatus;
use App\Domains\Pricing\Models\ServicePriceRule;
use App\Domains\Pricing\Models\ServicePriceTable;
use App\Domains\Pricing\ValueObjects\Money;
use App\Domains\ServiceCatalog\Enums\PricingMode;
use App\Domains\ServiceCatalog\Enums\PricingStrategy;
use App\Domains\ServiceCatalog\Models\ServiceType;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Collection;

final readonly class DynamicPricingService
{
    public function __construct(
        private TenantContext $tenantContext,
        private ConditionMatcher $matcher,
        private RuleSpecificity $specificity,
        private DecimalMoneyCalculator $decimalMoney,
        private RollMaterialPricingCalculator $rollMaterial,
    ) {}

    public function calculate(ServicePricingInput $input): ServicePriceResult
    {
        if ((string) $this->tenantContext->currentId() !== $input->tenantId) {
            return ServicePriceResult::failure(PricingResultStatus::INVALID_INPUT, 'O serviço não pertence ao ambiente atual.');
        }

        if ($input->appliedQuantity <= 0) {
            return ServicePriceResult::failure(PricingResultStatus::INVALID_INPUT, 'Informe uma quantidade maior que zero.');
        }

        $service = ServiceType::query()->find($input->serviceTypeId);

        if (! $service instanceof ServiceType || ! $service->active) {
            return ServicePriceResult::failure(PricingResultStatus::UNAVAILABLE, 'Este serviço não está disponível para novos orçamentos.');
        }

        if ($service->active_schema_version_id !== $input->schemaVersionId) {
            return ServicePriceResult::failure(PricingResultStatus::INVALID_INPUT, 'Os campos informados não correspondem à configuração atual do serviço.');
        }

        if ($service->pricing_mode === PricingMode::MANUAL) {
            return ServicePriceResult::failure(PricingResultStatus::MANUAL_REQUIRED, 'Este serviço utiliza preço manual.');
        }

        $table = $service->activePriceTable()->with('rules')->first();

        if (! $table instanceof ServicePriceTable || ! $table->isValidOn($input->referenceDate)) {
            return $this->missingRuleResult($service);
        }

        if ($table->schema_version_id !== $input->schemaVersionId || $table->currency !== $input->currency) {
            return ServicePriceResult::failure(PricingResultStatus::INVALID_INPUT, 'A tabela de preços não corresponde aos dados informados.');
        }

        $parameters = $this->parametersForMatching($table, $input->parameters);

        /** @var Collection<int, ServicePriceRule> $candidates */
        $candidates = $table->rules
            ->filter(fn (ServicePriceRule $rule): bool => $this->quantityMatches($rule, $input->appliedQuantity))
            ->filter(fn (ServicePriceRule $rule): bool => $this->matcher->matches($rule->conditions ?? [], $parameters))
            ->values();

        if ($candidates->isEmpty()) {
            return $this->missingRuleResult($service);
        }

        $ranked = $candidates->sort(function (ServicePriceRule $left, ServicePriceRule $right): int {
            return $this->specificity->compare(
                $this->specificity->score($right),
                $this->specificity->score($left),
            );
        })->values();

        $winner = $ranked->first();

        if (! $winner instanceof ServicePriceRule) {
            return $this->missingRuleResult($service);
        }

        $second = $ranked->get(1);

        if (
            $second instanceof ServicePriceRule
            && $this->specificity->compare(
                $this->specificity->score($winner),
                $this->specificity->score($second),
            ) === 0
        ) {
            return ServicePriceResult::failure(
                PricingResultStatus::AMBIGUOUS,
                'Duas regras possuem a mesma prioridade e especificidade. Revise a configuração antes de usar este preço.',
            );
        }

        $calculation = $this->calculateRule($table, $winner, $input, $parameters);

        if (! $calculation instanceof RulePriceCalculation) {
            return ServicePriceResult::failure(
                PricingResultStatus::INVALID_INPUT,
                $table->strategy === PricingStrategy::ROLL_LENGTH
                    ? 'Revise as medidas da arte. Ela precisa caber na largura útil informada.'
                    : 'Faltam medidas ou parâmetros necessários para calcular este preço.',
            );
        }

        $explanation = $calculation->explanation
            ?? sprintf(
                '%s aplicada pela configuração v%d. Total já inclui preparação e valor mínimo quando definidos.',
                $winner->name,
                $table->version,
            );

        return ServicePriceResult::matched(
            $calculation->total,
            (string) $table->getKey(),
            (string) $winner->getKey(),
            $explanation,
            $calculation->details,
        );
    }

    private function missingRuleResult(ServiceType $service): ServicePriceResult
    {
        return $service->pricing_mode === PricingMode::HYBRID
            ? ServicePriceResult::failure(PricingResultStatus::MANUAL_REQUIRED, 'Nenhuma regra automática foi encontrada. Informe o preço manualmente.')
            : ServicePriceResult::failure(PricingResultStatus::UNAVAILABLE, 'Nenhuma faixa de preço atende a este pedido.');
    }

    private function quantityMatches(ServicePriceRule $rule, int $quantity): bool
    {
        return ($rule->min_quantity === null || $quantity >= $rule->min_quantity)
            && ($rule->max_quantity === null || $quantity <= $rule->max_quantity)
            && $rule->active;
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private function calculateRule(
        ServicePriceTable $table,
        ServicePriceRule $rule,
        ServicePricingInput $input,
        array $parameters,
    ): ?RulePriceCalculation {
        if ($table->strategy === PricingStrategy::ROLL_LENGTH) {
            return $this->rollMaterial->calculate($table->settings ?? [], $input);
        }

        if (
            $table->strategy === PricingStrategy::MATRIX
            && ($table->settings['guided_template'] ?? null) === GuidedPricingTemplate::SILK_MATRIX->value
        ) {
            return $this->silkMatrixTotal($table, $rule, $input, $parameters);
        }

        $base = match ($table->strategy) {
            PricingStrategy::UNIT,
            PricingStrategy::QUANTITY_TIER,
            PricingStrategy::MATRIX => $this->unitTotal($rule, $input),
            PricingStrategy::AREA => $this->areaTotal($table, $rule, $input),
            PricingStrategy::STITCH_RANGE => $this->stitchTotal($table, $rule, $input),
            PricingStrategy::ROLL_LENGTH => null,
        };

        if (! $base instanceof Money) {
            return null;
        }

        $withSetup = $base->add(new Money($rule->setup_amount_minor, $table->currency));
        $total = $withSetup->max(new Money($rule->minimum_amount_minor, $table->currency));

        return new RulePriceCalculation($total);
    }

    private function unitTotal(ServicePriceRule $rule, ServicePricingInput $input): ?Money
    {
        if ($rule->unit_amount_minor === null) {
            return null;
        }

        return (new Money($rule->unit_amount_minor, $input->currency))->multiply($input->appliedQuantity);
    }

    /** @param array<string, mixed> $parameters */
    private function silkMatrixTotal(
        ServicePriceTable $table,
        ServicePriceRule $rule,
        ServicePricingInput $input,
        array $parameters,
    ): ?RulePriceCalculation {
        if ($rule->unit_amount_minor === null) {
            return null;
        }

        $settings = $table->settings ?? [];
        $matrixParameter = $this->settingString($settings, 'matrix_parameter', 'screen_colors');
        $effectiveColors = max(1, (int) ($parameters[$matrixParameter] ?? 0));
        $unitBaseMinor = $rule->unit_amount_minor;
        $unitAddonsMinor = $this->perItemAddons($settings, $input->parameters);

        $whiteBaseMode = (string) ($settings['white_base_mode'] ?? 'ADD_COLOR');
        $whiteBaseParameter = $this->settingString($settings, 'white_base_parameter', 'white_base');
        $whiteBaseApplied = $this->isYes($input->parameters[$whiteBaseParameter] ?? null);

        if ($whiteBaseApplied && $whiteBaseMode === 'ADD_PER_ITEM') {
            $unitAddonsMinor += max(0, (int) ($settings['white_base_addon_minor'] ?? 0));
        }

        $unitFinalMinor = $unitBaseMinor + $unitAddonsMinor;
        $piecesTotalMinor = $unitFinalMinor * $input->appliedQuantity;
        $setupMinor = (string) ($settings['setup_charge_mode'] ?? 'INCLUDED') === 'SEPARATE'
            ? max(0, (int) ($settings['setup_per_color_minor'] ?? 0)) * $effectiveColors
            : 0;
        $total = new Money($piecesTotalMinor + $setupMinor, $table->currency);

        return new RulePriceCalculation(
            total: $total,
            details: [
                'template' => 'SILK_MATRIX',
                'effective_colors' => $effectiveColors,
                'white_base_counted_as_color' => $whiteBaseApplied && $whiteBaseMode === 'ADD_COLOR',
                'base_unit_price' => (new Money($unitBaseMinor, $table->currency))->toArray(),
                'addons_per_item' => (new Money($unitAddonsMinor, $table->currency))->toArray(),
                'final_unit_price' => (new Money($unitFinalMinor, $table->currency))->toArray(),
                'pieces_total' => (new Money($piecesTotalMinor, $table->currency))->toArray(),
                'setup_total' => (new Money($setupMinor, $table->currency))->toArray(),
            ],
            explanation: sprintf(
                '%s: %d peça(s), %d cor(es) consideradas e preço final de %s por peça.',
                $rule->name,
                $input->appliedQuantity,
                $effectiveColors,
                (new Money($unitFinalMinor, $table->currency))->format(),
            ),
        );
    }

    /** @param array<string, mixed> $settings @param array<string, mixed> $parameters */
    private function perItemAddons(array $settings, array $parameters): int
    {
        $configured = $settings['per_item_addons'] ?? null;

        if (! is_array($configured)) {
            return 0;
        }

        $total = 0;

        foreach ($configured as $parameter => $values) {
            if (! is_string($parameter) || ! is_array($values)) {
                continue;
            }

            $selected = trim((string) ($parameters[$parameter] ?? ''));

            if ($selected !== '') {
                $total += max(0, (int) ($values[$selected] ?? 0));
            }
        }

        return $total;
    }

    /** @param array<string, mixed> $settings @param array<string, mixed> $parameters @return array<string, mixed> */
    private function parametersForMatching(ServicePriceTable $table, array $parameters): array
    {
        $settings = $table->settings ?? [];

        if (
            $table->strategy !== PricingStrategy::MATRIX
            || ($settings['guided_template'] ?? null) !== GuidedPricingTemplate::SILK_MATRIX->value
        ) {
            return $parameters;
        }

        $matrixParameter = $this->settingString($settings, 'matrix_parameter', 'screen_colors');
        $whiteBaseParameter = $this->settingString($settings, 'white_base_parameter', 'white_base');
        $colors = max(0, (int) ($parameters[$matrixParameter] ?? 0));

        if (
            (string) ($settings['white_base_mode'] ?? 'ADD_COLOR') === 'ADD_COLOR'
            && $this->isYes($parameters[$whiteBaseParameter] ?? null)
        ) {
            $colors++;
        }

        $parameters[$matrixParameter] = (string) $colors;

        return $parameters;
    }

    private function areaTotal(
        ServicePriceTable $table,
        ServicePriceRule $rule,
        ServicePricingInput $input,
    ): ?Money {
        $settings = $table->settings ?? [];
        $widthKey = $this->settingString($settings, 'width_parameter', 'width_cm');
        $heightKey = $this->settingString($settings, 'height_parameter', 'height_cm');
        $width = str_replace(',', '.', trim((string) ($input->parameters[$widthKey] ?? '')));
        $height = str_replace(',', '.', trim((string) ($input->parameters[$heightKey] ?? '')));

        if ($rule->rate_value === null || ! is_numeric($width) || ! is_numeric($height)) {
            return null;
        }

        $area = bcmul($width, $height, 8);
        $perItem = bcmul($area, $rule->rate_value, 8);
        $line = bcmul($perItem, (string) $input->appliedQuantity, 8);

        return $this->decimalMoney->majorToMoney($line, $table->currency);
    }

    private function stitchTotal(
        ServicePriceTable $table,
        ServicePriceRule $rule,
        ServicePricingInput $input,
    ): ?Money {
        $settings = $table->settings ?? [];
        $stitchKey = $this->settingString($settings, 'stitch_parameter', 'stitch_count');
        $stitches = str_replace(',', '.', trim((string) ($input->parameters[$stitchKey] ?? '')));

        if ($rule->rate_value === null || ! is_numeric($stitches)) {
            return null;
        }

        $thousands = bcdiv($stitches, '1000', 8);
        $perItem = bcmul($thousands, $rule->rate_value, 8);
        $line = bcmul($perItem, (string) $input->appliedQuantity, 8);

        return $this->decimalMoney->majorToMoney($line, $table->currency);
    }

    /** @param array<string, mixed> $settings */
    private function settingString(array $settings, string $key, string $default): string
    {
        $value = $settings[$key] ?? null;

        return is_string($value) && trim($value) !== '' ? trim($value) : $default;
    }

    private function isYes(mixed $value): bool
    {
        return in_array(mb_strtolower(trim((string) ($value ?? ''))), ['sim', 'yes', 'true', '1'], true);
    }
}
