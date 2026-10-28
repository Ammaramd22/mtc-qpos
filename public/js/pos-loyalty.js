/**
 * POS Loyalty: mobile lookup / quick add + redeem OTP helpers
 */
(function ($) {
    'use strict';

    var selectedCustomer = null;
    var otpVerified = false;
    var focusBarcodeAfterClose = false;

    function csrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val();
    }

    function focusBarcodeInput() {
        var $barcode = $('#search_product');
        if (!$barcode.length) {
            $barcode = $('input[name="search_product"]').first();
        }
        if ($barcode.length) {
            $barcode.trigger('focus').select();
        }
    }

    function setCustomerOnPos(customer) {
        if (!customer || !customer.id) {
            return;
        }
        var $sel = $('select#customer_id');
        var label = customer.text || customer.name || ('#' + customer.id);
        if ($sel.find('option[value="' + customer.id + '"]').length === 0) {
            $sel.append($('<option>', { value: customer.id, text: label }));
        }
        $sel.val(customer.id).trigger('change');
        if (typeof pos_refresh_customer_select2_ui === 'function') {
            pos_refresh_customer_select2_ui($sel, label);
        }
        if (typeof getCustomerRewardPoints === 'function') {
            getCustomerRewardPoints();
        }
        if (typeof update_shipping_address === 'function') {
            update_shipping_address(customer);
        }
        focusBarcodeAfterClose = true;
    }

    function showLookupStep() {
        $('#loyalty_lookup_step').removeClass('hide');
        $('#loyalty_create_step').addClass('hide');
        $('#loyalty_search_btn').removeClass('hide');
        $('#loyalty_create_btn').addClass('hide');
        $('#loyalty_select_btn').addClass('hide');
        $('#loyalty_lookup_result').addClass('hide').empty();
        selectedCustomer = null;
    }

    function showCreateStep(mobile) {
        $('#loyalty_lookup_step').addClass('hide');
        $('#loyalty_create_step').removeClass('hide');
        $('#loyalty_search_btn').addClass('hide');
        $('#loyalty_select_btn').addClass('hide');
        $('#loyalty_create_btn').removeClass('hide');
        $('#loyalty_new_mobile').val(mobile || '');
        $('#loyalty_new_first_name').val('').focus();
    }

    function renderFound(customer) {
        selectedCustomer = customer;
        var rp = Math.max(0, parseFloat(customer.total_rp != null ? customer.total_rp : 0) || 0);
        var html =
            '<div class="loyalty-found-card">' +
            '<div class="loyalty-found-name">' +
            $('<div>').text(customer.name || customer.text).html() +
            '</div>' +
            '<div class="loyalty-found-meta">' +
            (LANG.mobile || 'Mobile') +
            ': ' +
            $('<div>').text(customer.mobile || '').html() +
            '</div>' +
            '<div class="loyalty-found-points"><i class="fa fa-gift"></i> ' +
            rp +
            ' ' +
            ($('#rp_name').val() || 'points') +
            '</div>' +
            '</div>';
        $('#loyalty_lookup_result').html(html).removeClass('hide');
        $('#loyalty_select_btn').removeClass('hide');
        $('#loyalty_create_btn').addClass('hide');
        // Ready for second Enter to add customer
        setTimeout(function () {
            $('#loyalty_mobile_input').trigger('focus').select();
        }, 50);
    }

    function lookupMobile() {
        var mobile = $.trim($('#loyalty_mobile_input').val());
        if (!mobile) {
            toastr.error(LANG.please_enter || 'Please enter mobile number');
            return;
        }
        $.ajax({
            method: 'POST',
            url: '/sells/pos/loyalty/lookup',
            data: { mobile: mobile, _token: csrfToken() },
            dataType: 'json',
            success: function (res) {
                if (res.success && res.customer) {
                    renderFound(res.customer);
                } else if (res.success && !res.customer) {
                    showCreateStep(mobile);
                } else {
                    toastr.error(res.msg || (LANG.something_went_wrong || 'Something went wrong'));
                }
            },
            error: function () {
                toastr.error(LANG.something_went_wrong || 'Something went wrong');
            },
        });
    }

    function createCustomer() {
        var payload = {
            _token: csrfToken(),
            first_name: $.trim($('#loyalty_new_first_name').val()),
            last_name: $.trim($('#loyalty_new_last_name').val()),
            mobile: $.trim($('#loyalty_new_mobile').val()),
            email: $.trim($('#loyalty_new_email').val()),
            address_line_1: $.trim($('#loyalty_new_address').val()),
        };
        if (!payload.first_name || !payload.mobile) {
            toastr.error('Name and mobile are required');
            return;
        }
        $.ajax({
            method: 'POST',
            url: '/sells/pos/loyalty/quick-create',
            data: payload,
            dataType: 'json',
            success: function (res) {
                if (res.success && res.customer) {
                    setCustomerOnPos(res.customer);
                    $('#loyaltyCustomerModal').modal('hide');
                    toastr.success(res.msg || 'Customer added');
                } else {
                    toastr.error(res.msg || (LANG.something_went_wrong || 'Something went wrong'));
                }
            },
            error: function (xhr) {
                var msg =
                    (xhr.responseJSON && xhr.responseJSON.msg) ||
                    LANG.something_went_wrong ||
                    'Something went wrong';
                toastr.error(msg);
            },
        });
    }

    function focusLoyaltyMobileInput() {
        var $input = $('#loyalty_mobile_input');
        if (!$input.length || !$('#loyalty_lookup_step').is(':visible') || $('#loyalty_lookup_step').hasClass('hide')) {
            return;
        }
        // Native focus is more reliable than jQuery trigger inside Bootstrap modal
        try {
            $input[0].focus({ preventScroll: true });
            $input[0].select();
        } catch (e) {
            $input.trigger('focus').select();
        }
    }

    function openLoyaltyModal(force) {
        if ($('form#edit_pos_sell_form').length || $('form#edit_sell_form').length) {
            return;
        }
        if (!force && !$('#loyalty_customer_shortcut').length && !$('#loyalty_ask_mobile_on_pos').length) {
            return;
        }
        if (!$('#loyaltyCustomerModal').length) {
            return;
        }
        // Don't open over pay popup
        if ($('#vspos_mobile_pay_popup').hasClass('vspos-mobile-pay-popup--open')) {
            return;
        }
        showLookupStep();
        $('#loyalty_mobile_input').val('');
        $('#loyaltyCustomerModal').modal('show');
    }

    function openLoyaltyModalIfNeeded() {
        if (!$('#loyalty_ask_mobile_on_pos').length) {
            return;
        }
        setTimeout(function () {
            openLoyaltyModal(true);
        }, 400);
    }

    window.vsposOpenLoyaltyCustomer = function () {
        openLoyaltyModal(true);
    };

    // —— Redeem OTP ——
    function otpEnabled() {
        return $('#loyalty_otp_on_redeem').length > 0;
    }

    function resetOtpUi() {
        otpVerified = false;
        $('#loyalty_otp_verified').val('0');
        $('#loyalty_otp_code').val('');
        $('#loyalty_otp_status').text('').removeClass('text-success text-danger');
        updateRedeemGate();
    }

    function updateRedeemGate() {
        if (!otpEnabled()) {
            return;
        }
        var hasCard = $('#loyalty_has_card').is(':checked');
        var points = parseInt($('#rp_redeemed_modal').val(), 10) || 0;
        var ok = points <= 0 || hasCard || otpVerified;
        $('#loyalty_otp_block').toggleClass('hide', hasCard || points <= 0);
        if (!ok && points > 0) {
            $('#posEditDiscountModalUpdate').prop('disabled', true);
        } else {
            $('#posEditDiscountModalUpdate').prop('disabled', false);
        }
    }

    window.loyaltyRedeemAllowed = function () {
        if (!otpEnabled()) {
            return true;
        }
        // Discount-modal redeem points
        var points = parseInt($('#rp_redeemed_modal').val(), 10) || 0;
        // Or loyalty payment-row amount
        var loyaltyAmt = 0;
        $('#payment_rows_div .payment_row').each(function () {
            if ($(this).find('.payment_types_dropdown').val() === 'loyalty') {
                loyaltyAmt += typeof __read_number === 'function'
                    ? __read_number($(this).find('.payment-amount'))
                    : (parseFloat($(this).find('.payment-amount').val()) || 0);
            }
        });
        if (points <= 0 && loyaltyAmt <= 0) {
            return true;
        }
        if ($('#loyalty_has_card').is(':checked')) {
            return true;
        }
        return otpVerified || $('#loyalty_otp_verified').val() === '1';
    };

    window.vsposHasLoyaltyPayment = function () {
        var found = false;
        $('#payment_rows_div .payment_row').each(function () {
            if ($(this).find('.payment_types_dropdown').val() === 'loyalty') {
                var amt = typeof __read_number === 'function'
                    ? __read_number($(this).find('.payment-amount'))
                    : (parseFloat($(this).find('.payment-amount').val()) || 0);
                if (amt > 0) {
                    found = true;
                }
            }
        });
        return found;
    };

    function parsePts(val) {
        return parseInt(String(val || '0').replace(/,/g, ''), 10) || 0;
    }

    function perPointAmount() {
        var v = parseFloat($('#rp_redeemed_modal').attr('data-amount_per_unit_point'))
            || parseFloat($('#rp_redeemed_modal').data('amount_per_unit_point'));
        return v > 0 ? v : 1;
    }

    function maxLoyaltyPoints() {
        var fromAttr = parsePts($('#rp_redeemed_modal').attr('data-max_points'));
        var fromData = parsePts($('#rp_redeemed_modal').data('max_points'));
        var fromGlobal = parsePts(window.__loyaltyRedeemablePts);
        var fromAvail = parsePts($('#available_rp').text());
        var fromBadge = parsePts($('#pos_loyalty_points_value').text());
        // Prefer redeemable; fall back to displayed balance for UI
        return Math.max(fromAttr, fromData, fromGlobal, fromAvail, fromBadge);
    }

    function loyaltyBalancePoints() {
        return Math.max(
            parsePts(window.__loyaltyBalancePts),
            parsePts($('#pos_loyalty_points_value').text()),
            parsePts($('#available_rp').text()),
            maxLoyaltyPoints()
        );
    }

    /**
     * Ensure Loyalty option exists on every payment method dropdown,
     * placed before custom_pay_* (and after standard methods).
     */
    window.ensureLoyaltyInPaymentDropdowns = function () {
        if (!$('#reward_point_enabled').length && !$('#rp_redeemed_modal').length) {
            return;
        }
        var label = ($('#rp_name').val() || 'Loyalty Points').trim();
        $('#payment_rows_div select.payment_types_dropdown').each(function () {
            var $select = $(this);
            var $opt = $select.find('option[value="loyalty"]');
            if (!$opt.length) {
                $opt = $('<option>', { value: 'loyalty', text: label });
            } else {
                $opt.text(label);
            }
            var $firstCustom = $select.find('option[value^="custom_pay_"]').first();
            if ($firstCustom.length) {
                $opt.insertBefore($firstCustom);
            } else if (!$opt.parent().length) {
                // After standard methods, before end
                var $anchor = $select.find('option[value="other"], option[value="bank_transfer"], option[value="credit_note"]').last();
                if ($anchor.length) {
                    $opt.insertAfter($anchor);
                } else {
                    $select.append($opt);
                }
            }
        });
    };

    function updateLoyaltyRowUi($row) {
        if (!$row || !$row.length) {
            return;
        }
        var amt = typeof __read_number === 'function'
            ? __read_number($row.find('.payment-amount'))
            : (parseFloat($row.find('.payment-amount').val()) || 0);
        var per = perPointAmount();
        var pts = per > 0 ? Math.floor(amt / per) : 0;
        var maxPts = maxLoyaltyPoints();
        var bal = loyaltyBalancePoints();
        var showPts = maxPts > 0 ? maxPts : bal;
        $row.find('.loyalty-pay-available-pts').text(showPts);
        $row.find('.loyalty-pay-points-eq').text(pts);
        if (otpVerified || $('#loyalty_otp_verified').val() === '1') {
            $row.find('.loyalty-pay-verified-badge').removeClass('hide');
        } else {
            $row.find('.loyalty-pay-verified-badge').addClass('hide');
        }
        var $msg = $row.find('.vspos-loyalty-pay-msg');
        if (typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer()) {
            $msg.removeClass('hide').text('Select a loyalty customer first');
        } else if (maxPts > 0 && pts > maxPts) {
            $msg.removeClass('hide').text('Not enough points (max ' + maxPts + ' pts)');
        } else if ($('#loyalty_redeem_blocked_msg').length && !$('#loyalty_redeem_blocked_msg').hasClass('hide')) {
            $msg.removeClass('hide').text($('#loyalty_redeem_blocked_msg').text());
        } else {
            $msg.addClass('hide').text('');
        }
    }

    window.refreshLoyaltyPayRows = function () {
        $('#payment_rows_div .payment_row').each(function () {
            if ($(this).find('.payment_types_dropdown').val() === 'loyalty') {
                updateLoyaltyRowUi($(this));
            }
        });
    };

    /**
     * Convert loyalty payment rows → rp_redeemed, then remove those rows' amounts
     * so they are not double-counted as cash payments.
     */
    window.vsposConvertLoyaltyPaymentsToRedeem = function () {
        var totalAmount = 0;
        var $loyaltyRows = [];
        $('#payment_rows_div .payment_row').each(function () {
            var $row = $(this);
            if ($row.find('.payment_types_dropdown').val() === 'loyalty') {
                var amt = typeof __read_number === 'function'
                    ? __read_number($row.find('.payment-amount'))
                    : (parseFloat($row.find('.payment-amount').val()) || 0);
                if (amt > 0) {
                    totalAmount += amt;
                    $loyaltyRows.push($row);
                }
            }
        });

        if (totalAmount <= 0) {
            return { ok: true, amount: 0, points: 0 };
        }

        if (typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer()) {
            return { error: 'Select a loyalty customer to redeem points' };
        }

        var per = perPointAmount();
        var points = per > 0 ? Math.floor(totalAmount / per) : 0;
        var maxPts = maxLoyaltyPoints();
        if (maxPts > 0 && points > maxPts) {
            return { error: 'Not enough loyalty points (available: ' + maxPts + ')' };
        }
        // Use exact cash value from amount entered
        var redeemedAmount = totalAmount;
        // Prefer points that match amount; if remainder, keep amount as entered
        if (per > 0 && points * per < totalAmount && (points + 1) <= maxPts) {
            // keep floor points; amount stays as entered for payable reduction
        }

        $('#rp_redeemed_modal').val(points);
        $('#rp_redeemed').val(points);
        $('#rp_redeemed_amount').val(redeemedAmount);
        $('#rp_redeemed_amount_text, #loyalty_amount_display').text(
            typeof __currency_trans_from_en === 'function'
                ? __currency_trans_from_en(redeemedAmount, true)
                : redeemedAmount
        );

        // Zero loyalty payment rows (backend skips method=loyalty anyway)
        $loyaltyRows.forEach(function ($row) {
            if (typeof __write_number === 'function') {
                __write_number($row.find('.payment-amount'), 0);
            } else {
                $row.find('.payment-amount').val(0);
            }
        });

        if (typeof pos_total_row === 'function') {
            pos_total_row();
        }
        if (typeof calculate_balance_due === 'function') {
            calculate_balance_due();
        }

        return { ok: true, amount: redeemedAmount, points: points };
    };

    function sendLoyaltyOtpAndOpenPopup($row) {
        var customer_id = $('#customer_id').val();
        if (!customer_id || (typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer())) {
            toastr.error('Select a loyalty customer first');
            return;
        }
        var mobile = '';
        try {
            mobile = String($('#customer_id option:selected').data('mobile') || $('#customer_id option:selected').text() || '');
        } catch (e) {}

        $.ajax({
            method: 'POST',
            url: '/sells/pos/loyalty/send-otp',
            data: { customer_id: customer_id, _token: csrfToken() },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    toastr.success(res.msg || 'OTP sent');
                    var hint = 'OTP sent to customer mobile';
                    if (res.mobile) {
                        hint = 'OTP sent to ' + res.mobile;
                    }
                    $('#vspos_loyalty_otp_mobile_hint').text(hint);
                    $('#vspos_loyalty_otp_input').val('');
                    $('#vspos_loyalty_otp_modal_status').text(res.msg || 'OTP sent').css('color', '#4ade80');
                    if ($('#vsposLoyaltyOtpModal').length) {
                        $('#vsposLoyaltyOtpModal').modal('show');
                        setTimeout(function () {
                            $('#vspos_loyalty_otp_input').focus();
                        }, 300);
                    } else {
                        toastr.info(res.msg || 'OTP sent — check SMS');
                    }
                    if ($row) {
                        $row.data('otp-sent', true);
                    }
                } else {
                    toastr.error(res.msg || 'Failed to send OTP');
                }
            },
            error: function (xhr) {
                toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'Failed to send OTP');
            },
        });
    }

    function verifyLoyaltyOtpFromPopup() {
        var customer_id = $('#customer_id').val();
        var otp = $.trim($('#vspos_loyalty_otp_input').val());
        if (!otp) {
            toastr.error('Enter OTP');
            return;
        }
        $.ajax({
            method: 'POST',
            url: '/sells/pos/loyalty/verify-otp',
            data: { customer_id: customer_id, otp: otp, _token: csrfToken() },
            dataType: 'json',
            success: function (res) {
                if (res.success) {
                    otpVerified = true;
                    $('#loyalty_otp_verified').val('1');
                    $('#vspos_loyalty_otp_modal_status').text(res.msg || 'Verified').css('color', '#4ade80');
                    toastr.success(res.msg || 'OTP verified');
                    refreshLoyaltyPayRows();
                    setTimeout(function () {
                        $('#vsposLoyaltyOtpModal').modal('hide');
                    }, 400);
                } else {
                    otpVerified = false;
                    $('#loyalty_otp_verified').val('0');
                    $('#vspos_loyalty_otp_modal_status').text(res.msg || 'Invalid OTP').css('color', '#f87171');
                    toastr.error(res.msg || 'Invalid OTP');
                }
            },
            error: function (xhr) {
                otpVerified = false;
                $('#loyalty_otp_verified').val('0');
                toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'Invalid OTP');
            },
        });
    }

    function useSelectedCustomer() {
        if (!selectedCustomer) {
            return false;
        }
        setCustomerOnPos(selectedCustomer);
        $('#loyaltyCustomerModal').modal('hide');
        return true;
    }

    function handleLoyaltyEnter(e) {
        if (e.which !== 13 && e.keyCode !== 13) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();

        if (!$('#loyalty_create_step').hasClass('hide')) {
            createCustomer();
            return;
        }

        if (selectedCustomer && !$('#loyalty_select_btn').hasClass('hide')) {
            useSelectedCustomer();
            return;
        }

        lookupMobile();
    }

    $(document).ready(function () {
        openLoyaltyModalIfNeeded();

        $(document).on('click', '#vspos_loyalty_open_btn', function (e) {
            e.preventDefault();
            openLoyaltyModal(true);
        });

        $(document).on('click', '#loyalty_search_btn', lookupMobile);
        $(document).on('keydown', '#loyaltyCustomerModal', function (e) {
            // Ignore Enter inside buttons that already handle click (except we unify flow)
            if (e.which === 13 || e.keyCode === 13) {
                var tag = (e.target && e.target.tagName) ? e.target.tagName.toLowerCase() : '';
                if (tag === 'textarea') {
                    return;
                }
                handleLoyaltyEnter(e);
            }
            // Esc = skip / close (bootstrap also handles; ensure barcode focus)
            if (e.which === 27 || e.keyCode === 27) {
                focusBarcodeAfterClose = true;
            }
        });
        $(document).on('input', '#loyalty_mobile_input', function () {
            // New digits invalidate previous find — next Enter searches again
            if (selectedCustomer) {
                selectedCustomer = null;
                $('#loyalty_lookup_result').addClass('hide').empty();
                $('#loyalty_select_btn').addClass('hide');
            }
        });
        $(document).on('click', '#loyalty_select_btn', function () {
            useSelectedCustomer();
        });
        $(document).on('click', '#loyalty_create_btn', createCustomer);
        $(document).on('click', '#loyalty_skip_btn', function () {
            showLookupStep();
        });

        $('#loyaltyCustomerModal').on('shown.bs.modal', function () {
            // Focus mobile field after modal animation so typing works immediately
            setTimeout(focusLoyaltyMobileInput, 50);
            setTimeout(focusLoyaltyMobileInput, 200);
        });

        $('#loyaltyCustomerModal').on('hidden.bs.modal', function () {
            // Ensure no stuck backdrop blocks POS / pay clicks
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open');
            if (focusBarcodeAfterClose) {
                focusBarcodeAfterClose = false;
                setTimeout(focusBarcodeInput, 50);
            }
        });

        $(document).on('change', '#loyalty_has_card', function () {
            $('#loyalty_has_card_input').val($(this).is(':checked') ? '1' : '0');
            $('#vspos_mp_loyalty_has_card').prop('checked', $(this).is(':checked'));
            resetOtpUi();
            updateRedeemGate();
        });
        $(document).on('change keyup', '#rp_redeemed_modal', updateRedeemGate);

        $(document).on('click', '#loyalty_send_otp_btn', function () {
            var customer_id = $('#customer_id').val();
            if (!customer_id) {
                toastr.error('Select a customer first');
                return;
            }
            $.ajax({
                method: 'POST',
                url: '/sells/pos/loyalty/send-otp',
                data: { customer_id: customer_id, _token: csrfToken() },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        toastr.success(res.msg || 'OTP sent');
                        $('#loyalty_otp_status').text(res.msg || 'OTP sent').removeClass('text-danger').addClass('text-success');
                    } else {
                        toastr.error(res.msg || 'Failed to send OTP');
                    }
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'Failed to send OTP');
                },
            });
        });

        $(document).on('click', '#loyalty_verify_otp_btn', function () {
            var customer_id = $('#customer_id').val();
            var otp = $.trim($('#loyalty_otp_code').val());
            if (!otp) {
                toastr.error('Enter OTP');
                return;
            }
            $.ajax({
                method: 'POST',
                url: '/sells/pos/loyalty/verify-otp',
                data: { customer_id: customer_id, otp: otp, _token: csrfToken() },
                dataType: 'json',
                success: function (res) {
                    if (res.success) {
                        otpVerified = true;
                        $('#loyalty_otp_verified').val('1');
                        $('#loyalty_otp_status').text(res.msg || 'Verified').removeClass('text-danger').addClass('text-success');
                        toastr.success(res.msg || 'OTP verified');
                        updateRedeemGate();
                        refreshLoyaltyPayRows();
                    } else {
                        otpVerified = false;
                        $('#loyalty_otp_verified').val('0');
                        toastr.error(res.msg || 'Invalid OTP');
                    }
                },
                error: function (xhr) {
                    toastr.error((xhr.responseJSON && xhr.responseJSON.msg) || 'Invalid OTP');
                },
            });
        });

        $(document).on('shown.bs.modal', '#posEditDiscountModal', function () {
            resetOtpUi();
            updateRedeemGate();
        });

        // Gate discount modal update when OTP required
        $(document).on('click', '#posEditDiscountModalUpdate', function (e) {
            if (typeof window.loyaltyRedeemAllowed === 'function' && !window.loyaltyRedeemAllowed()) {
                e.preventDefault();
                e.stopImmediatePropagation();
                toastr.error('Verify OTP or confirm customer has loyalty card before redeeming points');
                return false;
            }
        });

        // ── Loyalty as payment method ──
        ensureLoyaltyInPaymentDropdowns();

        $(document).on('change', '.payment_types_dropdown', function () {
            var $dd = $(this);
            var payment_type = $dd.val();
            var $row = $dd.closest('.payment_row');

            if (payment_type === 'loyalty') {
                if (typeof pos_is_walk_in_customer === 'function' && pos_is_walk_in_customer()) {
                    toastr.error('Select a loyalty customer to use points');
                    $dd.val('cash').trigger('change');
                    return;
                }
                // Only one loyalty row
                var loyaltyCount = 0;
                $('#payment_rows_div .payment_types_dropdown').each(function () {
                    if ($(this).val() === 'loyalty') {
                        loyaltyCount++;
                    }
                });
                if (loyaltyCount > 1) {
                    toastr.warning('Only one Loyalty payment row is allowed');
                    $dd.val('cash').trigger('change');
                    return;
                }
                if (typeof getCustomerRewardPoints === 'function') {
                    getCustomerRewardPoints();
                }
                setTimeout(function () {
                    updateLoyaltyRowUi($row);
                    // Prefill amount with max usable on this bill
                    var per = perPointAmount();
                    var maxPts = maxLoyaltyPoints();
                    var payable = typeof __read_number === 'function'
                        ? __read_number($('#final_total_input'))
                        : (parseFloat($('#final_total_input').val()) || 0);
                    // Current row may already hold full payable — use that as bill total
                    var maxByBill = per > 0 ? Math.floor(payable / per) : 0;
                    var usePts = maxPts > 0 ? Math.min(maxPts, maxByBill || maxPts) : 0;
                    var useAmt = usePts * per;
                    if (useAmt > 0 && typeof __write_number === 'function') {
                        __write_number($row.find('.payment-amount'), useAmt);
                        $row.find('.payment-amount').trigger('change');
                    }
                    updateLoyaltyRowUi($row);
                    $row.find('.payment-amount').focus().select();
                }, 150);
            }
        });

        $(document).on('change keyup', '#payment_rows_div .payment-amount', function () {
            var $row = $(this).closest('.payment_row');
            if ($row.find('.payment_types_dropdown').val() === 'loyalty') {
                updateLoyaltyRowUi($row);
            }
        });

        $(document).on('click', '.vspos-loyalty-send-otp-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();
            sendLoyaltyOtpAndOpenPopup($(this).closest('.payment_row'));
        });

        $(document).on('click', '#vspos_loyalty_otp_verify_btn', function (e) {
            e.preventDefault();
            verifyLoyaltyOtpFromPopup();
        });

        $(document).on('keydown', '#vspos_loyalty_otp_input', function (e) {
            if (e.which === 13 || e.keyCode === 13) {
                e.preventDefault();
                verifyLoyaltyOtpFromPopup();
            }
        });

        // Before classic payment modal finalize, convert loyalty rows
        $(document).on('click', '#pos-save, button.pos-save, #pos-finalize', function () {
            if (typeof vsposHasLoyaltyPayment === 'function' && vsposHasLoyaltyPayment()) {
                if (typeof loyaltyRedeemAllowed === 'function' && !loyaltyRedeemAllowed()) {
                    toastr.error('Send OTP to customer and verify before redeeming loyalty points');
                    return false;
                }
                if (typeof vsposConvertLoyaltyPaymentsToRedeem === 'function') {
                    var conv = vsposConvertLoyaltyPaymentsToRedeem();
                    if (conv && conv.error) {
                        toastr.error(conv.error);
                        return false;
                    }
                }
            }
        });
    });
})(jQuery);
