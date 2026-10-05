@extends('_layouts.app')

@section('title', 'Forbidden')

@section('content')
    <x-error-page :status="$status" title="Access denied" :message="$message"
        fallback="You don't have permission to view this page." />
@endsection
