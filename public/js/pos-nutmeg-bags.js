/**
 * Nutmeg — bag packing on POS sell.
 * Asks bag size + count, adds spice weight line + bag line.
 */
(function ($, window) {
    'use strict';

    var bags = [];
    var askBag = true;
    var pending = null;
    var originalPosProductRow = null;
    var bagsUrl = '/nutmeg/pos-bags';

    function refreshSummary() {
        var bag = bags.find(function (b) {
            return String(b.variation_id) === String($('#nbag_bag_id').val());
        });
        var n = __read_number($('#nbag_qty')) || 0;
        var skip = $('#nbag_skip').is(':checked');
        if (skip || !bag) {
            $('#nbag_weight').text('—');
            $('#nbag_bags_lbl').text('—');
            return;
        }
        var weight = n * (parseFloat(bag.fill_weight) || 0);
        $('#nbag_weight').text(__number_f(weight));
        $('#nbag_bags_lbl').text(n + ' × ' + (bag.label || ''));
    }

    function openBagModal(variationId, purchaseLineId, weightingId, qty) {
        if (!bags.length) {
            if (typeof originalPosProductRow === 'function') {
                originalPosProductRow(variationId, purchaseLineId, weightingId, qty);
            }
            return;
        }
        pending = {
            variationId: variationId,
            purchaseLineId: purchaseLineId,
            weightingId: weightingId,
            qty: qty,
        };
        var $sel = $('#nbag_bag_id').empty();
        bags.forEach(function (b) {
            $sel.append(
                $('<option>')
                    .val(b.variation_id)
                    .text(b.label + ' (' + b.fill_weight + ')')
            );
        });
        $('#nbag_qty').val(qty && qty > 0 ? qty : 1);
        $('#nbag_skip').prop('checked', false);
        refreshSummary();
        $('#nutmeg_bag_modal').modal('show');
    }

    function addWithBag() {
        if (!pending || typeof originalPosProductRow !== 'function') {
            return;
        }
        var skip = $('#nbag_skip').is(':checked');
        var bag = bags.find(function (b) {
            return String(b.variation_id) === String($('#nbag_bag_id').val());
        });
        var n = __read_number($('#nbag_qty')) || 0;

        $('#nutmeg_bag_modal').modal('hide');

        if (skip || !bag || n <= 0) {
            originalPosProductRow(
                pending.variationId,
                pending.purchaseLineId,
                pending.weightingId,
                pending.qty
            );
            pending = null;
            return;
        }

        var weight = n * (parseFloat(bag.fill_weight) || 0);
        originalPosProductRow(pending.variationId, pending.purchaseLineId, pending.weightingId, weight);
        setTimeout(function () {
            originalPosProductRow(bag.variation_id, null, null, n);
        }, 350);
        pending = null;
    }

    function wrapPosProductRow() {
        if (typeof window.pos_product_row !== 'function') {
            return false;
        }
        if (window.pos_product_row.__nutmeg_wrapped) {
            return true;
        }
        originalPosProductRow = window.pos_product_row;
        window.pos_product_row = function (variation_id, purchase_line_id, weighting_id, qty) {
            if (askBag && bags.length && !$('#is_pop_mode').length) {
                var isBag = bags.some(function (b) {
                    return String(b.variation_id) === String(variation_id);
                });
                if (!isBag) {
                    openBagModal(variation_id, purchase_line_id, weighting_id, qty);
                    return;
                }
            }
            return originalPosProductRow(variation_id, purchase_line_id, weighting_id, qty);
        };
        window.pos_product_row.__nutmeg_wrapped = true;
        return true;
    }

    $(document).ready(function () {
        if ($('#is_pop_mode').length || !$('#nutmeg_bag_modal').length) {
            return;
        }
        if (window.__nutmeg_pos_bags_url) {
            bagsUrl = window.__nutmeg_pos_bags_url;
        }

        $.getJSON(bagsUrl, function (res) {
            if (!res || !res.enabled) {
                return;
            }
            bags = res.bags || [];
            askBag = !!res.ask_bag_on_sell && bags.length > 0;
            if (!askBag) {
                return;
            }
            var tries = 0;
            var timer = setInterval(function () {
                tries++;
                if (wrapPosProductRow() || tries > 40) {
                    clearInterval(timer);
                }
            }, 200);
        });

        $(document).on('change keyup', '#nbag_bag_id, #nbag_qty, #nbag_skip', refreshSummary);
        $('#nbag_confirm').on('click', addWithBag);
    });
})(jQuery, window);
