/**
 * QPOS Product List — individual + bulk delete
 * Uses POST + _method=DELETE (cPanel-safe; many hosts block HTTP DELETE).
 */
(function () {
    'use strict';

    function csrfToken() {
        var m = document.querySelector('meta[name="csrf-token"]');
        return m ? m.getAttribute('content') : '';
    }

    function toast(ok, msg) {
        if (typeof toastr !== 'undefined') {
            ok ? toastr.success(msg) : toastr.error(msg);
            return;
        }
        alert(msg);
    }

    function reloadTable() {
        if (window.product_table && product_table.ajax) {
            product_table.ajax.reload(null, false);
            return;
        }
        window.location.reload();
    }

    function confirmAction(title, thenFn) {
        title = title || (window.LANG && LANG.sure) || 'Are you sure?';
        if (typeof swal === 'function') {
            swal({ title: title, icon: 'warning', buttons: true, dangerMode: true })
                .then(function (ok) { if (ok) thenFn(); });
            return;
        }
        if (window.confirm(title)) {
            thenFn();
        }
    }

    function deleteUrlFrom(el) {
        var dataHref = el.getAttribute('data-href') || '';
        if (dataHref && dataHref !== '#') {
            return dataHref;
        }
        var href = el.getAttribute('href') || '';
        var m = String(href).match(/\/products\/(\d+)/);
        if (m) {
            var base = (typeof window.base_path === 'string') ? window.base_path : '';
            return base + '/products/' + m[1];
        }
        return '';
    }

    /**
     * Individual delete via method spoofing (POST).
     */
    function doIndividualDelete(url) {
        var body = new URLSearchParams();
        body.set('_method', 'DELETE');
        body.set('_token', csrfToken());

        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
            .then(function (r) {
                return r.json().then(function (json) {
                    return { status: r.status, json: json };
                }).catch(function () {
                    return { status: r.status, json: { success: false, msg: 'Delete failed (invalid server response).' } };
                });
            })
            .then(function (res) {
                var ok = res.json && (res.json.success === true || res.json.success == 1);
                if (ok) {
                    toast(true, res.json.msg || 'Product deleted successfully');
                    reloadTable();
                } else {
                    toast(false, (res.json && res.json.msg) || ('Delete failed (HTTP ' + res.status + ')'));
                }
            })
            .catch(function () {
                toast(false, 'Delete failed. Check your connection and try again.');
            });
    }

    function getSelectedProductIds() {
        if (typeof getSelectedRows === 'function') {
            return getSelectedRows().map(function (v) { return String(v); }).filter(Boolean);
        }
        var ids = [];
        document.querySelectorAll('.row-select:checked').forEach(function (el) {
            if (el.value) ids.push(String(el.value));
        });
        return ids;
    }

    function doBulkDelete(ids) {
        var form = document.getElementById('mass_delete_form');
        var action = form ? form.getAttribute('action') : ((window.base_path || '') + '/products/mass-delete');
        var body = new URLSearchParams();
        body.set('_token', csrfToken());
        body.set('selected_rows', ids.join(','));

        fetch(action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken(),
                'Accept': 'application/json',
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: body.toString()
        })
            .then(function (r) {
                return r.json().then(function (json) {
                    return { status: r.status, json: json };
                }).catch(function () {
                    return { status: r.status, json: { success: false, msg: 'Bulk delete failed (invalid server response).' } };
                });
            })
            .then(function (res) {
                var ok = res.json && (res.json.success === true || res.json.success == 1);
                if (ok) {
                    toast(true, res.json.msg || 'Deleted');
                } else {
                    toast(false, (res.json && res.json.msg) || 'Bulk delete failed');
                }
                // Clear checkboxes + reload either way so UI matches DB
                document.querySelectorAll('.row-select:checked').forEach(function (el) {
                    el.checked = false;
                });
                var selectAll = document.getElementById('select-all-row');
                if (selectAll) selectAll.checked = false;
                var hidden = document.getElementById('selected_rows');
                if (hidden) hidden.value = '';
                reloadTable();
            })
            .catch(function () {
                toast(false, 'Bulk delete failed. Check your connection and try again.');
            });
    }

    // Capture-phase individual delete (beats broken/old handlers)
    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el) return;
        if (el.closest) {
            el = el.closest('a.delete-product');
        } else {
            while (el && !(el.tagName === 'A' && el.classList && el.classList.contains('delete-product'))) {
                el = el.parentNode;
            }
        }
        if (!el) return;

        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();

        var url = deleteUrlFrom(el);
        if (!url) {
            toast(false, 'Missing product delete URL.');
            return;
        }
        confirmAction(
            (window.LANG && LANG.sure) || 'Are you sure you want to delete this product?',
            function () { doIndividualDelete(url); }
        );
    }, true);

    // Bulk delete button (capture so form submit cannot race)
    document.addEventListener('click', function (e) {
        var el = e.target;
        if (!el) return;
        if (el.closest) {
            el = el.closest('#delete-selected');
        } else if (!(el.id === 'delete-selected')) {
            return;
        }
        if (!el || el.id !== 'delete-selected') return;

        e.preventDefault();
        e.stopPropagation();
        if (e.stopImmediatePropagation) e.stopImmediatePropagation();

        var ids = getSelectedProductIds();
        if (!ids.length) {
            if (typeof swal === 'function') {
                swal((window.LANG && LANG.no_row_selected) || 'Please select at least one product.');
            } else {
                toast(false, 'Please select at least one product.');
            }
            return;
        }

        var title = 'Delete ' + ids.length + ' selected product' + (ids.length > 1 ? 's' : '') + '?';
        confirmAction(title, function () { doBulkDelete(ids); });
    }, true);
})();
