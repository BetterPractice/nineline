<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Int extensions');

// Integer literals are not directly dereferenceable, so the receiver is parenthesised.
nl_eq(true, (42)->isEven(), 'isEven true');
nl_eq(false, (7)->isEven(), 'isEven false');
nl_eq(true, (7)->isOdd(), 'isOdd true');
nl_eq(5, (-5)->abs(), 'abs of negative');
nl_eq(0, (-5)->clamp(0, 10), 'clamp below min');
nl_eq(10, (99)->clamp(0, 10), 'clamp above max');
nl_eq(5, (5)->clamp(0, 10), 'clamp within range');
nl_eq(5, (5)->clamp(10, 0), 'clamp tolerates swapped bounds');

nl_eq([0, 1, 4], (3)->times(fn(int $i) => $i * $i), 'times collects results');
nl_eq([], (0)->times(fn(int $i) => $i), 'times 0 is empty');
// upTo/downTo return Sequence<int>
nl_eq([2, 3, 4, 5], (2)->upTo(5)->toArray(), 'upTo inclusive range');
nl_eq(true, (2)->upTo(5) instanceof NL:>Sequence<int>, 'upTo is a Sequence<int>');
nl_eq([], (5)->upTo(2)->toArray(), 'upTo empty when end < start');
nl_eq([5, 4, 3], (5)->downTo(3)->toArray(), 'downTo inclusive range');
nl_eq(15, (1)->upTo(5)->reduce(fn(int $a, int $b) => $a + $b, 0), 'upTo result composes with reduce');
nl_eq(8, (2)->pow(3), 'pow');
nl_eq(6, (54)->gcd(24), 'gcd');
nl_eq(4, (-12)->gcd(8), 'gcd of negatives is non-negative');
nl_eq(true, (13)->isPrime(), 'isPrime true');
nl_eq(false, (1)->isPrime(), 'isPrime false for 1');
nl_eq(false, (9)->isPrime(), 'isPrime false for 9');
nl_eq(true, (2)->isPrime(), 'isPrime true for 2');
