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
 * A strongly-typed function delegate — PHP's answer to C#'s `Func<...>`.
 *
 * The type parameters are a variadic pack of argument types followed by the
 * return type: `Func<int, string, bool>` wraps a callable `(int, string): bool`.
 * Because a pack requires at least one element, `Func` models functions of one
 * or more arguments (a zero-argument supplier is not expressible this way).
 *
 *     $toPrice = new NL:>Func<Order, Price>(fn(Order $o): Price => new Price($o->cents / 100));
 *     $toPrice->invoke($order);   // Price
 *     $toPrice($order);           // Price  (via __invoke)
 *     $f = $toPrice->invoke(...);  // a typed Closure (FCC), still enforcing the signature
 *
 * Two layers of type safety:
 *
 * - **At construction**, the wrapped callable's declared signature is validated
 *   against the type arguments (see {@see CallableSignature}); an incompatible
 *   arity or parameter/return type throws a `TypeError` immediately.
 * - **At invocation**, the return value is enforced natively as `TReturn`, and
 *   the wrapped callable enforces its own (validated) parameter types.
 *
 * @template TArgs
 * @template TReturn
 */
struct Func<...TArgs, TReturn>
{
    private Closure $fn;

    public function __construct(callable $fn)
    {
        $closure = $fn(...);
        // Type arguments arrive flat: [...TArgs, TReturn]. The last is the return.
        $types = (new ReflectionClass($this))->getGenericTypeArguments();
        $returnType = array_pop($types);
        CallableSignature::validate($closure, $types, $returnType);
        $this->fn = $closure;
    }

    /** Invoke the delegate; the result is enforced as TReturn. */
    public function invoke(mixed ...$args): TReturn
    {
        return ($this->fn)(...$args);
    }

    /** Invoke via `$func(...)`; also usable as any `callable`. */
    public function __invoke(mixed ...$args): TReturn
    {
        return ($this->fn)(...$args);
    }
}
