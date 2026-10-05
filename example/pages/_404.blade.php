@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Not found')

@section('content')
    <div class="py-16 text-center">
        <p class="text-sm font-semibold text-indigo-600 dark:text-indigo-400">404</p>
        <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $message }}</h1>
        <p class="mt-3 text-slate-600 dark:text-slate-400">Nothing lives at <code class="font-mono text-sm">{{ $request->path }}</code>.</p>
        <a href="{{ route('home') }}" class="mt-8 inline-block rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Go home</a>
    </div>
@endsection
