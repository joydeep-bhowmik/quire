@extends('_layouts.app')

@section('title', 'Bad request')

@section('content')
    <x-error-page :status="$status" title="Bad request" :message="$message"
        fallback="The server couldn't understand that request. Check the address or form and try again.">
        <x-error-action onclick="history.back()">Go back</x-error-action>
    </x-error-page>
@endsection
