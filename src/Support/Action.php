<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Support;

module BetterPractice\NineLine;

use Closure;
use ReflectionClass;

/**
 * A strongly-typed void delegate — PHP's answer to C#'s `Action<...>`.
 *
 * The type parameters are a variadic pack of argument types: `Action<string, int>`
 * wraps a callable `(string, int): void`. Because a pack requires at least one
 * element, `Action` models side effects of one or more arguments.
 *
 *     $log = new NL:>Action<string>(fn(string $line) => error_log($line));
 *     $log->invoke("hello");
 *     $log("world");   // via __invoke
 *
 * As with {@see Func}, the wrapped callable's signature is validated against the
 * type arguments at construction (see {@see CallableSignature}); the return value
 * is ignored.
 *
 * @template TArgs
 */
struct Action<...TArgs>
{
    private Closure $fn;

    public function __construct(callable $fn)
    {
        $closure = $fn(...);
        $types = (new ReflectionClass($this))->getGenericTypeArguments();
        CallableSignature::validate($closure, $types, null);
        $this->fn = $closure;
    }

    public function invoke(mixed ...$args): void
    {
        ($this->fn)(...$args);
    }

    public function __invoke(mixed ...$args): void
    {
        ($this->fn)(...$args);
    }
}
