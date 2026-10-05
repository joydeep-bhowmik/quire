---
name: quire
description: Build and change PHP apps that use Quire (joydeep-bhowmik/quire), a file-based page router where every .php or .blade.php file in a pages/ folder is a route. Use when adding pages, dynamic routes ([id].php, [...slug].php), middleware, named routes, error pages (_errors/), redirects, forms, JSON endpoints or Blade layouts in a Quire project, or when the user mentions Quire, Folio-style routing in plain PHP, or a pages/ folder with [param] file names.
---

# Quire

Quire maps files to URLs. There is no routes file: to add a route, create a file. To change a URL, rename or move the file.

Spot a Quire project by `joydeep-bhowmik/quire` in composer.json, `new Quire\Quire()` in a front controller (usually `public/index.php`), or a `pages/` folder containing `[param].php` files.

Before editing, read the front controller to learn: the pages directory, mount prefixes (`->uri()`), the base path (`->base()`), middleware aliases, and whether Blade is registered (`->extension('.blade.php', ...)`).

## File → URL rules

| File in `pages/`              | URL                  | Variables in the page             |
| ----------------------------- | -------------------- | --------------------------------- |
| `index.php`                   | `/`                  |                                   |
| `about.php`                   | `/about`             |                                   |
| `users/index.php`             | `/users`             |                                   |
| `users/[id].php`              | `/users/42`          | `$id = '42'` (always a string)    |
| `users/[id]/edit.php`         | `/users/42/edit`     | `$id`                             |
| `docs/[...slug].php`          | `/docs/a/b`          | `$slug = ['a', 'b']` (1+ parts)   |
| `blog/[[...slug]].php`        | `/blog`, `/blog/a/b` | `$slug = []` or `['a', 'b']`      |
| `_anything`, `.anything`      | never a route        | partials, layouts, data, `_errors/`, `_middleware.php` |

- Static segments beat `[param]`, which beats `[...catchall]`, which beats `[[...optional]]`. So `users/create.php` wins over `users/[id].php`.
- Two files for one URL (`users.php` + `users/index.php`, or `[id].php` + `[slug].php` in the same folder) throw a `LogicException`. Pick one.
- Catch-alls must be the last segment. Param names are `[A-Za-z_][A-Za-z0-9_]*`.
- With Blade registered, `about.blade.php` is `/about` too. Without a Blade renderer, `.blade.php` files are skipped entirely.
- `$request` (a `Quire\Request`) is always in scope. Route params are also in `$request->params`.

## Writing a page

```php
<?php
use function Quire\{name, middleware, methods, abort, redirect, route, e};

name('users.edit');            // metadata block: must come first (see below)
middleware('auth');
methods('GET', 'POST');

$user = find_user($id) ?? abort(404, 'User not found');

if ($request->isMethod('POST')) {
    save_user($id, $request->input('name'));
    return redirect(route('users.show', ['id' => $id]));
}
?>
<h1>Edit <?= e($user['name']) ?></h1>
<form method="post">
    <input name="name" value="<?= e($user['name']) ?>">
    <button>Save</button>
</form>
```

What a page returns decides the response:

| Page does                        | Response                    |
| -------------------------------- | --------------------------- |
| echoes or outputs HTML           | 200 `text/html`             |
| `return ['ok' => true];`         | JSON                        |
| `return redirect('/x');`         | that `Quire\Response`       |
| `respond($response);`            | that response, from anywhere (also inside Blade and nested functions) |
| `abort(403, 'Message');`         | the matching error page     |

### The metadata block (most common mistake)

Quire reads `name()`, `middleware()` and `methods()` **without running the page**. It only reads the leading run of `use` statements and those three calls at the very top of the file. The first other statement ends the block.

- Import them with `use function Quire\{name, middleware, methods};`. Unqualified calls without the import fail with "Call to undefined function".
- Put nothing before them: no `$vars`, `require`, `if` or `echo`. Comments are fine.
- Pages are **GET only** (HEAD is automatic) unless `methods(...)` says otherwise. A POST to a GET page returns 405.
- Values must be literals or expressions that work without page variables: `middleware('role:admin')` is fine; `middleware($x)` is not.
- In Blade, put the same calls in an `@php ... @endphp` block (or `<?php ?>`) as the first thing in the file.

HTML forms can send PUT, PATCH or DELETE with a hidden `<input type="hidden" name="_method" value="DELETE">`, so declare `methods('POST', 'DELETE')` and similar.

## Middleware

A middleware is `function (Request $request, Closure $next, string ...$args): Response`. Return `$next($request)` to continue, or return your own response (a redirect, `abort()`) to stop. Not returning anything throws.

```php
// public/index.php
use Quire\{Request, Response};
use function Quire\{redirect, url, abort};

$quire->alias('auth', function (Request $request, Closure $next) {
    if (!$request->cookie('user')) {
        return redirect(url('/login') . '?next=' . rawurlencode($request->path));
    }
    $request->attributes['user'] = $request->cookie('user');   // hand data to the page
    return $next($request);
});

$quire->alias('role', function (Request $request, Closure $next, string ...$roles) {
    in_array($request->attributes['user'] ?? null, $roles, true) || abort(403);
    return $next($request);
});
```

`'role:admin,editor'` calls it with `$roles = ['admin', 'editor']`. Aliases can also be class names (with `handle()` or `__invoke`) or callables.

Attach middleware in one of four places. They run outermost first, in this order:

1. Global: `$quire->middleware('timing');`
2. By URL pattern, relative to the mount: `$quire->path($dir)->middleware(['admin/*' => ['auth'], '*' => ['log']]);` (`'admin/*'` does not match `/admin` itself; use `['admin', 'admin/*']` or `'admin*'`)
3. A folder's `_middleware.php`, which covers that folder and all subfolders: `<?php return ['auth', 'role:admin'];`
4. The page's metadata block: `middleware('auth');`

Prefer `_middleware.php` for "everything under this folder", and page metadata for one page.

## Links and redirects

- `route('users.show', ['id' => 5])` → `/users/5`. Extra params become the query string. Catch-alls take an array: `route('docs', ['slug' => ['a', 'b']])`.
- `url('/login')` adds the base path. `route()` adds it too.
- `redirect($to)` uses `$to` exactly as given. Build it with `route()` or `url()` so the base path is included.
- Never redirect straight to a user-supplied `next` value. Allow it only if it starts with `/` and not `//`.

## Error pages

`abort($status, $message = '', $headers = [])` from a page or middleware renders, from the mount's root folder:

1. `_errors/{status}.php` or `_errors/{status}.blade.php` (such as `_errors/404.blade.php`)
2. otherwise `_errors/error.php` or `_errors/error.blade.php`
3. otherwise plain text

Error pages get `$status`, `$message`, `$exception` (a `Quire\HttpException`, with `->headers`) and `$request`. `$request->route` is null on a 404. Uncaught exceptions become 500. The real message is shown only with `$quire->debug()`, which must stay off in production.

## Blade

Requires `illuminate/view` and `illuminate/events`, plus this in the front controller:

```php
$blade = new Quire\Blade\BladeRenderer(viewPaths: $pagesDir, cachePath: $storageDir . '/views');
$blade->components($pagesDir . '/_components');   // <x-card> → pages/_components/card.blade.php
$quire->extension('.blade.php', $blade);
```

```blade
@php
    use function Quire\{name, middleware};

    name('team.manage');
    middleware('auth');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Manage team')

@section('content')
    <h1>Hi {{ $request->attributes['user'] }}</h1>
    <a href="{{ route('team.index') }}">Back</a>
@endsection
```

Blade rules that bite:

- Layouts, partials and components go in `_` folders: `@extends('_layouts.app')` loads `pages/_layouts/app.blade.php`.
- `@use('function Quire\route')` imports only into the current file. A layout's imports don't reach the page, so each file imports what it calls.
- A Blade page can't `return` a response. Call `respond(redirect(...))` or `abort(...)` instead.
- Never write `@php`, or component tags like `<x-foo>`, inside `{{-- comments --}}`. Blade still compiles them.
- `__DIR__` and `__FILE__` point at the `.blade.php` file, not the compiled cache.
- `{{ }}` escapes output. Use `{!! !!}` only for trusted HTML.

## Testing

Call `handle()`. It returns a `Response` without sending anything:

```php
$response = $quire->handle(Quire\Request::create('POST', '/login', ['user' => 'bob'], cookies: ['session' => 'x']));
assert($response->status === 302);
assert($response->getHeader('Location') === '/');
```

## Checklist for a change

1. New URL → create the file at the matching path. Renamed URL → move the file, then update every `route()` call that uses its name.
2. Anything other than GET → add `methods(...)`.
3. Protected page → `middleware(...)` in the metadata block, or `_middleware.php` for a whole folder.
4. Linked from elsewhere → give it a `name()` and link with `route()`.
5. Missing record → `abort(404, '...')`. Check that `_errors/404` exists.
6. Escape output with `e()` in PHP pages; Blade's `{{ }}` escapes for you.
7. Run the project's tests, or call `$quire->handle(Request::create(...))` for the new route.

See [reference.md](reference.md) for the full API: `Quire`, `Mount`, `Request`, `Response`, `BladeRenderer` and the helper functions.
