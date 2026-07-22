<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Func<...TArgs, TReturn> and Action<...TArgs>');

// --- Func: construction-time signature validation ---
$add = new NL:>Func<int, int, int>(fn(int $a, int $b): int => $a + $b);
nl_eq(5, $add->invoke(2, 3), 'Func invoke');
nl_eq(9, $add(4, 5), 'Func __invoke');
nl_eq(true, is_callable($add), 'Func instance is callable');

// FCC yields a typed Closure that still enforces
$fcc = $add->invoke(...);
nl_eq(13, $fcc(6, 7), 'Func FCC invoke');

// return value enforced natively as TReturn
$lying = new NL:>Func<int, string>(fn(int $n): string => "n=$n");
nl_eq('n=8', $lying->invoke(8), 'Func single-arg invoke');

// construction rejects incompatible callables
nl_throws(\TypeError::class, fn() => new NL:>Func<int, int, int>(fn(int $a, string $b): int => 0), 'rejects wrong param type');
nl_throws(\TypeError::class, fn() => new NL:>Func<int, string>(fn(int $n): int => $n), 'rejects wrong return type');
nl_throws(\TypeError::class, fn() => new NL:>Func<int, int, int>(fn(int $a): int => $a), 'rejects wrong arity');

// an untyped closure is accepted (arity still checked); deferred to invoke-time
$untyped = new NL:>Func<int, int>(fn($n) => $n * 10);
nl_eq(30, $untyped->invoke(3), 'untyped closure accepted, invokes');

// a Func is a drop-in callable
nl_eq([2, 4, 6], array_map(new NL:>Func<int, int>(fn(int $n): int => $n * 2), [1, 2, 3]), 'Func works with array_map');

// --- Action: void delegate ---
$seen = [];
$record = new NL:>Action<string, int>(function (string $s, int $n) use (&$seen): void { $seen[] = "$s=$n"; });
$record->invoke('a', 1);
$record('b', 2);
nl_eq(['a=1', 'b=2'], $seen, 'Action invoke + __invoke');
nl_throws(\TypeError::class, fn() => new NL:>Action<string>(function (int $n): void {}), 'Action rejects wrong param type');
