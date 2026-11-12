<?php

declare(strict_types=1);

/**
 * Autoloader PSR-4 nativo para el proyecto COTIZA-ELEV
 */
spl_autoload_register(function (string $class): void {
    $prefixes = [
        'CotizaElev\\Services\\' => __DIR__ . '/../src/Services/',
        'App\\Core\\'            => __DIR__ . '/Core/',
        'App\\Models\\'          => __DIR__ . '/Models/',
        'App\\Middlewares\\'     => __DIR__ . '/Middlewares/',
        'App\\Controllers\\'     => __DIR__ . '/Controllers/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) === 0) {
            $relativeClass = substr($class, $len);
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});
