/**
 * QPOS Business Settings saver v5
 * Posts form as JSON so PHP max_input_vars cannot drop later tabs.
 */
(function (window, $) {
    'use strict';

    if (!$) {
        return;
    }

    function killBizValidator() {
        var $f = $('#bussiness_edit_form');
        if (!$f.length || !$.fn.validate) {
            return;
        }
        try {
            if ($f.data('validator')) {
                $f.validate().destroy();
            }
        } catch (e) {}
        $f.removeData('validator');
        $f.off('.validate');
    }

    function assignDeep(obj, name, value) {
        var path = [];
        var base = name.replace(/\[([^\]]*)\]/g, function (_, k) {
            path.push(k);
            return '';
        });
        path.unshift(base);

        var cur = obj;
        for (var i = 0; i < path.length; i++) {
            var key = path[i];
            var last = i === path.length - 1;

            if (key === '') {
                if (!Array.isArray(cur)) {
                    return;
                }
                if (last) {
                    cur.push(value);
                } else {
                    var next = {};
                    cur.push(next);
                    cur = next;
                }
                continue;
            }

            if (last) {
                if (Object.prototype.hasOwnProperty.call(cur, key)) {
                    if (!Array.isArray(cur[key])) {
                        cur[key] = [cur[key]];
                    }
                    cur[key].push(value);
                } else {
                    cur[key] = value;
                }
            } else {
                var nextKey = path[i + 1];
                if (typeof cur[key] === 'undefined') {
                    cur[key] = nextKey === '' ? [] : {};
                }
                cur = cur[key];
            }
        }
    }

    function formToNestedObject(form) {
        var out = {};
        $.each($(form).serializeArray(), function () {
            assignDeep(out, this.name, this.value);
        });
        return out;
    }

    function setButtonBusy(busy) {
        var $btn = $('#btn_force_save_settings');
        if (!$btn.length) {
            return;
        }
        if (busy) {
            $btn.prop('disabled', true).data('old-html', $btn.html()).html('<i class="fa fa-spinner fa-spin"></i> Saving v5…');
        } else {
            $btn.prop('disabled', false).html($btn.data('old-html') || '<i class="fa fa-check"></i> Update Settings ★v5');
        }
    }

    function forceSaveSettings(e) {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }

        var form = document.getElementById('bussiness_edit_form');
        if (!form) {
            alert('QPOS v5: settings form not found');
            return false;
        }

        window.onbeforeunload = null;
        killBizValidator();

        var hasFile = false;
        $(form).find('input[type="file"]').each(function () {
            if (this.files && this.files.length) {
                hasFile = true;
            }
        });

        if (hasFile) {
            // multipart still subject to max_input_vars — warn user
            alert('QPOS v5: file upload uses normal POST. If save fails, clear the logo/file fields and try again, or use /qpos_settings_save.php');
            HTMLFormElement.prototype.submit.call(form);
            return false;
        }

        var payload = formToNestedObject(form);
        var fieldCount = $(form).serializeArray().length;
        payload._settings_field_count = fieldCount;
        payload._qpos_saver = 'v5-json';

        var token = $('meta[name="csrf-token"]').attr('content') || payload._token || '';
        setButtonBusy(true);

        fetch(form.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        })
            .then(function (res) {
                return res.text().then(function (text) {
                    var data = null;
                    try {
                        data = JSON.parse(text);
                    } catch (err) {
                        data = null;
                    }
                    return { ok: res.ok, status: res.status, data: data, text: text };
                });
            })
            .then(function (result) {
                if (result.data && (result.data.success == 1 || result.data.success === true)) {
                    alert('QPOS v5 Saved: ' + (result.data.msg || 'Settings updated') + '\nFields sent: ' + fieldCount);
                    window.location.href = (form.getAttribute('data-success-url') || window.location.pathname) + '?saved=1&v=5';
                    return;
                }

                var msg = 'Save failed (HTTP ' + result.status + ')';
                if (result.data && result.data.msg) {
                    msg = result.data.msg;
                } else if (result.status === 419) {
                    msg = 'Session expired (419). Refresh and try again.';
                } else if (result.text) {
                    msg = result.text.replace(/<[^>]+>/g, ' ').substring(0, 400);
                }
                alert('QPOS v5 Error: ' + msg);
                setButtonBusy(false);
            })
            .catch(function (err) {
                alert('QPOS v5 Network error: ' + (err && err.message ? err.message : err));
                setButtonBusy(false);
            });

        return false;
    }

    window.__qposForceSaveSettings = forceSaveSettings;
    window.__qposSaveSettings = forceSaveSettings;

    $(function () {
        killBizValidator();
        var $form = $('#bussiness_edit_form');
        if ($form.length) {
            $form.attr('novalidate', 'novalidate');
            $form.off('submit.qposv5').on('submit.qposv5', forceSaveSettings);
        }
        $(document).off('click.qposv5', '#btn_force_save_settings').on('click.qposv5', '#btn_force_save_settings', forceSaveSettings);

        // Visible proof new JS loaded
        if ($('#btn_force_save_settings').length && $('#qpos-settings-v5-badge').length === 0) {
            $('#btn_force_save_settings').after(
                '<div id="qpos-settings-v5-badge" style="margin-top:8px;color:#0a7;font-weight:700;">Saver v5 loaded — JSON mode</div>'
            );
        }
    });
})(window, window.jQuery);
