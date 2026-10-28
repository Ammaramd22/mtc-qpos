@extends('layouts.app')
@section('title', __('lang_v1.workshop_jobs'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.workshop_jobs')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <a href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'jobCreate']) }}" class="btn btn-primary">
                    <i class="fa fa-plus"></i> @lang('lang_v1.new_workshop_job')
                </a>
            </div>
        @endslot

        <div class="row" style="margin-bottom:10px;">
            <div class="col-md-3">
                {!! Form::select('job_status_filter', ['' => __('lang_v1.all')] + collect($statuses)->mapWithKeys(fn($s) => [$s => __('lang_v1.workshop_status_'.$s)])->all(), null, ['class' => 'form-control', 'id' => 'job_status_filter']) !!}
            </div>
        </div>

        <table class="table table-bordered table-striped" id="workshop_job_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('lang_v1.reference_no')</th>
                    <th>@lang('contact.customer')</th>
                    <th>@lang('lang_v1.vehicle')</th>
                    <th>@lang('sale.status')</th>
                    <th>@lang('lang_v1.payment')</th>
                    <th>@lang('sale.total')</th>
                    <th>@lang('messages.action')</th>
                </tr>
            </thead>
        </table>
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(function () {
    var table = $('#workshop_job_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action([\App\Http\Controllers\VehicleModificationController::class, "jobIndex"]) }}',
            data: function (d) {
                d.status = $('#job_status_filter').val();
            }
        },
        columns: [
            { data: 'reference_no', name: 'reference_no' },
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'vehicle', name: 'vehicle', orderable: false },
            { data: 'status', name: 'status' },
            { data: 'payment_status', name: 'payment_status' },
            { data: 'estimated_total', name: 'estimated_total' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
    $('#job_status_filter').on('change', function () { table.ajax.reload(); });
});
</script>
@endsection
