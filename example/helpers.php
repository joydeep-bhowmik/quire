<?php

declare(strict_types=1);

// App-wide helpers, loaded by public/index.php. Global functions, so pages,
// layouts and components can call them without imports.

use function Quire\{abort, request, url};

if (!function_exists('asset')) {
    /**
     * URL for a file in public/, with its modified time appended so browsers
     * fetch a fresh copy after every rebuild: asset('css/app.css').
     */
    function asset(string $path): string
    {
        $file = __DIR__ . '/public/' . ltrim($path, '/');
        $version = is_file($file) ? '?v=' . filemtime($file) : '';

        return url($path) . $version;
    }
}

if (!function_exists('users')) {
    /**
     * All users, keyed by id.
     *
     * @return array<int, array{name: string, email: string}>
     */
    function users(): array
    {
        static $users;

        return $users ??= require __DIR__ . '/data/users.php';
    }
}

if (!function_exists('user')) {
    /**
     * One user, or a 404 page if there's no such id.
     *
     * @return array{name: string, email: string}
     */
    function user(int|string $id): array
    {
        return users()[(int) $id] ?? abort(404, 'User not found');
    }
}

if (!function_exists('initials')) {
    /**
     * "Ada Lovelace" -> "AL".
     */
    function initials(string $name): string
    {
        $words = array_slice(preg_split('/\s+/', trim($name)) ?: [], 0, 2);

        return mb_strtoupper(implode('', array_map(static fn (string $word) => mb_substr($word, 0, 1), $words)));
    }
}

if (!function_exists('is_current')) {
    /**
     * Is the current page this named route, or one of its family?
     * is_current('users.index') is true on users.index and users.show.
     */
    function is_current(string $name): bool
    {
        $current = request()?->route?->name() ?? '';

        return $current === $name || strtok($current, '.') === strtok($name, '.');
    }
}
