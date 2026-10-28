/**
 * QPOS Business Settings saver v6 — vanilla JS (no jQuery required).
 * Fixes: $ is not defined + Failed to fetch (http form action on https page).
 */
(function (window, document) {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function csrfToken(form) {
        var meta = qs('meta[name="csrf-token"]');
        if (meta && meta.getAttribute('content')) {
            return meta.getAttribute('content');
        }
        var input = form ? form.querySelector('input[name="_token"]') : null;
        return input ? input.value : '';
    }

    function httpsSafeUrl(url) {
        if (!url) {
            return url;
        }
        try {
            if (window.location.protocol === 'https:' && url.indexOf('http://') === 0) {
                return 'https://' + url.substring('http://'.length);
            }
        } catch (e) {}
        return url;
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
                    var nextObj = {};
                    cur.push(nextObj);
                    cur = nextObj;
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
        var fd = new FormData(form);
        fd.forEach(function (value, name) {
            // Skip files in JSON payload
            if (typeof File !== 'undefined' && value instanceof File) {
                return;
            }
            assignDeep(out, name, value);
        });
        return out;
    }

    function fieldCount(form) {
        try {
            return Array.prototype.slice.call(new FormData(form).keys()).length;
        } catch (e) {
            return 0;
        }
    }

    function setButtonBusy(busy) {
        var btn = qs('#btn_force_save_settings');
        if (!btn) {
            return;
        }
        if (busy) {
            btn.setAttribute('data-old-html', btn.innerHTML);
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving v6…';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.getAttribute('data-old-html') || '<i class="fa fa-check"></i> Update Settings ★v6';
        }
    }

    function hasSelectedFiles(form) {
        return qsa('input[type="file"]', form).some(function (el) {
            return el.files && el.files.length > 0;
        });
    }

    function forceSaveSettings(e) {
        if (e && e.preventDefault) {
            e.preventDefault();
            e.stopPropagation();
        }

        var form = qs('#bussiness_edit_form');
        if (!form) {
            alert('QPOS v6: settings form not found');
            return false;
        }

        window.onbeforeunload = null;

        var action = httpsSafeUrl(form.getAttribute('action') || form.action || '/business/update');
        var token = csrfToken(form);
        var count = fieldCount(form);

        if (hasSelectedFiles(form)) {
            // Keep multipart for logo — but force https action
            form.setAttribute('action', action);
            form.removeAttribute('novalidate');
            HTMLFormElement.prototype.submit.call(form);
            return false;
        }

        var payload = formToNestedObject(form);
        payload._settings_field_count = count;
        payload._qpos_saver = 'v6-json';
        if (token && !payload._token) {
            payload._token = token;
        }

        setButtonBusy(true);

        fetch(action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token || ''
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
                    alert('QPOS v6 Saved: ' + (result.data.msg || 'Settings updated') + '\nFields: ' + count);
                    var successUrl = form.getAttribute('data-success-url') || '/business/settings';
                    window.location.href = httpsSafeUrl(successUrl) + (successUrl.indexOf('?') >= 0 ? '&' : '?') + 'saved=1&v=6';
                    return;
                }

                var msg = 'Save failed (HTTP ' + result.status + ')';
                if (result.data && result.data.msg) {
                    msg = result.data.msg;
                } else if (result.status === 419) {
                    msg = 'Session expired (419). Refresh and try again.';
                } else if (result.text) {
                    msg = result.text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').substring(0, 400);
                }
                alert('QPOS v6 Error: ' + msg);
                setButtonBusy(false);
            })
            .catch(function (err) {
                alert(
                    'QPOS v6 Network error: ' +
                        (err && err.message ? err.message : err) +
                        '\n\nUsually APP_URL is http while the site is https.\nFix .env APP_URL to https://YOUR-DOMAIN\nOr use /qpos_settings_save.php?key=qposclear2026'
                );
                setButtonBusy(false);
            });

        return false;
    }

    window.__qposForceSaveSettings = forceSaveSettings;
    window.__qposSaveSettings = forceSaveSettings;

    function boot() {
        var form = qs('#bussiness_edit_form');
        if (form) {
            form.setAttribute('novalidate', 'novalidate');
            // Upgrade http action → https when needed
            form.setAttribute('action', httpsSafeUrl(form.getAttribute('action') || form.action));
            form.addEventListener('submit', forceSaveSettings, true);
        }

        var btn = qs('#btn_force_save_settings');
        if (btn) {
            btn.addEventListener('click', forceSaveSettings, true);
            if (!qs('#qpos-settings-v6-badge')) {
                var badge = document.createElement('div');
                badge.id = 'qpos-settings-v6-badge';
                badge.style.cssText = 'margin-top:8px;color:#0a7;font-weight:700;';
                badge.textContent = 'Saver v6 loaded — no jQuery needed';
                btn.parentNode.appendChild(badge);
            }
            if (btn.innerHTML.indexOf('★v6') === -1 && btn.innerHTML.indexOf('★v5') !== -1) {
                btn.innerHTML = btn.innerHTML.replace('★v5', '★v6');
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);
