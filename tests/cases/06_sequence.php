<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Sequence<T>');

// construction + basic reads
$s = new NL:>Sequence<int>([3, 1, 2]);
nl_eq(3, $s->count(), 'count');
nl_eq(3, $s->first(), 'first');
nl_eq(2, $s->last(), 'last');
nl_eq(2, $s->get(2), 'get by index');
nl_eq(null, $s->get(9), 'get out of range is null');
nl_eq(false, $s->isEmpty(), 'isEmpty false');
nl_eq(true, (new NL:>Sequence<int>())->isEmpty(), 'isEmpty true');
nl_eq([3, 1, 2], $s->toArray(), 'toArray');
nl_eq(true, $s->contains(2), 'contains');
nl_eq(1, $s->indexOf(1), 'indexOf');
nl_eq(null, $s->indexOf(99), 'indexOf missing is null');

// value semantics: a copy is independent
$s->push(4);
$copy = $s;
$copy->push(5);
nl_eq([3, 1, 2, 4], $s->toArray(), 'original untouched by copy mutation');
nl_eq([3, 1, 2, 4, 5], $copy->toArray(), 'copy has its own value');

// mutating stack/queue ops
$stack = new NL:>Sequence<int>([1, 2, 3]);
nl_eq(3, $stack->pop(), 'pop returns last');
nl_eq([1, 2], $stack->toArray(), 'pop removed it');
$stack->unshift(0);
nl_eq([0, 1, 2], $stack->toArray(), 'unshift prepends');
nl_eq(0, $stack->shift(), 'shift returns first');
nl_eq([1, 2], $stack->toArray(), 'shift removed it');

// set / removeAt
$s2 = new NL:>Sequence<int>([10, 20, 30]);
$s2->set(1, 99);
nl_eq([10, 99, 30], $s2->toArray(), 'set replaces');
nl_eq(99, $s2->removeAt(1), 'removeAt returns removed');
nl_eq([10, 30], $s2->toArray(), 'removeAt spliced');

// sort / reverse in place
$s3 = new NL:>Sequence<int>([3, 1, 2]);
$s3->sort();
nl_eq([1, 2, 3], $s3->toArray(), 'sort natural (no comparator)');
// typed comparator: Func<int, int, NL:>ComparisonResult>
$descending = new NL:>Func<int, int, NL:>ComparisonResult>(
    fn(int $a, int $b): NL:>ComparisonResult => NL:>ComparisonResult::of($b, $a)
);
$s3->sort($descending);
nl_eq([3, 2, 1], $s3->toArray(), 'sort with typed descending comparator');
// ComparisonResult::of + reversed()
nl_eq(NL:>ComparisonResult::Ascending, NL:>ComparisonResult::of(1, 2), 'ComparisonResult::of ascending');
nl_eq(NL:>ComparisonResult::Descending, NL:>ComparisonResult::of(1, 2)->reversed(), 'ComparisonResult reversed');
nl_eq(NL:>ComparisonResult::Equal, NL:>ComparisonResult::of(5, 5), 'ComparisonResult::of equal');
// sort rejects a comparator of the wrong element type
nl_throws(\TypeError::class, function () {
    $bad = new NL:>Func<string, string, NL:>ComparisonResult>(
        fn(string $a, string $b): NL:>ComparisonResult => NL:>ComparisonResult::Equal
    );
    $wrong = new NL:>Sequence<int>([1, 2]);   // mutating sort needs a variable receiver
    $wrong->sort($bad);
}, 'sort rejects Func of wrong element type');
$s3->reverse();
nl_eq([1, 2, 3], $s3->toArray(), 'reverse in place');

// append from any iterable (element type still enforced via push)
$s4 = new NL:>Sequence<int>([1, 2]);
$s4->append([3, 4]);
nl_eq([1, 2, 3, 4], $s4->toArray(), 'append from array');

// appendSequence: typed, checked at the call boundary
$s4b = new NL:>Sequence<int>([1, 2]);
$s4b->appendSequence(new NL:>Sequence<int>([3, 4]));
nl_eq([1, 2, 3, 4], $s4b->toArray(), 'appendSequence from Sequence<int>');
nl_throws(\TypeError::class, function () {
    $seq = new NL:>Sequence<int>([1]);
    $seq->appendSequence(new NL:>Sequence<string>(['x']));
}, 'appendSequence rejects a Sequence of the wrong element type');

// functional API (returns new sequences; element type preserved)
$nums = new NL:>Sequence<int>([1, 2, 3, 4, 5, 6]);
$isEven = new NL:>Func<int, bool>(fn(int $n): bool => $n % 2 === 0);
nl_eq([2, 4, 6], $nums->filter($isEven)->toArray(), 'filter with typed Func<T, bool>');
nl_throws(\TypeError::class, function () use ($nums) {
    $wrong = new NL:>Func<string, bool>(fn(string $s): bool => true);
    $nums->filter($wrong);
}, 'filter rejects a Func of the wrong element type');
nl_eq(
    [2, 4, 6, 8, 10, 12],
    $nums->map<int>(new NL:>Func<int, int>(fn(int $n): int => $n * 2))->toArray(),
    'map<int> same-type with typed Func<T, U>',
);
// type-changing map: Sequence<int> -> Sequence<string>
$stringified = $nums->map<string>(new NL:>Func<int, string>(fn(int $n): string => "#{$n}"));
nl_eq(['#1', '#2', '#3', '#4', '#5', '#6'], $stringified->toArray(), 'map<string> changes element type');
nl_eq(true, $stringified instanceof NL:>Sequence<string>, 'map<string> yields Sequence<string>');
// U is fixed by the explicit type argument: a Func returning something else is rejected
nl_throws(\TypeError::class, function () use ($nums) {
    $nums->map<string>(new NL:>Func<int, int>(fn(int $n): int => $n));
}, 'map<U> rejects a Func whose return type is not U');
// reduce carries a typed accumulator U
nl_eq(
    21,
    $nums->reduce<int>(new NL:>Func<int, int, int>(fn(int $a, int $b): int => $a + $b), 0),
    'reduce<int> with typed Func<U, T, U>',
);
nl_eq(
    '|1|2|3|4|5|6',
    $nums->reduce<string>(new NL:>Func<string, int, string>(fn(string $c, int $n): string => "{$c}|{$n}"), ''),
    'reduce<string> folds to a different type',
);
nl_throws(\TypeError::class, function () use ($nums) {
    $nums->reduce<int>(new NL:>Func<int, int, int>(fn(int $a, int $b): int => $a + $b), 'seed');
}, 'reduce<U> enforces U on the seed');
// each takes a typed Action<T>
$sum = 0;
$nums->each(new NL:>Action<int>(function (int $n) use (&$sum): void { $sum += $n; }));
nl_eq(21, $sum, 'each with typed Action<T>');
nl_throws(\TypeError::class, function () use ($nums) {
    $nums->each(new NL:>Action<string>(function (string $s): void {}));
}, 'each rejects an Action of the wrong element type');
nl_eq([6, 5, 4, 3, 2, 1], $nums->reversed()->toArray(), 'reversed returns new');
nl_eq([1, 2, 3, 4, 5, 6], $nums->toArray(), 'functional ops do not mutate the receiver');

// withers
$base = new NL:>Sequence<int>([1]);
$plus = $base->withAppended(2);
nl_eq([1], $base->toArray(), 'withAppended leaves original');
nl_eq([1, 2], $plus->toArray(), 'withAppended returns a new value');

// element type enforcement
nl_throws(\TypeError::class, fn() => $nums->push("nope"), 'push enforces T');
nl_throws(\TypeError::class, fn() => $s4->append(["ok", "bad"]), 'append enforces T via push');
nl_throws(\TypeError::class, fn() => new NL:>Sequence<int>(['a', 'b']), 'constructor enforces T (fails fast)');
nl_throws(\TypeError::class, fn() => new NL:>Sequence<int>([1, null, 3]), 'constructor rejects null element');
// construction from any iterable (a generator), still type-checked
nl_eq([0, 1, 2], (new NL:>Sequence<int>((function () { yield 0; yield 1; yield 2; })()))->toArray(), 'constructs from a generator');

// instanceof with generic args
nl_eq(true, $nums instanceof NL:>Sequence<int>, 'instanceof Sequence<int>');
nl_eq(false, $nums instanceof NL:>Sequence<string>, 'not instanceof Sequence<string>');

// count() via the Countable interface
nl_eq(6, count($nums), 'count() via Countable');

// foreach via IteratorAggregate (unblocked by the scope-bug fix)
$collected = [];
foreach ($nums as $index => $value) {
    $collected[$index] = $value;
}
nl_eq([1, 2, 3, 4, 5, 6], $collected, 'foreach yields index => value');

// iterating does not consume the value — foreach again yields the same
$again = [];
foreach ($nums as $value) {
    $again[] = $value;
}
nl_eq([1, 2, 3, 4, 5, 6], $again, 'foreach is repeatable (value never consumed)');

// getIterator directly
nl_eq([1, 2, 3, 4, 5, 6], iterator_to_array($nums->getIterator()), 'getIterator');

// a module member nested inside another member's generic argument list
$maybes = new NL:>Sequence<NL:>Option<int>>();
$maybes->push(NL:>Option<int>::some(1));
$maybes->push(NL:>Option<int>::none());
nl_eq(2, $maybes->count(), 'Sequence<Option<int>> holds both cases');
nl_eq(1, $maybes->get(0)->get(), 'nested Some unwraps');
nl_eq(true, $maybes->get(1)->isNone(), 'nested None survives the round trip');
nl_eq(true, $maybes instanceof NL:>Sequence<NL:>Option<int>>, 'instanceof discriminates on the nested argument');
nl_eq(false, $maybes instanceof NL:>Sequence<NL:>Option<string>>, 'not instanceof Sequence<Option<string>>');
nl_throws(\TypeError::class, function () {
    $seq = new NL:>Sequence<NL:>Option<int>>();
    $seq->push(NL:>Option<string>::some('x'));
}, 'nested element type is enforced');
nl_throws(\TypeError::class, function () {
    $seq = new NL:>Sequence<NL:>Option<int>>();
    $seq->push(42);
}, 'a bare int is rejected where Option<int> is expected');
