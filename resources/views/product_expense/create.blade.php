@extends('layouts.app')
@section('title', __('lang_v1.product_expenses'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.product_expenses')
        <small>@lang('lang_v1.product_expenses_help')</small>
    </h1>
</section>

<section class="content">
    {!! Form::open(['url' => action([\App\Http\Controllers\ProductExpenseController::class, 'store']), 'method' => 'post', 'id' => 'product_expense_form']) !!}

    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.select_purchase_invoice')])
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('contact_id', __('purchase.supplier') . ' (' . __('lang_v1.optional') . '):') !!}
                    {!! Form::select('contact_id', $suppliers, null, [
                        'class' => 'form-control select2',
                        'id' => 'pe_supplier_id',
                        'placeholder' => __('lang_v1.all'),
                        'style' => 'width:100%',
                    ]) !!}
                    <p class="help-block">@lang('lang_v1.product_expense_supplier_help')</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('purchase_id', __('lang_v1.purchase_invoice') . ':*') !!}
                    <select name="purchase_id" id="pe_purchase_id" class="form-control" style="width:100%" required>
                        <option value="">@lang('messages.please_select')</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" class="btn btn-default btn-block" id="pe_load_purchase">
                        <i class="fa fa-refresh"></i> @lang('lang_v1.load')
                    </button>
                </div>
            </div>
        </div>
        <div id="pe_purchase_meta" class="hide tw-mb-2">
            <span class="label label-default" id="pe_meta_ref"></span>
            <span class="text-muted" id="pe_meta_supplier"></span>
            <span class="text-muted" id="pe_meta_date"></span>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.product_expenses')])
        <div id="pe_lines_empty" class="text-muted">
            @lang('lang_v1.product_expense_select_invoice_first')
        </div>
        <div id="pe_lines_wrap" class="hide">
            <div class="table-responsive pvs-table-scroll">
                <table class="table table-bordered table-striped" id="pe_lines_table">
                    <thead>
                        <tr>
                            <th>@lang('sale.product')</th>
                            <th>@lang('lang_v1.quantity')</th>
                            <th>@lang('lang_v1.buy_unit_cost')</th>
                            <th>@lang('lang_v1.product_expenses')</th>
                            <th>@lang('lang_v1.landed_unit_cost')</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-12 text-right">
                    <strong>@lang('lang_v1.expense_total'):</strong>
                    <span id="pe_grand_expense_total">0</span>
                </div>
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pe_submit_btn" disabled>
                <i class="fa fa-save"></i> @lang('messages.save')
            </button>
            <a href="{{ action([\App\Http\Controllers\ProductExpenseController::class, 'index']) }}" class="tw-dw-btn tw-dw-btn-default">
                @lang('messages.cancel')
            </a>
        </div>
    </div>

    {!! Form::close() !!}
</section>
@endsection

@section('javascript')
<script type="text/javascript">
(function () {
    var invoicesUrl = @json(action([\App\Http\Controllers\ProductExpenseController::class, 'getInvoices']));
    var linesUrlTpl = @json(url('product-expenses/lines'));
    var preselectId = @json($purchase_id ? (string) $purchase_id : null);

    function expenseRowHtml(lineId, ei, name, amount) {
        name = name || '';
        amount = (amount === 0 || amount) ? amount : '';
        return '<tr class="pe-exp-row">' +
            '<td><input type="text" name="lines[' + lineId + '][product_expenses][' + ei + '][name]" class="form-control input-sm pe-exp-name" value="' + $('<div>').text(name).html() + '" placeholder="Transport"></td>' +
            '<td style="width:110px;"><input type="text" name="lines[' + lineId + '][product_expenses][' + ei + '][amount]" class="form-control input-sm input_number pe-exp-amount" value="' + (amount !== '' ? __number_f(amount) : '') + '"></td>' +
            '<td style="width:32px;"><button type="button" class="btn btn-xs btn-danger pe-exp-remove"><i class="fa fa-times"></i></button></td>' +
            '</tr>';
    }

    function lineExpenseTotal($block) {
        var t = 0;
        $block.find('.pe-exp-amount').each(function () {
            t += __read_number($(this));
        });
        return t;
    }

    function refreshLineLanded($tr) {
        var qty = parseFloat($tr.data('qty')) || 0;
        var buy = parseFloat($tr.data('buy')) || 0;
        var exp = lineExpenseTotal($tr);
        var landed = qty > 0 ? buy + (exp / qty) : buy;
        $tr.find('.pe-landed-cost').text(__currency_trans_from_en(landed, true));
        $tr.find('.pe-line-exp-total').text(__currency_trans_from_en(exp, true));
        refreshGrandExpense();
    }

    function refreshGrandExpense() {
        var g = 0;
        $('#pe_lines_table tbody tr.pe-product-row').each(function () {
            g += lineExpenseTotal($(this));
        });
        $('#pe_grand_expense_total').text(__currency_trans_from_en(g, true));
    }

    function initPurchaseSelect() {
        $('#pe_purchase_id').select2({
            ajax: {
                url: invoicesUrl,
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term,
                        contact_id: $('#pe_supplier_id').val() || '',
                    };
                },
                processResults: function (data) {
                    return { results: data.results || [] };
                },
                cache: true,
            },
            placeholder: LANG.please_select,
            width: '100%',
            allowClear: true,
        });
    }

    function loadLines(purchaseId) {
        if (!purchaseId) {
            return;
        }
        $.ajax({
            url: linesUrlTpl + '/' + purchaseId,
            dataType: 'json',
            success: function (res) {
                var p = res.purchase || {};
                $('#pe_purchase_meta').removeClass('hide');
                $('#pe_meta_ref').text(p.ref_no || ('#' + p.id));
                $('#pe_meta_supplier').text(p.supplier ? ' — ' + p.supplier : '');
                $('#pe_meta_date').text(p.transaction_date ? ' — ' + p.transaction_date : '');

                if (p.contact_id && !$('#pe_supplier_id').val()) {
                    $('#pe_supplier_id').val(p.contact_id).trigger('change.select2');
                }

                var $tbody = $('#pe_lines_table tbody').empty();
                (res.lines || []).forEach(function (line) {
                    var expenses = line.product_expenses && line.product_expenses.length
                        ? line.product_expenses
                        : [{ name: '', amount: '' }];
                    var expHtml = '<table class="table table-condensed pe-exp-table" style="margin:0;"><tbody>';
                    expenses.forEach(function (e, i) {
                        expHtml += expenseRowHtml(line.purchase_line_id, i, e.name, e.amount);
                    });
                    expHtml += '</tbody></table>' +
                        '<button type="button" class="btn btn-xs btn-primary pe-exp-add" data-line-id="' + line.purchase_line_id + '"><i class="fa fa-plus"></i> ' + @json(__('lang_v1.add_product_expense')) + '</button>' +
                        '<div class="text-muted" style="font-size:11px;margin-top:4px;">' + @json(__('lang_v1.expense_total')) + ': <span class="pe-line-exp-total">0</span></div>';

                    var $tr = $('<tr class="pe-product-row"></tr>');
                    $tr.attr('data-qty', line.quantity);
                    $tr.attr('data-buy', line.buy_unit_cost);
                    $tr.append('<td><strong>' + $('<div>').text(line.product_name).html() + '</strong><br><small class="text-muted">' + $('<div>').text(line.sku).html() + '</small></td>');
                    $tr.append('<td>' + __number_f(line.quantity) + '</td>');
                    $tr.append('<td>' + __currency_trans_from_en(line.buy_unit_cost, true) + '</td>');
                    $tr.append('<td class="pe-exp-cell">' + expHtml + '</td>');
                    $tr.append('<td class="pe-landed-cost"></td>');
                    $tbody.append($tr);
                    refreshLineLanded($tr);
                });

                $('#pe_lines_empty').addClass('hide');
                $('#pe_lines_wrap').removeClass('hide');
                $('#pe_submit_btn').prop('disabled', false);
            },
            error: function () {
                toastr.error(LANG.something_went_wrong);
            },
        });
    }

    $(document).ready(function () {
        $('#pe_supplier_id').select2({ width: '100%', allowClear: true });
        initPurchaseSelect();

        $('#pe_supplier_id').on('change', function () {
            $('#pe_purchase_id').val(null).trigger('change');
            $('#pe_lines_wrap').addClass('hide');
            $('#pe_lines_empty').removeClass('hide');
            $('#pe_purchase_meta').addClass('hide');
            $('#pe_submit_btn').prop('disabled', true);
        });

        $('#pe_purchase_id').on('change', function () {
            var id = $(this).val();
            if (id) {
                loadLines(id);
            }
        });

        $('#pe_load_purchase').on('click', function () {
            var id = $('#pe_purchase_id').val();
            if (id) {
                loadLines(id);
            } else {
                toastr.warning(@json(__('lang_v1.product_expense_select_invoice_first')));
            }
        });

        $(document).on('click', '.pe-exp-add', function (e) {
            e.preventDefault();
            var lineId = $(this).data('line-id');
            var $tbody = $(this).closest('td').find('.pe-exp-table tbody');
            var ei = $tbody.find('tr').length;
            $tbody.append(expenseRowHtml(lineId, ei, '', ''));
        });

        $(document).on('click', '.pe-exp-remove', function (e) {
            e.preventDefault();
            var $tr = $(this).closest('tr.pe-product-row');
            var $tbody = $(this).closest('tbody');
            if ($tbody.find('tr').length <= 1) {
                $(this).closest('tr').find('input').val('');
            } else {
                $(this).closest('tr').remove();
            }
            refreshLineLanded($tr);
        });

        $(document).on('change keyup', '.pe-exp-amount, .pe-exp-name', function () {
            refreshLineLanded($(this).closest('tr.pe-product-row'));
        });

        if (preselectId) {
            $.getJSON(invoicesUrl, { q: preselectId }, function (data) {
                var row = (data.results || []).find(function (r) { return String(r.id) === String(preselectId); });
                if (!row) {
                    row = { id: preselectId, text: '#' + preselectId };
                }
                var opt = new Option(row.text, row.id, true, true);
                $('#pe_purchase_id').append(opt).trigger('change');
            });
        }
    });
})();
</script>
@endsection
