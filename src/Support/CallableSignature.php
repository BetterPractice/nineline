<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — an internal helper of the module.
 */

namespace BetterPractice\NineLine\Support;

module BetterPractice\NineLine;

use Closure;
use ReflectionFunction;
use ReflectionType;
use TypeError;

/**
 * Validates a closure's declared signature against a required parameter/return
 * shape. Used by {@see Func} and {@see Action} to check — at construction — that
 * the wrapped callable is compatible with the delegate's type arguments.
 *
 * This is a module member but is **not exported**: it is an implementation detail
 * of the delegate types, not part of NineLine's public surface.
 *
 * The check is structural and exact (a declared type must match the required type
 * name). It deliberately does not implement full variance (contravariant
 * parameters, covariant returns); an *untyped* parameter or return is accepted
 * and left to invoke-time enforcement.
 */
final class CallableSignature
{
    /**
     * @param list<string> $paramTypes required parameter type names, in order
     * @param ?string      $returnType required return type name, or null to skip
     *
     * @throws TypeError when the callable's arity or declared types are incompatible
     */
    public static function validate(Closure $fn, array $paramTypes, ?string $returnType): void
    {
        $reflection = new ReflectionFunction($fn);
        $wanted = count($paramTypes);
        $required = $reflection->getNumberOfRequiredParameters();
        $max = $reflection->isVariadic() ? PHP_INT_MAX : $reflection->getNumberOfParameters();

        if ($wanted < $required || $wanted > $max) {
            throw new TypeError(sprintf(
                'callable must accept %d argument(s); it accepts %d..%s',
                $wanted,
                $required,
                $reflection->isVariadic() ? 'unlimited' : (string) $reflection->getNumberOfParameters(),
            ));
        }

        $parameters = $reflection->getParameters();
        foreach ($paramTypes as $index => $want) {
            if (isset($parameters[$index])) {
                self::assignable($parameters[$index]->getType(), $want, 'parameter #' . ($index + 1));
            }
        }

        if ($returnType !== null) {
            self::assignable($reflection->getReturnType(), $returnType, 'return value');
        }
    }

    private static function assignable(?ReflectionType $have, string $want, string $what): void
    {
        if ($have === null) {
            return; // untyped: cannot verify here — invoke-time enforcement still applies
        }
        if (strcasecmp(ltrim((string) $have, '?\\'), ltrim($want, '\\')) !== 0) {
            throw new TypeError(sprintf('%s type (%s) is not %s', $what, (string) $have, $want));
        }
    }
}
