<?php

declare(strict_types=1);

/**
 * Minimale PSR-4 autoloader voor de NHM\ namespace. Hiermee draait de demo
 * ook zonder `composer install`. In een echt project gebruik je
 * vendor/autoload.php van Composer.
 */
spl_autoload_register(static function (string $class): void {
    $prefix = 'NHM\\';
    $baseDir = __DIR__ . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require $file;
    }
});
