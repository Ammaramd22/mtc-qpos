/**
 * Tyre shop quick services: Air Fill, Nitrogen Fill, Tyre Fix, Addons.
 * Uses non-stock products (enable_stock=0) + existing POS cart row API.
 *
 * IMPORTANT: bind handlers exactly once. Double-binding caused one click
 * to insert two cart rows.
 */
(function () {
    if (window.__vsposTyreServicesBound) {
        return;
    }
    window.__vsposTyreServicesBound = true;

    function boot() {
        if (window.__vsposTyreServicesReady) {
            return;
        }
        if (typeof window.jQuery === 'undefined') {
            return;
        }
        var $ = window.jQuery;
        if (!$('#vspos_tyre_svc_modal').length) {
            return;
        }
        window.__vsposTyreServicesReady = true;

        var cache = null;
        var cacheLoc = null;
        var submitting = false;

        function locationId() {
            return $('input#location_id').val() || $('#location_id').val() || '';
        }

        function ensureServices(cb) {
            var loc = locationId();
            if (!loc) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Select a business location');
                }
                return;
            }
            if (cache && cacheLoc === loc) {
                cb(cache);
                return;
            }
            $.getJSON('/sells/pos/tyre-shop-services', { location_id: loc })
                .done(function (res) {
                    if (!res || !res.success || !res.services) {
                        if (typeof toastr !== 'undefined') {
                            toastr.error((res && res.msg) || 'Could not load services');
                        }
                        return;
                    }
                    cache = res.services;
                    cacheLoc = loc;
                    cb(cache);
                })
                .fail(function () {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Could not load services');
                    }
                });
        }

        function openModal(key) {
            ensureServices(function (services) {
                var svc = services[key];
                if (!svc || !svc.variation_id) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Service is not available');
                    }
                    return;
                }
                var isAddon = !!svc.needs_custom_name;
                submitting = false;
                $('#vspos_tyre_svc_key').val(key);
                $('#vspos_tyre_svc_variation_id').val(svc.variation_id);
                $('#vspos_tyre_svc_title').text(svc.name);
                $('#vspos_tyre_svc_name_wrap').toggleClass('hide', !isAddon);
                $('#vspos_tyre_svc_name').val(isAddon ? '' : svc.name);
                $('#vspos_tyre_svc_amount').val('');
                $('#vspos_tyre_svc_add').prop('disabled', false);
                $('#vspos_tyre_svc_modal').modal('show');
                setTimeout(function () {
                    if (isAddon) {
                        $('#vspos_tyre_svc_name').focus();
                    } else {
                        $('#vspos_tyre_svc_amount').focus().select();
                    }
                }, 280);
            });
        }

        function applyLineLabel($row, label) {
            var $name = $row.find('.vspos-line-name');
            if ($name.length) {
                var html = $name.html() || '';
                if (html.indexOf('vspos-line-edit-ico') !== -1) {
                    $name.html($('<div>').text(label).html() + ' <i class="fa fa-pen vspos-line-edit-ico"></i>');
                } else {
                    $name.text(label);
                }
            }
            $row.find('.vspos-line-sku').text('Service');

            var $note = $row.find('textarea[name*="[sell_line_note]"]');
            if ($note.length) {
                $note.val(label);
            }
            var $hiddenNote = $row.find('input.pos_sell_line_note');
            if (!$hiddenNote.length) {
                var vName = $row.find('input[name*="[variation_id]"]').attr('name') || '';
                if (vName) {
                    $row.find('td.vspos-pos-hidden-fields, td.hide').first().append(
                        '<input type="hidden" name="' +
                            vName.replace('[variation_id]', '[sell_line_note]') +
                            '" class="pos_sell_line_note" value="">'
                    );
                    $hiddenNote = $row.find('input.pos_sell_line_note');
                }
            }
            if ($hiddenNote.length) {
                $hiddenNote.val(label);
            }
        }

        function addServiceToCart() {
            // One user action → one cart insertion (blocks double-bound handlers / rapid click)
            if (submitting) {
                return;
            }

            var key = $('#vspos_tyre_svc_key').val();
            var variationId = parseInt($('#vspos_tyre_svc_variation_id').val(), 10);
            var amount = parseFloat(String($('#vspos_tyre_svc_amount').val() || '').replace(/,/g, ''));
            var name = $.trim($('#vspos_tyre_svc_name').val() || '');
            var isAddon = key === 'addons';

            if (!variationId) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Service is not available');
                }
                return;
            }
            if (isAddon && !name) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Enter addon name');
                }
                $('#vspos_tyre_svc_name').focus();
                return;
            }
            if (isNaN(amount) || amount < 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Enter a valid amount');
                }
                $('#vspos_tyre_svc_amount').focus();
                return;
            }

            var label = isAddon ? name : ($('#vspos_tyre_svc_title').text() || name || 'Service');
            var location = locationId();
            if (!location) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('Select a business location');
                }
                return;
            }

            submitting = true;
            var $btn = $('#vspos_tyre_svc_add');
            $btn.prop('disabled', true);
            window.__vspos_entry_committing = true;

            $.ajax({
                method: 'GET',
                url: '/sells/pos/get_product_row/' + variationId + '/' + location,
                data: {
                    product_row: $('input#product_row_count').val(),
                    customer_id: $('select#customer_id').val(),
                    price_group: $('#price_group').length ? $('#price_group').val() : '',
                    quantity: 1,
                    disable_qty_alert: true,
                },
                dataType: 'json',
                success: function (result) {
                    window.__vspos_entry_committing = false;
                    if (!result || !result.success) {
                        submitting = false;
                        $btn.prop('disabled', false);
                        if (typeof toastr !== 'undefined') {
                            toastr.error((result && result.msg) || 'Could not add to cart');
                        }
                        return;
                    }
                    if (typeof pos_insert_product_row === 'function') {
                        pos_insert_product_row(result);
                    }

                    var $row = $('table#pos_table tbody tr.product_row').last();
                    if (typeof __write_number === 'function') {
                        __write_number($row.find('input.pos_unit_price'), amount);
                        __write_number($row.find('input.pos_unit_price_inc_tax'), amount);
                        __write_number($row.find('input.pos_quantity'), 1);
                    } else {
                        $row.find('input.pos_unit_price').val(amount);
                        $row.find('input.pos_unit_price_inc_tax').val(amount);
                        $row.find('input.pos_quantity').val(1);
                    }

                    applyLineLabel($row, label);

                    $row.find('input.pos_unit_price').trigger('change');
                    $row.find('input.pos_quantity').trigger('change');

                    if (typeof vspos_sync_all_row_edit_modals === 'function') {
                        vspos_sync_all_row_edit_modals();
                    } else if (typeof pos_each_row === 'function') {
                        pos_each_row($row);
                    }
                    if (typeof pos_total_row === 'function') {
                        pos_total_row();
                    }

                    $('#vspos_tyre_svc_modal').modal('hide');
                    submitting = false;
                    $btn.prop('disabled', false);
                    if (typeof toastr !== 'undefined') {
                        toastr.success(label + ' added');
                    }
                },
                error: function () {
                    window.__vspos_entry_committing = false;
                    submitting = false;
                    $btn.prop('disabled', false);
                    if (typeof toastr !== 'undefined') {
                        toastr.error('Could not add to cart');
                    }
                },
            });
        }

        // Namespaced handlers so re-init cannot stack duplicates
        $(document)
            .off('click.vsposTyreSvc', '.vspos-tyre-svc-btn')
            .on('click.vsposTyreSvc', '.vspos-tyre-svc-btn', function (e) {
                e.preventDefault();
                e.stopPropagation();
                openModal($(this).data('service'));
            });

        $('#vspos_tyre_svc_add')
            .off('click.vsposTyreSvc')
            .on('click.vsposTyreSvc', function (e) {
                e.preventDefault();
                e.stopPropagation();
                addServiceToCart();
            });

        $('#vspos_tyre_svc_amount, #vspos_tyre_svc_name')
            .off('keydown.vsposTyreSvc')
            .on('keydown.vsposTyreSvc', function (e) {
                if (e.key !== 'Enter' && e.which !== 13) {
                    return;
                }
                e.preventDefault();
                e.stopPropagation();
                if (this.id === 'vspos_tyre_svc_name') {
                    $('#vspos_tyre_svc_amount').focus().select();
                    return;
                }
                addServiceToCart();
            });

        setTimeout(function () {
            if (locationId()) {
                ensureServices(function () {});
            }
        }, 1200);
    }

    // Single boot path only (do NOT also call jQuery(boot) — that double-bound handlers)
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery(boot);
    } else if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof window.jQuery !== 'undefined') {
                window.jQuery(boot);
            }
        });
    }
})();
