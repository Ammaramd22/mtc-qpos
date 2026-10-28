//This file contains all functions used products tab

$(document).ready(function() {
    var tax_system_disabled = $('body').hasClass('tax-system-disabled');

    // Tyre Item Name: Size + Brand + Note (live). Manual name typing sets name_is_manual.
    (function vsposBindTyreAutoName() {
        function brandText($form) {
            var $sel = $form.find('select#brand_id');
            if (!$sel.length) {
                return '';
            }
            var t = ($sel.find('option:selected').text() || '').trim();
            if (!t || t.toLowerCase() === 'please select' || t === '—') {
                return '';
            }
            return t;
        }

        function buildName($form) {
            var size = ($form.find('input#tyre_size').val() || '').trim();
            var note = ($form.find('input#tyre_note').val() || '').trim();
            var brand = brandText($form);
            var parts = [];
            if (size) {
                parts.push(size);
            }
            if (brand) {
                parts.push(brand);
            }
            if (note) {
                parts.push(note);
            }
            return parts.join(' ');
        }

        function shouldAuto($form) {
            var size = ($form.find('input#tyre_size').val() || '').trim();
            var note = ($form.find('input#tyre_note').val() || '').trim();
            return !!(size || note);
        }

        function applyAuto($form) {
            if (!$form || !$form.length) {
                return;
            }
            if ($form.find('#name_is_manual').val() === '1') {
                return;
            }
            if (!shouldAuto($form)) {
                return;
            }
            var built = buildName($form);
            if (!built) {
                return;
            }
            $form.find('input#name').val(built);
        }

        $(document).on('input change', 'form.product_form input#tyre_size, form.product_form input#tyre_note, form#quick_add_product_form input#tyre_size, form#quick_add_product_form input#tyre_note', function () {
            var $form = $(this).closest('form');
            $form.find('#name_is_manual').val('0');
            applyAuto($form);
        });

        $(document).on('change', 'form.product_form select#brand_id, form#quick_add_product_form select#brand_id', function () {
            var $form = $(this).closest('form');
            $form.find('#name_is_manual').val('0');
            applyAuto($form);
        });

        $(document).on('input', 'form.product_form input#name, form#quick_add_product_form input#name', function () {
            $(this).closest('form').find('#name_is_manual').val('1');
        });
    })();

    if (tax_system_disabled) {
        $(document).on('change', 'input#single_dpp, input#single_dsp, input.variable_dpp, input.variable_dsp', function() {
            var $row = $(this).closest('tr');
            var dpp = __read_number($row.find('input.dpp, input.variable_dpp').first());
            var dsp = __read_number($row.find('input.dsp, input.variable_dsp').first());
            __write_number($row.find('input.dpp_inc_tax, input.variable_dpp_inc_tax').first(), dpp);
            __write_number($row.find('input#single_dsp_inc_tax, input.variable_dsp_inc_tax').first(), dsp);
        });
    }

    $(document).on('ifChecked', 'input#enable_stock', function() {
        $('div#alert_quantity_div').show();
        if ($('select#type').val() === 'single' || !$('select#type').length) {
            $('#vspos_create_opening_stock_box').show();
            $('div#quick_product_opening_stock_div').show();
        }

        //Enable expiry selection
        if ($('#expiry_period_type').length) {
            $('#expiry_period_type').removeAttr('disabled');
        }

        if ($('#opening_stock_button').length) {
            $('#opening_stock_button').removeAttr('disabled');
        }
    });
    $(document).on('ifUnchecked', 'input#enable_stock', function() {
        $('div#alert_quantity_div').hide();
        $('div#quick_product_opening_stock_div').hide();
        $('#vspos_create_opening_stock_box').hide();
        $('input#alert_quantity').val(0);

        //Disable expiry selection
        if ($('#expiry_period_type').length) {
            $('#expiry_period_type')
                .val('')
                .change();
            $('#expiry_period_type').attr('disabled', true);
        }
        if ($('#opening_stock_button').length) {
            $('#opening_stock_button').attr('disabled', true);
        }
    });

    // Show opening stock only for single + manage stock
    function vsposToggleCreateOpeningStock() {
        var show = (!$('select#type').length || $('select#type').val() === 'single') && $('input#enable_stock').is(':checked');
        if (show) {
            $('div#quick_product_opening_stock_div').show();
            $('#vspos_create_opening_stock_box').show();
        } else {
            $('div#quick_product_opening_stock_div').hide();
            $('#vspos_create_opening_stock_box').hide();
        }
    }
    $(document).on('change', 'select#type', vsposToggleCreateOpeningStock);
    vsposToggleCreateOpeningStock();

    if ($('.os_exp_date').length) {
        $('.os_exp_date').datepicker({
            autoclose: true,
            format: datepicker_date_format,
        });
    }

    //Start For product type single

    //If purchase price exc tax is changed
    $(document).on('change', 'input#single_dpp', function(e) {
        var cost = $(this).val();
        $('input.opening_stock_cost').each(function() {
            if (!$(this).val()) {
                $(this).val(cost);
            }
        });

        var purchase_exc_tax = __read_number($('input#single_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
        __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax);

        var profit_percent = __read_number($('#profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    //If tax rate is changed
    $(document).on('change', 'select#tax', function() {
        if ($('select#type').val() == 'single') {
            var purchase_exc_tax = __read_number($('input#single_dpp'));
            purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

            var tax_rate = $('select#tax')
                .find(':selected')
                .data('rate');
            tax_rate = tax_rate == undefined ? 0 : tax_rate;

            var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
            __write_number($('input#single_dpp_inc_tax'), purchase_inc_tax);

            var selling_price = __read_number($('input#single_dsp'));
            var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
            __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
        }
    });

    //If purchase price inc tax is changed
    $(document).on('change', 'input#single_dpp_inc_tax', function(e) {
        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
        __write_number($('input#single_dpp'), purchase_exc_tax);
        $('input#single_dpp').change();

        var profit_percent = __read_number($('#profit_percent'));
        profit_percent = profit_percent == undefined ? 0 : profit_percent;
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#profit_percent', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __read_number($('input#single_dpp_inc_tax'));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var purchase_exc_tax = __read_number($('input#single_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var profit_percent = __read_number($('input#profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number($('input#single_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#single_dsp', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var selling_price = __read_number($('input#single_dsp'));
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var profit_percent = __read_number($('input#profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number($('input#profit_percent'), profit_percent);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number($('input#single_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input#single_dsp_inc_tax', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;
        var selling_price_inc_tax = __read_number($('input#single_dsp_inc_tax'));

        var selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number($('input#single_dsp'), selling_price);
        var purchase_exc_tax = __read_number($('input#single_dpp'));
        var profit_percent = __read_number($('input#profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number($('input#profit_percent'), profit_percent);
    });

    if ($('#product_add_form').length) {
        $('form#product_add_form').validate({
            rules: {
                sku: {
                    remote: {
                        url: '/products/check_product_sku',
                        type: 'post',
                        data: {
                            sku: function() {
                                return $('#sku').val();
                            },
                            product_id: function() {
                                if ($('#product_id').length > 0) {
                                    return $('#product_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
                name: {
                    remote: {
                        url: '/products/check_product_name',
                        type: 'post',
                        data: {
                            name: function() {
                                return $('#name').val();
                            },
                            product_id: function() {
                                if ($('#product_id').length > 0) {
                                    return $('#product_id').val();
                                } else {
                                    return '';
                                }
                            },
                        },
                    },
                },
                expiry_period: {
                    required: {
                        depends: function(element) {
                            return (
                                $('#expiry_period_type')
                                    .val()
                                    .trim() != ''
                            );
                        },
                    },
                },
            },
            messages: {
                sku: {
                    remote: LANG.sku_already_exists,
                },
                name: {
                    remote: LANG.name_already_exists,
                },
            },
        });
    }

    $(document).on('click', '.submit_product_form', function(e) {
        e.preventDefault();

        var is_valid_product_form = true;

        var variation_skus = [];

        var submit_type  = $(this).attr('value');

        $('#product_form_part').find('.input_sub_sku').each( function(){
            var element = $(this);
            var row_variation_id = '';
            if ($(this).closest('tr').find('.row_variation_id')) {
                row_variation_id = $(this).closest('tr').find('.row_variation_id').val();
            }

            variation_skus.push({sku: element.val(), variation_id: row_variation_id});
            
        });

        if (variation_skus.length > 0) {
            $.ajax({
                method: 'post',
                url: '/products/validate_variation_skus',
                data: { skus: variation_skus},
                success: function(result) {
                    if (result.success == true) {
                        $('#submit_type').val(submit_type);
                        if ($('form#product_add_form').valid()) {
                            $('form#product_add_form').submit();
                        }
                    } else {
                        toastr.error(__translate('skus_already_exists', {sku: result.sku}));
                        return false;
                    }
                },
            });
        } else {
            $('#submit_type').val(submit_type);
            if ($('form#product_add_form').valid()) {
                $('form#product_add_form').submit();
            }
        }
        
    });
    //End for product type single

    //Start for product type Variable
    //If purchase price exc tax is changed
    $(document).on('change', 'input.variable_dpp', function(e) {
        var tr_obj = $(this).closest('tr');

        var purchase_exc_tax = __read_number($(this));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dpp_inc_tax'), purchase_inc_tax);

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    //If purchase price inc tax is changed
    $(document).on('change', 'input.variable_dpp_inc_tax', function(e) {
        var tr_obj = $(this).closest('tr');

        var purchase_inc_tax = __read_number($(this));
        purchase_inc_tax = purchase_inc_tax == undefined ? 0 : purchase_inc_tax;

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var purchase_exc_tax = __get_principle(purchase_inc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dpp'), purchase_exc_tax);

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input.variable_profit_percent', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var tr_obj = $(this).closest('tr');
        var profit_percent = __read_number($(this));

        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));
        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

        var selling_price = __add_percent(purchase_exc_tax, profit_percent);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });

    $(document).on('change', 'input.variable_dsp', function(e) {
        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var tr_obj = $(this).closest('tr');
        var selling_price = __read_number($(this));
        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));

        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));

        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number(tr_obj.find('input.variable_profit_percent'), profit_percent);

        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp_inc_tax'), selling_price_inc_tax);
    });
    $(document).on('change', 'input.variable_dsp_inc_tax', function(e) {
        var tr_obj = $(this).closest('tr');
        var selling_price_inc_tax = __read_number($(this));

        var tax_rate = $('select#tax')
            .find(':selected')
            .data('rate');
        tax_rate = tax_rate == undefined ? 0 : tax_rate;

        var selling_price = __get_principle(selling_price_inc_tax, tax_rate);
        __write_number(tr_obj.find('input.variable_dsp'), selling_price);

        var purchase_exc_tax = __read_number(tr_obj.find('input.variable_dpp'));
        var profit_percent = __read_number(tr_obj.find('input.variable_profit_percent'));
        //if purchase price not set
        if (purchase_exc_tax == 0) {
            profit_percent = 0;
        } else {
            profit_percent = __get_rate(purchase_exc_tax, selling_price);
        }

        __write_number(tr_obj.find('input.variable_profit_percent'), profit_percent);
    });

    $(document).on('click', '.add_variation_value_row', function() {
        var variation_row_index = $(this)
            .closest('.variation_row')
            .find('.row_index')
            .val();
        var variation_value_row_index = $(this)
            .closest('table')
            .find('tr:last .variation_row_index')
            .val();

        if (
            $(this)
                .closest('.variation_row')
                .find('.row_edit').length >= 1
        ) {
            var row_type = 'edit';
        } else {
            var row_type = 'add';
        }

        var table = $(this).closest('table');

        $.ajax({
            method: 'GET',
            url: '/products/get_variation_value_row',
            data: {
                variation_row_index: variation_row_index,
                value_index: variation_value_row_index,
                row_type: row_type,
            },
            dataType: 'html',
            success: function(result) {
                if (result) {
                    table.append(result);
                    toggle_dsp_input();
                }
            },
        });
    });
    $(document).on('change', '.variation_template_values', function() {
        var tr_obj = $(this).closest('tr');
        var val = $(this).val();
        tr_obj.find('.variation_value_row').each(function(){
            if(val.includes($(this).attr('data-variation_value_id'))) {
                $(this).removeClass('hide');
                $(this).find('.is_variation_value_hidden').val(0);
            } else {
                $(this).addClass('hide');
                $(this).find('.is_variation_value_hidden').val(1);
            }
        })
    });
    $(document).on('change', '.variation_template', function() {
        tr_obj = $(this).closest('tr');

        if ($(this).val() !== '') {
            tr_obj.find('input.variation_name').val(
                $(this)
                    .find('option:selected')
                    .text()
            );

            var template_id = $(this).val();
            var row_index = $(this)
                .closest('tr')
                .find('.row_index')
                .val();
            $.ajax({
                method: 'POST',
                url: '/products/get_variation_template',
                dataType: 'json',
                data: { template_id: template_id, row_index: row_index },
                success: function(result) {
                    if (result) {
                        if(result.values.length > 0) {
                            tr_obj.find('.variation_template_values').select2();
                            tr_obj.find('.variation_template_values').empty();
                            tr_obj.find('.variation_template_values').select2({data: result.values, closeOnSelect: false});
                            tr_obj.find('.variation_template_values_div').removeClass('hide');
                            tr_obj.find('.variation_template_values').select2('open');
                        } else {
                            tr_obj.find('.variation_template_values_div').addClass('hide');
                        }
                        tr_obj
                            .find('table.variation_value_table')
                            .find('tbody')
                            .html(result.html);

                        toggle_dsp_input();
                    }
                },
            });
        }
    });

    $(document).on('click','.delete_complete_row', function(){
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                .closest('.variation_row')
                .remove();
            }
        });
    });

    $(document).on('click', '.remove_variation_value_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                var count = $(this)
                    .closest('table')
                    .find('.remove_variation_value_row').length;
                if (count === 1) {
                    $(this)
                        .closest('.variation_row')
                        .remove();
                } else {
                    $(this)
                        .closest('tr')
                        .remove();
                }
            }
        });
    });

    //If tax rate is changed
    $(document).on('change', 'select#tax', function() {
        if ($('select#type').val() == 'variable') {
            var tax_rate = $('select#tax')
                .find(':selected')
                .data('rate');
            tax_rate = tax_rate == undefined ? 0 : tax_rate;

            $('table.variation_value_table > tbody').each(function() {
                $(this)
                    .find('tr')
                    .each(function() {
                        var purchase_exc_tax = __read_number($(this).find('input.variable_dpp'));
                        purchase_exc_tax = purchase_exc_tax == undefined ? 0 : purchase_exc_tax;

                        var purchase_inc_tax = __add_percent(purchase_exc_tax, tax_rate);
                        __write_number(
                            $(this).find('input.variable_dpp_inc_tax'),
                            purchase_inc_tax
                        );

                        var selling_price = __read_number($(this).find('input.variable_dsp'));
                        var selling_price_inc_tax = __add_percent(selling_price, tax_rate);
                        __write_number(
                            $(this).find('input.variable_dsp_inc_tax'),
                            selling_price_inc_tax
                        );
                    });
            });
        }
    });
    //End for product type Variable
    $(document).on('change', '#tax_type', function(e) {
        toggle_dsp_input();
    });
    toggle_dsp_input();

    $(document).on('change', '#expiry_period_type', function(e) {
        if ($(this).val()) {
            $('input#expiry_period').prop('disabled', false);
        } else {
            $('input#expiry_period').val('');
            $('input#expiry_period').prop('disabled', true);
        }
    });

    $(document).on('click', 'a.view-product', function(e) {
        e.preventDefault();
        $.ajax({
            url: $(this).attr('href'),
            dataType: 'html',
            success: function(result) {
                $('#view_product_modal')
                    .html(result)
                    .modal('show');
                __currency_convert_recursively($('#view_product_modal'));
            },
        });
    });
    var img_fileinput_setting = {
        showUpload: false,
        showPreview: true,
        browseLabel: LANG.file_browse_label,
        removeLabel: LANG.remove,
        previewSettings: {
            image: { width: 'auto', height: 'auto', 'max-width': '100%', 'max-height': '100%' },
        },
    };
    $('#upload_image').fileinput(img_fileinput_setting);

    if ($('textarea#product_description').length > 0) {
        tinymce.init({
            selector: 'textarea#product_description',
            height:250,
            skin: 'oxide-dark',
            content_css: 'dark'
        });
    }
});

function toggle_dsp_input() {
    // Simple / tax-disabled forms keep MRP in #single_dsp and use a hidden
    // #single_dsp_inc_tax. Never hide the visible MRP field in that case.
    var $dspInc = $('#single_dsp_inc_tax');
    if ($('body').hasClass('tax-system-disabled') || ($dspInc.length && $dspInc.attr('type') === 'hidden')) {
        $('#single_dsp').removeClass('hide');
        return;
    }

    var tax_type = $('#tax_type').val();
    if (tax_type == 'inclusive') {
        $('.dsp_label').each(function() {
            $(this).text(LANG.inc_tax);
        });
        $('#single_dsp').addClass('hide');
        $('#single_dsp_inc_tax').removeClass('hide');

        $('.add-product-price-table')
            .find('.variable_dsp_inc_tax')
            .each(function() {
                $(this).removeClass('hide');
            });
        $('.add-product-price-table')
            .find('.variable_dsp')
            .each(function() {
                $(this).addClass('hide');
            });
    } else if (tax_type == 'exclusive') {
        $('.dsp_label').each(function() {
            $(this).text(LANG.exc_tax);
        });
        $('#single_dsp').removeClass('hide');
        $('#single_dsp_inc_tax').addClass('hide');

        $('.add-product-price-table')
            .find('.variable_dsp_inc_tax')
            .each(function() {
                $(this).addClass('hide');
            });
        $('.add-product-price-table')
            .find('.variable_dsp')
            .each(function() {
                $(this).removeClass('hide');
            });
    }
}

function get_product_details(rowData) {
    var div = $('<div/>')
        .addClass('loading')
        .text('Loading...');

    $.ajax({
        url: '/products/' + rowData.id,
        dataType: 'html',
        success: function(data) {
            div.html(data).removeClass('loading');
        },
    });

    return div;
}

//Quick add unit
$(document).on('submit', 'form#quick_add_unit_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                $('select#unit_id, #quick_add_product_form select[name="unit_id"]').each(function() {
                    $(this).append(new Option(result.data.short_name, result.data.id, true, true)).trigger('change');
                });
                $('div.view_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
                form.find('button[type="submit"]').removeAttr('disabled');
            }
        },
    });
});

//Quick add brand
$(document).on('submit', 'form#quick_add_brand_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                $('select#brand_id, #quick_add_product_form select[name="brand_id"]').each(function() {
                    $(this).append(new Option(result.data.name, result.data.id, true, true)).trigger('change');
                });
                $('div.view_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
                form.find('button[type="submit"]').removeAttr('disabled');
            }
        },
    });
});

// Quick add category / subcategory (from product forms)
$(document).on('submit', 'form#quick_add_category_form', function(e) {
    e.preventDefault();
    var form = $(this);
    var data = form.serialize();
    var target = form.find('input[name="quick_add_target"]').val() || 'category';

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button(form.find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var row = result.data;
                var isSub = target === 'sub_category' || (row.parent_id && parseInt(row.parent_id, 10) > 0);

                if (isSub) {
                    var parentId = row.parent_id;
                    $('select#category_id, #quick_add_product_form select[name="category_id"]').each(function() {
                        if ($(this).val() != parentId) {
                            $(this).val(parentId).trigger('change');
                        }
                    });
                    $.ajax({
                        method: 'POST',
                        url: '/products/get_sub_categories',
                        dataType: 'html',
                        data: { cat_id: parentId },
                        success: function(html) {
                            $('select#sub_category_id, #quick_add_product_form select[name="sub_category_id"]').each(function() {
                                $(this).html(html);
                                if ($(this).find('option[value="' + row.id + '"]').length === 0) {
                                    $(this).append(new Option(row.name, row.id, true, true));
                                } else {
                                    $(this).val(row.id);
                                }
                                $(this).trigger('change');
                            });
                        },
                    });
                } else {
                    $('select#category_id, #quick_add_product_form select[name="category_id"]').each(function() {
                        $(this).append(new Option(row.name, row.id, true, true)).trigger('change');
                    });
                }

                $('div.view_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
                form.find('button[type="submit"]').removeAttr('disabled');
            }
        },
    });
});

// Open subcategory popup (requires parent category)
$(document).on('click', '.quick_add_sub_category_btn', function(e) {
    e.preventDefault();
    e.stopPropagation();

    var $btn = $(this);
    if ($btn.is(':disabled')) {
        return;
    }

    var $form = $btn.closest('form');
    var parentId = $form.find('select[name="category_id"]').val();
    if (!parentId) {
        parentId = $('select[name="category_id"]').filter(':visible').first().val();
    }
    if (!parentId) {
        toastr.warning((typeof LANG !== 'undefined' && LANG.please_select_category) ? LANG.please_select_category : 'Please select a category first');
        return;
    }

    var base = $btn.attr('data-href-base') || $btn.data('href-base');
    var url = base + (base.indexOf('?') >= 0 ? '&' : '?') + 'parent_id=' + encodeURIComponent(parentId);
    var container = $btn.data('container') || '.view_modal';

    $.ajax({
        url: url,
        dataType: 'html',
        success: function(result) {
            $(container).html(result).modal('show');
        },
    });
});

// Keep nested create popup above the quick-add product modal
$(document).on('shown.bs.modal', 'div.view_modal', function() {
    var zIndex = 1060 + 10 * $('.modal:visible').length;
    $(this).css('z-index', zIndex);
    setTimeout(function() {
        $('.modal-backdrop')
            .not('.modal-stack')
            .last()
            .css('z-index', zIndex - 1)
            .addClass('modal-stack');
    }, 0);
});

$(document).on('click', 'button.apply-all', function(){
    var val = $(this).closest('.input-group').find('input').val();
    var target_class = $(this).data('target-class');
    $(this).closest('tbody').find('tr').each( function(){
        element =  $(this).find(target_class);
        element.val(val);
        element.change();
    });
});

// Variation image upload preview handler
$(document).on('change', '.variation-file-input', function() {
    var $input = $(this);
    var $container = $input.closest('.variation-image-upload');
    var $preview = $container.find('.variation-image-preview');
    var $badge = $container.find('.image-count-badge');
    var $btn = $container.find('.variation-upload-btn');
    var files = this.files;
    
    // Clear previous previews
    $preview.empty();
    
    if (files && files.length > 0) {
        // Show badge with count
        $badge.text(files.length).show();
        $btn.removeClass('tw-dw-btn-primary').addClass('tw-dw-btn-success');
        
        // Show preview container
        $preview.show();
        
        // Create preview for each file (max 4 shown)
        var maxPreview = Math.min(files.length, 4);
        for (var i = 0; i < maxPreview; i++) {
            var file = files[i];
            if (file.type.match('image.*')) {
                var reader = new FileReader();
                reader.onload = (function(file) {
                    return function(e) {
                        var $thumb = $('<div class="preview-thumb" style="display: inline-block; position: relative; margin: 2px;">' +
                            '<img src="' + e.target.result + '" style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #ddd;">' +
                            '</div>');
                        $preview.append($thumb);
                    };
                })(file);
                reader.readAsDataURL(file);
            }
        }
        
        // Show +N if more than 4 files
        if (files.length > 4) {
            $preview.append('<span style="display: inline-block; padding: 10px; font-size: 12px; color: #666;">+' + (files.length - 4) + ' more</span>');
        }
    } else {
        // No files selected - reset
        $badge.hide();
        $btn.removeClass('tw-dw-btn-success').addClass('tw-dw-btn-primary');
        $preview.hide();
    }
});