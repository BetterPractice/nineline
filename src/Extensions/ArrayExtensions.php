<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this extension.
 */

namespace BetterPractice\NineLine\Extensions;

module BetterPractice\NineLine;

/**
 * Extension methods on the built-in `array` type.
 *
 *     use module BetterPractice\NineLine as NL;
 *     [1, 2, 3, 2]->unique();          // [1, 2, 3]
 *     [[1, 2], [3]]->flatten();        // [1, 2, 3]
 *     [1, 2, 3, 4]->sum();             // 10
 *
 * The receiver is the declared variable `$a`, bound by value — these never
 * mutate the caller's array.
 */
extension ArrayExtensions on array $a
{
    public function isEmpty(): bool
    {
        return $a === [];
    }

    /** The first element, or null when empty. */
    public function first(): mixed
    {
        foreach ($a as $value) {
            return $value;
        }
        return null;
    }

    /** The last element, or null when empty. */
    public function last(): mixed
    {
        if ($a === []) {
            return null;
        }
        return $a[array_key_last($a)];
    }

    /** The sum of the elements (0 when empty). */
    public function sum(): int|float
    {
        return array_sum($a);
    }

    /** The product of the elements (1 when empty). */
    public function product(): int|float
    {
        return array_product($a);
    }

    /** Strict (===) membership test. */
    public function contains(mixed $value): bool
    {
        return in_array($value, $a, true);
    }

    /** The distinct elements, preserving first-seen order and re-indexing. */
    public function unique(): array
    {
        return array_values(array_unique($a, SORT_REGULAR));
    }

    /** Flatten one level of nested arrays; scalar elements are kept as-is. */
    public function flatten(): array
    {
        $out = [];
        foreach ($a as $value) {
            if (is_array($value)) {
                foreach ($value as $inner) {
                    $out[] = $inner;
                }
            } else {
                $out[] = $value;
            }
        }
        return $out;
    }

    /**
     * Apply $fn to every value, preserving keys.
     *
     * @param callable(mixed): mixed $fn
     */
    public function mapValues(callable $fn): array
    {
        return array_map($fn, $a);
    }

    /**
     * Keep only values for which $fn returns true, preserving keys.
     *
     * @param callable(mixed): bool $fn
     */
    public function filterValues(callable $fn): array
    {
        return array_filter($a, $fn);
    }

    /** The smallest element, or null when empty. */
    public function min(): mixed
    {
        return $a === [] ? null : min($a);
    }

    /** The largest element, or null when empty. */
    public function max(): mixed
    {
        return $a === [] ? null : max($a);
    }
}
