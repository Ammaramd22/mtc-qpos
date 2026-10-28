@extends('layouts.app')
@section('title', __('report.reports'))

@section('css')
<style>
.qpos-report-hub {
    --qrh-card: #141414;
    --qrh-border: #2a2a2a;
    --qrh-text: #f5f5f5;
    --qrh-muted: #a3a3a3;
    --qrh-icon-bg: #1c1c1c;
    --qrh-accent: #f5c518;
    max-width: 1100px;
}
html[data-vspos-theme="light"] .qpos-report-hub {
    --qrh-card: #ffffff;
    --qrh-border: #e6e6e6;
    --qrh-text: #111111;
    --qrh-muted: #525252;
    --qrh-icon-bg: #f4f4f4;
}
.qpos-report-hub-header {
    padding-bottom: 0;
}
.qpos-report-hub-header h1 {
    margin-bottom: 4px;
}
.qrh-lead {
    margin: 0 0 4px;
    max-width: 36rem;
    color: var(--qrh-muted, #a3a3a3);
    font-size: 14px;
    line-height: 1.45;
}
html[data-vspos-theme="light"] .qrh-lead {
    color: #525252 !important;
}
html[data-vspos-theme="dark"] .qrh-lead,
html:not([data-vspos-theme]) .qrh-lead {
    color: #a3a3a3 !important;
}
.qrh-section {
    margin: 18px 0 0;
}
.qrh-section h2 {
    margin: 0 0 10px;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--qrh-muted, #a3a3a3) !important;
}
.qrh-grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr);
    gap: 10px;
}
.qrh-card {
    display: flex;
    align-items: center;
    gap: 12px;
    min-width: 0;
    min-height: 64px;
    padding: 12px 14px;
    border: 1px solid var(--qrh-border);
    border-radius: 14px;
    background: var(--qrh-card);
    color: var(--qrh-text) !important;
    text-decoration: none !important;
    box-shadow: none;
}
.qrh-card:hover,
.qrh-card:focus {
    border-color: var(--qrh-accent);
    color: var(--qrh-text) !important;
    text-decoration: none !important;
    outline: none;
}
.qrh-card:focus-visible {
    box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.28);
}
.qrh-card.is-current {
    border-color: var(--qrh-accent);
}
.qrh-icon {
    flex: 0 0 36px;
    width: 36px;
    height: 36px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    background: var(--qrh-icon-bg);
    color: var(--qrh-accent);
}
.qrh-icon svg {
    width: 20px;
    height: 20px;
}
.qrh-title {
    flex: 1 1 auto;
    min-width: 0;
    font-size: 15px;
    font-weight: 600;
    line-height: 1.3;
    white-space: normal;
    overflow-wrap: anywhere;
    word-break: break-word;
    color: inherit !important;
}
.qrh-chevron {
    flex: 0 0 auto;
    color: var(--qrh-muted);
}
.qrh-empty {
    margin-top: 16px;
    padding: 16px;
    border: 1px dashed var(--qrh-border);
    border-radius: 14px;
    color: var(--qrh-muted) !important;
}
@media (min-width: 480px) {
    .qrh-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (min-width: 768px) {
    .qrh-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); }
}
@media (min-width: 1100px) {
    .qrh-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
}
@media (max-width: 991.98px) {
    .qpos-report-hub {
        padding-bottom: 8px;
    }
}
</style>
@endsection

@section('content')
<section class="content-header qpos-report-hub-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold">@lang('report.reports')</h1>
    <p class="qrh-lead">@lang('report.reports_hub_lead')</p>
</section>

<section class="content qpos-report-hub">
    @forelse ($groups as $group)
        <div class="qrh-section">
            <h2>{{ $group['label'] }}</h2>
            <div class="qrh-grid">
                @foreach ($group['items'] as $item)
                    <a href="{{ $item['url'] }}" class="qrh-card {{ !empty($item['active']) ? 'is-current' : '' }}" @if(!empty($item['active'])) aria-current="page" @endif>
                        <span class="qrh-icon" aria-hidden="true">{!! $item['icon'] !!}</span>
                        <span class="qrh-title">{{ $item['title'] }}</span>
                        <span class="qrh-chevron" aria-hidden="true">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 6l6 6-6 6"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @empty
        <p class="qrh-empty">@lang('report.reports_hub_empty')</p>
    @endforelse
</section>
@endsection
