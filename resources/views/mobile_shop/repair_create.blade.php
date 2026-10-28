<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            <h4 class="modal-title">@lang('lang_v1.new_repair')</h4>
        </div>
        <div class="modal-body">
            @include('mobile_shop.partials.repair_form', ['imei' => request('imei'), 'form_id' => 'mobile_repair_add_form', 'is_modal' => true])
        </div>
    </div>
</div>

<script>
$('#mobile_repair_add_form').submit(function (e) {
    e.preventDefault();
    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        data: $(this).serialize(),
        success: function (result) {
            if (result.success) {
                toastr.success(result.msg);
                $('.view_modal').modal('hide');
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
                if ($.fn.DataTable.isDataTable('#mobile_repair_table')) {
                    $('#mobile_repair_table').DataTable().ajax.reload();
                }
            } else {
                toastr.error(result.msg);
            }
        }
    });
});
</script>
