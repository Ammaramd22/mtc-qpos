@extends('layouts.app')
@section('title', __('lang_v1.nutmeg_settings'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.nutmeg_settings')
        <small>@lang('lang_v1.nutmeg_settings_help')</small>
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\NutmegSettingsController::class, 'updateSettings']), 'method' => 'post']) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.nutmeg')])
        <div class="checkbox">
            <label>
                {!! Form::checkbox('enable_nutmeg_module', 1, $module_enabled, ['class' => 'input-icheck']) !!}
                @lang('lang_v1.enable_nutmeg_module')
            </label>
        </div>
        <p class="help-block">@lang('lang_v1.nutmeg_module_help')</p>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.nutmeg_bags')])
        <p class="help-block">@lang('lang_v1.nutmeg_bags_help')</p>
        <div class="checkbox">
            <label>
                {!! Form::checkbox('ask_bag_on_sell', 1, !isset($settings['ask_bag_on_sell']) || !empty($settings['ask_bag_on_sell']), ['class' => 'input-icheck']) !!}
                @lang('lang_v1.ask_bag_on_sell')
            </label>
        </div>

        <div class="table-responsive pvs-table-scroll">
            <table class="table table-bordered" id="nutmeg_bags_table">
                <thead>
                    <tr>
                        <th>@lang('lang_v1.bag_product')</th>
                        <th style="width:160px;">@lang('lang_v1.fill_weight')</th>
                        <th>@lang('lang_v1.note')</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php $bags = $settings['bags'] ?? [['variation_id'=>'','fill_weight'=>'','label'=>'']]; @endphp
                    @foreach($bags as $i => $bag)
                        <tr>
                            <td>
                                <select name="bags[{{ $i }}][variation_id]" class="form-control select2 bag-variation" style="width:100%">
                                    <option value="">@lang('messages.please_select')</option>
                                    @foreach($productOptions as $vid => $opt)
                                        <option value="{{ $vid }}" data-product-id="{{ $opt['product_id'] }}"
                                            @if(($bag['variation_id'] ?? '') == $vid) selected @endif>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="bags[{{ $i }}][product_id]" class="bag-product-id" value="{{ $bag['product_id'] ?? '' }}">
                            </td>
                            <td>
                                <input type="text" name="bags[{{ $i }}][fill_weight]" class="form-control input_number"
                                    value="{{ $bag['fill_weight'] ?? '' }}" placeholder="0.2">
                                <small class="text-muted">@lang('lang_v1.fill_weight_help')</small>
                            </td>
                            <td>
                                <input type="text" name="bags[{{ $i }}][label]" class="form-control"
                                    value="{{ $bag['label'] ?? '' }}" placeholder="200g bag">
                            </td>
                            <td>
                                <button type="button" class="btn btn-xs btn-danger remove-bag-row"><i class="fa fa-times"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <button type="button" class="btn btn-sm btn-primary" id="add_bag_row">
            <i class="fa fa-plus"></i> @lang('lang_v1.add_bag')
        </button>
    @endcomponent

    @if(function_exists('nutmeg_enabled') && nutmeg_enabled())
        @component('components.widget', ['class' => 'box-solid', 'title' => __('report.reports')])
            <p class="help-block" style="margin-top:0;">@lang('lang_v1.nutmeg_reports_help')</p>
            <a href="{{ action([\App\Http\Controllers\NutmegReportController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
                <i class="fa fa-chart-bar"></i> @lang('lang_v1.nutmeg_cost_report')
            </a>
            <a href="{{ action([\App\Http\Controllers\NutmegDryingController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-outline">
                <i class="fa fa-fire"></i> @lang('lang_v1.nutmeg_drying')
            </a>
        @endcomponent
    @endif

    <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">
        <i class="fa fa-save"></i> @lang('messages.save')
    </button>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
@php
    $bagProductOptionsJson = collect($productOptions)->map(function ($opt, $vid) {
        return [
            'id' => $vid,
            'label' => $opt['label'] ?? '',
            'product_id' => $opt['product_id'] ?? '',
        ];
    })->values();
@endphp
<script>
(function(){
    var optionsHtml = @json($bagProductOptionsJson);

    function rowHtml(i){
        var opts = '<option value="">{{ __("messages.please_select") }}</option>';
        optionsHtml.forEach(function(o){
            opts += '<option value="'+o.id+'" data-product-id="'+o.product_id+'">'+ $('<div>').text(o.label).html() +'</option>';
        });
        return '<tr>'+
            '<td><select name="bags['+i+'][variation_id]" class="form-control select2 bag-variation" style="width:100%">'+opts+'</select>'+
            '<input type="hidden" name="bags['+i+'][product_id]" class="bag-product-id" value=""></td>'+
            '<td><input type="text" name="bags['+i+'][fill_weight]" class="form-control input_number" placeholder="0.2">'+
            '<small class="text-muted">{{ __("lang_v1.fill_weight_help") }}</small></td>'+
            '<td><input type="text" name="bags['+i+'][label]" class="form-control" placeholder="200g bag"></td>'+
            '<td><button type="button" class="btn btn-xs btn-danger remove-bag-row"><i class="fa fa-times"></i></button></td>'+
            '</tr>';
    }

    $(document).on('change', '.bag-variation', function(){
        var pid = $(this).find('option:selected').data('product-id') || '';
        $(this).closest('tr').find('.bag-product-id').val(pid);
    });

    $('#add_bag_row').on('click', function(){
        var i = $('#nutmeg_bags_table tbody tr').length;
        $('#nutmeg_bags_table tbody').append(rowHtml(i));
        $('#nutmeg_bags_table tbody tr:last .select2').select2({width:'100%'});
    });

    $(document).on('click', '.remove-bag-row', function(){
        var $tbody = $('#nutmeg_bags_table tbody');
        if($tbody.find('tr').length <= 1){
            $(this).closest('tr').find('input, select').val('').trigger('change');
        } else {
            $(this).closest('tr').remove();
        }
    });

    $('.bag-variation').select2({width:'100%'});
})();
</script>
@endsection
