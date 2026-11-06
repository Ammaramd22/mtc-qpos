$(document).ready(function() {
    $('table#product_table tbody').find('.label-date-picker').each( function(){
        $(this).datepicker({
            autoclose: true
        });
    });
    // Suggestions only. A product is added after the user clicks a row or presses Enter on the highlighted row.
    var labelSearchXhr = null;
    var labelSearchSeq = 0;
    var labelPendingVariations = {};

    function labelBase() {
        return (typeof base_path !== 'undefined' && base_path) ? base_path : '';
    }

    function setLabelSearchStatus(text, isError) {
        var el = document.getElementById('labels_search_status');
        if (!el) return;
        el.textContent = text || '';
        el.classList.toggle('is-error', !!isError);
    }

    function setLabelSearchLoading(on) {
        $('#search_product_for_label').closest('.labels-search-wrap').toggleClass('is-loading', !!on);
    }

    function labelRowKey(variationId, size, color) {
        return String(variationId || '') + '|' + String(size || '') + '|' + String(color || '');
    }

    function findLabelRow(variationId, size, color) {
        var found = null;
        if (!variationId) return null;
        var wantSize = size ? String(size) : '';
        var wantColor = color ? String(color) : '';
        $('table#product_table tbody tr.labels-product-row').each(function() {
            var vid = $(this).find('input[name$="[variation_id]"]').val();
            if (String(vid) !== String(variationId)) return;
            if (wantSize) {
                var rowSize = $(this).find('select[name$="[label_size]"]').val() || '';
                if (String(rowSize) !== wantSize) return;
            }
            if (wantColor) {
                var rowColor = $(this).find('select[name$="[label_color]"]').val() || '';
                if (String(rowColor) !== wantColor) return;
            }
            found = $(this);
            return false;
        });
        return found;
    }

    function formatLabelList(values) {
        var list = $.isArray(values) ? values : [];
        if (!list.length) return '';
        if (list.length <= 10) return list.join(', ');
        return list.slice(0, 10).join(', ') + ' +' + (list.length - 10);
    }

    function bumpLabelQuantity($row, extra) {
        var $qty = $row.find('input[name$="[quantity]"]');
        var current = parseInt($qty.val(), 10);
        var add = extra || 1;
        if (isNaN(current) || current < 1) {
            $qty.val(add);
        } else {
            $qty.val(current + add);
        }
        $row.addClass('labels-row-flash');
        setTimeout(function() { $row.removeClass('labels-row-flash'); }, 700);
    }

    function reserveLabelRowIndex() {
        if (typeof window.__labelRowSeq !== 'number') {
            window.__labelRowSeq = -1;
            $('table#product_table tbody input[name^="products["]').each(function() {
                var match = String(this.name).match(/^products\[(\d+)\]/);
                if (match) {
                    window.__labelRowSeq = Math.max(window.__labelRowSeq, parseInt(match[1], 10));
                }
            });
        }
        window.__labelRowSeq += 1;
        return window.__labelRowSeq;
    }
    window.reserveLabelRowIndex = reserveLabelRowIndex;

    function add_or_increment_label_product(product_id, variation_id, label_size, label_color) {
        if (!product_id || !variation_id) return;
        label_size = label_size || '';
        label_color = label_color || '';
        var key = labelRowKey(variation_id, label_size, label_color);
        var $existing = findLabelRow(variation_id, label_size, label_color);
        if ($existing) {
            bumpLabelQuantity($existing, 1);
            return;
        }
        if (labelPendingVariations[key]) {
            labelPendingVariations[key].extra += 1;
            return;
        }
        labelPendingVariations[key] = { extra: 0 };
        get_label_product_row(product_id, variation_id, function(ok) {
            var extra = labelPendingVariations[key] ? labelPendingVariations[key].extra : 0;
            delete labelPendingVariations[key];
            if (!ok) return;
            if (extra > 0) {
                var $row = findLabelRow(variation_id, label_size, label_color);
                if ($row) bumpLabelQuantity($row, extra);
            }
        }, label_size, label_color);
    }

    if ($('#search_product_for_label').length > 0) {
        var $labelSearch = $('#search_product_for_label');
        $labelSearch.autocomplete({
                source: function(request, response) {
                    var term = $.trim(request.term || '');
                    if (term.length < 2) {
                        setLabelSearchLoading(false);
                        setLabelSearchStatus('');
                        response([]);
                        return;
                    }
                    if (labelSearchXhr && labelSearchXhr.abort) {
                        labelSearchXhr.abort();
                    }
                    var seq = ++labelSearchSeq;
                    setLabelSearchLoading(true);
                    setLabelSearchStatus('');
                    labelSearchXhr = $.ajax({
                        url: labelBase() + '/labels/search-products',
                        dataType: 'json',
                        data: { term: term },
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    }).done(function(data) {
                        if (seq !== labelSearchSeq) return;
                        if (data && data.error) {
                            setLabelSearchStatus((typeof LANG !== 'undefined' && LANG.label_search_failed) ? LANG.label_search_failed : 'Search failed. Please try again.', true);
                            response([{
                                label: (typeof LANG !== 'undefined' && LANG.label_search_failed) ? LANG.label_search_failed : 'Search failed. Please try again.',
                                value: '',
                                disabled: true,
                                error: true
                            }]);
                            return;
                        }
                        var payload = (data && $.isArray(data.results)) ? data : { results: $.isArray(data) ? data : [], truncated: false };
                        var rows = payload.results;
                        if (!rows.length) {
                            setLabelSearchStatus((typeof LANG !== 'undefined' && LANG.no_products_found) ? LANG.no_products_found : 'No matching product found');
                            response([{
                                label: (typeof LANG !== 'undefined' && LANG.no_products_found) ? LANG.no_products_found : 'No matching product found',
                                value: '',
                                disabled: true,
                                empty: true
                            }]);
                            return;
                        }
                        var status = rows.length + ' suggestion' + (rows.length === 1 ? '' : 's');
                        if (payload.truncated) {
                            status += (typeof LANG !== 'undefined' && LANG.label_search_narrow) ? LANG.label_search_narrow : '. Type more of the name, SKU, barcode, size, or color.';
                        }
                        setLabelSearchStatus(status);
                        response(rows);
                    }).fail(function(xhr) {
                        if (!xhr || xhr.statusText === 'abort') return;
                        if (seq !== labelSearchSeq) return;
                        setLabelSearchStatus('Search failed. Please try again.', true);
                        response([{
                            label: 'Search failed. Please try again.',
                            value: '',
                            disabled: true,
                            error: true
                        }]);
                    }).always(function() {
                        if (seq === labelSearchSeq) setLabelSearchLoading(false);
                    });
                },
                minLength: 2,
                delay: 300,
                autoFocus: true,
                appendTo: 'body',
                position: { my: 'left top', at: 'left bottom', collision: 'flipfit' },
                focus: function(event) {
                    event.preventDefault();
                },
                select: function(event, ui) {
                    event.preventDefault();
                    if (!ui.item || ui.item.disabled || ui.item.empty || ui.item.error || !ui.item.product_id || !ui.item.variation_id) {
                        return false;
                    }
                    $(this).val('');
                    setLabelSearchStatus('');
                    add_or_increment_label_product(
                        ui.item.product_id,
                        ui.item.variation_id,
                        ui.item.match_size || '',
                        ui.item.match_color || ''
                    );
                    return false;
                },
                open: function() {
                    var $menu = $(this).autocomplete('widget');
                    $menu.addClass('labels-suggest-menu');
                    $menu.css('z-index', 10050);
                    $menu.outerWidth(Math.max($(this).outerWidth(), 280));
                    $(this).attr('aria-expanded', 'true');
                },
                close: function() {
                    $(this).attr('aria-expanded', 'false');
                }
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            ul.addClass('labels-suggest-menu');
            if (item.disabled || item.empty || item.error) {
                return $('<li class="ui-state-disabled labels-suggest-status">')
                    .append($('<div class="labels-suggest-item">').text(item.label || ''))
                    .appendTo(ul);
            }
            var sizes = $.isArray(item.sizes) ? item.sizes : (item.size ? [item.size] : []);
            var colors = $.isArray(item.colors) ? item.colors : (item.color ? [item.color] : []);
            var bits = [];
            if (item.sku) bits.push('SKU ' + item.sku);
            if (item.barcode && item.barcode !== item.sku) bits.push('Barcode ' + item.barcode);
            if (item.variation) bits.push(item.variation);
            var $item = $('<div class="labels-suggest-item">');
            var $name = $('<div class="labels-suggest-name">').text(item.name || item.label || '');
            if (item.inactive) {
                $name.append($('<span class="labels-suggest-badge">').text('Inactive'));
            }
            $item.append($name);
            if (bits.length) {
                $item.append($('<div class="labels-suggest-meta">').text(bits.join(' · ')));
            }
            var sizeText = formatLabelList(sizes);
            var colorText = formatLabelList(colors);
            if (sizeText) {
                $item.append($('<div class="labels-suggest-meta">').text('Size: ' + sizeText));
            }
            if (colorText) {
                $item.append($('<div class="labels-suggest-meta">').text('Color: ' + colorText));
            }
            return $('<li>').append($item).appendTo(ul);
        };

        $labelSearch.on('input', function() {
            if ($.trim(this.value).length < 2) {
                labelSearchSeq++;
                if (labelSearchXhr && labelSearchXhr.abort) labelSearchXhr.abort();
                setLabelSearchLoading(false);
                setLabelSearchStatus('');
                $labelSearch.autocomplete('close');
            }
        });
        $labelSearch.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
            if (e.key === 'Escape') {
                setLabelSearchStatus('');
            }
        });
    }

    function updateLabelsEmptyState() {
        var hasRows = $('table#product_table tbody tr.labels-product-row').length > 0;
        if (hasRows) {
            $('#labels_empty_state').addClass('hide');
        } else {
            $('#labels_empty_state').removeClass('hide');
        }
    }
    window.updateLabelsEmptyState = updateLabelsEmptyState;

    $(document).on('click', '.labels-remove-row', function() {
        $(this).closest('tr.labels-product-row').remove();
        updateLabelsEmptyState();
    });

    $(document).on('click', '.labels-add-combo', function() {
        var $row = $(this).closest('tr.labels-product-row');
        if (!$row.length) return;
        var $clone = $row.clone();
        var idx = reserveLabelRowIndex();
        $clone.find('[name]').each(function() {
            this.name = String(this.name).replace(/products\[\d+\]/, 'products[' + idx + ']');
        });
        $clone.removeClass('labels-row-flash');
        $row.after($clone);
        updateLabelsEmptyState();
    });

    $('button#labels_export_csv').click(function() {
        if ($('form#labels_export_form table#product_table tbody tr.labels-product-row').length > 0) {
            var url = base_path + '/labels/export-bartender-csv?' + $('form#labels_export_form').serialize();
            $.ajax({
                method: 'GET',
                url: url,
                dataType: 'json',
                success: function(result) {
                    if (result && result.success) {
                        var msg = result.message || 'Barcodes loaded successfully. Please open barcode print batch file.';
                        swal({
                            title: 'Success',
                            text: msg,
                            icon: 'success',
                            buttons: {
                                cancel: true,
                                confirm: {
                                    text: 'Open Barcode',
                                    closeModal: true
                                }
                            }
                        }).then(function(confirmOpen) {
                            if (result.download_url) {
                                window.open(result.download_url, '_blank');
                            }
                            if (confirmOpen) {
                                var launcherUrl = result.launcher_url || $('#bartender_batch_launcher_url').val();
                                if (launcherUrl) {
                                    window.location.href = launcherUrl;
                                }
                            }
                        });
                    } else {
                        window.open(url, '_blank');
                    }
                },
                error: function(xhr) {
                    // If endpoint returns file stream instead of JSON, keep old behavior.
                    window.open(url, '_blank');
                }
            });
        } else {
            swal(LANG.label_no_product_error).then(value => {
                $('#search_product_for_label').focus();
            });
        }
    });

    // Browser print — AJAX-POST products into session, then open a SHORT URL
    // (?print_id=xxx). Avoids LiteSpeed/shared-hosting 414 Request-URI Too Large.
    // Note: Labels show page also binds #labels_browser_print_v2 inline (cache-proof).
    $('button#labels_browser_print, button#labels_browser_print_v2').off('click.qposBp').on('click.qposBp', function() {
        if ($('form#labels_export_form table#product_table tbody tr.labels-product-row').length > 0) {
            var base = typeof base_path !== 'undefined' ? base_path : '';
            var token = $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val();
            var $btn = $(this);
            $btn.prop('disabled', true);
            $.ajax({
                method: 'POST',
                url: base + '/labels/browser-print/prepare',
                data: $('form#labels_export_form').serialize(),
                headers: token ? { 'X-CSRF-TOKEN': token } : {},
            }).done(function(res) {
                if (res && res.success && res.print_id) {
                    var theme = document.documentElement.getAttribute('data-vspos-theme') === 'light' ? 'light' : 'dark';
                    window.open(base + '/labels/browser-print?print_id=' + encodeURIComponent(res.print_id) + '&theme=' + theme, '_blank');
                } else {
                    swal((res && res.msg) || (LANG.label_no_product_error || 'Add products first'));
                }
            }).fail(function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) || 'Could not prepare labels. Please try again.';
                swal(msg);
            }).always(function() {
                $btn.prop('disabled', false);
            });
        } else {
            swal(LANG.label_no_product_error || 'Add products first').then(function() {
                $('#search_product_for_label').focus();
            });
        }
    });

    updateLabelsEmptyState();

    // Came from purchase create with ?auto_export=1 — products already in grid with purchase qty
    if ($('#labels_auto_export').val() === '1'
        && $('table#product_table tbody tr.labels-product-row').length > 0
        && $('#labels_export_csv').length) {
        setTimeout(function() {
            $('button#labels_export_csv').trigger('click');
            // Clean URL so refresh does not re-export
            if (window.history && window.history.replaceState) {
                var u = new URL(window.location.href);
                u.searchParams.delete('auto_export');
                window.history.replaceState({}, '', u.toString());
            }
        }, 500);
    }
});

function get_label_product_row(product_id, variation_id, done, label_size, label_color) {
    if (product_id) {
        var row_count = typeof reserveLabelRowIndex === 'function'
            ? reserveLabelRowIndex()
            : $('table#product_table tbody tr').length;
        var base = (typeof base_path !== 'undefined' && base_path) ? base_path : '';
        $.ajax({
            method: 'GET',
            url: base + '/labels/add-product-row',
            dataType: 'html',
            data: {
                product_id: product_id,
                row_count: row_count,
                variation_id: variation_id,
                label_size: label_size || '',
                label_color: label_color || ''
            },
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function(result) {
                if (!result || !$.trim(result)) {
                    swal('Could not add that product.');
                    if (done) done(false);
                    return;
                }
                $('table#product_table tbody').append(result);

                $('table#product_table tbody').find('.label-date-picker').each( function(){
                    $(this).datepicker({
                        autoclose: true
                    });
                });
                if (typeof updateLabelsEmptyState === 'function') updateLabelsEmptyState();
                if (done) done(true);
            },
            error: function() {
                swal('Could not add that product. Please try again.');
                if (done) done(false);
            }
        });
    } else if (done) {
        done(false);
    }
}
