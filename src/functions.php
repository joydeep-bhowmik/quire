<?php

declare(strict_types=1);

namespace Quire;

/**
 * Name the current page so you can link to it with route().
 */
function name(string $name): void
{
    if ($metadata = PageMetadata::collecting()) {
        $metadata->name = $name;
    }
}

/**
 * Attach middleware to the current page.
 */
function middleware(mixed ...$middleware): void
{
    if ($metadata = PageMetadata::collecting()) {
        array_push($metadata->middleware, ...Quire::flatten($middleware));
    }
}

/**
 * Allow HTTP methods other than GET for the current page.
 */
function methods(string|array ...$methods): void
{
    if ($metadata = PageMetadata::collecting()) {
        foreach (Quire::flatten($methods) as $method) {
            $metadata->methods[] = strtoupper((string) $method);
        }
    }
}

/**
 * URL for a named page: route('users.show', ['id' => 1]).
 *
 * @param array<string, mixed> $params
 */
function route(string $name, array $params = []): string
{
    return Quire::instance()->url($name, $params);
}

/**
 * Path prefixed with the app's base path: url('/login').
 */
function url(string $path = '/'): string
{
    return Quire::instance()->to($path);
}

function redirect(string $to, int $status = 302): Response
{
    return Response::redirect($to, $status);
}

/**
 * @param array<string, string> $headers
 */
function abort(int $status, string $message = '', array $headers = []): never
{
    throw new HttpException($status, $message, $headers);
}

/**
 * Stop the page and send this response instead: respond(redirect('/login')).
 */
function respond(Response $response): never
{
    throw new ResponseException($response);
}

function request(): ?Request
{
    return Quire::instance()->request();
}

/**
 * Escape for HTML output.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
