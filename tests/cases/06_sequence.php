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
nl_eq([1, 2, 3], $s3->toArray(), 'sort ascending');
$s3->sort(fn(int $a, int $b) => $b <=> $a);
nl_eq([3, 2, 1], $s3->toArray(), 'sort with comparator');
$s3->reverse();
nl_eq([1, 2, 3], $s3->toArray(), 'reverse in place');

// append from any iterable (element type still enforced via push)
$s4 = new NL:>Sequence<int>([1, 2]);
$s4->append([3, 4]);
nl_eq([1, 2, 3, 4], $s4->toArray(), 'append from array');

// functional API (returns new sequences; element type preserved)
$nums = new NL:>Sequence<int>([1, 2, 3, 4, 5, 6]);
nl_eq([2, 4, 6], $nums->filter(fn(int $n) => $n % 2 === 0)->toArray(), 'filter');
nl_eq([2, 4, 6, 8, 10, 12], $nums->map<int>(fn(int $n) => $n * 2)->toArray(), 'map<int> same-type');
// type-changing map: Sequence<int> -> Sequence<string>
$stringified = $nums->map<string>(fn(int $n) => "#{$n}");
nl_eq(['#1', '#2', '#3', '#4', '#5', '#6'], $stringified->toArray(), 'map<string> changes element type');
nl_eq(true, $stringified instanceof NL:>Sequence<string>, 'map<string> yields Sequence<string>');
// the output element type is enforced when collected
nl_throws(\TypeError::class, fn() => $nums->map<string>(fn(int $n) => $n), 'map<U> enforces U on the result');
nl_eq(21, $nums->reduce(fn(int $a, int $b) => $a + $b, 0), 'reduce');
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
