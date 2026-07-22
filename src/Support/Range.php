<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Support;

module BetterPractice\NineLine;

use Countable;
use InvalidArgumentException;
use Iterator;

/**
 * A half-open integer range `[start, end)` with a step, implemented as a
 * value-semantic struct that satisfies the (colored) `Iterator` contract.
 *
 *     foreach (new Range(0, 5) as $i) { ... }        // 0 1 2 3 4
 *     foreach (new Range(10, 0, -2) as $i) { ... }   // 10 8 6 4 2
 *
 * `Iterator::next()` and `rewind()` are mutating requirements; `foreach` takes
 * the **value route** over a struct iterator — it copies the range into its own
 * loop state and advances that copy — so iterating a Range never consumes the
 * caller's value, and the same Range can be iterated again with the same result:
 *
 *     $r = new Range(0, 3);
 *     foreach ($r as $i) { ... }   // 0 1 2
 *     foreach ($r as $i) { ... }   // 0 1 2 again — $r was never mutated
 */
struct Range implements Iterator, Countable
{
    /** The cursor; the readonly bounds below never change. */
    private int $position;

    public function __construct(
        public readonly int $start,
        public readonly int $end,
        public readonly int $step = 1,
    ) {
        if ($step === 0) {
            throw new InvalidArgumentException('Range step must not be zero');
        }
        $this->position = $start;
    }

    /** Narrows Iterator::current(): mixed — a Range always yields int. */
    public function current(): int
    {
        return $this->position;
    }

    /** Narrows Iterator::key(): mixed — the 0-based iteration index. */
    public function key(): int
    {
        return intdiv($this->position - $this->start, $this->step);
    }

    public function next() mutating: void
    {
        $this->position += $this->step;
    }

    public function rewind() mutating: void
    {
        $this->position = $this->start;
    }

    public function valid(): bool
    {
        return $this->step > 0
            ? $this->position < $this->end
            : $this->position > $this->end;
    }

    /** The number of integers the range yields. */
    public function count(): int
    {
        $span = $this->end - $this->start;
        $sign = $this->step > 0 ? 1 : -1;
        if ($span * $sign <= 0) {
            return 0;
        }
        return intdiv(abs($span) + abs($this->step) - 1, abs($this->step));
    }

    /** Whether $n is one of the values the range yields. */
    public function contains(int $n): bool
    {
        if ($this->step > 0) {
            if ($n < $this->start || $n >= $this->end) {
                return false;
            }
        } elseif ($n > $this->start || $n <= $this->end) {
            return false;
        }
        return ($n - $this->start) % $this->step === 0;
    }

    /**
     * Collect the range into a plain list. Iterates a private copy (foreach's
     * value route), so the receiver is untouched.
     *
     * @return list<int>
     */
    public function toArray(): array
    {
        $out = [];
        foreach ($this as $value) {
            $out[] = $value;
        }
        return $out;
    }
}
