/**
 * POS Quick Entry
 * 1) Barcode / SKU  → Enter → fills Product name
 * 2) Product name   → Enter → Qty
 * 3) Qty (edit)     → Enter → Rate
 * 4) Rate (edit)    → Enter → add to cart
 */
(function (window, document, $) {
    'use strict';

    if (!$) {
        return;
    }

    window.VSPOS_QUICK_ENTRY = 1;

    function enabled() {
        return !!document.getElementById('vspos_quick_entry');
    }

    function moneyWrite($el, amount) {
        amount = parseFloat(amount) || 0;
        if (typeof window.__write_number === 'function') {
            window.__write_number($el, amount);
        } else {
            $el.val(amount.toFixed(2));
        }
    }

    function moneyRead($el) {
        if (typeof window.__read_number === 'function') {
            return window.__read_number($el);
        }
        return parseFloat(String($el.val() || '0').replace(/,/g, '')) || 0;
    }

    function clearEntry(focusSearch) {
        $('#vspos_entry_variation_id').val('');
        $('#vspos_entry_purchase_line_id').val('');
        $('#vspos_entry_enable_stock').val('');
        $('#vspos_entry_qty_available').val('');
        $('#vspos_entry_desc').val('');
        $('#vspos_entry_qty').val('1');
        $('#vspos_entry_rate').val('');
        $('#vspos_quick_entry').removeClass('is-ready is-looking');
        // Keep return-to-grid flag for pos_insert_product_row
        if (window.__vspos_return_focus_grid) {
            return;
        }
        if (focusSearch !== false) {
            var $s = $('#search_product');
            if ($s.length && !$s.prop('disabled')) {
                $s.focus().select();
            }
        }
    }

    function fillEntry(item, purchaseLineId) {
        if (!item || !item.variation_id) {
            return false;
        }
        var name = item.name || '';
        if (item.type == 'variable' && item.variation) {
            name += ' - ' + item.variation;
        }
        // Decode HTML entities from server (e() escaped names)
        if (name.indexOf('&') !== -1) {
            var tmp = document.createElement('textarea');
            tmp.innerHTML = name;
            name = tmp.value;
        }
        var price = item.variation_group_price || item.selling_price || 0;
        if (typeof price === 'string') {
            price = price.replace(/,/g, '');
        }
        price = parseFloat(price) || 0;

        $('#vspos_entry_variation_id').val(String(item.variation_id));
        $('#vspos_entry_purchase_line_id').val(purchaseLineId || '');
        $('#vspos_entry_enable_stock').val(item.enable_stock);
        $('#vspos_entry_qty_available').val(item.qty_available);
        $('#vspos_entry_desc').val(name);
        $('#vspos_entry_qty').val('1');
        moneyWrite($('#vspos_entry_rate'), price);
        $('#vspos_quick_entry').addClass('is-ready').removeClass('is-looking');
        $('#search_product').val('');

        setTimeout(function () {
            $('#vspos_entry_qty').focus().select();
        }, 10);
        return true;
    }

    function parseProducts(data) {
        var products = data;
        if (data && data.products) {
            products = data.products;
        }
        if (typeof products === 'string') {
            try {
                products = JSON.parse(products);
            } catch (err) {
                products = [];
            }
        }
        if (!$.isArray(products)) {
            products = [];
        }
        return products.filter(function (p) {
            return p && !p.auto_added && p.variation_id;
        });
    }

    function pickExactSku(products, term) {
        var termL = String(term).toLowerCase();
        for (var i = 0; i < products.length; i++) {
            var sku = String(products[i].sub_sku || products[i].sku || '').toLowerCase();
            if (sku === termL) {
                return products[i];
            }
        }
        // Numeric barcode/SKU: never fall back to a single prefix hit (0002 vs 00021)
        if (/^[0-9]{4,}$/.test(String(term))) {
            return null;
        }
        return products.length === 1 ? products[0] : null;
    }

    function locationId() {
        return $('input#location_id').val() || $('#location_id').val() || '';
    }

    function lookup(term) {
        term = String(term || '').trim();
        if (!term) {
            return;
        }
        if (!locationId()) {
            if (window.toastr) {
                toastr.warning((window.LANG && LANG.select_location) || 'Select location');
            }
            return;
        }
        if ($('#search_product').data('vspos_looking_up')) {
            return;
        }

        var priceGroup = '';
        if ($('#price_group').length) {
            priceGroup = $('#price_group').val() || '';
        }
        if ($('#default_price_group').length && !priceGroup) {
            priceGroup = $('#default_price_group').val() || '';
        }

        $('#search_product').data('vspos_looking_up', true);
        $('#vspos_quick_entry').addClass('is-looking');

        // Do NOT use traditional:true — Laravel needs search_fields as an array
        $.ajax({
            url: '/products/list',
            method: 'GET',
            dataType: 'json',
            cache: false,
            data: {
                price_group: priceGroup,
                location_id: locationId(),
                term: term,
                not_for_selling: 0,
                'search_fields[]': 'sku',
                auto_add_single: 0,
                customer_id: $('#customer_id').val() || '',
            },
            success: function (data) {
                $('#search_product').data('vspos_looking_up', false);
                $('#vspos_quick_entry').removeClass('is-looking');

                var products = parseProducts(data);
                var item = pickExactSku(products, term);

                if (!item) {
                    $.ajax({
                        url: '/products/list',
                        method: 'GET',
                        dataType: 'json',
                        cache: false,
                        data: {
                            price_group: priceGroup,
                            location_id: locationId(),
                            term: term,
                            not_for_selling: 0,
                            'search_fields[]': ['sku', 'name'],
                            auto_add_single: 0,
                            customer_id: $('#customer_id').val() || '',
                        },
                        success: function (data2) {
                            var products2 = parseProducts(data2);
                            item = pickExactSku(products2, term);
                            if (!item && products2.length === 1 && !/^[0-9]{4,}$/.test(String(term))) {
                                item = products2[0];
                            }
                            afterLookup(item, term);
                        },
                        error: function () {
                            afterLookup(null, term);
                        },
                    });
                    return;
                }

                afterLookup(item, term);
            },
            error: function (xhr) {
                $('#search_product').data('vspos_looking_up', false);
                $('#vspos_quick_entry').removeClass('is-looking');
                console.error('SKU lookup failed', xhr && xhr.status, xhr && xhr.responseText);
                if (window.toastr) {
                    toastr.error('Product lookup failed');
                }
                $('#search_product').focus().select();
            },
        });
    }

    function afterLookup(item, term) {
        if (!item || !item.variation_id) {
            if (window.toastr) {
                toastr.info((window.LANG && LANG.no_products_found) || 'No matching products found');
            }
            $('#search_product').focus().select();
            return;
        }

        var oversell = $('#is_overselling_allowed').length > 0;
        var forSo = $('#sale_type').length && $('#sale_type').val() == 'sales_order';
        var isDraft =
            $('#status').length &&
            ($('#status').val() == 'quotation' || $('#status').val() == 'draft');
        var isPop = $('#is_pop_mode').length > 0;
        if (
            item.enable_stock == 1 &&
            parseFloat(item.qty_available) <= 0 &&
            !oversell &&
            !forSo &&
            !isDraft &&
            !isPop
        ) {
            alert((window.LANG && LANG.out_of_stock) || 'Out of stock');
            $('#search_product').focus().select();
            return;
        }

        window.__vspos_last_search_item = item;
        fillEntry(item, null);
    }

    function commit() {
        // Prefer pos.js implementation (handles stock-price async correctly)
        if (typeof window.vspos_pos_entry_commit === 'function') {
            window.vspos_pos_entry_commit();
            return;
        }
        var variationId = $('#vspos_entry_variation_id').val();
        if (!variationId) {
            if (window.toastr) {
                toastr.error('Scan / enter barcode or SKU first');
            }
            $('#search_product').focus();
            return;
        }
        var qtyRaw = $('#vspos_entry_qty').val();
        var qty = moneyRead($('#vspos_entry_qty'));
        if (qtyRaw === '' || qtyRaw === null || typeof qtyRaw === 'undefined' || isNaN(qty)) {
            qty = 1;
        } else if (qty === 0) {
            if (window.toastr) {
                toastr.warning(
                    (typeof LANG !== 'undefined' && LANG.inline_return_qty_zero)
                        ? LANG.inline_return_qty_zero
                        : 'Quantity cannot be zero'
                );
            }
            $('#vspos_entry_qty').focus().select();
            return;
        }
        var rate = moneyRead($('#vspos_entry_rate'));
        var purchaseLineId = $('#vspos_entry_purchase_line_id').val() || null;
        if (purchaseLineId === '') {
            purchaseLineId = null;
        }

        window.__vspos_pending_entry_rate = rate;
        window.__vspos_pending_entry_qty = qty;
        window.__vspos_entry_committing = true;

        var stockPrice =
            typeof window.__stock_price_management !== 'undefined' && window.__stock_price_management;

        try {
            if (stockPrice && typeof window.pos_check_price_and_add === 'function') {
                window.pos_check_price_and_add(variationId, purchaseLineId, qty);
                return; // flag cleared by pos_check_price_and_add
            }
            if (typeof window.pos_product_row === 'function') {
                window.pos_product_row(variationId, purchaseLineId, null, qty);
            } else {
                if (window.toastr) {
                    toastr.error('POS not ready');
                }
                return;
            }
            var $row = $('#pos_table tbody tr.product_row').last();
            if ($row.length) {
                if (qty !== 1) {
                    var $qty = $row.find('td input.pos_quantity').first();
                    if ($qty.length) {
                        moneyWrite($qty, qty);
                        $qty.trigger('change');
                    }
                }
                if (typeof window.vspos_sync_inline_return_row === 'function') {
                    window.vspos_sync_inline_return_row($row);
                }
                var $price = $row.find('td.pos-td-unitprice input.pos_unit_price').first();
                if (!$price.length) {
                    $price = $row.find('input.pos_unit_price').filter(function () {
                        return $(this).closest('.row_edit_product_price_model, .modal').length === 0;
                    }).first();
                }
                if ($price.length) {
                    moneyWrite($price, rate);
                    var $modalPrice = $row.find('.row_edit_product_price_model input.pos_unit_price');
                    if ($modalPrice.length) {
                        moneyWrite($modalPrice, rate);
                    }
                    $price.trigger('change').trigger('blur');
                }
            }
            clearEntry(true);
        } finally {
            if (!stockPrice) {
                window.__vspos_entry_committing = false;
            }
        }
    }

    window.vspos_entry_enabled = enabled;
    window.vspos_entry_fill = fillEntry;
    window.vspos_entry_lookup = lookup;
    window.vspos_entry_clear = clearEntry;
    // Keep a reference; do not clobber pos.js vspos_entry_commit if already present
    if (typeof window.vspos_entry_commit !== 'function') {
        window.vspos_entry_commit = commit;
    } else {
        window.vspos_pos_entry_commit = window.vspos_entry_commit;
        window.vspos_entry_commit = function () {
            window.vspos_pos_entry_commit();
        };
    }

    function isEnter(e) {
        return e.key === 'Enter' || e.which === 13;
    }

    function isQuickField(id) {
        return (
            id === 'search_product' ||
            id === 'vspos_entry_desc' ||
            id === 'vspos_entry_qty' ||
            id === 'vspos_entry_rate'
        );
    }

    document.addEventListener(
        'keydown',
        function (e) {
            // Inline blade handler already owns Enter when present
            if (window.__vspos_inline_entry_bound) {
                return;
            }
            if (!enabled() || !isEnter(e)) {
                return;
            }
            var t = e.target;
            if (!t) {
                return;
            }
            var id = t.id || '';

            if (!isQuickField(id)) {
                if (
                    t.tagName !== 'TEXTAREA' &&
                    t.closest &&
                    t.closest('#add_pos_sell_form, #edit_pos_sell_form')
                ) {
                    e.preventDefault();
                }
                return;
            }

            e.preventDefault();
            e.stopPropagation();
            if (e.stopImmediatePropagation) {
                e.stopImmediatePropagation();
            }

            if (id === 'search_product') {
                // Live autocomplete owns Enter when present
                if (window.vsposLiveSearch) {
                    return;
                }
                var term = String(t.value || '').trim();
                if (term) {
                    lookup(term);
                }
                return;
            }
            if (id === 'vspos_entry_desc') {
                if (!$('#vspos_entry_variation_id').val()) {
                    $('#search_product').focus();
                    return;
                }
                $('#vspos_entry_qty').focus().select();
                return;
            }
            if (id === 'vspos_entry_qty') {
                if (!$('#vspos_entry_variation_id').val()) {
                    $('#search_product').focus();
                    return;
                }
                $('#vspos_entry_rate').focus().select();
                return;
            }
            if (id === 'vspos_entry_rate') {
                commit();
            }
        },
        true
    );

    document.addEventListener(
        'keypress',
        function (e) {
            if (!enabled() || !isEnter(e)) {
                return;
            }
            var t = e.target;
            if (t && isQuickField(t.id || '')) {
                e.preventDefault();
                e.stopPropagation();
                if (e.stopImmediatePropagation) {
                    e.stopImmediatePropagation();
                }
            }
        },
        true
    );

    $(function () {
        try {
            if ($('#search_product').data('ui-autocomplete')) {
                $('#search_product').autocomplete('destroy');
            }
        } catch (err) {}

        $('#add_pos_sell_form, #edit_pos_sell_form').attr('autocomplete', 'off');
    });
})(window, document, window.jQuery);
