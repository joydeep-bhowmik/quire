@php
    use function Quire\name;

    name('home');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Home')

@section('content')
    <section class="text-center">
        <span class="inline-block rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-300">
            File-based routing for PHP
        </span>
        <h1 class="mt-4 text-4xl font-bold tracking-tight text-slate-900 sm:text-5xl dark:text-white">
            Drop a file in <code class="text-indigo-600 dark:text-indigo-400">pages/</code>,<br> get a route.
        </h1>
        <p class="mx-auto mt-4 max-w-xl text-lg text-slate-600 dark:text-slate-400">
            This page is <code class="rounded bg-slate-100 px-1.5 py-0.5 text-sm dark:bg-slate-800">pages/index.blade.php</code>.
            No route file, no controller.
        </p>
        <div class="mt-8 flex justify-center gap-3">
            <a href="{{ route('users.index') }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
                Browse users
            </a>
            <a href="{{ route('about') }}" class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                How it works
            </a>
        </div>
    </section>

    <section class="mt-16 grid gap-4 sm:grid-cols-3">
        @foreach ([
            ['Pages are routes', 'about.blade.php becomes /about. users/[id].blade.php becomes /users/{id}.'],
            ['Middleware', 'Attach it globally, per folder with _middleware.php, or at the top of a page.'],
            ['Blade layouts', 'Every page here extends _layouts/app.blade.php and is styled with Tailwind.'],
        ] as [$title, $body])
            <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
                <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">{{ $body }}</p>
            </div>
        @endforeach
    </section>
@endsection
