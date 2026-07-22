<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Collections;

module BetterPractice\NineLine;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfRangeException;
use Traversable;

/**
 * A generic, ordered, value-semantic list.
 *
 * `Sequence<T>` is a `struct`, so it has **value semantics**: assigning it,
 * passing it, or storing it copies it (copy-on-write under the hood). A mutating
 * method updates the caller's own value in place, but any prior copy is
 * untouched:
 *
 *     $a = new Sequence<int>();
 *     $a->push(1);
 *     $b = $a;          // a copy
 *     $b->push(2);      // $a is still [1], $b is [1, 2]
 *
 * The element type `T` is enforced at runtime by the ordinary typed-parameter
 * machinery: `Sequence<int>::push()` rejects a string with a `TypeError`.
 *
 * Methods that return "a sequence of the same element type" are typed `static`
 * (the late-static-bound instantiation, e.g. `Sequence<int>`); `self` would name
 * the bare template. To transform the element type, `map<U>()` is a generic
 * method returning `Sequence<U>`.
 *
 * @template T
 */
struct Sequence<T> implements Countable, IteratorAggregate
{
    /** @var list<T> */
    private array $items;

    /**
     * @param iterable<T> $items
     */
    public function __construct(iterable $items = [])
    {
        // Route every element through the typed push() so the element type `T`
        // is enforced at construction. Without this, a wrongly-typed element
        // would sit undetected until it failed a `?T` return check on a later
        // access (get/first/pop) — a bug far from its cause. Appending also
        // yields the 0-indexed list the rest of the API assumes.
        $this->items = [];
        foreach ($items as $item) {
            $this->push($item);
        }
    }

    // ---- Mutating API (updates the caller's value in place) -----------------

    /** Append an item to the end. */
    public function push(T $item) mutating: void
    {
        $this->items[] = $item;
    }

    /** Remove and return the last item, or null when empty. */
    public function pop() mutating: ?T
    {
        if ($this->items === []) {
            return null;
        }
        // Array builtins that take the array by reference cannot receive a
        // struct property directly (the interior-reference ban), so operate on
        // a local copy and assign it back.
        $items = $this->items;
        $item = array_pop($items);
        $this->items = $items;
        return $item;
    }

    /** Prepend an item to the front. */
    public function unshift(T $item) mutating: void
    {
        $items = $this->items;
        array_unshift($items, $item);
        $this->items = $items;
    }

    /** Remove and return the first item, or null when empty. */
    public function shift() mutating: ?T
    {
        if ($this->items === []) {
            return null;
        }
        $items = $this->items;
        $item = array_shift($items);
        $this->items = $items;
        return $item;
    }

    /**
     * Replace the item at $index. $index may equal count() to append.
     *
     * @throws OutOfRangeException on a negative or gap-creating index.
     */
    public function set(int $index, T $item) mutating: void
    {
        if ($index < 0 || $index > count($this->items)) {
            throw new OutOfRangeException("Index {$index} is out of range");
        }
        $this->items[$index] = $item;
    }

    /** Remove and return the item at $index, or null when absent. */
    public function removeAt(int $index) mutating: ?T
    {
        if (!array_key_exists($index, $this->items)) {
            return null;
        }
        $item = $this->items[$index];
        $items = $this->items;
        array_splice($items, $index, 1);
        $this->items = $items;
        return $item;
    }

    /**
     * Append every item from another sequence (or any iterable). Each item is
     * routed through push(), so the element type `T` is still enforced.
     *
     * A param type of `Sequence<T>` is not expressible in this generics version
     * (a type parameter may not be used as a generic argument), so this accepts
     * `iterable` and relies on push()'s runtime check for type safety.
     *
     * @param iterable<T> $other
     */
    public function append(iterable $other) mutating: void
    {
        foreach ($other as $item) {
            $this->push($item);
        }
    }

    /** Sort in place using $comparator (defaults to standard comparison). */
    public function sort(?callable $comparator = null) mutating: void
    {
        $items = $this->items;
        if ($comparator === null) {
            sort($items);
        } else {
            usort($items, $comparator);
        }
        $this->items = $items;
    }

    /** Reverse the order in place. */
    public function reverse() mutating: void
    {
        $this->items = array_reverse($this->items);
    }

    /** Drop every item. */
    public function clear() mutating: void
    {
        $this->items = [];
    }

    // ---- Read API -----------------------------------------------------------

    /** The item at $index, or null when out of range. */
    public function get(int $index): ?T
    {
        return $this->items[$index] ?? null;
    }

    /** The first item, or null when empty. */
    public function first(): ?T
    {
        return $this->items[0] ?? null;
    }

    /** The last item, or null when empty. */
    public function last(): ?T
    {
        if ($this->items === []) {
            return null;
        }
        return $this->items[array_key_last($this->items)];
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /** Strict (===) membership test. */
    public function contains(T $item): bool
    {
        return in_array($item, $this->items, true);
    }

    /** The index of the first strict match, or null. */
    public function indexOf(T $item): ?int
    {
        $index = array_search($item, $this->items, true);
        return $index === false ? null : $index;
    }

    /** @return list<T> a plain array copy of the items. */
    public function toArray(): array
    {
        return $this->items;
    }

    public function getIterator(): Traversable
    {
        // A fresh iterator over a snapshot: iterating never mutates the value.
        return new ArrayIterator($this->items);
    }

    // ---- Functional API (returns a new sequence; element type preserved) ----

    /**
     * A new sequence containing only items for which $predicate returns true.
     *
     * @param callable(T): bool $predicate
     */
    public function filter(callable $predicate): static
    {
        $out = new self();
        foreach ($this->items as $item) {
            if ($predicate($item)) {
                $out->push($item);
            }
        }
        return $out;
    }

    /**
     * A new `Sequence<U>` with $fn applied to every item — a type-changing map
     * (generic method), so the target element type is given explicitly:
     * `$seq->map<string>(fn(int $n) => (string) $n)`. The output type `U` is
     * enforced as each mapped value is collected.
     *
     * @template U
     * @param callable(T): U $fn
     */
    public function map<U>(callable $fn): Sequence<U>
    {
        $out = new Sequence<U>();
        foreach ($this->items as $item) {
            $out->push($fn($item));
        }
        return $out;
    }

    /**
     * Left fold over the items.
     *
     * @param callable(mixed, T): mixed $fn
     */
    public function reduce(callable $fn, mixed $initial = null): mixed
    {
        $accumulator = $initial;
        foreach ($this->items as $item) {
            $accumulator = $fn($accumulator, $item);
        }
        return $accumulator;
    }

    /**
     * Run $fn for each item, for side effects.
     *
     * @param callable(T): void $fn
     */
    public function each(callable $fn): void
    {
        foreach ($this->items as $item) {
            $fn($item);
        }
    }

    /** A new sequence in reverse order (the receiver is untouched). */
    public function reversed(): static
    {
        $out = new self();
        $out->items = array_reverse($this->items);
        return $out;
    }

    // ---- Withers (value-oriented; return a modified copy) -------------------

    /**
     * A copy with $item appended. Because uncolored methods bind `$this` by
     * value, mutating the local `$this` and returning it is the wither idiom —
     * the caller's original is untouched.
     */
    public function withAppended(T $item): static
    {
        $this->items[] = $item;
        return $this;
    }

    /** A copy with the last item removed. */
    public function withoutLast(): static
    {
        array_pop($this->items);
        return $this;
    }
}
