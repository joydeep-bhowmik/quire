<?php

declare(strict_types=1);

use Quire\Blade\BladeRenderer;
use Quire\Quire;

// Let PHP's built-in server serve real files (css, images...) directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require __DIR__ . '/../../vendor/autoload.php';
require __DIR__ . '/../helpers.php';

$pages = __DIR__ . '/../pages';

$quire = new Quire();
$quire->path($pages);
$blade = new BladeRenderer(
    viewPaths: $pages,                          // so @extends('_layouts.app') finds pages/_layouts/app.blade.php
    cachePath: __DIR__ . '/../storage/views',   // compiled templates
);
$blade->components($pages . '/_components');    // <x-avatar> -> pages/_components/avatar.blade.php

$quire->extension('.blade.php', $blade);

$quire->debug()->run();
