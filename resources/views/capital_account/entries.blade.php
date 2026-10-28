@extends('layouts.app')
@section('title', __('lang_v1.capital_entries'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.capital_entries')
        <small><a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'index']) }}">@lang('lang_v1.partner_balance_sheet')</a></small>
    </h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_type', __('lang_v1.capital_entry_type') . ':') !!}
                        {!! Form::select('filter_type', $typeLabels, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_type']) !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('filter_source', __('lang_v1.capital_source') . ':') !!}
                        {!! Form::select('filter_source', $sourceLabels, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'filter_source']) !!}
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.capital_entries')])
                @can('capital_account.manage_entries')
                    @slot('tool')
                        <div class="box-tools">
                            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-dw-btn-sm btn-modal"
                                data-href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'createEntry']) }}"
                                data-container=".capital_entry_modal">
                                <i class="fa fa-plus"></i> @lang('lang_v1.add_capital_entry')
                            </button>
                        </div>
                    @endslot
                @endcan
                <p class="help-block">@lang('lang_v1.capital_entries_help')</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="capital_entries_table" style="width:100%;">
                        <thead>
                            <tr>
                                <th>@lang('messages.date')</th>
                                <th>@lang('lang_v1.capital_entry_type')</th>
                                <th>@lang('expense.expense_category') / @lang('lang_v1.capital_asset_name')</th>
                                <th>@lang('lang_v1.capital_source')</th>
                                <th>@lang('lang_v1.capital_partner_name')</th>
                                <th>@lang('sale.amount')</th>
                                <th>@lang('purchase.ref_no')</th>
                                <th>@lang('brand.note')</th>
                                <th>@lang('messages.action')</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<div class="modal fade capital_entry_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
@endsection

@section('javascript')
<script>
$(document).ready(function() {
    var table = $('#capital_entries_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ action([\App\Http\Controllers\CapitalAccountController::class, "entries"]) }}',
            data: function(d) {
                d.type = $('#filter_type').val();
                d.source = $('#filter_source').val();
            }
        },
        columns: [
            { data: 'entry_date', name: 'entry_date' },
            { data: 'type', name: 'type' },
            { data: 'category', name: 'ec.name', orderable: false, searchable: true },
            { data: 'source', name: 'source' },
            { data: 'partner', name: 'partner', orderable: false, searchable: false },
            { data: 'amount', name: 'amount' },
            { data: 'reference_no', name: 'reference_no' },
            { data: 'note', name: 'note' },
            { data: 'action', name: 'action', orderable: false, searchable: false }
        ],
        fnDrawCallback: function() {
            __currency_convert_recursively($('#capital_entries_table'));
        }
    });

    $('#filter_type, #filter_source').on('change', function() {
        table.ajax.reload();
    });

    $(document).on('submit', 'form#capital_entry_form', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            method: form.attr('method'),
            url: form.attr('action'),
            dataType: 'json',
            data: form.serialize(),
            success: function(result) {
                if (result.success) {
                    $('.capital_entry_modal').modal('hide');
                    toastr.success(result.msg);
                    table.ajax.reload();
                } else {
                    toastr.error(result.msg);
                }
            }
        });
    });

    $(document).on('click', '.delete-capital-entry', function(e) {
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
