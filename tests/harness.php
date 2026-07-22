<?php

/**
 * A tiny zero-dependency assertion harness. Case files under tests/cases/ are
 * plain PHP scripts (each free to `use module BetterPractice\NineLine as NL;`)
 * that call these globals; tests/run.php includes them and reports a summary.
 */

declare(strict_types=1);

final class NLTest
{
    public static int $passed = 0;
    public static int $failed = 0;
    public static int $skipped = 0;
    /** @var list<string> */
    public static array $failures = [];
}

function nl_group(string $name): void
{
    echo "\n# {$name}\n";
}

function nl_ok(bool $condition, string $message): void
{
    if ($condition) {
        NLTest::$passed++;
        echo "  ok   {$message}\n";
    } else {
        NLTest::$failed++;
        NLTest::$failures[] = $message;
        echo "  FAIL {$message}\n";
    }
}

function nl_eq(mixed $expected, mixed $actual, string $message): void
{
    if ($expected === $actual) {
        NLTest::$passed++;
        echo "  ok   {$message}\n";
    } else {
        NLTest::$failed++;
        $detail = sprintf('expected %s, got %s', var_export($expected, true), var_export($actual, true));
        NLTest::$failures[] = "{$message} — {$detail}";
        echo "  FAIL {$message} — {$detail}\n";
    }
}

/** @param class-string<\Throwable> $type */
function nl_throws(string $type, callable $fn, string $message): void
{
    try {
        $fn();
    } catch (\Throwable $e) {
        nl_ok($e instanceof $type, "{$message} (threw " . $e::class . ')');
        return;
    }
    nl_ok(false, "{$message} (no exception thrown)");
}

function nl_skip(string $message): void
{
    NLTest::$skipped++;
    echo "  skip {$message}\n";
}

function nl_summary(): int
{
    echo "\n" . str_repeat('-', 60) . "\n";
    $status = NLTest::$failed === 0 ? 'PASS' : 'FAIL';
    printf(
        "%s: %d passed, %d failed, %d skipped\n",
        $status,
        NLTest::$passed,
        NLTest::$failed,
        NLTest::$skipped,
    );
    return NLTest::$failed === 0 ? 0 : 1;
}
