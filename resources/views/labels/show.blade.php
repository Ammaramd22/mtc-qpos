@extends('layouts.app')
@section('title', __('barcode.print_labels'))

@section('content')

<section class="content-header">
<br>
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-white">
        @lang('barcode.print_labels')
        @if(session('business.enable_tooltip'))
            <i class="fa fa-info-circle text-info labels-info-tip popover-default no-print" aria-hidden="true"></i>
        @endif
    </h1>
</section>

<div id="labels-guide-popover-content" class="hide">
    <div class="labels-guide-popover-inner">
        <div class="labels-guide-popover-title">@lang('lang_v1.print_labels_guide_title')</div>
        <ol class="labels-guide-popover-steps">
            <li>@lang('lang_v1.print_labels_guide_step_1')</li>
            <li>@lang('lang_v1.print_labels_guide_step_2')</li>
            <li>@lang('lang_v1.print_labels_guide_step_3')</li>
            <li>@lang('lang_v1.print_labels_guide_step_4')</li>
        </ol>
        <p class="labels-guide-popover-note">{!! __('lang_v1.print_labels_guide_settings', ['link' => '<a href="' . action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) . '#settings-bartender">' . __('lang_v1.bartender_settings') . '</a>']) !!}</p>
    </div>
</div>

<section class="content no-print">
	{!! Form::open(['url' => '#', 'method' => 'post', 'id' => 'labels_export_form', 'onsubmit' => 'return false']) !!}

	@component('components.widget', ['class' => 'box-primary', 'title' => __('product.add_product_for_labels')])
		<div class="labels-add-panel">
			<div class="row">
				<div class="col-sm-12">
					<label class="labels-search-label">@lang('lang_v1.search_product')</label>
					<div class="labels-search-wrap">
						<span class="labels-search-icon"><i class="fa fa-search"></i></span>
						{!! Form::text('search_product', null, ['class' => 'form-control labels-search-input', 'id' => 'search_product_for_label', 'placeholder' => __('lang_v1.enter_product_name_to_print_labels'), 'autofocus', 'autocomplete' => 'off', 'autocorrect' => 'off', 'spellcheck' => 'false', 'role' => 'combobox', 'aria-autocomplete' => 'list', 'aria-expanded' => 'false']); !!}
					</div>
					<p class="labels-search-status" id="labels_search_status" role="status"></p>
					<p class="labels-search-hint">@lang('lang_v1.label_search_hint')</p>
				</div>
			</div>

			<div class="row labels-table-wrap">
				<div class="col-sm-12">
					<div class="table-responsive">
						<table class="table table-bordered labels-product-table" id="product_table">
							<thead>
								<tr>
									<th>@lang('barcode.products')</th>
									<th class="labels-qty-col">@lang('barcode.no_of_labels')</th>
									@if(request()->session()->get('business.enable_lot_number') == 1)
										<th>@lang('lang_v1.lot_number')</th>
									@endif
									@if(request()->session()->get('business.enable_product_expiry') == 1)
										<th>@lang('product.exp_date')</th>
									@endif
									<th class="labels-action-col"></th>
								</tr>
							</thead>
							<tbody id="labels_product_tbody">
								@include('labels.partials.show_table_rows', ['index' => 0])
							</tbody>
						</table>
					</div>
					<div id="labels_empty_state" class="labels-empty-state @if(!empty($products) && count($products)) hide @endif">
						<i class="fa fa-tags"></i>
						<p>@lang('lang_v1.label_list_empty')</p>
					</div>
				</div>
			</div>

			<div class="row labels-actions-row">
				<div class="col-sm-12 text-center labels-btn-row">
					<input type="hidden" id="bartender_batch_launcher_url" value="{{ $bartender_batch_launcher_url ?? '' }}">
					<input type="hidden" id="labels_auto_export" value="{{ request('auto_export') ? '1' : '0' }}">
					<button type="button" id="labels_browser_print_v2" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-lg tw-text-white">
						<i class="fa fa-print"></i> @lang('lang_v1.browser_print_labels')
					</button>
					@if($enable_bartender)
					<button type="button" id="labels_export_csv" class="tw-dw-btn tw-dw-btn-accent tw-dw-btn-lg tw-text-white">
						<i class="fa fa-download"></i> @lang('lang_v1.export_label_data')
					</button>
					@else
					<p class="labels-export-disabled-hint" style="margin-top:8px;">
						@lang('lang_v1.enable_label_export_in_settings')
						<a href="{{ action([\App\Http\Controllers\BusinessController::class, 'getBusinessSettings']) }}#settings-bartender">@lang('lang_v1.bartender_settings')</a>
					</p>
					@endif
				</div>
			</div>
		</div>
	@endcomponent
	{!! Form::close() !!}

</section>

<style>
.labels-info-tip {
    font-size: 18px;
    margin-left: 6px;
    cursor: pointer;
    vertical-align: middle;
}
.labels-guide-popover {
    max-width: 360px;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    box-shadow: 0 12px 32px rgba(15, 23, 42, .12);
    background: #ffffff;
}
html[data-vspos-theme="dark"] .labels-guide-popover {
    border-color: rgba(125, 211, 252, .35);
    box-shadow: 0 12px 32px rgba(0, 0, 0, .4);
    background: #0f172a;
}
.labels-guide-popover .popover-content {
    padding: 0;
    background: transparent;
    border-radius: 12px;
}
.labels-guide-popover.bottom > .arrow:after {
    border-bottom-color: #ffffff;
}
.labels-guide-popover.top > .arrow:after {
    border-top-color: #ffffff;
}
html[data-vspos-theme="dark"] .labels-guide-popover.bottom > .arrow:after {
    border-bottom-color: #0f172a;
}
html[data-vspos-theme="dark"] .labels-guide-popover.top > .arrow:after {
    border-top-color: #0f172a;
}
.labels-guide-popover-inner {
    padding: 14px 16px;
    color: #111827;
}
html[data-vspos-theme="dark"] .labels-guide-popover-inner { color: #e2e8f0; }
.labels-guide-popover-title {
    font-weight: 600;
    color: #111827;
    margin-bottom: 8px;
    font-size: 14px;
}
html[data-vspos-theme="dark"] .labels-guide-popover-title { color: #f8fafc; }
.labels-guide-popover-steps {
    margin: 0 0 10px;
    padding-left: 18px;
    color: #374151;
    font-size: 13px;
}
html[data-vspos-theme="dark"] .labels-guide-popover-steps { color: #cbd5e1; }
.labels-guide-popover-steps li { margin-bottom: 4px; }
.labels-guide-popover-note {
    margin: 0;
    font-size: 12px;
    color: #4b5563;
    border-top: 1px solid rgba(148, 163, 184, .2);
    padding-top: 10px;
}
.labels-guide-popover-note a { color: #1d4ed8; text-decoration: underline; }
html[data-vspos-theme="dark"] .labels-guide-popover-note { color: #94a3b8; }
html[data-vspos-theme="dark"] .labels-guide-popover-note a { color: #7dd3fc; }
.labels-add-panel { padding: 4px 0 8px; }
.labels-search-label {
	display: block;
	font-weight: 600;
	color: #111827;
	margin-bottom: 8px;
}
html[data-vspos-theme="dark"] .labels-search-label { color: #e2e8f0; }
.labels-search-wrap {
	position: relative;
	max-width: 720px;
	margin: 0 auto;
}
.labels-search-icon {
	position: absolute;
	left: 14px;
	top: 50%;
	transform: translateY(-50%);
	color: #4b5563;
	z-index: 2;
}
html[data-vspos-theme="dark"] .labels-search-icon { color: #94a3b8; }
.labels-search-input {
	padding-left: 42px !important;
	height: 46px;
	border-radius: 10px !important;
	font-size: 15px;
}
.labels-search-hint {
	text-align: center;
	color: #4b5563;
	font-size: 13px;
	margin: 10px 0 20px;
}
html[data-vspos-theme="dark"] .labels-search-hint { color: #94a3b8; }
.labels-table-wrap { margin-top: 8px; }
.labels-product-table {
	margin-bottom: 0;
	border-radius: 10px;
	overflow: hidden;
}
.labels-product-table thead th {
	background: #f8fafc !important;
	color: #374151 !important;
	border-color: #e5e7eb !important;
	font-size: 12px;
	text-transform: uppercase;
	letter-spacing: .4px;
}
.labels-product-table tbody td {
	vertical-align: middle !important;
	background: #ffffff !important;
	color: #111827 !important;
	border-color: #e5e7eb !important;
}
.labels-product-table tbody tr:hover td {
	background: #f8fafc !important;
}
html[data-vspos-theme="dark"] .labels-product-table thead th {
	background: #0f172a !important;
	color: #f8fafc !important;
	border-color: rgba(148, 163, 184, .22) !important;
}
html[data-vspos-theme="dark"] .labels-product-table tbody td {
	background: #0b1220 !important;
	color: #e2e8f0 !important;
	border-color: rgba(148, 163, 184, .18) !important;
}
.labels-qty-col { width: 140px; }
.labels-action-col { width: 56px; text-align: center; }
.labels-product-name { font-weight: 600; color: #111827; }
.labels-product-variation { color: #4b5563; font-size: 12px; display: block; margin-top: 2px; }
.labels-product-attrs { color: #1d4ed8; font-size: 12px; display: block; margin-top: 2px; }
html[data-vspos-theme="dark"] .labels-product-name { color: #f8fafc; }
html[data-vspos-theme="dark"] .labels-product-variation { color: #94a3b8; }
html[data-vspos-theme="dark"] .labels-product-attrs { color: #7dd3fc; }
.labels-search-status {
	text-align: center;
	min-height: 0;
	margin: 6px 0 0;
	font-size: 13px;
	color: #4b5563;
}
html[data-vspos-theme="dark"] .labels-search-status { color: #94a3b8; }
.labels-search-status:empty { display: none; }
.labels-search-status.is-error { color: #fca5a5; }
.labels-search-wrap.is-loading .labels-search-icon i {
	display: inline-block;
	animation: labels-search-spin .7s linear infinite;
}
@keyframes labels-search-spin { to { transform: rotate(360deg); } }
.labels-row-flash td { background: rgba(56, 189, 248, .22) !important; }
.labels-suggest-menu.ui-autocomplete,
.ui-autocomplete.labels-suggest-menu {
	z-index: 10050 !important;
	max-height: 320px;
	overflow-y: auto;
	overflow-x: hidden;
	background: #ffffff;
	border: 1px solid #e5e7eb;
	border-radius: 10px;
	box-shadow: 0 12px 32px rgba(15, 23, 42, .12);
	padding: 4px 0;
}
html[data-vspos-theme="dark"] .labels-suggest-menu.ui-autocomplete,
html[data-vspos-theme="dark"] .ui-autocomplete.labels-suggest-menu {
	background: #0f172a;
	border-color: rgba(125, 211, 252, .35);
	box-shadow: 0 12px 32px rgba(0, 0, 0, .45);
}
.labels-suggest-menu .ui-menu-item-wrapper {
	padding: 0;
	border: 0 !important;
	color: #111827;
	background: transparent;
}
html[data-vspos-theme="dark"] .labels-suggest-menu .ui-menu-item-wrapper { color: #e2e8f0; }
.labels-suggest-menu .ui-menu-item-wrapper.ui-state-active,
.labels-suggest-menu .ui-state-active {
	background: #1d4ed8 !important;
	border: 0 !important;
	color: #fff !important;
	margin: 0;
}
.labels-suggest-item { padding: 8px 12px; line-height: 1.3; cursor: pointer; }
.labels-suggest-name { font-weight: 600; color: #111827; }
.labels-suggest-meta { font-size: 12px; color: #4b5563; margin-top: 2px; white-space: normal; }
html[data-vspos-theme="dark"] .labels-suggest-name { color: #f8fafc; }
html[data-vspos-theme="dark"] .labels-suggest-meta { color: #cbd5e1; }
.labels-suggest-status .labels-suggest-item { cursor: default; color: #94a3b8; }
.ui-state-active .labels-suggest-name,
.ui-state-active .labels-suggest-meta { color: #fff; }
@media (max-width: 640px) {
	.labels-suggest-menu.ui-autocomplete { max-width: calc(100vw - 24px) !important; }
}
.labels-empty-state {
	text-align: center;
	padding: 36px 16px;
	border: 1px dashed rgba(148, 163, 184, .35);
	border-radius: 12px;
	color: #4b5563;
	margin-top: 12px;
}
html[data-vspos-theme="dark"] .labels-empty-state { color: #94a3b8; }
.labels-empty-state i { font-size: 28px; margin-bottom: 10px; opacity: .7; }
.labels-empty-state p { margin: 0; }
.labels-actions-row { margin-top: 24px; padding-top: 16px; border-top: 1px solid rgba(148, 163, 184, .18); }
.labels-btn-row .tw-dw-btn { margin: 6px 8px; min-width: 200px; }
.labels-export-disabled-hint { color: #4b5563; margin: 8px 0 0; }
.labels-export-disabled-hint a { color: #1d4ed8; text-decoration: underline; }
html[data-vspos-theme="dark"] .labels-export-disabled-hint { color: #94a3b8; }
html[data-vspos-theme="dark"] .labels-export-disabled-hint a { color: #7dd3fc; }
.labels-remove-row {
	background: transparent;
	border: 1px solid #fecaca;
	color: #b91c1c;
	border-radius: 8px;
	width: 34px;
	height: 34px;
	padding: 0;
}
.labels-remove-row:hover { background: #fef2f2; color: #991b1b; }
html[data-vspos-theme="dark"] .labels-remove-row {
	border-color: rgba(248, 113, 113, .45);
	color: #fca5a5;
}
html[data-vspos-theme="dark"] .labels-remove-row:hover { background: rgba(248, 113, 113, .15); color: #fecaca; }
</style>

@stop
@section('javascript')
	<script type="text/javascript">
		window.qposBrowserLabelLayout = @json($browser_label_layout ?? []);
	</script>
	{{-- Cache-bust: Cloudflare/LiteSpeed cache JS for 7 days; unique query forces fresh file --}}
	<script src="{{ asset('js/labels.js?v=' . $asset_v . '-labelsearch1-lt1') }}"></script>
	<script type="text/javascript">
		$(document).ready(function() {
			var $tip = $('.labels-info-tip');
			if ($tip.length) {
				$tip.popover({
					html: true,
					trigger: 'hover',
					placement: 'auto bottom',
					container: 'body',
					template: '<div class="popover labels-guide-popover" role="tooltip"><div class="arrow"></div><div class="popover-content"></div></div>',
					content: function() {
						return $('#labels-guide-popover-content').html();
					}
				});
			}

			// 414 fix (inline): ignore cached labels.js GET handlers.
			// Button id is v2 so old $('#labels_browser_print') bindings never fire.
			$(document).off('click', '#labels_browser_print_v2');
			$('button#labels_browser_print_v2').off('click').on('click', function(e) {
				e.preventDefault();
				e.stopImmediatePropagation();
				if ($('form#labels_export_form table#product_table tbody tr.labels-product-row').length === 0) {
					swal(LANG.label_no_product_error || 'Add products first').then(function() {
						$('#search_product_for_label').focus();
					});
					return;
				}
				var base = typeof base_path !== 'undefined' ? base_path : '';
				var token = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
				var $btn = $(this);
				$btn.prop('disabled', true);
				$.ajax({
					method: 'POST',
					url: base + '/labels/browser-print/prepare',
					data: $('form#labels_export_form').serialize(),
					headers: token ? { 'X-CSRF-TOKEN': token } : {}
				}).done(function(res) {
					if (res && res.success && res.print_id) {
						var theme = document.documentElement.getAttribute('data-vspos-theme') === 'light' ? 'light' : 'dark';
						window.open(base + '/labels/browser-print?print_id=' + encodeURIComponent(res.print_id) + '&theme=' + theme, '_blank');
					} else {
						swal((res && res.msg) || (LANG.label_no_product_error || 'Add products first'));
					}
				}).fail(function(xhr) {
					var msg = (xhr.responseJSON && xhr.responseJSON.msg) || 'Could not prepare labels (is /labels/browser-print/prepare installed?).';
					swal(msg);
				}).always(function() {
					$btn.prop('disabled', false);
				});
			});
		});
	</script>
@endsection
