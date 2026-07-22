<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this extension.
 */

namespace BetterPractice\NineLine\Extensions;

module BetterPractice\NineLine;

use BetterPractice\NineLine\Collections\Sequence;

/**
 * Extension methods on the built-in `int` type.
 *
 *     use module BetterPractice\NineLine as NL;
 *     42->isEven();          // true
 *     (-5)->clamp(0, 10);    // 0
 *     3->times(fn($i) => $i * $i);  // [0, 1, 4]
 *
 * The receiver is the declared variable `$n`, bound by value.
 */
extension IntExtensions on int $n
{
    public function isEven(): bool
    {
        return $n % 2 === 0;
    }

    public function isOdd(): bool
    {
        return $n % 2 !== 0;
    }

    public function abs(): int
    {
        return $n < 0 ? -$n : $n;
    }

    /** Constrain the value to the inclusive range [$min, $max]. */
    public function clamp(int $min, int $max): int
    {
        if ($min > $max) {
            [$min, $max] = [$max, $min];
        }
        return max($min, min($max, $n));
    }

    /**
     * Call $fn with each index 0..$n-1 and collect the results. A non-positive
     * receiver yields an empty list.
     *
     * @param callable(int): mixed $fn
     * @return list<mixed>
     */
    public function times(callable $fn): array
    {
        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = $fn($i);
        }
        return $out;
    }

    /** The ascending sequence of integers from the receiver up to $end (inclusive). */
    public function upTo(int $end): Sequence<int>
    {
        if ($end < $n) {
            return new Sequence<int>();
        }
        return new Sequence<int>(range($n, $end));
    }

    /** The descending sequence of integers from the receiver down to $end (inclusive). */
    public function downTo(int $end): Sequence<int>
    {
        if ($end > $n) {
            return new Sequence<int>();
        }
        return new Sequence<int>(range($n, $end));
    }

    /** The receiver raised to the power $exponent. */
    public function pow(int $exponent): int
    {
        return (int) ($n ** $exponent);
    }

    /** The greatest common divisor of the receiver and $other (always >= 0). */
    public function gcd(int $other): int
    {
        $a = $n < 0 ? -$n : $n;
        $b = $other < 0 ? -$other : $other;
        while ($b !== 0) {
            [$a, $b] = [$b, $a % $b];
        }
        return $a;
    }

    /** A primality test for the receiver (values < 2 are not prime). */
    public function isPrime(): bool
    {
        if ($n < 2) {
            return false;
        }
        if ($n % 2 === 0) {
            return $n === 2;
        }
        for ($d = 3; $d * $d <= $n; $d += 2) {
            if ($n % $d === 0) {
                return false;
            }
        }
        return true;
    }
}
