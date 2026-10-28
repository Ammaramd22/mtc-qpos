@extends('layouts.app')
@section('title', __('lang_v1.nutmeg_drying'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.nutmeg_drying')
        <small>@lang('lang_v1.nutmeg_drying_help')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.products_for_drying')])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('drying_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('drying_location_id', $locations, null, ['class' => 'form-control select2', 'id' => 'drying_location_id', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                </div>
            </div>
        </div>
        <div class="table-responsive pvs-table-scroll">
            <table class="table table-bordered table-striped" id="nutmeg_drying_products_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('sale.product')</th>
                        <th>SKU</th>
                        <th>@lang('report.current_stock')</th>
                        <th>@lang('lang_v1.avg_cost')</th>
                        <th>@lang('messages.action')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.drying_history')])
        <div class="table-responsive pvs-table-scroll">
            <table class="table table-bordered table-striped" id="nutmeg_drying_history_table" style="width:100%">
                <thead>
                    <tr>
                        <th>@lang('messages.date')</th>
                        <th>@lang('sale.product')</th>
                        <th>@lang('lang_v1.before_drying')</th>
                        <th>@lang('lang_v1.after_drying')</th>
                        <th>@lang('lang_v1.wastage')</th>
                        <th>@lang('lang_v1.avg_cost_before')</th>
                        <th>@lang('lang_v1.avg_cost_after')</th>
                    </tr>
                </thead>
            </table>
        </div>
    @endcomponent
</section>

{{-- Drying popup --}}
<div class="modal fade" id="nutmeg_drying_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content nutmeg-drying-modal">
            <div class="modal-header" style="background:#111;color:#f5c518;border-bottom:1px solid #333;">
                <button type="button" class="close" data-dismiss="modal" style="color:#fff;"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-fire"></i> <span id="nd_product_title">@lang('lang_v1.dry_product')</span></h4>
            </div>
            <div class="modal-body" style="background:#0d0d0d;color:#e5e5e5;">
                <input type="hidden" id="nd_product_id">
                <input type="hidden" id="nd_variation_id">
                <input type="hidden" id="nd_location_id">
                <input type="hidden" id="nd_unit">

                <div class="nd-stat-grid">
                    <div class="nd-stat">
                        <div class="nd-stat-lbl">@lang('report.current_stock')</div>
                        <div class="nd-stat-val"><span id="nd_stock">0</span> <small id="nd_unit_lbl"></small></div>
                    </div>
                    <div class="nd-stat">
                        <div class="nd-stat-lbl">@lang('lang_v1.current_avg_cost')</div>
                        <div class="nd-stat-val" id="nd_avg_cost">0</div>
                    </div>
                    <div class="nd-stat">
                        <div class="nd-stat-lbl">@lang('lang_v1.stock_value')</div>
                        <div class="nd-stat-val" id="nd_stock_value">0</div>
                    </div>
                </div>

                <div class="row" style="margin-top:16px;">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>@lang('lang_v1.before_drying') (<span class="nd_unit_inline"></span>)</label>
                            <input type="text" class="form-control input_number" id="nd_qty_before" placeholder="0">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>@lang('lang_v1.after_drying') (<span class="nd_unit_inline"></span>)</label>
                            <input type="text" class="form-control input_number" id="nd_qty_after" placeholder="0">
                        </div>
                    </div>
                </div>

                <div class="nd-result">
                    <div class="nd-result-row">
                        <span>@lang('lang_v1.wastage') / @lang('lang_v1.loss')</span>
                        <strong id="nd_wastage">0</strong>
                    </div>
                    <div class="nd-result-row">
                        <span>@lang('lang_v1.wastage_pct')</span>
                        <strong id="nd_wastage_pct">0%</strong>
                    </div>
                    <div class="nd-result-row nd-highlight">
                        <span>@lang('lang_v1.new_avg_cost')</span>
                        <strong id="nd_projected_avg">0</strong>
                    </div>
                    <div class="nd-result-row">
                        <span>@lang('lang_v1.stock_after_drying')</span>
                        <strong id="nd_projected_stock">0</strong>
                    </div>
                </div>

                <div class="form-group" style="margin-top:12px;">
                    <label>@lang('lang_v1.note')</label>
                    <textarea id="nd_notes" class="form-control" rows="2"></textarea>
                </div>
                <p class="help-block" style="color:#888;font-size:12px;">
                    <i>@lang('lang_v1.drying_cost_help')</i>
                </p>
            </div>
            <div class="modal-footer" style="background:#111;border-top:1px solid #333;">
                <button type="button" class="btn btn-default" data-dismiss="modal">@lang('messages.close')</button>
                <button type="button" class="btn btn-warning" id="nd_save_btn" style="background:#f5c518;border-color:#f5c518;color:#111;font-weight:700;">
                    <i class="fa fa-check"></i> @lang('lang_v1.save_drying')
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
.nd-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
.nd-stat{background:#161616;border:1px solid #2a2a2a;border-radius:10px;padding:12px;}
.nd-stat-lbl{font-size:11px;color:#888;text-transform:uppercase;letter-spacing:.04em;}
.nd-stat-val{font-size:18px;font-weight:700;color:#f5c518;margin-top:4px;}
.nd-result{margin-top:12px;background:#161616;border:1px solid #2a2a2a;border-radius:10px;padding:12px;}
.nd-result-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #222;font-size:13px;}
.nd-result-row:last-child{border-bottom:0;}
.nd-highlight{color:#f5c518;font-size:15px;}
.nutmeg-drying-modal .form-control{background:#1a1a1a;border-color:#333;color:#eee;}
</style>
@endsection

@section('javascript')
<script>
(function(){
    var productsUrl = @json(action([\App\Http\Controllers\NutmegDryingController::class, 'products']));
    var previewUrl = @json(action([\App\Http\Controllers\NutmegDryingController::class, 'preview']));
    var storeUrl = @json(action([\App\Http\Controllers\NutmegDryingController::class, 'store']));
    var historyUrl = @json(action([\App\Http\Controllers\NutmegDryingController::class, 'history']));
    var previewTimer = null;
    var productsTable, historyTable;

    function money(v){ return __currency_trans_from_en(parseFloat(v)||0, true); }
    function qty(v){ return __number_f(parseFloat(v)||0); }

    function refreshPreview(){
        var variation_id = $('#nd_variation_id').val();
        var location_id = $('#nd_location_id').val();
        if(!variation_id || !location_id) return;
        $.getJSON(previewUrl, {
            variation_id: variation_id,
            location_id: location_id,
            qty_before: $('#nd_qty_before').val(),
            qty_after: $('#nd_qty_after').val()
        }, function(res){
            if(!res.success) return;
            $('#nd_stock').text(qty(res.stock));
            $('#nd_avg_cost').text(money(res.avg_cost));
            $('#nd_stock_value').text(money(res.stock_value));
            $('#nd_wastage').text(qty(res.wastage) + ' ' + ($('#nd_unit').val()||''));
            $('#nd_wastage_pct').text((res.wastage_pct||0) + '%');
            $('#nd_projected_avg').text(money(res.projected_avg_cost));
            $('#nd_projected_stock').text(qty(res.projected_stock) + ' ' + ($('#nd_unit').val()||''));
        });
    }

    function openDrying(data){
        $('#nd_product_id').val(data.productId);
        $('#nd_variation_id').val(data.variationId);
        $('#nd_location_id').val(data.locationId);
        $('#nd_unit').val(data.unit||'');
        $('#nd_product_title').text(data.productName + (data.sku ? ' ('+data.sku+')' : ''));
        $('.nd_unit_inline, #nd_unit_lbl').text(data.unit||'');
        $('#nd_qty_before').val('');
        $('#nd_qty_after').val('');
        $('#nd_notes').val('');
        $('#nd_stock').text(qty(data.stock));
        $('#nd_avg_cost').text(money(data.avgCost));
        $('#nd_stock_value').text(money((parseFloat(data.stock)||0)*(parseFloat(data.avgCost)||0)));
        $('#nd_wastage').text('0');
        $('#nd_wastage_pct').text('0%');
        $('#nd_projected_avg').text(money(data.avgCost));
        $('#nd_projected_stock').text(qty(data.stock)+' '+(data.unit||''));
        $('#nutmeg_drying_modal').modal('show');
    }

    $(document).ready(function(){
        $('#drying_location_id').select2({width:'100%'});

        productsTable = $('#nutmeg_drying_products_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: productsUrl,
                data: function(d){ d.location_id = $('#drying_location_id').val(); }
            },
            columns: [
                {data:'product_label', name:'p.name'},
                {data:'sub_sku', name:'variations.sub_sku'},
                {data:'stock', name:'stock', searchable:false},
                {data:'dpp_inc_tax', name:'variations.dpp_inc_tax'},
                {data:'action', orderable:false, searchable:false}
            ],
            fnDrawCallback: function(){ __currency_convert_recursively($('#nutmeg_drying_products_table')); }
        });

        historyTable = $('#nutmeg_drying_history_table').DataTable({
            processing: true,
            serverSide: true,
            ajax: historyUrl,
            order: [[0,'desc']],
            columns: [
                {data:'created_at', name:'created_at'},
                {data:'product_label', name:'product_label', orderable:false},
                {data:'qty_before', name:'qty_before'},
                {data:'qty_after', name:'qty_after'},
                {data:'qty_wastage', name:'qty_wastage'},
                {data:'unit_cost_before', name:'unit_cost_before'},
                {data:'unit_cost_after', name:'unit_cost_after'}
            ],
            fnDrawCallback: function(){ __currency_convert_recursively($('#nutmeg_drying_history_table')); }
        });

        $('#drying_location_id').on('change', function(){ productsTable.ajax.reload(); });

        $(document).on('click', '.btn-open-drying', function(){
            var loc = $('#drying_location_id').val() || $(this).data('location-id');
            if(!loc){ toastr.warning(@json(__('lang_v1.select_location'))); return; }
            openDrying({
                productId: $(this).data('product-id'),
                variationId: $(this).data('variation-id'),
                locationId: loc,
                productName: $(this).data('product-name'),
                sku: $(this).data('sku'),
                unit: $(this).data('unit'),
                stock: $(this).data('stock'),
                avgCost: $(this).data('avg-cost')
            });
        });

        $('#nd_qty_before, #nd_qty_after').on('change keyup', function(){
            clearTimeout(previewTimer);
            previewTimer = setTimeout(refreshPreview, 250);
        });

        $('#nd_save_btn').on('click', function(){
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: storeUrl,
                dataType: 'json',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    location_id: $('#nd_location_id').val(),
                    product_id: $('#nd_product_id').val(),
                    variation_id: $('#nd_variation_id').val(),
                    qty_before: $('#nd_qty_before').val(),
                    qty_after: $('#nd_qty_after').val(),
                    notes: $('#nd_notes').val()
                },
                success: function(res){
                    $btn.prop('disabled', false);
                    if(res.success){
                        toastr.success(res.msg);
                        $('#nutmeg_drying_modal').modal('hide');
                        productsTable.ajax.reload();
                        historyTable.ajax.reload();
                    } else {
                        toastr.error(res.msg || LANG.something_went_wrong);
                    }
                },
                error: function(){
                    $btn.prop('disabled', false);
                    toastr.error(LANG.something_went_wrong);
                }
            });
        });
    });
})();
</script>
@endsection
