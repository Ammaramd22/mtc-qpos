/**
 * Mobile Shop — bill payments (reload, utility, other) with custom amounts.
 */
(function ($) {
    'use strict';

    window.vspos_mobile_bill_history_url = window.vspos_mobile_bill_history_url || '';
    var vsposBillHandlersBound = false;

    function vsposBillChipAmount($chip) {
        var raw = $chip.attr('data-amount');
        if (typeof raw === 'undefined' || raw === null) {
            raw = $chip.data('amount');
        }
        return raw;
    }

    function vsposBillGetActivePane($form) {
        var $pane = $form.find('.tab-pane.active');
        if ($pane.length) {
            return $pane;
        }
        return $form;
    }

    function vsposBillGetAmountFromPane($pane) {
        var $wrap = $pane.find('.vspos-bill-amount-wrap').first();
        if (!$wrap.length) {
            $wrap = $pane.closest('form').find('.vspos-bill-amount-wrap').first();
        }
        var $activeChip = $wrap.find('.vspos-bill-amount-chip.is-active').first();
        if ($activeChip.length) {
            var chipAmount = vsposBillChipAmount($activeChip);
            if (String(chipAmount) !== 'custom') {
                return parseFloat(chipAmount) || 0;
            }
        }
        return parseFloat($wrap.find('.vspos-bill-amount-input').val()) || 0;
    }

    function vsposBillApplyChip($chip) {
        var $wrap = $chip.closest('.vspos-bill-amount-wrap');
        var amount = vsposBillChipAmount($chip);
        var $form = $chip.closest('form');

        $wrap.find('.vspos-bill-amount-chip').removeClass('is-active');
        $chip.addClass('is-active');

        if (String(amount) === 'custom') {
            $wrap.find('.vspos-bill-amount-input-wrap').removeClass('hide');
            setTimeout(function () {
                $wrap.find('.vspos-bill-amount-input').trigger('focus');
            }, 20);
        } else {
            $wrap.find('.vspos-bill-amount-input-wrap').addClass('hide');
            $wrap.find('.vspos-bill-amount-input').val(amount);
            if ($form.length) {
                $form.find('.vspos-bill-hidden-amount').val(amount);
            }
        }
    }

    function vsposBillSyncHiddenFields($form) {
        var $pane = vsposBillGetActivePane($form);
        var paymentType = $form.find('.vspos-bill-pay-tabs li.active a').data('payment-type') || 'reload';
        var amount = vsposBillGetAmountFromPane($pane);

        $form.find('.vspos-bill-hidden-type').val(paymentType);
        $form.find('.vspos-bill-hidden-amount').val(amount);

        if (paymentType === 'reload') {
            $form.find('.vspos-bill-hidden-mobile').val($pane.find('.vspos-bill-field-mobile').val() || '');
            $form.find('.vspos-bill-hidden-operator').val($pane.find('.vspos-bill-field-operator').val() || '');
            $form.find('.vspos-bill-hidden-bill-provider').val('');
            $form.find('.vspos-bill-hidden-account').val('');
            $form.find('.vspos-bill-hidden-notes').val('');
        } else if (paymentType === 'utility') {
            $form.find('.vspos-bill-hidden-mobile').val('');
            $form.find('.vspos-bill-hidden-operator').val('');
            $form.find('.vspos-bill-hidden-bill-provider').val($pane.find('.vspos-bill-field-provider').val() || '');
            $form.find('.vspos-bill-hidden-account').val($pane.find('.vspos-bill-field-account').val() || '');
            $form.find('.vspos-bill-hidden-notes').val($pane.find('.vspos-bill-field-notes').val() || '');
        } else if (paymentType === 'other') {
            $form.find('.vspos-bill-hidden-mobile').val('');
            $form.find('.vspos-bill-hidden-operator').val('');
            $form.find('.vspos-bill-hidden-bill-provider').val('');
            $form.find('.vspos-bill-hidden-account').val($pane.find('.vspos-bill-field-other-account').val() || '');
            $form.find('.vspos-bill-hidden-notes').val($pane.find('.vspos-bill-field-other-desc').val() || '');
        }
    }

    function vsposBillValidate($form) {
        var paymentType = $form.find('.vspos-bill-hidden-type').val();
        var amount = parseFloat($form.find('.vspos-bill-hidden-amount').val()) || 0;

        if (amount < 50) {
            return (typeof LANG !== 'undefined' && LANG.bill_amount_min)
                ? LANG.bill_amount_min
                : 'Minimum amount is Rs 50';
        }

        if (paymentType === 'reload') {
            var mobile = ($form.find('.vspos-bill-hidden-mobile').val() || '').trim();
            if (mobile.length < 9) {
                return (typeof LANG !== 'undefined' && LANG.mobile_number_required)
                    ? LANG.mobile_number_required
                    : 'Enter a valid mobile number';
            }
        } else if (paymentType === 'utility') {
            var account = ($form.find('.vspos-bill-hidden-account').val() || '').trim();
            if (account.length < 3) {
                return (typeof LANG !== 'undefined' && LANG.account_number_required)
                    ? LANG.account_number_required
                    : 'Enter account / bill number';
            }
        } else if (paymentType === 'other') {
            var desc = ($form.find('.vspos-bill-hidden-notes').val() || '').trim();
            if (desc.length < 2) {
                return (typeof LANG !== 'undefined' && LANG.payment_description_required)
                    ? LANG.payment_description_required
                    : 'Enter payment description';
            }
        }

        return null;
    }

    function vsposLoadBillHistory($form) {
        var url = window.vspos_mobile_bill_history_url;
        var $shell = $form.find('.vspos-bill-pay-shell');
        var $loading = $shell.find('.vspos-bill-history-loading');
        var $empty = $shell.find('.vspos-bill-history-empty');
        var $list = $shell.find('.vspos-bill-history-list');

        if (!url || !$list.length) {
            return;
        }

        $loading.removeClass('hide');
        $empty.addClass('hide');
        $list.empty();

        $.getJSON(url).done(function (resp) {
            $loading.addClass('hide');
            var rows = (resp && resp.history) ? resp.history : [];
            if (!rows.length) {
                $empty.removeClass('hide');
                return;
            }

            var html = '';
            $.each(rows, function (_, r) {
                html += '<div class="vspos-bill-history-item">';
                html += '<div class="vspos-bill-history-top">';
                html += '<strong>' + (r.reference_no || '') + '</strong>';
                html += '<span class="label label-' + (r.status_class || 'default') + '">' + (r.status || '') + '</span>';
                html += '</div>';
                html += '<div class="vspos-bill-history-meta">' + (r.payment_type || '') + ' · ' + (r.provider || '') + '</div>';
                html += '<div class="vspos-bill-history-meta">' + (r.account || '') + '</div>';
                html += '<div class="vspos-bill-history-bottom">';
                html += '<span class="vspos-bill-history-amount">Rs ' + (r.amount || '0.00') + '</span>';
                html += '<span class="vspos-bill-history-date">' + (r.created_at || '') + '</span>';
                html += '</div></div>';
            });
            $list.html(html);
        }).fail(function () {
            $loading.addClass('hide');
            $empty.text('Could not load history.').removeClass('hide');
        });
    }

    window.vsposInitBillPaymentForms = function (options) {
        options = options || {};
        var formSelector = options.formSelector || '.vspos-bill-payment-form';

        // Bind document handlers once — POS modal + reload page share the same script.
        if (!vsposBillHandlersBound) {
            vsposBillHandlersBound = true;

            $(document).on('click', '.vspos-bill-amount-chip', function (e) {
                e.preventDefault();
                e.stopPropagation();
                vsposBillApplyChip($(this));
            });

            $(document).on('input change', '.vspos-bill-amount-input', function () {
                var $input = $(this);
                var $form = $input.closest('form');
                var $wrap = $input.closest('.vspos-bill-amount-wrap');
                $wrap.find('.vspos-bill-amount-chip').removeClass('is-active');
                $wrap.find('.vspos-bill-amount-chip--custom').addClass('is-active');
                if ($form.length) {
                    $form.find('.vspos-bill-hidden-amount').val($input.val());
                }
            });

            $(document).on('shown.bs.tab', '.vspos-bill-payment-form a[data-toggle="tab"]', function (e) {
                var $form = $(e.target).closest('form');
                if ($(e.target).data('payment-type') === 'history') {
                    vsposLoadBillHistory($form);
                }
            });
        }

        $(document).off('submit.vsposBill', formSelector).on('submit.vsposBill', formSelector, function (e) {
            e.preventDefault();
            var $form = $(this);

            if ($form.find('.tab-pane.active').attr('id') && $form.find('.tab-pane.active').attr('id').indexOf('_tab_history') !== -1) {
                return false;
            }

            vsposBillSyncHiddenFields($form);
            var err = vsposBillValidate($form);
            if (err) {
                toastr.error(err);
                return false;
            }

            var $btn = $form.find('[type=submit]').prop('disabled', true);
            $.post($form.attr('action'), $form.serialize(), function (r) {
                $btn.prop('disabled', false);
                if (r.success) {
                    toastr.success(r.msg);
                    $form.find('.vspos-bill-field-mobile, .vspos-bill-field-account, .vspos-bill-field-notes, .vspos-bill-field-other-desc, .vspos-bill-field-other-account').val('');
                    if (typeof options.onSuccess === 'function') {
                        options.onSuccess(r);
                    }
                    if ($form.attr('id') === 'pos_mobile_reload_form') {
                        $form.closest('.modal').modal('hide');
                    }
                } else {
                    toastr.error(r.msg);
                }
            }).fail(function () {
                $btn.prop('disabled', false);
                toastr.error('Payment request failed');
            });
        });
    };

    $(function () {
        // Always bind chip handlers so POS reload popup works even if forms load late.
        vsposInitBillPaymentForms();
    });
})(jQuery);
