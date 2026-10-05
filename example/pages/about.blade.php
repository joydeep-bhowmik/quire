@php
    use function Quire\name;

    name('about');
@endphp
@extends('_layouts.app')

@section('title', 'About')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">About</h1>
    <p class="mt-3 text-slate-600 dark:text-slate-400">
        Every file in <code class="font-mono text-sm">pages/</code> is a route. Files and folders starting with
        <code class="font-mono text-sm">_</code> are not, so layouts and components can sit right next to the pages.
    </p>

    <div class="mt-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 px-5 py-3 text-sm font-semibold dark:border-slate-800">This demo</div>
        <pre class="overflow-x-auto bg-slate-900 p-5 font-mono text-sm leading-relaxed text-slate-300"><span class="text-slate-500">example/</span>
├── app.css                    <span class="text-slate-500">Tailwind entry</span>
├── postcss.config.mjs
├── data/users.php
├── public/index.php
└── pages/
    ├── <span class="text-slate-500">_layouts/app.blade.php</span>
    ├── <span class="text-slate-500">_components/avatar.blade.php</span>
    ├── index.blade.php        <span class="text-indigo-400">→ /</span>
    ├── about.blade.php        <span class="text-indigo-400">→ /about</span>
    └── users/
        ├── index.blade.php    <span class="text-indigo-400">→ /users</span>
        └── [id].blade.php     <span class="text-indigo-400">→ /users/{id}</span></pre>
    </div>
@endsection
