/**
 * QPOS Business Settings saver — vanilla JS (no jQuery required).
 */
(function (window, document) {
    'use strict';

    function qs(sel, root) {
        return (root || document).querySelector(sel);
    }

    function qsa(sel, root) {
        return Array.prototype.slice.call((root || document).querySelectorAll(sel));
    }

    function notify(type, message) {
        if (typeof toastr !== 'undefined') {
            if (type === 'success') {
                toastr.success(message);
            } else if (type === 'info') {
                toastr.info(message);
            } else {
                toastr.error(message);
            }
            return;
        }
        if (typeof swal === 'function') {
            swal({
                text: message,
                icon: type === 'success' ? 'success' : type === 'info' ? 'info' : 'error',
            });
            return;
        }
        // last resort
        window.alert(message);
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

    function assignDeep(obj, name, value, asArray) {
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
                if (asArray) {
                    if (!Array.isArray(cur[key])) {
                        cur[key] = Object.prototype.hasOwnProperty.call(cur, key) ? [cur[key]] : [];
                    }
                    cur[key].push(value);
                } else {
                    cur[key] = value;
                }
            } else {
                var nextKey = path[i + 1];
                if (typeof cur[key] === 'undefined' || typeof cur[key] !== 'object' || cur[key] === null) {
                    cur[key] = nextKey === '' ? [] : {};
                }
                cur = cur[key];
            }
        }
    }

    function isCheckable(el) {
        return el.type === 'checkbox' || el.type === 'radio';
    }

    function shouldSkip(el) {
        if (!el || !el.name) {
            return true;
        }
        if (el.disabled) {
            return true;
        }
        if (el.type === 'file') {
            return true;
        }
        if (isCheckable(el) && !el.checked) {
            return true;
        }
        return false;
    }

    function collectNamedControls(root) {
        return qsa('input[name], select[name], textarea[name]', root);
    }

    function buildPayload(form) {
        var out = {};
        var count = 0;
        var roots = [];
        var page = qs('.upos-settings-page') || qs('.upos-settings-box') || form;
        roots.push(page);
        if (form && form !== page) {
            roots.push(form);
        }

        var seen = {};
        roots.forEach(function (root) {
            collectNamedControls(root).forEach(function (el) {
                if (shouldSkip(el)) {
                    return;
                }
                var name = el.name;
                var value = el.value;
                var asArray = /\[\]$/.test(name);
                // Deduplicate exact name+value even for arrays (same checkbox collected from page + form)
                var sig = name + "\0" + value + "\0" + (el.type || '');
                if (seen[sig]) {
                    return;
                }
                seen[sig] = true;
                assignDeep(out, name, value, asArray);
                count++;
            });
        });

        return { payload: out, count: count };
    }

    function setButtonBusy(busy) {
        var btn = qs('#btn_force_save_settings');
        if (!btn) {
            return;
        }
        if (busy) {
            btn.setAttribute('data-old-html', btn.innerHTML);
            btn.disabled = true;
            btn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Saving…';
        } else {
            btn.disabled = false;
            btn.innerHTML = btn.getAttribute('data-old-html') || '<i class="fa fa-check"></i> Update Settings';
        }
    }

    function hasSelectedFiles(form) {
        var root = qs('.upos-settings-page') || form;
        return qsa('input[type="file"]', root).some(function (el) {
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
            notify('error', 'Settings form not found');
            return false;
        }

        window.onbeforeunload = null;

        var action = httpsSafeUrl(form.getAttribute('action') || form.action || '/business/update');
        var token = csrfToken(form);

        if (hasSelectedFiles(form)) {
            form.setAttribute('action', action);
            HTMLFormElement.prototype.submit.call(form);
            return false;
        }

        var built = buildPayload(form);
        var count = built.count;
        var payload = built.payload;

        if (count < 15) {
            notify('error', 'Could not collect settings fields. Please hard-refresh and try again.');
            setButtonBusy(false);
            return false;
        }

        payload._settings_field_count = count;
        payload._qpos_saver = 'v7-json';
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
                    // Reload — layout #status_span shows the usual top-right toastr
                    var successUrl = form.getAttribute('data-success-url') || '/business/settings';
                    window.location.href = httpsSafeUrl(successUrl);
                    return;
                }

                var msg = 'Something went wrong';
                if (result.data && result.data.msg) {
                    msg = result.data.msg;
                } else if (result.status === 419) {
                    msg = 'Session expired. Refresh the page and try again.';
                }
                notify('error', msg);
                setButtonBusy(false);
            })
            .catch(function () {
                notify('error', 'Network error while saving settings');
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
            form.setAttribute('action', httpsSafeUrl(form.getAttribute('action') || form.action));
            form.addEventListener('submit', forceSaveSettings, true);
        }

        var btn = qs('#btn_force_save_settings');
        if (btn) {
            btn.addEventListener('click', forceSaveSettings, true);
            // Remove leftover debug UI from earlier fixes
            ['#qpos-settings-v7-badge', '#qpos-settings-v6-badge', '#qpos-settings-v5-badge'].forEach(function (id) {
                var el = qs(id);
                if (el && el.parentNode) {
                    el.parentNode.removeChild(el);
                }
            });
            btn.innerHTML = '<i class="fa fa-check"></i> ' + (btn.getAttribute('data-label') || 'Update Settings');
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})(window, document);
