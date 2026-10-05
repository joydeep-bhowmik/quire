@extends('_layouts.app')

@section('title', 'Unauthorized')

@section('content')
    <x-error-page :status="$status" title="Please sign in" :message="$message"
        fallback="You need to be signed in to see this page." />
@endsection
