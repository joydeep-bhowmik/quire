@php
    use function Quire\name;

    name('users.index');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Users')

@section('content')
    <h1 class="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">Users</h1>
    <p class="mt-2 text-slate-600 dark:text-slate-400">Each one links to <code class="font-mono text-sm">users/[id].blade.php</code>.</p>

    <ul class="mt-8 grid gap-3 sm:grid-cols-2">
        @foreach (users() as $id => $user)
            <li>
                <a href="{{ route('users.show', ['id' => $id]) }}"
                   class="group flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/50">
                    <x-avatar :name="$user['name']" />
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold text-slate-900 dark:text-white">{{ $user['name'] }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $user['email'] }}</p>
                    </div>
                    <span class="text-slate-400 transition group-hover:translate-x-0.5 group-hover:text-indigo-500">→</span>
                </a>
            </li>
        @endforeach
    </ul>
@endsection
