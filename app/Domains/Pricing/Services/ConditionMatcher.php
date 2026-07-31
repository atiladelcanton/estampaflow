<?php

declare(strict_types=1);

namespace App\Domains\Pricing\Services;

final class ConditionMatcher
{
    /**
     * @param  list<array{parameter: string, operator: string, value: mixed}>  $conditions
     * @param  array<string, mixed>  $parameters
     */
    public function matches(array $conditions, array $parameters): bool
    {
        foreach ($conditions as $condition) {
            $key = $condition['parameter'];

            if (! array_key_exists($key, $parameters)) {
                return false;
            }

            if (! $this->matchesOne($parameters[$key], $condition['operator'], $condition['value'])) {
                return false;
            }
        }

        return true;
    }

    private function matchesOne(mixed $actual, string $operator, mixed $expected): bool
    {
        return match ($operator) {
            'eq' => $this->same($actual, $expected),
            'in' => is_array($expected) && $this->in($actual, $expected),
            'gte' => $this->compare($actual, $expected) >= 0,
            'lte' => $this->compare($actual, $expected) <= 0,
            'between' => is_array($expected)
                && count($expected) === 2
                && $this->compare($actual, $expected[0]) >= 0
                && $this->compare($actual, $expected[1]) <= 0,
            'contains_all' => is_array($actual)
                && is_array($expected)
                && array_diff(array_map('strval', $expected), array_map('strval', $actual)) === [],
            default => false,
        };
    }

    private function same(mixed $actual, mixed $expected): bool
    {
        if (is_bool($actual) || is_bool($expected)) {
            return filter_var($actual, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                === filter_var($expected, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);
        }

        return trim((string) $actual) === trim((string) $expected);
    }

    /** @param list<mixed> $expected */
    private function in(mixed $actual, array $expected): bool
    {
        foreach ($expected as $value) {
            if ($this->same($actual, $value)) {
                return true;
            }
        }

        return false;
    }

    private function compare(mixed $left, mixed $right): int
    {
        $leftValue = str_replace(',', '.', trim((string) $left));
        $rightValue = str_replace(',', '.', trim((string) $right));

        if (is_numeric($leftValue) && is_numeric($rightValue)) {
            return bccomp($leftValue, $rightValue, 8);
        }

        return strcmp($leftValue, $rightValue);
    }
}
