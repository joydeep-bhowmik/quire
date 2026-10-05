@extends('_layouts.app')

@section('title', 'Not found')

@section('content')
    <x-error-page :status="$status" title="Page not found" :message="$message"
        :fallback="'Nothing lives at ' . $request->path . '.'">
        <x-error-action onclick="history.back()">Go back</x-error-action>
    </x-error-page>
@endsection
