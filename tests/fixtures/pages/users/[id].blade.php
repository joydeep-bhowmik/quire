<?php
use function Quire\{name, abort};

name('users.show');

$user = (require PAGES . '/_data/users.php')[$id] ?? abort(404, "No user with id {$id}");
?>
@use('function Quire\route')
@extends('_layouts.app')

@section('title', $user['name'])

@section('content')
    @if (isset($request->query['saved']))
        <p class="flash">Saved!</p>
    @endif

    <h1>{{ $user['name'] }}</h1>
    <p>Email: {{ $user['email'] }}</p>
    <p>
        <a href="{{ route('users.edit', ['id' => $id]) }}">Edit</a> (requires login)
        · <a href="{{ route('users.index') }}">All users</a>
    </p>
@endsection
