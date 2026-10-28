@extends('layouts.app')
@section('title', __('lang_v1.capital_partners'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.capital_partners')
        <small><a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'index']) }}">@lang('lang_v1.partner_balance_sheet')</a></small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.capital_partners')])
                @can('capital_account.manage_partners')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm btn-modal"
                                data-href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'createPartner']) }}"
                                data-container=".capital_partner_modal">
                                <i class="fa fa-plus"></i> @lang('lang_v1.add_partner')
                            </button>
                        </div>
                    @endslot
                @endcan
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="capital_partners_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.capital_partner_name')</th>
                                <th>@lang('lang_v1.capital_short_name')</th>
                                <th>@lang('lang_v1.capital_investment')</th>
                                <th>@lang('lang_v1.capital_profit_percent')</th>
                                <th>@lang('sale.status')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<div class="modal fade capital_partner_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#capital_partners_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ action([\App\Http\Controllers\CapitalAccountController::class, "partners"]) }}',
        columns: [
            { data: 'name', name: 'name' },
            { data: 'short_name', name: 'short_name' },
            { data: 'investment_amount', name: 'investment_amount' },
            { data: 'profit_percent', name: 'profit_percent' },
            { data: 'is_active', name: 'is_active' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        fnDrawCallback: function() {
            __currency_convert_recursively($('#capital_partners_table'));
        }
    });

    $(document).on('submit', 'form#capital_partner_form', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: form.attr('method'),
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            success: function(result) {
                if (result.success) {
                    $('.capital_partner_modal').modal('hide');
                    toastr.success(result.msg);
                    table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    $(document).on('click', '.delete-capital-partner', function(e) {
        e.preventDefault();
        var href = $(this).data('href');
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(function(willDelete) {
            if (willDelete) {
                $.ajax({
                    method: 'DELETE',
                    url: href,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == true || result.success == 1) {
                            toastr.success(result.msg);
                            table.ajax.reload();
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                    error: function(xhr) {
                        var msg = (xhr.responseJSON && xhr.responseJSON.msg)
                            ? xhr.responseJSON.msg
                            : (LANG.something_went_wrong || 'Something went wrong');
                        toastr.error(msg);
                    }
                });
            }
        });
    });
});
</script>
@endsection
