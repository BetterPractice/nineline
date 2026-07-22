<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Support;

module BetterPractice\NineLine;

use RuntimeException;

/**
 * An optional value: either `Some(T)` or `None`.
 *
 * A value-semantic alternative to returning `null`, with a small combinator API
 * that makes "may be absent" explicit in the type:
 *
 *     $found = NL:>Option<int>::some(42);
 *     $found->map(fn(int $n) => $n + 1)->getOr(0);   // 43
 *     NL:>Option<int>::none()->getOr(0);             // 0
 *
 * `T` is enforced when constructing a `Some` and when a value flows out of the
 * combinators. Being a `struct`, an Option copies by value like any other.
 *
 * Combinators that return "an Option of the same T" are typed `static` (the
 * late-static-bound instantiation, e.g. `Option<int>`); transforming to a
 * different element type is not expressible in this generics version, so `map()`
 * preserves `T`.
 *
 * @template T
 */
struct Option<T>
{
    /**
     * @param bool  $present true for Some, false for None
     * @param mixed $value   the wrapped T when present; null otherwise
     */
    private function __construct(
        private bool $present,
        private mixed $value,
    ) {}

    /** A present value. */
    public static function some(T $value): static
    {
        return new self(true, $value);
    }

    /** The absence of a value. */
    public static function none(): static
    {
        return new self(false, null);
    }

    /** `some($value)` when $value is non-null, otherwise `none()`. */
    public static function fromNullable(?T $value): static
    {
        return $value === null ? new self(false, null) : new self(true, $value);
    }

    public function isSome(): bool
    {
        return $this->present;
    }

    public function isNone(): bool
    {
        return !$this->present;
    }

    /**
     * The contained value.
     *
     * @throws RuntimeException when the Option is None.
     */
    public function get(): T
    {
        if (!$this->present) {
            throw new RuntimeException('Called get() on a None');
        }
        return $this->value;
    }

    /** The contained value, or $default when None. */
    public function getOr(T $default): T
    {
        return $this->present ? $this->value : $default;
    }

    /**
     * The contained value, or the result of $fallback when None.
     *
     * @param callable(): T $fallback
     */
    public function getOrElse(callable $fallback): T
    {
        return $this->present ? $this->value : $fallback();
    }

    /** The contained value, or null when None. */
    public function toNullable(): ?T
    {
        return $this->present ? $this->value : null;
    }

    /**
     * Map the contained value through $fn (element type preserved), or pass the
     * None through unchanged.
     *
     * @param callable(T): T $fn
     */
    public function map(callable $fn): static
    {
        return $this->present ? self::some($fn($this->value)) : $this;
    }

    /**
     * Keep a Some only when $predicate holds; otherwise become None.
     *
     * @param callable(T): bool $predicate
     */
    public function filter(callable $predicate): static
    {
        if ($this->present && !$predicate($this->value)) {
            return self::none();
        }
        return $this;
    }

    /**
     * Run $fn with the value when present (for side effects).
     *
     * @param callable(T): void $fn
     */
    public function ifSome(callable $fn): void
    {
        if ($this->present) {
            $fn($this->value);
        }
    }
}
