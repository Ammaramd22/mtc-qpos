/**
 * Touch POS — categories, tap-to-add (optional qty keypad), barcode auto-add, short-name bulk search, keypad
 */
(function (window, document, $) {
    'use strict';
    if (!$) return;

    function isTouchUi() {
        return !!document.getElementById('vspos_touch_shell');
    }

    function isPopMode() {
        return !!document.getElementById('is_pop_mode');
    }

    function askQtyEnabled() {
        // POP: always ask Qty + Cost Price before add (tiles, search, etc.)
        if (isPopMode()) return true;
        var shell = document.getElementById('vspos_touch_shell');
        if (!shell) return false;
        return String(shell.getAttribute('data-ask-qty') || '') === '1';
    }

    var pendingVariationId = null;
    var confirmingQty = false;
    var activeField = 'qty'; // 'qty' | 'rate'
    var pendingDefaultRate = null;

    function rateLabel() {
        if (isPopMode()) {
            return (typeof LANG !== 'undefined' && LANG.cost_price) ? LANG.cost_price : 'Cost Price';
        }
        return (typeof LANG !== 'undefined' && LANG.mrp) ? LANG.mrp : 'MRP';
    }

    function activeInput() {
        return activeField === 'rate' ? $('#vspos_touch_rate_input') : $('#vspos_touch_qty_input');
    }

    function setActiveField(field, doFocus) {
        activeField = field === 'rate' ? 'rate' : 'qty';
        $('#vspos_touch_qty_modal .vspos-touch-qty-tab').removeClass('is-active');
        $('#vspos_touch_qty_modal .vspos-touch-qty-display-wrap').removeClass('is-active');
        $('#vspos_touch_qty_modal .vspos-touch-qty-tab[data-field="' + activeField + '"]').addClass('is-active');
        $('#vspos_touch_qty_modal .vspos-touch-qty-display-wrap[data-field="' + activeField + '"]').addClass('is-active');
        $('#vspos_touch_key_one').text(activeField === 'rate' ? 'Reset' : 'Qty 1');
        if (doFocus === false) return;
        setTimeout(function () {
            var $input = activeInput();
            $input.trigger('focus');
            if ($input.length && $input[0].select) {
                $input[0].select();
            }
        }, 30);
    }

    function formatMoney(n) {
        var val = Number(n) || 0;
        var formatted = val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        if (typeof __currency_symbol !== 'undefined' && __currency_symbol) {
            if (typeof __currency_symbol_placement !== 'undefined' && __currency_symbol_placement === 'after') {
                return formatted + ' ' + __currency_symbol;
            }
            return __currency_symbol + ' ' + formatted;
        }
        return formatted;
    }

    function parseMoneyInput(val) {
        var n = parseFloat(String(val || '').replace(/,/g, '').trim());
        return isNaN(n) ? NaN : n;
    }

    function addProductQty(variationId, qty, rate) {
        variationId = parseInt(variationId, 10);
        qty = parseFloat(qty);
        if (!variationId) return;
        if (isNaN(qty) || qty <= 0) {
            if (window.toastr) toastr.warning('Enter a valid quantity');
            return;
        }

        window.__vspos_entry_committing = true;
        window.__vspos_pending_entry_qty = qty;
        if (rate !== undefined && rate !== null && !isNaN(rate) && rate >= 0) {
            window.__vspos_pending_entry_rate = rate;
        } else {
            window.__vspos_pending_entry_rate = undefined;
        }
        try {
            if (typeof pos_product_row === 'function') {
                pos_product_row(variationId, null, null, qty);
            }
            if (typeof vspos_apply_pending_entry_to_last_row === 'function') {
                vspos_apply_pending_entry_to_last_row();
            } else if (rate !== undefined && rate !== null && !isNaN(rate)) {
                var $row = $('table#pos_table tbody tr.product_row').filter(function () {
                    return String($(this).find('.row_variation_id').val()) === String(variationId);
                }).last();
                if ($row.length) {
                    var $price = $row.find('td.pos-td-unitprice input.pos_unit_price').first();
                    if (!$price.length) {
                        $price = $row.find('input.pos_unit_price').filter(function () {
                            return $(this).closest('.row_edit_product_price_model, .modal').length === 0;
                        }).first();
                    }
                    if ($price.length && typeof __write_number === 'function') {
                        __write_number($price, rate);
                        $price.trigger('change').trigger('blur');
                    }
                }
            }
        } finally {
            setTimeout(function () {
                window.__vspos_entry_committing = false;
                window.__vspos_pending_entry_qty = null;
                window.__vspos_pending_entry_rate = undefined;
            }, 400);
        }
    }

    function openQtyModalFromProduct(product) {
        if (!product || !product.variation_id) return false;
        if (!$('#vspos_touch_qty_modal').length) return false;

        pendingVariationId = parseInt(product.variation_id, 10);
        if (!pendingVariationId) return false;

        var name = product.name || 'Product';
        var variation = product.variation || product.variation_name || '';
        var sku = product.sku || product.sub_sku || '';
        var unit = product.unit || '';
        var lastBuy = product.last_purchase_price;
        if (lastBuy == null || lastBuy === '') {
            lastBuy = product.last_purchased_price;
        }
        lastBuy = lastBuy != null && lastBuy !== '' ? parseFloat(lastBuy) : NaN;

        var price = product.selling_price;
        if (price == null || price === '') {
            // POP uses cost; POS uses MRP/sell price
            price = isPopMode()
                ? (product.cost_price != null ? product.cost_price : product.sell_price)
                : (product.sell_price != null ? product.sell_price : product.cost_price);
        }
        // POP: prefer last purchase price as the editable rate default
        if (isPopMode() && !isNaN(lastBuy) && lastBuy >= 0) {
            pendingDefaultRate = lastBuy;
            if (price == null || price === '') {
                price = lastBuy;
            }
        } else {
            pendingDefaultRate = price != null && price !== '' ? parseFloat(price) : 0;
        }
        if (isNaN(pendingDefaultRate)) pendingDefaultRate = 0;

        var title = name;
        if (variation && String(variation).toUpperCase() !== 'DUMMY' && name.indexOf(variation) === -1) {
            title += ' — ' + variation;
        }

        var rl = rateLabel();
        var $modal = $('#vspos_touch_qty_modal');
        $('#vspos_touch_rate_tab_label').text(rl);
        $('#vspos_touch_rate_field_label').text(rl);
        if (isPopMode()) {
            $modal.addClass('vspos-touch-qty--purchase');
            $('#vspos_touch_qty_mode_badge').text('Purchase').show();
            $('#vspos_touch_qty_title').text('Qty & Cost Price');
        } else {
            $modal.removeClass('vspos-touch-qty--purchase');
            $('#vspos_touch_qty_mode_badge').hide().text('');
            $('#vspos_touch_qty_title').text('Enter Quantity & ' + rl);
        }
        setQtyPhoto(product.image || '');
        $('#vspos_touch_qty_product').text(title);
        $('#vspos_touch_qty_sku').text(sku ? '(' + sku + ')' : '');
        $('#vspos_touch_qty_price').text(price != null && price !== '' ? formatMoney(price) : '');

        var $lastBuy = $('#vspos_touch_qty_last_buy');
        if (isPopMode() && !isNaN(lastBuy)) {
            $lastBuy.text('Last purchase: ' + formatMoney(lastBuy)).show();
        } else {
            $lastBuy.hide().text('');
        }

        $('#vspos_touch_qty_unit').text(unit || '');
        $('#vspos_touch_qty_input').val('1');
        $('#vspos_touch_rate_input').val(
            typeof __number_f === 'function' ? __number_f(pendingDefaultRate) : String(pendingDefaultRate)
        );

        if ($modal.length && $modal.parent()[0] !== document.body) {
            $modal.appendTo('body');
        }
        $modal.modal('show');
        setActiveField('qty');
        return true;
    }

    function setQtyPhoto(url) {
        var img = document.getElementById('vspos_touch_qty_image');
        if (!img) return;
        url = url ? String(url) : '';
        if (url && url !== 'none') {
            img.src = url;
            img.hidden = false;
            img.classList.add('is-shown');
        } else {
            img.removeAttribute('src');
            img.hidden = true;
            img.classList.remove('is-shown');
        }
    }

    function productBoxImage($box) {
        var bg = ($box.find('.image-container').css('background-image') || '');
        var match = String(bg).match(/url\((['"]?)(.*?)\1\)/i);
        return match && match[2] ? match[2] : '';
    }

    function openQtyModal($box) {
        return openQtyModalFromProduct({
            variation_id: $box.data('variation_id'),
            name: $box.data('name') || 'Product',
            variation: $box.data('variation') || '',
            sku: $box.data('sku') || '',
            unit: $box.data('unit') || '',
            selling_price: $box.data('selling_price'),
            last_purchase_price: $box.data('last_purchase_price'),
            image: productBoxImage($box)
        });
    }

    function confirmQtyModal() {
        if (confirmingQty) return;
        var qty = parseMoneyInput($('#vspos_touch_qty_input').val());
        var rate = parseMoneyInput($('#vspos_touch_rate_input').val());
        var variationId = pendingVariationId;
        if (!variationId) return;
        if (isNaN(qty) || qty <= 0) {
            if (window.toastr) toastr.warning('Enter a valid quantity');
            setActiveField('qty');
            return;
        }
        if (isNaN(rate) || rate < 0) {
            if (window.toastr) toastr.warning('Enter a valid rate');
            setActiveField('rate');
            return;
        }
        confirmingQty = true;
        pendingVariationId = null;
        $('#vspos_touch_qty_modal').modal('hide');
        addProductQty(variationId, qty, rate);
        setTimeout(function () { confirmingQty = false; }, 400);
    }

    function closeQtyModal() {
        pendingVariationId = null;
        pendingDefaultRate = null;
        $('#vspos_touch_qty_modal').modal('hide');
    }

    function applyKeyToActive(key) {
        var $input = activeInput();
        var cur = String($input.val() || '');
        var el = $input[0];
        var fullySelected = el && el.selectionStart === 0 && el.selectionEnd === cur.length && cur.length > 0;

        if (key === 'clear') {
            $input.val('');
            return;
        }
        if (key === 'back') {
            if (fullySelected) {
                $input.val('');
            } else {
                $input.val(cur.slice(0, -1));
            }
            return;
        }
        if (key === 'one') {
            if (activeField === 'rate') {
                var reset = pendingDefaultRate != null ? pendingDefaultRate : 0;
                $input.val(typeof __number_f === 'function' ? __number_f(reset) : String(reset));
            } else {
                $input.val('1');
            }
            return;
        }
        if (key === '.') {
            if (fullySelected) cur = '';
            if (cur.indexOf('.') !== -1) return;
            $input.val((cur || '0') + '.');
            return;
        }
        if (fullySelected) {
            $input.val(key);
            return;
        }
        if (cur === '0' && key !== '.') cur = '';
        $input.val(cur + key);
    }

    function bindQtyModal() {
        if (!$('#vspos_touch_qty_modal').length) return;

        $(document).on('click', '#vspos_touch_qty_modal .vspos-touch-qty-tab', function (e) {
            e.preventDefault();
            setActiveField($(this).data('field'));
        });
        $(document).on('click', '#vspos_touch_qty_modal .vspos-touch-qty-display-wrap', function (e) {
            e.preventDefault();
            setActiveField($(this).data('field'));
        });
        $(document).on('focus', '#vspos_touch_qty_input', function () {
            setActiveField('qty', false);
        });
        $(document).on('focus', '#vspos_touch_rate_input', function () {
            setActiveField('rate', false);
        });

        $(document).on('click', '#vspos_touch_qty_keypad .vspos-touch-key', function (e) {
            e.preventDefault();
            applyKeyToActive(String($(this).data('key')));
        });

        $(document).on('click', '#vspos_touch_qty_enter', function (e) {
            e.preventDefault();
            e.stopPropagation();
            confirmQtyModal();
        });

        // Document-level: delegated '#vspos_touch_qty_modal' never fires because
        // keydown targets the focused input, not the modal element.
        $(document).off('keydown.vsposTouchQty').on('keydown.vsposTouchQty', function (e) {
            var $modal = $('#vspos_touch_qty_modal');
            if (!$modal.length) return;
            var open = $modal.hasClass('in') || $modal.hasClass('show') || $modal.is(':visible');
            if (!open) return;
            if ($(e.target).closest('.modal').length && !$(e.target).closest('#vspos_touch_qty_modal').length) {
                return;
            }

            var key = e.key;
            var which = e.which || e.keyCode;

            if (key === 'Enter' || which === 13) {
                e.preventDefault();
                e.stopImmediatePropagation();
                // Tab → Rate first, then Add
                if (activeField === 'qty') {
                    setActiveField('rate');
                } else {
                    confirmQtyModal();
                }
                return false;
            }
            if (key === 'Tab' || which === 9) {
                e.preventDefault();
                setActiveField(activeField === 'qty' ? 'rate' : 'qty');
                return false;
            }
            if (key === 'Escape' || which === 27) {
                e.preventDefault();
                closeQtyModal();
                return false;
            }
            if (key === 'Backspace' || which === 8) {
                e.preventDefault();
                applyKeyToActive('back');
                return false;
            }
            if (key === '.' || key === ',' || which === 190 || which === 110) {
                e.preventDefault();
                applyKeyToActive('.');
                return false;
            }
            if (/^[0-9]$/.test(key) || (which >= 48 && which <= 57) || (which >= 96 && which <= 105)) {
                e.preventDefault();
                var digit = key && /^[0-9]$/.test(key) ? key : String(which >= 96 ? which - 96 : which - 48);
                applyKeyToActive(digit);
                return false;
            }
        });

        // Capture-phase Enter so POS/Mousetrap shortcuts cannot swallow it
        if (!window.__vsposTouchQtyCaptureBound) {
            window.__vsposTouchQtyCaptureBound = true;
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Enter' && e.keyCode !== 13) return;
                var modal = document.getElementById('vspos_touch_qty_modal');
                if (!modal) return;
                var open = modal.classList.contains('in') || modal.classList.contains('show');
                if (!open) {
                    // Bootstrap may still be animating
                    if (!$(modal).is(':visible')) return;
                }
                e.preventDefault();
                e.stopPropagation();
                if (activeField === 'qty') {
                    setActiveField('rate');
                } else {
                    confirmQtyModal();
                }
            }, true);
        }

        $('#vspos_touch_qty_modal').on('show.bs.modal', function () {
            if (this.parentNode !== document.body) {
                document.body.appendChild(this);
            }
        });
        $('#vspos_touch_qty_modal').on('shown.bs.modal', function () {
            document.body.classList.add('vspos-touch-qty-open');
            var backs = document.querySelectorAll('.modal-backdrop');
            if (backs.length) {
                backs[backs.length - 1].classList.add('vspos-touch-qty-backdrop');
            }
            setActiveField('qty');
        });
        $('#vspos_touch_qty_modal').on('hidden.bs.modal', function () {
            document.body.classList.remove('vspos-touch-qty-open');
            document.querySelectorAll('.vspos-touch-qty-backdrop').forEach(function (el) {
                el.classList.remove('vspos-touch-qty-backdrop');
            });
            pendingVariationId = null;
            pendingDefaultRate = null;
            $('#vspos_touch_qty_input').val('1');
            $('#vspos_touch_rate_input').val('');
        });
    }

    function bindPopProductTap() {
        // Classic POP (no touch shell): still open big Qty & Cost popup on tile tap
        if (!isPopMode() || isTouchUi()) return;
        $(document).on('click.vsposPopQty', 'div.product_box', function (e) {
            if (!$('#vspos_touch_qty_modal').length) return;
            e.preventDefault();
            e.stopImmediatePropagation();
            if ($('input#location_id').val() == '') {
                if (window.toastr) toastr.warning(LANG.select_location);
                return;
            }
            var $box = $(this);
            if (!$box.data('variation_id')) return;
            openQtyModal($box);
        });
    }

    function boot() {
        // Qty modal bindings work for POP even without full touch shell
        if ($('#vspos_touch_qty_modal').length) {
            bindQtyModal();
        }

        if (!isTouchUi()) {
            bindPopProductTap();
            return;
        }
        document.body.classList.add('vspos-touch-ui');
        window.__vspos_touch_ui = true;

        function loadGrid(attempt) {
            attempt = attempt || 0;
            var loc = $('#location_id').val();
            if (!loc && attempt < 20) {
                setTimeout(function () { loadGrid(attempt + 1); }, 100);
                return;
            }
            if (typeof get_product_suggestion_list === 'function') {
                window.__vspos_suggest_ignore_scroll = true;
                window.__vspos_suggest_end = false;
                window.__vspos_suggest_recovered = false;
                $('input#suggestion_page').val(1);
                window.global_p_category_id = null;
                get_product_suggestion_list(null, null, loc || null, null);
            }
        }
        loadGrid(0);

        bindCategories();
        bindKeypad();
        bindProductTap();
        bindSearch();
        bindMobileRetail();
    }

    function bindMobileRetail() {
        var mq = window.matchMedia('(max-width: 768px)');

        function syncLines() {
            var n = $('#pos_table tbody tr.product_row').length;
            $('.vspos-mpos-line-count').text(n);
            $('.vspos-mpos-item-word').text(n === 1 ? 'item' : 'items');
            document.body.classList.toggle('vspos-mpos-has-items', n > 0);
        }

        function openCart(e) {
            if (e) e.preventDefault();
            if (!mq.matches) return;
            syncLines();
            document.body.classList.add('vspos-mpos-cart-open');
            var scroller = document.querySelector('.vspos-touch-cart-inner');
            if (scroller) scroller.scrollTop = 0;
        }

        function closeCart(e) {
            if (e) e.preventDefault();
            document.body.classList.remove('vspos-mpos-cart-open');
        }

        $(document).on('click', '#vspos_mpos_view_cart, #vspos_mpos_header_cart', openCart);
        $(document).on('click', '#vspos_mpos_cart_back', closeCart);
        $(document).on('click', '#vspos_mpos_cart_clear', function (e) {
            e.preventDefault();
            var btn = document.querySelector('button.js-pos-cancel');
            if (btn) btn.click();
        });

        function onViewportChange() {
            if (!mq.matches) {
                document.body.classList.remove('vspos-mpos-cart-open');
            }
            var search = document.getElementById('search_product');
            if (search && mq.matches && !search.getAttribute('data-mpos-ph')) {
                search.setAttribute('data-mpos-ph', search.getAttribute('placeholder') || '');
                search.setAttribute('placeholder', 'Enter product name / SKU / Scan barcode');
            } else if (search && !mq.matches && search.getAttribute('data-mpos-ph') !== null) {
                search.setAttribute('placeholder', search.getAttribute('data-mpos-ph'));
                search.removeAttribute('data-mpos-ph');
            }
        }

        if (mq.addEventListener) mq.addEventListener('change', onViewportChange);
        else if (mq.addListener) mq.addListener(onViewportChange);
        onViewportChange();
        syncLines();
        setInterval(syncLines, 400);
    }

    function setActiveCat($btn) {
        $('#vspos_touch_cats .vspos-touch-cat').removeClass('is-active');
        $btn.addClass('is-active');
        var name = $btn.data('name') || $btn.find('.vspos-touch-cat-label').text();
        $('#vspos_touch_cat_title').text(name);
    }

    function reloadProducts(categoryId) {
        window.__vspos_suggest_ignore_scroll = true;
        window.__vspos_suggest_end = false;
        window.__vspos_suggest_recovered = false;
        window.global_p_category_id = categoryId === 'all' || categoryId === null || categoryId === '' ? null : categoryId;
        $('input#suggestion_page').val(1);
        var loc = $('#location_id').val();
        if (typeof get_product_suggestion_list === 'function') {
            get_product_suggestion_list(window.global_p_category_id, window.global_brand_id || null, loc, null);
        }
        if (typeof get_featured_products === 'function') {
            get_featured_products();
        }
    }

    function bindCategories() {
        $(document).on('click', '#vspos_touch_cats .vspos-touch-cat', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var value = String($btn.data('value'));
            var hasSub = String($btn.data('has-sub')) === '1';

            if ($btn.hasClass('vspos-touch-cat--sub')) {
                setActiveCat($btn);
                reloadProducts(value);
                return;
            }

            if (value === 'all') {
                $('#vspos_touch_cats .vspos-touch-subcats').attr('hidden', true);
                setActiveCat($btn);
                reloadProducts(null);
                return;
            }

            if (value === 'communication') {
                $('#vspos_touch_cats .vspos-touch-subcats').attr('hidden', true);
                setActiveCat($btn);
                if (typeof window.vsposOpenCommunication === 'function') {
                    window.vsposOpenCommunication('photocopy');
                }
                return;
            }

            if (hasSub) {
                var $panel = $('#vspos_touch_cats .vspos-touch-subcats[data-category-id="' + value + '"]');
                var open = !$panel.attr('hidden');
                $('#vspos_touch_cats .vspos-touch-subcats').attr('hidden', true);
                if (!open) {
                    $panel.removeAttr('hidden');
                }
                setActiveCat($btn);
                reloadProducts(value);
                return;
            }

            $('#vspos_touch_cats .vspos-touch-subcats').attr('hidden', true);
            setActiveCat($btn);
            reloadProducts(value);
        });
    }


    function bindProductTap() {
        $(document).on('click.vsposTouch', '#vspos_touch_shell div.product_box', function (e) {
            e.preventDefault();
            e.stopImmediatePropagation();
            if ($(this).hasClass('vspos-comm-service-tile') || $(this).data('comm-service')) {
                var svc = $(this).data('comm-service');
                if (typeof window.vsposOpenCommunication === 'function') {
                    window.vsposOpenCommunication(svc);
                }
                return;
            }
            if ($('input#location_id').val() == '') {
                if (window.toastr) toastr.warning(LANG.select_location);
                return;
            }
            var $box = $(this);
            var variationId = $box.data('variation_id');
            if (!variationId) return;

            $box.addClass('is-touch-added');
            setTimeout(function () { $box.removeClass('is-touch-added'); }, 220);

            if (askQtyEnabled()) {
                openQtyModal($box);
                return;
            }

            addProductQty(variationId, 1);
        });
    }

    function looksLikeBarcode(term) {
        return /^[0-9]{4,}$/.test(term) || /^[A-Za-z0-9\-]{6,}$/.test(term) && /[0-9]/.test(term);
    }

    function isShortNameQuery(term) {
        // 2–3 letters (or short alpha name) → bulk product search
        if (/^[0-9]+$/.test(term)) return false;
        return term.length >= 2 && term.length <= 3;
    }

    function openBulk(term) {
        if (typeof window.vsposOpenBulkProductSearch === 'function') {
            window.vsposOpenBulkProductSearch(term || '');
        }
    }

    function addByTerm(term, opts) {
        opts = opts || {};
        term = String(term || '').trim();
        if (!term) {
            openBulk('');
            return;
        }

        // Short typed name → always open bulk search
        if (!opts.fromScanner && isShortNameQuery(term)) {
            openBulk(term);
            return;
        }

        window.__vspos_entry_committing = true;
        $.get('/products/list', {
            location_id: $('#location_id').val(),
            term: term,
            search_fields: ['sku', 'name'],
        }).done(function (data) {
            var products = data;
            if (data && data.products) products = data.products;
            if (typeof products === 'string') {
                try { products = JSON.parse(products); } catch (err) { products = []; }
            }
            if (!$.isArray(products)) products = [];
            products = products.filter(function (p) { return p && p.variation_id && !p.auto_added; });

            var hit = null;
            var termL = term.toLowerCase();
            for (var i = 0; i < products.length; i++) {
                var sku = String(products[i].sub_sku || products[i].sku || '').toLowerCase();
                if (sku === termL) {
                    hit = products[i];
                    break;
                }
            }
            // Numeric barcode: exact SKU only (no fuzzy single match)
            if (!hit && !/^[0-9]{4,}$/.test(term) && products.length === 1) {
                hit = products[0];
            }

            if (hit) {
                try {
                    $('#search_product').val('');
                    // POP (and ask-qty POS): open Qty & Cost/MRP popup — except USB scanner bursts stay instant
                    if (askQtyEnabled() && !opts.fromScanner && typeof openQtyModalFromProduct === 'function') {
                        var opened = openQtyModalFromProduct({
                            variation_id: hit.variation_id,
                            name: hit.name || '',
                            variation: hit.variation || hit.variation_name || '',
                            sku: hit.sub_sku || hit.sku || '',
                            unit: hit.unit || '',
                            sell_price: hit.selling_price != null ? hit.selling_price : hit.sell_price,
                            cost_price: hit.cost_price != null ? hit.cost_price : hit.purchase_price,
                            last_purchase_price: hit.last_purchase_price != null ? hit.last_purchase_price : hit.last_purchased_price,
                            selling_price: isPopMode()
                                ? (hit.cost_price != null ? hit.cost_price : (hit.selling_price != null ? hit.selling_price : hit.sell_price))
                                : null
                        });
                        if (opened) {
                            return;
                        }
                    }
                    pos_product_row(hit.variation_id);
                    $('#search_product').focus();
                } finally {
                    window.__vspos_entry_committing = false;
                }
            } else {
                window.__vspos_entry_committing = false;
                openBulk(term);
            }
        }).fail(function () {
            window.__vspos_entry_committing = false;
            if (window.toastr) toastr.error('Product lookup failed');
        });
    }

    function bindSearch() {
        var $search = $('#vspos_touch_shell #search_product');
        if (!$search.length) return;

        var scanTimer = null;
        var lastInputAt = 0;
        var burstCount = 0;
        var humanGapMs = 85;
        var scanQuietMs = 120;

        $search.on('input', function () {
            var now = Date.now();
            var term = String($(this).val() || '').trim();
            if (scanTimer) clearTimeout(scanTimer);

            if (!term) {
                burstCount = 0;
                lastInputAt = 0;
                return;
            }

            var gap = lastInputAt ? (now - lastInputAt) : 0;
            lastInputAt = now;
            if (gap > humanGapMs) {
                burstCount = 1;
            } else {
                burstCount += 1;
            }

            // USB scanners: fast burst of chars then pause → auto-add
            if (!looksLikeBarcode(term) || burstCount < 4) {
                return;
            }

            scanTimer = setTimeout(function () {
                var current = String($('#search_product').val() || '').trim();
                if (current !== term) return;
                if (burstCount < 4) return;
                addByTerm(term, { fromScanner: true });
            }, scanQuietMs);
        });

        $search.on('keydown', function (e) {
            if (e.key !== 'Enter' && e.which !== 13) return;
            e.preventDefault();
            e.stopPropagation();
            if (scanTimer) clearTimeout(scanTimer);
            burstCount = 0;
            addByTerm($(this).val());
        });
    }

    function bindKeypad() {
        var $input = $('#vspos_pay_amount_input');
        if (!$input.length) return;

        function markReplaceNext() {
            $input.attr('data-replace-next', '1');
        }

        function clearReplaceNext() {
            $input.removeAttr('data-replace-next');
        }

        function shouldReplace(cur) {
            if ($input.attr('data-replace-next') === '1') return true;
            // Legacy formatted values (commas) from older prefills — never append onto them
            if (/[,\s]/.test(cur)) return true;
            return false;
        }

        // Prefill is fully selected so a physical keyboard also replaces on first type
        $(document).on('focus', '#vspos_pay_amount_input', function () {
            if ($input.attr('data-replace-next') !== '1') return;
            var el = this;
            setTimeout(function () {
                try { el.select(); } catch (err) {}
            }, 0);
        });

        $(document).on('click', '#vspos_touch_keypad .vspos-touch-key', function (e) {
            e.preventDefault();
            var key = String($(this).data('key'));
            var cur = String($input.val() || '');
            var replace = shouldReplace(cur);

            if (key === 'clear') {
                clearReplaceNext();
                $input.val('').trigger('input').trigger('change');
                return;
            }
            if (key === 'back') {
                if (replace) {
                    clearReplaceNext();
                    $input.val('').trigger('input').trigger('change');
                    return;
                }
                // Strip grouping commas before editing
                cur = cur.replace(/,/g, '');
                $input.val(cur.slice(0, -1)).trigger('input').trigger('change');
                return;
            }
            if (key === 'exact') {
                var total = 0;
                // Prefer remaining due when touch multipay is active
                if (typeof window.jQuery !== 'undefined') {
                    var $due = window.jQuery('#vspos_touch_pay_due');
                    if ($due.length && window.jQuery('#vspos_mobile_pay_popup').hasClass('vspos-mobile-pay-popup--open')) {
                        var dueRaw = $due.text().replace(/[^0-9.\-]/g, '');
                        total = parseFloat(dueRaw) || 0;
                    }
                }
                if (!total) {
                    var $mirror = $('.grand_total_mirror').first();
                    if ($mirror.length) {
                        var raw = $mirror.text().replace(/[^0-9.\-]/g, '');
                        total = parseFloat(raw) || 0;
                    }
                }
                markReplaceNext();
                $input.val(total ? String(total) : '').trigger('input').trigger('change');
                try { $input[0].select(); } catch (err2) {}
                return;
            }
            if (key === '.') {
                if (replace) {
                    clearReplaceNext();
                    $input.val('0.').trigger('input').trigger('change');
                    return;
                }
                cur = cur.replace(/,/g, '');
                if (cur.indexOf('.') !== -1) return;
                $input.val((cur || '0') + '.').trigger('input').trigger('change');
                return;
            }
            // Digit
            if (replace) {
                clearReplaceNext();
                $input.val(key === '0' ? '0' : key).trigger('input').trigger('change');
                return;
            }
            cur = cur.replace(/,/g, '');
            if (cur === '0') cur = '';
            $input.val(cur + key).trigger('input').trigger('change');
        });

        // Expose so create.blade can mark after prefilling amount
        window.__vsposPayAmountMarkReplace = markReplaceNext;
    }

    window.vsposOpenTouchQtyModal = function (product) {
        try {
            return openQtyModalFromProduct(product || {});
        } catch (e) {
            return false;
        }
    };

    $(boot);
})(window, document, window.jQuery);
