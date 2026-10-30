var global_brand_id = null;
var global_p_category_id = null;
var global_is_clear_local_storage = false;
var pos_display_block_save_until = 0;
var vspos_display_channel = null;
var pos_last_display_customer_ts = 0;
var pos_last_applied_customer_ts = 0;
var pos_customer_from_display_pending = null;
window._posDisplayCustomerQueue = window._posDisplayCustomerQueue || [];

try {
    if (typeof BroadcastChannel !== 'undefined') {
        vspos_display_channel = new BroadcastChannel('vspos_customer_display');
    }
} catch (e) {}

/**
 * Customer display asked for cart — queue until POS form is ready.
 */
function pos_handle_display_cart_request() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        window._posPendingCartRequest = Date.now();
        return;
    }

    window._posPendingCartRequest = null;
    saveFormDataToLocalStorage(true);
}

function pos_flush_pending_display_cart_request() {
    if (!window._posPendingCartRequest) {
        return;
    }

    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    window._posPendingCartRequest = null;
    saveFormDataToLocalStorage(true);
}

function pos_handle_display_ping() {
    pos_handle_display_cart_request();
    if ($('select#customer_id').length) {
        pos_publish_current_customer_to_display();
    }
}

if (!window._posDisplaySyncEarlyBound) {
    window._posDisplaySyncEarlyBound = true;

    window.addEventListener('storage', function(e) {
        if (e.key === 'pos_display_ping') {
            pos_handle_display_ping();
        }
        if (e.key === 'pos_display_customer_select' && e.newValue) {
            try {
                var customer = JSON.parse(e.newValue);
                pos_queue_display_customer(customer);
                if ($('select#customer_id').hasClass('select2-hidden-accessible')) {
                    pos_select_customer_from_display(customer);
                }
            } catch (err) {}
        }
    });

    if (vspos_display_channel) {
        vspos_display_channel.addEventListener('message', function(ev) {
            if (!ev.data) {
                return;
            }
            if (ev.data.type === 'cart_request') {
                pos_handle_display_cart_request();
            }
            if (ev.data.type === 'customer_select' && ev.data.customer) {
                pos_queue_display_customer(ev.data.customer);
                if ($('select#customer_id').hasClass('select2-hidden-accessible')) {
                    pos_select_customer_from_display(ev.data.customer);
                }
            }
        });
    }
}

function pos_broadcast_display_reset(sessionId) {
    var ts = Date.now();
    try {
        if (vspos_display_channel) {
            vspos_display_channel.postMessage({
                type: 'cache_clear',
                session: sessionId,
                ts: ts,
            });
        }
    } catch (e) {}
}

/**
 * Wipe all customer-display sync keys from localStorage (POS + display share these).
 */
function pos_clear_display_local_storage(sessionId) {
    var ts = Date.now();
    var keys = [
        'pos_form_data_array',
        'pos_display_customer_select',
        'pos_display_sale_completed',
        'pos_display_cart_ts',
        'pos_display_ping',
    ];

    keys.forEach(function(key) {
        try {
            localStorage.removeItem(key);
        } catch (e) {}
    });

    try {
        localStorage.setItem('pos_form_data_array', JSON.stringify([]));
        localStorage.setItem('pos_display_cart_ts', String(ts));
        if (sessionId) {
            localStorage.setItem('pos_display_session', String(sessionId));
            localStorage.setItem('pos_display_refresh', String(sessionId));
        }
    } catch (e) {}

    return ts;
}

function pos_publish_customer_to_display(customer, options) {
    if (!customer || customer.id == null || customer.id === '') {
        return;
    }

    options = options || {};
    var defaultId = String($('#default_customer_id').val() || '');
    if (defaultId && String(customer.id) === defaultId) {
        try {
            localStorage.removeItem('pos_display_customer_select');
            localStorage.removeItem('pos_display_customer_welcome');
        } catch (e) {}
        try {
            if (vspos_display_channel) {
                vspos_display_channel.postMessage({ type: 'customer_clear' });
            }
        } catch (e) {}
        return;
    }

    var customerText = (customer.text || customer.name || '').trim();
    var payload = {
        id: customer.id,
        text: customerText,
        name: customer.name || customerText,
        prefix: customer.prefix || '',
        first_name: customer.first_name || '',
        mobile: customer.mobile || '',
        balance: customer.balance,
        shipping_address: customer.shipping_address,
        pay_term_number: customer.pay_term_number,
        pay_term_type: customer.pay_term_type,
        price_calculation_type: customer.price_calculation_type,
        selling_price_group_id: customer.selling_price_group_id,
        ts: Date.now(),
        show_welcome: !!options.show_welcome,
        from_display: !!customer.from_display,
    };

    try {
        localStorage.setItem('pos_display_customer_select', JSON.stringify(payload));
    } catch (e) {}

    try {
        if (vspos_display_channel) {
            vspos_display_channel.postMessage({ type: 'customer_select', customer: payload });
        }
    } catch (e) {}

    // Dedicated welcome signal so customer display always gets the popup
    if (options.show_welcome && !customer.from_display) {
        pos_signal_customer_welcome(payload);
    }
}

/**
 * Tell customer display to show the welcome popup for this customer.
 */
function pos_signal_customer_welcome(customer) {
    if (!customer || customer.id == null || customer.id === '') {
        return;
    }

    var welcomePayload = {
        id: customer.id,
        text: customer.text || customer.name || '',
        name: customer.name || customer.text || '',
        prefix: customer.prefix || '',
        first_name: customer.first_name || '',
        ts: Date.now(),
    };

    try {
        localStorage.setItem('pos_display_customer_welcome', JSON.stringify(welcomePayload));
    } catch (e) {}

    try {
        if (vspos_display_channel) {
            vspos_display_channel.postMessage({ type: 'customer_welcome', customer: welcomePayload });
        }
    } catch (e) {}

    // Retry a few times in case display tab was busy
    setTimeout(function() {
        try {
            if (vspos_display_channel) {
                vspos_display_channel.postMessage({ type: 'customer_welcome', customer: welcomePayload });
            }
        } catch (e) {}
    }, 300);
    setTimeout(function() {
        try {
            if (vspos_display_channel) {
                vspos_display_channel.postMessage({ type: 'customer_welcome', customer: welcomePayload });
            }
        } catch (e) {}
    }, 900);
}

/**
 * Publish the customer currently selected on POS to the customer display.
 */
function pos_publish_current_customer_to_display() {
    var $sel = $('select#customer_id');
    if (!$sel.length) {
        return;
    }

    var cid = String($sel.val() || '');
    var defaultId = String($('#default_customer_id').val() || '');
    if (!cid || (defaultId && cid === defaultId)) {
        try {
            localStorage.removeItem('pos_display_customer_select');
        } catch (e) {}
        return;
    }

    var $opt = $sel.find('option:selected');
    // Keep any pending welcome flag so a display ping does not wipe it
    var keepWelcome = false;
    try {
        var welcomeRaw = localStorage.getItem('pos_display_customer_welcome');
        if (welcomeRaw) {
            var welcomeData = JSON.parse(welcomeRaw);
            keepWelcome = welcomeData && String(welcomeData.id) === cid;
        }
    } catch (e) {}

    pos_publish_customer_to_display({
        id: cid,
        text: ($opt.text() || '').trim(),
        name: ($opt.text() || '').trim(),
    }, { show_welcome: keepWelcome });
}

/**
 * Tell the customer display tab to reload (localStorage + BroadcastChannel + named window).
 */
function pos_signal_customer_display_reload() {
    var ts = String(Date.now());

    try {
        localStorage.setItem('pos_display_force_reload', ts);
    } catch (e) {}

    try {
        if (vspos_display_channel) {
            vspos_display_channel.postMessage({ type: 'display_reload', ts: ts });
        }
    } catch (e) {}

    pos_reload_customer_display_window();
}

/**
 * Ensure this POS tab is never named "customer_display".
 * Browsers keep window.name across navigations, so opening POS in the CD tab
 * would make window.open(cdUrl, 'customer_display') navigate POS itself away.
 */
function pos_ensure_pos_window_name() {
    try {
        if (window.name === 'customer_display') {
            window.name = 'vspos_pos';
        }
    } catch (e) {}
}

/**
 * Reload customer display tab when POS refreshes (same tab name: customer_display).
 * Never navigates the current POS window.
 */
function pos_reload_customer_display_window() {
    try {
        pos_ensure_pos_window_name();

        var cdOpen = false;
        try {
            cdOpen = sessionStorage.getItem('vspos_cd_open') === '1'
                || localStorage.getItem('vspos_cd_open') === '1';
        } catch (e) {}

        if (!cdOpen) {
            return;
        }

        var link = document.getElementById('customer_display_screen');
        if (!link || !link.href) {
            return;
        }

        var existing = window.vsposCustomerDisplay || null;
        if (existing === window) {
            existing = null;
            window.vsposCustomerDisplay = null;
        }

        if (!existing || existing.closed) {
            try {
                existing = window.open('', 'customer_display');
            } catch (e) {
                existing = null;
            }
        }

        // window.open('', 'customer_display') returns THIS window if it still
        // has that name — never treat self as the customer display.
        if (!existing || existing.closed || existing === window) {
            return;
        }

        try {
            var path = existing.location && existing.location.pathname ? existing.location.pathname : '';
            if (path.indexOf('customer-display') !== -1) {
                existing.location.reload();
                window.vsposCustomerDisplay = existing;
                return;
            }
        } catch (e) {}

        try {
            existing.location.href = link.href;
            window.vsposCustomerDisplay = existing;
        } catch (e) {
            try {
                var reopened = window.open(link.href, 'customer_display');
                if (reopened && !reopened.closed && reopened !== window) {
                    window.vsposCustomerDisplay = reopened;
                }
            } catch (err) {}
        }
    } catch (e) {}
}

function pos_play_success_sound() {
    if (window.QPOS_MUTE_SOUND) {
        return;
    }
    var audio = document.getElementById('success-audio');
    if (audio) {
        try { audio.currentTime = 0; audio.play(); } catch (e) {}
    }
}

function pos_sync_empty_state() {
    var hasRows = $('#pos_table tbody .product_row').length > 0;
    $('#pos_table').toggleClass('pos-has-rows', hasRows);
    $('#add_pos_sell_form, #edit_pos_sell_form').toggleClass('pos-has-rows', hasRows);
}
$(document).ready(function() {
    pos_ensure_pos_window_name();
    pos_reset_customer_display_storage();
    pos_sync_empty_state();
    // POP: never enforce sell-side stock max on purchase qty
    if ($('#is_pop_mode').length && typeof jQuery !== 'undefined' && jQuery.validator) {
        jQuery.validator.addMethod('max-value', function () {
            return true;
        });
    }
    if (typeof vspos_clear_pop_qty_stock_rules === 'function') {
        vspos_clear_pop_qty_stock_rules();
    }
    customer_set = false;
    vspos_parse_exchange_url_params();
    //Prevent enter key from submitting the form (must include keydown — browsers submit on keydown)
    $('form#add_pos_sell_form, form#edit_pos_sell_form').on('keydown keypress keyup', function(e) {
        var keyCode = e.keyCode || e.which;
        if (keyCode !== 13) {
            return;
        }
        if (e.target.tagName === 'TEXTAREA') {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        // Quick-entry Enter flow is handled by pos-quick-entry.js (capture phase)
        return false;
    });

    // Classic quick-entry bind only when dedicated script is absent
    if (!window.VSPOS_QUICK_ENTRY && typeof vspos_bind_quick_entry === 'function') {
        vspos_bind_quick_entry();
    }

    //For edit pos form
    if ($('form#edit_pos_sell_form').length > 0) {
        pos_total_row();
        pos_form_obj = $('form#edit_pos_sell_form');
    } else {
        pos_form_obj = $('form#add_pos_sell_form');
    }

    /* Initialise price-info spans on all existing rows (edit/draft reload) */
    $('table#pos_table tbody tr.product_row').each(function() {
        pos_each_row($(this));
    });
    if ($('form#edit_pos_sell_form').length > 0 || $('form#add_pos_sell_form').length > 0) {
        initialize_printer();
    }

    $('select#select_location_id').change(function() {
        reset_pos_form();

        var default_price_group = $(this).find(':selected').data('default_price_group')
        if (default_price_group) {
            if($("#price_group option[value='" + default_price_group + "']").length > 0) {
                $("#price_group").val(default_price_group);
                $("#price_group").change();
            }
        }

        //Set default invoice scheme for location
        if ($('#invoice_scheme_id').length) {
            if($('input[name="is_direct_sale"]').length > 0){
                //default scheme for sale screen
                var invoice_scheme_id = $(this).find(':selected').data('default_sale_invoice_scheme_id');
            } else {
                var invoice_scheme_id =  $(this).find(':selected').data('default_invoice_scheme_id');
            }
            
            $("#invoice_scheme_id").val(invoice_scheme_id).change();
        }

        //Set default invoice layout for location
        if ($('#invoice_layout_id').length) {
            let invoice_layout_id = $(this).find(':selected').data('default_invoice_layout_id');
            $("#invoice_layout_id").val(invoice_layout_id).change();
        }
        
        //Set default price group
        if ($('#default_price_group').length) {
            var dpg = default_price_group ?
            default_price_group : 0;
            $('#default_price_group').val(dpg);
        }

        set_payment_type_dropdown();

        if ($('#types_of_service_id').length && $('#types_of_service_id').val()) {
            $('#types_of_service_id').change();
        }
    });

    //get customer (POP: customers + suppliers)
    $('select#customer_id').select2({
        dropdownCssClass: 'vspos-select2-dropdown',
        ajax: {
            url: $('#is_pop_mode').length ? '/purchase-pos/contacts' : '/contacts/customers',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term, // search term
                    page: params.page,
                };
            },
            processResults: function(data) {
                return {
                    results: data,
                };
            },
        },
        templateResult: function (data) { 
            var template = '';
            if (data.supplier_business_name) {
                template += data.supplier_business_name + "<br>";
            }
            template += data.text + "<br>" + LANG.mobile + ": " + data.mobile;

            if (typeof(data.total_rp) != "undefined") {
                var rp = data.total_rp ? data.total_rp : 0;
                template += "<br><i class='fa fa-gift text-success'></i> " + rp;
            }

            return  template;
        },
        minimumInputLength: 1,
        language: {
            inputTooShort: function (args) {
                return LANG.please_enter + args.minimum + LANG.or_more_characters;
            },
            noResults: function() {
                var name = $('#customer_id')
                    .data('select2')
                    .dropdown.$search.val();
                return (
                    '<button type="button" data-name="' +
                    name +
                    '" class="btn btn-link add_new_customer"><i class="fa fa-plus-circle fa-lg" aria-hidden="true"></i>&nbsp; ' +
                    __translate('add_name_as_new_customer', { name: name }) +
                    '</button>'
                );
            },
        },
        escapeMarkup: function(markup) {
            return markup;
        },
    });
    // After customer select2 is ready, apply pending exchange (return → take other items)
    setTimeout(function() {
        vspos_apply_exchange_to_pos();
    }, 400);
    $(document).on('shown.bs.modal', '#modal_payment', function() {
        vspos_apply_exchange_credit_note();
    });
    $('#customer_id').on('select2:select', function(e) {
        var data = e.params.data;
        if (data && data.text) {
            pos_refresh_customer_select2_ui($(this), data.text);
        }
        if (data.pay_term_number) {
            $('input#pay_term_number').val(data.pay_term_number);
        } else {
            $('input#pay_term_number').val('');
        }

        if (data.pay_term_type) {
            $('#add_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
            $('#edit_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
        } else {
            $('#add_sell_form select[name="pay_term_type"]').val('');
            $('#edit_sell_form select[name="pay_term_type"]').val('');
        }
        
        update_shipping_address(data);
        $('#advance_balance_text').text(__currency_trans_from_en(data.balance), true);
        $('#advance_balance').val(data.balance);
        if (typeof updatePosCustomerBalanceBadges === 'function') {
            updatePosCustomerBalanceBadges({
                due: '',
                advance: data.balance
            });
        }

        if (data.price_calculation_type == 'selling_price_group') {
            $('#price_group').val(data.selling_price_group_id);
            $('#price_group').change();
        }
        //  else {
        //     $('#price_group').val(0);
        //     $('#price_group').change();
        // }
        get_contact_due(data.id);
        saveFormDataToLocalStorage(true);
        pos_publish_customer_to_display(data, { show_welcome: true });

    });

    if (!pos_try_apply_display_customer_on_boot()) {
        set_default_customer();
    } else {
        pos_flush_display_customer_queue();
    }

    pos_flush_pending_display_cart_request();

    pos_init_customer_display_sync();

    // Quick entry uses its own SKU lookup — skip autocomplete so Enter never clears/hides the code
    if (document.getElementById('vspos_quick_entry')) {
        try {
            if ($('#search_product').data('ui-autocomplete')) {
                $('#search_product').autocomplete('destroy');
            }
        } catch (err) { /* ignore */ }
        // Enter flow is handled by pos-quick-entry.js (do not bind twice)
    } else if ($('#search_product').length) {
        //Add Product (classic autocomplete)
        $('#search_product')
            .autocomplete({
                delay: 1000,
                source: function(request, response) {
                    var price_group = '';
                    var search_fields = [];
                    $('.search_fields:checked').each(function(i){
                      search_fields[i] = $(this).val();
                    });

                    if ($('#price_group').length > 0) {
                        price_group = $('#price_group').val();
                    }

                    // Check if search term is a weighing scale barcode (7-13 digits, numeric)
                    var search_term = request.term.trim();
                    var enable_weighing_scale_scan = $('#enable_weighing_scale_scan').val() === '1';
                    
                    // Accept 7-13 digit numeric barcodes as potential weighing scale barcodes
                    if (enable_weighing_scale_scan && /^\d{7,13}$/.test(search_term)) {
                        // Try to process as weighing scale barcode
                        pos_product_row(null, null, search_term);
                        $('#search_product').val('');
                        // Mark that we processed a weighing scale barcode to suppress error
                        $('#search_product').data('weighing_scale_processed', true);
                        return response([]);
                    }
                    
                    //If default price group present
                    if ($('#default_price_group').length > 0 && price_group === '') {
                        price_group = $('#default_price_group').val();
                    }

                    //If types of service selected give more priority
                    if ($('#types_of_service_price_group').length > 0 && 
                        $('#types_of_service_price_group').val()) {
                        price_group = $('#types_of_service_price_group').val();
                    }
                    
                    var customer_id = $('select#customer_id').val();
                    var is_direct_sell = false;
                    if ($('input[name="is_direct_sale"]').length > 0 &&
                        $('input[name="is_direct_sale"]').val() == 1) {
                        is_direct_sell = true;
                    }
                    
                    var disable_qty_alert = false;
                    if ($('#disable_qty_alert').length) {
                        disable_qty_alert = true;
                    }
                    
                    var is_sales_order = $('#sale_type').length && $('#sale_type').val() == 'sales_order' ? true : false;
                    
                    var is_draft = false;
                    if($('#status') && ($('#status').val()=='quotation' || 
                    $('#status').val()=='draft')) {
                        is_draft = true;
                    }
                    
                    var is_serial_no = false;
                    if ($('input[name="is_serial_no"]').length > 0 &&
                        $('input[name="is_serial_no"]').val() == 1) {
                        is_serial_no = true;
                    }

                    // Quick entry bar: never auto-insert — load Search → Desc → Qty → Rate first
                    var use_quick_entry = $('#vspos_quick_entry').length > 0;
                    
                    $.getJSON(
                        '/products/list',
                        {
                            price_group: price_group,
                            location_id: $('input#location_id').val(),
                            term: request.term,
                            not_for_selling: 0,
                            search_fields: search_fields,
                            auto_add_single: use_quick_entry ? false : true,
                            product_row: $('input#product_row_count').val(),
                            customer_id: customer_id,
                            is_direct_sell: is_direct_sell,
                            is_serial_no: is_serial_no,
                            is_sales_order: is_sales_order,
                            disable_qty_alert: disable_qty_alert,
                            is_draft: is_draft
                        },
                        function(data) {
                            // Check if auto-add is enabled (single product found)
                            if (data.auto_add && data.row_data) {
                                // Automatically add the product row
                                if (data.row_data.success) {
                                    $('#search_product').val('');
                                    // Clear autocomplete cache to prevent stale data issues to avoid same product getting added again and again if search term is same as previous one 
                                    var ac = $('#search_product').data('ui-autocomplete');
                                    if (ac) {
                                        ac.cache = {};
                                        ac.term = '';
                                    }
                                    pos_add_product_row_from_data(data.row_data);
                                } else {
                                    toastr.error(data.row_data.msg);
                                }
                                // Return a special marker to indicate auto-add was handled
                                response([{auto_added: true}]);
                            } else {
                                // Normal autocomplete response
                                response(data.products || data);
                            }
                        }
                    );
                },
                minLength: 1,
                delay: $('#vspos_quick_entry').length ? 200 : 1000,
                response: function(event, ui) {
                    // Skip if auto-add already handled the product
                    if (ui.content.length == 1 && ui.content[0].auto_added) {
                        return;
                    }

                    // Quick entry: do NOT auto-add / auto-select.
                    // User presses Enter on search to load Description → Qty → Rate.
                    if ($('#vspos_quick_entry').length) {
                        return;
                    }
                    
                    if (ui.content.length == 1) {
                        ui.item = ui.content[0];

                        var is_overselling_allowed = false;
                        if($('input#is_overselling_allowed').length) {
                            is_overselling_allowed = true;
                        }
                        var is_pop = $('#is_pop_mode').length > 0;
                        var for_so = false;
                        if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                            for_so = true;
                        }

                        if ((ui.item.enable_stock == 1 && ui.item.qty_available > 0) || 
                                (ui.item.enable_stock == 0) || is_overselling_allowed || for_so || is_pop) {
                            $(this)
                                .data('ui-autocomplete')
                                ._trigger('select', 'autocompleteselect', ui);
                            $(this).autocomplete('close');
                        }
                    } else if (ui.content.length == 0) {
                        // Check if this was a weighing scale barcode - if so, don't show error
                        var was_weighing_scale = $('#search_product').data('weighing_scale_processed');
                        $('#search_product').data('weighing_scale_processed', false); // Reset flag
                        
                        if (!was_weighing_scale) {
                            toastr.error(LANG.no_products_found);
                            if (!$('#__is_mobile').length) {
                                $('input#search_product').select();
                            }
                        }
                    }
                },
                focus: function(event, ui) {
                    if ($('#is_pop_mode').length) {
                        return;
                    }
                    if (ui.item.qty_available <= 0) {
                        return false;
                    }
                },
                select: function(event, ui) {
                    var searched_term = $(this).val();
                    var is_overselling_allowed = false;
                    if($('input#is_overselling_allowed').length) {
                        is_overselling_allowed = true;
                    }
                    var is_pop = $('#is_pop_mode').length > 0;
                    var for_so = false;
                    if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                        for_so = true;
                    }

                    var is_draft=false;
                    if($('#status') && ($('#status').val()=='quotation' || 
                    $('#status').val()=='draft')) {
                        var is_draft=true;
                    }

                    if (ui.item.enable_stock != 1 || ui.item.qty_available > 0 || is_overselling_allowed || for_so || is_draft || is_pop) {
                        //Pre select lot number only if the searched term is same as the lot number
                        var purchase_line_id = ui.item.purchase_line_id && searched_term == ui.item.lot_number ? ui.item.purchase_line_id : null;

                        // Quick entry: fill Description / Qty / Rate — do not clear search until fill works
                        if (typeof vspos_entry_enabled === 'function' && vspos_entry_enabled()) {
                            window.__vspos_last_search_item = ui.item;
                            if (typeof vspos_entry_fill === 'function') {
                                vspos_entry_fill(ui.item, purchase_line_id);
                            }
                            return false;
                        }

                        $(this).val(null);

                        if (typeof __stock_price_management !== 'undefined' && __stock_price_management) {
                            pos_check_price_and_add(ui.item.variation_id, purchase_line_id);
                        } else {
                            pos_product_row(ui.item.variation_id, purchase_line_id);
                        }
                    } else {
                        alert(LANG.out_of_stock);
                    }
                },
            })
            .autocomplete('instance')._renderItem = function(ul, item) {
                // Skip rendering if this is the auto_added marker
                if (item.auto_added) {
                    return $('<li style="display:none;">').appendTo(ul);
                }
                
                var is_overselling_allowed = false;
                if($('input#is_overselling_allowed').length) {
                    is_overselling_allowed = true;
                }

                var for_so = false;
                if ($('#sale_type').length && $('#sale_type').val() == 'sales_order') {
                    for_so = true;
                }
                var is_draft=false;
                
                if($('#status') && ($('#status').val()=='quotation' || 
                $('#status').val()=='draft')) {
                    var is_draft=true;
                }
                var is_pop = $('#is_pop_mode').length > 0;

            if (item.enable_stock == 1 && item.qty_available <= 0 && !is_overselling_allowed && !for_so && !is_draft && !is_pop) {
                var string = '<li class="ui-state-disabled">' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }
                var selling_price = item.selling_price;
                if (item.variation_group_price) {
                    selling_price = item.variation_group_price;
                }
                string +=
                    ' (' +
                    item.sub_sku +
                    ')' +
                    '<br> Price: ' +
                    __currency_trans_from_en(selling_price, false, false, __currency_precision, true) +
                    ' (Out of stock) </li>';
                return $(string).appendTo(ul);
            } else {
                var string = '<div>' + item.name;
                if (item.type == 'variable') {
                    string += '-' + item.variation;
                }

                var selling_price = item.selling_price;
                if (item.variation_group_price) {
                    selling_price = item.variation_group_price;
                }

                string += ' (' + item.sub_sku + ')' + '<br> Price: ' + __currency_trans_from_en(selling_price, false, false, __currency_precision, true);
                if (item.enable_stock == 1) {
                    var qty_available = __currency_trans_from_en(item.qty_available, false, false, __currency_precision, true);
                    string += ' - ' + qty_available + item.unit;
                }
                string += '</div>';

                return $('<li>')
                    .append(string)
                    .appendTo(ul);
            }
        };

        // Classic mode only (quick entry is handled above / by pos-quick-entry.js)
    }

    //Update line total and check for quantity not greater than max quantity
    $('table#pos_table tbody').on('change', 'input.pos_quantity', function() {
        if ($('#is_pop_mode').length && typeof vspos_clear_pop_qty_stock_rules === 'function') {
            vspos_clear_pop_qty_stock_rules($(this).closest('tr'));
        }
        pos_play_success_sound();
        // comment line becouse it validate form at increment and decrement item
        // if (sell_form_validator) {
        //     sell_form.valid();
        // }
        if (pos_form_validator) {
            pos_form_validator.element($(this));
        }
        // var max_qty = parseFloat($(this).data('rule-max'));
        var entered_qty = __read_number($(this));
        

        var tr = $(this).parents('tr');

        if (entered_qty === 0) {
            toastr.warning(
                (typeof LANG !== 'undefined' && LANG.inline_return_qty_zero)
                    ? LANG.inline_return_qty_zero
                    : 'Quantity cannot be zero'
            );
            __write_number($(this), 1);
            entered_qty = 1;
        }

        vspos_sync_inline_return_row(tr);

        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
        var line_total = entered_qty * unit_price_inc_tax;

        __write_number(tr.find('input.pos_line_total'), line_total, false);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        //Change modifier quantity
        tr.find('.modifier_qty_text').each( function(){
            $(this).text(__currency_trans_from_en(entered_qty, false));
        });
        tr.find('.modifiers_quantity').each( function(){
            $(this).val(entered_qty);
        });

        pos_total_row();

        adjustComboQty(tr);
    });

    //If change in unit price update price including tax and line total
    $(document).on('blur', 'table#pos_table tbody td input.pos_unit_price', function() {
        var $input = $(this);
        // Ignore modal mirror — only the visible POS unit price drives the line
        if ($input.hasClass('vspos-modal-unit-price-mirror') || $input.closest('.row_edit_product_price_model, .modal').length) {
            return;
        }
        var tr = $input.parents('tr');
        var unit_price = __read_number($input);

        /* Apply any row discount on top of typed price */
        var $rdtEl = tr.find('td select.row_discount_type');
        var disc_type = $rdtEl.length ? $rdtEl.first().val() : tr.find('select.row_discount_type').first().val();
        var $rdaEl = tr.find('td input.row_discount_amount');
        var disc_amt = __read_number($rdaEl.length ? $rdaEl.first() : tr.find('input.row_discount_amount').first());
        var discounted_unit_price = unit_price;
        if (disc_amt > 0) {
            discounted_unit_price = disc_type === 'fixed'
                ? unit_price - disc_amt
                : __substract_percent(unit_price, disc_amt);
        }

        var $taxSelect = tr.find('td select.tax_id').first();
        var tax_rate = 0;
        if ($taxSelect.length) {
            tax_rate = $taxSelect.find(':selected').data('rate') || 0;
        } else {
            tax_rate = tr.find('.vspos-modal-tax-mirror :selected').data('rate') || 0;
        }
        var quantity = __read_number(tr.find('input.pos_quantity'));
        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;

        __write_number(tr.find('td input.pos_unit_price_inc_tax').length ? tr.find('td input.pos_unit_price_inc_tax') : tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        __write_number(tr.find('td input.pos_line_total').length ? tr.find('td input.pos_line_total') : tr.find('input.pos_line_total'), line_total);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        __write_number(tr.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);
        // Keep modal mirror in sync
        var $modalPrice = tr.find('.vspos-modal-unit-price-mirror');
        if ($modalPrice.length) {
            __write_number($modalPrice, unit_price);
        }
        pos_total_row();
    });
    $('table#pos_table tbody').on('change', 'input.pos_unit_price', function() {
        /* Inline td input is handled by the blur handler above — skip to avoid stale value */
        if ($(this).closest('td').length) return;
        var unit_price = __read_number($(this));
        var tr = $(this).parents('tr');

        //calculate discounted unit price
        var discounted_unit_price = calculate_discounted_unit_price(tr);

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        var line_total = quantity * unit_price_inc_tax;

        __write_number(tr.find('td input.pos_unit_price_inc_tax').length ? tr.find('td input.pos_unit_price_inc_tax') : tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
        __write_number(tr.find('td input.pos_line_total').length ? tr.find('td input.pos_line_total') : tr.find('input.pos_line_total'), line_total);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
        pos_each_row(tr);
        pos_total_row();
        round_row_to_iraqi_dinnar(tr);
    });

    //If change in tax rate then update unit price according to it.
    $('table#pos_table tbody').on('change', 'select.tax_id', function() {
        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));

        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);
        __write_number(tr.find('input.pos_unit_price'), unit_price);
        pos_each_row(tr);
    });

    //If change in unit price including tax, update unit price
    $('table#pos_table tbody').on('change', 'input.pos_unit_price_inc_tax', function() {
        var unit_price_inc_tax = __read_number($(this));

        if (iraqi_selling_price_adjustment) {
            unit_price_inc_tax = round_to_iraqi_dinnar(unit_price_inc_tax);
            __write_number($(this), unit_price_inc_tax);
        }

        var tr = $(this).parents('tr');

        var tax_rate = tr
            .find('select.tax_id')
            .find(':selected')
            .data('rate');
        var quantity = __read_number(tr.find('input.pos_quantity'));

        var line_total = quantity * unit_price_inc_tax;
        var discounted_unit_price = __get_principle(unit_price_inc_tax, tax_rate);
        var unit_price = get_unit_price_from_discounted_unit_price(tr, discounted_unit_price);

        __write_number(tr.find('input.pos_unit_price'), unit_price);
        __write_number(tr.find('input.pos_line_total'), line_total, false);
        tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

        pos_each_row(tr);
        pos_total_row();
    });

    //Change max quantity rule if lot number changes
    $('table#pos_table tbody').on('change', 'select.lot_number', function() {
        var qty_element = $(this)
            .closest('tr')
            .find('input.pos_quantity');

        var tr = $(this).closest('tr');
        var multiplier = 1;
        var unit_name = '';
        var sub_unit_length = tr.find('select.sub_unit').length;
        if (sub_unit_length > 0) {
            var select = tr.find('select.sub_unit');
            multiplier = parseFloat(select.find(':selected').data('multiplier'));
            unit_name = select.find(':selected').data('unit_name');
        }
        var allow_overselling = qty_element.data('allow-overselling');
        if ($('#is_pop_mode').length) {
            return;
        }
        if ($(this).val() && !allow_overselling) {
            var lot_qty = $('option:selected', $(this)).data('qty_available');
            var max_err_msg = $('option:selected', $(this)).data('msg-max');

            if (sub_unit_length > 0) {
                lot_qty = lot_qty / multiplier;
                var lot_qty_formated = __number_f(lot_qty, false);
                max_err_msg = __translate('lot_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', lot_qty);
            qty_element.attr('data-msg-max-value', max_err_msg);

            qty_element.rules('add', {
                'max-value': lot_qty,
                messages: {
                    'max-value': max_err_msg,
                },
            });
        } else {
            var default_qty = qty_element.data('qty_available');
            var default_err_msg = qty_element.data('msg_max_default');
            if (sub_unit_length > 0) {
                default_qty = default_qty / multiplier;
                var lot_qty_formated = __number_f(default_qty, false);
                default_err_msg = __translate('pos_max_qty_error', {
                    max_val: lot_qty_formated,
                    unit_name: unit_name,
                });
            }

            qty_element.attr('data-rule-max-value', default_qty);
            qty_element.attr('data-msg-max-value', default_err_msg);

            qty_element.rules('add', {
                'max-value': default_qty,
                messages: {
                    'max-value': default_err_msg,
                },
            });
        }
        qty_element.trigger('change');
    });

    //Change in row discount type or discount amount
    $('table#pos_table tbody').on(
        'change',
        'select.row_discount_type, input.row_discount_amount',
        function() {
            var tr = $(this).parents('tr');

            //calculate discounted unit price
            var discounted_unit_price = calculate_discounted_unit_price(tr);

            var tax_rate = tr
                .find('select.tax_id')
                .find(':selected')
                .data('rate');
            var quantity = __read_number(tr.find('input.pos_quantity'));

            var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
            var line_total = quantity * unit_price_inc_tax;

            __write_number(tr.find('input.pos_unit_price_inc_tax'), unit_price_inc_tax);
            __write_number(tr.find('input.pos_line_total'), line_total, false);
            tr.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));
            pos_each_row(tr);
            pos_total_row();
            round_row_to_iraqi_dinnar(tr);
        }
    );

    //Remove row on click on remove row
    $('table#pos_table tbody').on('click', '.pos_remove_row', function() {
        $(this)
            .parents('tr')
            .remove();
        pos_total_row();
        pos_sync_empty_state();
    });

    //Cancel the invoice (delegated so both mobile + desktop Cancel buttons fire the same handler)
    $(document).on('click', 'button.js-pos-cancel', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(confirm => {
            if (confirm) {
                reset_pos_form();
            }
        });
    });

    //Save invoice as draft
    $(document).on('click', 'button#pos-draft', function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=draft';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Save invoice as Quotation (no payment)
    $(document).on('click', 'button#pos-quotation', function() {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_valid = isValidPosForm();
        if (is_valid != true) {
            return;
        }

        var data = pos_form_obj.serialize();
        data = data + '&status=quotation';
        var url = pos_form_obj.attr('action');

        disable_pos_form_actions();
        $.ajax({
            method: 'POST',
            url: url,
            data: data,
            dataType: 'json',
            success: function(result) {
                enable_pos_form_actions();
                if (result.success == 1) {
                    reset_pos_form();
                    toastr.success(result.msg);
                    if (typeof pos_update_last_bill === 'function') {
                        pos_update_last_bill(result);
                    }

                    //Check if enabled or not
                    if (result.receipt && result.receipt.is_enabled) {
                        pos_print(result.receipt);
                    }
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

    //Finalize invoice, open payment modal
    $(document).on('click', 'button.pos-finalize', function() {
        if (typeof vspos_clear_pop_qty_stock_rules === 'function') {
            vspos_clear_pop_qty_stock_rules();
        }
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if (!vspos_validate_inline_returns()) {
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        if (typeof pos_validate_serial_per_qty === 'function' && !pos_validate_serial_per_qty()) {
            return false;
        }

        if (typeof vspos_sync_all_row_edit_modals === 'function') {
            vspos_sync_all_row_edit_modals();
        }

        $('#modal_payment').modal('show');
    });

    $('#modal_payment').one('shown.bs.modal', function() {
        $('#modal_payment')
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
        if ($('form#edit_pos_sell_form').length == 0) {
            $(this).find('#method_0').change();
        }
    });

    //Finalize without showing payment options
    $('button.pos-express-finalize').click(function() {
        if (typeof vspos_clear_pop_qty_stock_rules === 'function') {
            vspos_clear_pop_qty_stock_rules();
        }

        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        if (!vspos_validate_inline_returns()) {
            return false;
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        if (typeof pos_validate_serial_per_qty === 'function' && !pos_validate_serial_per_qty()) {
            return false;
        }

        if (typeof vspos_sync_all_row_edit_modals === 'function') {
            vspos_sync_all_row_edit_modals();
        }

        var pay_method = $(this).data('pay_method');

        //If pay method is credit sale submit form
        if (pay_method == 'credit_sale') {
            $('#is_credit_sale').val(1);
            pos_form_obj.submit();
            return true;
        } else {
            if ($('#is_credit_sale').length) {
                $('#is_credit_sale').val(0);
            }
        }

        //Check for remaining balance & add it in 1st payment row
        var total_payable = __read_number($('input#final_total_input'));
        var total_paying = __read_number($('input#total_paying_input'));
        if (total_payable > total_paying) {
            var bal_due = total_payable - total_paying;

            var first_row = $('#payment_rows_div')
                .find('.payment-amount')
                .first();
            var first_row_val = __read_number(first_row);
            first_row_val = first_row_val + bal_due;
            __write_number(first_row, first_row_val);
            first_row.trigger('change');
        }

        //Change payment method.
        var payment_method_dropdown = $('#payment_rows_div')
            .find('.payment_types_dropdown')
            .first();
        
            payment_method_dropdown.val(pay_method);
            payment_method_dropdown.change();
        if (pay_method == 'card') {
            $('div#card_details_modal').modal('show');
        } else if (pay_method == 'suspend') {
            $('div#confirmSuspendModal').modal('show');
        } else {
            pos_form_obj.submit();
        }
    });

    $('div#card_details_modal').on('shown.bs.modal', function(e) {
        $('input#card_number').focus();
    });

    $('div#confirmSuspendModal').on('shown.bs.modal', function(e) {
        $(this)
            .find('textarea')
            .focus();
    });

    //on save card details
    $('button#pos-save-card').click(function() {
        $('input#card_number_0').val($('#card_number').val());
        $('input#card_holder_name_0').val($('#card_holder_name').val());
        $('input#card_transaction_number_0').val($('#card_transaction_number').val());
        $('select#card_type_0').val($('#card_type').val());
        $('input#card_month_0').val($('#card_month').val());
        $('input#card_year_0').val($('#card_year').val());
        $('input#card_security_0').val($('#card_security').val());

        $('div#card_details_modal').modal('hide');
        pos_form_obj.submit();
    });

    $('button#pos-suspend').click(function() {
        $('input#is_suspend').val(1);
        $('div#confirmSuspendModal').modal('hide');
        pos_form_obj.submit();
        $('input#is_suspend').val(0);
    });

    //fix select2 input issue on modal
    $('#modal_payment')
        .find('.select2')
        .each(function() {
            $(this).select2({
                dropdownParent: $('#modal_payment'),
            });
        });

    $('button#add-payment-row').click(function() {
        var row_index = $('#payment_row_index').val();
        var location_id = $('input#location_id').val();
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get_payment_row',
            data: { row_index: row_index, location_id: location_id },
            dataType: 'html',
            success: function(result) {
                if (result) {
                    var appended = $('#payment_rows_div').append(result);

                    var total_payable = __read_number($('input#final_total_input'));
                    var total_paying = __read_number($('input#total_paying_input'));
                    var b_due = total_payable - total_paying;
                    $(appended)
                        .find('input.payment-amount')
                        .focus();
                    $(appended)
                        .find('input.payment-amount')
                        .last()
                        .val(__currency_trans_from_en(b_due, false))
                        .change()
                        .select();
                    __select2($(appended).find('.select2'));
                    if (typeof initChequeDatepicker === 'function') {
                        initChequeDatepicker($(appended));
                    }
                    $(appended).find('#method_' + row_index).change();
                    $('#payment_row_index').val(parseInt(row_index) + 1);
                }
            },
        });
    });

    $(document).on('click', '.remove_payment_row', function() {
        swal({
            title: LANG.sure,
            icon: 'warning',
            buttons: true,
            dangerMode: true,
        }).then(willDelete => {
            if (willDelete) {
                $(this)
                    .closest('.payment_row')
                    .remove();
                calculate_balance_due();
            }
        });
    });

    pos_form_validator = pos_form_obj.validate({
        submitHandler: function(form) {
            if (window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                return false;
            }
            // var total_payble = __read_number($('input#final_total_input'));
            // var total_paying = __read_number($('input#total_paying_input'));
            var cnf = true;

            //Ignore if the difference is less than 0.5
            if ($('input#in_balance_due').val() >= 0.5) {
                cnf = confirm(LANG.paid_amount_is_less_than_payable);
                // if( total_payble > total_paying ){
                // 	cnf = confirm( LANG.paid_amount_is_less_than_payable );
                // } else if(total_payble < total_paying) {
                // 	alert( LANG.paid_amount_is_more_than_payable );
                // 	cnf = false;
                // }
            }

            var total_advance_payments = 0;
            $('#payment_rows_div').find('select.payment_types_dropdown').each( function(){
                if ($(this).val() == 'advance') {
                    total_advance_payments++
                };
            });

            if (total_advance_payments > 1) {
                alert(LANG.advance_payment_cannot_be_more_than_once);
                return false;
            }

            if (typeof pos_validate_cheque_payments === 'function' && !pos_validate_cheque_payments()) {
                return false;
            }

            if (typeof pos_validate_serial_per_qty === 'function' && !pos_validate_serial_per_qty()) {
                return false;
            }

            if (typeof vspos_sync_all_row_edit_modals === 'function') {
                vspos_sync_all_row_edit_modals();
            }

            var is_msp_valid = true;
            //Validate minimum selling price if hidden (skip for Point of Purchase)
            if (!$('#is_pop_mode').length) {
            $('.pos_unit_price_inc_tax').each( function(){
                if (!$(this).is(":visible") && $(this).data('rule-min-value')) {
                    var val = __read_number($(this));
                    var error_msg_td = $(this).closest('tr').find('.pos_line_total_text').closest('td');
                    if (val > $(this).data('rule-min-value')) {
                        is_msp_valid = false;
                        error_msg_td.append( '<label class="error">' + $(this).data('msg-min-value') + '</label>');
                    } else {
                        error_msg_td.find('label.error').remove();
                    }
                }
            });
            }

            if (!is_msp_valid) {
                return false;
            }

            if (cnf) {
                disable_pos_form_actions();

                var data = $(form).serialize();
                data = data + '&status=final';
                var url = $(form).attr('action');
                $.ajax({
                    method: 'POST',
                    url: url,
                    data: data,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success == 1) {
                            var is_pop = $('#is_pop_mode').length > 0 || !!result.is_pop;
                            if (!is_pop && result.whatsapp_link) {
                                window.open(result.whatsapp_link);
                            }
                            $('#modal_payment').modal('hide');
                            if (typeof window.vsposClosePayPopup === 'function') {
                                window.vsposClosePayPopup();
                            }
                            toastr.success(result.msg);

                            if (!is_pop) {
                                pos_notify_customer_display_sale_complete();
                            }
                            var receiptReady = !!(result.receipt && (result.receipt.is_enabled || result.receipt.html_content));
                            var showReceiptUi = !is_pop && !!(result.ebill_prompt || receiptReady);
                            var isEditSale = $('form#edit_pos_sell_form').length > 0;
                            reset_pos_form({
                                deferRedirect: showReceiptUi && isEditSale,
                                redirectNow: !showReceiptUi && isEditSale
                            });
                            pos_update_last_bill(result);
                            if (showReceiptUi && window.PosReceiptReturn) {
                                PosReceiptReturn.begin({
                                    transactionId: result.transaction_id,
                                    isEdit: isEditSale,
                                    saleCompleteOpen: !!result.ebill_prompt
                                });
                            }

                            // POP: 80mm purchase invoice print preview (no eBill)
                            if (is_pop) {
                                if (result.receipt && result.receipt.is_enabled) {
                                    pos_print(result.receipt);
                                }
                                // Ask to print labels with purchase quantities
                                if (result.transaction_id) {
                                    var labelsUrl = (typeof base_path !== 'undefined' ? base_path : '') +
                                        '/labels/show?purchase_id=' + result.transaction_id + '&auto_export=1';
                                    setTimeout(function() {
                                        swal({
                                            title: (LANG.print_labels_after_purchase_title) || 'Print labels?',
                                            text: (LANG.print_labels_after_purchase_text) ||
                                                'Add purchased products to the label list with purchase quantities and export?',
                                            icon: 'info',
                                            buttons: {
                                                cancel: LANG.no || 'No',
                                                confirm: {
                                                    text: LANG.yes || 'Yes',
                                                    value: true
                                                }
                                            }
                                        }).then(function(willPrint) {
                                            if (willPrint) {
                                                window.location.href = labelsUrl;
                                            }
                                        });
                                    }, 600);
                                }
                            } else if (result.ebill_prompt) {
                                window.__ebill_prompt_data = result.ebill_prompt;
                                window.__ebill_receipt = result.receipt || null;
                                $(document).trigger('ebill_prompt_ready');
                            } else if (receiptReady) {
                                if (pos_print(result.receipt) === false && window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                                    toastr.warning('Sale saved, but the receipt could not be prepared.');
                                    PosReceiptReturn.handleAction('print-failed');
                                }
                            } else if (result.receipt && result.receipt.print_error) {
                                toastr.warning('Sale saved, but receipt print failed: ' + result.receipt.print_error);
                            }
                        } else {
                            toastr.error(result.msg);
                        }

                        enable_pos_form_actions();
                    },
                });
            }
            return false;
        },
    });

    $(document).on('change', '.payment-amount', function() {
        calculate_balance_due();
    });

    //Update discount
    $('button#posEditDiscountModalUpdate').click(function() {

        //if discount amount is not valid return false
        if (!$("#discount_amount_modal").valid()) {
            return false;
        }
        //Close modal
        $('div#posEditDiscountModal').modal('hide');

        //Update values
        $('input#discount_type').val($('select#discount_type_modal').val());
        __write_number($('input#discount_amount'), __read_number($('input#discount_amount_modal')));

        if ($('#reward_point_enabled').length) {
            var reward_validation = isValidatRewardPoint();
            if (!reward_validation['is_valid']) {
                toastr.error(reward_validation['msg']);
                $('#rp_redeemed_modal').val(0);
                $('#rp_redeemed_modal').change();
            }
            updateRedeemedAmount();
        }

        pos_total_row();
    });

    //Shipping
    $('button#posShippingModalUpdate').click(function() {
        //Close modal
        $('div#posShippingModal').modal('hide');

        //update shipping details
        $('input#shipping_details').val($('#shipping_details_modal').val());

        $('input#shipping_address').val($('#shipping_address_modal').val());
        $('input#shipping_status').val($('#shipping_status_modal').val());
        $('input#delivered_to').val($('#delivered_to_modal').val());
        $('input#delivery_person').val($('#delivery_person_modal').val());

        //Update shipping charges
        __write_number(
            $('input#shipping_charges'),
            __read_number($('input#shipping_charges_modal'))
        );

        //$('input#shipping_charges').val(__read_number($('input#shipping_charges_modal')));

        pos_total_row();
    });

    $('#posShippingModal').on('shown.bs.modal', function() {
        $('#posShippingModal')
            .find('#shipping_details_modal')
            .filter(':visible:first')
            .focus()
            .select();
            // $('.select2-selection__rendered').css('padding-right', '150px');
    });

    /* Line-edit modal lives inside #pos_table — reparent to the sell form so fields still submit after escaping overflow:hidden cart panel */
    $(document).on('show.bs.modal', '.row_edit_product_price_model', function () {
        var $modal = $(this);
        var $form = $modal.closest('form');
        if (!$form.length) {
            $form = $('#add_pos_sell_form').length ? $('#add_pos_sell_form') : $('#edit_pos_sell_form');
        }
        if ($form.length && !$modal.parent().is($form)) {
            $modal.appendTo($form);
        }
    });

    $(document).on('shown.bs.modal', '.row_edit_product_price_model', function() {
        var $modal = $(this);
        if (typeof vspos_serial_rebuild_for_modal === 'function') {
            vspos_serial_rebuild_for_modal($modal);
        }
        $modal
            .find('input')
            .filter(':visible:first')
            .focus()
            .select();
    });

    /* When product price/discount modal closes, sync its inputs back to the
       hidden table-cell inputs and recalculate so item discount shows correctly */
    $(document).on('hidden.bs.modal', '.row_edit_product_price_model', function() {
        vspos_sync_row_edit_modal($(this));
    });

    $(document).on('change', '.vspos-modal-tax-mirror', function() {
        var $modal = $(this).closest('.row_edit_product_price_model');
        var tax_rate = $(this).find(':selected').data('rate') || 0;
        var unit_price = __read_number($modal.find('input.pos_unit_price'));
        var disc_type = $modal.find('select.row_discount_type').val();
        var disc_amt = __read_number($modal.find('input.row_discount_amount'));
        var discounted_unit_price = unit_price;
        if (disc_amt > 0) {
            discounted_unit_price = disc_type === 'fixed'
                ? unit_price - disc_amt
                : __substract_percent(unit_price, disc_amt);
        }
        var unit_price_inc_tax = __add_percent(discounted_unit_price, tax_rate);
        __write_number($modal.find('.vspos-modal-item-tax-mirror'), unit_price_inc_tax - discounted_unit_price);
    });

    //Update Order tax
    $('button#posEditOrderTaxModalUpdate').click(function() {
        //Close modal
        $('div#posEditOrderTaxModal').modal('hide');

        var tax_obj = $('select#order_tax_modal');
        var tax_id = tax_obj.val();
        var tax_rate = tax_obj.find(':selected').data('rate');

        $('input#tax_rate_id').val(tax_id);

        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });

    $(document).on('click', '.add_new_customer', function() {
        $('#customer_id').select2('close');
        var name = $(this).data('name');
        $('.contact_modal')
            .find('input#name')
            .val(name);
        $('.contact_modal')
            .find('select#contact_type')
            .val('customer')
            .closest('div.contact_type_div')
            .addClass('hide');
        $('.contact_modal').modal('show');
    });
    $('form#quick_add_contact')
        .submit(function(e) {
            e.preventDefault();
        })
        .validate({
            rules: {
                contact_id: {
                    remote: {
                        url: '/contacts/check-contacts-id',
                        type: 'post',
                        data: {
                            contact_id: function() {
                                return $('#contact_id').val();
                            },
                            hidden_id: function() {
                                return $('#hidden_id').val() || '';
                            },
                        },
                    },
                },
                // tax_number remote validation removed - now handled with sweet alert
            },
            messages: {
                contact_id: {
                    required: LANG.contact_id_required,
                    remote: LANG.contact_id_already_exists,
                },
            },
            submitHandler: function(form) {
                checkTaxNumberAndSubmitQuick(form);
            },
        });

    function checkTaxNumberAndSubmitQuick(form) {
        // Check if tax_number field exists and has a value
        if ($('#tax_number').length && $('#tax_number').val().trim() !== '') {
            $.ajax({
                method: 'POST',
                url: base_path + '/contacts/check-tax-number',
                dataType: 'json',
                data: {
                    contact_id: $('#hidden_id').val(),
                    tax_number: $('#tax_number').val(),
                },
                success: function(result) {
                    if (result.is_tax_number_exists == true) {
                        swal({
                            title: LANG.sure,
                            text: result.msg,
                            icon: 'warning',
                            buttons: true,
                            dangerMode: true,
                        }).then(willContinue => {
                            if (willContinue) {
                                checkMobileAndSubmitQuick(form);
                            } else {
                                $('#tax_number').select();
                            }
                        });
                    } else {
                        checkMobileAndSubmitQuick(form);
                    }
                },
            });
        } else {
            // If no tax number, proceed to mobile check
            checkMobileAndSubmitQuick(form);
        }
    }

    function checkMobileAndSubmitQuick(form) {
        $.ajax({
            method: 'POST',
            url: base_path + '/check-mobile',
            dataType: 'json',
            data: {
                contact_id: function() {
                    return $('#hidden_id').val();
                },
                mobile_number: function() {
                    return $('#mobile').val();
                },
            },
            success: function(result) {
                if (result.is_mobile_exists == true) {
                    swal({
                        title: LANG.sure,
                        text: result.msg,
                        icon: 'warning',
                        buttons: true,
                        dangerMode: true,
                    }).then(willContinue => {
                        if (willContinue) {
                            submitQuickContactForm(form);
                        } else {
                            $('#mobile').select();
                        }
                    });
                    
                } else {
                    submitQuickContactForm(form);
                }
            },
        });
    }

    $('.contact_modal').on('hidden.bs.modal', function() {
        $('form#quick_add_contact')
            .find('button[type="submit"]')
            .removeAttr('disabled');
        $('form#quick_add_contact')[0].reset();
    });

    //Updates for add sell
    $('select#discount_type, input#discount_amount, input#shipping_charges, \
        input#rp_redeemed_amount').change(function() {
        pos_total_row();
    });
    $('select#tax_rate_id').change(function() {
        var tax_rate = $(this)
            .find(':selected')
            .data('rate');
        __write_number($('input#tax_calculation_amount'), tax_rate);
        pos_total_row();
    });
    //Datetime picker
    $('#transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });

    //Direct sell submit
    sell_form = $('form#add_sell_form');
    if ($('form#edit_sell_form').length) {
        sell_form = $('form#edit_sell_form');
        pos_total_row();
    }
    sell_form_validator = sell_form.validate({
        rules: {
            invoice_no: {
                remote: {
                    url: '/sell/check-invoice-number',
                    type: 'post',
                    data: {
                        invoice_no: function() {
                            return $('#invoice_no').val();
                        },
                        transaction_id: function() {
                            var id = '';
                            var editForm = $('form#edit_sell_form');
                            if (editForm.length) {
                                id = editForm.data('transaction-id');
                            }
                            return id || '';
                        }
                    }
                }
            },
        },
        messages: {
            invoice_no: {
                remote: LANG.invoice_number_already_exists,
            },
        },
    });

    $('button#submit-sell, button#save-and-print').click(function(e) {
        //Check if product is present or not.
        if ($('table#pos_table tbody').find('.product_row').length <= 0) {
            toastr.warning(LANG.no_products_added);
            return false;
        }

        var is_msp_valid = true;
        //Validate minimum selling price if hidden
        $('.pos_unit_price_inc_tax').each( function(){
            if (!$(this).is(":visible") && $(this).data('rule-min-value')) {
                var val = __read_number($(this));
                var error_msg_td = $(this).closest('tr').find('.pos_line_total_text').closest('td');
                if (val > $(this).data('rule-min-value')) {
                    is_msp_valid = false;
                    error_msg_td.append( '<label class="error">' + $(this).data('msg-min-value') + '</label>');
                } else {
                    error_msg_td.find('label.error').remove();
                }
            }
        });

        if (!is_msp_valid) {
            return false;
        }

        if ($(this).attr('id') == 'save-and-print') {
            $('#is_save_and_print').val(1);           
        } else {
            $('#is_save_and_print').val(0);
        }

        if ($('#reward_point_enabled').length) {
            var validate_rp = isValidatRewardPoint();
            if (!validate_rp['is_valid']) {
                toastr.error(validate_rp['msg']);
                return false;
            }
        }

        if ($('.enable_cash_denomination_for_payment_methods').length) {
            var payment_row = $('.enable_cash_denomination_for_payment_methods').closest('.payment_row');
            var is_valid = true;
            var payment_type = payment_row.find('.payment_types_dropdown').val();
            var denomination_for_payment_types = JSON.parse($('.enable_cash_denomination_for_payment_methods').val());
            if (denomination_for_payment_types.includes(payment_type) && payment_row.find('.is_strict').length && payment_row.find('.is_strict').val() === '1' ) {
                var payment_amount = __read_number(payment_row.find('.payment-amount'));
                var total_denomination = payment_row.find('input.denomination_total_amount').val();
                if (payment_amount != total_denomination ) {
                    is_valid = false;
                }
            }

            if (!is_valid) {
                payment_row.find('.cash_denomination_error').removeClass('hide');
                toastr.error(payment_row.find('.cash_denomination_error').text());
                e.preventDefault();
                return false;
            } else {
                payment_row.find('.cash_denomination_error').addClass('hide');
            }
        }

        if (sell_form.valid()) {
            window.onbeforeunload = null;
            $(this).attr('disabled', true);
            sell_form.submit();
        }
    });

    //REPAIR MODULE:check if repair module field is present send data to filter product
    var is_enabled_stock = null;
    if ($("#is_enabled_stock").length) {
        is_enabled_stock = $("#is_enabled_stock").val();
    }

    var device_model_id = null;
    if ($("#repair_model_id").length) {
        device_model_id = $("#repair_model_id").val();
    }

    //Show product list (Touch UI loads its own grid in pos-touch.js — avoid double-fetch race)
    if (!document.getElementById('vspos_touch_shell')) {
        get_product_suggestion_list(
            global_p_category_id,
            global_brand_id,
            $('input#location_id').val(),
            null,
            is_enabled_stock,
            device_model_id
        );
    }
    
    $('select#select_location_id').on('change', function(e) {
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                global_p_category_id,
                global_brand_id,
                $('input#location_id').val(),
                null
            );
        }
        get_featured_products();
    });

// Active filter is shown as a second line INSIDE the matching Category / Brand
    // button. The blade template already renders the row markup (hidden); this
    // function just toggles `.is-active` on it and writes the selected name in.
    function pos_set_filter_chip(kind, name) {
        var cls = (kind === 'category') ? '.pos-filter-badge-cat'
                : (kind === 'brand')    ? '.pos-filter-badge-brand'
                : null;
        if (!cls) return;
        var $rows = $(cls);
        if (!$rows.length) return;
        var $names = $rows.find('.pos-active-filter-name');
        if (!name) {
            $rows.removeClass('is-active');
            $names.text('');
            return;
        }
        $names.text(name);
        $rows.addClass('is-active');
    }

    // Re-trigger the staggered card entrance each time a drawer opens.
    // Using class toggle + forced reflow because CSS @keyframes only fire once per element.
    function pos_animate_drawer_cards($drawerSide) {
        var $grid = $drawerSide.find('.pos-card-grid').filter(':visible');
        $grid.each(function() {
            var $g = $(this);
            $g.removeClass('is-animating');
            void this.offsetWidth;
            $g.addClass('is-animating');
        });
    }

    $(document).on('click', '.pos-filter-chip-clear', function(e) {
        // Pill wraps the × in a <label for="...">; without these the click would
        // also toggle the drawer checkbox.
        e.preventDefault();
        e.stopPropagation();
        var kind = $(this).data('clear');
        if (kind === 'category') {
            global_p_category_id = null;
        } else if (kind === 'brand') {
            global_brand_id = null;
        }
        pos_set_filter_chip(kind, null);
        $('input#suggestion_page').val(1);
        get_product_suggestion_list(global_p_category_id, global_brand_id, $('input#location_id').val(), null);
        get_featured_products();
    });

// on click sub category in category drawer
    $('.product_category').on('click', function(e) {
        global_p_category_id = $(this).data('value');
        pos_set_filter_chip('category', $(this).data('name'));
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();
        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                global_p_category_id,
                global_brand_id,
                $('input#location_id').val(),
                null
            );
        }
        get_featured_products();
        $('.overlay-category').trigger('click');
    });

    // Category card tap:
    //   "All"              → clear filter, close drawer
    //   No subcategories   → filter directly, close drawer
    //   Has subcategories  → inject pill strip below the card's row (toggle)
    $('.main-category').on('click', function(e) {
        e.stopPropagation();

        var value = $(this).data('value');
        var name  = $(this).data('name');
        var $grid = $('.pos-card-grid-categories');

        // "All Categories" — clear filter and any open strip
        if (value === 'all') {
            global_p_category_id = null;
            pos_set_filter_chip('category', null);
            $grid.find('.pos-subcat-strip').remove();
            $grid.removeData('open-cat');
            $grid.find('.pos-card.cat').removeClass('is-active');
            $('input#suggestion_page').val(1);
            get_product_suggestion_list(null, global_brand_id, $('input#location_id').val(), null);
            get_featured_products();
            $('.overlay-category').trigger('click');
            return;
        }

        // Check for subcategory data stored in the hidden divs
        var $subcatSource = $('.all-sub-category[data-category-id="' + value + '"]');

        if (!$subcatSource.length) {
            // No subcategories — filter immediately and close
            global_p_category_id = value;
            pos_set_filter_chip('category', name);
            $grid.find('.pos-subcat-strip').remove();
            $grid.removeData('open-cat');
            $grid.find('.pos-card.cat').removeClass('is-active');
            $('input#suggestion_page').val(1);
            get_product_suggestion_list(global_p_category_id, global_brand_id, $('input#location_id').val(), null);
            get_featured_products();
            $('.overlay-category').trigger('click');
            return;
        }

        // Has subcategories — tapping same card again collapses the strip
        if ($grid.data('open-cat') == value) {
            $grid.find('.pos-subcat-strip').remove();
            $grid.removeData('open-cat');
            $grid.find('.pos-card.cat').removeClass('is-active');
            return;
        }

        // Mark active parent card
        $grid.data('open-cat', value);
        $grid.find('.pos-card.cat').removeClass('is-active');
        $(this).closest('.main-category-div').find('.pos-card.cat').addClass('is-active');

        // Find the last col in the same visual row (3 cols per row at md breakpoint)
        var $cols     = $grid.children('[class*="col-"]').not('.pos-subcat-strip');
        var idx       = $cols.index($(this).closest('[class*="col-"]'));
        var $lastInRow = $cols.eq(Math.min(Math.ceil((idx + 1) / 3) * 3 - 1, $cols.length - 1));

        // Build pills: first pill = "All [Name]" (selects parent), rest = subcategories
        var allLabel = $grid.data('label-all') || 'All';
        var pills = '<button type="button" class="pos-subcat-pill pos-subcat-pill--all" data-value="' + value + '" data-name="' + name + '">' + allLabel + ' ' + name + '</button>';
        $subcatSource.find('.product_category').each(function() {
            var scId   = $(this).data('value');
            var scName = $(this).data('name');
            if (scId && scName) {
                pills += '<button type="button" class="pos-subcat-pill" data-value="' + scId + '" data-name="' + scName + '">' + scName + '</button>';
            }
        });

        $grid.find('.pos-subcat-strip').remove();
        $('<div class="pos-subcat-strip"></div>').html(pills).insertAfter($lastInRow);
    });

    // Subcategory pill tap — delegated because pills are dynamically injected
    $(document).on('click', '.pos-subcat-pill', function(e) {
        e.stopPropagation();
        var value = $(this).data('value');
        var name  = $(this).data('name');

        global_p_category_id = value;
        pos_set_filter_chip('category', name);
        $('input#suggestion_page').val(1);
        get_product_suggestion_list(global_p_category_id, global_brand_id, $('input#location_id').val(), null);
        get_featured_products();

        var $grid = $('.pos-card-grid-categories');
        $grid.find('.pos-subcat-strip').remove();
        $grid.removeData('open-cat');
        $grid.find('.pos-card.cat').removeClass('is-active');
        $('.overlay-category').trigger('click');
    });

    // on click brand in brand drawer
    $('.product_brand').on('click', function(e) {
        global_brand_id = $(this).data('value');
        pos_set_filter_chip('brand', $(this).data('name'));
        $('input#suggestion_page').val(1);
        var location_id = $('input#location_id').val();

        if (location_id != '' || location_id != undefined) {
            get_product_suggestion_list(
                global_p_category_id,
                global_brand_id,
                $('input#location_id').val(),
                null
            );
        }
        get_featured_products();
        $('.overlay-brand').trigger('click');
    });

    // close side bar

    $('.close-side-bar-category').on('click', function() {
        $('.overlay-category').trigger('click');
    });

    $('.close-side-bar-brand').on('click', function() {
        $('.overlay-brand').trigger('click');
    });

    // Each drawer opens → re-run card entrance stagger.
    // Category drawer also resets to the main view when it closes (so next open is clean).
    $('#my-drawer-4').on('change', function() {
        var $side = $(this).siblings('.tw-dw-drawer-side');
        if (this.checked) {
            pos_animate_drawer_cards($side);
        } else {
            // Drawer closed — collapse any open subcategory strip
            var $grid = $('.pos-card-grid-categories');
            $grid.find('.pos-subcat-strip').remove();
            $grid.removeData('open-cat');
            $grid.find('.pos-card.cat').removeClass('is-active');
        }
    });
    $('#my-drawer-brand').on('change', function() {
        if (this.checked) {
            pos_animate_drawer_cards($(this).siblings('.tw-dw-drawer-side'));
        }
    });

    // When the mobile product-suggestion Bootstrap modal closes, uncheck any DaisyUI
    // drawer toggles that may have been activated inside it (duplicate IDs mean the
    // label inside the modal targets the main-page drawer, leaving its overlay open).
    $(document).on('hidden.bs.modal', '#mobile_product_suggestion_modal', function() {
        // Uncheck both the main-sidebar drawers and the modal-sidebar drawers (suffix _modal)
        $('#my-drawer-4, #my-drawer-4_modal, #my-drawer-brand, #my-drawer-brand_modal').prop('checked', false);
        var $grid = $('.pos-card-grid-categories');
        $grid.find('.pos-subcat-strip').remove();
        $grid.removeData('open-cat');
        $grid.find('.pos-card.cat').removeClass('is-active');
    });




    // Product grid → Qty/Rate entry (click / dblclick / Enter). Confirm with Rate Enter.
    function vspos_product_box_item($box) {
        if (!$box || !$box.length) {
            return null;
        }
        var variation_id = $box.data('variation_id') || $box.attr('data-variation_id');
        if (!variation_id) {
            return null;
        }
        return {
            variation_id: variation_id,
            name: $box.attr('data-name') || $box.data('name') || '',
            type: $box.attr('data-type') || $box.data('type') || '',
            variation: $box.attr('data-variation') || $box.data('variation') || '',
            selling_price: $box.attr('data-selling_price') || $box.data('selling_price') || 0,
            enable_stock: $box.attr('data-enable_stock') || $box.data('enable_stock'),
            qty_available: $box.attr('data-qty_available') || $box.data('qty_available'),
            sub_sku: $box.attr('data-sku') || $box.data('sku') || ''
        };
    }

    function vspos_fill_entry_from_grid($box, returnTarget) {
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
            return false;
        }
        var item = vspos_product_box_item($box);
        if (!item) {
            return false;
        }
        window.__vspos_return_focus_grid = returnTarget || ($box.get(0) || 'tiles');
        $('.product_box').removeClass('is-grid-selected');
        $box.addClass('is-grid-selected');
        if (typeof window.vspos_entry_fill === 'function') {
            return window.vspos_entry_fill(item);
        }
        if (typeof vspos_entry_fill === 'function') {
            return vspos_entry_fill(item);
        }
        return false;
    }

    $(document).on('click', 'div.product_box', function(e) {
        // Touch POS handles taps in pos-touch.js (immediate add)
        if (window.__vspos_touch_ui || document.getElementById('vspos_touch_shell')) {
            return;
        }
        // Shift+click still adds immediately for power users
        if (e.shiftKey) {
            if ($('input#location_id').val() == '') {
                toastr.warning(LANG.select_location);
                return;
            }
            window.__vspos_return_focus_grid = $(this).get(0) || 'tiles';
            window.__vspos_entry_committing = true;
            try {
                pos_product_row($(this).data('variation_id'));
            } finally {
                window.__vspos_entry_committing = false;
            }
            setTimeout(function() {
                if (typeof vspos_focus_return_after_entry === 'function') {
                    vspos_focus_return_after_entry();
                }
            }, 350);
            return;
        }
        vspos_fill_entry_from_grid($(this));
    });

    $(document).on('dblclick', 'div.product_box', function(e) {
        if (window.__vspos_touch_ui || document.getElementById('vspos_touch_shell')) {
            return;
        }
        e.preventDefault();
        vspos_fill_entry_from_grid($(this));
    });

    $(document).on('keydown', 'div.product_box', function(e) {
        if (window.__vspos_touch_ui || document.getElementById('vspos_touch_shell')) {
            return;
        }
        var key = e.key || e.which;
        if (key !== 'Enter' && key !== 13 && key !== ' ' && key !== 32) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        vspos_fill_entry_from_grid($(this));
    });

    $(document).on('shown.bs.modal', '.row_description_modal', function() {
        $(this)
            .find('textarea')
            .first()
            .focus();
    });

    //Press enter on search product to jump into last quantty and vice-versa
    $('#search_product').keydown(function(e) {
        var key = e.which;
        if (key == 9) {
            // the tab key code
            e.preventDefault();
            if ($('#pos_table tbody tr').length > 0) {
                $('#pos_table tbody tr:last')
                    .find('input.pos_quantity')
                    .focus()
                    .select();
            }
        }
    });
    $('#pos_table').on('keypress', 'input.pos_quantity', function(e) {
        var key = e.which;
        if (key == 13) {
            // the enter key code
            if (!$('#__is_mobile').length) {
                $('#search_product').focus();
            }
        }
    });

    $('#exchange_rate').change(function() {
        var curr_exchange_rate = 1;
        if ($(this).val()) {
            curr_exchange_rate = __read_number($(this));
        }
        var total_payable = __read_number($('input#final_total_input'));
        var shown_total = total_payable * curr_exchange_rate;
        $('span#total_payable').text(__currency_trans_from_en(shown_total, false));
    });

    function vsposParseGroupPrices(raw) {
        if (!raw) {
            return {};
        }
        if (typeof raw === 'object') {
            return raw;
        }
        try {
            return JSON.parse(raw);
        } catch (e) {
            return {};
        }
    }

    function vsposResolveModePrice($box, pg) {
        var retail = parseFloat($box.attr('data-retail_price'));
        if (isNaN(retail)) {
            retail = parseFloat($box.attr('data-selling_price')) || 0;
        }
        var groups = vsposParseGroupPrices($box.attr('data-group_prices'));
        var key = String(pg || '0');
        if (key !== '0' && key !== '' && groups[key] != null && groups[key] !== '') {
            var gp = parseFloat(groups[key]);
            if (!isNaN(gp)) {
                return gp;
            }
        }
        return retail;
    }

    function vsposApplyPosPriceMode() {
        if ($('#is_pop_mode').length) {
            return;
        }
        var pg = $('#price_group').val() || 0;

        $('.product_box').each(function() {
            var $box = $(this);
            var price = vsposResolveModePrice($box, pg);
            $box.attr('data-selling_price', price);
            $box.data('selling_price', price);
            var $priceEl = $box.find('.product_price');
            if ($priceEl.length) {
                $priceEl.text(__currency_trans_from_en(price, true));
            }
        });

        // Update prices already in the cart to match the active mode.
        $('#pos_table tbody tr').each(function() {
            var $row = $(this);
            var vid = $row.find('.row_variation_id').val();
            if (!vid) {
                return;
            }
            var $box = $('.product_box[data-variation_id="' + vid + '"]');
            if (!$box.length) {
                return;
            }
            var price = vsposResolveModePrice($box, pg);
            var $inc = $row.find('input.pos_unit_price_inc_tax').first();
            var $exc = vspos_row_unit_price_input($row);
            if ($inc.length) {
                __write_number($inc, price);
                $inc.trigger('change');
            }
            if ($exc.length) {
                __write_number($exc, price);
                $exc.trigger('change').trigger('blur');
            }
            var $modalPrice = $row.find('.vspos-modal-unit-price-mirror, .row_edit_product_price_model input.pos_unit_price');
            if ($modalPrice.length) {
                __write_number($modalPrice, price);
            }
        });

        if (typeof pos_total_row === 'function') {
            pos_total_row();
        }
    }
    window.vsposApplyPosPriceMode = vsposApplyPosPriceMode;

    function vsposSyncWholesaleSwitch(pg) {
        var $sw = $('#pos_wholesale_switch');
        if (!$sw.length) {
            return;
        }
        var wg = String($sw.data('wholesale-group'));
        var on = String(pg) === wg;
        $sw.prop('checked', on);
        var label = on ? $sw.data('label-wholesale') : $sw.data('label-retail');
        $('#pos_wholesale_label').text(label || (on ? 'Wholesale' : 'Retail'));
        $sw.closest('.pos-wholesale-switch').toggleClass('is-on', on);
    }

    $(document).on('change', '#price_group', function() {
        $('input#hidden_price_group').val($(this).val());
        vsposSyncWholesaleSwitch($(this).val());
        vsposApplyPosPriceMode();
    });

    $(document).on('change', '#pos_wholesale_switch', function() {
        var $sw = $(this);
        var wg = $sw.data('wholesale-group');
        var pg = $sw.is(':checked') ? wg : 0;
        var label = $sw.is(':checked') ? $sw.data('label-wholesale') : $sw.data('label-retail');
        $('#pos_wholesale_label').text(label || ($sw.is(':checked') ? 'Wholesale' : 'Retail'));
        $sw.closest('.pos-wholesale-switch').toggleClass('is-on', $sw.is(':checked'));

        var $pg = $('#price_group');
        if ($pg.length) {
            $pg.val(pg);
            $('input#hidden_price_group').val(pg);
            // Avoid double-apply: set then apply directly (change may also fire)
            vsposApplyPosPriceMode();
        }
    });

    //Quick add product (POS / POP)
    $(document).on('click', 'button.pos_add_quick_product', function() {
        var url = $(this).data('href');
        var container = $(this).data('container');
        var product_for = $('#is_pop_mode').length ? 'pop' : 'pos';
        $.ajax({
            url: url + '?product_for=' + product_for,
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                $('.os_exp_date').datepicker({
                    autoclose: true,
                    format: 'dd-mm-yyyy',
                    clearBtn: true,
                });
            },
        });
    });

    $(document).on('change', 'form#quick_add_product_form input#single_dpp', function() {
        var unit_price = __read_number($(this));
        $('table#quick_product_opening_stock_table tbody tr').each(function() {
            var input = $(this).find('input.unit_price');
            __write_number(input, unit_price);
            input.change();
        });
    });

    $(document).on('quickProductAdded', function(e) {
        //Check if location is not set then show error message.
        if ($('input#location_id').val() == '') {
            toastr.warning(LANG.select_location);
            return;
        }
        if (!e.variation || !e.variation.id) {
            return;
        }

        var is_pop = $('#is_pop_mode').length > 0;
        var cost = e.variation.dpp_inc_tax != null
            ? e.variation.dpp_inc_tax
            : e.variation.default_purchase_price;
        var sell = e.variation.sell_price_inc_tax != null
            ? e.variation.sell_price_inc_tax
            : e.variation.default_sell_price;

        // Refresh product tiles so the new item appears in the grid
        try {
            if (typeof get_product_suggestion_list === 'function') {
                $('input#suggestion_page').val(1);
                get_product_suggestion_list(
                    window.global_p_category_id || null,
                    window.global_brand_id || null,
                    $('input#location_id').val(),
                    null
                );
            }
        } catch (err) {}

        // POP always / touch ask-qty: open Qty & Cost/MRP popup, then add to cart
        var ask_qty = String($('#vspos_touch_shell').attr('data-ask-qty') || '') === '1';
        if (typeof window.vsposOpenTouchQtyModal === 'function' && $('#vspos_touch_qty_modal').length && (is_pop || ask_qty)) {
            var opened = window.vsposOpenTouchQtyModal({
                variation_id: e.variation.id,
                name: (e.product && e.product.name) ? e.product.name : 'Product',
                sku: (e.product && e.product.sku) ? e.product.sku : (e.variation.sub_sku || ''),
                unit: '',
                cost_price: cost,
                sell_price: sell,
                last_purchase_price: cost,
                selling_price: is_pop ? cost : sell
            });
            if (opened) {
                return;
            }
        }

        pos_product_row(e.variation.id);
    });

    $('div.view_modal').on('show.bs.modal', function() {
        __currency_convert_recursively($(this));
    });

    $('table#pos_table').on('change', 'select.sub_unit', function() {
        var tr = $(this).closest('tr');
        var base_unit_selling_price = tr.find('input.hidden_base_unit_sell_price').val();

        var selected_option = $(this).find(':selected');

        var multiplier = parseFloat(selected_option.data('multiplier'));

        var allow_decimal = parseInt(selected_option.data('allow_decimal'));

        tr.find('input.base_unit_multiplier').val(multiplier);

        var unit_sp = base_unit_selling_price * multiplier;

        var sp_element = tr.find('input.pos_unit_price');
        __write_number(sp_element, unit_sp);

        sp_element.change();

        var qty_element = tr.find('input.pos_quantity');
        var base_max_avlbl = qty_element.data('qty_available');
        var error_msg_line = 'pos_max_qty_error';

        if (tr.find('select.lot_number').length > 0) {
            var lot_select = tr.find('select.lot_number');
            if (lot_select.val()) {
                base_max_avlbl = lot_select.find(':selected').data('qty_available');
                error_msg_line = 'lot_max_qty_error';
            }
        }

        qty_element.attr('data-decimal', allow_decimal);
        var abs_digit = true;
        if (allow_decimal) {
            abs_digit = false;
        }
        qty_element.rules('add', {
            abs_digit: abs_digit,
        });

        if (base_max_avlbl && !$('#is_pop_mode').length) {
            var max_avlbl = parseFloat(base_max_avlbl) / multiplier;
            var formated_max_avlbl = __number_f(max_avlbl);
            var unit_name = selected_option.data('unit_name');
            var max_err_msg = __translate(error_msg_line, {
                max_val: formated_max_avlbl,
                unit_name: unit_name,
            });
            qty_element.attr('data-rule-max-value', max_avlbl);
            qty_element.attr('data-msg-max-value', max_err_msg);
            qty_element.rules('add', {
                'max-value': max_avlbl,
                messages: {
                    'max-value': max_err_msg,
                },
            });
            qty_element.trigger('change');
        }
        adjustComboQty(tr);
    });

    // Leave POS: empty-state row used to falsely trigger browser "Leave site?" dialog.
    // Use product rows only; intercept Back/Home with themed SweetAlert (not browser alert).
    function pos_has_cart_items() {
        return $('table#pos_table tbody').find('.product_row').length > 0;
    }

    function pos_clear_leave_guard() {
        window.onbeforeunload = null;
    }

    function pos_confirm_leave(url) {
        if (!url) return;
        if (!pos_has_cart_items()) {
            pos_clear_leave_guard();
            window.location.href = url;
            return;
        }
        swal({
            title: (typeof LANG !== 'undefined' && LANG.leave_pos_title) ? LANG.leave_pos_title : 'Leave POS?',
            text: (typeof LANG !== 'undefined' && LANG.leave_pos_text) ? LANG.leave_pos_text : 'Items in the cart will be lost. Continue?',
            icon: 'warning',
            buttons: {
                cancel: {
                    text: (typeof LANG !== 'undefined' && LANG.stay) ? LANG.stay : 'Stay',
                    value: null,
                    visible: true,
                    className: 'vspos-swal-cancel',
                    closeModal: true,
                },
                confirm: {
                    text: (typeof LANG !== 'undefined' && LANG.leave) ? LANG.leave : 'Leave',
                    value: true,
                    visible: true,
                    className: 'vspos-swal-confirm',
                    closeModal: true,
                },
            },
            dangerMode: true,
            className: 'vspos-swal',
        }).then(function (willLeave) {
            if (willLeave) {
                pos_clear_leave_guard();
                window.location.href = url;
            }
        });
    }

    window.pos_confirm_leave = pos_confirm_leave;
    window.pos_clear_leave_guard = pos_clear_leave_guard;

    // Browser close / refresh only — browsers force native UI here; keep when cart has items
    window.onbeforeunload = function () {
        if ($('form#edit_pos_sell_form').length == 0 && pos_has_cart_items()) {
            return LANG.sure;
        }
    };

    $(document).on('click', 'a#pos_go_back_btn, a.pos-brand-chip', function (e) {
        if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
            return;
        }
        var href = $(this).attr('href');
        if (!href || href === '#' || href.indexOf('javascript:') === 0) {
            return;
        }
        e.preventDefault();
        pos_confirm_leave(href);
    });

    $(window).resize(function() {
        var win_height = $(window).height();
        div_height = __calculate_amount('percentage', 63, win_height);
        // $('div.pos_product_div').css('min-height', div_height + 'px');
        // $('div.pos_product_div').css('max-height', div_height + 'px');
    });

    //Used for weighing scale barcode
    $('#weighing_scale_modal').on('shown.bs.modal', function (e) {

        //Attach the scan event
        onScan.attachTo(document, {
            suffixKeyCodes: [13], // enter-key expected at the end of a scan
            reactToPaste: true, // Compatibility to built-in scanners in paste-mode (as opposed to keyboard-mode)
            onScan: function(sCode, iQty) {
                console.log('Scanned: ' + iQty + 'x ' + sCode); 
                $('input#weighing_scale_barcode').val(sCode);
                $('button#weighing_scale_submit').trigger('click');
            },
            onScanError: function(oDebug) {
                console.log(oDebug); 
            },
            minLength: 2
            // onKeyDetect: function(iKeyCode){ // output all potentially relevant key events - great for debugging!
            //     console.log('Pressed: ' + iKeyCode);
            // }
        });

        $('input#weighing_scale_barcode').focus();
    });

    $('#weighing_scale_modal').on('hide.bs.modal', function (e) {
        //Detach from the document once modal is closed.
        onScan.detachFrom(document);
    });

    $('button#weighing_scale_submit').click(function(){

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = $('#price_group').val();
        }

        if($('#weighing_scale_barcode').val().length > 0){
            pos_product_row(null, null, $('#weighing_scale_barcode').val());
            $('#weighing_scale_modal').modal('hide');
            $('input#weighing_scale_barcode').val('');
        } else{
            $('input#weighing_scale_barcode').focus();
        }
    });

    $(document).on('click', '#show_featured_products', function(){
        var $root = $(this).closest('.pos-sidebar-root');
        var $box = $root.length ? $root.find('#featured_products_box') : $('#featured_products_box');
        var emptyHtml = ($root.length ? $root.find('#featured_empty_state_template') : $('#featured_empty_state_template')).html() || '';
        if ($.trim($box.html()) === '') {
            $box.html(emptyHtml);
        }
        if (!$box.is(':visible')) {
            $box.css('display', 'flex').hide().fadeIn();
        } else {
            $box.fadeOut();
        }
    });
    validate_discount_field();
    set_payment_type_dropdown();
    if ($('#__is_mobile').length) {
        $('.pos_form_totals').css('margin-bottom', $('.pos-form-actions').height() - 30);
    }

    setInterval(function () {
        if ($('span.curr_datetime').length) {
            $('span.curr_datetime').html(__current_datetime());
        }
    }, 60000);

    set_search_fields();
});

function set_payment_type_dropdown() {
    var payment_settings = null;
    var locAttr = $('#location_id').attr('data-default_payment_accounts');
    if (locAttr) {
        try { payment_settings = JSON.parse(locAttr); } catch (err) { payment_settings = null; }
    }
    if (!payment_settings) {
        payment_settings = $('#location_id').data('default_payment_accounts');
    }
    if (!payment_settings && $('select#select_location_id').length) {
        var $opt = $('select#select_location_id').find(':selected');
        var optAttr = $opt.attr('data-default_payment_accounts');
        if (optAttr) {
            try { payment_settings = JSON.parse(optAttr); } catch (err2) { payment_settings = null; }
        }
        if (!payment_settings) {
            payment_settings = $opt.data('default_payment_accounts');
        }
    }
    payment_settings = payment_settings ? payment_settings : [];
    enabled_payment_types = [];
    for (var key in payment_settings) {
        if (payment_settings[key] && payment_settings[key]['is_enabled']) {
            enabled_payment_types.push(key);
        }
    }
    if (enabled_payment_types.length) {
        $(".payment_types_dropdown > option").each(function() {
            //skip if advance
            if ($(this).val() && $(this).val() != 'advance') {
                if (enabled_payment_types.indexOf($(this).val()) != -1) {
                    $(this).removeClass('hide');
                } else {
                    $(this).addClass('hide');
                }
            }
        });

        // Touch / mobile pay method buttons
        $('[data-touch-pay-method]').each(function () {
            var method = $(this).attr('data-touch-pay-method');
            if (enabled_payment_types.indexOf(method) != -1) {
                $(this).removeClass('hide');
            } else {
                $(this).addClass('hide');
            }
        });
        // Cash Full Pay follows cash enablement
        if ($('#vspos_cash_full_pay_btn').length) {
            if (enabled_payment_types.indexOf('cash') != -1) {
                $('#vspos_cash_full_pay_btn').removeClass('hide');
            } else {
                $('#vspos_cash_full_pay_btn').addClass('hide');
            }
        }
        // Classic express chips (non-touch)
        $('.vspos-pay-v2-chip--card').each(function () {
            $(this).toggleClass('hide', enabled_payment_types.indexOf('card') == -1);
        });
        $('.vspos-pay-v2-chip--bank').each(function () {
            $(this).toggleClass('hide', enabled_payment_types.indexOf('bank_transfer') == -1);
        });
        $('.vspos-pay-v2-chip--cheque').each(function () {
            $(this).toggleClass('hide', enabled_payment_types.indexOf('cheque') == -1);
        });
    }
}

function get_featured_products() {
    // The Featured Products button is always visible; when there are no featured products
    // the box is populated with the empty-state template so the click still shows a message.
    var $box = $('#featured_products_box');
    if ($box.length === 0) {
        return;
    }
    var emptyHtml = $('#featured_empty_state_template').html() || '';
    var location_id = $('#location_id').val();
    if (location_id) {
        $.ajax({
            method: 'GET',
            url: '/sells/pos/get-featured-products/' + location_id,
            dataType: 'html',
            success: function(result) {
                $box.html(result ? result : emptyHtml);
            },
            error: function() {
                $box.html(emptyHtml);
            },
        });
    } else {
        $box.html(emptyHtml);
    }
}

/* vspos-suggest-guard:start */
function vsposSuggestClassifyHtml(html) {
    html = html == null ? '' : String(html);
    var matches = html.match(/class=["']product_box/g);
    var count = matches ? matches.length : 0;
    var isEnd = html.indexOf('id="suggestion_end"') !== -1;
    var isEmpty = html.indexOf('id="no_products_found"') !== -1;
    return {
        html: html,
        count: count,
        hasProducts: count > 0,
        isEnd: isEnd,
        isEmpty: isEmpty,
        valid: count > 0 || isEnd || isEmpty
    };
}

function vsposSuggestShouldLoadNext(state) {
    if (!state) {
        return false;
    }
    if (state.ignoreScroll || state.loading || state.ended) {
        return false;
    }
    var scrollHeight = Number(state.scrollHeight) || 0;
    var clientHeight = Number(state.clientHeight) || 0;
    var scrollTop = Number(state.scrollTop) || 0;
    if (scrollHeight <= clientHeight + 8) {
        return false;
    }
    if (scrollTop + clientHeight < scrollHeight - 48) {
        return false;
    }
    return true;
}

function vsposSuggestApplyPlan(opts) {
    opts = opts || {};
    var pageSize = opts.pageSize || 50;
    if (opts.reqId !== opts.currentReqId) {
        return { action: 'ignore', end: !!opts.ended, stale: true };
    }
    var c = opts.classified || vsposSuggestClassifyHtml('');
    if (!c.valid) {
        if ((opts.page || 1) === 1) {
            return { action: 'error', end: false };
        }
        return { action: 'rewind', end: false };
    }
    if (opts.page > 1 && (c.isEnd || (c.isEmpty && !c.hasProducts))) {
        if (!opts.hasVisibleProducts) {
            return { action: 'recover', end: false };
        }
        return { action: 'ignore', end: true };
    }
    var reachedEnd = !c.hasProducts || c.count < pageSize;
    if ((opts.page || 1) === 1) {
        return { action: 'replace', end: reachedEnd, html: c.html };
    }
    if (c.hasProducts) {
        return { action: 'append', end: reachedEnd, html: c.html };
    }
    return { action: 'ignore', end: true };
}
/* vspos-suggest-guard:end */

function vsposSuggestLoadingHtml() {
    return '<div class="col-md-12 vspos-products-loading" id="vspos_products_loading">' +
        '<h4 class="text-center vspos-no-products-title">' +
        '<i class="fa fa-spinner fa-spin" aria-hidden="true"></i> Loading products...' +
        '</h4></div>';
}

function vsposSuggestErrorHtml() {
    return '<div class="col-md-12 vspos-no-products vspos-products-error">' +
        '<h4 class="text-center vspos-no-products-title">Could not load products.</h4>' +
        '<p class="text-center" style="margin-bottom:24px;">' +
        '<button type="button" class="btn btn-sm btn-default" id="vspos_retry_products">Retry</button>' +
        '</p></div>';
}

function vsposSuggestionItems() {
    return $('div#product_list_items').length ? $('div#product_list_items') : $('div#product_list_body');
}

function vsposSuggestionContext() {
    var is_enabled_stock = null;
    if ($('#is_enabled_stock').length) {
        is_enabled_stock = $('#is_enabled_stock').val();
    }
    var repair_model_id = null;
    if ($('#repair_model_id').length) {
        repair_model_id = $('#repair_model_id').val();
    }
    return {
        category_id: typeof global_p_category_id !== 'undefined' ? global_p_category_id : null,
        brand_id: typeof global_brand_id !== 'undefined' ? global_brand_id : null,
        location_id: $('input#location_id').val() || $('#location_id').val() || '',
        is_enabled_stock: is_enabled_stock,
        repair_model_id: repair_model_id
    };
}

function vsposMaybeFillSuggestionViewport() {
    if (window.__vspos_suggest_loading || window.__vspos_suggest_end || window.__vspos_suggest_ignore_scroll) {
        return;
    }
    var el = document.getElementById('product_list_body');
    if (!el || el.scrollHeight > el.clientHeight + 8) {
        return;
    }
    var page = parseInt($('#suggestion_page').val(), 10) || 1;
    if (page >= 8) {
        window.__vspos_suggest_end = true;
        return;
    }
    $('#suggestion_page').val(page + 1);
    var ctx = vsposSuggestionContext();
    get_product_suggestion_list(ctx.category_id, ctx.brand_id, ctx.location_id, null, ctx.is_enabled_stock, ctx.repair_model_id);
}

function get_product_suggestion_list(category_id, brand_id, location_id, url = null, is_enabled_stock = null, repair_model_id = null) {
    if ($('div#product_list_body').length == 0) {
        return false;
    }

    var $items_container = vsposSuggestionItems();

    if (url == null) {
        url = '/sells/pos/get-product-suggestion';
    }

    location_id = location_id || $('input#location_id').val() || $('#location_id').val() || '';
    if (!location_id) {
        window.__vspos_suggest_retry = (window.__vspos_suggest_retry || 0) + 1;
        if (window.__vspos_suggest_retry <= 15) {
            setTimeout(function () {
                get_product_suggestion_list(category_id, brand_id, null, url, is_enabled_stock, repair_model_id);
            }, 120);
        } else {
            window.__vspos_suggest_ignore_scroll = false;
            window.__vspos_suggest_loading = false;
            $items_container.html(vsposSuggestErrorHtml());
        }
        return false;
    }
    window.__vspos_suggest_retry = 0;

    var page = parseInt($('input#suggestion_page').val(), 10);
    if (!page || page < 1 || isNaN(page)) {
        page = 1;
        $('input#suggestion_page').val(1);
    }

    // A scroll event during a reset must not replace page 1 with an empty later page.
    if (page > 1 && (window.__vspos_suggest_loading || window.__vspos_suggest_end || window.__vspos_suggest_ignore_scroll)) {
        $('input#suggestion_page').val(Math.max(1, page - 1));
        return false;
    }

    if (page === 1) {
        window.__vspos_suggest_ignore_scroll = true;
        window.__vspos_suggest_end = false;
        if (!window.__vspos_suggest_recovering) {
            window.__vspos_suggest_recovered = false;
        }
        $items_container.html(vsposSuggestLoadingHtml());
        $('#suggestion_page_loader').stop(true, true).hide();
    } else {
        $('#suggestion_page_loader').stop(true, true).fadeIn(150);
    }

    window.__vspos_suggest_loading = true;
    window.__vspos_suggest_seq = (window.__vspos_suggest_seq || 0) + 1;
    var reqId = window.__vspos_suggest_seq;
    var reqPage = page;
    if (window.__vspos_suggest_xhr && window.__vspos_suggest_xhr.abort) {
        try { window.__vspos_suggest_xhr.abort(); } catch (e) {}
    }

    window.__vspos_suggest_xhr = $.ajax({
        method: 'GET',
        url: url,
        data: {
            category_id: category_id,
            brand_id: brand_id,
            location_id: location_id,
            page: page,
            is_enabled_stock: is_enabled_stock,
            repair_model_id: repair_model_id,
            for_pop: $('#is_pop_mode').length ? 1 : 0,
            price_group: $('#price_group').length ? ($('#price_group').val() || 0) : 0,
            term: (typeof window.global_p_search_term === 'string' && window.global_p_search_term !== '')
                ? window.global_p_search_term
                : (String($('#search_product').val() || '').trim() || undefined)
        },
        dataType: 'html',
        success: function (result) {
            if (reqId !== window.__vspos_suggest_seq) {
                return;
            }
            var classified = vsposSuggestClassifyHtml(result);
            var plan = vsposSuggestApplyPlan({
                reqId: reqId,
                currentReqId: window.__vspos_suggest_seq,
                page: reqPage,
                classified: classified,
                hasVisibleProducts: $items_container.find('.product_box').length > 0,
                ended: window.__vspos_suggest_end,
                pageSize: 50
            });
            if (plan.stale) {
                return;
            }

            window.__vspos_suggest_loading = false;
            $('#suggestion_page_loader').stop(true, true).hide();

            if (plan.action === 'error') {
                window.__vspos_suggest_ignore_scroll = false;
                window.__vspos_suggest_end = false;
                if (reqPage === 1) {
                    $items_container.html(vsposSuggestErrorHtml());
                } else {
                    $('input#suggestion_page').val(Math.max(1, reqPage - 1));
                }
                return;
            }
            if (plan.action === 'rewind') {
                window.__vspos_suggest_ignore_scroll = false;
                $('input#suggestion_page').val(Math.max(1, reqPage - 1));
                return;
            }
            if (plan.action === 'recover') {
                window.__vspos_suggest_ignore_scroll = false;
                if (window.__vspos_suggest_recovered) {
                    window.__vspos_suggest_end = true;
                    $items_container.html(vsposSuggestErrorHtml());
                    return;
                }
                window.__vspos_suggest_recovered = true;
                window.__vspos_suggest_recovering = true;
                $('input#suggestion_page').val(1);
                get_product_suggestion_list(category_id, brand_id, location_id, url, is_enabled_stock, repair_model_id);
                window.__vspos_suggest_recovering = false;
                return;
            }
            if (plan.action === 'ignore') {
                window.__vspos_suggest_ignore_scroll = false;
                window.__vspos_suggest_end = !!plan.end;
                return;
            }

            window.__vspos_suggest_end = !!plan.end;
            window.__vspos_suggest_ignore_scroll = true;
            if (plan.action === 'replace') {
                $items_container.html(plan.html);
            } else if (plan.action === 'append') {
                $items_container.find('#vspos_products_loading').remove();
                $items_container.append(plan.html);
            }
            window.__vspos_suggest_ignore_scroll = false;

            if (typeof vsposApplyPosPriceMode === 'function' && !$('#is_pop_mode').length) {
                vsposApplyPosPriceMode();
            }
            if (!plan.end && classified.hasProducts) {
                vsposMaybeFillSuggestionViewport();
            }
        },
        error: function (xhr, status) {
            if (status === 'abort' || reqId !== window.__vspos_suggest_seq) {
                return;
            }
            window.__vspos_suggest_loading = false;
            window.__vspos_suggest_ignore_scroll = false;
            $('#suggestion_page_loader').stop(true, true).hide();
            if (reqPage === 1) {
                $items_container.html(vsposSuggestErrorHtml());
            } else {
                $('input#suggestion_page').val(Math.max(1, reqPage - 1));
            }
        },
    });
}

$(document).on('click', '#vspos_retry_products', function (e) {
    e.preventDefault();
    window.__vspos_suggest_ignore_scroll = true;
    window.__vspos_suggest_end = false;
    window.__vspos_suggest_recovered = false;
    $('input#suggestion_page').val(1);
    var ctx = vsposSuggestionContext();
    get_product_suggestion_list(
        ctx.category_id,
        ctx.brand_id,
        ctx.location_id,
        null,
        ctx.is_enabled_stock,
        ctx.repair_model_id
    );
});

//Get recent transactions (POS) or recent purchases (POP)
function get_recent_transactions(status, element_obj) {
    if (element_obj.length == 0) {
        return false;
    }
    var is_pop = $('#is_pop_mode').length > 0;
    var url = is_pop ? '/purchase-pos/get-recent-purchases' : '/sells/pos/get-recent-transactions';
    var data = is_pop
        ? { location_id: $('input#location_id').val() }
        : { status: status, transaction_sub_type: $("#transaction_sub_type").val() };

    $.ajax({
        method: 'GET',
        url: url,
        data: data,
        dataType: 'html',
        success: function(result) {
            element_obj.html(result);
            __currency_convert_recursively(element_obj);
        },
    });
}

//variation_id is null when weighing_scale_barcode is used.

/**
 * Quick entry helpers: Search → Description → Qty → Rate → Add
 */
function vspos_entry_enabled() {
    return !!(window.VSPOS_QUICK_ENTRY || $('#vspos_quick_entry').length);
}

/**
 * After Qty/Rate confirm or product add, return focus to POS.
 * Do NOT reopen F8 bulk search — user must press F8 explicitly.
 */
function vspos_focus_return_after_entry() {
    var target = window.__vspos_return_focus_grid;
    window.__vspos_return_focus_grid = null;
    if (!target) {
        return false;
    }
    if (target === 'bulk') {
        // Stay on POS; focus barcode/product input. F8 opens search only when pressed.
        setTimeout(function () {
            if ($('#search_product').length) {
                $('#search_product').focus().select();
            }
        }, 80);
        return true;
    }
    setTimeout(function () {
        var el = target;
        if (typeof target === 'string') {
            el = document.querySelector('.product_box.is-grid-selected') ||
                document.querySelector('#product_list_body .product_box') ||
                document.querySelector('.product_box');
        }
        if (el && typeof el.focus === 'function') {
            try {
                el.focus({ preventScroll: true });
            } catch (err) {
                el.focus();
            }
            $(el).addClass('is-grid-selected');
        }
    }, 80);
    return true;
}
window.vspos_focus_return_after_entry = vspos_focus_return_after_entry;

function vspos_entry_clear(focus_search) {
    $('#vspos_entry_variation_id').val('');
    $('#vspos_entry_purchase_line_id').val('');
    $('#vspos_entry_enable_stock').val('');
    $('#vspos_entry_qty_available').val('');
    $('#vspos_entry_desc').val('');
    $('#vspos_entry_qty').val('1');
    $('#vspos_entry_rate').val('');
    $('#vspos_quick_entry').removeClass('is-ready');
    // Keep __vspos_return_focus_grid for pos_insert_product_row after AJAX add
    if (window.__vspos_return_focus_grid) {
        return;
    }
    if (focus_search !== false && !$('#__is_mobile').length) {
        $('input#search_product').focus().select();
    }
}

function vspos_entry_fill(item, purchase_line_id) {
    if (!item || !item.variation_id) {
        return false;
    }
    // Description = product name only (+ variation if needed)
    var name = item.name || '';
    if (item.type == 'variable' && item.variation) {
        name += ' - ' + item.variation;
    }

    var selling_price = item.variation_group_price || item.selling_price || 0;
    if (typeof selling_price === 'string') {
        selling_price = selling_price.replace(/,/g, '');
    }
    selling_price = parseFloat(selling_price) || 0;

    $('#vspos_entry_variation_id').val(item.variation_id);
    $('#vspos_entry_purchase_line_id').val(purchase_line_id || '');
    $('#vspos_entry_enable_stock').val(item.enable_stock);
    $('#vspos_entry_qty_available').val(item.qty_available);
    $('#vspos_entry_desc').val(name);
    $('#vspos_entry_qty').val(1);
    if (typeof __write_number === 'function') {
        __write_number($('#vspos_entry_rate'), selling_price);
    } else {
        $('#vspos_entry_rate').val(selling_price.toFixed(2));
    }
    $('#vspos_quick_entry').addClass('is-ready');
    $('input#search_product').val('');

    try {
        $('input#search_product').autocomplete('close');
    } catch (err) { /* ignore */ }

    setTimeout(function () {
        $('#vspos_entry_qty').focus().select();
    }, 30);
    return true;
}

/**
 * Lookup product code / name and fill Description + Qty + Rate (does NOT add to cart).
 * Exact SKU / single hit → fill. Never opens F8 (live autocomplete handles name search).
 */
function vspos_entry_lookup(term) {
    term = (term || '').trim();
    if (!term) {
        return;
    }
    if ($('input#location_id').val() == '') {
        toastr.warning(LANG.select_location);
        return;
    }

    var price_group = '';
    if ($('#price_group').length > 0) {
        price_group = $('#price_group').val();
    }
    if ($('#default_price_group').length > 0 && price_group === '') {
        price_group = $('#default_price_group').val();
    }
    if ($('#types_of_service_price_group').length > 0 && $('#types_of_service_price_group').val()) {
        price_group = $('#types_of_service_price_group').val();
    }

    var search_fields = [];
    $('.search_fields:checked').each(function (i) {
        search_fields[i] = $(this).val();
    });
    if (!search_fields.length) {
        search_fields = ['name', 'sku'];
    }

    var $search = $('input#search_product');
    if ($search.data('vspos_looking_up')) {
        return;
    }
    $search.data('vspos_looking_up', true);
    $('#vspos_quick_entry').addClass('is-looking');

    $.getJSON(
        '/products/list',
        {
            price_group: price_group,
            location_id: $('input#location_id').val(),
            term: term,
            not_for_selling: 0,
            search_fields: search_fields,
            auto_add_single: false,
            customer_id: $('select#customer_id').val(),
        },
        function (data) {
            $search.data('vspos_looking_up', false);
            $('#vspos_quick_entry').removeClass('is-looking');
            var products = data.products || data;
            if (typeof products === 'string') {
                try { products = JSON.parse(products); } catch (err) { products = []; }
            }
            if (!$.isArray(products)) {
                products = [];
            }
            products = products.filter(function (p) {
                return p && p.variation_id && !p.auto_added;
            });

            // Prefer exact SKU / sub_sku match
            var term_l = term.toLowerCase();
            var item = null;
            for (var i = 0; i < products.length; i++) {
                var p = products[i];
                var sku = String(p.sub_sku || p.sku || '').toLowerCase();
                if (sku === term_l) {
                    item = p;
                    break;
                }
            }
            // Numeric barcode/SKU: exact match only (avoid 0002 matching while intending 00021)
            var is_numeric_code = /^[0-9]{4,}$/.test(term);
            if (!item && products.length === 1 && !is_numeric_code) {
                item = products[0];
            }

            if (!item || !item.variation_id) {
                toastr.info(LANG.no_products_found || 'No matching products found');
                $search.focus().select();
                return;
            }

            var is_overselling_allowed = $('input#is_overselling_allowed').length > 0;
            var for_so = $('#sale_type').length && $('#sale_type').val() == 'sales_order';
            var is_draft = $('#status').length && ($('#status').val() == 'quotation' || $('#status').val() == 'draft');
            var is_pop = $('#is_pop_mode').length > 0;

            if (item.enable_stock == 1 && parseFloat(item.qty_available) <= 0 && !is_overselling_allowed && !for_so && !is_draft && !is_pop) {
                alert(LANG.out_of_stock);
                $search.focus().select();
                return;
            }

            window.__vspos_last_search_item = item;
            var purchase_line_id = item.purchase_line_id && term == item.lot_number ? item.purchase_line_id : null;
            vspos_entry_fill(item, purchase_line_id);
        }
    ).fail(function () {
        $search.data('vspos_looking_up', false);
        $('#vspos_quick_entry').removeClass('is-looking');
        toastr.error(LANG.something_went_wrong || 'Lookup failed');
        $search.focus().select();
    });
}

/** Exact barcode/SKU fill only — used by live search Enter fallback. Never opens F8. */
window.vspos_entry_lookup_exact_only = vspos_entry_lookup;

function vspos_entry_commit() {
    var variation_id = $('#vspos_entry_variation_id').val();
    if (!variation_id) {
        toastr.error((typeof LANG !== 'undefined' && LANG.no_products_found) ? LANG.no_products_found : 'Select a product first');
        $('input#search_product').focus();
        return;
    }

    var qtyRaw = $('#vspos_entry_qty').val();
    var qty = typeof __read_number === 'function'
        ? __read_number($('#vspos_entry_qty'))
        : parseFloat(String(qtyRaw || '').replace(/,/g, ''));
    if (qtyRaw === '' || qtyRaw === null || typeof qtyRaw === 'undefined' || isNaN(qty)) {
        qty = 1;
    } else if (qty === 0) {
        toastr.warning(
            (typeof LANG !== 'undefined' && LANG.inline_return_qty_zero)
                ? LANG.inline_return_qty_zero
                : 'Quantity cannot be zero'
        );
        $('#vspos_entry_qty').focus().select();
        return;
    }

    var rate = typeof __read_number === 'function'
        ? __read_number($('#vspos_entry_rate'))
        : parseFloat(String($('#vspos_entry_rate').val()).replace(/,/g, '')) || 0;

    var purchase_line_id = $('#vspos_entry_purchase_line_id').val() || null;
    if (purchase_line_id === '') {
        purchase_line_id = null;
    }

    window.__vspos_pending_entry_rate = rate;
    window.__vspos_pending_entry_qty = qty;
    window.__vspos_entry_committing = true;

    try {
        if (typeof __stock_price_management !== 'undefined' && __stock_price_management) {
            // Async — flag stays true until pos_check_price_and_add finishes
            pos_check_price_and_add(variation_id, purchase_line_id, qty);
            return;
        }
        pos_product_row(variation_id, purchase_line_id, null, qty);
        vspos_apply_pending_entry_to_last_row();
        vspos_entry_clear(true);
    } catch (err) {
        console.error(err);
        toastr.error('Could not add item');
    } finally {
        // Only clear here for sync path; async path clears itself
        if (!(typeof __stock_price_management !== 'undefined' && __stock_price_management)) {
            window.__vspos_entry_committing = false;
        }
    }
}

function vspos_sync_inline_return_row($row) {
    if (!$row || !$row.length) {
        return;
    }
    var $qty = $row.find('input.pos_quantity').first();
    var qty = typeof __read_number === 'function' ? __read_number($qty) : parseFloat($qty.val()) || 0;
    var label =
        typeof LANG !== 'undefined' && LANG.inline_return ? LANG.inline_return : 'RETURN';

    if (qty < 0) {
        $row.addClass('vspos-inline-return');
        $qty.attr('data-min', '-999999').data('min', -999999);
        $qty.removeAttr('data-rule-max-value').removeData('ruleMaxValue');
        if (!$row.find('.vspos-return-badge').length) {
            var $meta = $row.find('.vspos-line-meta').first();
            if ($meta.length) {
                $meta.prepend('<span class="vspos-return-badge">' + label + '</span> ');
            }
        }
    } else {
        $row.removeClass('vspos-inline-return');
        $qty.attr('data-min', '1').data('min', 1);
        $row.find('.vspos-return-badge').remove();
    }
}

function vspos_validate_inline_returns() {
    var zero_qty = false;
    $('table#pos_table tbody tr.product_row').each(function () {
        var q = __read_number($(this).find('input.pos_quantity'));
        if (q === 0) {
            zero_qty = true;
            return false;
        }
    });
    if (zero_qty) {
        toastr.warning(
            (typeof LANG !== 'undefined' && LANG.inline_return_qty_zero)
                ? LANG.inline_return_qty_zero
                : 'Quantity cannot be zero'
        );
        return false;
    }

    var total_payable = __read_number($('input#final_total_input'));
    if (total_payable < 0) {
        toastr.error(
            (typeof LANG !== 'undefined' && LANG.inline_return_exceeds_sale)
                ? LANG.inline_return_exceeds_sale
                : 'Return amount exceeds this sale. Reduce return qty or add sale items.'
        );
        return false;
    }
    return true;
}

function vspos_apply_pending_entry_to_last_row() {
    var qty = window.__vspos_pending_entry_qty;
    var rate = window.__vspos_pending_entry_rate;
    var $row = $('table#pos_table tbody tr.product_row').last();
    if (!$row.length) {
        return;
    }
    if (qty !== undefined && qty !== null && !isNaN(qty) && qty !== 1) {
        var $qty = $row.find('td input.pos_quantity, input.pos_quantity').first();
        if ($qty.length && typeof __write_number === 'function') {
            __write_number($qty, qty);
            $qty.trigger('change');
        }
    }
    // Apply RATE to the visible UNIT PRICE cell (not the modal's duplicate input)
    if (rate !== undefined && rate !== null && !isNaN(rate)) {
        var $price = $row.find('td.pos-td-unitprice input.pos_unit_price').first();
        if (!$price.length) {
            $price = $row.find('input.pos_unit_price').filter(function () {
                return $(this).closest('.row_edit_product_price_model, .modal').length === 0;
            }).first();
        }
        if ($price.length && typeof __write_number === 'function') {
            __write_number($price, rate);
            // Keep modal price in sync if present
            var $modalPrice = $row.find('.row_edit_product_price_model input.pos_unit_price');
            if ($modalPrice.length) {
                __write_number($modalPrice, rate);
            }
            // blur handler recalculates tax + line total from td unit price
            $price.trigger('change').trigger('blur');
        }
    }
    vspos_sync_inline_return_row($row);
    window.__vspos_pending_entry_rate = undefined;
    window.__vspos_pending_entry_qty = undefined;
}

function vspos_bind_quick_entry() {
    if (!vspos_entry_enabled() || window.__vspos_entry_bound) {
        return;
    }
    window.__vspos_entry_bound = true;

    // Capture-phase Enter on search — live autocomplete owns Enter when available
    var searchEl = document.getElementById('search_product');
    if (searchEl) {
        searchEl.addEventListener(
            'keydown',
            function (e) {
                if (e.key === 'Enter' || e.which === 13) {
                    // pos-live-search.js handles Enter (select suggestion / never open F8)
                    if (window.vsposLiveSearch) {
                        return;
                    }
                    e.preventDefault();
                    e.stopImmediatePropagation();
                    var term = (this.value || '').trim();
                    if (!term) {
                        return;
                    }
                    // Only fill Description / Qty / Rate — never open F8
                    vspos_entry_lookup(term);
                } else if (e.key === 'Escape' || e.which === 27) {
                    if ($('#vspos_entry_variation_id').val()) {
                        e.preventDefault();
                        e.stopImmediatePropagation();
                        vspos_entry_clear(true);
                    }
                }
            },
            true
        );
    }

    $(document).on('keydown', '#vspos_entry_desc', function (e) {
        if (e.which === 13 || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            if (!$('#vspos_entry_variation_id').val()) {
                $('input#search_product').focus();
                return;
            }
            $('#vspos_entry_qty').focus().select();
        } else if (e.which === 27) {
            e.preventDefault();
            vspos_entry_clear(true);
        }
    });

    $(document).on('keydown', '#vspos_entry_qty', function (e) {
        if (e.which === 13 || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            if (!$('#vspos_entry_variation_id').val()) {
                $('input#search_product').focus();
                return;
            }
            $('#vspos_entry_rate').focus().select();
        } else if (e.which === 27) {
            e.preventDefault();
            vspos_entry_clear(true);
        }
    });

    $(document).on('keydown', '#vspos_entry_rate', function (e) {
        if (e.which === 13 || e.key === 'Enter') {
            e.preventDefault();
            e.stopPropagation();
            vspos_entry_commit();
        } else if (e.which === 27) {
            e.preventDefault();
            vspos_entry_clear(true);
        }
    });

    $(document).on('input', '#search_product', function () {
        if ($(this).val() && $('#vspos_entry_variation_id').val()) {
            $('#vspos_entry_variation_id').val('');
            $('#vspos_entry_desc').val('');
            $('#vspos_entry_rate').val('');
            $('#vspos_entry_qty').val('1');
            $('#vspos_quick_entry').removeClass('is-ready');
        }
    });
}

/**
 * Common function to insert product row into POS table
 * @param {object} result - The result object containing html_content and other data
 */
function vspos_clear_pop_qty_stock_rules($scope) {
    if (!$('#is_pop_mode').length) {
        return;
    }
    var $inputs = ($scope && $scope.length ? $scope : $('table#pos_table'))
        .find('input.pos_quantity');
    $inputs.each(function () {
        var $qty = $(this);
        $qty.removeAttr('data-rule-max-value')
            .removeAttr('data-msg-max-value')
            .removeAttr('data-msg_max_default')
            .removeAttr('data-qty_available')
            .removeData('ruleMaxValue')
            .removeData('msgMaxValue')
            .removeData('msg_max_default')
            .removeData('qty_available')
            .attr('data-allow-overselling', 'true')
            .data('allow-overselling', true);
        $qty.closest('td, .pos-td-qty').find('label.error').remove();
        try {
            if ($qty.rules) {
                $qty.rules('remove', 'max-value');
            }
        } catch (e) {}
    });
}

function pos_insert_product_row(result) {
    pos_play_success_sound();
    var product_row = $('input#product_row_count').val();
    $('table#pos_table tbody')
        .append(result.html_content)
        .find('input.pos_quantity');
    pos_sync_empty_state();
    //increment row count
    $('input#product_row_count').val(parseInt(product_row) + 1);
    var this_row = $('table#pos_table tbody')
        .find('tr')
        .last();
    vspos_clear_pop_qty_stock_rules(this_row);
    pos_each_row(this_row);

    //For initial discount if present
    var line_total = __read_number(this_row.find('input.pos_line_total'));
    this_row.find('span.pos_line_total_text').text(line_total);

    pos_total_row();

    //Check if multipler is present then multiply it when a new row is added.
    if(__getUnitMultiplier(this_row) > 1){
        this_row.find('select.sub_unit').trigger('change');
    }

    // Never auto-open Unit Price / Discount / Description modal on normal Add.
    // Serial-per-qty / Mobile IMEI still open when those UIs are present on the row.
    // Cashier can always open the edit modal via the pen icon on the cart line.
    var new_row = $('table#pos_table tbody').find('tr').last();
    var needs_serial_entry = new_row.find('.vspos-serial-per-qty-wrap').length > 0
        || new_row.find('.vspos-mobile-imei-wrap').length > 0;
    if (needs_serial_entry) {
        new_row.find('.row_edit_product_price_model').modal('show');
    }

    round_row_to_iraqi_dinnar(this_row);
    __currency_convert_recursively(this_row);
    vspos_sync_inline_return_row(this_row);

    if (!$('#__is_mobile').length) {
        if (window.__vspos_return_focus_grid) {
            vspos_focus_return_after_entry();
        } else {
            $('input#search_product')
                .focus()
                .select();
        }
    }

    //Used in restaurant module
    if (result.html_modifier) {
        $('table#pos_table tbody')
            .find('tr')
            .last()
            .find('td:first')
            .append(result.html_modifier);
    }

    //scroll bottom of items list
    $(".pos_product_div").animate({ scrollTop: $('.pos_product_div').prop("scrollHeight")}, 1000);
}

// Helper function to add product row from server data
function pos_add_product_row_from_data(result) {
    // Hard gate: never auto-insert while quick entry is waiting for Qty/Rate confirm
    if (vspos_entry_enabled() && !window.__vspos_entry_committing) {
        var typed = ($('input#search_product').val() || '').trim();
        if (typed) {
            vspos_entry_lookup(typed);
        }
        return;
    }

    if (result.success) {
        var variation_id = result.variation_id;
        var add_new_row = true;
        
        // Check item addition method setting
        // POP: always new row so same product can be bought at different rates
        var item_addtn_method = 0;
        if ($('#is_pop_mode').length) {
            item_addtn_method = 0;
        } else if ($('#item_addition_method').length) {
            item_addtn_method = $('#item_addition_method').val();
        }
        
        // If item_addtn_method != 0, check for duplicate products (never merge returns)
        if (item_addtn_method != 0 && variation_id) {
            var pending_qty = parseFloat(window.__vspos_pending_entry_qty);
            if (isNaN(pending_qty)) {
                pending_qty = 1;
            }
            if (pending_qty >= 0) {
                var is_added = false;
                $('#pos_table tbody')
                    .find('tr')
                    .each(function() {
                        var row_v_id = $(this).find('.row_variation_id').val();
                        var enable_sr_no = $(this).find('.enable_sr_no').val();
                        var modifiers_exist = false;
                        if ($(this).find('input.modifiers_exist').length > 0) {
                            modifiers_exist = true;
                        }

                        var existing_qty = __read_number($(this).find('.pos_quantity'));
                        if (existing_qty < 0) {
                            return;
                        }

                        if (
                            row_v_id == variation_id &&
                            enable_sr_no !== '1' &&
                            !modifiers_exist &&
                            !is_added
                        ) {
                            add_new_row = false;
                            is_added = true;

                            var qty_element = $(this).find('.pos_quantity');
                            var qty = __read_number(qty_element);
                            var add_qty = pending_qty > 0 ? pending_qty : 1;
                            __write_number(qty_element, qty + add_qty);
                            qty_element.change();

                            round_row_to_iraqi_dinnar($(this));

                            if (!$('#__is_mobile').length) {
                                $('input#search_product')
                                    .focus()
                                    .select();
                            }
                        }
                    });
            }
        }
        
        // Add new row if not a duplicate
        if (add_new_row) {
            pos_insert_product_row(result);
        }
    } else {
        toastr.error(result.msg);
        if (!$('#__is_mobile').length) {
            $('input#search_product')
                .focus()
                .select();
        }
    }
}

function pos_check_price_and_add(variation_id, purchase_line_id, quantity) {
    quantity = quantity || window.__vspos_pending_entry_qty || 1;
    var location_id = $('input#location_id').val();
    // Keep commit open across this async request
    window.__vspos_entry_committing = true;
    $.ajax({
        url: '/purchases/get_variation_stock_prices',
        type: 'GET',
        dataType: 'json',
        data: { variation_id: variation_id, location_id: location_id },
        success: function(data) {
            var prices = data.prices || [];
            var uniquePrices = [];
            $.each(prices, function(i, p) {
                if (parseFloat(p.sell_price_inc_tax) > 0) {
                    uniquePrices.push(p);
                }
            });
            if (uniquePrices.length > 1) {
                pos_show_price_select_modal(variation_id, purchase_line_id, uniquePrices, quantity);
            } else {
                window.__vspos_entry_committing = true;
                pos_product_row(variation_id, purchase_line_id, null, quantity);
                if (typeof vspos_apply_pending_entry_to_last_row === 'function') {
                    vspos_apply_pending_entry_to_last_row();
                }
                if (typeof vspos_entry_clear === 'function') {
                    vspos_entry_clear(true);
                }
                window.__vspos_entry_committing = false;
            }
        },
        error: function() {
            window.__vspos_entry_committing = true;
            pos_product_row(variation_id, purchase_line_id, null, quantity);
            if (typeof vspos_apply_pending_entry_to_last_row === 'function') {
                vspos_apply_pending_entry_to_last_row();
            }
            if (typeof vspos_entry_clear === 'function') {
                vspos_entry_clear(true);
            }
            window.__vspos_entry_committing = false;
        }
    });
}

function pos_show_price_select_modal(variation_id, purchase_line_id, prices, quantity) {
    quantity = quantity || window.__vspos_pending_entry_qty || 1;
    $('#__price_select_modal').remove();
    var rows = '';
    $.each(prices, function(i, p) {
        var labelText = p.label ? '<span class="label label-info" style="margin-left:6px;">' + p.label + '</span>' : '';
        var qtyText = p.qty_available !== null ? ' <small class="text-muted">(Qty: ' + parseFloat(p.qty_available).toFixed(2) + ')</small>' : '';
        rows += '<button type="button" class="btn btn-default btn-block __psp_btn" data-price="' + p.sell_price_inc_tax + '" style="text-align:left;margin-bottom:6px;">' +
            '<strong>' + __currency_trans_from_en(p.sell_price_inc_tax, true) + '</strong>' + labelText + qtyText +
            '</button>';
    });
    var modal = '<div id="__price_select_modal" class="modal fade" tabindex="-1" role="dialog">' +
        '<div class="modal-dialog modal-sm" role="document">' +
        '<div class="modal-content">' +
        '<div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button>' +
        '<h4 class="modal-title">Select Selling Price</h4></div>' +
        '<div class="modal-body">' + rows + '</div>' +
        '</div></div></div>';
    $('body').append(modal);
    $('#__price_select_modal').modal('show');
    $('#__price_select_modal').on('click', '.__psp_btn', function() {
        var selected_price = parseFloat($(this).data('price'));
        $('#__price_select_modal').modal('hide');
        window.__vspos_entry_committing = true;
        window.__vspos_pending_entry_rate = selected_price;
        pos_product_row(variation_id, purchase_line_id, null, quantity);
        var afterAdd = setInterval(function() {
            var lastRow = $('table#pos_table tbody tr:last');
            var priceInput = lastRow.find('input.pos_unit_price_inc_tax');
            if (priceInput.length && lastRow.find('.row_variation_id').val() == variation_id) {
                clearInterval(afterAdd);
                __write_number(priceInput, selected_price);
                priceInput.trigger('change');
                var taxRate = lastRow.find('select.tax_id :selected').data('rate') || 0;
                if (!taxRate) {
                    taxRate = lastRow.find('.vspos-modal-tax-mirror :selected').data('rate') || 0;
                }
                var priceExc = taxRate > 0 ? selected_price / (1 + taxRate / 100) : selected_price;
                var excInput = vspos_row_unit_price_input(lastRow);
                __write_number(excInput, priceExc);
                var $modalPrice = lastRow.find('.vspos-modal-unit-price-mirror, .row_edit_product_price_model input.pos_unit_price');
                if ($modalPrice.length) {
                    __write_number($modalPrice, priceExc);
                }
                excInput.trigger('change').trigger('blur');
                if (typeof vspos_entry_clear === 'function') {
                    vspos_entry_clear(true);
                }
                window.__vspos_entry_committing = false;
            }
        }, 80);
        setTimeout(function() {
            clearInterval(afterAdd);
            window.__vspos_entry_committing = false;
        }, 3000);
    });
    $('#__price_select_modal').on('hidden.bs.modal', function() {
        $(this).remove();
        if ($('#vspos_entry_variation_id').val()) {
            window.__vspos_entry_committing = false;
        }
    });
}

function pos_product_row(variation_id = null, purchase_line_id = null, weighing_scale_barcode = null, quantity = 1) {

    // Hard gate: barcode/SKU must go through Qty → Rate → Enter before cart add
    // Never re-fill the entry bar here — that blocked Rate Enter from adding to cart
    if (vspos_entry_enabled() && !window.__vspos_entry_committing) {
        var typed = ($('input#search_product').val() || '').trim();
        if (typed) {
            if (typeof vspos_entry_lookup === 'function') {
                vspos_entry_lookup(typed);
            }
            return;
        }
        if (variation_id && !weighing_scale_barcode) {
            toastr.info('Confirm Qty & Rate, then press Enter on Rate to add');
            $('#vspos_entry_qty').focus().select();
            return;
        }
    }

    //Get item addition method
    var item_addtn_method = 0;
    var add_via_ajax = true;
    var incoming_qty = parseFloat(quantity);
    if (isNaN(incoming_qty)) {
        incoming_qty = 1;
    }
    // Negative qty = inline return: always add as its own line (never merge)
    var is_inline_return = incoming_qty < 0;

    if (variation_id != null && $('#item_addition_method').length) {
        item_addtn_method = $('#item_addition_method').val();
    }
    // POP: never merge — same product at different costs stays separate lines
    if ($('#is_pop_mode').length) {
        item_addtn_method = 0;
    }

    if (item_addtn_method == 0 || is_inline_return) {
        add_via_ajax = true;
    } else {
        var is_added = false;

        //Search for variation id in each row of pos table
        $('#pos_table tbody')
            .find('tr')
            .each(function() {
                var row_v_id = $(this)
                    .find('.row_variation_id')
                    .val();
                var enable_sr_no = $(this)
                    .find('.enable_sr_no')
                    .val();
                var modifiers_exist = false;
                if ($(this).find('input.modifiers_exist').length > 0) {
                    modifiers_exist = true;
                }

                var existing_qty = __read_number($(this).find('.pos_quantity'));
                // Do not merge into an existing return line
                if (existing_qty < 0) {
                    return;
                }

                if (
                    row_v_id == variation_id &&
                    enable_sr_no !== '1' &&
                    !modifiers_exist &&
                    !is_added
                ) {
                    add_via_ajax = false;
                    is_added = true;

                    //Increment product quantity (use requested qty when provided)
                    qty_element = $(this).find('.pos_quantity');
                    var qty = __read_number(qty_element);
                    var add_qty = incoming_qty > 0 ? incoming_qty : 1;
                    __write_number(qty_element, qty + add_qty);
                    qty_element.change();

                    round_row_to_iraqi_dinnar($(this));

                    if (!$('#__is_mobile').length) {
                        $('input#search_product')
                            .focus()
                            .select();
                    }
                }
        });
    }

    if (add_via_ajax) {
        var product_row = $('input#product_row_count').val();
        var location_id = $('input#location_id').val();
        var customer_id = $('select#customer_id').val();
        var is_direct_sell = false;
        if (
            $('input[name="is_direct_sale"]').length > 0 &&
            $('input[name="is_direct_sale"]').val() == 1
        ) {
            is_direct_sell = true;
        }

        var disable_qty_alert = false;

        if ($('#disable_qty_alert').length) {
            disable_qty_alert = true;
        }

        var is_sales_order = $('#sale_type').length && $('#sale_type').val() == 'sales_order' ? true : false;

        var price_group = '';
        if ($('#price_group').length > 0) {
            price_group = parseInt($('#price_group').val());
        }

        //If default price group present
        if ($('#default_price_group').length > 0 && 
            price_group === '') {
            price_group = $('#default_price_group').val();
        }

        //If types of service selected give more priority
        if ($('#types_of_service_price_group').length > 0 && 
            $('#types_of_service_price_group').val()) {
            price_group = $('#types_of_service_price_group').val();
        }

        var is_draft=false;
        if($('#status') && ($('#status').val()=='quotation' || 
        $('#status').val()=='draft')) {
            is_draft=true;
        }

        var is_serial_no = false;

        if (
            $('input[name="is_serial_no"]').length > 0 &&
            $('input[name="is_serial_no"]').val() == 1
        ) {
            is_serial_no = true;
        }
        
        $.ajax({
            method: 'GET',
            url: ($('#is_pop_mode').length ? '/purchase-pos/get_product_row/' : '/sells/pos/get_product_row/') + variation_id + '/' + location_id,
            async: false,
            data: {
                product_row: product_row,
                customer_id: customer_id,
                is_direct_sell: is_direct_sell,
                is_serial_no: is_serial_no,
                price_group: price_group,
                purchase_line_id: purchase_line_id,
                weighing_scale_barcode: weighing_scale_barcode,
                quantity: quantity,
                is_sales_order: is_sales_order,
                disable_qty_alert: $('#is_pop_mode').length ? true : disable_qty_alert,
                is_draft: is_draft,
                for_pop: $('#is_pop_mode').length ? 1 : 0
            },
            dataType: 'json',
            success: function(result) {
                if (result.success) {
                    pos_insert_product_row(result);
                } else {
                    toastr.error(result.msg);
                    if (!$('#__is_mobile').length) {
                        $('input#search_product')
                            .focus()
                            .select();
                    }
                }
            },
        });
    }
}

/** Canonical POS row unit price (visible cell) — never the edit-modal mirror. */
function vspos_row_unit_price_input(row) {
    var $row = $(row);
    var $price = $row.find('td.pos-td-unitprice input.pos_unit_price').first();
    if ($price.length) {
        return $price;
    }
    return $row.find('input.pos_unit_price').filter(function () {
        return $(this).closest('.row_edit_product_price_model, .modal').length === 0;
    }).first();
}

//Update values for each row
function pos_each_row(row_obj) {
    var unit_price = __read_number(vspos_row_unit_price_input(row_obj));

    var discounted_unit_price = calculate_discounted_unit_price(row_obj);
    var $taxSelect = row_obj.find('td select.tax_id').first();
    var tax_rate = 0;
    if ($taxSelect.length) {
        tax_rate = $taxSelect.find(':selected').data('rate');
    } else {
        var $modal = row_obj.find('.row_edit_product_price_model');
        if ($modal.length) {
            tax_rate = $modal.find('.vspos-modal-tax-mirror :selected').data('rate') || 0;
        }
    }

    var unit_price_inc_tax =
        discounted_unit_price + __calculate_amount('percentage', tax_rate, discounted_unit_price);
    __write_number(row_obj.find('td input.pos_unit_price_inc_tax').first(), unit_price_inc_tax);

    var qty = __read_number(row_obj.find('input.pos_quantity'));
    var line_total = qty * unit_price_inc_tax;
    __write_number(row_obj.find('td input.pos_line_total').length ? row_obj.find('td input.pos_line_total').first() : row_obj.find('input.pos_line_total').first(), line_total);
    row_obj.find('span.pos_line_total_text').text(__currency_trans_from_en(line_total, true));

    //var unit_price_inc_tax = __read_number(row_obj.find('input.pos_unit_price_inc_tax'));

    __write_number(row_obj.find('input.item_tax'), unit_price_inc_tax - discounted_unit_price);

}

function pos_total_row() {
    var total_quantity = 0;
    var price_total = get_subtotal();
    $('table#pos_table tbody tr').each(function() {
        total_quantity = total_quantity + __read_number($(this).find('input.pos_quantity'));
    });

    //updating shipping charges
    $('span#shipping_charges_amount').text(
        __currency_trans_from_en(__read_number($('input#shipping_charges_modal')), false)
    );

    $('span.total_quantity').each(function() {
        $(this).html(__number_f(total_quantity));
    });

    //$('span.unit_price_total').html(unit_price_total);
    $('span.price_total').html(__currency_trans_from_en(price_total, false));
    calculate_billing_details(price_total);

    if (
        $('input[name="is_serial_no"]').length > 0 &&
        $('input[name="is_serial_no"]').val() == 1
    ) {
        update_serial_no();
    }
    // store on any update
    saveFormDataToLocalStorage(true);

}

function get_subtotal() {
    var price_total = 0;

    $('table#pos_table tbody tr').each(function() {
        price_total = price_total + __read_number($(this).find('input.pos_line_total'));
    });

    //Go through the modifier prices.
    $('input.modifiers_price').each(function() {
        var modifier_price = __read_number($(this));
        var modifier_quantity = $(this).closest('.product_modifier').find('.modifiers_quantity').val();
        var modifier_subtotal = modifier_price * modifier_quantity;
        price_total = price_total + modifier_subtotal;
    });

    return price_total;
}

function calculate_billing_details(price_total) {
    var discount = pos_discount(price_total);
    if ($('#reward_point_enabled').length) {
        total_customer_reward = $('#rp_redeemed_amount').val();
        discount = parseFloat(discount) + parseFloat(total_customer_reward);

        // Discount cell shows pure discount (set by pos_discount()); loyalty cell shows reward amount separately
        if ($('input[name="is_direct_sale"]').length <= 0) {
            $('span#loyalty_amount_display').text(__currency_trans_from_en(total_customer_reward, false));
        }
    }

    var order_tax = pos_order_tax(price_total, discount);

    //Add shipping charges.
    var shipping_charges = __read_number($('input#shipping_charges'));

    var additional_expense = 0;
    //calculate additional expenses
    if ($('input#additional_expense_value_1').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_1'));
    }
    if ($('input#additional_expense_value_2').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_2'))
    }
    if ($('input#additional_expense_value_3').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_3'))
    }
    if ($('input#additional_expense_value_4').length > 0) {
        additional_expense += __read_number($('input#additional_expense_value_4'))
    }

    //Add packaging charge
    var packing_charge = 0;
    if ($('#types_of_service_id').length > 0 && 
            $('#types_of_service_id').val()) {
        packing_charge = __calculate_amount($('#packing_charge_type').val(), 
            __read_number($('input#packing_charge')), price_total);

        $('#packing_charge_text').text(__currency_trans_from_en(packing_charge, false));
    }

    var total_payable = price_total + order_tax - discount + shipping_charges + packing_charge + additional_expense;

    var rounding_multiple = $('#amount_rounding_method').val() ? parseFloat($('#amount_rounding_method').val()) : 0;
    var round_off_data = __round(total_payable, rounding_multiple);
    var total_payable_rounded = round_off_data.number;

    var round_off_amount = round_off_data.diff;
    if (round_off_amount != 0) {
        $('span#round_off_text').text(__currency_trans_from_en(round_off_amount, false));
    } else {
        $('span#round_off_text').text(0);
    }
    $('input#round_off_amount').val(round_off_amount);

    __write_number($('input#final_total_input'), total_payable_rounded);
    var curr_exchange_rate = 1;
    if ($('#exchange_rate').length > 0 && $('#exchange_rate').val()) {
        curr_exchange_rate = __read_number($('#exchange_rate'));
    }
    var shown_total = total_payable_rounded * curr_exchange_rate;
    $('span#total_payable').text(__currency_trans_from_en(shown_total, false));

    $('span.total_payable_span').text(__currency_trans_from_en(total_payable_rounded, true));

    //Check if edit form then don't update price.
    if ($('form#edit_pos_sell_form').length == 0 && $('form#edit_sell_form').length == 0) {
        __write_number($('.payment-amount').first(), total_payable_rounded);
    }

    $(document).trigger('invoice_total_calculated');

    calculate_balance_due();
}

function get_total_line_discount() {
    var total_line_discount = 0;
    // Only real cart lines — skip .pos-empty-state-row (no discount/price inputs)
    $('table#pos_table tbody tr.product_row').each(function() {
        var row = $(this);
        var $discInputTd = row.find('td input.row_discount_amount');
        var $discInput = $discInputTd.length ? $discInputTd.first() : row.find('input.row_discount_amount').first();
        var $priceInputTd = row.find('td input.pos_unit_price');
        var $priceInput = $priceInputTd.length ? $priceInputTd.first() : row.find('input.pos_unit_price').first();
        var row_discount_amount = __read_number($discInput);
        if (row_discount_amount > 0) {
            var original_price = __read_number($priceInput);
            var discounted_price = calculate_discounted_unit_price(row);
            var qty = __read_number(row.find('input.pos_quantity'));
            total_line_discount += (original_price - discounted_price) * qty;
        }
    });
    return total_line_discount;
}

function pos_discount(total_amount) {
    var calculation_type = $('#discount_type').val();
    var calculation_amount = __read_number($('#discount_amount'));

    var order_discount = __calculate_amount(calculation_type, calculation_amount, total_amount);
    var line_discount = get_total_line_discount();

    /* Update combined bottom-bar display */
    $('span#total_discount').text(__currency_trans_from_en(order_discount + line_discount, false));

    /* Update ORDER DETAILS panel separate rows */
    $('#op_item_discount_val').text(__currency_trans_from_en(line_discount, false));
    $('#op_bill_discount_val').text(__currency_trans_from_en(order_discount, false));

    return order_discount;
}

function pos_order_tax(price_total, discount) {
    var tax_rate_id = $('#tax_rate_id').val();
    var calculation_type = 'percentage';
    var calculation_amount = __read_number($('#tax_calculation_amount'));
    var total_amount = price_total - discount;

    if (tax_rate_id) {
        var order_tax = __calculate_amount(calculation_type, calculation_amount, total_amount);
    } else {
        var order_tax = 0;
    }

    $('span#order_tax').text(__currency_trans_from_en(order_tax, false));

    return order_tax;
}

function calculate_balance_due() {
    var total_payable = __read_number($('#final_total_input'));
    var total_paying = 0;
    $('#payment_rows_div')
        .find('.payment-amount')
        .each(function() {
            if (parseFloat($(this).val())) {
                total_paying += __read_number($(this));
            }
        });
    var bal_due = total_payable - total_paying;
    var change_return = 0;

    //change_return
    if (bal_due < 0 || Math.abs(bal_due) < 0.05) {
        __write_number($('input#change_return'), bal_due * -1);
        $('span.change_return_span').text(__currency_trans_from_en(bal_due * -1, true));
        change_return = bal_due * -1;
        bal_due = 0;
    } else {
        __write_number($('input#change_return'), 0);
        $('span.change_return_span').text(__currency_trans_from_en(0, true));
        change_return = 0;
        
    }

    if (change_return !== 0) {
        $('#change_return_payment_data').removeClass('hide');
    } else {
        $('#change_return_payment_data').addClass('hide');
    }

    __write_number($('input#total_paying_input'), total_paying);
    $('span.total_paying').text(__currency_trans_from_en(total_paying, true));

    __write_number($('input#in_balance_due'), bal_due);
    $('span.balance_due').text(__currency_trans_from_en(bal_due, true));

    __highlight(bal_due * -1, $('span.balance_due'));
    __highlight(change_return * -1, $('span.change_return_span'));
    // store payment details
    saveFormDataToLocalStorage(true);
}

function isValidPosForm() {
    flag = true;
    $('span.error').remove();

    if ($('select#customer_id').val() == null) {
        flag = false;
        error = '<span class="error">' + LANG.required + '</span>';
        $(error).insertAfter($('select#customer_id').parent('div'));
    }

    if ($('tr.product_row').length == 0) {
        flag = false;
        error = '<span class="error">' + LANG.no_products + '</span>';
        $(error).insertAfter($('input#search_product').parent('div'));
    }

    return flag;
}

function pos_navigate_to_fresh_pos() {
    if (window.__pos_fresh_nav_done) {
        return;
    }
    var url = $('input#pos_redirect_url').val();
    if (!url) {
        return;
    }
    window.__pos_fresh_nav_done = true;
    window.onbeforeunload = null;
    window.location = url;
}

function pos_focus_for_next_sale() {
    if (typeof pos_sync_empty_state === 'function') {
        pos_sync_empty_state();
    }
    if (typeof enable_pos_form_actions === 'function') {
        enable_pos_form_actions();
    }
    var search = document.getElementById('search_product');
    if (search && !search.disabled) {
        try {
            search.focus();
        } catch (e) {}
    }
}

function reset_pos_form(opts){
    opts = opts || {};

	//If on edit page then redirect to Add POS page
	if($('form#edit_pos_sell_form').length > 0){
        if (opts.deferRedirect) {
            return true;
        }
        if (opts.redirectNow) {
            pos_navigate_to_fresh_pos();
            return true;
        }
		setTimeout(function() {
			pos_navigate_to_fresh_pos();
		}, 4000);
		return true;
	}
	
    //reset all repair defects tags
    if ($("#repair_defects").length > 0) {
        tagify_repair_defects.removeAllTags();
    }

	if(pos_form_obj[0]){
		pos_form_obj[0].reset();
	}
	if(sell_form[0]){
		sell_form[0].reset();
	}
	set_default_customer();
	set_location();

	$('tr.product_row').remove();
	$('span.total_quantity, span.price_total, span#total_discount, span#order_tax, span#total_payable, span#shipping_charges_amount, span#loyalty_amount_display').text(0);
	$('span.total_payable_span', 'span.total_paying', 'span.balance_due').text(0);

	$('#modal_payment').find('.remove_payment_row').each( function(){
		$(this).closest('.payment_row').remove();
	});

    if ($('#is_credit_sale').length) {
        $('#is_credit_sale').val(0);
    }

	//Reset discount
	__write_number($('input#discount_amount'), $('input#discount_amount').data('default'));
	$('input#discount_type').val($('input#discount_type').data('default'));

	//Reset tax rate
	$('input#tax_rate_id').val($('input#tax_rate_id').data('default'));
	__write_number($('input#tax_calculation_amount'), $('input#tax_calculation_amount').data('default'));

	$('select.payment_types_dropdown').val('cash').trigger('change');
	$('#price_group').trigger('change');
	if (typeof vspos_clear_exchange_state === 'function') {
		vspos_clear_exchange_state(true);
	}

	//Reset shipping
	__write_number($('input#shipping_charges'), $('input#shipping_charges').data('default'));
	$('input#shipping_details').val($('input#shipping_details').data('default'));
    $('input#shipping_address, input#shipping_status, input#delivered_to').val('');
	if($('input#is_recurring').length > 0){
		$('input#is_recurring').iCheck('update');
	};
    if($('input#is_kitchen_order').length > 0){
		$('input#is_kitchen_order').iCheck('update');
	};
    if($('#invoice_layout_id').length > 0){
        $('#invoice_layout_id').trigger('change');
    };
    $('span#round_off_text').text(0);

    //repair module extra  fields reset
    if ($('#repair_device_id').length > 0) {
        $('#repair_device_id').val('').trigger('change');
    }

    //Status is hidden in sales order
    if ($('#status').length > 0 && $('#status').is(":visible")) {
        $('#status').val('').trigger('change');
    }
    if ($('#transaction_date').length > 0) {
        $('#transaction_date').data("DateTimePicker").date(moment());
    }
    if ($('.paid_on').length > 0) {
        $('.paid_on').data("DateTimePicker").date(moment());
    }
    if ($('#commission_agent').length > 0) {
        $('#commission_agent').val('').trigger('change');
    } 

    //reset contact due
    $('.contact_due_text').find('span').text('');
    $('.contact_due_text').addClass('hide');

    $(document).trigger('sell_form_reset');

    // Set global_is_clear_local_storage to true to clear local storage
    global_is_clear_local_storage = true;
    saveFormDataToLocalStorage();
}

function set_default_customer() {
    var default_customer_id = $('#default_customer_id').val();
    var default_customer_name = $('#default_customer_name').val();
    var default_customer_balance = $('#default_customer_balance').val();
    var default_customer_address = $('#default_customer_address').val();
    var exists = default_customer_id ? $('select#customer_id option[value=' + default_customer_id + ']').length : 0;
    if (exists == 0 && default_customer_id) {
        $('select#customer_id').append(
            $('<option>', { value: default_customer_id, text: default_customer_name })
        );
    }
    $('#advance_balance_text').text(__currency_trans_from_en(default_customer_balance), true);
    $('#advance_balance').val(default_customer_balance);
    if (typeof updatePosCustomerBalanceBadges === 'function') {
        updatePosCustomerBalanceBadges({ due: '', advance: 0 });
    }
    $('#shipping_address_modal').val(default_customer_address);
    if (default_customer_address) {
        $('#shipping_address').val(default_customer_address);
    }
    $('select#customer_id')
        .val(default_customer_id)
        .trigger('change');

    if ($('#default_selling_price_group').length) {
        $('#price_group').val($('#default_selling_price_group').val());
        $('#price_group').change();
    }

    //initialize tags input (tagify)
    if ($("textarea#repair_defects").length > 0 && !customer_set) {
        let suggestions = [];
        if ($("input#pos_repair_defects_suggestion").length > 0 && $("input#pos_repair_defects_suggestion").val().length > 2) {
            suggestions = JSON.parse($("input#pos_repair_defects_suggestion").val());    
        }
        let repair_defects = document.querySelector('textarea#repair_defects');
        tagify_repair_defects = new Tagify(repair_defects, {
                  whitelist: suggestions,
                  maxTags: 100,
                  dropdown: {
                    maxItems: 100,           // <- mixumum allowed rendered suggestions
                    classname: "tags-look", // <- custom classname for this dropdown, so it could be targeted
                    enabled: 0,             // <- show suggestions on focus
                    closeOnSelect: false    // <- do not hide the suggestions dropdown once an item has been selected
                  }
                });
    }

    customer_set = true;
}

//Set the location and initialize printer
function set_location() {
    if ($('select#select_location_id').length == 1) {
        $('input#location_id').val($('select#select_location_id').val());
        $('input#location_id').data(
            'receipt_printer_type',
            $('select#select_location_id')
                .find(':selected')
                .data('receipt_printer_type')
        );
        var __dpa = $('select#select_location_id')
            .find(':selected')
            .attr('data-default_payment_accounts');
        if (__dpa === undefined || __dpa === null || __dpa === '') {
            __dpa = $('select#select_location_id')
                .find(':selected')
                .data('default_payment_accounts');
        }
        // Do not wipe existing location defaults when the select option has none
        if (__dpa !== undefined && __dpa !== null && __dpa !== '') {
            var __dpaObj = __dpa;
            if (typeof __dpa === 'string') {
                try { __dpaObj = JSON.parse(__dpa); } catch (err) { __dpaObj = __dpa; }
            }
            $('input#location_id').data('default_payment_accounts', __dpaObj);
            $('input#location_id').attr('data-default_payment_accounts',
                typeof __dpa === 'string' ? __dpa : JSON.stringify(__dpaObj));
        }

        $('input#location_id').attr(
            'data-default_price_group',
            $('select#select_location_id')
                .find(':selected')
                .data('default_price_group')
        );
    }

    if ($('input#location_id').val()) {
        $('input#search_product')
            .prop('disabled', false);
        if (!$('#__is_mobile').length) {
            $('input#search_product').focus();
        }
    } else {
        $('input#search_product').prop('disabled', true);
    }

    initialize_printer();
}

function initialize_printer() {
    if ($('input#location_id').data('receipt_printer_type') == 'printer') {
        initializeSocket();
    }
}

$('body').on('click', 'label', function(e) {
    var field_id = $(this).attr('for');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

$('body').on('focus', 'select', function(e) {
    var field_id = $(this).attr('id');
    if (field_id) {
        if ($('#' + field_id).hasClass('select2')) {
            $('#' + field_id).select2('open');
            return false;
        }
    }
});

function round_row_to_iraqi_dinnar(row) {
    if (iraqi_selling_price_adjustment) {
        var element = row.find('input.pos_unit_price_inc_tax');
        var unit_price = round_to_iraqi_dinnar(__read_number(element));
        __write_number(element, unit_price);
        element.change();
    }
}

function pos_apply_receipt_return(decision) {
    if (!decision || !decision.closeUi || decision.duplicate) {
        return;
    }
    if (typeof window.vsposCloseSaleComplete === 'function') {
        window.vsposCloseSaleComplete();
    }
    if (typeof __vspos_close_print_preview === 'function') {
        __vspos_close_print_preview();
    }
    if (typeof window.vsposClosePayPopup === 'function') {
        window.vsposClosePayPopup();
    }
    try {
        $('#modal_payment').modal('hide');
    } catch (e) {}
    $('.modal-backdrop').remove();
    $('body').removeClass('modal-open vspos-pp-open vspos-pp-printing vspos-sc-open vspos-mobile-pay-open vspos-mpos-cart-open printing-receipt');

    if (decision.navigate) {
        pos_navigate_to_fresh_pos();
        return;
    }
    pos_focus_for_next_sale();
}

function pos_send_print_bridge(receipt) {
    if (window.__pos_print_bridge_busy) {
        return Promise.resolve({
            ok: false,
            duplicate: true,
            message: ''
        });
    }
    window.__pos_print_bridge_busy = true;

    return new Promise(function (resolve) {
        var settled = false;
        var connectionTimer = null;
        var activeSocket = null;

        function finish(ok, message, duplicate) {
            if (settled) {
                return;
            }
            settled = true;
            window.__pos_print_bridge_busy = false;
            if (connectionTimer) {
                clearTimeout(connectionTimer);
            }
            if (activeSocket) {
                activeSocket.removeEventListener('open', onOpen);
                activeSocket.removeEventListener('error', onUnavailable);
                activeSocket.removeEventListener('close', onUnavailable);
            }
            resolve({
                ok: !!ok,
                duplicate: !!duplicate,
                message: message || ''
            });
        }

        function payload() {
            var content = {};
            var key;
            for (key in receipt) {
                if (Object.prototype.hasOwnProperty.call(receipt, key)) {
                    content[key] = receipt[key];
                }
            }
            content.type = 'print-receipt';
            return JSON.stringify(content);
        }

        function sendNow() {
            try {
                activeSocket.send(payload());
                finish(true);
            } catch (err) {
                finish(false, 'The receipt could not be sent to the printer.');
            }
        }

        function onOpen() {
            sendNow();
        }

        function onUnavailable() {
            finish(false, 'The receipt printer is unavailable.');
        }

        try {
            if (typeof initializeSocket !== 'function') {
                finish(false, 'Print Bridge is not available.');
                return;
            }
            if (typeof socket === 'undefined' || socket == null || socket.readyState === 3) {
                try {
                    if (typeof socket !== 'undefined' && socket && socket.readyState === 3) {
                        socket = null;
                    }
                } catch (ignore) {}
                initializeSocket();
            }
            activeSocket = typeof socket === 'undefined' ? null : socket;
            if (!activeSocket) {
                finish(false, 'The receipt printer is unavailable.');
                return;
            }
            if (activeSocket.readyState === 1) {
                sendNow();
                return;
            }
            if (activeSocket.readyState !== 0) {
                finish(false, 'The receipt printer is unavailable.');
                return;
            }
            activeSocket.addEventListener('open', onOpen);
            activeSocket.addEventListener('error', onUnavailable);
            activeSocket.addEventListener('close', onUnavailable);
            connectionTimer = setTimeout(function () {
                finish(false, 'The receipt printer is unavailable.');
            }, 2000);
        } catch (err) {
            finish(false, 'The receipt printer is unavailable.');
        }
    });
}

function pos_print(receipt) {
    var postSale = window.PosReceiptReturn && PosReceiptReturn.isActive();

    function finishPostSale(result) {
        if (!postSale || !window.PosReceiptReturn || !result || result.duplicate) {
            return;
        }
        if (!result.ok && window.toastr && result.message) {
            toastr.warning(result.message);
        }
        PosReceiptReturn.handleAction(result.ok ? 'print' : 'print-failed');
    }

    if (!receipt) {
        return false;
    }

    // Configured receipt printer (QPOS Print Bridge). Never wait forever if it is down.
    if (receipt.print_type == 'printer') {
        if (postSale && window.PosReceiptReturn) {
            PosReceiptReturn.handleDismiss();
        }
        pos_send_print_bridge(receipt).then(finishPostSale);
        return 'async';
    }

    if (receipt.html_content != '') {
        var title = document.title;
        var printLabel = (typeof receipt.print_title != 'undefined' && receipt.print_title)
            ? receipt.print_title
            : 'Receipt';

        // Single receipt target (DOM can contain more than one #receipt_section)
        var $section = $('main > #receipt_section').first();
        if (!$section.length) {
            $section = $('#receipt_section').first();
        }
        // Clear any other stale receipt sections so preview can't pick empty clones
        $('#receipt_section').not($section).empty();

        $section.html(receipt.html_content);
        __currency_convert_recursively($section);

        var immediate =
            !!(receipt.immediate_print) ||
            ($('#handheld_immediate_print').length > 0 && $('#handheld_immediate_print').val() == '1');

        if (immediate && typeof __vspos_print_html_immediate === 'function') {
            __vspos_print_html_immediate($section.html(), {
                paper_width: receipt.paper_width || null,
                paper_height: receipt.paper_height || null,
                design: receipt.design || receipt.paper_label || null
            }, finishPostSale, function () {
                if (postSale && window.PosReceiptReturn && PosReceiptReturn.isActive()) {
                    PosReceiptReturn.handleDismiss();
                }
            });
            return 'async';
        } else if (typeof __vspos_show_print_preview === 'function') {
            __vspos_show_print_preview($section.html(), printLabel, receipt.paper_width || null, {
                paper_height: receipt.paper_height || null,
                design: receipt.design || null,
                paper_label: receipt.paper_label || null
            });
            if (postSale) {
                PosReceiptReturn.markUi({ previewOpen: true });
            }
            return 'preview';
        } else {
            if (typeof receipt.print_title != 'undefined') {
                document.title = receipt.print_title;
            }
            __print_receipt($section.attr('id') || 'receipt_section');
            setTimeout(function() {
                document.title = title;
            }, 1200);
            if (postSale) {
                PosReceiptReturn.markUi({ previewOpen: true });
            }
            return 'preview';
        }
    }

    return false;
}

if (window.PosReceiptReturn) {
    PosReceiptReturn.setHandler(pos_apply_receipt_return);
}

function calculate_discounted_unit_price(row) {
    var this_unit_price = __read_number(vspos_row_unit_price_input(row));
    var row_discounted_unit_price = this_unit_price;
    var $rdtTd3 = row.find('td select.row_discount_type');
    var row_discount_type = $rdtTd3.length ? $rdtTd3.first().val() : row.find('select.row_discount_type').first().val();
    var $rdaTd3 = row.find('td input.row_discount_amount');
    var row_discount_amount = __read_number($rdaTd3.length ? $rdaTd3.first() : row.find('input.row_discount_amount').first());
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            row_discounted_unit_price = this_unit_price - row_discount_amount;
        } else {
            row_discounted_unit_price = __substract_percent(this_unit_price, row_discount_amount);
        }
    }

    return row_discounted_unit_price;
}

function get_unit_price_from_discounted_unit_price(row, discounted_unit_price) {
    var this_unit_price = discounted_unit_price;
    var $rdtTd = row.find('td select.row_discount_type');
    var row_discount_type = $rdtTd.length ? $rdtTd.first().val() : row.find('select.row_discount_type').first().val();
    var $rdaTd = row.find('td input.row_discount_amount');
    var row_discount_amount = __read_number($rdaTd.length ? $rdaTd.first() : row.find('input.row_discount_amount').first());
    if (row_discount_amount) {
        if (row_discount_type == 'fixed') {
            this_unit_price = discounted_unit_price + row_discount_amount;
        } else {
            this_unit_price = __get_principle(discounted_unit_price, row_discount_amount, true);
        }
    }

    return this_unit_price;
}

//Update quantity if line subtotal changes
$('table#pos_table tbody').on('change', 'input.pos_line_total', function() {

    var subtotal = __read_number($(this));
    var tr = $(this).parents('tr');
    var quantity_element = tr.find('input.pos_quantity');
    var unit_price_inc_tax = __read_number(tr.find('input.pos_unit_price_inc_tax'));
    var quantity = subtotal / unit_price_inc_tax;
    __write_number(quantity_element, quantity);

    __write_number($(this), subtotal, false);


    if (sell_form_validator) {
        sell_form_validator.element(quantity_element);
    }
    if (pos_form_validator) {
        pos_form_validator.element(quantity_element);
    }
    tr.find('span.pos_line_total_text').text(__currency_trans_from_en(subtotal, true));

    pos_total_row();
});

function vsposBindSuggestionScroll() {
    var $list = $('div#product_list_body');
    if (!$list.length || $list.data('vsposSuggestScroll')) {
        return;
    }
    $list.data('vsposSuggestScroll', 1);
    $list.on('scroll.vsposSuggest', function () {
        if (!vsposSuggestShouldLoadNext({
            ignoreScroll: window.__vspos_suggest_ignore_scroll,
            loading: window.__vspos_suggest_loading,
            ended: window.__vspos_suggest_end,
            scrollTop: this.scrollTop,
            scrollHeight: this.scrollHeight,
            clientHeight: this.clientHeight
        })) {
            return;
        }
        var page = parseInt($('#suggestion_page').val(), 10) || 1;
        $('#suggestion_page').val(page + 1);
        var ctx = vsposSuggestionContext();
        get_product_suggestion_list(
            ctx.category_id,
            ctx.brand_id,
            ctx.location_id,
            null,
            ctx.is_enabled_stock,
            ctx.repair_model_id
        );
    });
}
vsposBindSuggestionScroll();
$(vsposBindSuggestionScroll);

$(document).on('ifChecked', '#is_recurring', function() {
    $('#recurringInvoiceModal').modal('show');
});

$(document).on('shown.bs.modal', '#recurringInvoiceModal', function() {
    $('input#recur_interval').focus();
});

$(document).on('click', '#select_all_service_staff', function() {
    var val = $('#res_waiter_id').val();
    $('#pos_table tbody')
        .find('select.order_line_service_staff')
        .each(function() {
            $(this)
                .val(val)
                .change();
        });
});

$(document).on('click', '.print-invoice-link', function(e) {
    e.preventDefault();
    $.ajax({
        url: $(this).attr('href') + "?check_location=true",
        dataType: 'json',
        success: function(result) {
            if (result.success == 1) {
                //Check if enabled or not
                if (result.receipt.is_enabled) {
                    pos_print(result.receipt);
                }
            } else {
                toastr.error(result.msg);
            }

        },
    });
});

function getCustomerRewardPoints() {
    if ($('#reward_point_enabled').length <= 0) {
        return false;
    }
    var is_edit = $('form#edit_sell_form').length || 
    $('form#edit_pos_sell_form').length ? true : false;
    if (is_edit && !customer_set) {
        return false;
    }

    var customer_id = $('#customer_id').val();

    $.ajax({
        method: 'POST',
        url: '/sells/pos/get-reward-details',
        data: { 
            customer_id: customer_id
        },
        dataType: 'json',
        success: function(result) {
            var bal = (typeof result.balance !== 'undefined') ? result.balance : (result.points || 0);
            var redeemable = result.blocked ? 0 : (result.points || 0);
            $('#available_rp').text(result.blocked ? bal : redeemable);
            // attr + data (jQuery .data() caches old HTML data-max_points="0")
            $('#rp_redeemed_modal').attr('data-max_points', redeemable).data('max_points', redeemable);
            window.__loyaltyBalancePts = parseInt(bal, 10) || 0;
            window.__loyaltyRedeemablePts = parseInt(redeemable, 10) || 0;
            if (typeof updatePosLoyaltyBadge === 'function') {
                updatePosLoyaltyBadge(bal);
            }
            if (result.blocked) {
                $('#loyalty_redeem_blocked_msg')
                    .removeClass('hide')
                    .text('Minimum ' + (result.min_required || 0) + ' points required to redeem (balance: ' + (result.balance || 0) + ')');
                $('#rp_redeemed_modal').val(0).attr('disabled', true);
            } else {
                $('#loyalty_redeem_blocked_msg').addClass('hide').text('');
                var default_customer_id = $('#default_customer_id').val();
                if ($('#customer_id').val() != default_customer_id) {
                    $('#rp_redeemed_modal').removeAttr('disabled');
                }
            }
            updateRedeemedAmount();
            $('#rp_redeemed_amount').change()
            if (typeof refreshLoyaltyPayRows === 'function') {
                refreshLoyaltyPayRows();
            }
            if (typeof ensureLoyaltyInPaymentDropdowns === 'function') {
                ensureLoyaltyInPaymentDropdowns();
            }
        },
    });
}

function updateRedeemedAmount(argument) {
    var points = $('#rp_redeemed_modal').val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($('#rp_redeemed_modal').data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text, #loyalty_amount_display').text(__currency_trans_from_en(redeemed_amount, true));
    $('#rp_redeemed').val(points);
    $('#rp_redeemed_amount').val(redeemed_amount);
}

function updatePosCustomerBalanceBadges(opts) {
    opts = opts || {};
    var default_customer_id = $('#default_customer_id').val();
    var customer_id = $('#customer_id').val();
    var $due = $('#pos_customer_due_badge');
    var $adv = $('#pos_customer_advance_badge');

    if (!customer_id || customer_id == default_customer_id) {
        $due.addClass('hide');
        $adv.addClass('hide');
        return;
    }

    if (typeof opts.due !== 'undefined' && opts.due !== null) {
        var dueRaw = String(opts.due).replace(/,/g, '').replace(/[^\d.\-]/g, '');
        var dueNum = parseFloat(dueRaw);
        if (!isNaN(dueNum) && Math.abs(dueNum) > 0.0001) {
            var dueLabel = (typeof opts.due_formatted === 'string' && opts.due_formatted)
                ? opts.due_formatted
                : (typeof __currency_trans_from_en === 'function'
                    ? __currency_trans_from_en(dueNum, true)
                    : String(opts.due));
            $('#pos_customer_due_value').text(dueLabel);
            $due.removeClass('hide');
        } else if (opts.due === '' || opts.due === false || dueNum === 0) {
            $due.addClass('hide');
            $('#pos_customer_due_value').text('0');
        }
    }

    if (typeof opts.advance !== 'undefined' && opts.advance !== null) {
        var advNum = parseFloat(opts.advance) || 0;
        if (advNum > 0.0001) {
            $('#pos_customer_advance_value').text(
                typeof __currency_trans_from_en === 'function'
                    ? __currency_trans_from_en(advNum, true)
                    : advNum.toFixed(2)
            );
            $adv.removeClass('hide');
        } else {
            $adv.addClass('hide');
            $('#pos_customer_advance_value').text('0');
        }
    }
}

function updatePosLoyaltyBadge(balance) {
    var $badge = $('#pos_loyalty_badge');
    var default_customer_id = $('#default_customer_id').val();
    var customer_id = $('#customer_id').val();
    var pts = Math.max(0, parseFloat(balance) || 0);
    var ptsLabel = (Math.abs(pts - Math.round(pts)) < 0.001) ? String(Math.round(pts)) : String(Math.round(pts * 100) / 100);

    if (balance === null || balance === undefined || !customer_id || customer_id == default_customer_id) {
        if ($badge.length) {
            $badge.addClass('hide');
        }
        $('#pos_loyalty_bal_label').text('');
        $('#vspos_cust_hist_pts').text('0');
        $('#vspos_cust_hist_pts_modal').text('0');
        if (typeof updatePosCustomerBalanceBadges === 'function') {
            updatePosCustomerBalanceBadges({ due: '', advance: 0 });
        }
        return;
    }

    $('#pos_loyalty_points_value').text(ptsLabel);
    $('#pos_loyalty_bal_label').text('(' + ptsLabel + ' pts)');
    $('#vspos_cust_hist_pts').text(ptsLabel);
    $('#vspos_cust_hist_pts_modal').text(ptsLabel);
    if ($badge.length) {
        $badge.removeClass('hide');
    }
}

function vspos_load_customer_sales_history(customer_id, opts) {
    opts = opts || {};
    var force_reload = !!opts.force;
    var $btnWrap = $('#vspos_customer_history');
    if (!$btnWrap.length) {
        return;
    }

    var default_customer_id = $('#default_customer_id').val();
    if (!customer_id || customer_id == default_customer_id) {
        $btnWrap.addClass('hide');
        $('#vspos_customer_history_modal').modal('hide');
        $('#vspos_cust_hist_list').html(
            '<div class="vspos-cust-hist-empty">' +
            ((typeof LANG !== 'undefined' && LANG.select_customer_for_history)
                ? LANG.select_customer_for_history
                : 'Select a customer to see sales history') +
            '</div>'
        );
        $('#vspos_cust_hist_foot').addClass('hide');
        $('#vspos_cust_hist_name').text('');
        $btnWrap.data('loaded-for', '');
        return;
    }

    $btnWrap.removeClass('hide');

    // Only fetch when opening popup (or forced refresh after sale)
    if (!force_reload && !opts.for_modal) {
        return;
    }

    if (!force_reload && $btnWrap.data('loaded-for') == String(customer_id) && opts.for_modal) {
        return;
    }

    $('#vspos_cust_hist_list').html(
        '<div class="vspos-cust-hist-loading"><i class="fas fa-sync fa-spin"></i></div>'
    );

    $.ajax({
        method: 'GET',
        url: '/sells/pos/customer-sales-history',
        data: { customer_id: customer_id, limit: 25 },
        dataType: 'json',
        success: function (res) {
            if (!res || !res.success || res.is_walk_in) {
                $btnWrap.addClass('hide');
                return;
            }

            $('#vspos_cust_hist_name').text(res.customer_name || '');
            if (typeof res.loyalty_points !== 'undefined') {
                updatePosLoyaltyBadge(res.loyalty_points);
                $('#vspos_cust_hist_pts_modal').text(
                    $('#vspos_cust_hist_pts').text() || String(res.loyalty_points)
                );
            }

            var sales = res.sales || [];
            if (!sales.length) {
                $('#vspos_cust_hist_list').html(
                    '<div class="vspos-cust-hist-empty">' +
                    ((typeof LANG !== 'undefined' && LANG.no_customer_sales)
                        ? LANG.no_customer_sales
                        : 'No previous sales for this customer') +
                    '</div>'
                );
                $('#vspos_cust_hist_foot').addClass('hide');
                $btnWrap.data('loaded-for', String(customer_id));
                return;
            }

            var html = '';
            for (var i = 0; i < sales.length; i++) {
                var s = sales[i];
                var statusClass = 'vspos-chist-status--' + (s.payment_status || 'unknown');
                html +=
                    '<div class="vspos-cust-hist-row" data-id="' + s.id + '">' +
                    '<div class="vspos-chist-main">' +
                    '<span class="vspos-chist-inv"># ' + (s.invoice_no || '') + '</span>' +
                    '<span class="vspos-chist-date">' + (s.date_short || s.date || '') + '</span>' +
                    '</div>' +
                    '<div class="vspos-chist-right">' +
                    '<span class="vspos-chist-amt">' + (s.final_total_formatted || '') + '</span>' +
                    '<span class="vspos-chist-status ' + statusClass + '">' + (s.payment_status_label || '') + '</span>' +
                    '<button type="button" class="vspos-chist-print js-print-last-bill" data-id="' + s.id + '" title="Print">' +
                    '<i class="fi fi-rr-print pos-fi-icon"></i></button>' +
                    '</div></div>';
            }
            $('#vspos_cust_hist_list').html(html);

            var shown = sales.length;
            var total = res.total_count || shown;
            var countTxt =
                ((typeof LANG !== 'undefined' && LANG.showing_sales_count)
                    ? LANG.showing_sales_count
                    : 'Showing :shown of :total')
                    .replace(':shown', shown)
                    .replace(':total', total);
            $('#vspos_cust_hist_count').text(countTxt);
            $('#vspos_cust_hist_foot').removeClass('hide');
            $btnWrap.data('loaded-for', String(customer_id));
        },
        error: function () {
            $('#vspos_cust_hist_list').html(
                '<div class="vspos-cust-hist-empty">' +
                ((typeof LANG !== 'undefined' && LANG.something_went_wrong)
                    ? LANG.something_went_wrong
                    : 'Could not load sales history') +
                '</div>'
            );
        },
    });
}

$(document).on('show.bs.modal', '#vspos_customer_history_modal', function () {
    var customer_id = $('#customer_id').val();
    vspos_load_customer_sales_history(customer_id, { for_modal: true, force: true });
    $('#vspos_cust_hist_pts_modal').text($('#vspos_cust_hist_pts').text() || '0');
});

$(document).on('change', 'select#customer_id', function(){
    var default_customer_id = $('#default_customer_id').val();
    if ($(this).val() == default_customer_id) {
        //Disable reward points for walkin customers
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').val('');
            $('#rp_redeemed_modal').change();
            $('#rp_redeemed_modal').attr('disabled', true);
            $('#available_rp').text('');
            updateRedeemedAmount();
            pos_total_row();
        }
        if (typeof updatePosLoyaltyBadge === 'function') {
            updatePosLoyaltyBadge(null);
        }
        // Reset any cheque rows — not allowed for walk-in (Sell POS only; POP allows supplier cheque)
        if (!$('#is_pop_mode').length) {
            $('#payment_rows_div').find('select.payment_types_dropdown').each(function () {
                if ($(this).val() === 'cheque') {
                    $(this).val('cash').trigger('change');
                }
            });
        }
    } else {
        if ($('#rp_redeemed_modal').length) {
            $('#rp_redeemed_modal').removeAttr('disabled');
        }
        getCustomerRewardPoints();
    }

    // Show/hide history button only; list loads when popup opens
    vspos_load_customer_sales_history($(this).val());
    get_sales_orders();
});

// Show history button if a registered customer is already selected on POS open
$(function () {
    if (typeof vspos_load_customer_sales_history === 'function' && $('#customer_id').length) {
        vspos_load_customer_sales_history($('#customer_id').val());
    }
});

$(document).on('change', '#rp_redeemed_modal', function(){
    var points = $(this).val().trim();
    points = points == '' ? 0 : parseInt(points);
    var amount_per_unit_point = parseFloat($(this).data('amount_per_unit_point'));
    var redeemed_amount = points * amount_per_unit_point;
    $('#rp_redeemed_amount_text, #loyalty_amount_display').text(__currency_trans_from_en(redeemed_amount, true));
    var reward_validation = isValidatRewardPoint();
    if (!reward_validation['is_valid']) {
        toastr.error(reward_validation['msg']);
        $('#rp_redeemed_modal').select();
    }
});

$(document).on('change', '.direct_sell_rp_input', function(){
    updateRedeemedAmount();
    pos_total_row();
});

function isValidatRewardPoint() {
    var element = $('#rp_redeemed_modal');
    if (!element.length) {
        return { is_valid: true, msg: '' };
    }
    var raw = element.val();
    var points = (raw === null || raw === undefined || String(raw).trim() === '')
        ? 0
        : parseInt(String(raw).trim(), 10);
    if (isNaN(points)) {
        points = 0;
    }

    var max_points = parseInt(element.data('max_points'), 10);
    if (isNaN(max_points)) {
        max_points = 0;
    }
    var is_valid = true;
    var msg = '';

    if (points == 0) {
        return {
            is_valid: is_valid,
            msg: msg
        }
    }

    var rp_name = $('input#rp_name').val();
    if (points > max_points) {
        is_valid = false;
        msg = __translate('max_rp_reached_error', {max_points: max_points, rp_name: rp_name});
    }

    var min_order_total_required = parseFloat(element.data('min_order_total'));
    if (isNaN(min_order_total_required)) {
        min_order_total_required = 0;
    }

    var order_total = __read_number($('#final_total_input'));

    if (order_total < min_order_total_required) {
        is_valid = false;
        msg = __translate('min_order_total_error', {min_order: __currency_trans_from_en(min_order_total_required, true), rp_name: rp_name});
    }

    var output = {
        is_valid: is_valid,
        msg: msg,
    }

    return output;
}

function adjustComboQty(tr){
    if(tr.find('input.product_type').val() == 'combo'){
        var qty = __read_number(tr.find('input.pos_quantity'));
        var multiplier = __getUnitMultiplier(tr);

        tr.find('input.combo_product_qty').each(function(){
            $(this).val($(this).data('unit_quantity') * qty * multiplier);
        });
    }
}

$(document).on('change', '#types_of_service_id', function(){
    var types_of_service_id = $(this).val();
    var location_id = $('#location_id').val();

    if(types_of_service_id) {
        $.ajax({
            method: 'POST',
            url: '/sells/pos/get-types-of-service-details',
            data: { 
                types_of_service_id: types_of_service_id,
                location_id: location_id
            },
            dataType: 'json',
            success: function(result) {
                //reset form if price group is changed
                var prev_price_group = $('#types_of_service_price_group').val();
                if(result.price_group_id) {
                    $('#types_of_service_price_group').val(result.price_group_id);
                    $('#price_group_text').removeClass('hide');
                    $('#price_group_text span').text(result.price_group_name);
                } else {
                    $('#types_of_service_price_group').val('');
                    $('#price_group_text').addClass('hide');
                    $('#price_group_text span').text('');
                }
                $('#types_of_service_id').val(types_of_service_id);
                $('.types_of_service_modal').html(result.modal_html);
                
                if (prev_price_group != result.price_group_id) {
                    if ($('form#edit_pos_sell_form').length > 0) {
                        $('table#pos_table tbody').html('');
                        pos_total_row();
                    } else {
                        reset_pos_form();
                    }
                } else {
                    pos_total_row();
                }

                $('.types_of_service_modal').modal('show');
            },
        });
    } else {
        $('.types_of_service_modal').html('');
        $('#types_of_service_price_group').val('');
        $('#price_group_text').addClass('hide');
        $('#price_group_text span').text('');
        $('#packing_charge_text').text('');
        if ($('form#edit_pos_sell_form').length > 0) {
            $('table#pos_table tbody').html('');
            pos_total_row();
        } else {
            reset_pos_form();
        }
    }
});

$(document).on('change', 'input#packing_charge, #additional_expense_value_1, #additional_expense_value_2, \
        #additional_expense_value_3, #additional_expense_value_4', function() {
    pos_total_row();
});

$(document).on('click', '.service_modal_btn', function(e) {
    if ($('#types_of_service_id').val()) {
        $('.types_of_service_modal').modal('show');
    }
});

/**
 * Guess Visa / Mastercard from the FIRST digits (BIN), not the last 4.
 * Visa: starts with 4
 * Mastercard: 51–55 or 2221–2720
 */
function vsposDetectCardBrand(digits) {
    var d = String(digits || '').replace(/\D/g, '');
    if (!d.length) {
        return null;
    }
    if (d.charAt(0) === '4') {
        return 'visa';
    }
    var two = parseInt(d.substring(0, 2), 10);
    if (two >= 51 && two <= 55) {
        return 'master';
    }
    var four = parseInt(d.substring(0, 4), 10);
    if (d.length >= 4 && four >= 2221 && four <= 2720) {
        return 'master';
    }
    // Partial Mastercard 2-series (e.g. typed "22", "23")
    if (d.charAt(0) === '2' && d.length >= 2 && two >= 22 && two <= 27) {
        return 'master';
    }
    return null;
}

function vsposApplyCardBrandFromInput($input) {
    var brand = vsposDetectCardBrand($input.val());
    if (!brand) {
        return;
    }
    // Express checkout modal
    if ($input.attr('id') === 'card_number') {
        var $type = $('#card_type');
        if ($type.length) {
            $type.val(brand).trigger('change');
        }
        return;
    }
    // Payment row
    var $row = $input.closest('.payment_row, .modal-body, form');
    var $select = $row.find('select[id^="card_type"]').first();
    if (!$select.length) {
        $select = $input.closest('.payment_details_div, .row').find('select[id^="card_type"]').first();
    }
    if ($select.length) {
        $select.val(brand).trigger('change');
    }
}

$(document).on('input change blur', '#card_number, .card_number_input, input[id^="card_number"]', function () {
    var $el = $(this);
    // Digits only, max 4
    var cleaned = String($el.val() || '').replace(/\D/g, '').substring(0, 4);
    if ($el.val() !== cleaned) {
        $el.val(cleaned);
    }
    vsposApplyCardBrandFromInput($el);
});

$(document).on('change', '.payment_types_dropdown', function(e) {
    var default_accounts = null;
    if ($('select#select_location_id').length) {
        var $opt = $('select#select_location_id').find(':selected');
        default_accounts = $opt.data('default_payment_accounts');
        if (!default_accounts) {
            var rawAttr = $opt.attr('data-default_payment_accounts');
            if (rawAttr) {
                try { default_accounts = JSON.parse(rawAttr); } catch (err) { default_accounts = null; }
            }
        }
    }
    if (!default_accounts) {
        default_accounts = $('#location_id').data('default_payment_accounts');
        if (!default_accounts) {
            var locAttr = $('#location_id').attr('data-default_payment_accounts');
            if (locAttr) {
                try { default_accounts = JSON.parse(locAttr); } catch (err2) { default_accounts = null; }
            }
        }
    }
    var payment_type = $(this).val();
    var payment_row = $(this).closest('.payment_row');

    // Cheque not allowed for Walk-In Customer on Sell POS — prompt to pick a registered customer.
    // POP (purchase) allows Walk-In / cash suppliers to pay by cheque.
    if (payment_type == 'cheque' && typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer()
        && !$('#is_pop_mode').length && !window.__vspos_allow_walkin_cheque) {
        var $methodDropdown = $(this);
        $methodDropdown.val('cash');
        payment_row.find('.vspos-cheque-walkin-note').removeClass('hide');
        pos_swal_select_customer_for_cheque(function (selected) {
            $methodDropdown.trigger('change');
            if (selected) {
                // Customer changed — switch this row back to Cheque
                $methodDropdown.val('cheque').trigger('change');
            }
        });
        return;
    }
    payment_row.find('.vspos-cheque-walkin-note').addClass('hide');

    if (payment_type && payment_type != 'advance') {
        var default_account = default_accounts && default_accounts[payment_type] && default_accounts[payment_type]['account'] ? 
            default_accounts[payment_type]['account'] : '';
        var row_index = payment_row.find('.payment_row_index').val();

        var account_dropdown = payment_row.find('select#account_' + row_index);
        if (account_dropdown.length && default_accounts) {
            account_dropdown.val(default_account);
            account_dropdown.change();
        }
    }

    //Validate max amount and disable account if advance 
    amount_element = payment_row.find('.payment-amount');
    account_dropdown = payment_row.find('.account-dropdown');
    if (payment_type == 'advance') {
        max_value = $('#advance_balance').val();
        msg = $('#advance_balance').data('error-msg');
        amount_element.rules('add', {
            'max-value': max_value,
            messages: {
                'max-value': msg,
            },
        });
        if (account_dropdown) {
            account_dropdown.prop('disabled', true);
            account_dropdown.closest('.form-group').addClass('hide');
        }
    } else {
        amount_element.rules("remove", "max-value");
        if (account_dropdown) {
            account_dropdown.prop('disabled', false);
            account_dropdown.closest('.form-group').removeClass('hide');
        }
    }

    // Show/hide credit note input
    var credit_note_div = payment_row.find('.credit_note_input_div');
    if (payment_type == 'credit_note') {
        credit_note_div.removeClass('hide');
        amount_element.prop('readonly', true);
    } else {
        credit_note_div.addClass('hide');
        amount_element.prop('readonly', false);
    }
});

function pos_is_walk_in_customer() {
    var customerId = String($('#customer_id').val() || '');
    var defaultId = String($('#default_customer_id').val() || '');
    if (customerId && defaultId && customerId === defaultId) {
        return true;
    }
    var name = '';
    try {
        name = String($('#customer_id option:selected').text() || $('#default_customer_name').val() || '').toLowerCase();
    } catch (err) {
        name = String($('#default_customer_name').val() || '').toLowerCase();
    }
    return name.indexOf('walk-in') !== -1 || name.indexOf('walk in') !== -1;
}

/**
 * SweetAlert with customer search — used when Cheque is chosen for Walk-In.
 * callback(selectedData|null)
 */
function pos_swal_select_customer_for_cheque(callback) {
    callback = typeof callback === 'function' ? callback : function () {};

    if (typeof swal !== 'function') {
        toastr.error((typeof LANG !== 'undefined' && LANG.cheque_not_allowed_walk_in)
            ? LANG.cheque_not_allowed_walk_in
            : 'Cheque payment is not allowed for Walk-In Customer.');
        callback(null);
        return;
    }

    var walkInId = String($('#default_customer_id').val() || '');
    var selectedCustomer = null;
    var searchTimer = null;

    var $content = $(
        '<div class="vspos-swal-customer-wrap">' +
            '<p class="vspos-swal-customer-help">' +
            ((typeof LANG !== 'undefined' && LANG.cheque_walk_in_text)
                ? LANG.cheque_walk_in_text
                : 'Cheque payment cannot be used for Walk-In Customer. Select a registered customer below.') +
            '</p>' +
            '<label class="vspos-swal-customer-label">' +
            ((typeof LANG !== 'undefined' && LANG.select_customer)
                ? LANG.select_customer
                : 'Select Customer') +
            '</label>' +
            '<div class="vspos-swal-customer-search">' +
                '<i class="fas fa-search"></i>' +
                '<input type="text" id="swal_cheque_customer_q" class="form-control" ' +
                    'placeholder="Type name or mobile..." autocomplete="off" />' +
            '</div>' +
            '<div id="swal_cheque_customer_results" class="vspos-swal-customer-results">' +
                '<div class="vspos-swal-customer-empty">Type at least 1 character to search</div>' +
            '</div>' +
            '<div id="swal_cheque_customer_picked" class="vspos-swal-customer-picked hide"></div>' +
        '</div>'
    );

    function destroySearch() {
        if (searchTimer) {
            clearTimeout(searchTimer);
            searchTimer = null;
        }
        $(document).off('keydown.vsposSwalCustomer');
        $('#swal_cheque_customer_q').off();
        $('#swal_cheque_customer_results').off();
    }

    function renderResults(rows) {
        var $box = $('#swal_cheque_customer_results');
        if (!$box.length) {
            return;
        }
        if (!rows.length) {
            $box.html('<div class="vspos-swal-customer-empty">No customers found</div>');
            return;
        }
        var html = '';
        rows.forEach(function (row) {
            var name = row.text || row.name || '';
            var mobile = row.mobile || '-';
            var biz = row.supplier_business_name ? '<span class="vspos-swal-customer-biz">' + $('<div>').text(row.supplier_business_name).html() + '</span>' : '';
            html +=
                '<button type="button" class="vspos-swal-customer-item" data-id="' + row.id + '">' +
                    biz +
                    '<span class="vspos-swal-customer-name">' + $('<div>').text(name).html() + '</span>' +
                    '<span class="vspos-swal-customer-mobile">' + $('<div>').text(mobile).html() + '</span>' +
                '</button>';
        });
        $box.html(html);
        // Attach row data for click
        $box.find('.vspos-swal-customer-item').each(function (i) {
            $(this).data('customer', rows[i]);
        });
    }

    function runSearch(term) {
        term = String(term || '').trim();
        var $box = $('#swal_cheque_customer_results');
        if (term.length < 1) {
            $box.html('<div class="vspos-swal-customer-empty">Type at least 1 character to search</div>');
            return;
        }
        $box.html('<div class="vspos-swal-customer-empty"><i class="fas fa-spinner fa-spin"></i> Searching...</div>');

        var url = (typeof base_path !== 'undefined' ? base_path : '') + '/contacts/customers';
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            data: { q: term },
            success: function (data) {
                var results = $.isArray(data) ? data : [];
                results = results.filter(function (row) {
                    if (!row || row.id === undefined || row.id === null) {
                        return false;
                    }
                    if (walkInId && String(row.id) === walkInId) {
                        return false;
                    }
                    var txt = String(row.text || row.name || '').toLowerCase();
                    if (txt.indexOf('walk-in') !== -1 || txt.indexOf('walk in') !== -1) {
                        return false;
                    }
                    return true;
                });
                renderResults(results);
            },
            error: function () {
                $box.html('<div class="vspos-swal-customer-empty">Search failed. Try again.</div>');
            },
        });
    }

    swal({
        title: (typeof LANG !== 'undefined' && LANG.cheque_walk_in_title)
            ? LANG.cheque_walk_in_title
            : 'Change Customer',
        content: $content[0],
        icon: 'warning',
        className: 'vspos-swal-customer',
        closeOnClickOutside: false,
        buttons: {
            cancel: (typeof LANG !== 'undefined' && LANG.cheque_walk_in_cancel)
                ? LANG.cheque_walk_in_cancel
                : 'Cancel',
            confirm: {
                text: (typeof LANG !== 'undefined' && LANG.cheque_walk_in_confirm)
                    ? LANG.cheque_walk_in_confirm
                    : 'Use Customer',
                value: true,
                className: 'btn-primary',
            },
        },
    }).then(function (confirmed) {
        destroySearch();
        if (!confirmed) {
            callback(null);
            return;
        }
        if (!selectedCustomer || String(selectedCustomer.id) === walkInId) {
            toastr.error((typeof LANG !== 'undefined' && LANG.cheque_walk_in_pick_registered)
                ? LANG.cheque_walk_in_pick_registered
                : 'Please select a registered customer (not Walk-In).');
            callback(null);
            return;
        }
        pos_apply_selected_customer(selectedCustomer);
        callback(selectedCustomer);
    });

    // Wire search after SweetAlert mounts (stop SweetAlert from eating keystrokes)
    setTimeout(function () {
        var $q = $('#swal_cheque_customer_q');
        if (!$q.length) {
            return;
        }

        // Critical: SweetAlert captures keydown — allow typing in our search box
        $(document).on('keydown.vsposSwalCustomer', '#swal_cheque_customer_q', function (e) {
            e.stopPropagation();
        });
        $q.on('keyup', function (e) {
            e.stopPropagation();
            var term = $(this).val();
            if (searchTimer) {
                clearTimeout(searchTimer);
            }
            searchTimer = setTimeout(function () {
                runSearch(term);
            }, 250);
        });
        $q.on('keypress keydown keyup', function (e) {
            e.stopPropagation();
        });

        $('#swal_cheque_customer_results').on('click', '.vspos-swal-customer-item', function () {
            selectedCustomer = $(this).data('customer') || null;
            $('#swal_cheque_customer_results .vspos-swal-customer-item').removeClass('is-selected');
            $(this).addClass('is-selected');
            if (selectedCustomer) {
                var label = (selectedCustomer.text || selectedCustomer.name || '') +
                    (selectedCustomer.mobile ? ' · ' + selectedCustomer.mobile : '');
                $('#swal_cheque_customer_picked')
                    .removeClass('hide')
                    .html('<i class="fas fa-check-circle"></i> ' + $('<div>').text(label).html());
            }
        });

        $q.focus();
    }, 100);
}

function pos_apply_selected_customer(data) {
    if (!data || !data.id) {
        return;
    }
    var $sel = $('select#customer_id');
    if (!$sel.length) {
        return;
    }
    var id = data.id;
    var text = data.text || data.name || String(id);
    if ($sel.find('option[value="' + id + '"]').length === 0) {
        $sel.append($('<option>', { value: id, text: text }));
    }
    $sel.val(id).trigger({
        type: 'select2:select',
        params: { data: data },
    }).trigger('change');

    if (typeof pos_refresh_customer_select2_ui === 'function') {
        pos_refresh_customer_select2_ui($sel, text);
    }
}

function pos_validate_cheque_payments() {
    var ok = true;
    var isPop = $('#is_pop_mode').length > 0;
    $('#payment_rows_div').find('select.payment_types_dropdown').each(function () {
        if ($(this).val() !== 'cheque') {
            return;
        }
        var $methodDropdown = $(this);
        // Sell POS: Walk-In cannot use cheque. POP (purchase) allows supplier cheque.
        if (!isPop && pos_is_walk_in_customer()) {
            ok = false;
            pos_swal_select_customer_for_cheque(function (selected) {
                if (selected) {
                    $methodDropdown.val('cheque').trigger('change');
                } else {
                    $methodDropdown.val('cash').trigger('change');
                }
            });
            return false;
        }
        var $row = $methodDropdown.closest('.payment_row');
        var chequeName = String($row.find('.cheque_name_input').val() || '').trim();
        if (!chequeName) {
            toastr.error((typeof LANG !== 'undefined' && LANG.cheque_name_required)
                ? LANG.cheque_name_required
                : 'Please enter the name on the cheque.');
            $row.find('.cheque_name_input').focus();
            ok = false;
            return false;
        }
        var chequeNo = String($row.find('input[name*="[cheque_number]"]').val() || '').trim();
        if (!chequeNo) {
            toastr.error((typeof LANG !== 'undefined' && LANG.cheque_number_required)
                ? LANG.cheque_number_required
                : 'Please enter the cheque number.');
            $row.find('input[name*="[cheque_number]"]').focus();
            ok = false;
            return false;
        }
    });
    return ok;
}

function vspos_serial_per_qty_enabled() {
    return $('#enable_serial_per_qty').length > 0 && $('#enable_serial_per_qty').val() == '1';
}

function vspos_serial_row_qty($tr) {
    if (!$tr || !$tr.length) {
        return 1;
    }
    var qty = typeof __read_number === 'function'
        ? __read_number($tr.find('input.pos_quantity'))
        : parseFloat($tr.find('input.pos_quantity').val()) || 1;
    qty = Math.round(Math.abs(qty));
    return qty > 0 ? qty : 1;
}

function vspos_serial_find_row($modal) {
    var rowIndex = $modal.find('.vspos-serial-per-qty-wrap').data('row-index');
    if (rowIndex === undefined || rowIndex === null || rowIndex === '') {
        var nameAttr = $modal.find('.vspos-sell-line-note-sync').attr('name')
            || $modal.find('textarea[name*="[sell_line_note]"]').attr('name')
            || $modal.find('input.row_discount_amount').attr('name');
        if (nameAttr) {
            var m = String(nameAttr).match(/products\[(\d+)\]/);
            if (m) {
                rowIndex = m[1];
            }
        }
    }
    var $tr = $('table#pos_table tbody tr[data-row_index="' + rowIndex + '"]');
    if (!$tr.length) {
        $tr = $('table#pos_table tbody').find('input[name="products[' + rowIndex + '][quantity]"]').closest('tr');
    }
    return $tr;
}

function vspos_serial_collect_values($modal) {
    var values = [];
    $modal.find('.vspos-serial-input').each(function () {
        values.push(String($(this).val() || '').trim());
    });
    return values;
}

function vspos_serial_sync_note($modal) {
    var $wrap = $modal.find('.vspos-serial-per-qty-wrap');
    if (!$wrap.length) {
        return;
    }
    var values = vspos_serial_collect_values($modal).filter(function (v) {
        return v !== '';
    });
    var note = values.join('\n');
    $wrap.find('.vspos-sell-line-note-sync').val(note);
    var $tr = vspos_serial_find_row($modal);
    if ($tr && $tr.length) {
        $tr.find('input.pos_sell_line_note').val(note);
    }
}

function vspos_sync_row_edit_modal($modal) {
    if (!$modal || !$modal.length) {
        return;
    }
    if (typeof vspos_serial_sync_note === 'function') {
        vspos_serial_sync_note($modal);
    }
    var $tr = $modal.closest('tr.product_row');
    if (!$tr.length) {
        var nameAttr = $modal.find('input.row_discount_amount').attr('name')
            || $modal.find('textarea[name*="[sell_line_note]"]').attr('name');
        if (!nameAttr) {
            return;
        }
        var match = nameAttr.match(/products\[(\d+)\]/);
        if (!match) {
            return;
        }
        var rowCount = match[1];
        $tr = $('table#pos_table tbody tr[data-row_index="' + rowCount + '"]');
        if (!$tr.length) {
            $tr = $('table#pos_table tbody').find('input[name="products[' + rowCount + '][unit_price]"]').closest('tr');
        }
    }
    if (!$tr.length) {
        return;
    }

    var modalDiscType = $modal.find('select.row_discount_type').val();
    if (modalDiscType) {
        $tr.find('td select.row_discount_type').first().val(modalDiscType);
    }

    var modalDiscAmt = $modal.find('input.row_discount_amount').val();
    $tr.find('td input.row_discount_amount').first().val(modalDiscAmt || '');

    var modalUnitPrice = $modal.find('input.pos_unit_price, .vspos-modal-unit-price-mirror').val();
    if (modalUnitPrice) {
        var $rowPrice = vspos_row_unit_price_input($tr);
        if ($rowPrice.length) {
            $rowPrice.val(modalUnitPrice);
        }
    }

    var modalItemTax = $modal.find('.vspos-modal-item-tax-mirror').val();
    if (modalItemTax !== undefined) {
        $tr.find('input.item_tax').val(modalItemTax);
    }
    var modalTaxId = $modal.find('.vspos-modal-tax-mirror').val();
    if (modalTaxId !== undefined) {
        $tr.find('input.tax_id').val(modalTaxId);
    }
    var modalWarrantyId = $modal.find('.vspos-modal-warranty-mirror').val();
    if (modalWarrantyId !== undefined) {
        $tr.find('input.warranty_id').val(modalWarrantyId);
    }

    var modalNote = $modal.find('.vspos-sell-line-note-sync').val()
        || $modal.find('textarea[name*="[sell_line_note]"]').val();
    if (modalNote !== undefined && modalNote !== null) {
        $tr.find('input.pos_sell_line_note').val(modalNote);
    }

    pos_each_row($tr);
    pos_total_row();
}

function vspos_sync_all_row_edit_modals() {
    $('table#pos_table tbody tr.product_row').each(function () {
        var $modal = $(this).find('.row_edit_product_price_model');
        if ($modal.length) {
            vspos_sync_row_edit_modal($modal);
        }
    });
}

function vspos_serial_rebuild_for_modal($modal) {
    var $wrap = $modal.find('.vspos-serial-per-qty-wrap');
    if (!$wrap.length) {
        return;
    }
    var $tr = vspos_serial_find_row($modal);
    var qty = vspos_serial_row_qty($tr);
    var $list = $wrap.find('.vspos-serial-list');
    var existing = [];
    try {
        existing = $list.data('existing') || [];
        if (typeof existing === 'string') {
            existing = JSON.parse(existing);
        }
    } catch (err) {
        existing = [];
    }
    if (!$.isArray(existing)) {
        existing = [];
    }
    // Prefer currently typed values if rebuilding
    var current = vspos_serial_collect_values($modal);
    if (current.length) {
        existing = current;
    }

    $list.empty();
    $wrap.find('.vspos-serial-count-label').text(' (' + qty + ')');

    for (var i = 0; i < qty; i++) {
        var val = existing[i] || '';
        var html =
            '<div class="vspos-serial-field">' +
            '<label>Serial #' + (i + 1) + '</label>' +
            '<div class="input-group input-group-sm">' +
            '<span class="input-group-addon"><i class="fas fa-hashtag"></i></span>' +
            '<input type="text" class="form-control vspos-serial-input" value="' +
            $('<div>').text(val).html() +
            '" placeholder="Serial / IMEI #' +
            (i + 1) +
            '" autocomplete="off">' +
            '</div></div>';
        $list.append(html);
    }
    vspos_serial_sync_note($modal);
}

function vspos_serial_pad(num, size) {
    var s = String(num);
    while (s.length < size) {
        s = '0' + s;
    }
    return s;
}

$(document).on('click', '.vspos-serial-generate-btn', function (e) {
    e.preventDefault();
    var $modal = $(this).closest('.row_edit_product_price_model');
    var $wrap = $modal.find('.vspos-serial-per-qty-wrap');
    if (!$wrap.length) {
        return;
    }
    var prefix = String($wrap.find('.vspos-serial-prefix').val() || 'SN').trim();
    if (prefix && !/[-_]$/.test(prefix)) {
        prefix = prefix + '-';
    }
    var $inputs = $modal.find('.vspos-serial-input');
    var padLen = Math.max(3, String($inputs.length).length);
    $inputs.each(function (idx) {
        $(this).val(prefix + vspos_serial_pad(idx + 1, padLen));
    });
    vspos_serial_sync_note($modal);
});

$(document).on('input change', '.vspos-serial-input', function () {
    vspos_serial_sync_note($(this).closest('.row_edit_product_price_model'));
});

// Rebuild serial fields when qty changes on a serial-enabled row
$(document).on('change', 'table#pos_table tbody input.pos_quantity', function () {
    if (!vspos_serial_per_qty_enabled()) {
        return;
    }
    var $tr = $(this).closest('tr');
    if ($tr.find('.enable_sr_no').val() != '1') {
        return;
    }
    var $modal = $tr.find('.row_edit_product_price_model');
    if ($modal.length && $modal.find('.vspos-serial-per-qty-wrap').length) {
        if ($modal.hasClass('in') || $modal.is(':visible')) {
            vspos_serial_rebuild_for_modal($modal);
        } else {
            // Keep existing list data in sync for next open
            var $list = $modal.find('.vspos-serial-list');
            var values = ($modal.find('.vspos-sell-line-note-sync').val() || '')
                .split(/\r\n|\n|\r/)
                .map(function (s) {
                    return s.trim();
                })
                .filter(Boolean);
            $list.data('existing', values);
        }
    }
});

function pos_validate_serial_per_qty() {
    if (!vspos_serial_per_qty_enabled()) {
        return true;
    }
    var ok = true;
    $('table#pos_table tbody tr.product_row').each(function () {
        var $tr = $(this);
        if ($tr.find('.enable_sr_no').val() != '1') {
            return;
        }
        var $modal = $tr.find('.row_edit_product_price_model');
        var $wrap = $modal.find('.vspos-serial-per-qty-wrap');
        if (!$wrap.length) {
            return;
        }
        // Ensure note is synced from inputs if modal was used
        vspos_serial_sync_note($modal);
        var qty = vspos_serial_row_qty($tr);
        var note = String($wrap.find('.vspos-sell-line-note-sync').val() || '').trim();
        var serials = note
            ? note.split(/\r\n|\n|\r/).map(function (s) {
                  return s.trim();
              }).filter(Boolean)
            : [];
        if (serials.length < qty) {
            var productName = $tr.find('.text-link, .tw-font-semibold').first().text().trim() || 'product';
            var msg =
                typeof LANG !== 'undefined' && LANG.serials_required_for_qty
                    ? LANG.serials_required_for_qty.replace(':qty', qty).replace(':product', productName)
                    : 'Please enter all ' + qty + ' serial number(s) for ' + productName;
            toastr.error(msg);
            $modal.modal('show');
            ok = false;
            return false;
        }
    });
    return ok;
}

// Credit note validation
$(document).on('blur', '.credit_note_no', function() {
    var $input = $(this);
    var note_no = $input.val().trim();
    if (!note_no) return;

    var payment_row = $input.closest('.payment_row');
    var amount_input = payment_row.find('.payment-amount');
    var info_span = payment_row.find('.credit_note_info');
    var credit_note_id_input = payment_row.find('.credit_note_id');
    var note_input = payment_row.find('textarea');

    $.ajax({
        url: '/sell-return/validate-credit-note',
        method: 'POST',
        data: { credit_note_no: note_no },
        dataType: 'json',
        success: function(response) {
            if (response.success === 1) {
                amount_input.val(response.remaining).trigger('change');
                credit_note_id_input.val(response.credit_note_id);
                note_input.val(response.credit_note_no);
                info_span.html('<span class="text-success">' + response.credit_note_no + ' — Remaining: ' + __currency_convert_from_number(response.remaining) + '</span>');
                calculate_balance_due();
            } else {
                amount_input.val('').trigger('change');
                credit_note_id_input.val('');
                note_input.val('');
                info_span.html('<span class="text-danger">' + response.msg + '</span>');
                calculate_balance_due();
            }
        },
        error: function(xhr) {
            var msg = 'Validation failed';
            if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            info_span.html('<span class="text-danger">' + msg + '</span>');
        }
    });
});

$(document).on('change', '.credit_note_no', function() {
    $(this).trigger('blur');
});

$(document).on('show.bs.modal', '#recent_transactions_modal', function () {
    get_recent_transactions('final', $('div#tab_final'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_quotation"]', function () {
    get_recent_transactions('quotation', $('div#tab_quotation'));
});
$(document).on('shown.bs.tab', 'a[href="#tab_draft"]', function () {
    get_recent_transactions('draft', $('div#tab_draft'));
});

function disable_pos_form_actions(){
    if (!window.navigator.onLine) {
        return false;
    }

    $('div.pos-processing').show();
    $('#pos-save').attr('disabled', 'true');
    $('div.pos-form-actions').find('button').attr('disabled', 'true');
}

function enable_pos_form_actions(){
    $('div.pos-processing').hide();
    $('#pos-save').removeAttr('disabled');
    $('div.pos-form-actions').find('button').removeAttr('disabled');
}

$(document).on('change', '#recur_interval_type', function() {
    if ($(this).val() == 'months') {
        $('.subscription_repeat_on_div').removeClass('hide');
    } else {
        $('.subscription_repeat_on_div').addClass('hide');
    }
});

function validate_discount_field() {
    discount_element = $('#discount_amount_modal');
    discount_type_element = $('#discount_type_modal');

    if ($('#add_sell_form').length || $('#edit_sell_form').length) {
        discount_element = $('#discount_amount');
        discount_type_element = $('#discount_type');
    }
    var max_value = parseFloat(discount_element.data('max-discount'));
    if (discount_element.val() != '' && !isNaN(max_value)) {
        if (discount_type_element.val() == 'fixed') {
            var subtotal = get_subtotal();
            //get max discount amount
            max_value = __calculate_amount('percentage', max_value, subtotal)
        }

        discount_element.rules('add', {
            'max-value': max_value,
            messages: {
                'max-value': discount_element.data('max-discount-error_msg'),
            },
        });
    } else {
        discount_element.rules("remove", "max-value");      
    }
    discount_element.trigger('change');
}

$(document).on('change', '#discount_type_modal, #discount_type', function() {
    validate_discount_field();
});

function update_shipping_address(data) {
    if ($('#shipping_address_div').length) {
        var shipping_address = '';
        if (data.supplier_business_name) {
            shipping_address += data.supplier_business_name;
        }
        if (data.name) {
            shipping_address += ',<br>' + data.name;
        }
        if (data.text) {
            shipping_address += ',<br>' + data.text;
        }
        shipping_address += ',<br>' + data.shipping_address ;
        $('#shipping_address_div').html(shipping_address);
    }
    if ($('#billing_address_div').length) {
        var address = [];
        if (data.supplier_business_name) {
            address.push(data.supplier_business_name);
        }
        if (data.name) {
            address.push('<br>' + data.name);
        }
        if (data.text) {
            address.push('<br>' + data.text);
        }
        if (data.address_line_1) {
            address.push('<br>' + data.address_line_1);
        }
        if (data.address_line_2) {
            address.push('<br>' + data.address_line_2);
        }
        if (data.city) {
            address.push('<br>' + data.city);
        }
        if (data.state) {
            address.push(data.state);
        }
        if (data.country) {
            address.push(data.country);
        }
        if (data.zip_code) {
            address.push('<br>' + data.zip_code);
        }
        var billing_address = address.join(', ');
        $('#billing_address_div').html(billing_address);
    }

    if ($('#shipping_custom_field_1').length) {
        let shipping_custom_field_1 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_1 : '';
        $('#shipping_custom_field_1').val(shipping_custom_field_1);
    }

    if ($('#shipping_custom_field_2').length) {
        let shipping_custom_field_2 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_2 : '';
        $('#shipping_custom_field_2').val(shipping_custom_field_2);
    }

    if ($('#shipping_custom_field_3').length) {
        let shipping_custom_field_3 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_3 : '';
        $('#shipping_custom_field_3').val(shipping_custom_field_3);
    }

    if ($('#shipping_custom_field_4').length) {
        let shipping_custom_field_4 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_4 : '';
        $('#shipping_custom_field_4').val(shipping_custom_field_4);
    }

    if ($('#shipping_custom_field_5').length) {
        let shipping_custom_field_5 = data.shipping_custom_field_details != null ? data.shipping_custom_field_details.shipping_custom_field_5 : '';
        $('#shipping_custom_field_5').val(shipping_custom_field_5);
    }
    
    //update export fields
    if (data.is_export) {
        $('#is_export').prop('checked', true);
        $('div.export_div').show();
        if ($('#export_custom_field_1').length) {
            $('#export_custom_field_1').val(data.export_custom_field_1);
        }
        if ($('#export_custom_field_2').length) {
            $('#export_custom_field_2').val(data.export_custom_field_2);
        }
        if ($('#export_custom_field_3').length) {
            $('#export_custom_field_3').val(data.export_custom_field_3);
        }
        if ($('#export_custom_field_4').length) {
            $('#export_custom_field_4').val(data.export_custom_field_4);
        }
        if ($('#export_custom_field_5').length) {
            $('#export_custom_field_5').val(data.export_custom_field_5);
        }
        if ($('#export_custom_field_6').length) {
            $('#export_custom_field_6').val(data.export_custom_field_6);
        }
    } else {
        $('#export_custom_field_1, #export_custom_field_2, #export_custom_field_3, #export_custom_field_4, #export_custom_field_5, #export_custom_field_6').val('');
        $('#is_export').prop('checked', false);
        $('div.export_div').hide();
    }
    
    $('#shipping_address_modal').val(data.shipping_address);
    $('#shipping_address').val(data.shipping_address);
}

function get_sales_orders() {
    if ($('#sales_order_ids').length) {
        if ($('#sales_order_ids').hasClass('not_loaded')) {
            $('#sales_order_ids').removeClass('not_loaded');
            return false;
        }
        var customer_id = $('select#customer_id').val();
        var location_id = $('input#location_id').val();
        $.ajax({
            url: '/get-sales-orders/' + customer_id + '?location_id=' + location_id,
            dataType: 'json',
            success: function(data) {
                $('#sales_order_ids').select2('destroy').empty().select2({data: data});
                $('table#pos_table tbody').find('tr').each( function(){
                    if (typeof($(this).data('so_id')) !== 'undefined') {
                        $(this).remove();
                    }
                });
                pos_total_row();
            },
        });
    }
}

$("#sales_order_ids").on("select2:select", function (e) {
    var sales_order_id = e.params.data.id;
    var product_row = $('input#product_row_count').val();
    var location_id = $('input#location_id').val();
    $.ajax({
        method: 'GET',
        url: '/get-sales-order-lines',
        async: false,
        data: {
            product_row: product_row,
            sales_order_id: sales_order_id,
            is_serial_no: true,

        },
        dataType: 'json',
        success: function(result) {
            if (result.html) {
                var html = result.html;
                $(html).find('tr').each(function(){
                    $('table#pos_table tbody')
                    .append($(this))
                    .find('input.pos_quantity');
                    
                    var this_row = $('table#pos_table tbody')
                        .find('tr')
                        .last();
                    pos_each_row(this_row);

                    product_row = parseInt(product_row) + 1;

                    //For initial discount if present
                    var line_total = __read_number(this_row.find('input.pos_line_total'));
                    this_row.find('span.pos_line_total_text').text(line_total);

                    //Check if multipler is present then multiply it when a new row is added.
                    if(__getUnitMultiplier(this_row) > 1){
                        this_row.find('select.sub_unit').trigger('change');
                    }

                    round_row_to_iraqi_dinnar(this_row);
                    __currency_convert_recursively(this_row);
                });

                set_so_values(result.sales_order);

                //increment row count
                $('input#product_row_count').val(product_row);
                
                pos_total_row();
            
            } else {
                toastr.error(result.msg);
                $('input#search_product')
                    .focus()
                    .select();
            }
        },
    });
});

function set_so_values(so) {
    $('textarea[name="sale_note"]').val(so.additional_notes);
    if ($('#shipping_details').is(':visible')) {
        $('#shipping_details').val(so.shipping_details);
    }
    $('#shipping_address').val(so.shipping_address);
    $('#delivered_to').val(so.delivered_to);
    $('#shipping_charges').val( __number_f(so.shipping_charges));
    $('#shipping_status').val(so.shipping_status);
    if ($('#shipping_custom_field_1').length) {
        $('#shipping_custom_field_1').val(so.shipping_custom_field_1);
    }
    if ($('#shipping_custom_field_2').length) {
        $('#shipping_custom_field_2').val(so.shipping_custom_field_2);
    }
    if ($('#shipping_custom_field_3').length) {
        $('#shipping_custom_field_3').val(so.shipping_custom_field_3);
    }
    if ($('#shipping_custom_field_4').length) {
        $('#shipping_custom_field_4').val(so.shipping_custom_field_4);
    }
    if ($('#shipping_custom_field_5').length) {
        $('#shipping_custom_field_5').val(so.shipping_custom_field_5);
    }
}

$("#sales_order_ids").on("select2:unselect", function (e) {
    var sales_order_id = e.params.data.id;
    $('table#pos_table tbody').find('tr').each( function(){
        if (typeof($(this).data('so_id')) !== 'undefined' 
            && $(this).data('so_id') == sales_order_id) {
            $(this).remove();
        pos_total_row();
        }
    });
});

$(document).on('click', '#add_expense', function(){
    $.ajax({
        url: '/expenses/create',
        data: { 
            location_id: $('#select_location_id').val()
        },
        dataType: 'html',
        success: function(result) {
            $('#expense_modal').html(result);
            $('#expense_modal').modal('show');
        },
    });
});

$(document).on('shown.bs.modal', '#expense_modal', function(){
    $('#expense_transaction_date').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    $('#expense_modal .paid_on').datetimepicker({
        format: moment_date_format + ' ' + moment_time_format,
        ignoreReadonly: true,
    });
    $(this).find('.select2').select2();
    $('#add_expense_modal_form').validate();
});

$(document).on('hidden.bs.modal', '#expense_modal', function(){
    $(this).html('');
});

$(document).on('submit', 'form#add_expense_modal_form', function(e) {
    e.preventDefault();
    var data = $(this).serialize();

    $.ajax({
        method: 'POST',
        url: $(this).attr('action'),
        dataType: 'json',
        data: data,
        success: function(result) {
            if (result.success == true) {
                $('#expense_modal').modal('hide');
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

// Receive Customer Payment from POS
$(document).on('click', '#pos-receive-customer-payment', function() {
    var customer_id = $('select#customer_id').val();
    if (!customer_id) {
        return;
    }
    $.ajax({
        url: '/payments/pay-contact-due/' + customer_id + '?type=sell',
        dataType: 'html',
        success: function(result) {
            $('#pos_pay_contact_due_modal').html(result);
            var $modal = $('#pos_pay_contact_due_modal');
            var $form = $modal.find('#pay_contact_due_form');

            // Inject from_pos flag
            $form.append('<input type="hidden" name="from_pos" value="1">');

            // Init UI same as app.js
            __currency_convert_recursively($modal);
            $modal.find('.select2').select2();
            $modal.find('[name="paid_on"]').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true
            });
            $form.validate();

            // Direct handler fires before app.js document-delegated handler.
            // stopPropagation prevents app.js from doing a normal form POST.
            $form.on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Cash denomination validation (same as app.js)
                var $denomInput = $form.find('.enable_cash_denomination_for_payment_methods');
                if ($denomInput.length && $denomInput.val()) {
                    var payment_type = $form.find('.payment_types_dropdown').val();
                    var denomination_for_payment_types = JSON.parse($denomInput.val());
                    if (denomination_for_payment_types.includes(payment_type) && $form.find('.is_strict').length && $form.find('.is_strict').val() === '1') {
                        var payment_amount = __read_number($form.find('.payment_amount'));
                        var total_denomination = $form.find('input.denomination_total_amount').val();
                        if (payment_amount != total_denomination) {
                            $form.find('.cash_denomination_error').removeClass('hide');
                            return false;
                        }
                    }
                }
                $form.find('.cash_denomination_error').addClass('hide');

                // Use FormData to support file upload (document attachment)
                var formData = new FormData($form[0]);

                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            $modal.modal('hide');
                            toastr.success(result.msg);
                            get_contact_due($('select#customer_id').val());
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $modal.modal('show');
        }
    });
});

$(document).on('hidden.bs.modal', '#pos_pay_contact_due_modal', function() {
    $(this).html('');
});

// Supplier pay button handler
$(document).on('click', '#pos_supplier_pay_btn', function() {
    var supplier_id = $('select#supplier_id').val();

    if (!supplier_id) {
        $.ajax({
            url: '/purchases/get_suppliers?q=*',
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(result) {
                var data = (typeof result === 'string') ? JSON.parse(result) : result;
                var suppliers = Array.isArray(data) ? data : (data.data || []);

                if (suppliers.length > 0) {
                    showSupplierSelectModal(suppliers);
                } else {
                    toastr.warning(LANG.no_suppliers_found || 'No suppliers found');
                }
            },
            error: function() {
                toastr.error(LANG.failed_to_load_suppliers || 'Failed to load suppliers');
            }
        });
    } else {
        openSupplierPayModal(supplier_id);
    }
});

function showSupplierSelectModal(suppliers) {
    var $modal = $('#pos_select_supplier_modal');
    var $select = $('#pos_supplier_select');

    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }

    $select.empty().append('<option value=""></option>');
    suppliers.forEach(function(s) {
        var label = s.text || s.name || '';
        if (s.mobile) {
            label += ' — ' + s.mobile;
        }
        $select.append($('<option>', { value: s.id, text: label }));
    });

    $select.select2({
        placeholder: LANG.search_by_name_or_phone || 'Search by name or phone...',
        minimumResultsForSearch: 0,
        dropdownParent: $modal,
        width: '100%',
    });

    $modal.modal('show');

    setTimeout(function() {
        $select.select2('open');
    }, 350);
}

$(document).on('click', '#pos_supplier_pay_confirm', function() {
    var selected_supplier = $('#pos_supplier_select').val();
    if (!selected_supplier) {
        toastr.warning(LANG.please_select_supplier || 'Please select a supplier');
        return;
    }
    $('#pos_select_supplier_modal').modal('hide');
    openSupplierPayModal(selected_supplier);
});

$(document).on('hidden.bs.modal', '#pos_select_supplier_modal', function() {
    var $select = $('#pos_supplier_select');
    if ($select.hasClass('select2-hidden-accessible')) {
        $select.select2('destroy');
    }
    $select.empty();
});

function openSupplierPayModal(supplier_id) {
    $.ajax({
        url: '/payments/pay-contact-due/' + supplier_id + '?type=purchase',
        dataType: 'html',
        success: function(result) {
            $('#pos_pay_contact_due_modal').html(result);
            var $modal = $('#pos_pay_contact_due_modal');
            var $form = $modal.find('#pay_contact_due_form');

            // Inject from_pos flag
            $form.append('<input type="hidden" name="from_pos" value="1">');

            // Init UI same as app.js
            __currency_convert_recursively($modal);
            $modal.find('.select2').select2({ dropdownParent: $modal });
            $modal.find('[name="paid_on"]').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true
            });
            $form.validate();

            // Direct handler fires before app.js document-delegated handler.
            $form.on('submit', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Cash denomination validation
                var $denomInput = $form.find('.enable_cash_denomination_for_payment_methods');
                if ($denomInput.length && $denomInput.val()) {
                    var payment_type = $form.find('.payment_types_dropdown').val();
                    var denomination_for_payment_types = JSON.parse($denomInput.val());
                    if (denomination_for_payment_types.includes(payment_type) && $form.find('.is_strict').length && $form.find('.is_strict').val() === '1') {
                        var payment_amount = __read_number($form.find('.payment_amount'));
                        var total_denomination = $form.find('input.denomination_total_amount').val();
                        if (payment_amount != total_denomination) {
                            $form.find('.cash_denomination_error').removeClass('hide');
                            return false;
                        }
                    }
                }
                $form.find('.cash_denomination_error').addClass('hide');

                var formData = new FormData($form[0]);

                $.ajax({
                    method: 'POST',
                    url: $form.attr('action'),
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(result) {
                        if (result.success) {
                            $modal.modal('hide');
                            toastr.success(result.msg);
                        } else {
                            toastr.error(result.msg);
                        }
                    }
                });
            });

            $modal.modal('show');
        }
    });
}

function get_contact_due(id) {
    if (!id) {
        if (typeof updatePosCustomerBalanceBadges === 'function') {
            updatePosCustomerBalanceBadges({ due: '', advance: null });
        }
        $('.contact_due_text').find('span').text('');
        $('.contact_due_text').addClass('hide');
        return;
    }
    $.ajax({
        method: 'get',
        url: /get-contact-due/ + id,
        dataType: 'text',
        success: function(result) {
            if (result != '') {
                $('.contact_due_text').find('span').text(result);
                $('.contact_due_text').removeClass('hide');
                if (typeof updatePosCustomerBalanceBadges === 'function') {
                    updatePosCustomerBalanceBadges({ due: result, due_formatted: result });
                }
            } else {
                $('.contact_due_text').find('span').text('');
                $('.contact_due_text').addClass('hide');
                if (typeof updatePosCustomerBalanceBadges === 'function') {
                    updatePosCustomerBalanceBadges({ due: '' });
                }
            }
        },
    });
}

function submitQuickContactForm(form) {
    var data = $(form).serialize();
    $.ajax({
        method: 'POST',
        url: $(form).attr('action'),
        dataType: 'json',
        data: data,
        beforeSend: function(xhr) {
            __disable_submit_button($(form).find('button[type="submit"]'));
        },
        success: function(result) {
            if (result.success == true) {
                var name = result.data.name;

                if (result.data.supplier_business_name) {
                    name += result.data.supplier_business_name;
                }
                
                $('select#customer_id').append(
                    $('<option>', { value: result.data.id, text: name })
                );
                $('select#customer_id')
                    .val(result.data.id)
                    .trigger('change');

                // Vehicle Modification / Tyre Shop: select first created vehicle for this sale
                if (result.vehicles && result.vehicles.length) {
                    var firstVehicle = result.vehicles[0];
                    if (!$('#workshop_vehicle_id').length) {
                        $('<input>', {
                            type: 'hidden',
                            id: 'workshop_vehicle_id',
                            name: 'workshop_vehicle_id'
                        }).appendTo('#add_sell_form, #edit_sell_form');
                    }
                    $('#workshop_vehicle_id').val(firstVehicle.id);
                    if (typeof toastr !== 'undefined' && firstVehicle.registration_number) {
                        toastr.info(firstVehicle.registration_number);
                    }
                }

                $('div.contact_modal').modal('hide');
                update_shipping_address(result.data)
                toastr.success(result.msg);
            } else {
                toastr.error(result.msg);
            }
        },
    });
}

$(document).on('click', '#send_for_sell_return', function(e) {
    var invoice_no = $('#send_for_sell_return_invoice_no').val();

    if (invoice_no) {
        $.ajax({
            method: 'get',
            url: /validate-invoice-to-return/ + encodeURI(invoice_no),
            dataType: 'json',
            success: function(result) {
                if (result.success == true) {
                    window.location = result.redirect_url ;
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    }
});

$(document).on('click', '#send_for_pos_exchange', function(e) {
    var invoice_no = $('#send_for_pos_exchange_invoice_no').val();

    if (!invoice_no) {
        toastr.error((typeof LANG !== 'undefined' && LANG.required) ? LANG.required : 'Required');
        return;
    }

    $.ajax({
        method: 'get',
        url: '/validate-invoice-to-return/' + encodeURI(invoice_no) + '?exchange=1',
        dataType: 'json',
        success: function(result) {
            if (result.success == true) {
                window.location = result.redirect_url;
            } else {
                toastr.error(result.msg);
            }
        },
    });
});

/**
 * Exchange flow: return items (credit note) then sell other items on POS.
 */
function vspos_get_exchange_data() {
    try {
        var raw = sessionStorage.getItem('vspos_exchange');
        return raw ? JSON.parse(raw) : null;
    } catch (e) {
        return null;
    }
}

function vspos_set_exchange_data(data) {
    try {
        if (data) {
            sessionStorage.setItem('vspos_exchange', JSON.stringify(data));
        } else {
            sessionStorage.removeItem('vspos_exchange');
        }
    } catch (e) {}
}

function vspos_parse_exchange_url_params() {
    if (!window.location.search) {
        return;
    }
    try {
        var params = new URLSearchParams(window.location.search);
        var cn = params.get('exchange_cn');
        if (!cn) {
            return;
        }
        vspos_set_exchange_data({
            credit_note_no: cn,
            credit_note_id: params.get('exchange_cn_id') || '',
            remaining: parseFloat(params.get('exchange_remaining') || 0) || 0,
            contact_id: params.get('contact_id') || '',
            contact_name: params.get('contact_name') || '',
        });
        // Clean query string so refresh doesn't re-parse oddly
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    } catch (e) {}
}

function vspos_clear_exchange_state(removeStorage) {
    $('#vspos_exchange_banner').remove();
    if (removeStorage) {
        vspos_set_exchange_data(null);
    }
}

function vspos_show_exchange_banner(data) {
    if (!data || !data.credit_note_no) {
        return;
    }
    $('#vspos_exchange_banner').remove();
    var remainingTxt = '';
    if (data.remaining && typeof __currency_trans_from_en === 'function') {
        remainingTxt = __currency_trans_from_en(data.remaining, true);
    } else if (data.remaining) {
        remainingTxt = data.remaining;
    }
    var msg = (typeof LANG !== 'undefined' && LANG.pos_exchange_pos_banner)
        ? LANG.pos_exchange_pos_banner
        : 'Exchange credit ready. Add the new item(s), then pay — Credit Note will be applied.';
    msg = msg.replace(':cn', data.credit_note_no).replace(':amount', remainingTxt);

    var html = '<div id="vspos_exchange_banner" class="alert alert-warning" style="margin:8px 0;padding:10px 12px;border-left:4px solid #F59E0B;">'
        + '<strong><i class="fas fa-exchange-alt"></i> '
        + ((typeof LANG !== 'undefined' && LANG.pos_exchange) ? LANG.pos_exchange : 'Exchange')
        + ':</strong> ' + msg
        + ' <button type="button" class="btn btn-xs btn-default pull-right" id="vspos_exchange_dismiss" style="margin-left:8px;">'
        + ((typeof LANG !== 'undefined' && LANG.dismiss) ? LANG.dismiss : 'Dismiss')
        + '</button></div>';

    var $host = $('.vspos-cart-toolbar').first();
    if ($host.length) {
        $host.prepend(html);
    } else {
        $('form#add_pos_sell_form').prepend(html);
    }
}

$(document).on('click', '#vspos_exchange_dismiss', function() {
    vspos_clear_exchange_state(true);
});

function vspos_apply_exchange_to_pos() {
    var data = vspos_get_exchange_data();
    if (!data || !data.credit_note_no) {
        return;
    }
    vspos_show_exchange_banner(data);

    if (data.contact_id && $('select#customer_id').length) {
        var customer = {
            id: data.contact_id,
            text: data.contact_name || ('#' + data.contact_id),
            name: data.contact_name || '',
        };
        if (typeof pos_select_customer_from_display === 'function') {
            pos_select_customer_from_display(customer);
        } else if (typeof pos_apply_customer_to_pos_select === 'function') {
            pos_apply_customer_to_pos_select(customer);
        } else {
            var $sel = $('select#customer_id');
            if ($sel.find('option[value="' + data.contact_id + '"]').length === 0) {
                $sel.append($('<option>', { value: data.contact_id, text: customer.text }));
            }
            $sel.val(data.contact_id).trigger('change');
        }
    }

    toastr.info((typeof LANG !== 'undefined' && LANG.pos_exchange_ready)
        ? LANG.pos_exchange_ready.replace(':cn', data.credit_note_no)
        : ('Exchange credit ' + data.credit_note_no + ' ready — add replacement items.'));
}

function vspos_apply_exchange_credit_note() {
    var data = vspos_get_exchange_data();
    if (!data || !data.credit_note_no) {
        return;
    }

    var $row = $('#payment_rows_div .payment_row').first();
    if (!$row.length) {
        $row = $('#modal_payment .payment_row').first();
    }
    if (!$row.length) {
        return;
    }

    var $type = $row.find('select.payment_types_dropdown');
    if (!$type.length || !$type.find('option[value="credit_note"]').length) {
        toastr.warning((typeof LANG !== 'undefined' && LANG.pos_exchange_cn_missing)
            ? LANG.pos_exchange_cn_missing
            : 'Enable Credit Note payment method to complete exchange.');
        return;
    }

    $type.val('credit_note').trigger('change');
    $row.find('.credit_note_no').val(data.credit_note_no);
    if (data.credit_note_id) {
        $row.find('.credit_note_id').val(data.credit_note_id);
    }
    // Trigger validation / remaining lookup
    $row.find('.credit_note_no').trigger('blur');

    var payable = 0;
    if ($('input#final_total_input').length) {
        payable = __read_number($('input#final_total_input')) || 0;
    }
    var credit = parseFloat(data.remaining) || 0;
    var pay_amt = credit > 0 && payable > 0 ? Math.min(credit, payable) : (credit || payable);
    if (pay_amt > 0 && $row.find('.payment-amount').length) {
        __write_number($row.find('.payment-amount'), pay_amt);
        $row.find('.payment-amount').trigger('change');
    }
}

    $(document).on('click', '#send_for_sercice_staff_replacement', function (e) {
        var invoice_no = $('#send_for_sell_service_staff_invoice_no').val();

        if (invoice_no) {
            $.ajax({
                method: 'get',
                url: /validate-invoice-to-service-staff-replacement/ + encodeURI(invoice_no),
                dataType: 'json',
                success: function (result) {
                    if (result.success == true) {
                        $('#service_staff_replacement').popover('hide');
                        $('#service_staff_modal').html(result.msg);
                        $('#service_staff_modal').modal('show');
                       
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        }
    });

    $(document).on('shown.bs.modal', '#service_staff_modal', function () {
        $('#change_service_staff').validate();
    });


    $(document).on('submit', 'form#change_service_staff', function (e) {
        e.preventDefault();
        var data = $(this).serialize();

        $.ajax({
            method: 'POST',
            url: $(this).attr('action'),
            dataType: 'json',
            data: data,
            success: function (result) {
                if (result.success == true) {
                    $('#service_staff_modal').modal('hide');
                    toastr.success(result.msg);
                } else {
                    toastr.error(result.msg);
                }
            },
        });
    });

$(document).on('ifChanged', 'input[name="search_fields[]"]', function(event) {
    var search_fields = [];
    $('input[name="search_fields[]"]:checked').each(function() {
       search_fields.push($(this).val());
    });

    localStorage.setItem('pos_search_fields', search_fields);
});

function set_search_fields() {
    if ($('input[name="search_fields[]"]').length == 0) {
        return false;
    }

    var pos_search_fields = localStorage.getItem('pos_search_fields');

    if (pos_search_fields === null) {
        pos_search_fields = ['name', 'sku', 'lot'];
    }

    $('input[name="search_fields[]"]').each(function() {
        if (pos_search_fields.indexOf($(this).val()) >= 0) {
            $(this).iCheck('check');
        } else {
            $(this).iCheck('uncheck');
        }
    });
}

$(document).on('click', '#show_service_staff_availability', function(){
    loadServiceStaffAvailability();
})
$(document).on('click', '#refresh_service_staff_availability_status', function(){
    loadServiceStaffAvailability(false);
})
$(document).on('click', 'button.pause_resume_timer', function(e){
    $('.view_modal').find('.overlay').removeClass('hide');
    $.ajax({
        method: 'get',
        url: $(this).attr('data-href'),
        dataType: 'json',
        success: function(result) {
            loadServiceStaffAvailability(false);
        },
    });
})

$(document).on('click', '.mark_as_available', function(e){
    e.preventDefault()
    $('.view_modal').find('.overlay').removeClass('hide');
    $.ajax({
        method: 'get',
        url: $(this).attr('href'),
        dataType: 'json',
        success: function(result) {
            loadServiceStaffAvailability(false);
        },
    });
})
var service_staff_availability_interval = null;

function loadServiceStaffAvailability(show = true) {
    var location_id = $('[name="location_id"]').val();
    $.ajax({
        method: 'get',
        url: $('#show_service_staff_availability').attr('data-href'),
        dataType: 'html',
        data: {location_id: location_id},
        success: function(result) {
            $('.view_modal').html(result);
            if (show) {
                $('.view_modal').modal('show')

                //auto refresh service staff availabilty if modal is open
                service_staff_availability_interval = setInterval(function () {
                    loadServiceStaffAvailability(false);
                }, 60000);
            }
        },
    });
}

$(document).on('hidden.bs.modal', '.view_modal', function(){
    if (service_staff_availability_interval !== null) {
        clearInterval(service_staff_availability_interval);
    }
    service_staff_availability_interval = null;
});


$(document).on('change', '#res_waiter_id', function(e){
    var is_enable = $(this).find('option:selected').data('is_enable');

    if(is_enable){
        swal({
            text: LANG.enter_pin_here,
            buttons: true,
            dangerMode: true,
            content: {
                element: "input",
                attributes: {
                    placeholder: LANG.enter_pin_here,
                    type: "password",
                },
            },
        })
        .then((inputValue) => {
            if (inputValue !== null) {
                    $.ajax({
                        method: 'get',
                        url: '/modules/data/check-staff-pin',
                        dataType: 'json',
                        data: {
                        service_staff_pin: inputValue,
                        user_id : $("#res_waiter_id").val(),
                        },
                        success: (result) => {

                            if (result == false) {
                                toastr.error(LANG.authentication_failed);
                                $("#res_waiter_id").val('');
                            } else {
                                // AJAX request succeeded, resolve
                                toastr.success(LANG.authentication_successfull);
                            }
                        },
                    });
            } else {
                // Handle the "Cancel" action
                $("#res_waiter_id").val('');
            }
        });
        
    }
})

// update serial number of product item
function update_serial_no(){
    $('.product_row').each(function (index) {
        // Add the serial number to the first <td> of each row (index + 1 to start from 1)
        if ($(this).find('td:first').hasClass('serial_no')) {
            $(this).find('td:first').text(index + 1);
        }
    });
}


/**
 * Saves the serialized form data from #add_pos_sell_form into LocalStorage.
 */
function saveFormDataToLocalStorage(force) {

    if (!force && !global_is_clear_local_storage && Date.now() < pos_display_block_save_until) {
        return false;
    }

    // Check if global_is_clear_local_storage is true and reset it to false if so
    if(global_is_clear_local_storage){
        localStorage.setItem("pos_form_data_array", JSON.stringify([]));
        global_is_clear_local_storage = false;
        pos_broadcast_cart_to_display([]);
        return false;
    }

    // var storedArrayData = JSON.parse(localStorage.getItem("pos_form_data_array"));

    // console.log("All data afer clear:", storedArrayData);

    let form = $('form#add_pos_sell_form'); // Select the form by ID
    if (form.length === 0) {
        form = $('form#edit_pos_sell_form');
    }
    // Check if the form exists in the DOM
    if (form.length === 0) {
        return;
    }
    // Serialize form data into an array of objects: [{name: 'input_name', value: 'input_value'}, ...]
    let formArray = form.serializeArray();

    // Find if "price_total" already exists in the array
    let priceIndex = formArray.findIndex(item => item.name === "price_total");

    if (priceIndex !== -1) {
        // If exists, update the value
        formArray[priceIndex].value = get_subtotal();
    } else {
        // If not exists, push new entry
        formArray.push({ name: "price_total", value: get_subtotal() });
    }

    // Find if "order_tax" already exists in the array
    let textIndex = formArray.findIndex(item => item.name === "order_tax");

    if (priceIndex !== -1) {
        // If exists, update the value
        formArray[textIndex].value = $("#order_tax").text().trim();
    } else {
        // If not exists, push new entry
        formArray.push({ name: "order_tax", value: $("#order_tax").text().trim()});
    }

    // Find if "shipping_charges_amount" already exists in the array
    let shipping_charges_amount = formArray.findIndex(item => item.name === "shipping_charges_amount");

    if (priceIndex !== -1) {
        // If exists, update the value
        formArray[shipping_charges_amount].value = $("#shipping_charges_amount").text().trim();
    } else {
        // If not exists, push new entry
        formArray.push({ name: "shipping_charges_amount", value: $("#shipping_charges_amount").text().trim()});
    }

    // Find if "total_paying_input" already exists in the array
    let total_paying_input = formArray.findIndex(item => item.name === "total_paying_input");
    
    if (priceIndex !== -1) {
        // If exists, update the value
        formArray[total_paying_input].value = $("#total_paying_input").val();
    } else {
        // If not exists, push new entry
        formArray.push({ name: "total_paying_input", value: $("#total_paying_input").val()});
    }

    // Find if "change_return" already exists in the array
    let change_return = formArray.findIndex(item => item.name === "change_return");
    if (priceIndex !== -1) {
        // If exists, update the value
        formArray[change_return].value = $("#change_return").val();
    } else {
        // If not exists, push new entry
        formArray.push({ name: "change_return", value: $("#change_return").val()});
    }
     // Find if "in_balance_due" already exists in the array
     let in_balance_due = formArray.findIndex(item => item.name === "in_balance_due");
     if (priceIndex !== -1) {
         // If exists, update the value
         formArray[in_balance_due].value = $("#in_balance_due").val();
     } else {
         // If not exists, push new entry
         formArray.push({ name: "in_balance_due", value: $("#in_balance_due").val()});
     }

    var finalTotalIndex = formArray.findIndex(function(item) { return item.name === 'final_total'; });
    var finalTotalVal = $('#final_total_input').val();
    if (finalTotalVal !== undefined && finalTotalVal !== null && finalTotalVal !== '') {
        if (finalTotalIndex !== -1) {
            formArray[finalTotalIndex].value = finalTotalVal;
        } else {
            formArray.push({ name: 'final_total', value: finalTotalVal });
        }
    }

    // Store serialized data in LocalStorage as a JSON string
    localStorage.setItem("pos_form_data_array", JSON.stringify(formArray));
    pos_broadcast_cart_to_display(formArray);

    // console.log("Form data successfully saved to LocalStorage.");
}

/**
 * Notify customer display to refresh cart immediately (BroadcastChannel + storage ping).
 */
function pos_broadcast_cart_to_display(formArray) {
    var ts = Date.now();
    try {
        localStorage.setItem('pos_display_cart_ts', String(ts));
    } catch (e) {}
    try {
        if (vspos_display_channel) {
            vspos_display_channel.postMessage({
                type: 'cart_sync',
                ts: ts,
                form: formArray || null,
            });
        }
    } catch (e) {}
}

/**
 * Queue customer from display until POS Select2 is ready.
 */
function pos_queue_display_customer(customerData) {
    if (!customerData || customerData.id == null || customerData.id === '') {
        return;
    }

    var queue = window._posDisplayCustomerQueue || [];
    var targetId = String(customerData.id);
    var eventTs = customerData.ts || 0;
    var duplicate = queue.some(function(item) {
        return String(item.id) === targetId && (item.ts || 0) === eventTs;
    });

    if (!duplicate) {
        queue.push(customerData);
        window._posDisplayCustomerQueue = queue;
    }
}

function pos_flush_display_customer_queue() {
    if (!$('select#customer_id').length || !$('select#customer_id').hasClass('select2-hidden-accessible')) {
        return false;
    }

    var queue = window._posDisplayCustomerQueue || [];
    var applied = false;

    while (queue.length) {
        var customer = queue.shift();
        if (pos_select_customer_from_display(customer)) {
            applied = true;
        }
    }

    window._posDisplayCustomerQueue = queue;
    return applied;
}

/**
 * On POS boot: prefer customer linked on display over walk-in default.
 */
function pos_try_apply_display_customer_on_boot() {
    var applied = pos_flush_display_customer_queue();
    if (applied) {
        return true;
    }

    try {
        var raw = localStorage.getItem('pos_display_customer_select');
        if (raw) {
            var data = JSON.parse(raw);
            var defaultId = String($('#default_customer_id').val() || '');
            if (data && data.id && String(data.id) !== defaultId) {
                if (pos_select_customer_from_display(data)) {
                    return true;
                }
            }
        }
    } catch (e) {}

    return false;
}

/**
 * Run the same side effects as manual customer selection in POS.
 */
function pos_run_customer_select_side_effects(data) {
    if (!data) {
        return;
    }

    if (data.pay_term_number) {
        $('input#pay_term_number').val(data.pay_term_number);
    } else {
        $('input#pay_term_number').val('');
    }

    if (data.pay_term_type) {
        $('#add_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
        $('#edit_sell_form select[name="pay_term_type"]').val(data.pay_term_type);
    } else {
        $('#add_sell_form select[name="pay_term_type"]').val('');
        $('#edit_sell_form select[name="pay_term_type"]').val('');
    }

    update_shipping_address(data);
    $('#advance_balance_text').text(__currency_trans_from_en(data.balance), true);
    $('#advance_balance').val(data.balance);
    if (typeof updatePosCustomerBalanceBadges === 'function') {
        updatePosCustomerBalanceBadges({
            due: '',
            advance: data.balance
        });
    }

    if (data.price_calculation_type == 'selling_price_group') {
        $('#price_group').val(data.selling_price_group_id);
        $('#price_group').change();
    }

    get_contact_due(data.id);

    saveFormDataToLocalStorage(true);
    pos_publish_customer_to_display(data, {
        show_welcome: !data.from_display,
    });
}

/**
 * Force Select2 customer dropdown to show the chosen label.
 */
function pos_refresh_customer_select2_ui($select, text) {
    if (!$select || !$select.length) {
        return;
    }
    var label = text || $select.find('option:selected').text() || '';
    var $container = $select.next('.select2-container');
    if (!$container.length && $select.data('select2')) {
        $container = $select.data('select2').$container;
    }
    if ($container.length) {
        var $rendered = $container.find('.select2-selection__rendered');
        $rendered.text(label).attr('title', label);
        $rendered.removeClass('select2-selection__placeholder');
    }
}

/**
 * Apply a customer record to the POS contact dropdown (Select2 + handlers).
 */
function pos_apply_customer_to_pos_select(customer) {
    if (!customer || customer.id == null || customer.id === '') {
        return false;
    }

    var $select = $('select#customer_id');
    if (!$select.length) {
        return false;
    }

    var customerId = String(customer.id);
    var customerText = (customer.text || customer.name || '').trim();
    var prefix = (customer.prefix || '').trim();
    var first = (customer.first_name || '').trim();
    if (prefix && first && customerText.indexOf(first) === -1) {
        customerText = (prefix + ' ' + first).trim();
    }
    if (!customerText) {
        customerText = 'Customer #' + customerId;
    }

    var select2Payload = $.extend({}, customer, {
        id: customer.id,
        text: customerText,
    });

    if ($select.hasClass('select2-hidden-accessible')) {
        try {
            $select.select2('close');
        } catch (e) {}
    }

    $select.find('option[value=""]').prop('selected', false);
    $select.find('option').prop('selected', false);

    var $existing = $select.find('option').filter(function() {
        return String(this.value) === customerId;
    });
    if ($existing.length) {
        $existing.text(customerText).prop('selected', true);
    } else {
        $select.append(new Option(customerText, customerId, true, true));
    }

    $select[0].value = customerId;
    $select.val(customerId);

    if ($select.data('select2')) {
        $select.data('select2').$container
            .find('.select2-selection__rendered')
            .text(customerText)
            .attr('title', customerText)
            .removeClass('select2-selection__placeholder');
    }

    pos_refresh_customer_select2_ui($select, customerText);

    $select.trigger({
        type: 'select2:select',
        params: { data: select2Payload },
    });
    $select.trigger('change');
    pos_run_customer_select_side_effects(select2Payload);

    setTimeout(function() {
        pos_refresh_customer_select2_ui($select, customerText);
    }, 50);
    setTimeout(function() {
        pos_refresh_customer_select2_ui($select, customerText);
    }, 250);

    return true;
}

/**
 * Select customer on POS when linked from customer display screen.
 */
function pos_select_customer_from_display(customerData) {
    if (!customerData || customerData.id == null || customerData.id === '') {
        return false;
    }

    var eventTs = customerData.ts || 0;
    var targetId = String(customerData.id);
    var $select = $('select#customer_id');

    if (!$select.length) {
        return false;
    }

    if (String($select.val() || '') === targetId) {
        if (eventTs) {
            pos_last_applied_customer_ts = eventTs;
        }
        pos_apply_customer_to_pos_select(customerData);
        return true;
    }

    if (eventTs && eventTs === pos_last_applied_customer_ts) {
        return true;
    }

    if (pos_customer_from_display_pending === targetId) {
        return false;
    }
    pos_customer_from_display_pending = targetId;

    var finish = function(customer) {
        pos_apply_customer_to_pos_select(customer);
        if (eventTs) {
            pos_last_applied_customer_ts = eventTs;
            pos_last_display_customer_ts = Math.max(pos_last_display_customer_ts, eventTs);
        }
        $(document).trigger('vspos:customer_from_display', [customer]);
        var msg = (typeof LANG !== 'undefined' && LANG.cd_customer_selected_from_display)
            ? LANG.cd_customer_selected_from_display
            : 'Customer selected from display screen';
        toastr.success(msg);
    };

    // Use payload directly when it already has full customer fields (server session / display)
    if (customerData.text && customerData.id) {
        finish(customerData);
        pos_customer_from_display_pending = null;
        return true;
    }

    $.ajax({
        url: '/contacts/customers',
        method: 'GET',
        data: { id: customerData.id },
        dataType: 'json',
    }).done(function(results) {
        var customer = (Array.isArray(results) && results.length) ? results[0] : customerData;
        finish(customer);
    }).fail(function() {
        finish(customerData);
    }).always(function() {
        if (pos_customer_from_display_pending === targetId) {
            pos_customer_from_display_pending = null;
        }
    });

    return true;
}

// Expose immediately for customer display tab (window.opener)
window.pos_select_customer_from_display = function(customerData) {
    if (!customerData || customerData.id == null || customerData.id === '') {
        return false;
    }

    pos_queue_display_customer(customerData);

    if (!$('select#customer_id').length || !$('select#customer_id').hasClass('select2-hidden-accessible')) {
        return false;
    }

    return pos_select_customer_from_display(customerData);
};

window.addEventListener('message', function(e) {
    if (!e.data || e.origin !== window.location.origin) {
        return;
    }
    if (e.data.type === 'vspos_customer_select' && e.data.customer) {
        pos_select_customer_from_display(e.data.customer);
    }
});

function pos_poll_display_customer_select() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    try {
        var raw = localStorage.getItem('pos_display_customer_select');
        if (raw) {
            var data = JSON.parse(raw);
            if (data.id) {
                var currentId = String($('select#customer_id').val() || '');
                var targetId = String(data.id);
                var defaultId = String($('#default_customer_id').val() || '');
                var eventTs = data.ts || 0;
                var needsApply = currentId !== targetId;
                var needsRefresh = eventTs && eventTs > pos_last_applied_customer_ts;

                if (needsApply || needsRefresh) {
                    pos_select_customer_from_display(data);
                } else if (targetId && targetId !== defaultId && currentId === targetId) {
                    pos_refresh_customer_select2_ui($('select#customer_id'), data.text || data.name || '');
                }
            }
        }
    } catch (err) {}
}

function pos_poll_server_display_customer() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    // Avoid stacking requests / Pace spinner loops
    if (window._posPendingCustomerAjax) {
        return;
    }
    window._posPendingCustomerAjax = true;

    $.ajax({
        url: '/pos/display/pending-customer',
        method: 'GET',
        dataType: 'json',
        cache: false,
        global: false, // do not trigger Pace.restart()
    }).done(function(customer) {
        if (!customer || !customer.id) {
            return;
        }

        var currentId = String($('select#customer_id').val() || '');
        var targetId = String(customer.id);
        var eventTs = customer.ts || 0;

        if (currentId === targetId) {
            if (eventTs) {
                pos_last_applied_customer_ts = eventTs;
            }
            return;
        }

        if (eventTs && eventTs === pos_last_applied_customer_ts) {
            return;
        }

        pos_select_customer_from_display(customer);
    }).always(function() {
        window._posPendingCustomerAjax = false;
    });
}

function pos_clear_server_display_customer() {
    try {
        $.ajax({
            url: '/pos/display/clear-customer',
            method: 'POST',
            global: false,
            data: { _token: $('meta[name="csrf-token"]').attr('content') },
        });
    } catch (e) {}
}

function pos_init_customer_display_sync() {
    pos_flush_pending_display_cart_request();

    pos_poll_display_customer_select();
    pos_poll_server_display_customer();

    if (window._posDisplayCustomerPoll) {
        clearInterval(window._posDisplayCustomerPoll);
    }
    // LocalStorage only — fast, no AJAX / no Pace spinner
    window._posDisplayCustomerPoll = setInterval(pos_poll_display_customer_select, 400);

    if (window._posDisplayServerCustomerPoll) {
        clearInterval(window._posDisplayServerCustomerPoll);
    }
    // Server fallback — rare, silent to Pace
    window._posDisplayServerCustomerPoll = setInterval(pos_poll_server_display_customer, 3000);

    if (window._posPreservedDisplayCustomer) {
        window._posPreservedDisplayCustomer = null;
    }

    // Pick up customer linked on display while POS was loading
    setTimeout(function() {
        pos_poll_display_customer_select();
        pos_poll_server_display_customer();
    }, 800);
    setTimeout(function() {
        pos_poll_display_customer_select();
    }, 2000);

    pos_start_display_heartbeat();
    pos_notify_customer_display_refresh();
    pos_flush_pending_display_cart_request();
}

/**
 * Ping so customer display knows POS is open and can accept live updates.
 */
function pos_start_display_heartbeat() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    if (window._posDisplayHeartbeat) {
        clearInterval(window._posDisplayHeartbeat);
    }

    var ping = function() {
        try {
            localStorage.setItem('pos_display_heartbeat', String(Date.now()));
        } catch (e) {}
    };

    ping();
    window._posDisplayHeartbeat = setInterval(ping, 2000);
}

if (!window._posDisplayHeartbeatUnloadBound) {
    window._posDisplayHeartbeatUnloadBound = true;
    $(window).on('beforeunload', function() {
        try {
            localStorage.removeItem('pos_display_heartbeat');
        } catch (e) {}
    });
}

/**
 * Clear stale cart/customer data and start a new POS session for customer display.
 */
function pos_reset_customer_display_storage() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    var sessionId = String(Date.now());
    pos_display_block_save_until = Date.now() + 200;
    window._posPreservedDisplayCustomer = null;

    pos_clear_display_local_storage(sessionId);

    try {
        localStorage.removeItem('pos_display_heartbeat');
    } catch (err) {}

    pos_broadcast_cart_to_display([]);
    pos_clear_server_display_customer();
    pos_broadcast_display_reset(sessionId);
    pos_signal_customer_display_reload();
    setTimeout(pos_signal_customer_display_reload, 500);
    setTimeout(pos_signal_customer_display_reload, 1500);
}

/**
 * Tell customer display tab to reset when POS loads or is refreshed.
 */
function pos_notify_customer_display_refresh() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    setTimeout(function() {
        try {
            var refreshTs = String(Date.now());
            localStorage.setItem('pos_display_refresh', refreshTs);
        } catch (err) {}
        pos_signal_customer_display_reload();
        saveFormDataToLocalStorage(true);
    }, 600);
}

/**
 * Notify customer display tab that a sale was completed (thank-you screen).
 */
function pos_notify_customer_display_sale_complete() {
    if ($('form#add_pos_sell_form').length === 0 && $('form#edit_pos_sell_form').length === 0) {
        return;
    }

    try {
        if (window.VsposVfdDisplay && typeof window.VsposVfdDisplay.showAmount === 'function') {
            window.VsposVfdDisplay.showAmount(0, 0);
        }
    } catch (e) {}

    var $sel = $('select#customer_id');
    var defaultId = String($('#default_customer_id').val() || '');
    var cid = String($sel.val() || '');
    var payload = { ts: Date.now(), prefix: '', customerName: '', first_name: '' };

    if ($sel.length && $sel.data('select2')) {
        try {
            var s2data = $sel.select2('data');
            if (s2data && s2data.length && s2data[0]) {
                var row = s2data[0];
                payload.customerName = (row.text || row.name || '').trim();
                payload.prefix = String(row.prefix || '').trim();
                payload.first_name = String(row.first_name || '').trim();
            }
        } catch (e) {}
    }

    if (!payload.customerName && cid && cid !== defaultId && $sel.length) {
        var $opt = $sel.find('option:selected');
        payload.customerName = ($opt.text() || '').trim();
        payload.prefix = String($opt.data('prefix') || '').trim();
        payload.first_name = String($opt.data('first-name') || '').trim();
    }

    if (!payload.customerName) {
        try {
            var rawLinked = localStorage.getItem('pos_display_customer_select');
            if (rawLinked) {
                var linked = JSON.parse(rawLinked);
                if (linked && linked.id && String(linked.id) !== defaultId) {
                    payload.customerName = (linked.name || linked.text || '').trim();
                    payload.prefix = String(linked.prefix || '').trim();
                    payload.first_name = String(linked.first_name || '').trim();
                }
            }
        } catch (e) {}
    }

    if (!payload.customerName) {
        var barName = ($('.customer_details').first().text() || '').trim();
        if (barName && barName.toLowerCase().indexOf('walk') === -1) {
            payload.customerName = barName;
        }
    }

    function pushSaleCompleteToDisplay() {
        try {
            localStorage.setItem('pos_display_sale_completed', JSON.stringify(payload));
        } catch (err) {}

        try {
            if (vspos_display_channel) {
                vspos_display_channel.postMessage({ type: 'sale_complete', data: payload });
            }
        } catch (err) {}
    }

    pushSaleCompleteToDisplay();
    setTimeout(pushSaleCompleteToDisplay, 80);
    setTimeout(pushSaleCompleteToDisplay, 300);
}

// Print Last Bill / Last Purchase button handler
$(document).on('click', '.js-print-last-bill', function() {
    var transaction_id = $(this).data('id');
    if (!transaction_id) return;
    var is_pop = $('#is_pop_mode').length > 0;
    var url = is_pop
        ? '/purchase-pos/' + transaction_id + '/print'
        : '/sells/' + transaction_id + '/print?check_location=true';
    $.ajax({
        url: url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success == 1) {
                if (data.receipt && data.receipt.is_enabled) {
                    pos_print(data.receipt);
                } else if (data.receipt && data.receipt.html_content) {
                    pos_print(data.receipt);
                } else {
                    toastr.warning('Receipt not enabled for this location.');
                }
            } else {
                toastr.error(data.msg || 'Could not load receipt.');
            }
        },
        error: function() {
            toastr.error('Print failed. Please try again.');
        }
    });
});

function pos_update_last_bill(result) {
    if (!result.invoice_no || !result.transaction_id) return;
    var amountText = (typeof __currency_trans_from_en === 'function')
        ? __currency_trans_from_en(result.final_total, true)
        : result.final_total;
    var is_pop = $('#is_pop_mode').length > 0 || !!result.is_pop;
    var kicker = is_pop ? 'Last purchase' : 'Last bill';

    // Classic last-bill bar
    var bar = $('.vspos-last-bill-bar');
    if (!bar.length) {
        var html = '<div class="vspos-last-bill-bar">'
            + '<div class="vspos-lb-info">'
            + '<span class="vspos-lb-kicker"><i class="fas fa-receipt"></i> ' + kicker + '</span>'
            + '<span class="vspos-lb-invoice"># ' + result.invoice_no + '</span>'
            + '<span class="vspos-lb-amount">' + amountText + '</span>'
            + '</div>'
            + '<button type="button" class="vspos-lb-print-btn js-print-last-bill" data-id="' + result.transaction_id + '" title="Reprint">'
            + '<i class="fas fa-print"></i> Reprint</button>'
            + '</div>';
        $('.vspos-op-header').before(html);
    } else {
        bar.find('.vspos-lb-kicker').html('<i class="fi fi-rr-receipt pos-fi-icon"></i> ' + kicker);
        bar.find('.vspos-lb-invoice').text('# ' + result.invoice_no);
        bar.find('.vspos-lb-amount').text(amountText);
        bar.find('.js-print-last-bill').attr('data-id', result.transaction_id).data('id', result.transaction_id).prop('disabled', false);
        if (is_pop) {
            var $details = bar.find('.vspos-lb-actions .btn-modal').first();
            if ($details.length) {
                $details.attr('data-href', '/purchases/' + result.transaction_id);
            }
        }
    }

    // Bills & Reprint hub (Touch + classic header shortcut)
    var $hub = $('#vspos_bills_hub_last');
    if ($hub.length) {
        $hub.find('.vspos-hub-invoice').text('# ' + result.invoice_no);
        $hub.find('.vspos-hub-amount').text(amountText);
        $hub.find('.js-print-last-bill')
            .attr('data-id', result.transaction_id)
            .data('id', result.transaction_id)
            .prop('disabled', false);
        if (is_pop) {
            var $hubDetails = $hub.find('.vspos-bills-hub-details');
            if ($hubDetails.length) {
                $hubDetails.attr('data-href', '/purchases/' + result.transaction_id).prop('disabled', false);
            }
        }
    }

    // POP header last-purchase chip + touch cart footer
    var $hdr = $('#vspos_header_last_bill');
    if ($hdr.length) {
        $hdr.find('.vspos-hub-invoice, .vspos-hlb-invoice').text('# ' + result.invoice_no);
        $hdr.find('.vspos-hub-amount, .vspos-hlb-amount').text(amountText);
        $hdr.find('.js-print-last-bill')
            .attr('data-id', result.transaction_id)
            .data('id', result.transaction_id)
            .prop('disabled', false);
    }
    var $tlp = $('#vspos_touch_last_purchase');
    if ($tlp.length) {
        $tlp.find('.vspos-hub-invoice, .vspos-tlp-invoice').text('# ' + result.invoice_no);
        $tlp.find('.vspos-hub-amount, .vspos-tlp-amount').text(amountText);
        $tlp.find('.js-print-last-bill')
            .attr('data-id', result.transaction_id)
            .data('id', result.transaction_id)
            .prop('disabled', false);
    }

    if (typeof vspos_load_customer_sales_history === 'function') {
        vspos_load_customer_sales_history($('#customer_id').val(), { force: true, for_modal: true });
    }
}
