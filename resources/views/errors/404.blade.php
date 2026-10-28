@extends('errors.layout')
@section('title', '404 Not Found')

@section('content')
    <span class="error-icon">🔍</span>
    <div class="error-code">404</div>
    <h1 class="error-title">Page Not Found</h1>
    <p class="error-desc">The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.</p>
    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn-primary">Go to Home</a>
        <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
    </div>
@endsection
