<?php
use function Quire\{name, abort, redirect, respond, route};

name('team.show');

// Old URL? Blade can't `return` a response, so use respond() to send one early.
if ($member === 'lovelace') {
    respond(redirect(route('team.show', ['member' => 'ada']), 301));
}

$person = (require PAGES . '/_data/team.php')[$member] ?? abort(404, "Nobody called {$member} here");
?>
@extends('_layouts.app')

@section('title', $person['name'])

@section('content')
    <h1>{{ $person['name'] }}</h1>
    <p>{{ $person['role'] }}</p>
    <p>Route param <code>$member</code> = <code>{{ $member }}</code>, path <code>{{ $request->path }}</code>.</p>
@endsection
