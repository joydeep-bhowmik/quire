<?php

declare(strict_types=1);

// The fixtures mix plain PHP and Blade pages, so it needs Composer's autoloader for illuminate/view.
if (!is_file(__DIR__ . '/../../vendor/autoload.php')) {
    exit("Run `composer install` first: the tests use Blade (illuminate/view).\n");
}

require __DIR__ . '/../../vendor/autoload.php';

use Quire\Blade\BladeRenderer;
use Quire\Quire;
use Quire\Request;
use Quire\Response;

use function Quire\{abort, redirect, url};

const PAGES = __DIR__ . '/pages';

$quire = new Quire();

$quire->path(PAGES)->middleware([
    '*' => ['timing'],
]);

// *.blade.php pages render through Blade. Layouts and partials live in pages/_layouts etc.,
// so @extends('_layouts.app') works; components in pages/_components become <x-...> tags.
$blade = new BladeRenderer(viewPaths: PAGES, cachePath: __DIR__ . '/storage/views');
$blade->components(PAGES . '/_components');

$quire->extension('.blade.php', $blade);

// After-middleware: runs the page first, then decorates the response.
$quire->alias('timing', function (Request $request, Closure $next): Response {
    $start = hrtime(true);
    $response = $next($request);

    return $response->header('X-Response-Time', round((hrtime(true) - $start) / 1e6, 2) . 'ms');
});

// Before-middleware: can short-circuit with its own response.
$quire->alias('auth', function (Request $request, Closure $next) {
    $user = $request->cookie('user');

    if ($user === null || $user === '') {
        return redirect(url('/login') . '?next=' . rawurlencode($request->path));
    }

    $request->attributes['user'] = $user;

    return $next($request);
});

// Parameterised middleware: 'role:admin,editor' calls this with $roles = ['admin', 'editor'].
$quire->alias('role', function (Request $request, Closure $next, string ...$roles) {
    if (!in_array($request->attributes['user'] ?? null, $roles, true)) {
        abort(403, 'You need one of these roles: ' . implode(', ', $roles));
    }

    return $next($request);
});

return $quire;
