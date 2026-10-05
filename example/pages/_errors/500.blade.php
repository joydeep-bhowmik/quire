@extends('_layouts.app')

@section('title', 'Server error')

@section('content')
    {{-- With $quire->debug() on, $message is the real exception message. --}}
    <x-error-page :status="$status" title="Something went wrong" :message="$message"
        fallback="An unexpected error happened on our side. Please try again later.">
        <x-error-action :href="$request->path">Try again</x-error-action>
    </x-error-page>
@endsection
