$(document).ready(function() {
    $('table#product_table tbody').find('.label-date-picker').each( function(){
        $(this).datepicker({
            autoclose: true
        });
    });
    //Add products
    if ($('#search_product_for_label').length > 0) {
        $('#search_product_for_label')
            .autocomplete({
                source: '/purchases/get_products?check_enable_stock=false',
                minLength: 2,
                response: function(event, ui) {
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];
                        $(this)
                            .data('ui-autocomplete')
                            ._trigger('select', 'autocompleteselect', ui);
                        $(this).autocomplete('close');
                    } else if (ui.content.length == 0) {
                        swal(LANG.no_products_found);
                    }
                },
                select: function(event, ui) {
                    $(this).val(null);
                    get_label_product_row(ui.item.product_id, ui.item.variation_id);
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
            return $('<li>')
                .append('<div>' + item.text + '</div>')
                .appendTo(ul);
        };
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
                    window.open(base + '/labels/browser-print?print_id=' + encodeURIComponent(res.print_id), '_blank');
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

function get_label_product_row(product_id, variation_id) {
    if (product_id) {
        var row_count = $('table#product_table tbody tr').length;
        $.ajax({
            method: 'GET',
            url: '/labels/add-product-row',
            dataType: 'html',
            data: { product_id: product_id, row_count: row_count, variation_id: variation_id },
            success: function(result) {
                $('table#product_table tbody').append(result);

                $('table#product_table tbody').find('.label-date-picker').each( function(){
                    $(this).datepicker({
                        autoclose: true
                    });
                });
                updateLabelsEmptyState();
            },
        });
    }
}
