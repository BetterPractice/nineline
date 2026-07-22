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
use Traversable;

/**
 * A generic, string-keyed dictionary with value semantics.
 *
 * `Map<V>` is a `struct`: assigning or passing it copies it (copy-on-write), so a
 * snapshot is never disturbed by a later mutation of the original. Keys are
 * always strings; the value type `V` is enforced at runtime.
 *
 *     $m = new Map<int>();
 *     $m->set("a", 1);
 *     $m->get("a");                 // 1
 *     $m->set("b", "oops");         // TypeError: Argument #2 must be of type int
 *
 * Methods that return "a map of the same value type" are typed `static` (the
 * late-static-bound instantiation, e.g. `Map<int>`); `mapValues<W>()` is a
 * generic method that transforms values to a new type `Map<W>`.
 *
 * @template V
 */
struct Map<V> implements Countable, IteratorAggregate
{
    /** @var array<string, V> */
    private array $entries;

    /**
     * @param iterable<string, V> $entries
     */
    public function __construct(iterable $entries = [])
    {
        // Route every pair through the typed set() so the value type `V` is
        // enforced at construction — otherwise a wrongly-typed value would sit
        // undetected until it failed a `?V` return check on a later get(). This
        // also normalises keys to strings, as the rest of the API assumes.
        $this->entries = [];
        foreach ($entries as $key => $value) {
            $this->set((string) $key, $value);
        }
    }

    // ---- Mutating API -------------------------------------------------------

    /** Set (or replace) the value at $key. */
    public function set(string $key, V $value) mutating: void
    {
        $this->entries[$key] = $value;
    }

    /**
     * Remove the entry at $key. Returns true if an entry was removed.
     */
    public function remove(string $key) mutating: bool
    {
        if (!array_key_exists($key, $this->entries)) {
            return false;
        }
        unset($this->entries[$key]);
        return true;
    }

    /** Drop every entry. */
    public function clear() mutating: void
    {
        $this->entries = [];
    }

    // ---- Read API -----------------------------------------------------------

    /** The value at $key, or null when absent. */
    public function get(string $key): ?V
    {
        return $this->entries[$key] ?? null;
    }

    /** The value at $key, or $default when absent. */
    public function getOr(string $key, V $default): V
    {
        return $this->entries[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->entries);
    }

    public function count(): int
    {
        return count($this->entries);
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    /** The keys as a strongly-typed sequence. */
    public function keys(): Sequence<string>
    {
        return new Sequence<string>(array_keys($this->entries));
    }

    /**
     * The values as a plain list. (A `Sequence<V>` return is not expressible: a
     * type parameter may not be used as a generic argument in this version.)
     *
     * @return list<V>
     */
    public function values(): array
    {
        return array_values($this->entries);
    }

    /** @return array<string, V> */
    public function toArray(): array
    {
        return $this->entries;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->entries);
    }

    // ---- Functional API (returns a new map; value type preserved) -----------

    /**
     * Run $fn for each key/value pair, for side effects.
     *
     * @param callable(string, V): void $fn
     */
    public function each(callable $fn): void
    {
        foreach ($this->entries as $key => $value) {
            $fn($key, $value);
        }
    }

    /**
     * A new `Map<W>` with $fn applied to every value, keys preserved — a
     * type-changing map (generic method), so the target value type is given
     * explicitly: `$prices->mapValues<string>(fn(int $c) => "$c cents")`. The
     * output type `W` is enforced as each value is set.
     *
     * @template W
     * @param callable(V): W $fn
     */
    public function mapValues<W>(callable $fn): Map<W>
    {
        $out = new Map<W>();
        foreach ($this->entries as $key => $value) {
            $out->set($key, $fn($value));
        }
        return $out;
    }

    /**
     * A new map keeping only pairs for which $predicate returns true.
     *
     * @param callable(string, V): bool $predicate
     */
    public function filter(callable $predicate): static
    {
        $out = new self();
        foreach ($this->entries as $key => $value) {
            if ($predicate($key, $value)) {
                $out->set($key, $value);
            }
        }
        return $out;
    }
}
