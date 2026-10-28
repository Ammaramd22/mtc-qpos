/**
 * Mobile Reports opens the dedicated selection page.
 * Run: node tests/js/qpos-mobile-reports-nav.test.js
 */
var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '../..');
var blade = fs.readFileSync(path.join(root, 'resources/views/layouts/partials/mobile_bottom_nav.blade.php'), 'utf8');
var hub = fs.readFileSync(path.join(root, 'resources/views/report/hub.blade.php'), 'utf8');
var routes = fs.readFileSync(path.join(root, 'routes/web.php'), 'utf8');
var controller = fs.readFileSync(path.join(root, 'app/Http/Controllers/ReportController.php'), 'utf8');
var sidebar = fs.readFileSync(path.join(root, 'app/Http/Middleware/AdminSidebarMenu.php'), 'utf8');

assert.ok(blade.indexOf('getProfitLoss') === -1, 'Reports no longer links to Profit / Loss');
assert.ok(blade.indexOf("route('reports.hub')") !== -1, 'Reports links to the selection page');
assert.ok(blade.indexOf('href="{{ $reports_url }}"') !== -1, 'Reports is a normal link');
assert.ok(blade.indexOf('qbn_reports_toggle') === -1, 'sidebar toggle button is gone');
assert.ok(blade.indexOf('qpos-mobile-reports-nav.js') === -1, 'sidebar opener script is not loaded');
assert.ok(blade.indexOf('href="{{ $home_url }}"') !== -1, 'Home link stays');
assert.ok(blade.indexOf('href="{{ $products_url }}"') !== -1, 'Products link stays');
assert.ok(blade.indexOf('href="{{ $pos_url }}"') !== -1, 'POS link stays');
assert.ok(blade.indexOf('href="{{ $invoices_url }}"') !== -1, 'Sell link stays');
assert.ok(blade.indexOf('SellPosController') !== -1, 'POS route stays');
assert.ok(blade.indexOf('ProductController') !== -1, 'Products route stays');
assert.ok(blade.indexOf('SellController') !== -1, 'Sell route stays');
assert.ok(blade.indexOf("$seg1 === 'reports'") !== -1, 'Reports stays active on report pages');
assert.ok(blade.indexOf("--qbn-clearance") !== -1, 'bottom nav clearance stays');
assert.ok(blade.indexOf('@media (max-width: 991.98px)') !== -1, 'mobile breakpoint unchanged');
assert.ok(blade.indexOf('.qpos-bottom-nav { display: none !important; }') !== -1, 'print still hides the bar');

assert.ok(routes.indexOf("Route::get('/reports', [ReportController::class, 'index'])->name('reports.hub');") !== -1, 'hub route exists');
assert.ok(routes.indexOf("'/reports/profit-loss'") !== -1, 'profit / loss route stays');
assert.ok(controller.indexOf('SidebarReports::collect') !== -1, 'page reads the sidebar menu');
assert.ok(controller.indexOf('function getProfitLoss') !== -1, 'profit / loss action stays');
assert.ok(sidebar.indexOf("'id' => 'tour_step8'") !== -1, 'sidebar reports menu id stays');

assert.ok(hub.indexOf("@lang('report.reports')") !== -1, 'page title uses the reports label');
assert.ok(hub.indexOf("@lang('report.reports_hub_lead')") !== -1, 'page has the description');
assert.ok(hub.indexOf("{{ \$item['title'] }}") !== -1, 'card labels come from the menu');
assert.ok(hub.indexOf("href=\"{{ \$item['url'] }}\"") !== -1, 'cards use menu urls');
assert.ok(hub.indexOf('overflow-wrap: anywhere') !== -1, 'long names wrap');
assert.ok(hub.indexOf('minmax(0, 1fr)') !== -1, 'cards stay inside the viewport');
assert.ok(hub.indexOf('html[data-vspos-theme="light"]') !== -1, 'light theme is styled');
assert.ok(hub.indexOf('getProfitLoss') === -1, 'hub does not hardcode a report route');

console.log('qpos-mobile-reports-nav tests passed');
