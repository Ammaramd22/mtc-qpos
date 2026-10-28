/**
 * Regression checks for the mobile POS action menu theme and icons.
 * Run: node tests/js/pos-mobile-action-menu.test.js
 */
var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '../..');
var theme = fs.readFileSync(path.join(root, 'public/css/pos-theme.css'), 'utf8');
var light = fs.readFileSync(path.join(root, 'public/css/qpos-light.css'), 'utf8');
var app = fs.readFileSync(path.join(root, 'public/css/app.css'), 'utf8');
var header = fs.readFileSync(path.join(root, 'resources/views/layouts/partials/header-pos.blade.php'), 'utf8');

function sliceBetween(source, start, end) {
    var from = source.indexOf(start);
    var to = source.indexOf(end, from + start.length);
    assert.ok(from !== -1 && to !== -1, 'missing block: ' + start);
    return source.slice(from, to);
}

var menu = sliceBetween(
    theme,
    'Opened hamburger menu. Colors come from theme tokens.',
    '@media (min-width: 768px)'
);

assert.ok(theme.indexOf('--vspos-menu-item-bg: #141414;') !== -1, 'dark menu item token');
assert.ok(light.indexOf('--vspos-menu-item-bg: #ffffff;') !== -1, 'light menu item token');
assert.ok(light.indexOf('--vspos-menu-accent-bg: #fff6d6;') !== -1, 'light POS highlight');
assert.ok(light.indexOf('--vspos-menu-item-text: #111827;') !== -1, 'light menu text token');
assert.ok(theme.indexOf('--vspos-menu-accent-bg: rgba(245, 197, 24, 0.18);') !== -1, 'dark POS highlight');
assert.ok(menu.indexOf('var(--vspos-menu-bg, #0a0a0a)') !== -1, 'sheet uses theme background');
assert.ok(menu.indexOf('var(--vspos-menu-item-bg, #141414)') !== -1, 'rows use theme background');
assert.ok(menu.indexOf('background: #141414 !important') === -1, 'rows are not hardcoded dark');
assert.ok(menu.indexOf('color: #f5f5f5 !important') === -1, 'labels are not hardcoded light');

assert.ok(menu.indexOf('> button.tw-bg-white:hover') !== -1, 'hover beats the white header hover rule');
assert.ok(menu.indexOf('> button.tw-bg-white:focus') !== -1, 'focus stays on the menu token');
assert.ok(menu.indexOf('> button.tw-bg-white:active') !== -1, 'pressed state stays on the menu token');
assert.ok(menu.indexOf('> button.tw-bg-white:hover:active') !== -1, 'touch hover+press stays on the menu token');

assert.ok(menu.indexOf('.vspos-stock-alert > .vspos-stock-alert-btn') !== -1, 'nested stock alert is a menu row');
assert.ok(menu.indexOf('span:not(.vspos-stock-alert-badge)') !== -1, 'stock label shows and the count badge does not');
assert.ok(menu.indexOf('> .qpos-theme-toggle') !== -1, 'duplicate theme toggle stays out of the mobile sheet');
assert.ok(menu.indexOf('> .tw-hidden') !== -1, 'hidden header controls stay hidden');

assert.ok(menu.indexOf('> strong > svg') !== -1, 'svg actions keep an icon well');
assert.ok(menu.indexOf('> strong > i') !== -1, 'icon-font actions get the same well');
assert.ok(menu.indexOf('var(--vspos-menu-icon-bg, rgba(245, 197, 24, 0.12))') !== -1, 'icon wells follow the theme');
assert.ok(theme.indexOf('.tw-text-\\[\\#F59E0B\\]') !== -1, 'exchange and stock amber color is defined');
assert.ok(theme.indexOf('.tw-text-\\[\\#0f766e\\]') !== -1, 'photocopy icon color is defined');
assert.ok(theme.indexOf('.tw-text-\\[\\#0369a1\\]') !== -1, 'printout icon color is defined');

var mobileApp = sliceBetween(app, 'POS MOBILE VIEW FIXES', 'ADMIN / DASHBOARD MOBILE RESPONSIVE');
assert.ok(
    mobileApp.indexOf('#pos_header_more_options > button:not(.tw-hidden)') !== -1,
    'mobile layout must not force hidden buttons visible'
);
assert.ok(
    mobileApp.indexOf('#pos_header_more_options > button {') === -1,
    'unfiltered button display rule was the leaked theme toggle'
);

var desktopAt = theme.indexOf('@media (min-width: 768px)');
assert.ok(desktopAt > theme.indexOf('Opened hamburger menu'), 'desktop query follows the drawer rules');
var beforeDesktop = 0;
for (var bi = 0; bi < desktopAt; bi++) {
    if (theme[bi] === '{') beforeDesktop++;
    else if (theme[bi] === '}') beforeDesktop--;
}
assert.strictEqual(beforeDesktop, 0, 'mobile drawer rules close before the desktop media query');
assert.ok(menu.indexOf('position: fixed') !== -1, 'drawer is fixed');
assert.ok(menu.indexOf('.pos-mnav-backdrop') !== -1, 'backdrop rule exists');
assert.ok(menu.indexOf('.pos-mnav-head') !== -1, 'sticky header rule exists');
assert.ok(menu.indexOf('> .pos-mnav-profile { order: 53;') !== -1, 'profile stays at the bottom');
assert.ok(menu.indexOf('.pos-mnav-only') !== -1 || theme.indexOf('.pos-mnav-only') !== -1, 'mobile-only chrome can be hidden on desktop');

['posMnavSet', 'pos-mnav-head', 'pos-mnav-label-returns', 'pos-mnav-label-account', 'pos-mnav-profile', 'Start a new sale', 'Process sales return', 'Return without sale', 'Exchange products', 'Resume suspended bills', 'Reprint invoices', 'View cash register details', 'id="return_sale"', 'id="pos_exchange"', 'id="view_suspended_sales"', 'id="recent-transactions"', 'id="register_details"', 'getBusinessSettings', 'LoginController::class, \'logout\'', 'getProfile'].forEach(function (needle) {
    assert.ok(header.indexOf(needle) !== -1, 'header keeps ' + needle);
});

console.log('pos-mobile-action-menu.test.js: ok');
