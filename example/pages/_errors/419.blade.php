@extends('_layouts.app')

@section('title', 'Page expired')

@section('content')
    <x-error-page :status="$status" title="Page expired" :message="$message"
        fallback="Your session expired before the form was sent. Reload the page and try again.">
        <x-error-action onclick="history.back()">Go back</x-error-action>
    </x-error-page>
@endsection
