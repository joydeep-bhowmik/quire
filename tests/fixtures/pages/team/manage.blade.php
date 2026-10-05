@php
    use function Quire\{name, middleware};

    name('team.manage');
    middleware('auth');
@endphp
@extends('_layouts.app')

@section('title', 'Manage team')

@section('content')
    <h1>Manage team</h1>
    <p>Hi {{ $request->attributes['user'] }}, middleware let you in.</p>
@endsection
