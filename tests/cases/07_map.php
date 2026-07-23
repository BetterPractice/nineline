<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Map<V>');

// construction + basic reads
$m = new NL:>Map<int>(['a' => 1, 'b' => 2]);
nl_eq(2, $m->count(), 'count');
nl_eq(1, $m->get('a'), 'get existing');
nl_eq(null, $m->get('z'), 'get missing is null');
nl_eq(7, $m->getOr('z', 7), 'getOr default');
nl_eq(true, $m->has('a'), 'has true');
nl_eq(false, $m->has('z'), 'has false');
nl_eq(['a', 'b'], $m->keys()->toArray(), 'keys returns a Sequence');
nl_eq(true, $m->keys() instanceof NL:>Sequence<string>, 'keys is a Sequence<string>');
nl_eq([1, 2], $m->values()->toArray(), 'values returns a Sequence');
nl_eq(true, $m->values() instanceof NL:>Sequence<int>, 'values is a Sequence<V>');
nl_eq(['a' => 1, 'b' => 2], $m->toArray(), 'toArray');
nl_eq(false, $m->isEmpty(), 'isEmpty false');
nl_eq(true, (new NL:>Map<int>())->isEmpty(), 'isEmpty true');

// mutating set / remove
$m->set('c', 3);
nl_eq(3, $m->get('c'), 'set adds');
nl_eq(true, $m->remove('c'), 'remove returns true');
nl_eq(false, $m->remove('c'), 'remove absent returns false');
nl_eq(false, $m->has('c'), 'removed key gone');

// value semantics: a copy is independent
$copy = $m;
$copy->set('d', 4);
nl_eq(false, $m->has('d'), 'original untouched by copy mutation');
nl_eq(true, $copy->has('d'), 'copy has its own value');

// functional API (returns new maps; value type preserved)
$prices = new NL:>Map<int>(['apple' => 3, 'pear' => 5, 'plum' => 2]);
nl_eq(
    ['apple' => 6, 'pear' => 10, 'plum' => 4],
    $prices->mapValues<int>(new NL:>Func<int, int>(fn(int $v): int => $v * 2))->toArray(),
    'mapValues<int> same-type with typed Func<V, W>',
);
// type-changing: Map<int> -> Map<string>
$labelled = $prices->mapValues<string>(new NL:>Func<int, string>(fn(int $v): string => "\${$v}"));
nl_eq(['apple' => '$3', 'pear' => '$5', 'plum' => '$2'], $labelled->toArray(), 'mapValues<string> changes value type');
nl_eq(true, $labelled instanceof NL:>Map<string>, 'mapValues<string> yields Map<string>');
// W is fixed by the explicit type argument: a Func returning something else is rejected
nl_throws(\TypeError::class, function () use ($prices) {
    $prices->mapValues<string>(new NL:>Func<int, int>(fn(int $v): int => $v));
}, 'mapValues<W> rejects a Func whose return type is not W');
nl_throws(\TypeError::class, function () use ($prices) {
    $prices->mapValues<string>(new NL:>Func<string, string>(fn(string $v): string => $v));
}, 'mapValues<W> rejects a Func of the wrong input type');
// filter takes a typed Func<string, V, bool>
$expensive = new NL:>Func<string, int, bool>(fn(string $k, int $v): bool => $v > 3);
nl_eq(['pear' => 5], $prices->filter($expensive)->toArray(), 'filter with typed Func<string, V, bool>');
nl_eq(['apple' => 3, 'pear' => 5, 'plum' => 2], $prices->toArray(), 'functional ops do not mutate receiver');
nl_throws(\TypeError::class, function () use ($prices) {
    $wrong = new NL:>Func<string, string, bool>(fn(string $k, string $v): bool => true);
    $prices->filter($wrong);
}, 'filter rejects a Func of the wrong value type');

// each takes a typed Action<string, V>
$seen = [];
$prices->each(new NL:>Action<string, int>(
    function (string $k, int $v) use (&$seen): void { $seen[$k] = $v; }
));
nl_eq(['apple' => 3, 'pear' => 5, 'plum' => 2], $seen, 'each with typed Action<string, V>');
nl_throws(\TypeError::class, function () use ($prices) {
    $wrong = new NL:>Action<string, string>(function (string $k, string $v): void {});
    $prices->each($wrong);
}, 'each rejects an Action of the wrong value type');

// foreach via IteratorAggregate
$collected = [];
foreach ($prices as $key => $value) {
    $collected[$key] = $value;
}
nl_eq(['apple' => 3, 'pear' => 5, 'plum' => 2], $collected, 'foreach yields key => value');

// value type enforcement
nl_throws(\TypeError::class, fn() => $m->set('x', 'nope'), 'set enforces V');
nl_throws(\TypeError::class, fn() => new NL:>Map<int>(['k' => 'v']), 'constructor enforces V (fails fast)');

// keys are normalised to strings; instanceof with generic args
$n = new NL:>Map<string>();
$n->set('k', 'v');
nl_eq(true, $n instanceof NL:>Map<string>, 'instanceof Map<string>');
nl_eq(false, $n instanceof NL:>Map<int>, 'not instanceof Map<int>');
