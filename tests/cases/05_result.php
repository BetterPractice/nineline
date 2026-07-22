<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('Result<T, E>');

$ok = NL:>Result<int, string>::ok(200);
$err = NL:>Result<int, string>::err("boom");

nl_eq(true, $ok->isOk(), 'ok isOk');
nl_eq(false, $ok->isErr(), 'ok isErr');
nl_eq(true, $err->isErr(), 'err isErr');
nl_eq(200, $ok->unwrap(), 'unwrap ok');
nl_eq(200, $ok->unwrapOr(0), 'unwrapOr on ok');
nl_eq(0, $err->unwrapOr(0), 'unwrapOr on err');
nl_eq("boom", $err->unwrapErr(), 'unwrapErr on err');

// map / mapErr preserve types
nl_eq(201, $ok->map(fn(int $n) => $n + 1)->unwrap(), 'map on ok');
nl_eq(true, $err->map(fn(int $n) => $n + 1)->isErr(), 'map on err passes through');
nl_eq("BOOM", $err->mapErr(fn(string $e) => strtoupper($e))->unwrapErr(), 'mapErr on err');
nl_eq(200, $ok->mapErr(fn(string $e) => strtoupper($e))->unwrap(), 'mapErr on ok passes through');

// match
nl_eq('ok:200', $ok->match(fn(int $n) => "ok:$n", fn(string $e) => "err:$e"), 'match ok branch');
nl_eq('err:boom', $err->match(fn(int $n) => "ok:$n", fn(string $e) => "err:$e"), 'match err branch');

// wrong-side unwraps throw
nl_throws(\RuntimeException::class, fn() => $err->unwrap(), 'unwrap on err throws');
nl_throws(\RuntimeException::class, fn() => $ok->unwrapErr(), 'unwrapErr on ok throws');

// type enforcement on both sides
nl_throws(\TypeError::class, fn() => NL:>Result<int, string>::ok("nope"), 'ok() enforces T');
nl_throws(\TypeError::class, fn() => NL:>Result<int, string>::err(123), 'err() enforces E');

// value semantics
$base = NL:>Result<int, string>::ok(1);
$mapped = $base->map(fn(int $n) => $n + 9);
nl_eq(1, $base->unwrap(), 'map does not mutate original Result');
nl_eq(10, $mapped->unwrap(), 'map returns a new Result');
