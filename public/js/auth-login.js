(function ($) {
    'use strict';

    var PIN_MIN = 4;
    var PIN_MAX = 6;
    var pinBuffer = '';

    function updatePinDisplay() {
        var $dots = $('#pin-display .vspos-pin-dot');
        $dots.each(function (i) {
            $(this).toggleClass('filled', i < pinBuffer.length);
        });
        $('#pin_value').val(pinBuffer);
        $('#pin-submit-btn').prop('disabled', pinBuffer.length < PIN_MIN);
    }

    function resetPinBuffer() {
        pinBuffer = '';
        updatePinDisplay();
    }

    function switchTab(tab) {
        $('.vspos-login-tab').removeClass('active').attr('aria-selected', 'false');
        $('.vspos-login-tab[data-tab="' + tab + '"]').addClass('active').attr('aria-selected', 'true');
        $('.vspos-login-panel').removeClass('active');
        $('#vspos-panel-' + tab).addClass('active');
        if (tab === 'pin') {
            $('#pin_username').trigger('focus');
        } else {
            $('#username').trigger('focus');
        }
    }

    $(document).ready(function () {
        // Tab switching
        $('.vspos-login-tab').on('click', function () {
            switchTab($(this).data('tab'));
        });

        // Open PIN tab if PIN login failed
        if ($('#vspos-panel-pin .help-block').length) {
            switchTab('pin');
        }

        // Password visibility toggle
        $('#show_hide_icon').on('click', function () {
            var $input = $('#password');
            if ($input.attr('type') === 'password') {
                $input.attr('type', 'text');
            } else {
                $input.attr('type', 'password');
            }
        });

        // PIN pad
        $('#pin-pad').on('click', '.vspos-pin-key', function () {
            var digit = $(this).data('digit');
            var action = $(this).data('action');

            if (digit !== undefined) {
                if (pinBuffer.length < PIN_MAX) {
                    pinBuffer += String(digit);
                    updatePinDisplay();
                    if (pinBuffer.length >= PIN_MIN) {
                        $('#pin-submit-btn').prop('disabled', false);
                    }
                    if (pinBuffer.length === PIN_MAX) {
                        $('#pin-login-form').trigger('submit');
                    }
                }
            } else if (action === 'back') {
                pinBuffer = pinBuffer.slice(0, -1);
                updatePinDisplay();
            } else if (action === 'clear') {
                resetPinBuffer();
            }
        });

        // Sync username between forms
        $('#username, #pin_username').on('input', function () {
            var val = $(this).val();
            $('#username, #pin_username, #setup_username').val(val);
        });

        // Setup PIN modal
        $('#open-setup-pin-modal').on('click', function () {
            var username = $('#username').val() || $('#pin_username').val();
            if (username) {
                $('#setup_username').val(username);
            }
            $('#setup-pin-modal').addClass('open').attr('aria-hidden', 'false');
        });

        $('#close-setup-pin-modal, #setup-pin-modal').on('click', function (e) {
            if (e.target === this) {
                $('#setup-pin-modal').removeClass('open').attr('aria-hidden', 'true');
            }
        });

        $('.vspos-modal').on('click', function (e) {
            e.stopPropagation();
        });

        // Demo shop boxes — preview branding, then fill credentials and submit
        function applyShopBrand($btn) {
            var shopName = $btn.data('shop-name') || '';
            var tagline = $btn.data('shop-tagline') || '';
            var theme = $btn.data('shop-theme') || 'qpos';
            var accent = $btn.data('shop-accent') || '#f5c518';

            document.documentElement.style.setProperty('--vspos-auth-shop-accent', accent);
            $('body').attr('data-auth-theme', theme);
            $('#auth-layout, #vspos-login-card').attr('data-theme', theme);

            if (shopName) {
                $('#auth-brand-shop-label, #vspos-login-shop-name').text(shopName);
                $('#vspos-login-subtitle').html('Sign in to <strong id="vspos-login-shop-inline">' + $('<div>').text(shopName).html() + '</strong>');
                if (theme === 'nutmeg') {
                    $('#auth-brand-headline').html('Nutmeg trading,<br><span>made simple.</span>');
                } else {
                    $('#auth-brand-headline').html('Run your business<br><span>smarter &amp; faster.</span>');
                }
                if (tagline) {
                    $('#auth-brand-copy').text(shopName + ' — ' + tagline + '. Powered by QPOS.');
                }
            }

            $('.vspos-demo-shop').removeClass('is-active');
            $btn.addClass('is-active');
        }

        $(document).on('mouseenter focus', '.demo-login', function () {
            applyShopBrand($(this));
        });

        $(document).on('click', '.demo-login', function (e) {
            e.preventDefault();
            var $btn = $(this);
            var admin = $btn.data('admin');
            var password = $btn.data('password') || window.VSPOS_LOGIN_DEMO_PASSWORD || '123456';
            if (!admin) {
                return;
            }
            applyShopBrand($btn);
            $btn.addClass('is-loading').prop('disabled', true);
            $('#username').val(admin);
            $('#pin_username').val(admin);
            $('#password').val(password);
            switchTab('password');
            $('#login-form').trigger('submit');
        });

        // Language switcher
        $('.change_lang').on('click', function () {
            window.location = '/login?lang=' + $(this).attr('value');
        });

        resetPinBuffer();

        // Re-open setup modal after validation errors
        if ($('#setup-pin-form .help-block').length) {
            $('#setup-pin-modal').addClass('open').attr('aria-hidden', 'false');
        }
    });
})(jQuery);
