@use('function Quire\route')
@use('function Quire\url')
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Quire') · Quire</title>
    <style>
        body { font: 16px/1.5 system-ui, sans-serif; max-width: 46rem; margin: 2rem auto; padding: 0 1rem; color: #222; }
        nav { display: flex; flex-wrap: wrap; gap: .25rem 1rem; padding-bottom: 1rem; border-bottom: 1px solid #eee; }
        nav a[aria-current] { font-weight: 600; }
        code { background: #f3f3f3; padding: .1rem .3rem; border-radius: 3px; }
        .card { border: 1px solid #ddd; border-radius: 6px; padding: .75rem 1rem; margin: .75rem 0; }
        .flash { background: #e8f6ec; border: 1px solid #b6e2c3; padding: .5rem 1rem; border-radius: 6px; }
        footer { margin-top: 3rem; padding-top: 1rem; border-top: 1px solid #eee; color: #777; font-size: .875rem; }
    </style>
</head>
<body>
<nav>
    @foreach (['home' => 'Home', 'about' => 'About', 'users.index' => 'Users', 'team.index' => 'Team', 'blog' => 'Blog', 'admin' => 'Admin', 'login' => 'Login'] as $routeName => $label)
        <a href="{{ route($routeName) }}"{!! $request->route?->name() === $routeName ? ' aria-current="page"' : '' !!}>{{ $label }}</a>
    @endforeach
</nav>

<main>
    @yield('content')
</main>

<footer>
    Served by <code>{{ str_replace(PAGES, 'pages', $request->route?->file ?? '') }}</code>
</footer>
</body>
</html>
