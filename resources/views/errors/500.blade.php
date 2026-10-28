@extends('errors.layout')
@section('title', '500 Server Error')

@section('content')
    <span class="error-icon">⚠️</span>
    <div class="error-code">500</div>
    <h1 class="error-title">Server Error</h1>
    <p class="error-desc">Something went wrong on our end. We're working to fix it. Please try again in a few moments.</p>
    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn-primary">Go to Home</a>
        <a href="javascript:location.reload()" class="btn-secondary">Refresh Page</a>
    </div>
@endsection
