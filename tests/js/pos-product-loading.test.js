/**
 * POS product-grid loading: request order, empty state, and scroll guards.
 * Run: node tests/js/pos-product-loading.test.js
 */
var assert = require('assert');
var fs = require('fs');
var path = require('path');
var vm = require('vm');

var root = path.join(__dirname, '../..');
var posJs = fs.readFileSync(path.join(root, 'public/js/pos.js'), 'utf8');
var liveJs = fs.readFileSync(path.join(root, 'public/js/pos-live-search.js'), 'utf8');
var touchJs = fs.readFileSync(path.join(root, 'public/js/pos-touch.js'), 'utf8');
var touchCss = fs.readFileSync(path.join(root, 'public/css/pos-touch.css'), 'utf8');
var blade = fs.readFileSync(path.join(root, 'resources/views/sale_pos/partials/product_list.blade.php'), 'utf8');

function sliceBetween(source, start, end) {
    var from = source.indexOf(start);
    var to = source.indexOf(end, from + start.length);
    assert.ok(from !== -1 && to !== -1, 'missing block: ' + start);
    return source.slice(from, to);
}

var guard = sliceBetween(posJs, '/* vspos-suggest-guard:start */', '/* vspos-suggest-guard:end */');
var sandbox = {};
vm.createContext(sandbox);
vm.runInContext(guard, sandbox);

var classify = sandbox.vsposSuggestClassifyHtml;
var shouldLoad = sandbox.vsposSuggestShouldLoadNext;
var planOf = sandbox.vsposSuggestApplyPlan;

function card(id) {
    return '<div class="product_box" data-variation_id="' + id + '" data-name="Rose" data-sku="SKU-' + id + '"></div>';
}
function cards(n) {
    var html = '';
    for (var i = 0; i < n; i++) html += card(i + 1);
    return html;
}
var emptyState = '<input type="hidden" id="no_products_found">' +
    '<div class="col-md-12 vspos-no-products"><h4>No Products to display</h4>' +
    '<button type="button" id="vspos_retry_products">Retry</button></div>';
var endMarker = '<input type="hidden" class="vspos-suggestion-end" id="suggestion_end" value="1">';

// The old scroll test was true whenever the panel was taller than the cards.
function oldScrollAtEnd(scrollTop, innerHeight, scrollHeight) {
    return scrollTop + innerHeight >= scrollHeight;
}

var shortGrid = { ignoreScroll: false, loading: false, ended: false, scrollTop: 0, clientHeight: 700, scrollHeight: 420 };
assert.strictEqual(oldScrollAtEnd(0, 700, 420), true, 'old handler treats a short grid as the bottom');
assert.strictEqual(shouldLoad(shortGrid), false, 'short grid must not request the next page');

assert.strictEqual(shouldLoad({
    ignoreScroll: true, loading: false, ended: false,
    scrollTop: 600, clientHeight: 400, scrollHeight: 1000
}), false, 'scroll during a category reset is ignored');
assert.strictEqual(shouldLoad({
    ignoreScroll: false, loading: true, ended: false,
    scrollTop: 600, clientHeight: 400, scrollHeight: 1000
}), false, 'in-flight request blocks the next page');
assert.strictEqual(shouldLoad({
    ignoreScroll: false, loading: false, ended: true,
    scrollTop: 600, clientHeight: 400, scrollHeight: 1000
}), false, 'finished list does not request another page');
assert.strictEqual(shouldLoad({
    ignoreScroll: false, loading: false, ended: false,
    scrollTop: 0, clientHeight: 400, scrollHeight: 1000
}), false, 'top of a long list does not request the next page');
assert.strictEqual(shouldLoad({
    ignoreScroll: false, loading: false, ended: false,
    scrollTop: 560, clientHeight: 400, scrollHeight: 1000
}), true, 'near the bottom of a long list loads the next page');

var twelve = classify(cards(12));
assert.strictEqual(twelve.count, 12);
assert.strictEqual(twelve.hasProducts, true);
var firstPage = planOf({
    reqId: 1, currentReqId: 1, page: 1, classified: twelve,
    hasVisibleProducts: false, pageSize: 50
});
assert.strictEqual(firstPage.action, 'replace');
assert.strictEqual(firstPage.end, true, 'fewer than 50 cards is the last page');

var full = planOf({
    reqId: 1, currentReqId: 1, page: 1, classified: classify(cards(50)),
    hasVisibleProducts: false, pageSize: 50
});
assert.strictEqual(full.action, 'replace');
assert.strictEqual(full.end, false, 'a full page can have another page');

var nextFull = planOf({
    reqId: 2, currentReqId: 2, page: 2, classified: classify(cards(50)),
    hasVisibleProducts: true, pageSize: 50
});
assert.strictEqual(nextFull.action, 'append');
assert.strictEqual(nextFull.end, false);

var nextShort = planOf({
    reqId: 3, currentReqId: 3, page: 2, classified: classify(cards(7)),
    hasVisibleProducts: true, pageSize: 50
});
assert.strictEqual(nextShort.action, 'append');
assert.strictEqual(nextShort.end, true);

// Screenshot 1: products are on screen and page 2 is the empty template.
var appendedEmpty = planOf({
    reqId: 2, currentReqId: 2, page: 2,
    classified: classify(emptyState),
    hasVisibleProducts: true, pageSize: 50
});
assert.strictEqual(appendedEmpty.action, 'ignore');
assert.strictEqual(appendedEmpty.end, true);
assert.ok(!appendedEmpty.html, 'empty page 2 is not written into the grid');

var endWhileVisible = planOf({
    reqId: 2, currentReqId: 2, page: 2,
    classified: classify(endMarker),
    hasVisibleProducts: true, pageSize: 50
});
assert.strictEqual(endWhileVisible.action, 'ignore');
assert.strictEqual(endWhileVisible.end, true);

// Screenshot 2: page 1 was dropped and only the empty page came back.
var lostFirstPage = planOf({
    reqId: 2, currentReqId: 2, page: 2,
    classified: classify(endMarker),
    hasVisibleProducts: false, pageSize: 50
});
assert.strictEqual(lostFirstPage.action, 'recover');

var stale = planOf({
    reqId: 1, currentReqId: 4, page: 1,
    classified: classify(cards(3)),
    hasVisibleProducts: false, ended: false
});
assert.strictEqual(stale.action, 'ignore');
assert.strictEqual(stale.stale, true);

var genuineEmpty = planOf({
    reqId: 5, currentReqId: 5, page: 1,
    classified: classify(emptyState),
    hasVisibleProducts: false, pageSize: 50
});
assert.strictEqual(genuineEmpty.action, 'replace');
assert.strictEqual(genuineEmpty.end, true);
assert.ok(genuineEmpty.html.indexOf('id="no_products_found"') !== -1);

var serverGarbage = planOf({
    reqId: 6, currentReqId: 6, page: 1,
    classified: classify('<html><body>Server Error</body></html>'),
    hasVisibleProducts: false
});
assert.strictEqual(serverGarbage.action, 'error');

var badNextPage = planOf({
    reqId: 7, currentReqId: 7, page: 3,
    classified: classify(''),
    hasVisibleProducts: true
});
assert.strictEqual(badNextPage.action, 'rewind');

var loader = sliceBetween(posJs, 'function vsposSuggestLoadingHtml()', 'function vsposSuggestErrorHtml()');
var errorHtml = sliceBetween(posJs, 'function vsposSuggestErrorHtml()', 'function vsposSuggestionItems()');
assert.ok(loader.indexOf('Loading products...') !== -1);
assert.ok(loader.indexOf('no_products_found') === -1, 'loading markup is not the empty state');
assert.ok(errorHtml.indexOf('Could not load products.') !== -1);
assert.ok(errorHtml.indexOf('id="vspos_retry_products"') !== -1);
assert.ok(errorHtml.indexOf('no_products_found') === -1, 'a failed request is not an empty product list');

var fetchFn = sliceBetween(posJs, 'function get_product_suggestion_list(', 'function get_recent_transactions(');
assert.ok(fetchFn.indexOf('__vspos_suggest_ignore_scroll = true') < fetchFn.indexOf('vsposSuggestLoadingHtml()'), 'scroll is blocked before the grid is cleared');
assert.ok(fetchFn.indexOf('page > 1 &&') < fetchFn.indexOf('.abort'), 'a blocked later page does not abort the current request');
assert.ok(fetchFn.indexOf('append(result)') === -1, 'raw HTML is not appended before it is classified');
assert.ok(fetchFn.indexOf("plan.action === 'replace'") !== -1);
assert.ok(fetchFn.indexOf("plan.action === 'append'") !== -1);
assert.ok(fetchFn.indexOf('vsposSuggestErrorHtml()') !== -1);
assert.ok(fetchFn.indexOf('pageSize: 50') !== -1);

var scrollFn = sliceBetween(posJs, 'function vsposBindSuggestionScroll()', 'vsposBindSuggestionScroll();');
assert.ok(scrollFn.indexOf('vsposSuggestShouldLoadNext') !== -1);
assert.ok(scrollFn.indexOf("scrollTop: this.scrollTop") !== -1);
assert.ok(posJs.indexOf("$('div#product_list_body').on('scroll'") === -1, 'old unguarded scroll handler is gone');

var allCategories = sliceBetween(posJs, '"All Categories"', 'return;');
assert.ok(allCategories.indexOf("$('input#suggestion_page').val(1);") !== -1, 'All Categories resets to page 1');

assert.ok(liveJs.indexOf("$items.html('')") === -1, 'search must not clear the grid before the loader runs');
assert.ok(liveJs.indexOf('__vspos_suggest_ignore_scroll = true') !== -1);
assert.ok(liveJs.indexOf('global_p_search_term = term') !== -1);

assert.ok(touchJs.indexOf('__vspos_suggest_ignore_scroll = true') !== -1);
assert.ok(touchJs.indexOf('function reloadProducts') !== -1);

assert.ok(blade.indexOf('id="suggestion_end"') !== -1);
assert.ok(blade.indexOf('currentPage() > 1') !== -1);
assert.ok(blade.indexOf('id="suggestion_end"') < blade.indexOf('@forelse'), 'end marker is not inside the product loop');
assert.ok(blade.indexOf('id="no_products_found"') !== -1, 'a real empty first page still uses the empty state');
assert.ok(blade.indexOf('data-variation_id') !== -1);
assert.ok(blade.indexOf('data-sku') !== -1);
assert.ok(blade.indexOf('qty_available') !== -1);

assert.ok(touchCss.indexOf('#product_list_items > .vspos-no-products') !== -1);
assert.ok(touchCss.indexOf('grid-column: 1 / -1') !== -1);

console.log('pos-product-loading.test.js: all assertions passed');
