<?php

/**
 * Standalone bootstrap for NineLine.
 *
 * Registers a minimal PSR-4 autoloader for the `BetterPractice\NineLine\`
 * namespace and loads the module definition. This lets the test suite run
 * against the raw php-src binary without Composer; a real install would use
 * Composer's autoloader plus the class-map generator (which maps named
 * extension declarations to their files) instead.
 */

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'BetterPractice\\NineLine\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }

    // Generic instantiations are stamped from their template; the engine only
    // ever asks the autoloader for the bare template name, but strip any
    // mangled `<...>` suffix defensively for the dynamic-string escape hatch.
    if (($lt = strpos($class, '<')) !== false) {
        $class = substr($class, 0, $lt);
    }

    $relative = substr($class, strlen($prefix));
    $file = __DIR__ . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

// The module definition must execute before any `use module BetterPractice\NineLine`.
require __DIR__ . '/src/NineLine.php';
