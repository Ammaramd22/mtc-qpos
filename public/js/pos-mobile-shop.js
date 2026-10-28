/**
 * Mobile Shop — IMEI picker for POS when mobile_shop module is enabled.
 */
(function ($) {
    'use strict';

    window.__mobile_shop_enabled = window.__mobile_shop_enabled || false;
    window.vspos_mobile_shop_imei_url = window.vspos_mobile_shop_imei_url || '';

    function vsposMobileShopEnabled() {
        return window.__mobile_shop_enabled === true || window.__mobile_shop_enabled === 'true';
    }

    function vsposGetLocationId() {
        var loc = $('#location_id').val();
        if (loc) return loc;
        return $('select#select_location_id').val() || '';
    }

    function vsposLoadImeisForModal($modal) {
        if (!vsposMobileShopEnabled() || !vspos_mobile_shop_imei_url) {
            return;
        }

        var $wrap = $modal.find('.vspos-mobile-imei-wrap');
        if (!$wrap.length) {
            return;
        }

        var productId = $wrap.data('product-id');
        var variationId = $wrap.data('variation-id');
        var locationId = vsposGetLocationId();
        var $list = $wrap.find('.vspos-mobile-imei-list');
        var existing = [];

        try {
            existing = JSON.parse($wrap.find('.vspos-serial-list').attr('data-existing') || '[]');
        } catch (e) {
            existing = [];
        }

        var current = [];
        $modal.find('.vspos-serial-input').each(function () {
            var v = String($(this).val() || '').trim();
            if (v) current.push(v);
        });
        if (current.length) existing = current;

        $list.html('<p class="text-muted"><i class="fa fa-spinner fa-spin"></i> Loading IMEIs…</p>');

        $.getJSON(vspos_mobile_shop_imei_url, {
            product_id: productId,
            variation_id: variationId,
            location_id: locationId
        }).done(function (resp) {
            var imeis = resp.imeis || {};
            var ids = Object.keys(imeis);
            if (!ids.length) {
                $list.html('<p class="text-warning">' + (typeof LANG !== 'undefined' && LANG.mobile_imei_not_found ? LANG.mobile_imei_not_found : 'No IMEIs in stock.') + '</p>');
                return;
            }

            var html = '<div class="vspos-mobile-imei-grid">';
            $.each(imeis, function (id, imei) {
                var checked = existing.indexOf(imei) !== -1 ? ' checked' : '';
                html += '<label class="vspos-mobile-imei-chip"><input type="checkbox" class="vspos-mobile-imei-cb" value="' + imei + '"' + checked + '> ' + imei + '</label>';
            });
            html += '</div>';
            $list.html(html);
            vsposMobileShopSyncFromPicker($modal);
        }).fail(function () {
            $list.html('<p class="text-danger">Could not load IMEIs.</p>');
        });
    }

    function vsposMobileShopSyncFromPicker($modal) {
        var $wrap = $modal.find('.vspos-mobile-imei-wrap');
        if (!$wrap.length) return;

        var selected = [];
        $wrap.find('.vspos-mobile-imei-cb:checked').each(function () {
            selected.push($(this).val());
        });

        var $serialWrap = $modal.find('.vspos-serial-per-qty-wrap');
        if (!$serialWrap.length) return;

        var $list = $serialWrap.find('.vspos-serial-list');
        $list.attr('data-existing', JSON.stringify(selected));

        if (typeof vspos_serial_rebuild_for_modal === 'function') {
            vspos_serial_rebuild_for_modal($modal);
        }
        if (typeof vspos_serial_sync_note === 'function') {
            vspos_serial_sync_note($modal);
        }
    }

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function () {
        vsposLoadImeisForModal($(this));
    });

    $(document).on('change', '.vspos-mobile-imei-cb', function () {
        vsposMobileShopSyncFromPicker($(this).closest('.row_edit_product_price_model'));
    });

    // Re-load when qty changes
    $(document).on('change', 'input.pos_quantity', function () {
        if (!vsposMobileShopEnabled()) return;
        var $tr = $(this).closest('tr');
        var nameAttr = $tr.find('input[name*="[variation_id]"]').attr('name');
        if (!nameAttr) return;
        var match = nameAttr.match(/\[(\d+)\]/);
        if (!match) return;
        var $modal = $('#row_edit_product_price_modal_' + match[1]);
        if ($modal.length) {
            setTimeout(function () { vsposLoadImeisForModal($modal); }, 100);
        }
    });

    /** Push a completed repair charge into the POS cart. */
    function vsposEnsureMobileRepairIdField() {
        var $form = $('form#add_pos_sell_form, form#edit_pos_sell_form').first();
        if (!$form.length) {
            return $();
        }
        var $hid = $('#mobile_repair_id');
        if (!$hid.length) {
            $form.append('<input type="hidden" name="mobile_repair_id" id="mobile_repair_id" value="">');
            $hid = $('#mobile_repair_id');
        }
        return $hid;
    }

    function vsposApplyRepairCheckout(c) {
        if (!c || !c.variation_id) {
            return;
        }

        var locationId = vsposGetLocationId();
        if (!locationId) {
            setTimeout(function () { vsposApplyRepairCheckout(c); }, 400);
            return;
        }

        if (c.contact_id && $('select#customer_id').length) {
            var $cust = $('select#customer_id');
            if ($cust.find('option[value="' + c.contact_id + '"]').length) {
                $cust.val(String(c.contact_id)).trigger('change');
            }
        }

        $.ajax({
            url: '/sells/pos/get_product_row/' + c.variation_id + '/' + locationId,
            dataType: 'json',
            success: function (result) {
                if (!result || !result.success) {
                    toastr.error((result && result.msg) ? result.msg : 'Could not add repair to cart');
                    return;
                }

                if (typeof pos_insert_product_row === 'function') {
                    pos_insert_product_row(result);
                }

                var $row = $('table#pos_table tbody tr').last();
                if (c.unit_price && typeof __write_number === 'function') {
                    __write_number($row.find('input.pos_unit_price'), c.unit_price);
                } else if (c.unit_price) {
                    $row.find('input.pos_unit_price').val(c.unit_price);
                }

                if (c.description) {
                    var $note = $row.find('textarea[name*="[sell_line_note]"]');
                    if ($note.length) {
                        $note.val(c.description);
                    }
                    var $hiddenNote = $row.find('input.pos_sell_line_note');
                    if (!$hiddenNote.length) {
                        $row.find('td.vspos-pos-hidden-fields, td.hide').first().append(
                            '<input type="hidden" name="' + ($row.find('input[name*="[variation_id]"]').attr('name') || '').replace('[variation_id]', '[sell_line_note]') + '" class="pos_sell_line_note" value="">'
                        );
                        $hiddenNote = $row.find('input.pos_sell_line_note');
                    }
                    $hiddenNote.val(c.description);
                }

                if (c.repair_id) {
                    vsposEnsureMobileRepairIdField().val(c.repair_id);
                }

                if (typeof vspos_sync_all_row_edit_modals === 'function') {
                    vspos_sync_all_row_edit_modals();
                } else if (typeof pos_each_row === 'function') {
                    pos_each_row($row);
                }
                if (typeof pos_total_row === 'function') {
                    pos_total_row();
                }

                toastr.success(
                    typeof LANG !== 'undefined' && LANG.mobile_repair_pos_added
                        ? LANG.mobile_repair_pos_added.replace(':ref', c.reference_no || '')
                        : 'Repair ' + (c.reference_no || '') + ' added — finalize payment.'
                );
            },
        });
    }

    function vsposLoadRepairCheckoutToPos() {
        var c = window.__mobile_repair_checkout;
        if (!c || !c.variation_id) {
            return;
        }
        vsposApplyRepairCheckout(c);
        window.__mobile_repair_checkout = null;
    }

    $(function () {
        if (window.__mobile_repair_checkout) {
            setTimeout(vsposLoadRepairCheckoutToPos, 900);
        }
    });

    /* ── Repair payment picker (POS header) ── */
    function vsposLoadPendingRepairsForPos() {
        var url = window.vspos_mobile_repair_pending_url;
        var $loading = $('#pos_mobile_repair_pay_loading');
        var $empty = $('#pos_mobile_repair_pay_empty');
        var $list = $('#pos_mobile_repair_pay_list');

        if (!url) {
            $loading.addClass('hide');
            $empty.removeClass('hide');
            return;
        }

        $loading.removeClass('hide');
        $empty.addClass('hide');
        $list.addClass('hide').empty();

        $.getJSON(url).done(function (resp) {
            $loading.addClass('hide');
            var repairs = (resp && resp.repairs) ? resp.repairs : [];
            if (!repairs.length) {
                $empty.removeClass('hide');
                return;
            }

            var html = '';
            $.each(repairs, function (_, r) {
                html += '<div class="vspos-repair-pay-item" data-id="' + r.id + '">';
                html += '<div class="vspos-repair-pay-item-main">';
                html += '<strong>' + (r.reference_no || '') + '</strong>';
                html += '<span class="vspos-repair-pay-charge">Rs ' + (r.charge_formatted || r.charge) + '</span>';
                html += '</div>';
                html += '<div class="vspos-repair-pay-meta">' + (r.device || '') + ' · IMEI ' + (r.imei || '—') + '</div>';
                if (r.customer && r.customer !== '—') {
                    html += '<div class="vspos-repair-pay-meta">' + r.customer + '</div>';
                }
                if (r.issue) {
                    html += '<div class="vspos-repair-pay-issue">' + $('<div>').text(r.issue).html() + '</div>';
                }
                html += '<button type="button" class="btn btn-success btn-sm vspos-repair-pay-add-btn">';
                html += '<i class="fa fa-cash-register"></i> ';
                html += (typeof LANG !== 'undefined' && LANG.mobile_repair_pos_add_to_cart)
                    ? LANG.mobile_repair_pos_add_to_cart
                    : 'Add to cart';
                html += '</button></div>';
            });
            $list.html(html).removeClass('hide');
        }).fail(function () {
            $loading.addClass('hide');
            $empty.text('Could not load repairs.').removeClass('hide');
        });
    }

    $(document).on('shown.bs.modal', '#pos_mobile_repair_payment_modal', function () {
        vsposLoadPendingRepairsForPos();
    });

    $(document).on('click', '.vspos-repair-pay-add-btn', function () {
        var $item = $(this).closest('.vspos-repair-pay-item');
        var repairId = $item.data('id');
        var baseUrl = window.vspos_mobile_repair_checkout_url || '';
        if (!repairId || !baseUrl) {
            return;
        }

        var $btn = $(this).prop('disabled', true);
        $.getJSON(baseUrl + '/' + repairId + '/pos-checkout').done(function (resp) {
            $btn.prop('disabled', false);
            if (!resp || !resp.success) {
                toastr.error((resp && resp.msg) ? resp.msg : 'Could not load repair');
                return;
            }
            $('#pos_mobile_repair_payment_modal').modal('hide');
            vsposApplyRepairCheckout(resp.checkout);
        }).fail(function () {
            $btn.prop('disabled', false);
            toastr.error('Could not load repair');
        });
    });

    /* ── Bill payments modal (reload / utility / other) ── */
    $(document).on('shown.bs.modal', '#pos_mobile_reload_modal', function () {
        var $pane = $(this).find('.tab-pane.active');
        if ($pane.find('.vspos-bill-field-mobile').length) {
            $pane.find('.vspos-bill-field-mobile').focus();
        }
    });
})(jQuery);
