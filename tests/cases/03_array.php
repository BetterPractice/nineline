<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Array extensions');

nl_eq(true, []->isEmpty(), 'isEmpty true');
nl_eq(false, [1]->isEmpty(), 'isEmpty false');
nl_eq(1, [1, 2, 3]->first(), 'first');
nl_eq(3, [1, 2, 3]->last(), 'last');
nl_eq(null, []->first(), 'first of empty is null');
nl_eq(10, [1, 2, 3, 4]->sum(), 'sum');
nl_eq(24, [1, 2, 3, 4]->product(), 'product');
nl_eq(true, [1, 2, 3]->contains(2), 'contains true');
nl_eq(false, [1, 2, 3]->contains("2"), 'contains is strict');
nl_eq([1, 2, 3], [1, 2, 3, 2, 1]->unique(), 'unique preserves first-seen order');
nl_eq([1, 2, 3, 4], [[1, 2], [3, 4]]->flatten(), 'flatten one level');
nl_eq([1, 2, 3], [1, [2, 3]]->flatten(), 'flatten mixes scalars and arrays');
nl_eq([2, 4, 6], [1, 2, 3]->mapValues(fn(int $n) => $n * 2), 'mapValues');
nl_eq([1, 3], array_values([1, 2, 3, 4]->filterValues(fn(int $n) => $n % 2 === 1)), 'filterValues keeps odds');
nl_eq(1, [3, 1, 2]->min(), 'min');
nl_eq(3, [3, 1, 2]->max(), 'max');
nl_eq(null, []->max(), 'max of empty is null');
