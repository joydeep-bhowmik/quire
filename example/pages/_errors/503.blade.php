@extends('_layouts.app')

@section('title', 'Service unavailable')

@section('content')
    <x-error-page :status="$status" title="Back soon" :message="$message"
        fallback="We're down for maintenance or under heavy load. Please check back in a few minutes.">
        <x-error-action :href="$request->path">Try again</x-error-action>
    </x-error-page>
@endsection
