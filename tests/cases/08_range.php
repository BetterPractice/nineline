<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Range (struct implementing the colored Iterator)');

// ascending foreach
$asc = [];
foreach (new NL:>Range(0, 5) as $i) {
    $asc[] = $i;
}
nl_eq([0, 1, 2, 3, 4], $asc, 'ascending half-open range');

// stepped descending foreach
$desc = [];
foreach (new NL:>Range(10, 0, -2) as $i) {
    $desc[] = $i;
}
nl_eq([10, 8, 6, 4, 2], $desc, 'descending stepped range');

// foreach keys
$pairs = [];
foreach (new NL:>Range(5, 8) as $k => $v) {
    $pairs[$k] = $v;
}
nl_eq([0 => 5, 1 => 6, 2 => 7], $pairs, 'keys are 0-based iteration counts');

// value route: iterating never consumes the range
$r = new NL:>Range(0, 3);
nl_eq([0, 1, 2], $r->toArray(), 'toArray first pass');
nl_eq([0, 1, 2], $r->toArray(), 'toArray repeatable — value never consumed');
$once = [];
foreach ($r as $i) {
    $once[] = $i;
}
foreach ($r as $i) {
    $once[] = $i;
}
nl_eq([0, 1, 2, 0, 1, 2], $once, 'two foreach passes yield the same sequence');

// count + contains
nl_eq(5, (new NL:>Range(0, 5))->count(), 'count ascending');
nl_eq(5, (new NL:>Range(10, 0, -2))->count(), 'count descending stepped');
nl_eq(3, (new NL:>Range(0, 5, 2))->count(), 'count with step 2');
nl_eq(0, (new NL:>Range(5, 5))->count(), 'empty range count is 0');
nl_eq(count(new NL:>Range(0, 5)), 5, 'count() via Countable');
nl_eq(true, (new NL:>Range(0, 10, 2))->contains(4), 'contains a member');
nl_eq(false, (new NL:>Range(0, 10, 2))->contains(5), 'does not contain off-step value');
nl_eq(false, (new NL:>Range(0, 5))->contains(5), 'end is exclusive');

// readonly bounds are exposed
$b = new NL:>Range(2, 9, 3);
nl_eq(2, $b->start, 'start exposed');
nl_eq(9, $b->end, 'end exposed');
nl_eq(3, $b->step, 'step exposed');

// zero step rejected
nl_throws(\InvalidArgumentException::class, fn() => new NL:>Range(0, 5, 0), 'zero step rejected');
