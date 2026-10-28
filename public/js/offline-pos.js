/**
 * Offline Billing for POS
 * Queues final sale POSTs when offline; Manual Sync reviews & uploads with idempotency.
 */
(function($) {
    'use strict';

    var STORAGE_KEY = 'vsoftpos_offline_transactions';
    var originalAjax = $.ajax;

    window.OfflinePOS = {
        enabled: false,
        /** When true, reconnect does NOT auto-sync — user opens Sync modal */
        manualSyncOnly: false,
        isOnline: navigator.onLine,
        syncInProgress: false,
        posUrl: '',
        csrfToken: '',

        init: function(config) {
            this.enabled = !!(config && config.enabled);
            this.manualSyncOnly = !!(config && config.manualSyncOnly);
            this.posUrl = (config && config.posUrl) || ($('form#add_pos_sell_form').attr('action') || '/pos');
            this.csrfToken = $('meta[name="csrf-token"]').attr('content') ||
                $('input[name="_token"]').val() || '';
            if (!this.enabled) {
                return;
            }

            this.installAjaxHook();
            this.bindEvents();
            this.updateUI();

            if (!this.manualSyncOnly) {
                this.checkPendingSync();
            }
        },

        installAjaxHook: function() {
            var self = this;
            $.ajax = function(options) {
                var opts = options || {};
                var url = opts.url || '';
                var method = (opts.type || opts.method || 'GET').toUpperCase();
                var isPosPost = method === 'POST' &&
                    (url === self.posUrl ||
                        (self.posUrl && url.indexOf(self.posUrl) !== -1) ||
                        /\/pos\/?(\?|$)/.test(url));

                if (isPosPost && !self.isOnline && self.enabled) {
                    return self.queueOfflineSale(opts);
                }
                return originalAjax.apply($, arguments);
            };
        },

        queueOfflineSale: function(opts) {
            var self = this;
            var formData = opts.data || '';
            if (typeof formData === 'object' && formData !== null && !(formData instanceof String)) {
                formData = $.param(formData);
            }
            formData = String(formData || '');

            var offlineUuid = 'offline_' + Date.now() + '_' + Math.random().toString(36).slice(2, 11);
            formData = self.ensureOfflineUuidInData(formData, offlineUuid);
            formData = self.ensureCsrfInData(formData);

            var summary = self.summarizeFormData(formData);
            var transactions = self.getStoredTransactions();
            transactions.push({
                id: offlineUuid,
                offline_uuid: offlineUuid,
                data: formData,
                created_at: new Date().toISOString(),
                status: 'pending',
                last_error: null,
                attempts: 0,
                summary: summary
            });
            self.saveTransactions(transactions);
            self.updateUI();
            self.showToast(self.t('offline_sale_queued', 'Sale saved offline. Use Sync when online.'), 'success');

            var deferred = $.Deferred();
            var fakeJqXHR = { abort: function() {}, readyState: 4, responseText: '', status: 200 };
            setTimeout(function() {
                deferred.resolve(
                    {
                        success: 1,
                        msg: self.t('offline_sale_queued_short', 'Saved offline'),
                        offline: true,
                        offline_uuid: offlineUuid
                    },
                    'success',
                    fakeJqXHR
                );
            }, 40);
            return $.extend(deferred.promise(), fakeJqXHR);
        },

        summarizeFormData: function(formData) {
            var params = {};
            try {
                formData.split('&').forEach(function(pair) {
                    var p = pair.split('=');
                    var k = decodeURIComponent((p[0] || '').replace(/\+/g, ' '));
                    var v = decodeURIComponent((p.slice(1).join('=') || '').replace(/\+/g, ' '));
                    params[k] = v;
                });
            } catch (e) {}

            var productCount = 0;
            Object.keys(params).forEach(function(k) {
                if (/^products\[\d+\]\[product_id\]$/.test(k)) {
                    productCount++;
                }
            });

            return {
                final_total: params.final_total || '',
                contact_id: params.contact_id || '',
                product_count: productCount
            };
        },

        bindEvents: function() {
            var self = this;

            $(window).on('online', function() {
                self.isOnline = true;
                self.csrfToken = $('meta[name="csrf-token"]').attr('content') || self.csrfToken;
                self.updateUI();
                self.showToast(self.t('offline_connection_restored', 'Connection restored.'), 'success');
                if (!self.manualSyncOnly) {
                    self.syncPendingTransactions();
                } else if (self.getPendingCount() > 0) {
                    self.showToast(self.t('offline_manual_sync_hint', 'Pending offline sales — open Sync to upload.'), 'warning');
                }
            });

            $(window).on('offline', function() {
                self.isOnline = false;
                self.updateUI();
                self.showToast(self.t('offline_now_offline', 'You are offline. Completed sales will be queued.'), 'warning');
            });

            $(document).on('click', '#offline-pending-badge, #offline-status-indicator, #vspos_offline_sync_open', function(e) {
                e.preventDefault();
                self.openSyncModal();
            });

            $(document).on('click', '#vspos_offline_sync_now', function() {
                self.syncPendingTransactions();
            });

            $(document).on('click', '#vspos_offline_retry_failed', function() {
                self.retryFailedOnly();
            });

            $(document).on('click', '#vspos_offline_clear_all', function() {
                self.confirmClearQueue();
            });

            $(document).on('click', '.vspos-offline-retry-one', function() {
                self.syncOne($(this).data('id'));
            });

            $(document).on('click', '.vspos-offline-remove-one', function() {
                self.removeOne($(this).data('id'));
                self.refreshSyncModal();
                self.updateUI();
            });
        },

        t: function(key, fallback) {
            return (typeof LANG !== 'undefined' && LANG[key]) ? LANG[key] : fallback;
        },

        getStoredTransactions: function() {
            try {
                var stored = localStorage.getItem(STORAGE_KEY);
                var list = stored ? JSON.parse(stored) : [];
                return Array.isArray(list) ? list : [];
            } catch (e) {
                return [];
            }
        },

        saveTransactions: function(list) {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(list || []));
        },

        getPendingCount: function() {
            return this.getStoredTransactions().filter(function(t) {
                return t.status !== 'synced';
            }).length;
        },

        getFailedCount: function() {
            return this.getStoredTransactions().filter(function(t) {
                return t.status === 'failed';
            }).length;
        },

        checkPendingSync: function() {
            if (this.isOnline && this.getPendingCount() > 0 && !this.syncInProgress) {
                this.syncPendingTransactions();
            }
        },

        ensureCsrfInData: function(formData) {
            var token = $('meta[name="csrf-token"]').attr('content') || this.csrfToken;
            if (!token) {
                return formData;
            }
            this.csrfToken = token;
            formData = String(formData || '');
            if (formData.indexOf('_token=') === -1) {
                return formData + (formData ? '&' : '') + '_token=' + encodeURIComponent(token);
            }
            return formData.replace(/(_token=)[^&]*/, '$1' + encodeURIComponent(token));
        },

        ensureOfflineUuidInData: function(formData, uuid) {
            if (!uuid) {
                return formData;
            }
            formData = String(formData || '');
            if (formData.indexOf('offline_uuid=') === -1) {
                return formData + (formData ? '&' : '') + 'offline_uuid=' + encodeURIComponent(uuid);
            }
            return formData;
        },

        syncPendingTransactions: function() {
            var self = this;

            if (!this.isOnline) {
                this.showToast(this.t('offline_still_offline', 'Still offline. Connect first.'), 'warning');
                return;
            }
            if (this.syncInProgress) {
                return;
            }

            var queue = this.getStoredTransactions().filter(function(t) {
                return t.status !== 'synced';
            });

            if (!queue.length) {
                this.showToast(this.t('offline_nothing_to_sync', 'No pending offline sales.'), 'info');
                this.refreshSyncModal();
                return;
            }

            this.syncInProgress = true;
            this.setSyncBusy(true);
            var idx = 0;
            var ok = 0;
            var fail = 0;

            function finish() {
                self.syncInProgress = false;
                self.setSyncBusy(false);
                self.updateUI();
                self.refreshSyncModal();
                if (fail === 0) {
                    self.showToast(self.t('offline_sync_done', ':count offline sale(s) synced.').replace(':count', ok), 'success');
                } else {
                    self.showToast(
                        self.t('offline_sync_partial', ':ok synced, :fail failed. Retry failed from Sync.')
                            .replace(':ok', ok).replace(':fail', fail),
                        'error'
                    );
                }
            }

            function processNext() {
                if (idx >= queue.length) {
                    finish();
                    return;
                }
                var txn = queue[idx++];
                self.postOne(txn, function(success) {
                    if (success) { ok++; } else { fail++; }
                    processNext();
                });
            }

            processNext();
        },

        postOne: function(txn, done) {
            var self = this;
            var uuid = txn.offline_uuid || txn.id;
            var data = this.ensureOfflineUuidInData(txn.data, uuid);
            data = this.ensureCsrfInData(data);

            originalAjax({
                method: 'POST',
                url: self.posUrl,
                data: data,
                dataType: 'json',
                headers: self.csrfToken ? { 'X-CSRF-TOKEN': self.csrfToken } : {}
            }).done(function(result) {
                var list = self.getStoredTransactions();
                var row = null;
                for (var i = 0; i < list.length; i++) {
                    if (list[i].id === txn.id) { row = list[i]; break; }
                }
                if (!row) {
                    done(false);
                    return;
                }
                row.attempts = (row.attempts || 0) + 1;

                if (result && (result.success == 1 || result.offline_duplicate == 1)) {
                    // Remove from queue (idempotent — duplicate also counts as success)
                    self.saveTransactions(list.filter(function(t) { return t.id !== txn.id; }));
                    done(true);
                } else {
                    row.status = 'failed';
                    row.last_error = (result && result.msg) ? result.msg : 'Sync failed';
                    self.saveTransactions(list);
                    done(false);
                }
            }).fail(function(xhr) {
                var list = self.getStoredTransactions();
                var row = null;
                for (var i = 0; i < list.length; i++) {
                    if (list[i].id === txn.id) { row = list[i]; break; }
                }
                if (row) {
                    row.attempts = (row.attempts || 0) + 1;
                    row.status = 'failed';
                    row.last_error = (xhr && xhr.status === 419)
                        ? 'Session expired — refresh POS, then Sync again'
                        : ((xhr && xhr.responseJSON && xhr.responseJSON.msg) || 'Network / server error');
                    self.saveTransactions(list);
                }
                done(false);
            });
        },

        syncOne: function(id) {
            var self = this;
            if (!this.isOnline) {
                this.showToast(this.t('offline_still_offline', 'Still offline. Connect first.'), 'warning');
                return;
            }
            var txn = null;
            var list = this.getStoredTransactions();
            for (var i = 0; i < list.length; i++) {
                if (list[i].id === id) { txn = list[i]; break; }
            }
            if (!txn) { return; }

            this.syncInProgress = true;
            this.setSyncBusy(true);
            this.postOne(txn, function() {
                self.syncInProgress = false;
                self.setSyncBusy(false);
                self.updateUI();
                self.refreshSyncModal();
            });
        },

        retryFailedOnly: function() {
            if (!this.isOnline) {
                this.showToast(this.t('offline_still_offline', 'Still offline. Connect first.'), 'warning');
                return;
            }
            var failed = this.getFailedCount();
            if (!failed) {
                this.showToast(this.t('offline_no_failed', 'No failed sales to retry.'), 'info');
                return;
            }
            var list = this.getStoredTransactions().map(function(t) {
                if (t.status === 'failed') {
                    t.status = 'pending';
                    t.last_error = null;
                }
                return t;
            });
            this.saveTransactions(list);
            this.syncPendingTransactions();
        },

        removeOne: function(id) {
            this.saveTransactions(this.getStoredTransactions().filter(function(t) {
                return t.id !== id;
            }));
        },

        confirmClearQueue: function() {
            var self = this;
            var count = this.getPendingCount();
            if (!count) {
                this.showToast(this.t('offline_nothing_to_sync', 'No pending offline sales.'), 'info');
                return;
            }
            var msg = this.t('offline_clear_confirm', 'Clear :count pending offline sale(s)? They will NOT upload.')
                .replace(':count', count);

            if (typeof swal === 'function') {
                swal({
                    title: this.t('sure', 'Are you sure?'),
                    text: msg,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true
                }).then(function(ok) {
                    if (ok) {
                        self.clearQueue();
                        self.refreshSyncModal();
                    }
                });
            } else if (window.confirm(msg)) {
                this.clearQueue();
                this.refreshSyncModal();
            }
        },

        clearQueue: function() {
            localStorage.removeItem(STORAGE_KEY);
            this.updateUI();
            this.showToast(this.t('offline_queue_cleared', 'Offline queue cleared.'), 'success');
        },

        retrySync: function() {
            this.openSyncModal();
        },

        openSyncModal: function() {
            this.ensureSyncModal();
            this.refreshSyncModal();
            $('#vspos_offline_sync_modal').modal('show');
        },

        ensureSyncModal: function() {
            if ($('#vspos_offline_sync_modal').length) {
                return;
            }
            var title = this.t('offline_sync_title', 'Offline sales — Sync');
            var html =
                '<div class="modal fade" id="vspos_offline_sync_modal" tabindex="-1" role="dialog">' +
                '<div class="modal-dialog modal-lg" role="document"><div class="modal-content">' +
                '<div class="modal-header">' +
                '<button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>' +
                '<h4 class="modal-title"><i class="fa fa-cloud-upload-alt"></i> ' + title + '</h4></div>' +
                '<div class="modal-body">' +
                '<p class="text-muted" id="vspos_offline_sync_help" style="margin-bottom:12px;"></p>' +
                '<div class="table-responsive"><table class="table table-condensed table-striped" id="vspos_offline_sync_table">' +
                '<thead><tr><th>When</th><th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>' +
                '<tbody></tbody></table></div>' +
                '<p class="text-muted" id="vspos_offline_sync_empty" style="display:none;text-align:center;padding:20px;">No pending sales.</p>' +
                '</div>' +
                '<div class="modal-footer" style="display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;">' +
                '<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                '<button type="button" class="btn btn-danger" id="vspos_offline_clear_all">Clear all</button>' +
                '<button type="button" class="btn btn-warning" id="vspos_offline_retry_failed">Retry failed</button>' +
                '<button type="button" class="btn btn-primary" id="vspos_offline_sync_now"><i class="fa fa-sync"></i> Sync now</button>' +
                '</div></div></div></div>';
            $('body').append(html);
        },

        refreshSyncModal: function() {
            if (!$('#vspos_offline_sync_modal').length) {
                return;
            }
            var list = this.getStoredTransactions().filter(function(t) {
                return t.status !== 'synced';
            });
            var $tb = $('#vspos_offline_sync_table tbody');
            $tb.empty();

            $('#vspos_offline_sync_help').text(this.isOnline
                ? this.t('offline_sync_help_online', 'Online — review pending sales and tap Sync now. Duplicate uploads are blocked safely.')
                : this.t('offline_sync_help_offline', 'Offline — sales stay queued until you reconnect, then Sync now.'));

            if (!list.length) {
                $('#vspos_offline_sync_empty').show();
                $('#vspos_offline_sync_table').hide();
                return;
            }
            $('#vspos_offline_sync_empty').hide();
            $('#vspos_offline_sync_table').show();

            var self = this;
            list.forEach(function(t) {
                var when = t.created_at ? new Date(t.created_at).toLocaleString() : '—';
                var items = (t.summary && t.summary.product_count) ? t.summary.product_count : '—';
                var total = (t.summary && t.summary.final_total) ? t.summary.final_total : '—';
                if (total !== '—' && typeof __currency_trans_from_en === 'function') {
                    var n = parseFloat(total);
                    if (!isNaN(n)) {
                        total = __currency_trans_from_en(n, true);
                    }
                }
                var statusHtml = t.status === 'failed'
                    ? ('<span class="label label-danger">failed</span>' +
                        (t.last_error ? ('<br><small class="text-danger">' + $('<div/>').text(t.last_error).html() + '</small>') : ''))
                    : '<span class="label label-warning">pending</span>';
                var actions =
                    '<button type="button" class="btn btn-xs btn-primary vspos-offline-retry-one" data-id="' + t.id + '"' +
                    (self.isOnline ? '' : ' disabled') + '>Retry</button> ' +
                    '<button type="button" class="btn btn-xs btn-default vspos-offline-remove-one" data-id="' + t.id + '">Remove</button>';
                $tb.append('<tr><td>' + when + '</td><td>' + items + '</td><td>' + total + '</td><td>' +
                    statusHtml + '</td><td class="text-right">' + actions + '</td></tr>');
            });

            $('#vspos_offline_sync_now').prop('disabled', !this.isOnline || this.syncInProgress);
            $('#vspos_offline_retry_failed').prop('disabled', !this.isOnline || this.getFailedCount() === 0 || this.syncInProgress);
        },

        setSyncBusy: function(busy) {
            $('#vspos_offline_sync_now').prop('disabled', !!busy || !this.isOnline)
                .html(busy
                    ? '<i class="fa fa-spinner fa-spin"></i> Syncing…'
                    : '<i class="fa fa-sync"></i> Sync now');
        },

        updateUI: function() {
            var $indicator = $('#offline-status-indicator');
            var $badge = $('#offline-pending-badge');
            var $syncBtn = $('#vspos_offline_sync_open');
            var pendingCount = this.getPendingCount();

            if ($indicator.length) {
                if (this.isOnline) {
                    $indicator.removeClass('offline').addClass('online')
                        .attr('title', 'Online — click to open Sync')
                        .html('<span class="pos-status-dot"></span> Online');
                } else {
                    $indicator.removeClass('online').addClass('offline')
                        .attr('title', 'Offline — sales are queued')
                        .html('<span class="pos-status-dot"></span> Offline');
                }
            }

            if ($badge.length) {
                if (pendingCount > 0) {
                    $badge.text(pendingCount).show()
                        .attr('title', pendingCount + ' sale(s) waiting — click to Sync')
                        .css({ cursor: 'pointer', display: 'inline-flex' });
                } else {
                    $badge.text('').hide();
                }
            }

            if ($syncBtn.length) {
                var $count = $syncBtn.find('.vspos-offline-sync-count');
                if ($count.length) {
                    if (pendingCount > 0) {
                        $count.text(pendingCount).show();
                    } else {
                        $count.text('').hide();
                    }
                }
            }
        },

        showToast: function(message, type) {
            type = type || 'info';
            if (typeof toastr !== 'undefined') {
                toastr.options = toastr.options || {};
                toastr.options.timeOut = type === 'error' ? 8000 : 4000;
                toastr.options.closeButton = true;
                toastr.options.progressBar = true;
                if (typeof toastr[type] === 'function') {
                    toastr[type](message);
                } else {
                    toastr.info(message);
                }
            } else {
                console.log('[' + type.toUpperCase() + ']', message);
            }
        }
    };

})(jQuery);
