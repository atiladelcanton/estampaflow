<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

use App\Domains\Pricing\Models\ServicePriceRule;

final class RuleSpecificity
{
    /** @return array{int, int, int, int, int, int} */
    public function score(ServicePriceRule $rule): array
    {
        $eq = 0;
        $in = 0;
        $closedRanges = 0;
        $rangeWidthScore = 0;
        $conditions = $rule->conditions ?? [];

        foreach ($conditions as $condition) {
            $operator = $condition['operator'];
            $eq += $operator === 'eq' ? 1 : 0;
            $in += $operator === 'in' ? 1 : 0;

            if ($operator === 'between' && is_array($condition['value']) && count($condition['value']) === 2) {
                $closedRanges++;
                $rangeWidthScore -= $this->scaledWidth($condition['value'][0], $condition['value'][1]);
            }
        }

        if ($rule->min_quantity !== null && $rule->max_quantity !== null) {
            $closedRanges++;
            $rangeWidthScore -= max(0, $rule->max_quantity - $rule->min_quantity);
        }

        return [$eq, $in, $closedRanges, count($conditions), $rangeWidthScore, $rule->priority];
    }

    /**
     * @param  array{int, int, int, int, int, int}  $left
     * @param  array{int, int, int, int, int, int}  $right
     */
    public function compare(array $left, array $right): int
    {
        foreach ($left as $index => $value) {
            if ($value === $right[$index]) {
                continue;
            }

            return $value <=> $right[$index];
        }

        return 0;
    }

    private function scaledWidth(mixed $min, mixed $max): int
    {
        $minimum = str_replace(',', '.', trim((string) $min));
        $maximum = str_replace(',', '.', trim((string) $max));

        if (! is_numeric($minimum) || ! is_numeric($maximum)) {
            return 0;
        }

        return (int) bcmul(bcsub($maximum, $minimum, 4), '10000', 0);
    }
}
