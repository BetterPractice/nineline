<?php

/**
 * NineLine test runner. Registers the library (bootstrap), loads the assertion
 * harness, then includes every case file under tests/cases/. Each case is
 * compiled after the module is registered, so it may `use module` at its top.
 *
 * Usage:  php tests/run.php
 */

declare(strict_types=1);

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/harness.php';

foreach (glob(__DIR__ . '/cases/*.php') as $case) {
    require $case;
}

exit(nl_summary());
