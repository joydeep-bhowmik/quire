{{-- Catch-all for any status without its own page in _errors/. --}}
@extends('_layouts.app')

@section('title', 'Error ' . $status)

@section('content')
    <x-error-page :status="$status" :title="\Quire\Response::reason($status)" :message="$message"
        fallback="Something didn't work as expected." />
@endsection
