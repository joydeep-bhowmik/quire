@props(['status', 'title', 'message' => '', 'fallback' => ''])
@use('function Quire\route')

@php
    // A custom message from abort(404, 'User not found') wins; the bare reason phrase ("Not Found") doesn't.
    $text = $message !== '' && $message !== \Quire\Response::reason((int) $status) ? $message : $fallback;
@endphp

<div class="py-16 text-center">
    <p class="font-mono text-sm font-semibold text-indigo-600 dark:text-indigo-400">{{ $status }}</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 sm:text-4xl dark:text-white">{{ $title }}</h1>

    @if ($text !== '')
        <p class="mx-auto mt-4 max-w-md text-slate-600 dark:text-slate-400">{{ $text }}</p>
    @endif

    <div class="mt-8 flex flex-wrap justify-center gap-3">
        {{ $slot }}
        <a href="{{ route('home') }}" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500">
            Go home
        </a>
    </div>
</div>
