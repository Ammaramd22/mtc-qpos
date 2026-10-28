@extends('layouts.app')
@section('title', __('lang_v1.communication'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.communication')
        <small>@lang('lang_v1.communication_settings_help')</small>
    </h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @if(empty($module_enabled))
            <div class="alert alert-warning">
                <i class="fa fa-exclamation-triangle"></i>
                @lang('lang_v1.communication_enable_hint')
            </div>
        @endif

        {!! Form::open(['url' => action([\App\Http\Controllers\CommunicationController::class, 'updateSettings']), 'method' => 'post', 'id' => 'communication_settings_form']) !!}

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('enable_communication', 1, $module_enabled, ['class' => 'input-icheck']) !!}
                            <strong>@lang('lang_v1.enable_communication_module')</strong>
                        </label>
                    </div>
                    <p class="help-block">@lang('lang_v1.communication_module_help')</p>
                </div>
            </div>
        </div>

        <hr>
        <h4><i class="fa fa-copy"></i> @lang('lang_v1.photocopy') — @lang('lang_v1.paper_products')</h4>
        <p class="help-block">@lang('lang_v1.communication_paper_select_help')</p>
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    {!! Form::label('photocopy_variation_ids', __('lang_v1.photocopy_papers') . ':') !!}
                    @php
                        $paperLabels = [];
                        foreach ($paperOptions as $id => $o) {
                            $paperLabels[$id] = $o['label'];
                        }
                    @endphp
                    {!! Form::select(
                        'photocopy_variation_ids[]',
                        $paperLabels,
                        $settings['photocopy_variation_ids'] ?? [],
                        ['class' => 'form-control select2', 'multiple' => true, 'style' => 'width:100%', 'id' => 'photocopy_variation_ids']
                    ) !!}
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('ink_cost_photocopy', __('lang_v1.ink_cost_photocopy') . ':') !!}
                    {!! Form::text('ink_cost_photocopy', @num_format($settings['ink_cost_photocopy'] ?? 0), ['class' => 'form-control input_number', 'placeholder' => '0.00']) !!}
                    <p class="help-block">@lang('lang_v1.ink_cost_help')</p>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_photocopy_single', __('lang_v1.default_price_photocopy_single') . ':') !!}
                    {!! Form::text('default_price_photocopy_single', @num_format($settings['default_price_photocopy_single'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_photocopy_double', __('lang_v1.default_price_photocopy_double') . ':') !!}
                    {!! Form::text('default_price_photocopy_double', @num_format($settings['default_price_photocopy_double'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
        </div>

        <hr>
        <h4><i class="fa fa-print"></i> @lang('lang_v1.printout') — @lang('lang_v1.paper_products')</h4>
        <div class="row">
            <div class="col-md-8">
                <div class="form-group">
                    {!! Form::label('printout_variation_ids', __('lang_v1.printout_papers') . ':') !!}
                    {!! Form::select(
                        'printout_variation_ids[]',
                        $paperLabels,
                        $settings['printout_variation_ids'] ?? [],
                        ['class' => 'form-control select2', 'multiple' => true, 'style' => 'width:100%', 'id' => 'printout_variation_ids']
                    ) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ink_cost_print_bw', __('lang_v1.ink_cost_print_bw') . ':') !!}
                    {!! Form::text('ink_cost_print_bw', @num_format($settings['ink_cost_print_bw'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('ink_cost_print_color', __('lang_v1.ink_cost_print_color') . ':') !!}
                    {!! Form::text('ink_cost_print_color', @num_format($settings['ink_cost_print_color'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_print_bw_single', __('lang_v1.default_price_print_bw_single') . ':') !!}
                    {!! Form::text('default_price_print_bw_single', @num_format($settings['default_price_print_bw_single'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_print_bw_double', __('lang_v1.default_price_print_bw_double') . ':') !!}
                    {!! Form::text('default_price_print_bw_double', @num_format($settings['default_price_print_bw_double'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_print_color_single', __('lang_v1.default_price_print_color_single') . ':') !!}
                    {!! Form::text('default_price_print_color_single', @num_format($settings['default_price_print_color_single'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('default_price_print_color_double', __('lang_v1.default_price_print_color_double') . ':') !!}
                    {!! Form::text('default_price_print_color_double', @num_format($settings['default_price_print_color_double'] ?? 0), ['class' => 'form-control input_number']) !!}
                </div>
            </div>
        </div>

        <div class="alert alert-info">
            <i class="fa fa-info-circle"></i>
            @lang('lang_v1.communication_avg_cost_help')
        </div>

        <div class="row">
            <div class="col-md-12 text-center">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa fa-save"></i> @lang('messages.save')
                </button>
            </div>
        </div>

        {!! Form::close() !!}
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
    $(function () {
        $('#photocopy_variation_ids, #printout_variation_ids').select2({
            width: '100%',
            placeholder: @json(__('lang_v1.select_paper_products'))
        });
    });
</script>
@endsection
