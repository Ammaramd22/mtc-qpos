@extends('layouts.app')
@section('title', __('lang_v1.customer_vehicles'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.customer_vehicles')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @slot('tool')
            <div class="box-tools">
                <button type="button" class="btn btn-primary btn-modal"
                    data-href="{{ action([\App\Http\Controllers\VehicleModificationController::class, 'vehicleCreate']) }}"
                    data-container=".view_modal">
                    <i class="fa fa-plus"></i> @lang('lang_v1.add_vehicle')
                </button>
            </div>
        @endslot

        <table class="table table-bordered table-striped" id="customer_vehicle_table" style="width:100%;">
            <thead>
                <tr>
                    <th>@lang('contact.customer')</th>
                    <th>@lang('lang_v1.vehicle')</th>
                    <th>@lang('lang_v1.registration_number')</th>
                    <th>@lang('lang_v1.vehicle_type')</th>
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
    $('#customer_vehicle_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action([\App\Http\Controllers\VehicleModificationController::class, "vehicleIndex"]) }}',
        columns: [
            { data: 'customer', name: 'customer', orderable: false },
            { data: 'vehicle', name: 'vehicle', orderable: false },
            { data: 'registration_number', name: 'registration_number' },
            { data: 'vehicle_type', name: 'vehicle_type' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ]
    });
});
</script>
@endsection
