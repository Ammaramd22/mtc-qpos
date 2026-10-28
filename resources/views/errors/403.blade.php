@extends('errors.layout')
@section('title', '403 Forbidden')

@section('content')
    <span class="error-icon">🚫</span>
    <div class="error-code">403</div>
    <h1 class="error-title">Access Denied</h1>
    <p class="error-desc">You don't have permission to access this page. If you believe this is an error, please contact the administrator.</p>
    <div class="error-actions">
        <a href="{{ url('/') }}" class="btn-primary">Go to Home</a>
        <a href="javascript:history.back()" class="btn-secondary">Go Back</a>
    </div>
@endsection
