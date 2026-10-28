@extends('layouts.app')
@section('title', empty($job) ? __('lang_v1.new_workshop_job') : __('lang_v1.edit_workshop_job'))

@section('content')
<section class="content-header">
    <h1>{{ empty($job) ? __('lang_v1.new_workshop_job') : __('lang_v1.edit_workshop_job') }}
        @if(!empty($job)) <small>{{ $job->reference_no }}</small> @endif
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => empty($job)
        ? action([\App\Http\Controllers\VehicleModificationController::class, 'jobStore'])
        : action([\App\Http\Controllers\VehicleModificationController::class, 'jobUpdate'], [$job->id]),
        'method' => 'post']) !!}

    <div class="box box-primary">
        <div class="box-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('contact_id', __('contact.customer') . ':*') !!}
                        {!! Form::select('contact_id', $customers, $job->contact_id ?? ($vehicle->contact_id ?? null), ['class' => 'form-control select2', 'id' => 'job_contact_id', 'required', 'style' => 'width:100%']) !!}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('vehicle_id', __('lang_v1.vehicle') . ':') !!}
                        <select name="vehicle_id" id="job_vehicle_id" class="form-control select2" style="width:100%">
                            <option value="">@lang('messages.please_select')</option>
                            @if(!empty($job) && !empty($vehicles))
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" @if($job->vehicle_id == $v->id) selected @endif>{{ $v->displayName() }}</option>
                                @endforeach
                            @elseif(!empty($vehicle))
                                <option value="{{ $vehicle->id }}" selected>{{ $vehicle->displayName() }}</option>
                            @endif
                        </select>
                        <p class="help-block">
                            <a href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'vehicleIndex']) }}">@lang('lang_v1.add_vehicle')</a>
                        </p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        {!! Form::label('status', __('sale.status') . ':*') !!}
                        <select name="status" class="form-control" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @if(($job->status ?? 'pending') === $status) selected @endif>
                                    @lang('lang_v1.workshop_status_'.$status)
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group">
                {!! Form::label('description', __('lang_v1.job_description') . ':') !!}
                {!! Form::textarea('description', $job->description ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
            <div class="form-group">
                {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                {!! Form::textarea('notes', $job->notes ?? null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>
        </div>
    </div>

    <div class="box box-solid">
        <div class="box-header with-border">
            <h3 class="box-title">@lang('lang_v1.parts_and_services')</h3>
        </div>
        <div class="box-body">
            <table class="table table-condensed" id="job_items_table">
                <thead>
                    <tr>
                        <th style="width:18%;">@lang('lang_v1.item_type')</th>
                        <th>@lang('sale.product')</th>
                        <th style="width:12%;">@lang('lang_v1.quantity')</th>
                        <th style="width:15%;">@lang('sale.unit_price')</th>
                        <th style="width:40px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @php $items = !empty($job) ? $job->items : collect(); @endphp
                    @forelse($items as $item)
                        <tr>
                            <td>
                                <select name="item_type[]" class="form-control">
                                    <option value="part" @if($item->item_type === 'part') selected @endif>@lang('lang_v1.part')</option>
                                    <option value="service" @if($item->item_type === 'service') selected @endif>@lang('lang_v1.service')</option>
                                </select>
                            </td>
                            <td>
                                <select name="item_product_id[]" class="form-control job-product-select" style="width:100%">
                                    @if($item->product_id)
                                        <option value="{{ $item->product_id }}" selected>{{ $item->name }}</option>
                                    @endif
                                </select>
                                <input type="hidden" name="item_name[]" value="{{ $item->name }}">
                            </td>
                            <td><input type="text" name="item_qty[]" class="form-control input_number" value="{{ $item->quantity }}"></td>
                            <td><input type="text" name="item_price[]" class="form-control input_number" value="{{ $item->unit_price }}"></td>
                            <td><button type="button" class="btn btn-xs btn-danger remove-job-item"><i class="fa fa-times"></i></button></td>
                        </tr>
                    @empty
                    @endforelse
                </tbody>
            </table>
            <button type="button" class="btn btn-sm btn-default" id="add_job_item">
                <i class="fa fa-plus"></i> @lang('lang_v1.add_item')
            </button>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
    <a href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'jobIndex']) }}" class="btn btn-default">@lang('messages.cancel')</a>
    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script>
function initJobProductSelect($el) {
    $el.select2({
        ajax: {
            url: '/products/list',
            dataType: 'json',
            delay: 250,
            data: function (params) {
                return { term: params.term, page: params.page || 1 };
            },
            processResults: function (data) {
                var items = data;
                if (data && data.results) { items = data.results; }
                return {
                    results: $.map(items, function (p) {
                        return { id: p.product_id || p.id, text: p.name || p.text };
                    })
                };
            }
        },
        minimumInputLength: 1,
        width: '100%'
    }).on('select2:select', function (e) {
        $(this).closest('td').find('input[name="item_name[]"]').val(e.params.data.text);
    });
}

$(function () {
    $('.job-product-select').each(function () { initJobProductSelect($(this)); });

    $('#job_contact_id').on('change', function () {
        var cid = $(this).val();
        $('#job_vehicle_id').empty().append('<option value="">@lang('messages.please_select')</option>');
        if (!cid) return;
        $.get('{{ action([\App\Http\Controllers\VehicleModificationController::class, "vehiclesForCustomer"]) }}', { contact_id: cid }, function (rows) {
            $.each(rows, function (i, v) {
                $('#job_vehicle_id').append($('<option>', { value: v.id, text: v.text }));
            });
        });
    });

    $('#add_job_item').on('click', function () {
        var row = '<tr>' +
            '<td><select name="item_type[]" class="form-control"><option value="part">@lang('lang_v1.part')</option><option value="service">@lang('lang_v1.service')</option></select></td>' +
            '<td><select name="item_product_id[]" class="form-control job-product-select" style="width:100%"></select><input type="hidden" name="item_name[]" value=""></td>' +
            '<td><input type="text" name="item_qty[]" class="form-control input_number" value="1"></td>' +
            '<td><input type="text" name="item_price[]" class="form-control input_number" value="0"></td>' +
            '<td><button type="button" class="btn btn-xs btn-danger remove-job-item"><i class="fa fa-times"></i></button></td>' +
            '</tr>';
        var $row = $(row);
        $('#job_items_table tbody').append($row);
        initJobProductSelect($row.find('.job-product-select'));
    });

    $(document).on('click', '.remove-job-item', function () {
        $(this).closest('tr').remove();
    });
});
</script>
@endsection
