<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this type.
 */

namespace BetterPractice\NineLine\Support;

/**
 * The result of comparing two values for ordering.
 *
 * Returned by a sort comparator (`Func<T, T, ComparisonResult>`) describing where
 * the first value sorts relative to the second. Backed by the integer values
 * `usort` expects (`-1` / `0` / `1`), so a `ComparisonResult` maps straight onto
 * the engine's comparison protocol.
 *
 * Unlike the collections and delegates, this enum is **not** a module member: a
 * module-qualified name (`NL:>ComparisonResult`) cannot appear inside a generic
 * argument list, and this type's whole job is to be a `Func` type argument. It is
 * an ordinary import — `use BetterPractice\NineLine\Support\ComparisonResult;`.
 *
 *     use BetterPractice\NineLine\Support\ComparisonResult;
 *
 *     $byLength = new NL:>Func<string, string, ComparisonResult>(
 *         fn(string $a, string $b): ComparisonResult =>
 *             ComparisonResult::of(strlen($a), strlen($b))
 *     );
 *     $words->sort($byLength);
 */
enum ComparisonResult: int
{
    /** The first value sorts before the second (ascending order: first is smaller). */
    case Ascending = -1;

    /** The two values sort the same. */
    case Equal = 0;

    /** The first value sorts after the second (ascending order: first is larger). */
    case Descending = 1;

    /**
     * The comparison of two values by PHP's standard ordering (`<=>`): a natural
     * ascending comparator is `fn($a, $b) => ComparisonResult::of($a, $b)`.
     */
    public static function of(mixed $a, mixed $b): self
    {
        $ordering = $a <=> $b;
        return $ordering < 0
            ? self::Ascending
            : ($ordering > 0 ? self::Descending : self::Equal);
    }

    /** The opposite ordering (`Ascending` ↔ `Descending`; `Equal` unchanged). */
    public function reversed(): self
    {
        return match ($this) {
            self::Ascending => self::Descending,
            self::Descending => self::Ascending,
            self::Equal => self::Equal,
        };
    }
}
