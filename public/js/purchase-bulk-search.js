/**
 * Purchase bulk product search + quantity popup
 * Load after jQuery + purchase.js
 */
(function () {
    if (window.__purchaseBulkSearchBound) return;

    function boot() {
        if (window.__purchaseBulkSearchBound) return;
        if (typeof window.jQuery === 'undefined') return;
        window.__purchaseBulkSearchBound = true;

        var $ = window.jQuery;
        var canViewCost = $('#purchase_bulk_search_modal').data('can-view-cost') == 1;
        var debounceTimer = null;
        var activeIndex = -1;
        var lastResults = [];
        var searchReq = null;
        var pendingProduct = null;

        function money(n) {
            var val = Number(n) || 0;
            if (typeof __currency_trans_from_en === 'function') {
                return String(__currency_trans_from_en(val, true)).replace(/<[^>]+>/g, '').trim();
            }
            return val.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        function escapeHtml(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        function locationId() {
            return $('#location_id').val() || '';
        }

        function focusPurchaseSearch() {
            setTimeout(function () {
                var $s = $('#search_product');
                if ($s.length && !$s.prop('disabled')) {
                    $s.focus().select();
                }
            }, 120);
        }

        window.vsposFocusPurchaseSearch = focusPurchaseSearch;

        function openBulkSearch(prefill) {
            var $modal = $('#purchase_bulk_search_modal');
            if (!$modal.length) return;
            if (!locationId()) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Please select business location first');
                }
                return;
            }
            $modal.modal('show');
            var term = typeof prefill === 'string' ? prefill : '';
            setTimeout(function () {
                $('#purchase_bulk_search_input').val(term).trigger('focus');
                if (term) {
                    $('#purchase_bulk_search_input').select();
                }
                runSearch($.trim(term));
            }, 250);
        }

        window.vsposOpenPurchaseBulkSearch = openBulkSearch;

        function renderEmpty(msg) {
            activeIndex = -1;
            lastResults = [];
            $('#purchase_bulk_search_body').html(
                '<div class="purchase-bulk-empty">' + msg + '</div>'
            );
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
                var q = Number(p.qty_available || 0);
                var cls = q <= 0 ? 'qty-low' : 'qty-ok';
                var qtyHtml = '<span class="' + cls + '">' + q.toFixed(2) + '</span>';
                if (p.unit) {
                    qtyHtml += ' <small style="color:#737373;">' + escapeHtml(p.unit) + '</small>';
                }
                html += '<div class="purchase-bulk-row' + (i === 0 ? ' is-active' : '') + '" role="option" tabindex="0" data-index="' + i + '">';
                html += '<div class="purchase-bulk-cell"><span class="purchase-bulk-name" title="' + escapeHtml(p.name) + '">' + escapeHtml(p.name) + '</span></div>';
                html += '<div class="purchase-bulk-cell"><span class="purchase-bulk-code">' + escapeHtml(p.sku || '') + '</span></div>';
                if (canViewCost) {
                    html += '<div class="purchase-bulk-cell is-num">' + money(p.cost_price) + '</div>';
                }
                html += '<div class="purchase-bulk-cell is-num">' + money(p.sell_price) + '</div>';
                html += '<div class="purchase-bulk-cell is-num">' + qtyHtml + '</div>';
                html += '<div class="purchase-bulk-cell is-action"><button type="button" class="btn purchase-bulk-add" data-index="' + i + '">Add</button></div>';
                html += '</div>';
            });
            $('#purchase_bulk_search_body').html(html);
        }

        function runSearch(term) {
            var loc = locationId();
            if (!loc) {
                renderEmpty('Select a location first');
                return;
            }
            renderEmpty('Searching…');
            if (searchReq && searchReq.abort) {
                try { searchReq.abort(); } catch (e) {}
            }
            searchReq = $.ajax({
                method: 'GET',
                url: '/purchases/bulk-product-search',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                data: {
                    term: term || '',
                    location_id: loc
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
            var $rows = $('#purchase_bulk_search_body .purchase-bulk-row');
            $rows.removeClass('is-active');
            var $row = $rows.eq(activeIndex).addClass('is-active');
            if ($row.length && $row.get(0).scrollIntoView) {
                $row.get(0).scrollIntoView({ block: 'nearest' });
            }
        }

        function writeMoneyInput($el, n) {
            if (!$el || !$el.length) return;
            if (typeof __write_number === 'function') {
                __write_number($el, Number(n) || 0, true);
            } else {
                $el.val((Number(n) || 0).toFixed(2));
            }
        }

        function readMoneyInput($el) {
            if (!$el || !$el.length) return 0;
            if (typeof __read_number === 'function') {
                return __read_number($el, true) || 0;
            }
            return parseFloat(String($el.val() || '0').replace(/,/g, '')) || 0;
        }

        function fillQtyModalFields(product) {
            pendingProduct = product;
            $('#purchase_qty_product').text(product.name || product.text || 'Product');
            var sku = product.sku || product.sub_sku || '';
            $('#purchase_qty_sku').text(sku ? ('SKU: ' + sku) : '');
            $('#purchase_qty_brand').text(product.brand || '—');
            $('#purchase_qty_category').text(product.category || '—');
            var desc = $.trim(product.description || '');
            $('#purchase_qty_description').text(desc || '—');
            $('#purchase_qty_unit').text(product.unit || '');
            $('#purchase_qty_input').val('1');
            writeMoneyInput($('#purchase_qty_cost'), product.cost_price || 0);
            writeMoneyInput($('#purchase_qty_sell'), product.sell_price || 0);
            var canCost = typeof product.cost_price !== 'undefined' && product.cost_price !== null;
            $('#purchase_qty_cost').prop('readonly', !canCost).closest('.col-xs-4').toggleClass('hide', false);
        }

        function showQtyModal() {
            if ($('#purchase_bulk_search_modal').hasClass('in') || $('#purchase_bulk_search_modal').is(':visible')) {
                $('#purchase_bulk_search_modal').modal('hide');
                setTimeout(function () {
                    $('#purchase_qty_modal').modal('show');
                }, 200);
            } else {
                $('#purchase_qty_modal').modal('show');
            }
        }

        function openQtyModal(product) {
            if (!product || !product.product_id || !product.variation_id) return;

            // Enrich from API when details missing (barcode / autocomplete)
            var needsDetails = !product.brand && !product.category && !product.description
                && (typeof product.cost_price === 'undefined');
            if (needsDetails) {
                $.ajax({
                    method: 'GET',
                    url: '/purchases/product-details',
                    dataType: 'json',
                    data: {
                        variation_id: product.variation_id,
                        location_id: locationId()
                    },
                    success: function (res) {
                        var merged = $.extend({}, product, (res && res.product) ? res.product : {});
                        fillQtyModalFields(merged);
                        showQtyModal();
                    },
                    error: function () {
                        fillQtyModalFields(product);
                        showQtyModal();
                    }
                });
                return;
            }

            fillQtyModalFields(product);
            showQtyModal();
        }

        window.vsposOpenPurchaseQtyModal = openQtyModal;

        function confirmQty() {
            if (!pendingProduct) return;
            var qty = readMoneyInput($('#purchase_qty_input'));
            if (!qty || qty <= 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Enter a valid quantity');
                }
                $('#purchase_qty_input').focus().select();
                return;
            }
            var cost = readMoneyInput($('#purchase_qty_cost'));
            var sell = readMoneyInput($('#purchase_qty_sell'));

            var product = pendingProduct;
            pendingProduct = null;
            $('#purchase_qty_modal').modal('hide');
            $('#search_product').val('');

            if (typeof window.get_purchase_entry_row === 'function') {
                window.get_purchase_entry_row(product.product_id, product.variation_id, qty, {
                    cost_price: cost,
                    sell_price: sell
                });
            } else if (typeof get_purchase_entry_row === 'function') {
                get_purchase_entry_row(product.product_id, product.variation_id, qty, {
                    cost_price: cost,
                    sell_price: sell
                });
            }
        }

        function pickResult(idx) {
            if (isNaN(idx) || !lastResults[idx]) return;
            openQtyModal(lastResults[idx]);
        }

        $(document).on('click', '#purchase_bulk_search_btn, .purchase-open-bulk-search', function (e) {
            e.preventDefault();
            e.stopPropagation();
            openBulkSearch();
        });

        $(document).on('input paste', '#purchase_bulk_search_input', function () {
            var $input = $(this);
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                runSearch($.trim($input.val() || ''));
            }, 200);
        });

        $(document).on('click', '#purchase_bulk_search_body .purchase-bulk-add', function (e) {
            e.preventDefault();
            e.stopPropagation();
            pickResult(parseInt($(this).attr('data-index'), 10));
        });

        $(document).on('click', '#purchase_bulk_search_body .purchase-bulk-row', function (e) {
            if ($(e.target).closest('.purchase-bulk-add').length) return;
            e.preventDefault();
            pickResult(parseInt($(this).attr('data-index'), 10));
        });

        $(document).on('keydown', '#purchase_bulk_search_input', function (e) {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIndex + 1);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIndex - 1);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (activeIndex >= 0 && lastResults[activeIndex]) {
                    pickResult(activeIndex);
                }
            }
        });

        $(document).on('shown.bs.modal', '#purchase_bulk_search_modal', function () {
            $('#purchase_bulk_search_input').focus();
        });

        $(document).on('shown.bs.modal', '#purchase_qty_modal', function () {
            setTimeout(function () {
                $('#purchase_qty_input').focus().select();
            }, 80);
        });

        $(document).on('hidden.bs.modal', '#purchase_qty_modal', function () {
            if (!pendingProduct) {
                focusPurchaseSearch();
            }
        });

        $(document).on('click', '#purchase_qty_confirm', function (e) {
            e.preventDefault();
            confirmQty();
        });

        // Enter flow: Qty → Cost → Selling → Add to grid
        $(document).on('keydown', '#purchase_qty_input, #purchase_qty_cost, #purchase_qty_sell', function (e) {
            if (e.key !== 'Enter') return;
            e.preventDefault();
            e.stopPropagation();
            var id = this.id;
            if (id === 'purchase_qty_input') {
                var qty = readMoneyInput($('#purchase_qty_input'));
                if (!qty || qty <= 0) {
                    if (typeof toastr !== 'undefined') {
                        toastr.warning('Enter a valid quantity');
                    }
                    $('#purchase_qty_input').focus().select();
                    return;
                }
                var $cost = $('#purchase_qty_cost');
                if ($cost.length && !$cost.prop('readonly') && $cost.is(':visible') && !$cost.closest('.hide').length) {
                    $cost.focus().select();
                } else {
                    $('#purchase_qty_sell').focus().select();
                }
                return;
            }
            if (id === 'purchase_qty_cost') {
                $('#purchase_qty_sell').focus().select();
                return;
            }
            if (id === 'purchase_qty_sell') {
                confirmQty();
            }
        });

        // Empty Enter on main search → bulk search
        $(document).on('keydown', '#search_product', function (e) {
            if (e.key !== 'Enter') return;
            var term = $.trim($(this).val() || '');
            if (term === '') {
                e.preventDefault();
                e.stopPropagation();
                openBulkSearch('');
            }
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
