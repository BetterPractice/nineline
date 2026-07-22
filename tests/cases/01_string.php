<?php

declare(strict_types=1);

use module BetterPractice\NineLine as NL;

nl_group('String extensions');

nl_eq(6, "foobar"->byteLength(), 'byteLength counts bytes');
nl_eq(true, "  \t "->isBlank(), 'isBlank on whitespace');
nl_eq(false, "x"->isBlank(), 'isBlank false on content');
nl_eq(true, "hello world"->contains("o w"), 'contains substring');
nl_eq(true, "foobar"->startsWith("foo"), 'startsWith');
nl_eq(true, "foobar"->endsWith("bar"), 'endsWith');
nl_eq("HI", "hi"->upper(), 'upper');
nl_eq("hi", "HI"->lower(), 'lower');
nl_eq("Hello There", "hello there"->titleCase(), 'titleCase');
nl_eq("Hello", "hello"->capitalize(), 'capitalize');
nl_eq("cba", "abc"->reverse(), 'reverse');
nl_eq("a b c", "  a   b\tc  "->collapseWhitespace(), 'collapseWhitespace');
nl_eq(4, "the quick brown fox"->wordCount(), 'wordCount');
nl_eq("ababab", "ab"->repeat(3), 'repeat');
nl_eq("", "ab"->repeat(0), 'repeat 0 is empty');
// split/tokenize/lines return a strongly-typed Sequence<string>
nl_eq(['a', 'b', 'c'], "a,b,c"->split(",")->toArray(), 'split returns Sequence');
nl_eq(true, "a,b,c"->split(",") instanceof NL:>Sequence<string>, 'split is a Sequence<string>');
nl_eq(['a', 'b', 'c'], "abc"->split("")->toArray(), 'split empty delimiter into chars');
nl_eq(['the', 'quick', 'brown'], "  the  quick brown  "->tokenize()->toArray(), 'tokenize on whitespace');
nl_eq(3, "the quick brown"->tokenize()->count(), 'tokenize result is countable');
nl_eq(['a', 'b', 'c'], "a\nb\r\nc"->lines()->toArray(), 'lines splits on CR/LF/CRLF');
// the returned Sequence composes with the collection API
nl_eq(['A', 'B', 'C'], "a,b,c"->split(",")->map<string>(fn(string $w) => strtoupper($w))->toArray(), 'split result maps');
nl_eq('hello-world-123', "  Hello,  World! 123 "->slug(), 'slug');
nl_eq('foo-bar', "bar"->ensurePrefix("foo-"), 'ensurePrefix adds');
nl_eq('foo-bar', "foo-bar"->ensurePrefix("foo-"), 'ensurePrefix idempotent');
nl_eq('bar.txt', "bar"->ensureSuffix(".txt"), 'ensureSuffix adds');

// The character-aware methods live in StringMbExtensions, which is declared only
// when ext-mbstring is loaded (ext-mbstring is a *suggested* dependency).
if (extension_loaded('mbstring')) {
    nl_eq(5, "héllo"->length(), 'length counts characters');
    nl_eq(0, ""->length(), 'length of empty string');
    nl_eq('The qui…', "The quick brown fox"->truncate(9), 'truncate composes ->length()');
    nl_eq('hi', "hi"->truncate(9), 'truncate shorter-than-max unchanged');
    nl_eq('HÉLLO', "héllo"->upperMb(), 'upperMb is multi-byte aware');
    nl_eq('héllo', "HÉLLO"->lowerMb(), 'lowerMb is multi-byte aware');
} else {
    // Without mbstring the extension is not declared, so the methods do not
    // exist on strings — this is the optional-dependency behavior in action.
    nl_throws(\Error::class, fn() => "abc"->length(), 'length() is absent without ext-mbstring');
    nl_throws(\Error::class, fn() => "abc"->truncate(2), 'truncate() is absent without ext-mbstring');
    nl_skip('character-aware methods verified as gated off (ext-mbstring not loaded)');
}
