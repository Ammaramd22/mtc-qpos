/**
 * Vehicle Modification POS: workshop services + job checkout into the existing cart.
 */
(function () {
    if (window.__vsposWorkshopBound) {
        return;
    }
    window.__vsposWorkshopBound = true;

    function locationId() {
        return window.jQuery('input#location_id').val() || window.jQuery('#location_id').val() || '';
    }

    function insertPosRow(variationId, quantity, unitPrice, label, done) {
        var $ = window.jQuery;
        var loc = locationId();
        if (!loc || !variationId) {
            if (done) done(false);
            return;
        }
        $.ajax({
            method: 'GET',
            url: '/sells/pos/get_product_row/' + variationId + '/' + loc,
            data: {
                product_row: $('input#product_row_count').val(),
                customer_id: $('select#customer_id').val(),
                price_group: $('#price_group').length ? $('#price_group').val() : '',
                quantity: quantity || 1,
                disable_qty_alert: true,
            },
            dataType: 'json',
            success: function (result) {
                if (!result || !result.success) {
                    if (done) done(false);
                    return;
                }
                if (typeof pos_insert_product_row === 'function') {
                    pos_insert_product_row(result);
                }
                var $row = $('table#pos_table tbody tr.product_row').last();
                if (unitPrice != null && !isNaN(unitPrice)) {
                    if (typeof __write_number === 'function') {
                        __write_number($row.find('input.pos_unit_price'), unitPrice);
                        __write_number($row.find('input.pos_unit_price_inc_tax'), unitPrice);
                    } else {
                        $row.find('input.pos_unit_price').val(unitPrice);
                        $row.find('input.pos_unit_price_inc_tax').val(unitPrice);
                    }
                    $row.find('input.pos_unit_price').trigger('change');
                }
                if (label) {
                    var $name = $row.find('.vspos-line-name');
                    if ($name.length) {
                        $name.text(label);
                    }
                }
                if (typeof pos_total_row === 'function') {
                    pos_total_row();
                }
                if (done) done(true);
            },
            error: function () {
                if (done) done(false);
            }
        });
    }

    window.jQuery(function ($) {
        var cache = null;

        $(document).on('click', '.vspos-mod-svc-btn', function () {
            var key = $(this).data('service');
            var loc = locationId();
            if (!loc) {
                if (typeof toastr !== 'undefined') toastr.error('Select a business location');
                return;
            }
            var open = function (services) {
                var svc = services[key];
                if (!svc || !svc.variation_id) {
                    if (typeof toastr !== 'undefined') toastr.error('Service is not available');
                    return;
                }
                var custom = !!svc.needs_custom_name;
                $('#vspos_mod_svc_key').val(key);
                $('#vspos_mod_svc_variation_id').val(svc.variation_id);
                $('#vspos_mod_svc_title').text(svc.name);
                $('#vspos_mod_svc_name_wrap').toggleClass('hide', !custom);
                $('#vspos_mod_svc_name').val(custom ? '' : svc.name);
                $('#vspos_mod_svc_amount').val('');
                $('#vspos_mod_svc_modal').modal('show');
            };
            if (cache) {
                open(cache);
                return;
            }
            $.getJSON('/vehicle-modification/pos-services', { location_id: loc })
                .done(function (res) {
                    if (!res || !res.success) {
                        if (typeof toastr !== 'undefined') toastr.error((res && res.msg) || 'Could not load services');
                        return;
                    }
                    cache = res.services;
                    open(cache);
                });
        });

        $('#vspos_mod_svc_add').on('click', function () {
            var variationId = parseInt($('#vspos_mod_svc_variation_id').val(), 10);
            var amount = parseFloat(String($('#vspos_mod_svc_amount').val() || '').replace(/,/g, ''));
            var label = $.trim($('#vspos_mod_svc_name').val() || $('#vspos_mod_svc_title').text() || 'Service');
            if (!variationId || isNaN(amount) || amount < 0) {
                if (typeof toastr !== 'undefined') toastr.error('Enter a valid amount');
                return;
            }
            $('#vspos_mod_svc_modal').modal('hide');
            insertPosRow(variationId, 1, amount, label);
        });

        var job = window.__workshop_job_checkout;
        if (job && $.isArray(job.lines) && job.lines.length) {
            setTimeout(function () {
                if (job.contact_id && $('#customer_id').length) {
                    $('#customer_id').val(job.contact_id).trigger('change');
                }
                var i = 0;
                function next() {
                    if (i >= job.lines.length) {
                        window.__workshop_job_checkout = null;
                        return;
                    }
                    var line = job.lines[i++];
                    insertPosRow(line.variation_id, line.quantity || 1, line.unit_price, line.name, next);
                }
                next();
            }, 900);
        }
    });
})();
