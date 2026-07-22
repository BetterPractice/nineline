<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this extension.
 */

namespace BetterPractice\NineLine\Extensions;

module BetterPractice\NineLine;

use BetterPractice\NineLine\Collections\Sequence;

/**
 * Extension methods on the built-in `string` type.
 *
 * Activated for a file the moment it imports the NineLine module:
 *
 *     use module BetterPractice\NineLine as NL;
 *     "hello world"->tokenize();    // ['hello', 'world']
 *     "  hi  "->trimmed()->upper(); // "HI"  (calls compose)
 *
 * The receiver is the declared variable `$s` (scalar extension methods have no
 * `$this`); it is bound by value, so nothing here can mutate the caller's string.
 *
 * The multi-byte-aware character methods (`length`, `truncate`) live in the
 * companion {@see StringMbExtensions}, which is declared only when ext-mbstring
 * is loaded — so those methods simply don't exist on strings without it.
 */
extension StringExtensions on string $s
{
    /** The size of the string in bytes. */
    public function byteLength(): int
    {
        return strlen($s);
    }

    /** True when the string has zero length. */
    public function isEmpty(): bool
    {
        return $s === '';
    }

    /** True when the string is empty or only whitespace. */
    public function isBlank(): bool
    {
        return trim($s) === '';
    }

    public function contains(string $needle): bool
    {
        return $needle === '' || str_contains($s, $needle);
    }

    public function startsWith(string $prefix): bool
    {
        return str_starts_with($s, $prefix);
    }

    public function endsWith(string $suffix): bool
    {
        return str_ends_with($s, $suffix);
    }

    public function upper(): string
    {
        return strtoupper($s);
    }

    public function lower(): string
    {
        return strtolower($s);
    }

    /** Upper-case the first character. */
    public function capitalize(): string
    {
        return ucfirst($s);
    }

    /** Upper-case the first character of each word. */
    public function titleCase(): string
    {
        return ucwords($s);
    }

    public function reverse(): string
    {
        return strrev($s);
    }

    /** Trim whitespace (or the given characters) from both ends. */
    public function trimmed(string $characters = " \t\n\r\0\x0B"): string
    {
        return trim($s, $characters);
    }

    /** Collapse every run of whitespace to a single space, and trim the ends. */
    public function collapseWhitespace(): string
    {
        return trim((string) preg_replace('/\s+/', ' ', $s));
    }

    /** The number of whitespace-separated words. */
    public function wordCount(): int
    {
        return str_word_count($s);
    }

    /** Repeat the string $times times. */
    public function repeat(int $times): string
    {
        return $times <= 0 ? '' : str_repeat($s, $times);
    }

    /**
     * Split on a delimiter into a sequence. An empty delimiter splits into
     * individual bytes.
     */
    public function split(string $delimiter): Sequence<string>
    {
        if ($delimiter === '') {
            return new Sequence<string>($s === '' ? [] : str_split($s));
        }
        return new Sequence<string>(explode($delimiter, $s));
    }

    /** Split into words on any run of whitespace, discarding empty tokens. */
    public function tokenize(): Sequence<string>
    {
        $trimmed = trim($s);
        if ($trimmed === '') {
            return new Sequence<string>();
        }
        return new Sequence<string>(preg_split('/\s+/', $trimmed) ?: []);
    }

    /** Split into lines on CR, LF, or CRLF. */
    public function lines(): Sequence<string>
    {
        return new Sequence<string>(preg_split('/\r\n|\r|\n/', $s) ?: []);
    }

    /**
     * A URL-friendly slug: lower-cased, non-alphanumeric runs turned into single
     * hyphens, with leading and trailing hyphens removed.
     */
    public function slug(): string
    {
        $lower = strtolower(trim($s));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $lower) ?? '';
        return trim($slug, '-');
    }

    /** The string with $prefix prepended only if not already present. */
    public function ensurePrefix(string $prefix): string
    {
        return str_starts_with($s, $prefix) ? $s : $prefix . $s;
    }

    /** The string with $suffix appended only if not already present. */
    public function ensureSuffix(string $suffix): string
    {
        return str_ends_with($s, $suffix) ? $s : $s . $suffix;
    }
}
