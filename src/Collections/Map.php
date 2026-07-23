<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Collections;

module BetterPractice\NineLine;

use ArrayIterator;
use BetterPractice\NineLine\Support\Action;
use BetterPractice\NineLine\Support\Func;
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

    /** The values as a strongly-typed sequence (mirrors {@see keys()}). */
    public function values(): Sequence<V>
    {
        return new Sequence<V>(array_values($this->entries));
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
     * Run a typed `Action<string, V>` for each key/value pair, for side effects.
     * The action's signature is validated when it is constructed, and its
     * argument types are checked against this map's `V` at the call boundary.
     */
    public function each(Action<string, V> $action): void
    {
        foreach ($this->entries as $key => $value) {
            $action->invoke($key, $value);
        }
    }

    /**
     * A new `Map<W>` with a typed `Func<V, W>` applied to every value, keys
     * preserved — a type-changing map (generic method), so the target value type
     * is given explicitly:
     *
     *     $prices->mapValues<string>(new Func<int, string>(
     *         fn(int $c): string => "$c cents",
     *     ));
     *
     * The delegate's signature is validated when it is constructed, and its
     * argument type is checked against this map's `V` at the call boundary.
     *
     * This maps values only. A key+value entry map — the natural
     * `Func<Pair<string, V>, Pair<string, W>>` — is not expressible: a type
     * parameter must be a *direct* generic argument, never nested inside another
     * one (see the README).
     *
     * @template W
     */
    public function mapValues<W>(Func<V, W> $fn): Map<W>
    {
        $out = new Map<W>();
        foreach ($this->entries as $key => $value) {
            $out->set($key, $fn->invoke($value));
        }
        return $out;
    }

    /**
     * A new map keeping only the pairs for which a typed
     * `Func<string, V, bool>` predicate returns true. Passing a predicate built
     * for a different value type is a `TypeError` at the call boundary.
     */
    public function filter(Func<string, V, bool> $predicate): static
    {
        $out = new self();
        foreach ($this->entries as $key => $value) {
            if ($predicate->invoke($key, $value)) {
                $out->set($key, $value);
            }
        }
        return $out;
    }
}
