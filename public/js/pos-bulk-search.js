/**
 * POS bulk product search modal (F8)
 * Must load after jQuery + pos.js
 */
(function () {
    if (window.__vsposBulkSearchBound) return;

    function boot() {
        if (window.__vsposBulkSearchBound) return;
        if (typeof window.jQuery === 'undefined') return;
        window.__vsposBulkSearchBound = true;

        var $ = window.jQuery;
        var canViewCost = $('#vspos_bulk_search_modal').data('can-view-cost') == 1;
        var debounceTimer = null;
        var activeIndex = -1;
        var lastResults = [];
        var lastBulkTerm = '';
        var searchReq = null;

        function money(n) {
            var val = Number(n) || 0;
            var formatted = val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            if (typeof __currency_symbol !== 'undefined' && __currency_symbol) {
                if (typeof __currency_symbol_placement !== 'undefined' && __currency_symbol_placement === 'after') {
                    return formatted + ' ' + __currency_symbol;
                }
                return __currency_symbol + ' ' + formatted;
            }
            if (typeof __currency_trans_from_en === 'function') {
                // Prefer plain text (avoid HTML wrappers that break table cells)
                var out = __currency_trans_from_en(val, true);
                return String(out).replace(/<[^>]+>/g, '').trim();
            }
            return formatted;
        }

        function locationId() {
            return $('#location_id').val() || '';
        }

        function openBulkSearch(prefill) {
            var $modal = $('#vspos_bulk_search_modal');
            if (!$modal.length) return;
            $modal.modal('show');
            var term = typeof prefill === 'string' ? prefill : ($('#search_product').val() || '');
            setTimeout(function () {
                $('#vspos_bulk_search_input').val(term).trigger('focus');
                if (term) {
                    $('#vspos_bulk_search_input').select();
                }
                runSearch($.trim(term));
            }, 280);
        }

        window.vsposOpenBulkProductSearch = openBulkSearch;

        function renderEmpty(msg) {
            activeIndex = -1;
            lastResults = [];
            $('#vspos_bulk_search_body').html(
                '<div class="vspos-bulk-empty">' + msg + '</div>'
            );
        }

        function escapeHtml(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        function renderRows(products) {
            lastResults = products || [];
            activeIndex = lastResults.length ? 0 : -1;
            if (!lastResults.length) {
                renderEmpty('No products found');
                return;
            }
            var html = '';
            lastResults.forEach(function (p, i) {
                var qtyHtml = '—';
                if (p.enable_stock) {
                    var q = Number(p.qty_available || 0);
                    var cls = q <= 0 ? 'qty-low' : 'qty-ok';
                    qtyHtml = '<span class="' + cls + '">' + q.toFixed(2) + '</span> <small style="color:#737373;">' + escapeHtml(p.unit || '') + '</small>';
                }
                html += '<div class="vspos-bulk-row' + (i === 0 ? ' is-active' : '') + '" role="option" tabindex="0" data-index="' + i + '" data-variation_id="' + p.variation_id + '">';
                html += '<div class="vspos-bulk-cell"><span class="vspos-bulk-name" title="' + escapeHtml(p.name) + '">' + escapeHtml(p.name) + '</span></div>';
                html += '<div class="vspos-bulk-cell"><span class="vspos-bulk-code">' + escapeHtml(p.sku || '') + '</span></div>';
                if (canViewCost) {
                    html += '<div class="vspos-bulk-cell is-num">' + money(p.cost_price) + '</div>';
                }
                html += '<div class="vspos-bulk-cell is-num">' + money(p.sell_price) + '</div>';
                html += '<div class="vspos-bulk-cell is-num">' + qtyHtml + '</div>';
                html += '<div class="vspos-bulk-cell is-action"><button type="button" class="btn vspos-bulk-add" data-variation_id="' + p.variation_id + '">Add</button></div>';
                html += '</div>';
            });
            $('#vspos_bulk_search_body').html(html);
        }

        function runSearch(term) {
            var loc = locationId();
            if (!loc) {
                renderEmpty('Select a location first');
                return;
            }
            lastBulkTerm = term || '';
            renderEmpty('Searching…');
            window.__vspos_last_bulk_term = lastBulkTerm;
            if (searchReq && searchReq.abort) {
                try { searchReq.abort(); } catch (e) {}
            }
            searchReq = $.ajax({
                method: 'GET',
                url: '/sells/pos/bulk-product-search',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: {
                    term: term || '',
                    location_id: loc,
                    for_pop: $('#is_pop_mode').length ? 1 : 0
                },
                success: function (res) {
                    if (res && typeof res.can_view_cost !== 'undefined') {
                        canViewCost = !!res.can_view_cost;
                    }
                    renderRows((res && res.products) ? res.products : []);
                },
                error: function (xhr, status) {
                    if (status === 'abort') return;
                    renderEmpty('Search failed. Try again.');
                }
            });
        }

        function setActive(idx) {
            if (!lastResults.length) return;
            if (idx < 0) idx = lastResults.length - 1;
            if (idx >= lastResults.length) idx = 0;
            activeIndex = idx;
            var $rows = $('#vspos_bulk_search_body .vspos-bulk-row');
            $rows.removeClass('is-active');
            var $row = $rows.eq(activeIndex).addClass('is-active');
            if ($row.length && $row.get(0).scrollIntoView) {
                $row.get(0).scrollIntoView({ block: 'nearest' });
            }
        }

        function selectProductForQtyRate(product) {
            if (!product || !product.variation_id) return;
            // After Qty/Rate confirm, stay on POS (do not reopen F8).
            window.__vspos_return_focus_grid = null;
            $('#vspos_bulk_search_modal').modal('hide');
            $('#search_product').val('');

            setTimeout(function () {
                // Prefer touch Qty & MRP popup when available (POS/POP touch UI)
                if (typeof window.vsposOpenTouchQtyModal === 'function' && $('#vspos_touch_qty_modal').length) {
                    var opened = window.vsposOpenTouchQtyModal({
                        variation_id: product.variation_id,
                        name: product.name || '',
                        variation: product.variation_name || product.variation || '',
                        sku: product.sku || product.sub_sku || '',
                        unit: product.unit || '',
                        sell_price: product.sell_price || product.selling_price || 0,
                        cost_price: product.cost_price,
                        last_purchase_price: product.last_purchase_price,
                        selling_price: null
                    });
                    if (opened) return;
                }

                var item = {
                    variation_id: product.variation_id,
                    name: product.name || '',
                    type: product.type || '',
                    variation: product.variation_name || product.variation || '',
                    selling_price: product.sell_price || product.selling_price || 0,
                    enable_stock: product.enable_stock,
                    qty_available: product.qty_available,
                    sub_sku: product.sku || product.sub_sku || ''
                };
                var fillFn = typeof window.vspos_entry_fill === 'function'
                    ? window.vspos_entry_fill
                    : null;
                if (fillFn) {
                    fillFn(item);
                } else if (typeof toastr !== 'undefined') {
                    toastr.error('Quick entry is not available');
                }
            }, 180);
        }

        function fillEntryFromProduct(product) {
            selectProductForQtyRate(product);
        }

        function addVariation(variationId) {
            variationId = parseInt(variationId, 10);
            if (!variationId) return;

            // "Add" = cart immediately (qty 1). Close F8 search and stay on POS.
            // Do NOT set __vspos_return_focus_grid = 'bulk' (that used to reopen F8).
            window.__vspos_return_focus_grid = null;
            window.__vspos_last_bulk_term = '';
            $('#vspos_bulk_search_modal').modal('hide');
            // Clear leftover Bootstrap backdrops if price modal never opened
            setTimeout(function () {
                if (!$('.modal:visible').length) {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open').css('padding-right', '');
                }
            }, 350);

            $('#search_product').val('');
            window.__vspos_entry_committing = true;
            window.__vspos_pending_entry_qty = 1;
            var addFn = typeof window.pos_product_row === 'function'
                ? window.pos_product_row
                : (typeof pos_product_row === 'function' ? pos_product_row : null);
            if (!addFn) {
                window.__vspos_entry_committing = false;
                window.__vspos_pending_entry_qty = null;
                if (typeof toastr !== 'undefined') {
                    toastr.error('Unable to add product');
                }
                return;
            }
            setTimeout(function () {
                window.__vspos_entry_committing = true;
                try {
                    addFn(variationId, null, null, 1);
                } finally {
                    setTimeout(function () {
                        window.__vspos_entry_committing = false;
                        window.__vspos_pending_entry_qty = null;
                        window.__vspos_return_focus_grid = null;
                        if ($('#search_product').length) {
                            $('#search_product').focus().select();
                        }
                    }, 600);
                }
            }, 200);
        }

        $(document).on('click', '#vspos_bulk_search_btn, .vspos-open-bulk-search', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openBulkSearch();
        });

        // Use input/paste only — keyup on ArrowDown/Up was re-searching and resetting to row 1
        $(document).on('input paste', '#vspos_bulk_search_input', function () {
            var $input = $(this);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                runSearch($.trim($input.val() || ''));
            }, 200);
        });

        // "Add" button = add immediately (then return to grid)
        $(document).on('click', '#vspos_bulk_search_body .vspos-bulk-add', function (e) {
            e.preventDefault();
            e.stopPropagation();
            addVariation($(this).attr('data-variation_id') || $(this).data('variation_id'));
        });

        // Row click / double-click → Qty & Rate entry bar
        $(document).on('click', '#vspos_bulk_search_body .vspos-bulk-row', function (e) {
            if ($(e.target).closest('.vspos-bulk-add').length) return;
            e.preventDefault();
            var idx = parseInt($(this).attr('data-index'), 10);
            if (isNaN(idx) || !lastResults[idx]) return;
            fillEntryFromProduct(lastResults[idx]);
        });

        $(document).on('dblclick', '#vspos_bulk_search_body .vspos-bulk-row', function (e) {
            if ($(e.target).closest('.vspos-bulk-add').length) return;
            e.preventDefault();
            var idx = parseInt($(this).attr('data-index'), 10);
            if (isNaN(idx) || !lastResults[idx]) return;
            fillEntryFromProduct(lastResults[idx]);
        });

        $(document).on('keydown', '#vspos_bulk_search_input', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIndex - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && lastResults[activeIndex]) {
                    fillEntryFromProduct(lastResults[activeIndex]);
                }
            } else if (e.key === 'Tab' && !e.shiftKey) {
                // Tab from search → first/active product row
                var $rows = $('#vspos_bulk_search_body .vspos-bulk-row');
                if ($rows.length) {
                    e.preventDefault();
                    if (activeIndex < 0) setActive(0);
                    $rows.eq(activeIndex).attr('tabindex', 0).focus();
                }
            }
        });

        $(document).on('keydown', '#vspos_bulk_search_body .vspos-bulk-row', function (e) {
            var $rows = $('#vspos_bulk_search_body .vspos-bulk-row');
            var idx = parseInt($(this).attr('data-index'), 10);
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(idx + 1);
                $rows.eq(activeIndex).attr('tabindex', 0).focus();
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(idx - 1);
                $rows.eq(activeIndex).attr('tabindex', 0).focus();
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (lastResults[idx]) {
                    fillEntryFromProduct(lastResults[idx]);
                }
            }
        });

        $(document).on('shown.bs.modal', '#vspos_bulk_search_modal', function () {
            $('#vspos_bulk_search_input').focus();
        });
    }

    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(boot);
    } else {
        document.addEventListener('DOMContentLoaded', function () {
            var tries = 0;
            var t = setInterval(function () {
                tries++;
                if (typeof window.jQuery !== 'undefined') {
                    clearInterval(t);
                    window.jQuery(boot);
                } else if (tries > 80) {
                    clearInterval(t);
                }
            }, 50);
        });
    }
})();
