@extends('layouts.app')
@section('title', __( 'account.balance_sheet' ))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang( 'account.balance_sheet')
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row no-print">
        <div class="col-sm-12">
            @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('bal_sheet_location_id',  __('purchase.business_location') . ':') !!}
                    {!! Form::select('bal_sheet_location_id', $business_locations, null, ['class' => 'form-control select2', 'style' => 'width:100%']); !!}
                </div>
            </div>
            <div class="col-sm-3 col-xs-6">
                    <label for="end_date">@lang('messages.filter_by_date'):</label>
                    <div class="input-group">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar"></i>
                        </span>
                        <input type="text" id="end_date" value="{{@format_date('now')}}" class="form-control" readonly>
                    </div>
            </div>
            @endcomponent
        </div>
    </div>
    <br>
    <div class="box box-solid printable_report">
        <div class="box-header">
            <h3 class="box-title">{{session()->get('business.name')}} - @lang( 'account.balance_sheet') - <span id="hidden_date">{{@format_date('now')}}</span></h3>
        </div>
        <div class="box-body">
            <table class="table table-border-center no-border table-pl-12">
                <thead>
                    <tr class="bg-gray">
                        <th>@lang( 'account.liability')</th>
                        <th>@lang( 'account.assets')</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <table class="table">
                                <tr>
                                    <th>@lang('account.supplier_due'):</th>
                                <td>
                                    <input type="hidden" id="hidden_supplier_due" class="liability">
                                    <span class="remote-data" id="supplier_due">
                                        <i class="fas fa-sync fa-spin fa-fw"></i>
                                    </span>
                                </td>
                                </tr>
                                <tr>
                                    <th>@lang('account.cheques_payable'):</th>
                                    <td>
                                        <input type="hidden" id="hidden_cheques_payable" class="liability">
                                        <span class="remote-data" id="cheques_payable">
                                            <i class="fas fa-sync fa-spin fa-fw"></i>
                                        </span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="table" id="assets_table">
                                <tbody>
                                    <tr>
                                        <th>@lang('account.customer_due'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_customer_due" class="asset">
                                            <span class="remote-data" id="customer_due">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>@lang('account.cheques_receivable'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_cheques_receivable" class="asset">
                                            <span class="remote-data" id="cheques_receivable">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>@lang('report.closing_stock'):</th>
                                        <td>
                                            <input type="hidden" id="hidden_closing_stock" class="asset">
                                            <span class="remote-data" id="closing_stock">
                                                <i class="fas fa-sync fa-spin fa-fw"></i>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th colspan="2">@lang('account.account_balances'):</th>
                                    </tr>
                                </tbody>
                                <tbody id="account_balances" class="pl-20-td">
                                    <tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>
                                </tbody>
                                {{--
                                <tbody>
                                    <tr>
                                        <th colspan="2">@lang('account.capital_accounts'):</th>
                                    </tr>
                                </tbody>
                                <tbody id="capital_account_balances" class="pl-20-td">
                                    <tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>
                                </tbody>
                                --}}
                            </table>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="bg-gray">
                        <td>
                            <table class="table bg-gray mb-0 no-border">
                                <tr>
                                    <th>
                                        @lang('account.total_liability'): 
                                    </th>
                                    <td>
                                        <span id="total_liabilty"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td>
                            <table class="table bg-gray mb-0 no-border">
                                <tr>
                                    <th>
                                        @lang('account.total_assets'): 
                                    </th>
                                    <td>
                                        <span id="total_assets"><i class="fas fa-sync fa-spin fa-fw"></i></span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        <div class="box-footer">
            <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print pull-right" id="btn_print_balance_sheet">
          <i class="fa fa-print"></i> @lang('messages.print')</button>
        </div>
    </div>

</section>
<!-- /.content -->
@stop
@section('css')
<style>
@media print {
    html, body, main, .content-wrapper, section.content, .box, .box-body, .box-header, .table, .table th, .table td {
        background: #fff !important;
        background-color: #fff !important;
        color: #000 !important;
    }
    .no-print, .side-bar, aside { display: none !important; }
}
</style>
@endsection
@section('javascript')

<script type="text/javascript">
    $(document).ready( function(){
        //Date picker
        $('#end_date').datepicker({
            autoclose: true,
            format: datepicker_date_format
        });
        update_balance_sheet();

        $('#end_date').change( function() {
            update_balance_sheet();
            $('#hidden_date').text($(this).val());
        });
        $('#bal_sheet_location_id').change( function() {
            update_balance_sheet();
        });

        $('#btn_print_balance_sheet').on('click', function() {
            print_balance_sheet_clean();
        });
    });

    function print_balance_sheet_clean() {
        var title = ($('.printable_report .box-title').text() || 'Balance Sheet').trim();
        var html = $('.printable_report').clone();
        html.find('.no-print, button, .fa-sync').remove();
        var win = window.open('', '_blank', 'width=900,height=700');
        if (!win) {
            window.print();
            return;
        }
        win.document.write(
            '<!DOCTYPE html><html><head><title>' + title + '</title>' +
            '<style>' +
            'body{font-family:Arial,Helvetica,sans-serif;color:#000;background:#fff;padding:24px;}' +
            'table{width:100%;border-collapse:collapse;}' +
            'th,td{padding:8px 10px;border:1px solid #ccc;text-align:left;vertical-align:top;color:#000;background:#fff;}' +
            '.bg-gray,tfoot td,tfoot th{background:#eee !important;font-weight:700;}' +
            'h3,.box-title{margin:0 0 16px;font-size:18px;}' +
            '.box-header,.box-body,.box-footer{border:0;padding:0;}' +
            'input{display:none;}' +
            '</style></head><body>' +
            '<h3>' + title + '</h3>' +
            html.find('.box-body').html() +
            '</body></html>'
        );
        win.document.close();
        win.focus();
        setTimeout(function() {
            win.print();
            win.close();
        }, 300);
    }

    function update_balance_sheet(){
        var loader = '<i class="fas fa-sync fa-spin fa-fw"></i>';
        $('span.remote-data').each( function() {
            $(this).html(loader);
        });

        $('table#assets_table tbody#account_balances').html('<tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');
        $('table#assets_table tbody#capital_account_balances').html('<tr><td colspan="2"><i class="fas fa-sync fa-spin fa-fw"></i></td></tr>');

        var end_date = $('input#end_date').val();
        var location_id = $('#bal_sheet_location_id').val()
        $.ajax({
            url: "{{action([\App\Http\Controllers\AccountReportsController::class, 'balanceSheet'])}}?end_date=" + end_date + '&location_id=' + location_id, 
            dataType: "json",
            success: function(result){
                $('span#supplier_due').text(__currency_trans_from_en(result.supplier_due, true));
                __write_number($('input#hidden_supplier_due'), result.supplier_due);

                var cheques_payable = result.cheques_payable || 0;
                $('span#cheques_payable').text(__currency_trans_from_en(cheques_payable, true));
                __write_number($('input#hidden_cheques_payable'), cheques_payable);

                $('span#customer_due').text(__currency_trans_from_en(result.customer_due, true));
                __write_number($('input#hidden_customer_due'), result.customer_due);

                var cheques_receivable = result.cheques_receivable || 0;
                $('span#cheques_receivable').text(__currency_trans_from_en(cheques_receivable, true));
                __write_number($('input#hidden_cheques_receivable'), cheques_receivable);

                $('span#closing_stock').text(__currency_trans_from_en(result.closing_stock, true));
                __write_number($('input#hidden_closing_stock'), result.closing_stock);
                var account_balances = result.account_balances;
                $('table#assets_table tbody#account_balances').html('');
                for (var key in account_balances) {
                    var accnt_bal = __currency_trans_from_en(result.account_balances[key]);
                    var accnt_bal_with_sym = __currency_trans_from_en(result.account_balances[key], true);
                    var account_tr = '<tr><td class="pl-20-td">' + key + ':</td><td><input type="hidden" class="asset" value="' + accnt_bal + '">' + accnt_bal_with_sym + '</td></tr>';
                    $('table#assets_table tbody#account_balances').append(account_tr);
                }
                var capital_account_details = result.capital_account_details;
                $('table#assets_table tbody#capital_account_balances').html('');
                for (var key in capital_account_details) {
                    var accnt_bal = __currency_trans_from_en(result.capital_account_details[key]);
                    var accnt_bal_with_sym = __currency_trans_from_en(result.capital_account_details[key], true);
                    var account_tr = '<tr><td class="pl-20-td">' + key + ':</td><td><input type="hidden" class="asset" value="' + accnt_bal + '">' + accnt_bal_with_sym + '</td></tr>';
                    $('table#assets_table tbody#capital_account_balances').append(account_tr);
                }


                var total_liabilty = 0;
                var total_assets = 0;
                $('input.liability').each( function(){
                    total_liabilty += __read_number($(this));
                });
                $('input.asset').each( function(){
                    total_assets += __read_number($(this));
                });

                $('span#total_liabilty').text(__currency_trans_from_en(total_liabilty, true));
                $('span#total_assets').text(__currency_trans_from_en(total_assets, true));
                
            }
        });
    }
</script>

@endsection