@extends('layouts.app')
@section('title', __('lang_v1.standalone_return'))

@section('content')

<section class="content-header no-print">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('lang_v1.standalone_return')</h1>
    <p class="text-muted">@lang('lang_v1.return_without_invoice_help')</p>
</section>

<section class="content no-print">

    {!! Form::open(['url' => action([\App\Http\Controllers\SellReturnController::class, 'storeStandalone']), 'method' => 'post', 'id' => 'standalone_return_form' ]) !!}
    <input type="hidden" name="transaction_id" id="transaction_id" value="">
    <input type="hidden" name="location_id_locked" id="location_id_locked" value="">

    <div class="box box-solid">
        <div class="box-header">
            <h3 class="box-title">@lang('lang_v1.return_details')</h3>
        </div>
        <div class="box-body">
            <div class="row">
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                        {!! Form::select('location_id', $business_locations, $default_location, ['class' => 'form-control select2', 'id' => 'location_id', 'required', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            {!! Form::text('transaction_date', \Carbon::now()->format('m/d/Y H:i'), ['class' => 'form-control datetimepicker', 'readonly', 'required']); !!}
                        </div>
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('contact.customer') . ':') !!}
                        {!! Form::select('contact_id', [], $walk_in_customer['id'] ?? null, ['class' => 'form-control select2', 'id' => 'customer_id', 'data-walkin' => $walk_in_customer['id'] ?? '', 'style' => 'width:100%']); !!}
                    </div>
                </div>
                <div class="col-sm-3">
                    <div class="form-group">
                        {!! Form::label('against_invoice_no', __('sale.invoice_no') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('against_invoice_no', null, ['class' => 'form-control', 'id' => 'against_invoice_no', 'placeholder' => 'e.g. 0008']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="load_against_invoice" title="Load invoice">
                                    <i class="fa fa-search"></i> Load
                                </button>
                            </span>
                        </div>
                        <p class="help-block" style="margin-bottom:0;">Optional — load an existing POS invoice to return its items.</p>
                    </div>
                </div>
            </div>

            <div id="against_invoice_banner" class="alert alert-info hide" style="margin-bottom:12px;"></div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="form-group vspos-sr-search-wrap">
                        {!! Form::label('search_product', __('lang_v1.scan_search_product') . ':') !!}
                        <div class="input-group">
                            {!! Form::text('search_product', null, ['class' => 'form-control', 'id' => 'search_product', 'placeholder' => __('lang_v1.search_product_placeholder'), 'autocomplete' => 'off']); !!}
                            <span class="input-group-btn">
                                <button type="button" class="btn btn-default" id="vspos_sr_f8_btn" title="Product search (F8)">
                                    <i class="fa fa-search"></i> F8
                                </button>
                            </span>
                        </div>
                        <p class="help-block" style="margin-bottom:0;">Type to search by name, tyre size, brand, note, SKU or barcode. Press F8 for full search.</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <table class="table table-bordered table-striped" id="standalone_return_table">
                        <thead>
                            <tr class="bg-green">
                                <th>#</th>
                                <th>@lang('product.product_name')</th>
                                <th>@lang('sale.unit_price')</th>
                                <th>@lang('lang_v1.return_quantity')</th>
                                <th>@lang('lang_v1.return_subtotal')</th>
                                <th><i class="fa fa-trash" aria-hidden="true"></i></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray">
                                <td colspan="4" class="text-right"><strong>@lang('sale.total_amount')</strong></td>
                                <td><span id="total_return_amount">0.00</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-sm-6">
                    <div class="form-group">
                        {!! Form::label('additional_notes', __('lang_v1.return_note_remarks') . ':') !!}
                        {!! Form::textarea('additional_notes', null, ['class' => 'form-control', 'rows' => 3]); !!}
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-sm-12 text-right">
                    <button type="button" class="btn btn-primary" id="save_standalone_return">
                        <i class="fa fa-save"></i> @lang('lang_v1.generate_return_note')
                    </button>
                </div>
            </div>
        </div>
    </div>

    {!! Form::close() !!}

    {{-- Simple F8 product search modal (standalone; does not depend on POS) --}}
    <div class="modal fade" id="vspos_sr_f8_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Product Search (F8)</h4>
                </div>
                <div class="modal-body">
                    <input type="text" id="vspos_sr_f8_input" class="form-control" placeholder="Search name / tyre size / brand / note / SKU" autocomplete="off">
                    <div id="vspos_sr_f8_results" style="margin-top:10px;max-height:360px;overflow:auto;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="return_note_receipt_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-body" id="return_note_receipt_content" style="background:#fff;"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">@lang('messages.print')</button>
                    <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                </div>
            </div>
        </div>
    </div>

    <style type="text/css" media="print">
        @media print {
            body * { visibility: hidden; }
            #printable_receipt, #printable_receipt * { visibility: visible; }
            #printable_receipt { position: absolute; left: 0; top: 0; width: 80mm; margin: 0; padding: 5mm; font-family: monospace; font-size: 12px; }
        }
    </style>
    <style>
        .vspos-sr-search-wrap { position: relative; }
        #vspos_sr_live_dd.vspos-live-search-dd {
            position: absolute; left: 0; right: 0; top: 100%; z-index: 1050;
            background: #1f2937; border: 1px solid #374151; border-radius: 6px;
            max-height: 280px; overflow: auto; display: none; box-shadow: 0 8px 24px rgba(0,0,0,.35);
        }
        #vspos_sr_live_dd.vspos-live-search-dd.is-open { display: block; }
        #vspos_sr_live_dd .vspos-live-search-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #374151; color: #e5e7eb; }
        #vspos_sr_live_dd .vspos-live-search-item.is-active,
        #vspos_sr_live_dd .vspos-live-search-item:hover { background: #111827; }
        #vspos_sr_live_dd .vspos-live-search-name { font-weight: 600; }
        #vspos_sr_live_dd .vspos-live-search-meta { font-size: 11px; color: #9ca3af; margin-top: 2px; }
        #vspos_sr_live_dd .vspos-live-search-empty { padding: 12px; color: #9ca3af; text-align: center; }
        .vspos-sr-f8-row { padding: 8px 10px; border-bottom: 1px solid #333; cursor: pointer; }
        .vspos-sr-f8-row:hover, .vspos-sr-f8-row.is-active { background: #111; }
    </style>

</section>

@endsection

@section('javascript')
<script src="{{ asset('js/standalone-return.js?v=' . ($asset_v ?? config('constants.asset_version'))) }}"></script>
<script type="text/javascript">
(function ($) {
    // Standalone F8 modal (independent of POS bulk search)
    var f8Req = null;
    var f8Results = [];
    var f8Active = -1;

    function openF8(prefill) {
        $('#vspos_sr_f8_modal').modal('show');
        setTimeout(function () {
            $('#vspos_sr_f8_input').val(prefill || '').trigger('focus');
            if (prefill) {
                runF8(prefill);
            }
        }, 250);
    }

    window.vsposOpenBulkProductSearch = openF8;

    function runF8(term) {
        term = $.trim(term || '');
        $('#vspos_sr_f8_results').html('<div class="text-muted" style="padding:12px;">Searching...</div>');
        if (f8Req && f8Req.abort) f8Req.abort();
        if (!term) {
            $('#vspos_sr_f8_results').html('<div class="text-muted" style="padding:12px;">Type to search products</div>');
            return;
        }
        f8Req = $.getJSON('/products/list', {
            term: term,
            location_id: $('#location_id').val(),
            check_qty: false,
            not_for_selling: 0,
            search_fields: ['name', 'sku', 'sub_sku']
        }).done(function (data) {
            f8Results = Array.isArray(data) ? data : [];
            f8Active = f8Results.length ? 0 : -1;
            if (!f8Results.length) {
                $('#vspos_sr_f8_results').html('<div class="text-muted" style="padding:12px;">No matching products found</div>');
                return;
            }
            var html = '';
            f8Results.forEach(function (p, i) {
                var name = $('<textarea/>').html(p.name || '').val();
                var stock = p.enable_stock ? (Number(p.qty_available || 0).toFixed(2) + (p.unit ? ' ' + p.unit : '')) : '—';
                html += '<div class="vspos-sr-f8-row' + (i === 0 ? ' is-active' : '') + '" data-index="' + i + '">' +
                    '<strong>' + $('<div>').text(name).html() + '</strong><br>' +
                    '<small class="text-muted">SKU: ' + $('<div>').text(p.sub_sku || '').html() +
                    (p.tyre_size ? ' · Tyre Size: ' + $('<div>').text(p.tyre_size).html() : '') +
                    ' · Stock: ' + stock + ' · ' + Number(p.selling_price || 0).toFixed(2) + '</small></div>';
            });
            $('#vspos_sr_f8_results').html(html);
        }).fail(function (xhr) {
            if (xhr.statusText === 'abort') return;
            $('#vspos_sr_f8_results').html('<div class="text-danger" style="padding:12px;">Product could not be found.</div>');
        });
    }

    var f8Timer = null;
    $(document).on('input', '#vspos_sr_f8_input', function () {
        var t = $(this).val();
        clearTimeout(f8Timer);
        f8Timer = setTimeout(function () { runF8(t); }, 220);
    });

    $(document).on('mousedown', '#vspos_sr_f8_results .vspos-sr-f8-row', function (e) {
        e.preventDefault();
        var idx = parseInt($(this).data('index'), 10);
        var p = f8Results[idx];
        if (!p) return;
        p.name = $('<textarea/>').html(p.name || '').val();
        if (typeof window.vsposStandaloneAddProduct === 'function') {
            window.vsposStandaloneAddProduct(p);
        }
        $('#vspos_sr_f8_modal').modal('hide');
        $('#search_product').val('');
    });
})(jQuery);

function printReceipt() {
    var el = document.getElementById('printable_receipt');
    if (!el) return;
    var content = el.innerHTML;
    var win = window.open('', '_blank', 'width=320,height=600');
    win.document.write('<html><head><title>Return Note</title>');
    win.document.write('<style>body{font-family:monospace;font-size:12px;margin:0;padding:5mm;width:72mm;} table{width:100%;border-collapse:collapse;} th,td{padding:1mm 0;}</style>');
    win.document.write('</head><body>');
    win.document.write(content);
    win.document.write('</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function() { win.print(); win.close(); }, 250);
}
</script>
@endsection
