<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Data\RulePriceCalculation;
use App\Domains\Pricing\Data\ServicePricingInput;
use App\Domains\Pricing\ValueObjects\Money;

final class RollMaterialPricingCalculator
{
    private const SCALE = 1000;

    /** @param array<string, mixed> $settings */
    public function calculate(array $settings, ServicePricingInput $input): ?RulePriceCalculation
    {
        $widthKey = $this->stringSetting($settings, 'width_parameter', 'width_cm');
        $heightKey = $this->stringSetting($settings, 'height_parameter', 'height_cm');
        $artWidth = $this->dimensionToUnits($input->parameters[$widthKey] ?? null);
        $artHeight = $this->dimensionToUnits($input->parameters[$heightKey] ?? null);
        $usableWidth = $this->dimensionToUnits($settings['usable_width_cm'] ?? null);
        $spacing = $this->dimensionToUnits($settings['spacing_cm'] ?? '0') ?? 0;

        if ($artWidth === null || $artHeight === null || $usableWidth === null) {
            return null;
        }

        $normal = $this->layout($artWidth, $artHeight, $usableWidth, $spacing, $input->appliedQuantity, false);
        $rotated = (bool) ($settings['allow_rotation'] ?? true)
            ? $this->layout($artHeight, $artWidth, $usableWidth, $spacing, $input->appliedQuantity, true)
            : null;

        $layout = $this->bestLayout($normal, $rotated);

        if ($layout === null) {
            return null;
        }

        $wasteBasisPoints = $this->nonNegativeInt($settings['waste_basis_points'] ?? 0);
        $lengthWithWaste = $this->applyBasisPoints($layout['length_units'], $wasteBasisPoints);
        $meterUnits = 100 * self::SCALE;
        $requiredMetersThousandths = $this->ceilDiv($lengthWithWaste * 1000, $meterUnits);
        $purchaseStep = max(1, $this->nonNegativeInt($settings['purchase_step_meters'] ?? 1));
        $minimumMeters = max(1, $this->nonNegativeInt($settings['minimum_purchase_meters'] ?? 1));
        $chargedMeters = max($minimumMeters, $this->ceilDiv($requiredMetersThousandths, $purchaseStep * 1000) * $purchaseStep);

        $meterCostMinor = $this->nonNegativeInt($settings['meter_cost_minor'] ?? 0);
        $applicationAmountMinor = $this->nonNegativeInt($settings['application_amount_minor'] ?? 0);
        $markupBasisPoints = $this->nonNegativeInt($settings['material_markup_basis_points'] ?? 0);

        $materialCostMinor = $meterCostMinor * $chargedMeters;
        $materialMarkupMinor = $this->percentageMinor($materialCostMinor, $markupBasisPoints);
        $applicationTotalMinor = $applicationAmountMinor * $input->appliedQuantity;
        $totalMinor = $materialCostMinor + $materialMarkupMinor + $applicationTotalMinor;
        $total = new Money($totalMinor, $input->currency);
        $perItem = new Money($this->roundDivide($totalMinor, $input->appliedQuantity), $input->currency);

        $requiredMeters = $requiredMetersThousandths / 1000;
        $leftoverMeters = max(0, $chargedMeters - $requiredMeters);

        return new RulePriceCalculation(
            total: $total,
            details: [
                'template' => 'DTF_METER',
                'items_per_row' => $layout['items_per_row'],
                'rows' => $layout['rows'],
                'orientation' => $layout['rotated'] ? 'Girada para aproveitar melhor o material' : 'Posição original',
                'required_length_cm' => $this->formatDecimalUnits($lengthWithWaste),
                'required_meters' => number_format($requiredMeters, 2, ',', '.'),
                'charged_meters' => $chargedMeters,
                'leftover_meters' => number_format($leftoverMeters, 2, ',', '.'),
                'material_cost' => (new Money($materialCostMinor, $input->currency))->toArray(),
                'material_markup' => (new Money($materialMarkupMinor, $input->currency))->toArray(),
                'application_total' => (new Money($applicationTotalMinor, $input->currency))->toArray(),
                'per_item' => $perItem->toArray(),
            ],
            explanation: sprintf(
                '%d peças ocupam cerca de %s m. Como a compra é feita em metros inteiros, foram considerados %d m.',
                $input->appliedQuantity,
                number_format($requiredMeters, 2, ',', '.'),
                $chargedMeters,
            ),
        );
    }

    /**
     * @return array{items_per_row: int, rows: int, length_units: int, rotated: bool}|null
     */
    private function layout(
        int $itemWidth,
        int $itemHeight,
        int $usableWidth,
        int $spacing,
        int $quantity,
        bool $rotated,
    ): ?array {
        if ($itemWidth > $usableWidth) {
            return null;
        }

        $itemsPerRow = max(1, intdiv($usableWidth + $spacing, $itemWidth + $spacing));
        $rows = $this->ceilDiv($quantity, $itemsPerRow);
        $length = ($rows * $itemHeight) + (max(0, $rows - 1) * $spacing);

        return [
            'items_per_row' => $itemsPerRow,
            'rows' => $rows,
            'length_units' => $length,
            'rotated' => $rotated,
        ];
    }

    /**
     * @param  array{items_per_row: int, rows: int, length_units: int, rotated: bool}|null  $normal
     * @param  array{items_per_row: int, rows: int, length_units: int, rotated: bool}|null  $rotated
     * @return array{items_per_row: int, rows: int, length_units: int, rotated: bool}|null
     */
    private function bestLayout(?array $normal, ?array $rotated): ?array
    {
        if ($normal === null) {
            return $rotated;
        }

        if ($rotated === null) {
            return $normal;
        }

        return $rotated['length_units'] < $normal['length_units'] ? $rotated : $normal;
    }

    private function dimensionToUnits(mixed $value): ?int
    {
        $normalized = str_replace(',', '.', trim((string) ($value ?? '')));

        if ($normalized === '' || ! preg_match('/^\d+(?:\.\d{1,4})?$/', $normalized)) {
            return null;
        }

        $units = (int) bcadd(bcmul($normalized, (string) self::SCALE, 4), '0.5', 0);

        return $units > 0 ? $units : null;
    }

    private function formatDecimalUnits(int $units): string
    {
        return number_format($units / self::SCALE, 2, ',', '.');
    }

    private function applyBasisPoints(int $value, int $basisPoints): int
    {
        if ($basisPoints <= 0) {
            return $value;
        }

        return $this->ceilDiv($value * (10000 + $basisPoints), 10000);
    }

    private function percentageMinor(int $amountMinor, int $basisPoints): int
    {
        return intdiv(($amountMinor * $basisPoints) + 5000, 10000);
    }

    private function roundDivide(int $value, int $divisor): int
    {
        return intdiv($value + intdiv($divisor, 2), $divisor);
    }

    private function ceilDiv(int $value, int $divisor): int
    {
        return intdiv($value + $divisor - 1, $divisor);
    }

    /** @param array<string, mixed> $settings */
    private function stringSetting(array $settings, string $key, string $default): string
    {
        return is_string($settings[$key] ?? null) && trim((string) $settings[$key]) !== ''
            ? trim((string) $settings[$key])
            : $default;
    }

    private function nonNegativeInt(mixed $value): int
    {
        return max(0, (int) $value);
    }
}
