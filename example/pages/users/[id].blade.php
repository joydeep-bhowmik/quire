@php
    use function Quire\name;

    name('users.show');

    $user = user($id); // 404s for unknown ids (see helpers.php)
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', $user['name'])

@section('content')
    <a href="{{ route('users.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">← All users</a>

    <div class="mt-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="h-24 bg-gradient-to-r from-indigo-500 to-violet-500"></div>
        <div class="px-6 pb-6">
            <x-avatar :name="$user['name']" size="lg" class="-mt-10 ring-4 ring-white dark:ring-slate-900" />
            <h1 class="mt-4 text-2xl font-bold text-slate-900 dark:text-white">{{ $user['name'] }}</h1>

            <dl class="mt-6 grid gap-4 border-t border-slate-200 pt-6 text-sm sm:grid-cols-2 dark:border-slate-800">
                <div>
                    <dt class="text-slate-500">Email</dt>
                    <dd class="mt-1 font-medium"><a href="mailto:{{ $user['email'] }}" class="hover:text-indigo-600">{{ $user['email'] }}</a></dd>
                </div>
                <div>
                    <dt class="text-slate-500">User ID</dt>
                    <dd class="mt-1 font-mono font-medium">{{ $id }}</dd>
                </div>
            </dl>
        </div>
    </div>
@endsection
