# Quire

A page-based router for plain PHP, inspired by [Laravel Folio](https://github.com/laravel/folio). No framework needed.
Drop `.php` files in a folder and each one becomes a route. Middleware and route names are declared at the top of the page.

> A *quire* is a stack of folio sheets bound together.

```
pages/
├── index.php              →  /
├── about.php              →  /about
├── login.php              →  /login
├── users/
│   ├── index.php          →  /users
│   ├── [id].php           →  /users/42            $id = '42'
│   └── [id]/
│       └── edit.php       →  /users/42/edit       $id = '42'
├── docs/
│   └── [...slug].php      →  /docs/a/b/c          $slug = ['a', 'b', 'c']   (1+ segments)
├── blog/
│   └── [[...slug]].php    →  /blog, /blog/x/y     $slug = [] or ['x', 'y']  (0+ segments)
├── admin/
│   ├── _middleware.php    →  (middleware for everything in admin/)
│   └── index.php          →  /admin
├── _partials/             →  (ignored: files/folders starting with _ or . aren't routes)
├── _404.php               →  404 page
└── _error.php             →  any other error page
```

## Installation

```bash
composer require joydeep-bhowmik/quire
```

Requires PHP 8.1+. No other dependencies. For Blade pages, also install Blade (see [Blade](#blade)).

## Usage

```php
// public/index.php
require __DIR__ . '/../vendor/autoload.php';

$quire = new Quire\Quire();
$quire->path(__DIR__ . '/../pages');
$quire->run();
```

Send every request that isn't a real file to `index.php`. With Apache, use
[`example/public/.htaccess`](example/public/.htaccess). With PHP's built-in server, run
`php -S localhost:8000 -t public public/index.php`.

No Composer? Download the repo and `require 'path/to/quire/bootstrap.php';` instead of the autoloader.

## Example app

```bash
git clone https://github.com/joydeep-bhowmik/quire && cd quire
composer install  # the example app uses Blade
composer test     # run the test suite
composer serve    # http://localhost:8000
```

The example is a small Blade site styled with Tailwind CSS v4:

```
example/
├── app.css                      Tailwind entry (@import "tailwindcss")
├── postcss.config.mjs           @tailwindcss/postcss, like Next.js
├── package.json                 npm run dev / npm run build
├── public/
│   ├── index.php                front controller (Quire + BladeRenderer)
│   └── css/app.css              built CSS
├── data/users.php               fake data, outside pages/
└── pages/
    ├── _layouts/app.blade.php   layout with nav
    ├── _components/avatar.blade.php   <x-avatar>
    ├── _404.blade.php           404 page
    ├── index.blade.php          /
    ├── about.blade.php          /about
    └── users/
        ├── index.blade.php      /users
        └── [id].blade.php       /users/{id}
```

The built CSS is checked in, so the demo is styled without Node. To change styles:

```bash
cd example
npm install
npm run dev     # rebuilds public/css/app.css when pages change
npm run build   # minified build
```

`tests/fixtures/` has a bigger app covering middleware, catch-alls, plain PHP pages and more.

## Pages

A page is a normal PHP file. Route params are local variables, and `$request` is always there.

```php
<?php
// pages/users/[id].php
use function Quire\{name, middleware, abort, e};

name('users.show');
middleware(['auth']);

$user = find_user($id) ?? abort(404);
?>
<h1>Hello <?= e($user->name) ?></h1>
```

What the page returns decides the response:

| Page does                         | Response              |
| --------------------------------- | --------------------- |
| outputs HTML (`echo`, `?>...`)    | `200 text/html`       |
| `return ['ok' => true];`          | JSON                  |
| `return redirect('/somewhere');`  | that `Response`       |
| `abort(403, 'Nope');`             | error page with 403   |
| `respond($response);`             | that response, from anywhere (even templates) |

### Page metadata

Put these at the **top** of the page, before any other code:

```php
use function Quire\{name, middleware, methods};

name('users.edit');                 // name the route for route()
middleware('auth', 'role:admin');   // page middleware
methods('GET', 'POST');             // default is GET only (HEAD is automatic)
```

Quire reads only the run of `use`, `name()`, `middleware()` and `methods()` statements at the top of the file,
so it can find a page's middleware and name **without running the page**. When the page actually renders, these calls do nothing.
Anything after the first other statement isn't read as metadata.

POST to a GET-only page gives `405` with an `Allow` header. HTML forms can fake `PUT`/`PATCH`/`DELETE` with a hidden `_method` field.

## Blade

Pages can be Blade templates. Install Blade (no Laravel app needed) and register the adapter:

```bash
composer require illuminate/view illuminate/events
```

```php
use Quire\Blade\BladeRenderer;

$blade = new BladeRenderer(
    viewPaths: __DIR__ . '/pages',             // where @extends / @include look
    cachePath: __DIR__ . '/storage/views',     // compiled templates
);
$blade->components(__DIR__ . '/pages/_components');   // <x-card> → pages/_components/card.blade.php

$quire->extension('.blade.php', $blade);
```

Now `pages/team/[member].blade.php` is served at `/team/{member}`. Route params, `$request`, middleware, names,
`_middleware.php`, and error pages (`_404.blade.php`) all work as they do for plain PHP pages. Layouts and partials go in `_`-folders,
so they never become routes: `@extends('_layouts.app')` loads `pages/_layouts/app.blade.php`.

Put metadata at the top in an `@php` block (or a plain `<?php ?>` block):

```blade
@php
    use function Quire\{name, middleware};

    name('team.manage');
    middleware('auth');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('content')
    <h1>Hi {{ $request->attributes['user'] }}</h1>
    <a href="{{ route('team.index') }}">Back</a>
@endsection
```

A Blade template can't `return` a response. Call `respond()` instead, which works from any page:

```php
<?php
use function Quire\{name, respond, redirect, route, abort};
name('team.show');

if ($member === 'old-name') {
    respond(redirect(route('team.show', ['member' => 'new-name']), 301));
}
$person = find($member) ?? abort(404);
?>
@extends('_layouts.app')
...
```

Also on the adapter: `->directive('money', fn ($e) => "<?php echo number_format($e, 2); ?>")`, `->share('appName', 'Acme')`,
plus `->factory()` and `->compiler()` for anything else Blade can do.

Until a `.blade.php` renderer is registered, `*.blade.php` files are skipped. Their raw source is never served as PHP.

### Other template engines

`extension()` takes any callable `fn (string $file, array $vars): string|array|Response`, so Twig, Latte or Plates can be plugged in the same way.
If an engine wraps exceptions, Quire still finds `abort()` and `respond()` inside them.

## Middleware

A middleware is any callable `fn (Request $request, Closure $next, ...$args)` that returns a response,
usually `$next($request)`. It can also be a class with a `handle()` method, or an invokable class.

```php
use Quire\{Request, Response};

$quire->alias('auth', function (Request $request, Closure $next) {
    if (!$request->cookie('user')) {
        return Quire\redirect(Quire\url('/login'));   // stop here
    }

    $request->attributes['user'] = $request->cookie('user'); // pass data to the page
    return $next($request);
});

$quire->alias('role', function (Request $request, Closure $next, string ...$roles) {
    in_array($request->attributes['user'], $roles, true) || Quire\abort(403);
    return $next($request);
});

$quire->alias('timing', function (Request $request, Closure $next): Response {
    $response = $next($request);                       // run the page first...
    return $response->header('X-Powered-By', 'Quire'); // ...then change the response
});

$quire->alias('csrf', App\Middleware\VerifyCsrf::class); // class with handle()
```

`'role:admin,editor'` passes `'admin'` and `'editor'` as extra arguments.

There are four places to attach middleware. They run in this order (outermost first):

1. **Global**: `$quire->middleware('timing')`
2. **By path pattern** on a mount (relative to its URI, `*` is a wildcard):
   ```php
   $quire->path(__DIR__ . '/pages')->middleware([
       '*'         => ['timing'],
       'account/*' => ['auth'],
   ]);
   ```
3. **By folder**: a `_middleware.php` that returns an array. It covers that folder and every subfolder, parent folders first.
   ```php
   <?php // pages/admin/_middleware.php
   return ['auth', 'role:admin'];
   ```
4. **On the page**: `middleware(...)` at the top of the file.

Using a DI container? `$quire->resolveUsing(fn (string $class) => $container->get($class));`

## Named routes & URLs

```php
use function Quire\{route, url};

route('users.show', ['id' => 5]);               // /users/5
route('users.show', ['id' => 5, 'tab' => 'x']); // /users/5?tab=x   (extra params → query string)
route('blog', ['slug' => ['2026', 'hello']]);    // /blog/2026/hello
route('blog');                                   // /blog            (optional catch-all can be left out)
url('/login');                                   // /login, with the base path added
```

## Mounts, prefixes, base path

```php
$quire->path(__DIR__ . '/pages');                                          // served at /
$quire->path(__DIR__ . '/admin-pages')->uri('/admin')->middleware(['auth']);  // served at /admin/...
$quire->base('/my-app');   // app lives at example.com/my-app/
```

## Route priority

More specific routes win, segment by segment: `static` > `[param]` > `[...catchall]` > `[[...optional]]`.
So `users/create.php` beats `users/[id].php`. If two pages map to the same route (`users.php` and `users/index.php`,
or `[id].php` and `[slug].php` in the same folder), Quire throws an error.

## Errors

`abort($status, $message)` anywhere (page or middleware) renders `_{status}.php` (such as `_404.php`), then `_error.php`,
from the mount's root folder. If neither exists, you get plain text. Error pages get `$status`, `$message`, `$exception` and `$request`.
Uncaught exceptions become `500`. Turn on `$quire->debug()` to see the real message (never in production).

## Request & Response

```php
$request->method; $request->path; $request->query; $request->body; $request->cookies; $request->files;
$request->input('name', 'default');   // body, then query
$request->param('id');                // route param
$request->header('Accept');
$request->attributes['user'];         // set by middleware
$request->isMethod('POST');
$request->wantsJson();

Response::html($html, 200);
Response::json($data, 201);
Response::redirect('/x', 303)->cookie('flash', 'Saved')->header('X-Foo', 'bar');
```

Inside pages and middleware, `Quire\request()` returns the current request.

## Testing

`$quire->handle(Request::create('POST', '/login', ['user' => 'bob'], cookies: [...]))` returns a `Response` without sending it.
See `tests/run.php`.

## License

MIT. See [LICENSE](LICENSE).
