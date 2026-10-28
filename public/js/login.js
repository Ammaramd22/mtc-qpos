var recaptchaRendered = false;

$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();

    // registration form steps start
    if ($('#business_register_form').length) {
        var form = $('#business_register_form').show();
        form.steps({
            headerTag: 'h3',
            bodyTag: 'fieldset',
            transitionEffect: 'fade',
            labels: {
                finish: LANG.register,
                next: LANG.next,
                previous: LANG.previous,
            },
            onStepChanging: function(event, currentIndex, newIndex) {
                // Allways allow previous action even if the current form is not valid!
                if (currentIndex > newIndex) {
                    return true;
                }
                // Needed in some cases if the user went back (clean up)
                if (currentIndex < newIndex) {
                    // To remove error styles
                    form.find('.body:eq(' + newIndex + ') label.error').remove();
                    form.find('.body:eq(' + newIndex + ') .error').removeClass('error');
                }
                form.validate().settings.ignore = ':disabled,:hidden';
                return form.valid();
            },
            onStepChanged: function(event, currentIndex, priorIndex) {
                // Render reCAPTCHA on last step only when the widget container exists
                if (currentIndex === 2 && !recaptchaRendered) {
                    var captchaEl = document.getElementById('recaptcha-container');
                    if (
                        captchaEl &&
                        typeof grecaptcha !== 'undefined' &&
                        typeof grecaptcha.render === 'function' &&
                        window.RECAPTCHA_SITE_KEY
                    ) {
                        try {
                            grecaptcha.render(captchaEl, {
                                sitekey: window.RECAPTCHA_SITE_KEY,
                            });
                            recaptchaRendered = true;
                        } catch (e) {
                            // Ignore missing/disabled reCAPTCHA so registration can continue
                            console.warn('reCAPTCHA render skipped:', e);
                        }
                    }
                }
            },
            onFinishing: function(event, currentIndex) {
                // Keep :hidden ignored so select2 originals don't block finish;
                // still validate visible owner fields + filled previous-step values.
                form.validate().settings.ignore = ':disabled,:hidden';
                return form.valid();
            },
            onFinished: function(event, currentIndex) {
                // Disable finish button to prevent double submit
                form.find('a[href="#finish"]').addClass('disabled');
                form.submit();
            },
        });
        form.find('a[href="#previous"]').addClass('tw-dw-btn');
        form.find('a[href="#next"]').addClass('tw-dw-btn tw-dw-btn-primary');
        form.find('a[href="#finish"]').addClass('tw-dw-btn tw-dw-btn-primary');

        // Ensure first step fields are visible after init / validation redirect
        setTimeout(function() {
            var $bodies = form.find('.content > .body');
            if ($bodies.length && !$bodies.filter('.current').length) {
                $bodies.first().addClass('current').show();
            }
        }, 50);
    }
    // registration form steps end

    //Date picker
    $('.start-date-picker').datepicker({
        autoclose: true,
        endDate: 'today',
    });

    function registerCsrfToken() {
        return $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val() || '';
    }

    $('form#business_register_form').validate({
        errorPlacement: function(error, element) {
            if (element.parent('.input-group').length) {
                error.insertAfter(element.parent());
            } else if (element.hasClass('input-icheck') && element.parent().hasClass('icheckbox_square-blue')) {
                error.insertAfter(element.parent().parent().parent());
            } else {
                error.insertAfter(element);
            }
        },
        rules: {
            name: 'required',
            email: {
                email: true,
                remote: {
                    url: '/business/register/check-email',
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': registerCsrfToken(),
                    },
                    data: {
                        email: function() {
                            return $('#email').val();
                        },
                        is_disposable_email: true,
                        _token: registerCsrfToken,
                    },
                    dataFilter: function(response) {
                        try {
                            // jQuery Validate expects 'true' or a quoted string as the error message
                            if (response === 'true' || response === true) {
                                return '"true"';
                            }
                            // Non-JSON CSRF/HTML error pages should not block registration forever
                            if (typeof response === 'string' && response.indexOf('<') !== -1) {
                                return '"true"';
                            }
                            var msg = (typeof response === 'string' && response.trim().length)
                                ? response
                                : ((typeof LANG !== 'undefined' && LANG.email_taken) ? LANG.email_taken : 'This email has already been taken.');
                            // Escape quotes in message
                            return '"' + msg.replace(/"/g, '\\"') + '"';
                        } catch (e) {
                            return '"' + ((typeof LANG !== 'undefined' && LANG.email_taken) ? LANG.email_taken : 'This email has already been taken.') + '"';
                        }
                    }
                },
            },
            password: {
                required: true,
                minlength: 5,
            },
            confirm_password: {
                equalTo: '#password',
            },
            username: {
                required: true,
                minlength: 4,
                remote: {
                    url: '/business/register/check-username',
                    type: 'post',
                    headers: {
                        'X-CSRF-TOKEN': registerCsrfToken(),
                    },
                    data: {
                        username: function() {
                            return $('#username').val();
                        },
                        _token: registerCsrfToken,
                    },
                    dataFilter: function(response) {
                        if (response === 'true' || response === true) {
                            return 'true';
                        }
                        // Ignore CSRF/HTML error bodies so registration is not blocked
                        if (typeof response === 'string' && response.indexOf('<') !== -1) {
                            return 'true';
                        }
                        return 'false';
                    },
                },
            },
            website: {
                url: {
                    depends: function() {
                        return $.trim($('#website').val() || '') !== '';
                    },
                },
            },
        },
        messages: {
            name: LANG.specify_business_name,
            password: {
                minlength: LANG.password_min_length,
            },
            confirm_password: {
                equalTo: LANG.password_mismatch,
            },
            username: {
                remote: LANG.invalid_username,
            },
            // Do not override email.remote; we use server response via dataFilter
        },
    });

    $('#business_logo').fileinput({
        showUpload: false,
        showPreview: false,
        browseLabel: LANG.file_browse_label,
        removeLabel: LANG.remove,
    });
});
