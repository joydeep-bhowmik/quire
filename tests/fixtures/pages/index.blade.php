@php
    use function Quire\name;

    name('home');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Home')

@section('content')
    <h1>Quire</h1>
    <p>Every file in <code>pages/</code> is a route. This page is <code>pages/index.blade.php</code>.</p>

    <x-card title="Plain PHP or Blade" :href="route('about')">
        Pages can be <code>.php</code> or <code>.blade.php</code>, side by side.
    </x-card>

    <x-card title="Users" :href="route('users.index')">
        A list page and a <code>[id]</code> page, both on this layout.
    </x-card>
@endsection
