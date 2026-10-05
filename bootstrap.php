<?php

declare(strict_types=1);

// Autoloader for use without Composer: require this file and go.

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'Quire\\')) {
        $file = __DIR__ . '/src/' . str_replace('\\', '/', substr($class, 6)) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
});

require_once __DIR__ . '/src/functions.php';
