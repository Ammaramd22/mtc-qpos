<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      <h4 class="modal-title">
        <i class="fa fa-car"></i> @lang('lang_v1.manage_vehicles') — {{ $contact->name }}
      </h4>
    </div>
    <div class="modal-body">
      <p class="text-muted">@lang('lang_v1.manage_vehicles_help')</p>

      <div class="table-responsive">
        <table class="table table-bordered table-striped" id="manage_vehicles_table">
          <thead>
            <tr>
              <th>@lang('lang_v1.vehicle_number')</th>
              <th>@lang('lang_v1.jobs')</th>
              <th>@lang('messages.action')</th>
            </tr>
          </thead>
          <tbody>
            @forelse($vehicles as $vehicle)
              <tr data-vehicle-id="{{ $vehicle->id }}">
                <td>
                  <input type="text"
                         class="form-control manage-vehicle-reg"
                         value="{{ $vehicle->registration_number }}"
                         maxlength="40">
                </td>
                <td>{{ (int) ($vehicle->jobs_count ?? 0) }}</td>
                <td>
                  <button type="button"
                          class="btn btn-xs btn-primary save-manage-vehicle"
                          data-url="{{ action([\App\Http\Controllers\ContactController::class, 'updateVehicle'], [$contact->id, $vehicle->id]) }}">
                    <i class="fa fa-save"></i>
                  </button>
                  <button type="button"
                          class="btn btn-xs btn-danger delete-manage-vehicle"
                          data-url="{{ action([\App\Http\Controllers\ContactController::class, 'destroyVehicle'], [$contact->id, $vehicle->id]) }}"
                          @if(($vehicle->jobs_count ?? 0) > 0) title="@lang('lang_v1.vehicle_cannot_delete_has_history')" @endif>
                    <i class="fa fa-trash"></i>
                  </button>
                </td>
              </tr>
            @empty
              <tr class="no-vehicles-row">
                <td colspan="3" class="text-center text-muted">@lang('lang_v1.no_vehicles_for_customer')</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <hr/>
      <h5>@lang('lang_v1.add_vehicle')</h5>
      <div class="row">
        <div class="col-md-8">
          <div class="form-group">
            <label>@lang('lang_v1.vehicle_number')</label>
            <input type="text" id="new_manage_vehicle_reg" class="form-control"
                   placeholder="@lang('lang_v1.vehicle_number_placeholder')" maxlength="40">
          </div>
        </div>
        <div class="col-md-4">
          <div class="form-group" style="padding-top: 25px;">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="add_manage_vehicle_btn"
                    data-url="{{ action([\App\Http\Controllers\ContactController::class, 'storeVehicle'], [$contact->id]) }}">
              <i class="fa fa-plus"></i> @lang('messages.save')
            </button>
          </div>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
    </div>
  </div>
</div>

<script>
(function () {
  var csrf = $('meta[name="csrf-token"]').attr('content');

  $('#add_manage_vehicle_btn').off('click').on('click', function () {
    var $btn = $(this);
    var reg = $.trim($('#new_manage_vehicle_reg').val());
    if (!reg) {
      toastr.error("@lang('lang_v1.vehicle_number_required')");
      return;
    }
    $.ajax({
      method: 'POST',
      url: $btn.data('url'),
      data: { _token: csrf, registration_number: reg },
      dataType: 'json',
      success: function (result) {
        if (result.success) {
          toastr.success(result.msg);
          $('.view_modal').load("{{ action([\App\Http\Controllers\ContactController::class, 'manageVehicles'], [$contact->id]) }}", function () {
            $('.view_modal').modal('show');
          });
        } else {
          toastr.error(result.msg);
        }
      },
      error: function (xhr) {
        toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || "@lang('messages.something_went_wrong')");
      }
    });
  });

  $(document).off('click.manageVeh', '.save-manage-vehicle').on('click.manageVeh', '.save-manage-vehicle', function () {
    var $btn = $(this);
    var $row = $btn.closest('tr');
    var reg = $.trim($row.find('.manage-vehicle-reg').val());
    if (!reg) {
      toastr.error("@lang('lang_v1.vehicle_number_required')");
      return;
    }
    $.ajax({
      method: 'POST',
      url: $btn.data('url'),
      data: { _token: csrf, registration_number: reg },
      dataType: 'json',
      success: function (result) {
        if (result.success) {
          toastr.success(result.msg);
        } else {
          toastr.error(result.msg);
        }
      },
      error: function () {
        toastr.error("@lang('messages.something_went_wrong')");
      }
    });
  });

  $(document).off('click.manageVeh', '.delete-manage-vehicle').on('click.manageVeh', '.delete-manage-vehicle', function () {
    var $btn = $(this);
    swal({
      title: LANG.sure,
      text: "@lang('lang_v1.confirm_remove_vehicle')",
      icon: 'warning',
      buttons: true,
      dangerMode: true
    }).then(function (willDelete) {
      if (!willDelete) return;
      $.ajax({
        method: 'DELETE',
        url: $btn.data('url'),
        data: { _token: csrf },
        dataType: 'json',
        success: function (result) {
          if (result.success) {
            toastr.success(result.msg);
            $btn.closest('tr').remove();
            if ($('#manage_vehicles_table tbody tr').length === 0) {
              $('#manage_vehicles_table tbody').append(
                '<tr class="no-vehicles-row"><td colspan="3" class="text-center text-muted">@lang('lang_v1.no_vehicles_for_customer')</td></tr>'
              );
            }
          } else {
            toastr.error(result.msg);
          }
        },
        error: function () {
          toastr.error("@lang('messages.something_went_wrong')");
        }
      });
    });
  });
})();
</script>
