{{-- Page metadata goes in the first block of the file. --}}
@php
    use function Quire\name;

    name('team.index');
@endphp
@use('function Quire\route')
@extends('_layouts.app')

@section('title', 'Team')

@section('content')
    <h1>Team</h1>
    <p>This page is <code>pages/team/index.blade.php</code>.</p>

    @foreach (require PAGES . '/_data/team.php' as $slug => $person)
        <x-card :title="$person['name']" :href="route('team.show', ['member' => $slug])">
            {{ $person['role'] }}
        </x-card>
    @endforeach

    <p><a href="{{ route('team.manage') }}">Manage team</a> (requires login)</p>
@endsection
