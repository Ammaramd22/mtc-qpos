/**
 * Post-payment receipt completion.
 * Decides when a receipt action is finished and whether the cashier
 * should leave the receipt UI. Does not submit payments or clear the cart.
 */
(function (root, factory) {
    var api = factory();
    if (typeof module !== 'undefined' && module.exports) {
        module.exports = api;
    }
    if (root) {
        root.PosReceiptReturn = api.createController();
    }
})(typeof window !== 'undefined' ? window : (typeof global !== 'undefined' ? global : this), function () {
    function createSession() {
        return {
            active: false,
            finished: false,
            transactionId: null,
            isEdit: false,
            previewOpen: false,
            saleCompleteOpen: false
        };
    }

    function copySession(session) {
        return {
            active: !!session.active,
            finished: !!session.finished,
            transactionId: session.transactionId,
            isEdit: !!session.isEdit,
            previewOpen: !!session.previewOpen,
            saleCompleteOpen: !!session.saleCompleteOpen
        };
    }

    function begin(session, meta) {
        meta = meta || {};
        var current = session || createSession();
        if (current.active && !current.finished && current.transactionId === meta.transactionId) {
            return current;
        }
        return {
            active: true,
            finished: false,
            transactionId: meta.transactionId == null ? null : meta.transactionId,
            isEdit: !!meta.isEdit,
            previewOpen: false,
            saleCompleteOpen: !!meta.saleCompleteOpen
        };
    }

    function markUi(session, patch) {
        if (!session || !session.active || session.finished) {
            return session || createSession();
        }
        var next = copySession(session);
        if (patch && Object.prototype.hasOwnProperty.call(patch, 'previewOpen')) {
            next.previewOpen = !!patch.previewOpen;
        }
        if (patch && Object.prototype.hasOwnProperty.call(patch, 'saleCompleteOpen')) {
            next.saleCompleteOpen = !!patch.saleCompleteOpen;
        }
        return next;
    }

    function decide(session, action) {
        var decision = {
            action: action || '',
            closeUi: false,
            closePreviewOnly: false,
            navigate: false,
            duplicate: false,
            reportPrintSuccess: false,
            resubmitPayment: false,
            clearCart: false,
            transactionId: session ? session.transactionId : null,
            session: session || createSession()
        };

        if (!session || !session.active || session.finished) {
            decision.duplicate = true;
            return decision;
        }

        if (action === 'send-failed' || action === 'download-failed') {
            return decision;
        }

        decision.closeUi = true;
        decision.navigate = !!session.isEdit;
        decision.reportPrintSuccess = action === 'print' || action === 'send' || action === 'download';
        decision.session = {
            active: false,
            finished: true,
            transactionId: session.transactionId,
            isEdit: !!session.isEdit,
            previewOpen: false,
            saleCompleteOpen: false
        };
        return decision;
    }

    function createController() {
        var session = createSession();
        var handler = null;
        var settled = [];

        function emit(decision) {
            if (decision.closeUi && typeof handler === 'function') {
                handler(decision);
            }
            if (decision.closeUi) {
                var waiters = settled.splice(0, settled.length);
                for (var i = 0; i < waiters.length; i++) {
                    waiters[i](decision);
                }
            }
            return decision;
        }

        return {
            begin: function (meta) {
                session = begin(session, meta);
                return copySession(session);
            },
            markUi: function (patch) {
                session = markUi(session, patch);
                return copySession(session);
            },
            isActive: function () {
                return !!(session && session.active && !session.finished);
            },
            isPreviewOpen: function () {
                return !!(session && session.active && !session.finished && session.previewOpen);
            },
            setHandler: function (fn) {
                handler = fn;
            },
            whenSettled: function (fn) {
                if (session && session.finished) {
                    fn(decide(session, 'dismiss'));
                    return;
                }
                settled.push(fn);
            },
            handleAction: function (action) {
                var decision = decide(session, action);
                session = decision.session;
                return emit(decision);
            },
            handleDismiss: function () {
                var decision = decide(session, 'dismiss');
                session = decision.session;
                return emit(decision);
            }
        };
    }

    return {
        createSession: createSession,
        begin: begin,
        markUi: markUi,
        decide: decide,
        createController: createController
    };
});
