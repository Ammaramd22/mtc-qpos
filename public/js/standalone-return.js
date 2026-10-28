/**
 * Standalone Return — live product search + invoice lookup.
 * Reuses /products/list (same tyre search as POS). F8 opens bulk search modal.
 */
(function () {
    if (window.__vsposStandaloneReturnBound) {
        return;
    }
    window.__vsposStandaloneReturnBound = true;

    function boot() {
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        var $ = window.jQuery;
        if (!$('#standalone_return_form').length) {
            return;
        }

        var rowIndex = 0;
        var searchReq = null;
        var debounceTimer = null;
        var results = [];
        var activeIndex = -1;
        var $dd = null;
        var invoiceMode = false;
        var invoiceLines = [];

        function escapeHtml(s) {
            return $('<div>').text(s == null ? '' : String(s)).html();
        }

        function money(n) {
            var val = Number(n) || 0;
            return val.toFixed(2);
        }

        function locId() {
            return $('#location_id').val() || '';
        }

        function ensureDropdown() {
            if ($dd && $dd.length) {
                return $dd;
            }
            var $wrap = $('#search_product').closest('.vspos-sr-search-wrap');
            if (!$wrap.length) {
                $wrap = $('#search_product').parent();
            }
            if (!$wrap.css('position') || $wrap.css('position') === 'static') {
                $wrap.css('position', 'relative');
            }
            $dd = $('<div id="vspos_sr_live_dd" class="vspos-live-search-dd" role="listbox" aria-hidden="true"></div>');
            $wrap.append($dd);
            return $dd;
        }

        function hideDd() {
            activeIndex = -1;
            results = [];
            if ($dd && $dd.length) {
                $dd.removeClass('is-open').attr('aria-hidden', 'true').empty();
            }
        }

        function setActive(idx) {
            if (!results.length) {
                return;
            }
            if (idx < 0) {
                idx = results.length - 1;
            }
            if (idx >= results.length) {
                idx = 0;
            }
            activeIndex = idx;
            $dd.find('.vspos-live-search-item').removeClass('is-active');
            var $row = $dd.find('.vspos-live-search-item').eq(activeIndex).addClass('is-active');
            if ($row.length && $row.get(0).scrollIntoView) {
                $row.get(0).scrollIntoView({ block: 'nearest' });
            }
        }

        function renderDd(list, emptyMsg) {
            ensureDropdown();
            results = list || [];
            if (!results.length) {
                activeIndex = -1;
                $dd.html(
                    '<div class="vspos-live-search-empty">' +
                        escapeHtml(emptyMsg || 'No matching products found') +
                        '</div>'
                );
                $dd.addClass('is-open').attr('aria-hidden', 'false');
                return;
            }
            activeIndex = 0;
            var html = '';
            results.forEach(function (p, i) {
                var qty = '—';
                if (p.enable_stock) {
                    qty = Number(p.qty_available || 0).toFixed(2) + (p.unit ? ' ' + p.unit : '');
                }
                if (p.qty_returnable != null) {
                    qty = 'Returnable: ' + Number(p.qty_returnable).toFixed(2);
                }
                var metaBits = [];
                if (p.sub_sku || p.sku) {
                    metaBits.push('SKU: ' + (p.sub_sku || p.sku));
                }
                if (p.tyre_size) {
                    metaBits.push('Tyre Size: ' + p.tyre_size);
                }
                metaBits.push(qty);
                html +=
                    '<div class="vspos-live-search-item' +
                    (i === 0 ? ' is-active' : '') +
                    '" role="option" data-index="' +
                    i +
                    '">' +
                    '<div class="vspos-live-search-name">' +
                    escapeHtml(p.name || '') +
                    '</div>' +
                    '<div class="vspos-live-search-meta">' +
                    escapeHtml(metaBits.join(' · ')) +
                    '</div></div>';
            });
            $dd.html(html).addClass('is-open').attr('aria-hidden', 'false');
        }

        function calculateRowTotal($row) {
            var unitPrice = parseFloat($row.find('.return-unit-price').val()) || 0;
            var qty = parseFloat($row.find('.return-qty').val()) || 0;
            $row.find('.return-subtotal').text((unitPrice * qty).toFixed(2));
            calculateTotal();
        }

        function calculateTotal() {
            var total = 0;
            $('#standalone_return_table tbody tr').each(function () {
                total += parseFloat($(this).find('.return-subtotal').text()) || 0;
            });
            $('#total_return_amount').text(total.toFixed(2));
        }

        function updateRowNumbers() {
            var idx = 1;
            $('#standalone_return_table tbody tr').each(function () {
                $(this).find('.sr-row-index').text(idx);
                $(this).find('input[name*="products["]').each(function () {
                    var name = $(this).attr('name');
                    $(this).attr('name', name.replace(/products\[\d+\]/, 'products[' + idx + ']'));
                });
                idx++;
            });
            rowIndex = idx - 1;
        }

        function addProductRow(product, opts) {
            opts = opts || {};
            var productId = product.product_id || product.id;
            var variationId = product.variation_id;
            var sellLineId = product.sell_line_id || '';
            var maxQty = product.qty_returnable != null ? Number(product.qty_returnable) : null;
            var startQty = opts.qty != null ? Number(opts.qty) : 1;
            if (isNaN(startQty)) {
                startQty = 1;
            }
            if (maxQty != null && startQty > maxQty) {
                startQty = maxQty;
            }
            if (opts.qty == null && startQty <= 0) {
                startQty = maxQty != null && maxQty > 0 ? Math.min(1, maxQty) : 1;
            }
            if (startQty < 0) {
                startQty = 0;
            }

            var exists = false;
            $('#standalone_return_table tbody tr').each(function () {
                var $tr = $(this);
                if (sellLineId && String($tr.data('sell-line-id')) === String(sellLineId)) {
                    exists = true;
                    var $qty = $tr.find('.return-qty');
                    var next = (parseFloat($qty.val()) || 0) + 1;
                    var max = parseFloat($qty.attr('max'));
                    if (!isNaN(max) && next > max) {
                        next = max;
                        if (typeof toastr !== 'undefined') {
                            toastr.warning('Return quantity exceeds the remaining returnable quantity.');
                        }
                    }
                    $qty.val(next);
                    calculateRowTotal($tr);
                } else if (
                    !sellLineId &&
                    String($tr.data('product-id')) === String(productId) &&
                    String($tr.data('variation-id')) === String(variationId)
                ) {
                    exists = true;
                    var $q2 = $tr.find('.return-qty');
                    $q2.val((parseFloat($q2.val()) || 0) + 1);
                    calculateRowTotal($tr);
                }
            });
            if (exists) {
                return;
            }

            var unitPrice =
                parseFloat(product.unit_price) ||
                parseFloat(product.selling_price) ||
                parseFloat(product.default_sell_price) ||
                parseFloat(product.sell_price) ||
                0;

            rowIndex++;
            var maxAttr = maxQty != null ? ' max="' + maxQty + '"' : '';
            var minAttr = startQty === 0 ? ' min="0"' : ' min="0.01"';
            var returnableHint =
                maxQty != null
                    ? '<div class="text-muted" style="font-size:11px;">Returnable: ' +
                      maxQty.toFixed(2) +
                      (product.qty_sold != null
                          ? ' (sold ' + Number(product.qty_sold).toFixed(2) + ', returned ' + Number(product.qty_returned || 0).toFixed(2) + ')'
                          : '') +
                      '</div>'
                    : '';

            var row =
                '<tr data-product-id="' +
                productId +
                '" data-variation-id="' +
                variationId +
                '" data-sell-line-id="' +
                (sellLineId || '') +
                '">' +
                '<td class="sr-row-index">' +
                rowIndex +
                '</td>' +
                '<td>' +
                escapeHtml(product.name || '') +
                (product.sub_sku ? '<br><small class="text-muted">SKU: ' + escapeHtml(product.sub_sku) + '</small>' : '') +
                returnableHint +
                '<input type="hidden" name="products[' +
                rowIndex +
                '][product_id]" value="' +
                productId +
                '">' +
                '<input type="hidden" name="products[' +
                rowIndex +
                '][variation_id]" value="' +
                variationId +
                '">' +
                (sellLineId
                    ? '<input type="hidden" name="products[' + rowIndex + '][sell_line_id]" value="' + sellLineId + '">'
                    : '') +
                '</td>' +
                '<td><input type="number" name="products[' +
                rowIndex +
                '][unit_price]" class="form-control input-sm return-unit-price" value="' +
                unitPrice.toFixed(2) +
                '" step="0.01" min="0"' +
                (sellLineId ? ' readonly' : '') +
                '></td>' +
                '<td><input type="number" name="products[' +
                rowIndex +
                '][quantity]" class="form-control input-sm return-qty" value="' +
                (startQty === 0 ? '' : startQty) +
                '" step="0.01"' +
                minAttr +
                maxAttr +
                '></td>' +
                '<td class="return-subtotal">' +
                (unitPrice * startQty).toFixed(2) +
                '</td>' +
                '<td><button type="button" class="btn btn-xs btn-danger remove-row"><i class="fa fa-trash"></i></button></td>' +
                '</tr>';

            $('#standalone_return_table tbody').append(row);
            updateRowNumbers();
            calculateTotal();
        }

        window.vsposStandaloneAddProduct = addProductRow;

        function runProductSearch(term) {
            term = $.trim(term || '');
            if (term.length < 1) {
                hideDd();
                return;
            }

            // Invoice mode: filter loaded invoice lines first
            if (invoiceMode && invoiceLines.length) {
                var t = term.toLowerCase();
                var matched = invoiceLines.filter(function (line) {
                    return (
                        String(line.name || '').toLowerCase().indexOf(t) !== -1 ||
                        String(line.sub_sku || '').toLowerCase().indexOf(t) !== -1 ||
                        String(line.tyre_size || '').toLowerCase().indexOf(t) !== -1
                    );
                });
                renderDd(matched, 'No matching products on this invoice');
                return;
            }

            ensureDropdown();
            $dd.html('<div class="vspos-live-search-empty">Searching...</div>').addClass('is-open');

            if (searchReq && searchReq.abort) {
                searchReq.abort();
            }
            searchReq = $.getJSON('/products/list', {
                term: term,
                location_id: locId(),
                check_qty: false,
                not_for_selling: 0,
                search_fields: ['name', 'sku', 'sub_sku'],
            })
                .done(function (data) {
                    var list = Array.isArray(data) ? data : [];
                    // Decode HTML entities from server-escaped names for display/storage
                    list = list.map(function (p) {
                        var $tmp = $('<textarea/>').html(p.name || '');
                        p.name = $tmp.val();
                        p.selling_price = p.selling_price;
                        p.sell_price = p.selling_price;
                        return p;
                    });
                    renderDd(list);
                })
                .fail(function (xhr) {
                    if (xhr.statusText === 'abort') {
                        return;
                    }
                    renderDd([], 'Product could not be found.');
                });
        }

        function selectActive() {
            if (!results.length || activeIndex < 0) {
                return;
            }
            var product = results[activeIndex];
            addProductRow(product);
            $('#search_product').val('');
            hideDd();
        }

        $('#search_product').on('input', function () {
            var term = $(this).val();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(function () {
                runProductSearch(term);
            }, 220);
        });

        $('#search_product').on('keydown', function (e) {
            if (e.key === 'ArrowDown') {
                if ($dd && $dd.hasClass('is-open') && results.length) {
                    e.preventDefault();
                    setActive(activeIndex + 1);
                }
                return;
            }
            if (e.key === 'ArrowUp') {
                if ($dd && $dd.hasClass('is-open') && results.length) {
                    e.preventDefault();
                    setActive(activeIndex - 1);
                }
                return;
            }
            if (e.key === 'Escape') {
                hideDd();
                return;
            }
            if (e.key === 'Enter') {
                e.preventDefault();
                if ($dd && $dd.hasClass('is-open') && results.length) {
                    selectActive();
                } else {
                    runProductSearch($(this).val());
                }
            }
        });

        $(document).on('mousedown', '#vspos_sr_live_dd .vspos-live-search-item', function (e) {
            e.preventDefault();
            activeIndex = parseInt($(this).data('index'), 10);
            selectActive();
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#search_product, #vspos_sr_live_dd, .vspos-sr-search-wrap').length) {
                hideDd();
            }
        });

        // F8 — open bulk search if modal present, else focus search
        $(document).on('keydown', function (e) {
            if (e.key === 'F8') {
                e.preventDefault();
                if (typeof window.vsposOpenBulkProductSearch === 'function' && $('#vspos_bulk_search_modal').length) {
                    window.vsposOpenBulkProductSearch($('#search_product').val() || '');
                } else {
                    $('#search_product').focus().select();
                }
            }
        });

        $('#vspos_sr_f8_btn').on('click', function () {
            if (typeof window.vsposOpenBulkProductSearch === 'function' && $('#vspos_bulk_search_modal').length) {
                window.vsposOpenBulkProductSearch($('#search_product').val() || '');
            } else {
                $('#search_product').focus();
            }
        });

        // Hook bulk modal row select for standalone page (when POS pos_product_row is absent)
        $(document).on('click', '#vspos_bulk_search_modal .vspos-bulk-row', function () {
            if (typeof window.pos_product_row === 'function') {
                return; // POS handler owns it
            }
            var idx = parseInt($(this).data('index'), 10);
            // Bulk search keeps lastResults internally; fall back to data attrs
            var variationId = $(this).data('variation_id');
            var name = $(this).find('.vspos-bulk-name').text() || $(this).data('name');
            if (!variationId) {
                return;
            }
            // Fetch from list by re-searching single variation via products/list is heavy;
            // use data attributes if present
            addProductRow({
                variation_id: variationId,
                product_id: $(this).data('product_id'),
                name: name,
                selling_price: $(this).data('sell_price'),
                sub_sku: $(this).data('sku'),
            });
            $('#vspos_bulk_search_modal').modal('hide');
        });

        function clearInvoiceMode() {
            invoiceMode = false;
            invoiceLines = [];
            $('#transaction_id').val('');
            $('#against_invoice_banner').addClass('hide').empty();
            $('#location_id').prop('disabled', false);
            $('#search_product').attr('placeholder', 'Enter Product name / SKU / Scan bar code');
        }

        function applyInvoice(payload) {
            var sale = payload.sale;
            invoiceMode = true;
            invoiceLines = payload.lines || [];

            $('#transaction_id').val(sale.transaction_id);
            $('#location_id').val(sale.location_id).trigger('change').prop('disabled', true);
            $('#location_id_locked').val(sale.location_id);

            if (sale.contact_id) {
                var opt = new Option(sale.contact_name || ('#' + sale.contact_id), sale.contact_id, true, true);
                $('#customer_id').append(opt).trigger('change');
            }

            $('#against_invoice_banner')
                .removeClass('hide')
                .html(
                    '<strong>Invoice loaded:</strong> ' +
                        escapeHtml(sale.invoice_no) +
                        ' · ' +
                        escapeHtml(sale.contact_name || 'Walk-in') +
                        ' · ' +
                        escapeHtml(sale.location_name || '') +
                        ' <button type="button" class="btn btn-xs btn-default" id="clear_against_invoice">Clear</button>'
                );

            $('#standalone_return_table tbody').empty();
            rowIndex = 0;
            invoiceLines.forEach(function (line) {
                addProductRow(line, { qty: 0 });
            });
            calculateTotal();
            $('#search_product').attr('placeholder', 'Filter invoice products / add by name or SKU');
            if (typeof toastr !== 'undefined') {
                toastr.success('Invoice ' + sale.invoice_no + ' loaded');
            }
        }

        function lookupInvoice() {
            var invoiceNo = $.trim($('#against_invoice_no').val() || '');
            if (!invoiceNo) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Enter an invoice number to load.');
                }
                return;
            }
            var $btn = $('#load_against_invoice');
            $btn.prop('disabled', true);
            $.getJSON('/sell-return/standalone/lookup-invoice', { invoice_no: invoiceNo })
                .done(function (res) {
                    if (!res || !res.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error((res && res.msg) || 'Invoice could not be found.');
                        }
                        return;
                    }
                    applyInvoice(res);
                })
                .fail(function () {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Unable to load invoice items.');
                    }
                })
                .always(function () {
                    $btn.prop('disabled', false);
                });
        }

        $('#load_against_invoice').on('click', lookupInvoice);
        $('#against_invoice_no').on('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                lookupInvoice();
            }
        });

        $(document).on('click', '#clear_against_invoice', function () {
            clearInvoiceMode();
            $('#standalone_return_table tbody').empty();
            rowIndex = 0;
            calculateTotal();
            $('#against_invoice_no').val('');
        });

        $(document).on('change', '.return-unit-price, .return-qty', function () {
            var $row = $(this).closest('tr');
            var $qty = $row.find('.return-qty');
            var max = parseFloat($qty.attr('max'));
            var val = parseFloat($qty.val()) || 0;
            if (!isNaN(max) && val > max) {
                $qty.val(max);
                if (typeof toastr !== 'undefined') {
                    toastr.warning('Return quantity exceeds the remaining returnable quantity.');
                }
            }
            calculateRowTotal($row);
        });

        $(document).on('click', '.remove-row', function () {
            $(this).closest('tr').remove();
            updateRowNumbers();
            calculateTotal();
        });

        $('#customer_id').select2({
            ajax: {
                url: '/contacts/customers',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return { q: params.term, page: params.page };
                },
                processResults: function (data) {
                    return { results: data };
                },
            },
            placeholder: 'Customer',
            minimumInputLength: 1,
            escapeMarkup: function (markup) {
                return markup;
            },
            templateResult: function (data) {
                if (data.loading) {
                    return data.text;
                }
                var mobile = data.mobile ? ' <span class="text-muted">(' + data.mobile + ')</span>' : '';
                return data.text + mobile;
            },
            templateSelection: function (data) {
                return data.text;
            },
        });

        $('#save_standalone_return').on('click', function (e) {
            e.preventDefault();

            // Drop zero-qty rows before submit (invoice mode)
            $('#standalone_return_table tbody tr').each(function () {
                var q = parseFloat($(this).find('.return-qty').val()) || 0;
                if (q <= 0) {
                    $(this).remove();
                }
            });
            updateRowNumbers();

            if ($('#standalone_return_table tbody tr').length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('No products added');
                }
                return;
            }

            // Re-enable location so value posts
            var $loc = $('#location_id');
            var wasDisabled = $loc.prop('disabled');
            if (wasDisabled) {
                $loc.prop('disabled', false);
            }
            if ($('#location_id_locked').val()) {
                $loc.val($('#location_id_locked').val());
            }

            var $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Please wait');

            $.ajax({
                url: $('#standalone_return_form').attr('action'),
                method: 'POST',
                data: $('#standalone_return_form').serialize(),
                dataType: 'json',
                success: function (result) {
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Generate Return Note');
                    if (wasDisabled) {
                        $loc.prop('disabled', true);
                    }
                    if (result.success === 1) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(result.msg);
                        }
                        $('#return_note_receipt_content').html(result.receipt);
                        $('#return_note_receipt_modal').modal('show');
                        $('#standalone_return_form')[0].reset();
                        $('#standalone_return_table tbody').empty();
                        clearInvoiceMode();
                        calculateTotal();
                        $('#customer_id').val($('#customer_id').data('walkin') || '').trigger('change');
                    } else if (typeof toastr !== 'undefined') {
                        toastr.error(result.msg || 'Unable to generate return note.');
                    }
                },
                error: function (xhr) {
                    $btn.prop('disabled', false).html('<i class="fa fa-save"></i> Generate Return Note');
                    if (wasDisabled) {
                        $loc.prop('disabled', true);
                    }
                    var msg = 'Unable to generate return note.';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }
                    if (typeof toastr !== 'undefined') {
                        toastr.error(msg);
                    }
                },
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
