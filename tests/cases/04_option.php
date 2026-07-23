<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Option<T>');

$some = NL:>Option<int>::some(42);
$none = NL:>Option<int>::none();

nl_eq(true, $some->isSome(), 'some isSome');
nl_eq(false, $some->isNone(), 'some isNone');
nl_eq(true, $none->isNone(), 'none isNone');
nl_eq(42, $some->get(), 'get returns value');
nl_eq(42, $some->getOr(0), 'getOr on some');
nl_eq(0, $none->getOr(0), 'getOr on none');
nl_eq(99, $none->getOrElse(fn() => 99), 'getOrElse on none');
nl_eq(42, $some->toNullable(), 'toNullable on some');
nl_eq(null, $none->toNullable(), 'toNullable on none');

// map preserves element type
nl_eq(43, $some->map(fn(int $n) => $n + 1)->get(), 'map on some');
nl_eq(true, $none->map(fn(int $n) => $n + 1)->isNone(), 'map on none stays none');

// filter
nl_eq(true, $some->filter(fn(int $n) => $n > 100)->isNone(), 'filter fails -> none');
nl_eq(42, $some->filter(fn(int $n) => $n > 10)->get(), 'filter passes -> some');

// fromNullable
nl_eq(true, (NL:>Option<int>::fromNullable(null))->isNone(), 'fromNullable(null) is none');
nl_eq(7, (NL:>Option<int>::fromNullable(7))->get(), 'fromNullable(value) is some');

// get() on none throws
nl_throws(\RuntimeException::class, fn() => $none->get(), 'get() on none throws');

// element type is enforced on construction
nl_throws(\TypeError::class, fn() => NL:>Option<int>::some("nope"), 'some() enforces T');

// value semantics: Options are values, copied on assignment
$a = NL:>Option<int>::some(1);
$b = $a->map(fn(int $n) => $n + 100);
nl_eq(1, $a->get(), 'map does not mutate the original Option');
nl_eq(101, $b->get(), 'map returns a new Option');

// instanceof with generic args
nl_eq(true, $some instanceof NL:>Option<int>, 'instanceof Option<int>');
nl_eq(false, $some instanceof NL:>Option<string>, 'not instanceof Option<string>');

// the payload slot is typed with the substituted T, not `mixed`
$slot = (new \ReflectionObject(NL:>Option<int>::some(1)))->getProperty('value');
nl_eq('?int', (string) $slot->getType(), 'Some payload slot is typed ?T (substituted)');
$slotNone = (new \ReflectionObject(NL:>Option<int>::none()))->getProperty('value');
nl_eq('?int', (string) $slotNone->getType(), 'None payload slot carries the same ?T');

// nesting: Option<Option<T>> distinguishes "absent" from "present but empty" —
// the one shape `?T` cannot express, since there is no `??T`.
$presentButEmpty = NL:>Option<NL:>Option<int>>::some(NL:>Option<int>::none());
$absent          = NL:>Option<NL:>Option<int>>::none();
nl_eq(true, $presentButEmpty->isSome(), 'outer Some is present');
nl_eq(true, $presentButEmpty->get()->isNone(), 'inner None is empty');
nl_eq(true, $absent->isNone(), 'absent is a distinct state from present-but-empty');
