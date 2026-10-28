/**
 * Regression tests for the post-payment receipt return decision.
 * Run: node tests/js/pos-receipt-return.test.js
 */
var assert = require('assert');
var path = require('path');
var fs = require('fs');
var R = require(path.join(__dirname, '../../public/js/pos-receipt-return.js'));

function finish(session, action) {
    return R.decide(session, action);
}

var fresh = R.begin(R.createSession(), { transactionId: 41, isEdit: false, saleCompleteOpen: true });

var printed = finish(fresh, 'print');
assert.strictEqual(printed.closeUi, true, 'print closes the receipt UI');
assert.strictEqual(printed.navigate, false, 'a new sale stays on the POS screen');
assert.strictEqual(printed.resubmitPayment, false, 'print must not submit payment again');
assert.strictEqual(printed.clearCart, false, 'receipt completion must not clear the cart itself');
assert.strictEqual(printed.reportPrintSuccess, true);
assert.strictEqual(printed.session.finished, true);
assert.strictEqual(printed.session.transactionId, 41);

var again = finish(printed.session, 'print');
assert.strictEqual(again.duplicate, true, 'a second completion is ignored');
assert.strictEqual(again.closeUi, false);
assert.strictEqual(again.navigate, false);
assert.strictEqual(again.resubmitPayment, false);

var edit = R.begin(R.createSession(), { transactionId: 88, isEdit: true, saleCompleteOpen: false });
edit = R.markUi(edit, { previewOpen: true });
var editPrint = finish(edit, 'print');
assert.strictEqual(editPrint.navigate, true, 'an edited sale returns to a new POS screen');
assert.strictEqual(editPrint.resubmitPayment, false);
assert.strictEqual(editPrint.clearCart, false);
var editAgain = finish(editPrint.session, 'dismiss');
assert.strictEqual(editAgain.duplicate, true);
assert.strictEqual(editAgain.navigate, false, 'return navigation happens once');

var failed = finish(R.begin(R.createSession(), { transactionId: 7, isEdit: true }), 'print-failed');
assert.strictEqual(failed.closeUi, true, 'a failed print still leaves the receipt screen');
assert.strictEqual(failed.navigate, true);
assert.strictEqual(failed.reportPrintSuccess, false, 'a failed print is not reported as printed');
assert.strictEqual(failed.resubmitPayment, false);

var sendFail = finish(R.begin(R.createSession(), { transactionId: 8, isEdit: false, saleCompleteOpen: true }), 'send-failed');
assert.strictEqual(sendFail.closeUi, false, 'a failed send stays on the receipt popup');
assert.strictEqual(sendFail.navigate, false);
assert.strictEqual(sendFail.session.active, true);

var sent = finish(sendFail.session, 'send');
assert.strictEqual(sent.closeUi, true);
assert.strictEqual(sent.reportPrintSuccess, true);
assert.strictEqual(sent.resubmitPayment, false);
assert.strictEqual(sent.clearCart, false);

var both = R.markUi(
    R.begin(R.createSession(), { transactionId: 9, isEdit: true, saleCompleteOpen: true }),
    { previewOpen: true }
);
var closedPreview = finish(both, 'dismiss');
assert.strictEqual(closedPreview.closeUi, true, 'closing the preview returns to the POS screen');
assert.strictEqual(closedPreview.closePreviewOnly, false);
assert.strictEqual(closedPreview.navigate, true);
assert.strictEqual(closedPreview.session.finished, true);
assert.strictEqual(closedPreview.clearCart, false);
assert.strictEqual(closedPreview.resubmitPayment, false);

var closedAgain = finish(closedPreview.session, 'dismiss');
assert.strictEqual(closedAgain.duplicate, true);
assert.strictEqual(closedAgain.navigate, false);

var previewOnly = R.markUi(
    R.begin(R.createSession(), { transactionId: 10, isEdit: false, saleCompleteOpen: false }),
    { previewOpen: true }
);
var closedPreviewOnly = finish(previewOnly, 'dismiss');
assert.strictEqual(closedPreviewOnly.closeUi, true);
assert.strictEqual(closedPreviewOnly.navigate, false);

var downloaded = finish(R.begin(R.createSession(), { transactionId: 11, isEdit: false }), 'download');
assert.strictEqual(downloaded.closeUi, true);
assert.strictEqual(downloaded.resubmitPayment, false);
assert.strictEqual(downloaded.clearCart, false);

var same = R.begin(R.createSession(), { transactionId: 12, isEdit: false, saleCompleteOpen: true });
var sameAgain = R.begin(same, { transactionId: 12, isEdit: true, saleCompleteOpen: false });
assert.strictEqual(sameAgain, same, 'the same sale does not start a second receipt session');
assert.strictEqual(sameAgain.isEdit, false);

var calls = 0;
var controller = R.createController();
controller.setHandler(function (decision) {
    calls += 1;
    assert.strictEqual(decision.resubmitPayment, false);
    assert.strictEqual(decision.clearCart, false);
});
controller.begin({ transactionId: 15, isEdit: true, saleCompleteOpen: true });
controller.handleAction('print');
controller.handleAction('print');
controller.handleDismiss();
assert.strictEqual(calls, 1, 'duplicate receipt callbacks do not return twice');

var posJs = fs.readFileSync(path.join(__dirname, '../../public/js/pos.js'), 'utf8');
assert.ok(posJs.indexOf('PosReceiptReturn.begin') !== -1, 'payment success starts one receipt session');
assert.ok(posJs.indexOf('deferRedirect: showReceiptUi && isEditSale') !== -1, 'edit sales wait for the receipt action');
assert.ok(posJs.indexOf('pos_form_obj.submit') !== -1);
var returnFn = posJs.slice(posJs.indexOf('function pos_apply_receipt_return'));
returnFn = returnFn.slice(0, returnFn.indexOf('function pos_send_print_bridge'));
assert.strictEqual(returnFn.indexOf('.submit('), -1, 'returning from the receipt must not submit the sale');
assert.strictEqual(returnFn.indexOf('reset_pos_form'), -1, 'returning from the receipt must not clear the cart again');

var bridgeFn = posJs.slice(posJs.indexOf('function pos_send_print_bridge'));
bridgeFn = bridgeFn.slice(0, bridgeFn.indexOf('function pos_print'));
assert.ok(bridgeFn.indexOf('receipt printer is unavailable') !== -1);
assert.strictEqual(bridgeFn.indexOf('printed successfully'), -1);

var functionsJs = fs.readFileSync(path.join(__dirname, '../../public/js/functions.js'), 'utf8');
assert.ok(functionsJs.indexOf('returnToPosBeforePrint') !== -1, 'print returns to POS before the browser print dialog');
var runPrint = functionsJs.slice(functionsJs.indexOf('function __vspos_run_browser_print'));
runPrint = runPrint.slice(0, runPrint.indexOf('function __vspos_bind_print_preview_once'));
assert.ok(runPrint.indexOf('beforePrint()') !== -1 && runPrint.indexOf('beforePrint()') < runPrint.indexOf('printWindow.print()'), 'the POS is restored before print() can block');

console.log('pos-receipt-return tests passed');
