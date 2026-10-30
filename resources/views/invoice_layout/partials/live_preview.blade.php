<link rel="stylesheet" href="{{ asset('css/invoice-layout-preview.css?v=20261030f') }}">
<style>
#qposPreviewStage{height:75vh !important;min-height:560px !important;overflow:auto !important;background:#3f3f46 !important;}
#qposPreviewScaler{width:min(100%,440px);margin:0 auto;}
#qposPreviewFrame{display:block;width:400px;height:1680px;min-height:1680px;border:0;background:#fff;color-scheme:light;margin:0 auto;}
#qposPreviewStatus{font-weight:700;color:#fff;}
#qposLivePreview .qpos-live-preview-head,
#qposLivePreview .qpos-live-preview-tools{position:relative;z-index:40;pointer-events:auto;}
#qposLivePreview .qpos-live-preview-tools button,
#qposLivePreview .qpos-live-preview-tools select{pointer-events:auto !important;cursor:pointer;position:relative;z-index:41;}
#qposLivePreview [data-qpos-mode].is-on{background:#2563eb !important;color:#fff !important;border-color:#93c5fd !important;}
#qposZoomLabel{display:inline-block;min-width:46px;text-align:center;font-weight:800;color:#fff;background:#111;border-radius:6px;padding:2px 6px;line-height:20px;}
</style>
<div id="qposLivePreview" class="qpos-live-preview" data-url="{{ route('invoice-layouts.live-preview') }}">
    @if(!empty($invoice_layout->id))
        <input type="hidden" name="layout_id" value="{{ $invoice_layout->id }}">
    @endif
    <div class="qpos-live-preview-head">
        <div>
            <div class="qpos-live-preview-title">@lang('invoice.live_invoice_preview')</div>
            <div class="qpos-live-preview-note">@lang('invoice.preview_sample_note')</div>
        </div>
        <span class="qpos-live-preview-status" id="qposPreviewStatus"></span>
    </div>
    <div class="qpos-live-preview-tools">
        <label class="qpos-tool">
            <span>@lang('lang_v1.design')</span>
            <select id="qposPreviewDesign" class="form-control input-sm">
                @foreach($designs as $key => $label)
                    <option value="{{ $key }}" @if(isset($invoice_layout) && $invoice_layout->design === $key) selected @elseif(!isset($invoice_layout) && $key === 'classic') selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="qpos-tool">
            <span>@lang('invoice.preview_document')</span>
            <select name="preview_document" id="qposPreviewDocument" class="form-control input-sm">
                <option value="invoice_unpaid">@lang('invoice.preview_unpaid')</option>
                <option value="invoice_paid">@lang('invoice.preview_paid')</option>
                <option value="quotation">@lang('invoice.preview_quotation')</option>
                <option value="sales_order">@lang('invoice.preview_sales_order')</option>
                <option value="proforma">@lang('invoice.preview_proforma')</option>
            </select>
        </label>
        <div class="qpos-tool qpos-tool-modes">
            <span>@lang('invoice.print_appearance')</span>
            <div class="qpos-preview-modes">
                <button type="button" class="btn btn-default btn-xs" data-qpos-mode="colour" data-qpos-action="colour">@lang('invoice.print_appearance_colour')</button>
                <button type="button" class="btn btn-default btn-xs" data-qpos-mode="black_white" data-qpos-action="black_white">@lang('invoice.print_appearance_bw')</button>
            </div>
        </div>
        <div class="qpos-tool qpos-tool-zoom">
            <span>@lang('invoice.preview_zoom')</span>
            <div>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewZoomOut" data-qpos-action="zoom-out" title="@lang('invoice.preview_zoom_out')">−</button>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewFit" data-qpos-action="fit">@lang('invoice.preview_fit')</button>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewZoomIn" data-qpos-action="zoom-in" title="@lang('invoice.preview_zoom_in')">+</button>
                <span id="qposZoomLabel">100%</span>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewRefresh" data-qpos-action="refresh">@lang('invoice.preview_refresh')</button>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewTop" data-qpos-action="top">@lang('invoice.preview_top')</button>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewBottom" data-qpos-action="bottom">@lang('invoice.preview_bottom')</button>
                <button type="button" class="btn btn-default btn-xs" id="qposPreviewExpand" data-qpos-action="expand">@lang('invoice.preview_expand')</button>
                <button type="submit" class="btn btn-primary btn-xs" form="add_invoice_layout_form">@lang('invoice.designer_save')</button>
            </div>
        </div>
    </div>
    <div class="qpos-preview-stage" id="qposPreviewStage">
        <p id="qposPreviewMessage" class="qpos-preview-message" hidden></p>
        <div class="qpos-preview-scaler" id="qposPreviewScaler">
            <iframe id="qposPreviewFrame" title="@lang('invoice.live_invoice_preview')" scrolling="yes" height="1680" width="400" @if(!empty($preview_html)) srcdoc="{{ $preview_html }}" @endif></iframe>
        </div>
    </div>
</div>
@php
    $qposPreviewJsPath = public_path('js/invoice-layout-preview.js');
    $qposPreviewJs = is_file($qposPreviewJsPath) ? file_get_contents($qposPreviewJsPath) : '';
@endphp
@if($qposPreviewJs !== '')
<script>{!! $qposPreviewJs !!}</script>
@endif
