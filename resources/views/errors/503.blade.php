@extends('errors.layout')
@section('title', '503 Maintenance')

@section('content')
    <span class="error-icon">🔧</span>
    <div class="error-code">503</div>
    <h1 class="error-title">Under Maintenance</h1>
    <p class="error-desc">We're performing scheduled maintenance to improve your experience. Please check back soon.</p>
    <div class="error-actions">
        <a href="javascript:location.reload()" class="btn-primary">Try Again</a>
    </div>
@endsection
