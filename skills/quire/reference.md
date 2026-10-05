# Quire API reference

Namespace `Quire`. PHP 8.1+.

## Front controller

```php
<?php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

// Let PHP's built-in server serve real files (CSS, images) directly.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

$quire = new Quire\Quire();
$quire->path(__DIR__ . '/../pages');
$quire->run();
```

Send every request that isn't a real file to `index.php`:

- PHP's built-in server: `php -S localhost:8000 -t public public/index.php`
- Apache `public/.htaccess`:
  ```
  RewriteEngine On
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{REQUEST_FILENAME} !-d
  RewriteRule ^ index.php [QSA,L]
  ```

## `Quire\Quire`

| Method | Purpose |
| --- | --- |
| `path(string $dir): Mount` | Register a pages directory. Call it more than once for several mounts. |
| `middleware(mixed ...$mw): self` | Global middleware. Accepts aliases, closures, class names or arrays of them. |
| `alias(string $name, mixed $mw): self` | Name a middleware. Use it as `'name'` or `'name:arg1,arg2'`. |
| `aliases(array $map): self` | Several aliases at once. |
| `resolveUsing(Closure $fn): self` | Build middleware classes yourself, e.g. `fn (string $class) => $container->get($class)`. |
| `extension(string $ext, callable $renderer): self` | Render another file type. `$renderer($file, $vars)` returns a string, array or `Response`. |
| `extensions(): array` | Registered extensions, longest first. |
| `base(string $path): self` | The app lives in a subfolder, e.g. `base('/my-app')`. |
| `debug(bool $on = true): self` | Show exception messages on 500 pages. Development only. |
| `run(?Request $r = null): void` | Handle the request (from globals by default) and send the response. |
| `handle(Request $r): Response` | Handle without sending. Use this in tests. |
| `url(string $name, array $params = []): string` | URL for a named page, including the base path. |
| `has(string $name): bool` | Is a route name defined? |
| `to(string $path): string` | Add the base path to a path. |
| `routes(): list<Route>` | All routes, most specific first. `$route->pattern()`, `->name()`, `->methods()`, `->file`. |
| `request(): ?Request` | The request being handled. |
| `Quire::instance(): Quire` | The current router (the one handling a request, otherwise the last one created). |

## `Quire\Mount` (returned by `path()`)

| Method | Purpose |
| --- | --- |
| `uri(string $prefix): self` | Serve this folder under a prefix: `->uri('/admin')`. |
| `middleware(array $map): self` | Middleware by pattern relative to the prefix: `['*' => ['log'], 'account/*' => ['auth']]`. Entries with integer keys apply to every page. |

## Helper functions (`use function Quire\{...}`)

| Function | Purpose |
| --- | --- |
| `name(string $name)` | Metadata: route name. |
| `middleware(mixed ...$mw)` | Metadata: page middleware. |
| `methods(string ...$methods)` | Metadata: allowed HTTP methods (default GET). |
| `route(string $name, array $params = []): string` | URL for a named page. |
| `url(string $path = '/'): string` | Path with the base path added. |
| `redirect(string $to, int $status = 302): Response` | Redirect response. `$to` is used exactly as given. |
| `abort(int $status, string $message = '', array $headers = []): never` | Throw an `HttpException`, which renders an error page. |
| `respond(Response $response): never` | Stop and send this response, from anywhere. |
| `request(): ?Request` | The current request. |
| `e(mixed $value): string` | `htmlspecialchars` for output. |

Gotcha: in a file with `use Quire\Quire;`, writing `Quire\abort()` resolves to `Quire\Quire\abort()`. Import with `use function Quire\abort;`, or write `\Quire\abort()`.

## `Quire\Request`

Properties: `method` (uppercase), `path`, `query`, `body`, `headers` (lowercase keys), `cookies`, `files`, `server`, `params` (route params), `route` (`?Route`), `attributes` (free space for middleware to share data).

| Method | Purpose |
| --- | --- |
| `Request::fromGlobals()` | Build from `$_SERVER` and friends. Applies the `_method` override and decodes JSON bodies. |
| `Request::create(string $method, string $uri, array $body = [], array $headers = [], array $cookies = [])` | Build by hand, for tests. The query string in `$uri` is parsed. |
| `input(string $key, $default = null)` | Body first, then query. |
| `param(string $key, $default = null)` | Route param. |
| `header(string $name, $default = null)` | Case-insensitive. |
| `cookie(string $name, $default = null)` | |
| `isMethod(string $method): bool` | |
| `wantsJson(): bool` | `Accept` header contains "json". |

## `Quire\Response`

Properties: `body`, `status`, `headers`, `cookies`.

| Method | Purpose |
| --- | --- |
| `Response::html(string $html, int $status = 200)` | |
| `Response::text(string $text, int $status = 200)` | |
| `Response::json(mixed $data, int $status = 200)` | |
| `Response::redirect(string $to, int $status = 302)` | |
| `Response::from(mixed $value)` | Converts a Response, string, Stringable, array or JsonSerializable. |
| `Response::reason(int $status): string` | "Not Found" and so on. |
| `header(string $name, string $value): self` | Replaces the header, matching names case-insensitively. Chainable. |
| `getHeader(string $name): ?string` | |
| `cookie(string $name, string $value, array $options = []): self` | Options as for `setcookie()`. Defaults: `path=/`, `httponly`, `samesite=Lax`. |
| `forgetCookie(string $name): self` | |
| `send(bool $withoutBody = false): void` | Sends status, headers, cookies and body. |

## `Quire\HttpException`

`new HttpException(int $status, string $message = '', array $headers = [], ?Throwable $previous = null)`. It has public `status` and `headers`. Error pages receive it as `$exception`.

## `Quire\Blade\BladeRenderer`

Requires `composer require illuminate/view illuminate/events`.

| Method | Purpose |
| --- | --- |
| `new BladeRenderer(string\|array $viewPaths, string $cachePath, ?Container $container = null)` | `$viewPaths` is where `@extends` and `@include` look (usually the pages folder). The cache folder is created if missing. |
| `components(string $path, ?string $prefix = null): self` | Anonymous components: `<x-card>`, or `<x-ui::card>` with a prefix. |
| `directive(string $name, callable $handler): self` | Custom `@directive`. |
| `share(string $key, mixed $value): self` | A variable available in every view. |
| `factory()`, `compiler()` | The underlying Illuminate objects. |

## Route matching details

- Paths are split on `/`, and each segment is URL-decoded. A trailing slash is ignored.
- `[param]` never matches an empty segment.
- HEAD is served by GET pages with no body. A matching path with the wrong method returns 405 with an `Allow` header.
- `name()` must be unique across all pages, or a `LogicException` is thrown.
- Metadata is cached per file and refreshed when the file's modified time changes.
