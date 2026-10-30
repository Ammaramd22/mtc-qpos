/**
 * POS product list view: separate grid and list templates.
 * Run: node tests/js/pos-product-list-view.test.js
 */
var assert = require('assert');
var fs = require('fs');
var path = require('path');

var root = path.join(__dirname, '../..');
var blade = fs.readFileSync(path.join(root, 'resources/views/sale_pos/partials/product_list.blade.php'), 'utf8');
var rows = fs.readFileSync(path.join(root, 'resources/views/sale_pos/partials/product_list_rows.blade.php'), 'utf8');
var touch = fs.readFileSync(path.join(root, 'resources/views/sale_pos/partials/touch_main.blade.php'), 'utf8');
var css = fs.readFileSync(path.join(root, 'public/css/pos-touch.css'), 'utf8');
var js = fs.readFileSync(path.join(root, 'public/js/pos-touch.js'), 'utf8');
var controller = fs.readFileSync(path.join(root, 'app/Http/Controllers/SellPosController.php'), 'utf8');

function check(label, cond) {
    assert.ok(cond, label);
    console.log('PASS ' + label);
}

check('grid card markup remains', blade.indexOf('class="text_div') !== -1 && blade.indexOf('image-container') !== -1);
check('grid template does not embed list cells', blade.indexOf('vspos-plist-cells') === -1 && blade.indexOf('vspos-plist-row') === -1);
check('grid template delegates list view', blade.indexOf("product_list_rows") !== -1);
check('empty state copy is unchanged', blade.indexOf('no_products_to_display') !== -1 && blade.indexOf('No matching products found') !== -1);

check('list template is a full-width row, not a card', rows.indexOf('vspos-plist-wrap') !== -1 && rows.indexOf('grid-column:1 / -1') !== -1 && rows.indexOf('image-container') === -1 && rows.indexOf('text_div') === -1);
check('list template includes a thumbnail image', rows.indexOf('<img ') !== -1);
check('list row is still a product_box click target', rows.indexOf('class="product_box vspos-plist-row') !== -1);
check('list add control does not take its own click', rows.indexOf('<span class="vspos-plist-add"') !== -1);
check('list has size and color columns', rows.indexOf('vspos-plist-size') !== -1 && rows.indexOf('vspos-plist-color') !== -1 && rows.indexOf('vspos-plist-empty') === -1);
check('list header travels with the list template', rows.indexOf('id="vspos_plist_head"') !== -1);
check('list empty state copy is unchanged', rows.indexOf('no_products_to_display') !== -1 && rows.indexOf('No matching products found') !== -1);

check('view switch offers grid and list', touch.indexOf('data-pos-view="grid"') !== -1 && touch.indexOf('data-pos-view="list"') !== -1);
check('static list header is not above the product container', touch.indexOf('id="vspos_plist_head"') === -1);
check('preference is read before the shell paints', touch.indexOf("localStorage.getItem('qpos.pos.productView')") !== -1);
check('suggestion requests carry the view mode', touch.indexOf('product_view') !== -1);
check('grid and list buttons call the view switch directly', touch.indexOf("vsposSetProductView('grid'") !== -1 && touch.indexOf("vsposSetProductView('list'") !== -1);

check('preference key is stored in the browser', js.indexOf("localStorage.setItem(PRODUCT_VIEW_KEY") !== -1 && js.indexOf("qpos.pos.productView") !== -1);
check('product tap is still the product card', js.indexOf("#vspos_touch_shell div.product_box") !== -1);
check('list add control has no click handler', js.indexOf("'.vspos-plist-add'") === -1 && js.indexOf('".vspos-plist-add"') === -1);
check('switching views reloads the product template', js.indexOf('reloadVisibleProducts') !== -1 && js.indexOf('get_product_suggestion_list') !== -1);
check('view button uses the direct switch when it is present', js.indexOf('window.vsposSetProductView') !== -1);

check('list styles target the row, not grid cards', css.indexOf('.vspos-plist-row') !== -1 && css.indexOf('display: contents') === -1);
check('grid cards are hidden while list mode is active', css.indexOf('html.vspos-product-view-list #product_list_items > .col-md-3') !== -1);

var suggestion = controller.slice(controller.indexOf('function getProductSuggestion'), controller.indexOf('function getTradeInOptions'));
check('suggestion query includes category', suggestion.indexOf("pos_cat.name as category_name") !== -1);
check('size column is selected only when it exists', suggestion.indexOf("hasColumn('products', 'size')") !== -1 && suggestion.indexOf('p.size as product_size') !== -1);
check('color column is selected only when it exists', suggestion.indexOf("hasColumn('products', 'color')") !== -1 && suggestion.indexOf('p.color as product_color') !== -1);
check('controller chooses list or grid', suggestion.indexOf("product_view") !== -1);

console.log('All list-view checks passed');
