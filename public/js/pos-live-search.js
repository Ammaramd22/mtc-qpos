/**
 * POS live product search — filters the product-card grid as the user types.
 * Does NOT open a popup while typing. F8 remains the full Product Search modal.
 * Enter: exact barcode/SKU add (existing workflow); never opens F8.
 */
(function () {
    if (window.__vsposLiveSearchBound) {
        return;
    }
    window.__vsposLiveSearchBound = true;

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        var $ = window.jQuery;
        if (!$('#search_product').length) {
            return;
        }
        if (window.__vsposLiveSearchReady) {
            return;
        }
        window.__vsposLiveSearchReady = true;

        var debounceTimer = null;
        var lastTerm = '';

        function locId() {
            return $('input#location_id').val() || $('#location_id').val() || '';
        }

        function itemsContainer() {
            return $('div#product_list_items').length
                ? $('div#product_list_items')
                : $('div#product_list_body');
        }

        function showSearching() {
            var $items = itemsContainer();
            if (!$items.length) {
                return;
            }
            $items.html(
                '<div class="col-md-12 vspos-no-products">' +
                    '<h4 class="text-center" style="color:#a3a3a3;margin:48px 12px 12px;">Searching...</h4>' +
                    '</div>'
            );
        }

        function refreshProductGrid(term) {
            term = String(term || '').trim();
            window.global_p_search_term = term;
            lastTerm = term;

            if (typeof get_product_suggestion_list !== 'function') {
                return;
            }

            var loc = locId();
            if (!loc) {
                return;
            }

            $('input#suggestion_page').val(1);
            var $items = itemsContainer();
            if ($items.length) {
                $items.html('');
            }
            if (term) {
                showSearching();
            }

            var is_enabled_stock = null;
            if ($('#is_enabled_stock').length) {
                is_enabled_stock = $('#is_enabled_stock').val();
            }
            var device_model_id = null;
            if ($('#repair_model_id').length) {
                device_model_id = $('#repair_model_id').val();
            }

            get_product_suggestion_list(
                typeof global_p_category_id !== 'undefined' ? global_p_category_id : null,
                typeof global_brand_id !== 'undefined' ? global_brand_id : null,
                loc,
                null,
                is_enabled_stock,
                device_model_id
            );
        }

        function scheduleGridFilter(term) {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                var current = String($('#search_product').val() || '').trim();
                if (current !== String(term || '').trim()) {
                    return;
                }
                refreshProductGrid(current);
            }, 200);
        }

        // Live filter of product cards while typing (no dropdown / no F8)
        $(document).on('input', '#search_product', function () {
            var term = String($(this).val() || '').trim();

            // Pure long numeric barcode burst: wait for Enter/scanner — avoid thrashing grid
            if (/^[0-9]{8,}$/.test(term)) {
                return;
            }

            if (!term) {
                clearTimeout(debounceTimer);
                refreshProductGrid('');
                return;
            }

            scheduleGridFilter(term);
        });

        // Capture Enter before other POS handlers — never open F8 from typing
        var searchEl = document.getElementById('search_product');
        if (searchEl && !searchEl.__vspos_live_enter_bound) {
            searchEl.__vspos_live_enter_bound = true;
            searchEl.addEventListener(
                'keydown',
                function (e) {
                    var key = e.key || '';
                    var which = e.which || e.keyCode;

                    if (key === 'Escape' || which === 27) {
                        if (this.value) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            this.value = '';
                            refreshProductGrid('');
                        }
                        return;
                    }

                    if (key === 'Enter' || which === 13) {
                        e.preventDefault();
                        e.stopImmediatePropagation();

                        var term = String(this.value || '').trim();
                        if (!term) {
                            return;
                        }

                        // Exact barcode/SKU path — fill entry / add (existing Qty/Rate flow)
                        if (typeof window.vspos_entry_lookup_exact_only === 'function') {
                            window.vspos_entry_lookup_exact_only(term);
                            return;
                        }

                        // Ensure grid is filtered; if a single card remains, click it
                        refreshProductGrid(term);
                        setTimeout(function () {
                            if (String($('#search_product').val() || '').trim() !== term) {
                                return;
                            }
                            var $boxes = itemsContainer().find('.product_box');
                            if ($boxes.length === 1) {
                                $boxes.first().trigger('click');
                                $('#search_product').val('');
                                refreshProductGrid('');
                            }
                        }, 350);
                    }
                },
                true
            );
        }

        // Category/brand reloads already call get_product_suggestion_list —
        // keep current search term via window.global_p_search_term.

        window.vsposLiveSearch = {
            hide: function () {},
            isOpen: function () {
                return false;
            },
            selectActive: function () {
                return false;
            },
            hasResults: function () {
                return itemsContainer().find('.product_box').length > 0;
            },
            search: function (term) {
                refreshProductGrid(term);
            },
            refreshGrid: refreshProductGrid,
            getResults: function () {
                return [];
            },
        };
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(boot);
    }
})();
