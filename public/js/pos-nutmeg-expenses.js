/**
 * Nutmeg POP — product expenses on purchase lines (capitalized into stock/AVCO).
 */
(function ($) {
    'use strict';

    if (!$('#is_pop_mode').length) {
        return;
    }

    var $activeRow = null;

    function expenseTotalFromJson(raw) {
        try {
            var arr = typeof raw === 'string' ? JSON.parse(raw || '[]') : raw;
            if (!Array.isArray(arr)) {
                return 0;
            }
            return arr.reduce(function (s, e) {
                return s + (parseFloat(e.amount) || 0);
            }, 0);
        } catch (e) {
            return 0;
        }
    }

    window.pos_row_product_expense_total = function (tr) {
        return expenseTotalFromJson(tr.find('.product_expenses_json').val());
    };

    function refreshBadge(tr) {
        var total = window.pos_row_product_expense_total(tr);
        var $badge = tr.find('.pop-pe-badge');
        if (total > 0) {
            $badge.removeClass('hide').text(__currency_trans_from_en(total, false, true));
        } else {
            $badge.addClass('hide').text('0');
        }
    }

    function rebuildLineTotal(tr) {
        var qty = __read_number(tr.find('input.pos_quantity'));
        var unit = __read_number(tr.find('input.pos_unit_price_inc_tax'));
        if (!tr.find('input.pos_unit_price_inc_tax').length) {
            unit = __read_number(tr.find('input.pos_unit_price'));
        }
        var line_total = qty * unit + window.pos_row_product_expense_total(tr);
        __write_number(tr.find('input.pos_line_total'), line_total, false);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        if (typeof pos_total_row === 'function') {
            pos_total_row();
        }
    }

    function openModal(tr) {
        $activeRow = tr;
        var expenses = [];
        try {
            expenses = JSON.parse(tr.find('.product_expenses_json').val() || '[]');
        } catch (e) {
            expenses = [];
        }
        if (!expenses.length) {
            expenses = [{ name: '', amount: '' }];
        }
        var $tbody = $('#pop_pe_modal_tbody').empty();
        expenses.forEach(function (e) {
            $tbody.append(rowHtml(e.name || '', e.amount || ''));
        });
        $('#pop_product_expenses_modal').modal('show');
    }

    function rowHtml(name, amount) {
        return (
            '<tr>' +
            '<td><input type="text" class="form-control input-sm pop-pe-name" value="' +
            $('<div>').text(name).html() +
            '" placeholder="Transport"></td>' +
            '<td><input type="text" class="form-control input-sm input_number pop-pe-amount" value="' +
            (amount !== '' && amount !== null && amount !== undefined ? amount : '') +
            '"></td>' +
            '<td><button type="button" class="btn btn-xs btn-danger pop-pe-remove"><i class="fa fa-times"></i></button></td>' +
            '</tr>'
        );
    }

    $(document).on('click', '.pop-product-expenses-btn', function (e) {
        e.preventDefault();
        openModal($(this).closest('tr.product_row'));
    });

    $(document).on('click', '#pop_pe_add_row', function (e) {
        e.preventDefault();
        $('#pop_pe_modal_tbody').append(rowHtml('', ''));
    });

    $(document).on('click', '.pop-pe-remove', function (e) {
        e.preventDefault();
        var $tbody = $('#pop_pe_modal_tbody');
        if ($tbody.find('tr').length <= 1) {
            $(this).closest('tr').find('input').val('');
        } else {
            $(this).closest('tr').remove();
        }
    });

    $(document).on('click', '#pop_pe_save', function (e) {
        e.preventDefault();
        if (!$activeRow || !$activeRow.length) {
            return;
        }
        var expenses = [];
        $('#pop_pe_modal_tbody tr').each(function () {
            var name = $.trim($(this).find('.pop-pe-name').val() || '');
            var amount = __read_number($(this).find('.pop-pe-amount'));
            if (!name && !amount) {
                return;
            }
            if (!name) {
                name = 'Expense';
            }
            if (!amount) {
                return;
            }
            expenses.push({ name: name, amount: amount });
        });
        $activeRow.find('.product_expenses_json').val(JSON.stringify(expenses));
        refreshBadge($activeRow);
        rebuildLineTotal($activeRow);
        $('#pop_product_expenses_modal').modal('hide');
    });

    // After normal qty/price updates, re-add expenses into line total
    $(document).on('change', 'table#pos_table tbody tr.product_row input.pos_quantity, table#pos_table tbody tr.product_row input.pos_unit_price, table#pos_table tbody tr.product_row input.pos_unit_price_inc_tax', function () {
        var tr = $(this).closest('tr.product_row');
        if (!tr.find('.product_expenses_json').length) {
            return;
        }
        setTimeout(function () {
            rebuildLineTotal(tr);
        }, 0);
    });
})(jQuery);
