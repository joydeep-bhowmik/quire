@php
    use function Quire\name;

    name('about');
@endphp
@extends('_layouts.app')

@section('title', 'About')

@section('content')
    <h1>About</h1>
    <p>This is <code>pages/about.blade.php</code>, served at <code>/about</code>.</p>
    <p>It extends <code>pages/_layouts/app.blade.php</code>. Folders starting with <code>_</code> are never routes,
        so layouts, partials and components can live next to the pages.</p>
@endsection
