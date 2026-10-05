@extends('_layouts.app')

@section('title', 'Too many requests')

@section('content')
    <x-error-page :status="$status" title="Slow down" :message="$message"
        :fallback="'You\'ve made too many requests.'
            . (isset($exception->headers['Retry-After']) ? ' Try again in ' . $exception->headers['Retry-After'] . ' seconds.' : ' Wait a moment and try again.')">
        <x-error-action :href="$request->path">Try again</x-error-action>
    </x-error-page>
@endsection
