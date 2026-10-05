@use('function Quire\route')
<!doctype html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Quire</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="/css/app.css?v={{ @filemtime(__DIR__ . '/../../public/css/app.css') }}">
</head>
<body class="flex min-h-full flex-col bg-slate-50 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
    <header class="sticky top-0 z-10 border-b border-slate-200 bg-white/80 backdrop-blur dark:border-slate-800 dark:bg-slate-900/80">
        <div class="mx-auto flex max-w-4xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold text-slate-900 dark:text-white">
                <span class="grid size-8 place-items-center rounded-lg bg-indigo-600 text-sm font-bold text-white">Q</span>
                Quire
            </a>

            <nav class="flex gap-1 text-sm font-medium">
                @foreach (['home' => 'Home', 'about' => 'About', 'users.index' => 'Users'] as $name => $label)
                    @php
                        // "users.index" stays highlighted on "users.show" too.
                        $active = explode('.', $request->route?->name() ?? '')[0] === explode('.', $name)[0];
                    @endphp
                    <a href="{{ route($name) }}"
                       @class([
                           'rounded-md px-3 py-2 transition-colors',
                           'bg-indigo-50 text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300' => $active,
                           'text-slate-600 hover:bg-slate-100 hover:text-slate-900 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white' => !$active,
                       ])
                       @if ($active) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-4xl flex-1 px-4 py-12">
        @yield('content')
    </main>

    <footer class="border-t border-slate-200 py-6 text-center text-sm text-slate-500 dark:border-slate-800">
        Built with Quire, Blade &amp; Tailwind CSS
    </footer>
</body>
</html>
