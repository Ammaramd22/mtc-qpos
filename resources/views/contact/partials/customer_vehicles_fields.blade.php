@php
  $customer_vehicles_enabled = $customer_vehicles_enabled
    ?? (function_exists('customer_vehicles_enabled') && customer_vehicles_enabled());
  $customer_vehicles = $customer_vehicles ?? collect();
  $is_edit = isset($contact);
@endphp

@if($customer_vehicles_enabled)
<div class="col-md-12 customer_fields contact_vehicle_section">
  <hr/>
  <h4 class="tw-font-semibold tw-mb-2">
    <i class="fa fa-car"></i> @lang('lang_v1.vehicle_information')
  </h4>
  <p class="help-block tw-mb-3">@lang('lang_v1.vehicle_information_help')</p>

  <div id="contact_vehicles_list">
    @forelse($customer_vehicles as $index => $vehicle)
      <div class="row contact-vehicle-row tw-mb-2" data-vehicle-id="{{ $vehicle->id }}">
        <div class="col-md-8">
          <div class="form-group">
            {!! Form::label('vehicles_'.$index.'_registration', __('lang_v1.vehicle_number') . ':') !!}
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-car"></i></span>
              <input type="hidden" name="vehicles[{{ $index }}][id]" value="{{ $vehicle->id }}">
              <input type="text"
                     name="vehicles[{{ $index }}][registration_number]"
                     id="vehicles_{{ $index }}_registration"
                     class="form-control vehicle-registration-input"
                     value="{{ $vehicle->registration_number }}"
                     placeholder="@lang('lang_v1.vehicle_number_placeholder')"
                     maxlength="40">
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group" style="padding-top: 25px;">
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-error tw-text-white remove-contact-vehicle">
              <i class="fa fa-times"></i> @lang('messages.remove')
            </button>
          </div>
        </div>
      </div>
    @empty
      <div class="row contact-vehicle-row tw-mb-2">
        <div class="col-md-8">
          <div class="form-group">
            {!! Form::label('vehicles_0_registration', __('lang_v1.vehicle_number') . ':') !!}
            <div class="input-group">
              <span class="input-group-addon"><i class="fa fa-car"></i></span>
              <input type="text"
                     name="vehicles[0][registration_number]"
                     id="vehicles_0_registration"
                     class="form-control vehicle-registration-input"
                     value=""
                     placeholder="@lang('lang_v1.vehicle_number_placeholder')"
                     maxlength="40">
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group" style="padding-top: 25px;">
            <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-error tw-text-white remove-contact-vehicle" style="display:none;">
              <i class="fa fa-times"></i> @lang('messages.remove')
            </button>
          </div>
        </div>
      </div>
    @endforelse
  </div>

  <div id="removed_vehicles_container"></div>

  <div class="tw-mb-3">
    <button type="button" class="tw-dw-btn tw-dw-btn-sm tw-dw-btn-outline tw-dw-btn-primary" id="add_another_vehicle_btn">
      <i class="fa fa-plus"></i> @lang('lang_v1.add_another_vehicle')
    </button>
  </div>
</div>

<script type="text/template" id="contact_vehicle_row_template">
  <div class="row contact-vehicle-row tw-mb-2">
    <div class="col-md-8">
      <div class="form-group">
        <label>@lang('lang_v1.vehicle_number'):</label>
        <div class="input-group">
          <span class="input-group-addon"><i class="fa fa-car"></i></span>
          <input type="text"
                 name="vehicles[__INDEX__][registration_number]"
                 class="form-control vehicle-registration-input"
                 value=""
                 placeholder="@lang('lang_v1.vehicle_number_placeholder')"
                 maxlength="40">
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="form-group" style="padding-top: 25px;">
        <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-error tw-text-white remove-contact-vehicle">
          <i class="fa fa-times"></i> @lang('messages.remove')
        </button>
      </div>
    </div>
  </div>
</script>

<script>
(function () {
  function refreshRemoveButtons() {
    var rows = $('#contact_vehicles_list .contact-vehicle-row');
    rows.find('.remove-contact-vehicle').toggle(rows.length > 1);
  }

  $(document).off('click.contactVehicles', '#add_another_vehicle_btn').on('click.contactVehicles', '#add_another_vehicle_btn', function () {
    var index = $('#contact_vehicles_list .contact-vehicle-row').length;
    var html = $('#contact_vehicle_row_template').html().replace(/__INDEX__/g, index);
    $('#contact_vehicles_list').append(html);
    refreshRemoveButtons();
  });

  $(document).off('click.contactVehicles', '.remove-contact-vehicle').on('click.contactVehicles', '.remove-contact-vehicle', function () {
    var $row = $(this).closest('.contact-vehicle-row');
    var vehicleId = $row.data('vehicle-id');
    if (vehicleId) {
      $('#removed_vehicles_container').append(
        $('<input>', { type: 'hidden', name: 'removed_vehicles[]', value: vehicleId })
      );
    }
    $row.remove();
    if ($('#contact_vehicles_list .contact-vehicle-row').length === 0) {
      $('#add_another_vehicle_btn').trigger('click');
    }
    refreshRemoveButtons();
  });

  refreshRemoveButtons();
})();
</script>
@endif
