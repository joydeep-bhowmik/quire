@php
    use function Quire\name;

    name('users.index');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Users')

@section('content')
    <h1>Users</h1>

    @foreach (require PAGES . '/_data/users.php' as $id => $user)
        <x-card :title="$user['name']" :href="route('users.show', ['id' => $id])">
            {{ $user['email'] }}
        </x-card>
    @endforeach
@endsection
