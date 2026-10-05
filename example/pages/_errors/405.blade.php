@extends('_layouts.app')

@section('title', 'Method not allowed')

@section('content')
    <x-error-page :status="$status" title="Method not allowed" :message="$message"
        :fallback="'This page doesn\'t accept ' . $request->method . ' requests.'
            . (isset($exception->headers['Allow']) ? ' Allowed: ' . $exception->headers['Allow'] . '.' : '')" />
@endsection
