<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Support;

module BetterPractice\NineLine;

use RuntimeException;

/**
 * The outcome of an operation that may fail: either `Ok(T)` or `Err(E)`.
 *
 * A value-semantic alternative to exceptions for expected failures, carrying a
 * typed success value and a typed error value:
 *
 *     function parsePort(string $s): Result<int, string> {
 *         return ctype_digit($s)
 *             ? NL:>Result<int, string>::ok((int) $s)
 *             : NL:>Result<int, string>::err("not a number: $s");
 *     }
 *
 *     parsePort("8080")->unwrapOr(80);   // 8080
 *     parsePort("nope")->unwrapOr(80);   // 80
 *
 * Both `T` (the ok value) and `E` (the error value) are enforced at their
 * constructors and where they flow out. Combinators that return "a Result of the
 * same T and E" are typed `static` (the late-static-bound instantiation).
 *
 * @template T
 * @template E
 */
struct Result<T, E>
{
    /**
     * @param bool  $ok    true for Ok, false for Err
     * @param mixed $value the T when Ok; null otherwise
     * @param mixed $error the E when Err; null otherwise
     */
    private function __construct(
        private bool $ok,
        private mixed $value,
        private mixed $error,
    ) {}

    /** A successful result carrying $value. */
    public static function ok(T $value): static
    {
        return new self(true, $value, null);
    }

    /** A failed result carrying $error. */
    public static function err(E $error): static
    {
        return new self(false, null, $error);
    }

    public function isOk(): bool
    {
        return $this->ok;
    }

    public function isErr(): bool
    {
        return !$this->ok;
    }

    /**
     * The ok value.
     *
     * @throws RuntimeException when the Result is an Err.
     */
    public function unwrap(): T
    {
        if (!$this->ok) {
            throw new RuntimeException('Called unwrap() on an Err');
        }
        return $this->value;
    }

    /** The ok value, or $default when this is an Err. */
    public function unwrapOr(T $default): T
    {
        return $this->ok ? $this->value : $default;
    }

    /**
     * The error value.
     *
     * @throws RuntimeException when the Result is Ok.
     */
    public function unwrapErr(): E
    {
        if ($this->ok) {
            throw new RuntimeException('Called unwrapErr() on an Ok');
        }
        return $this->error;
    }

    /**
     * Map the ok value through $fn (types preserved); an Err passes through.
     *
     * @param callable(T): T $fn
     */
    public function map(callable $fn): static
    {
        return $this->ok ? self::ok($fn($this->value)) : $this;
    }

    /**
     * Map the error value through $fn (types preserved); an Ok passes through.
     *
     * @param callable(E): E $fn
     */
    public function mapErr(callable $fn): static
    {
        return $this->ok ? $this : self::err($fn($this->error));
    }

    /**
     * Run $onOk when Ok or $onErr when Err, returning whatever they return.
     *
     * @param callable(T): mixed $onOk
     * @param callable(E): mixed $onErr
     */
    public function match(callable $onOk, callable $onErr): mixed
    {
        return $this->ok ? $onOk($this->value) : $onErr($this->error);
    }
}
