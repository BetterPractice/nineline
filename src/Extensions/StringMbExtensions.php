<?php

declare(strict_types=1);

/**
 * @see \BetterPractice\NineLine — the module that exports this extension.
 */

namespace BetterPractice\NineLine\Extensions;

module BetterPractice\NineLine;

/**
 * Character-aware string extension methods that depend on ext-mbstring.
 *
 * The whole extension is declared **only when mbstring is loaded**. Extension
 * registration is load-gated — it happens when the `extension { }` declaration
 * executes — so guarding the declaration with a runtime condition makes these
 * methods exist only where their dependency does. Without mbstring, the file
 * still loads (autoloaded on first use), the declaration is skipped, and a call
 * like `"x"->length()` raises the ordinary "Call to a member function length()
 * on string" — the method is simply not there.
 *
 * ext-mbstring is therefore a *suggested*, not required, dependency: install it
 * to gain these methods; omit it and the rest of NineLine is unaffected.
 */
if (extension_loaded('mbstring')) {
    extension StringMbExtensions on string $s {
        /** The number of characters (multi-byte aware). */
        public function length(): int
        {
            return mb_strlen($s);
        }

        /**
         * Truncate to at most $max characters, appending $ellipsis when
         * shortened. The result (including the ellipsis) never exceeds $max
         * characters.
         */
        public function truncate(int $max, string $ellipsis = '…'): string
        {
            if ($max <= 0) {
                return '';
            }
            if ($s->length() <= $max) {
                return $s;
            }
            $keep = max(0, $max - $ellipsis->length());
            return mb_substr($s, 0, $keep) . $ellipsis;
        }

        /** Upper-case every character (multi-byte aware). */
        public function upperMb(): string
        {
            return mb_strtoupper($s);
        }

        /** Lower-case every character (multi-byte aware). */
        public function lowerMb(): string
        {
            return mb_strtolower($s);
        }
    }
}
