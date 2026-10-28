/**
 * POS Exchange / Trade-In (used tyre / battery) — not a bill discount.
 * Attaches trade-in credit to a cart product row and subtracts from payable.
 */
(function ($) {
    'use strict';

    var optionsCache = null;
    var loadingOptions = false;

    function uf(val) {
        if (typeof __read_number === 'function' && val && val.jquery) {
            return __read_number(val);
        }
        var n = parseFloat(String(val == null ? 0 : val).replace(/,/g, ''));
        return isNaN(n) ? 0 : n;
    }

    function money(n) {
        if (typeof __currency_trans_from_en === 'function') {
            return __currency_trans_from_en(n, true);
        }
        return Number(n || 0).toFixed(2);
    }

    function getRow($btnOrRow) {
        return $btnOrRow.closest('tr.product_row');
    }

    function ensureHiddenFields($row) {
        if ($row.find('.vspos-ti-enabled').length) {
            return;
        }
        var idx = $row.data('row_index');
        var prefix = 'products[' + idx + '][trade_in]';
        var html =
            '<input type="hidden" class="vspos-ti-enabled" name="' + prefix + '[enabled]" value="0">' +
            '<input type="hidden" class="vspos-ti-item_name" name="' + prefix + '[item_name]" value="">' +
            '<input type="hidden" class="vspos-ti-tyre_size" name="' + prefix + '[tyre_size]" value="">' +
            '<input type="hidden" class="vspos-ti-brand_id" name="' + prefix + '[brand_id]" value="">' +
            '<input type="hidden" class="vspos-ti-brand_name" name="' + prefix + '[brand_name]" value="">' +
            '<input type="hidden" class="vspos-ti-category_id" name="' + prefix + '[category_id]" value="">' +
            '<input type="hidden" class="vspos-ti-sub_category_id" name="' + prefix + '[sub_category_id]" value="">' +
            '<input type="hidden" class="vspos-ti-condition" name="' + prefix + '[condition]" value="">' +
            '<input type="hidden" class="vspos-ti-usage_percent" name="' + prefix + '[usage_percent]" value="">' +
            '<input type="hidden" class="vspos-ti-barcode" name="' + prefix + '[barcode]" value="">' +
            '<input type="hidden" class="vspos-ti-quantity" name="' + prefix + '[quantity]" value="1">' +
            '<input type="hidden" class="vspos-ti-exchange_value" name="' + prefix + '[exchange_value]" value="0">' +
            '<input type="hidden" class="vspos-ti-notes" name="' + prefix + '[notes]" value="">' +
            '<input type="hidden" class="vspos-ti-reason" name="' + prefix + '[reason]" value="Exchange / Trade-In">' +
            '<input type="hidden" class="vspos-ti-old_product_id" name="' + prefix + '[old_product_id]" value="">';
        $row.find('td.pos-td-product').append(html);
    }

    function readTradeIn($row) {
        ensureHiddenFields($row);
        if ($row.find('.vspos-ti-enabled').val() !== '1') {
            return null;
        }
        return {
            enabled: 1,
            item_name: $row.find('.vspos-ti-item_name').val(),
            tyre_size: $row.find('.vspos-ti-tyre_size').val(),
            brand_id: $row.find('.vspos-ti-brand_id').val(),
            brand_name: $row.find('.vspos-ti-brand_name').val(),
            category_id: $row.find('.vspos-ti-category_id').val(),
            sub_category_id: $row.find('.vspos-ti-sub_category_id').val(),
            condition: $row.find('.vspos-ti-condition').val(),
            usage_percent: $row.find('.vspos-ti-usage_percent').val(),
            barcode: $row.find('.vspos-ti-barcode').val(),
            quantity: uf($row.find('.vspos-ti-quantity').val()) || 1,
            exchange_value: uf($row.find('.vspos-ti-exchange_value').val()),
            notes: $row.find('.vspos-ti-notes').val(),
            old_product_id: $row.find('.vspos-ti-old_product_id').val(),
        };
    }

    function writeTradeIn($row, data) {
        ensureHiddenFields($row);
        if (!data) {
            $row.find('.vspos-ti-enabled').val('0');
            $row.find('input[class^="vspos-ti-"]').not('.vspos-ti-enabled').val('');
            $row.find('.vspos-ti-quantity').val('1');
            $row.find('.vspos-ti-exchange_value').val('0');
            $row.find('.vspos-tradein-badge').remove();
            $row.removeClass('vspos-has-tradein');
            return;
        }
        $row.find('.vspos-ti-enabled').val('1');
        $row.find('.vspos-ti-item_name').val(data.item_name || '');
        $row.find('.vspos-ti-tyre_size').val(data.tyre_size || '');
        $row.find('.vspos-ti-brand_id').val(data.brand_id || '');
        $row.find('.vspos-ti-brand_name').val(data.brand_name || '');
        $row.find('.vspos-ti-category_id').val(data.category_id || '');
        $row.find('.vspos-ti-sub_category_id').val(data.sub_category_id || '');
        $row.find('.vspos-ti-condition').val(data.condition || 'Used');
        $row.find('.vspos-ti-usage_percent').val(data.usage_percent || '');
        $row.find('.vspos-ti-barcode').val(data.barcode || '');
        $row.find('.vspos-ti-quantity').val(data.quantity || 1);
        $row.find('.vspos-ti-exchange_value').val(data.exchange_value || 0);
        $row.find('.vspos-ti-notes').val(data.notes || '');
        $row.find('.vspos-ti-old_product_id').val(data.old_product_id || '');
        $row.addClass('vspos-has-tradein');
        renderBadge($row, data);
    }

    function renderBadge($row, data) {
        $row.find('.vspos-tradein-badge').remove();
        var labelParts = [];
        if (data.brand_name) labelParts.push(data.brand_name);
        if (data.tyre_size) labelParts.push(data.tyre_size);
        if (data.condition) labelParts.push(data.condition);
        if (data.item_name && !labelParts.length) labelParts.push(data.item_name);
        var usage = data.usage_percent ? (data.usage_percent + '% used') : '';
        var credit = (uf(data.exchange_value) * (uf(data.quantity) || 1));
        var html =
            '<div class="vspos-tradein-badge">' +
            '<strong>Exchange / Trade-In</strong><br>' +
            (labelParts.join(' ') || data.item_name || 'Old item') +
            (usage ? '<br>' + usage : '') +
            '<br><span class="vspos-ti-val">Exchange Value: −' + money(credit) + '</span>' +
            '</div>';
        $row.find('.vspos-line-meta').append(html);
    }

    function sumTradeInCredit() {
        var total = 0;
        $('#pos_table tbody tr.product_row').each(function () {
            var ti = readTradeIn($(this));
            if (ti && ti.exchange_value > 0) {
                total += ti.exchange_value * (ti.quantity || 1);
            }
        });
        return total;
    }

    window.vspos_get_tradein_credit = sumTradeInCredit;

    function updateTradeInSummary() {
        var credit = sumTradeInCredit();
        var $amt = $('#trade_in_amount_display');
        if ($amt.length) {
            $amt.text(typeof __currency_trans_from_en === 'function'
                ? __currency_trans_from_en(credit, false)
                : credit.toFixed(2));
        }
        $('input#trade_in_total').val(credit);

        var payableText = $('span#total_payable').text();
        // Refund banner is set from calculate_billing_details hook via data attr
        var refund = uf($('#trade_in_refund_due').val());
        var $banner = $('#vspos_tradein_refund_banner');
        if (refund > 0.0001) {
            $banner.html('<i class="fas fa-exclamation-circle"></i> Customer owed (refund / balance): <strong>' + money(refund) + '</strong>').show();
        } else {
            $banner.hide().empty();
        }
        return credit;
    }

    window.vspos_update_tradein_summary = updateTradeInSummary;

    function fillSubcategories(parentId, selected) {
        var $sub = $('#vspos_ti_sub_category_id');
        $sub.empty().append('<option value="">— None —</option>');
        if (!optionsCache || !optionsCache.subcategories) return;
        optionsCache.subcategories.forEach(function (sc) {
            if (String(sc.parent_id) === String(parentId)) {
                $sub.append($('<option/>').val(sc.id).text(sc.name));
            }
        });
        if (selected) $sub.val(selected);
    }

    function loadOptions(cb) {
        if (optionsCache) {
            cb(optionsCache);
            return;
        }
        if (loadingOptions) {
            setTimeout(function () { loadOptions(cb); }, 200);
            return;
        }
        loadingOptions = true;
        $.ajax({
            url: '/sells/pos/trade-in-options',
            method: 'GET',
            dataType: 'json',
            data: { location_id: $('#location_id').val() },
        }).done(function (res) {
            loadingOptions = false;
            if (res && res.success) {
                optionsCache = res;
                cb(optionsCache);
            } else {
                toastr.error((res && res.msg) || 'Could not load trade-in options');
            }
        }).fail(function () {
            loadingOptions = false;
            toastr.error('Could not load trade-in options');
        });
    }

    function populateSelects(opts) {
        var $cat = $('#vspos_ti_category_id').empty();
        (opts.categories || []).forEach(function (c) {
            $cat.append($('<option/>').val(c.id).text(c.name));
        });
        if (opts.default_category_id) {
            $cat.val(opts.default_category_id);
        }

        var $brand = $('#vspos_ti_brand_id').empty().append('<option value="">— Select / type below —</option>');
        (opts.brands || []).forEach(function (b) {
            $brand.append($('<option/>').val(b.id).text(b.name));
        });

        var $cond = $('#vspos_ti_condition').empty();
        (opts.conditions || ['Used']).forEach(function (c) {
            $cond.append($('<option/>').val(c).text(c));
        });

        fillSubcategories($cat.val());
    }

    function openModal($row) {
        ensureHiddenFields($row);
        var idx = $row.data('row_index');
        var name = $.trim($row.find('.vspos-line-name').first().text()) || 'Product';
        $('#vspos_tradein_row').val(idx);
        $('#vspos_tradein_new_name').text(name);

        loadOptions(function (opts) {
            populateSelects(opts);
            var existing = readTradeIn($row);
            if (existing) {
                $('#vspos_ti_item_name').val(existing.item_name || '');
                $('#vspos_ti_tyre_size').val(existing.tyre_size || '');
                $('#vspos_ti_brand_id').val(existing.brand_id || '');
                $('#vspos_ti_brand_name').val(existing.brand_name || '');
                if (existing.category_id) {
                    $('#vspos_ti_category_id').val(existing.category_id);
                }
                fillSubcategories($('#vspos_ti_category_id').val(), existing.sub_category_id);
                $('#vspos_ti_condition').val(existing.condition || 'Used');
                $('#vspos_ti_usage').val(existing.usage_percent || '');
                $('#vspos_ti_barcode').val(existing.barcode || '');
                $('#vspos_ti_qty').val(existing.quantity || 1);
                $('#vspos_ti_value').val(existing.exchange_value || '');
                $('#vspos_ti_notes').val(existing.notes || '');
            } else {
                $('#vspos_ti_item_name').val('');
                $('#vspos_ti_tyre_size').val('');
                $('#vspos_ti_brand_id').val('');
                $('#vspos_ti_brand_name').val('');
                $('#vspos_ti_usage').val('');
                $('#vspos_ti_barcode').val('');
                $('#vspos_ti_qty').val(1);
                $('#vspos_ti_value').val('');
                $('#vspos_ti_notes').val('');
                $('#vspos_ti_condition').val('Used');
                fillSubcategories($('#vspos_ti_category_id').val());
            }
            $('#vspos_tradein_modal').modal('show');
            setTimeout(function () { $('#vspos_ti_value').focus(); }, 300);
        });
    }

    function applyFromModal() {
        var idx = $('#vspos_tradein_row').val();
        var $row = $('#pos_table tbody tr.product_row[data-row_index="' + idx + '"]');
        if (!$row.length) {
            toastr.error('Cart line not found');
            return;
        }
        var value = uf($('#vspos_ti_value').val());
        if (value <= 0) {
            toastr.error('Enter exchange value');
            $('#vspos_ti_value').focus();
            return;
        }
        var brandId = $('#vspos_ti_brand_id').val();
        var brandName = $.trim($('#vspos_ti_brand_name').val());
        if (!brandName && brandId) {
            brandName = $('#vspos_ti_brand_id option:selected').text();
        }
        var data = {
            item_name: $.trim($('#vspos_ti_item_name').val()),
            tyre_size: $.trim($('#vspos_ti_tyre_size').val()),
            brand_id: brandId,
            brand_name: brandName,
            category_id: $('#vspos_ti_category_id').val(),
            sub_category_id: $('#vspos_ti_sub_category_id').val(),
            condition: $('#vspos_ti_condition').val() || 'Used',
            usage_percent: $('#vspos_ti_usage').val(),
            barcode: $.trim($('#vspos_ti_barcode').val()),
            quantity: uf($('#vspos_ti_qty').val()) || 1,
            exchange_value: value,
            notes: $.trim($('#vspos_ti_notes').val()),
        };
        if (!data.item_name) {
            data.item_name = [data.brand_name, data.tyre_size, data.condition].filter(Boolean).join(' ') || 'Trade-In Item';
        }
        writeTradeIn($row, data);
        $('#vspos_tradein_modal').modal('hide');
        if (typeof pos_total_row === 'function') {
            pos_total_row();
        } else {
            updateTradeInSummary();
        }
        toastr.success('Exchange / Trade-In added');
    }

    function clearFromModal() {
        var idx = $('#vspos_tradein_row').val();
        var $row = $('#pos_table tbody tr.product_row[data-row_index="' + idx + '"]');
        writeTradeIn($row, null);
        $('#vspos_tradein_modal').modal('hide');
        if (typeof pos_total_row === 'function') {
            pos_total_row();
        } else {
            updateTradeInSummary();
        }
    }

    function injectExchangeButtons() {
        $('#pos_table tbody tr.product_row').each(function () {
            var $row = $(this);
            if ($row.find('.vspos-line-exchange').length) {
                return;
            }
            // Skip inline return lines
            if ($row.hasClass('vspos-inline-return')) {
                return;
            }
            var $btn = $(
                '<button type="button" class="vspos-line-exchange" title="Exchange / Trade-In" aria-label="Exchange / Trade-In">' +
                '<i class="fas fa-exchange-alt"></i></button>'
            );
            var $action = $row.find('td.pos-td-action');
            if ($action.length) {
                $action.prepend($btn);
            }
            ensureHiddenFields($row);
        });
    }

    $(document).on('click', '.vspos-line-exchange', function (e) {
        e.preventDefault();
        e.stopPropagation();
        openModal(getRow($(this)));
    });

    $(document).on('click', '#vspos_ti_add', function (e) {
        e.preventDefault();
        applyFromModal();
    });

    $(document).on('click', '#vspos_ti_clear', function (e) {
        e.preventDefault();
        clearFromModal();
    });

    $(document).on('change', '#vspos_ti_category_id', function () {
        fillSubcategories($(this).val());
    });

    $(document).on('change', '#vspos_ti_brand_id', function () {
        var t = $(this).find('option:selected').text();
        if ($(this).val()) {
            $('#vspos_ti_brand_name').val(t);
        }
    });

    // After product rows inserted
    $(document).on('DOMNodeInserted', '#pos_table tbody', function () {
        // deprecated path — also use MutationObserver below
    });

    if (window.MutationObserver) {
        $(function () {
            var tbody = document.querySelector('#pos_table tbody');
            if (!tbody) return;
            var mo = new MutationObserver(function () {
                injectExchangeButtons();
            });
            mo.observe(tbody, { childList: true, subtree: false });
            injectExchangeButtons();
        });
    } else {
        $(function () {
            injectExchangeButtons();
            setInterval(injectExchangeButtons, 1500);
        });
    }

    $(document).on('invoice_total_calculated', function () {
        updateTradeInSummary();
    });

    // Patch calculate_billing_details once available
    function patchBilling() {
        if (typeof window.calculate_billing_details !== 'function') {
            return false;
        }
        if (window.calculate_billing_details.__vspos_tradein_patched) {
            return true;
        }
        var original = window.calculate_billing_details;
        window.calculate_billing_details = function (price_total) {
            original(price_total);
            var credit = sumTradeInCredit();
            var finalInput = uf($('input#final_total_input'));
            var before = finalInput;
            var after = before - credit;
            var refund = 0;
            if (after < 0) {
                refund = Math.abs(after);
                after = 0;
            }
            $('input#trade_in_refund_due').val(refund);
            __write_number($('input#final_total_input'), after);
            var curr_exchange_rate = 1;
            if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
                curr_exchange_rate = __read_number($('#exchange_rate'));
            }
            var shown_total = after * curr_exchange_rate;
            $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
            $('span.total_payable_span').text(__currency_trans_from_en(after, true));
            if ($('form#edit_pos_sell_form').length == 0 && $('form#edit_sell_form').length == 0) {
                __write_number($('.payment-amount').first(), after);
            }
            updateTradeInSummary();
            if (typeof calculate_balance_due === 'function') {
                calculate_balance_due();
            }
        };
        window.calculate_billing_details.__vspos_tradein_patched = true;
        return true;
    }

    $(function () {
        var tries = 0;
        var t = setInterval(function () {
            tries++;
            if (patchBilling() || tries > 40) {
                clearInterval(t);
            }
        }, 250);
    });
})(jQuery);
