<?php

declare(strict_types=1);

/**
 * NineLine — a PHP standard library built on the PHP 9 roadmap features.
 *
 * This file declares the `NineLine` module. Its fully-qualified module name is
 * `BetterPractice\NineLine` (the module `NineLine` declared inside namespace
 * `BetterPractice`), so consumers import it with:
 *
 *     use module BetterPractice\NineLine as NL;
 *
 * and reach members through the module-resolution operator, e.g. `NL:>Sequence`,
 * `new NL:>Map<int>()`. The single `use module` line also activates every
 * `export extension` below, so scalar extension methods like `"hi"->length()`
 * become available with no separate `use extension`.
 */

namespace BetterPractice;

module NineLine {
    // Collections — generic, value-semantic structs.
    export BetterPractice\NineLine\Collections\Sequence;
    export BetterPractice\NineLine\Collections\Map;

    // Support — value-struct monads, a mutating iterator, and typed delegates.
    export BetterPractice\NineLine\Support\Option;
    export BetterPractice\NineLine\Support\Result;
    export BetterPractice\NineLine\Support\Range;
    export BetterPractice\NineLine\Support\Func;
    export BetterPractice\NineLine\Support\Action;
    // CallableSignature is an internal helper of Func/Action — intentionally not exported.
    // ComparisonResult is a plain (non-member) enum: a module-qualified name can't
    // appear inside a generic argument list, and it exists to be a Func type argument.

    // Scalar extension methods — activated by `use module`.
    export extension BetterPractice\NineLine\Extensions\StringExtensions;
    export extension BetterPractice\NineLine\Extensions\StringMbExtensions;   // methods present only when ext-mbstring is loaded
    export extension BetterPractice\NineLine\Extensions\IntExtensions;
    export extension BetterPractice\NineLine\Extensions\ArrayExtensions;
}
