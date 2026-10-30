<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AccountReportsController;
use App\Http\Controllers\AccountTypeController;
// use App\Http\Controllers\Auth;
use App\Http\Controllers\BackUpController;
use App\Http\Controllers\BarcodeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessLocationController;
use App\Http\Controllers\CashRegisterController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CombinedPurchaseReturnController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerGroupController;
use App\Http\Controllers\DashboardConfiguratorController;
use App\Http\Controllers\DiscountController;
use App\Http\Controllers\DocumentAndNoteController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\GroupTaxController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportOpeningStockController;
use App\Http\Controllers\ImportProductsController;
use App\Http\Controllers\ImportSalesController;
use App\Http\Controllers\Install;
use App\Http\Controllers\InvoiceLayoutController;
use App\Http\Controllers\InvoiceSchemeController;
use App\Http\Controllers\LabelsController;
use App\Http\Controllers\LedgerDiscountController;
use App\Http\Controllers\LocationSettingsController;
use App\Http\Controllers\ManageUserController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\OpeningStockController;
use App\Http\Controllers\PrinterController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PurchasePosController;
use App\Http\Controllers\PurchaseRequisitionController;
use App\Http\Controllers\PurchaseReturnController;
use App\Http\Controllers\ProductExpenseController;
use App\Http\Controllers\NutmegDryingController;
use App\Http\Controllers\NutmegReportController;
use App\Http\Controllers\NutmegSettingsController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\Restaurant;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SalesCommissionAgentController;
use App\Http\Controllers\SalesOrderController;
use App\Http\Controllers\SellController;
use App\Http\Controllers\SellingPriceGroupController;
use App\Http\Controllers\SellPosController;
use App\Http\Controllers\SellReturnController;
use App\Http\Controllers\StockAdjustmentController;
use App\Http\Controllers\StockTransferController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\TaxRateController;
use App\Http\Controllers\TransactionPaymentController;
use App\Http\Controllers\TypesOfServiceController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VariationTemplateController;
use App\Http\Controllers\MobileShopController;
use App\Http\Controllers\TyreShopController;
use App\Http\Controllers\VehicleModificationController;
use App\Http\Controllers\VehicleSparePartsController;
use App\Http\Controllers\HardwareShopController;
use App\Http\Controllers\CommunicationController;
use App\Http\Controllers\CapitalAccountController;
use App\Http\Controllers\WarrantyController;
use App\Http\Controllers\ChequeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

include_once 'install_r.php';

Route::middleware(['setData'])->group(function () {
    Route::get('/', function () {
        // Guest landing is the login screen (demo shops live there)
        return redirect()->route('login');
    });

    Auth::routes();

    Route::post('/login/pin', [\App\Http\Controllers\Auth\LoginController::class, 'loginWithPin'])->name('login.pin');
    Route::post('/login/setup-pin', [\App\Http\Controllers\Auth\LoginController::class, 'setupLoginPin'])->name('login.setupPin');

    Route::get('/business/register', [BusinessController::class, 'getRegister'])->name('business.getRegister');
    Route::post('/business/register', [BusinessController::class, 'postRegister'])->name('business.postRegister');
    Route::post('/business/register/check-username', [BusinessController::class, 'postCheckUsername'])->name('business.postCheckUsername');
    Route::post('/business/register/check-email', [BusinessController::class, 'postCheckEmail'])->name('business.postCheckEmail');

    Route::get('/invoice/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_invoice');
    Route::get('/quote/{token}', [SellPosController::class, 'showInvoice'])
        ->name('show_quote');

    Route::get('/pay/{token}', [SellPosController::class, 'invoicePayment'])
        ->name('invoice_payment');
    Route::post('/confirm-payment/{id}', [SellPosController::class, 'confirmPayment'])
        ->name('confirm_payment');

    Route::get('/manifest.json', [BusinessController::class, 'pwaManifest'])
        ->name('pwa.manifest');
});

//Routes for authenticated users only
Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone', 'AdminSidebarMenu', 'CheckUserLogin', 'CheckFeatureAccess', 'CheckShopMode'])->group(function () {
    Route::get('pos/payment/{id}', [SellPosController::class, 'edit'])->name('edit-pos-payment');
    Route::get('service-staff-availability', [SellPosController::class, 'showServiceStaffAvailibility']);
    Route::get('pause-resume-service-staff-timer/{user_id}', [SellPosController::class, 'pauseResumeServiceStaffTimer']);
    Route::get('mark-as-available/{user_id}', [SellPosController::class, 'markAsAvailable']);

    Route::resource('purchase-requisition', PurchaseRequisitionController::class)->except(['edit', 'update']);
    Route::post('/get-requisition-products', [PurchaseRequisitionController::class, 'getRequisitionProducts'])->name('get-requisition-products');
    Route::get('get-purchase-requisitions/{location_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitions']);
    Route::get('get-purchase-requisition-lines/{purchase_requisition_id}', [PurchaseRequisitionController::class, 'getPurchaseRequisitionLines']);

    Route::get('/sign-in-as-user/{id}', [ManageUserController::class, 'signInAsUser'])->name('sign-in-as-user');

    // Cheque management
    Route::get('/cheque', [ChequeController::class, 'index'])->name('cheque.index');
    Route::post('/cheque', [ChequeController::class, 'store'])->name('cheque.store');
    Route::post('/cheque/{id}/status', [ChequeController::class, 'updateStatus'])->name('cheque.updateStatus');
    Route::post('/cheque/{id}/update', [ChequeController::class, 'update'])->name('cheque.update');
    Route::post('/cheque/{id}/remind', [ChequeController::class, 'remind'])->name('cheque.remind');
    Route::delete('/cheque/{id}', [ChequeController::class, 'destroy'])->name('cheque.destroy');
    Route::get('/cheque/stats', [ChequeController::class, 'dashboardWidget'])->name('cheque.stats');
    Route::get('/cheque/report', [ChequeController::class, 'report'])->name('cheque.report');
    Route::get('/cheque/{id}', [ChequeController::class, 'show'])->name('cheque.show');

    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/home/get-totals', [HomeController::class, 'getTotals']);
    Route::get('/home/product-stock-alert', [HomeController::class, 'getProductStockAlert']);
    Route::get('/home/product-stock-alert-list', [HomeController::class, 'getProductStockAlertList']);
    Route::get('/home/purchase-payment-dues', [HomeController::class, 'getPurchasePaymentDues']);
    Route::get('/home/sales-payment-dues', [HomeController::class, 'getSalesPaymentDues']);
    Route::post('/attach-medias-to-model', [HomeController::class, 'attachMediasToGivenModel'])->name('attach.medias.to.model');
    Route::get('/calendar', [HomeController::class, 'getCalendar'])->name('calendar');

    Route::post('/test-email', [BusinessController::class, 'testEmailConfiguration']);
    Route::post('/test-sms', [BusinessController::class, 'testSmsConfiguration']);
    Route::get('/business/settings', [BusinessController::class, 'getBusinessSettings'])->name('business.getBusinessSettings');
    Route::post('/business/update', [BusinessController::class, 'postBusinessSettings'])->name('business.postBusinessSettings');
    Route::post('/business/clear-cache', [BusinessController::class, 'clearAppCache'])->name('business.clearAppCache');
    Route::post('/business/settings-visibility', [BusinessController::class, 'postSettingsMenuVisibility'])->name('business.postSettingsMenuVisibility');
    Route::post('/business/shop-mode', [BusinessController::class, 'postShopMode'])->name('business.postShopMode');
    Route::post('/business/login-background', [BusinessController::class, 'postLoginBackground'])->name('business.postLoginBackground');
    Route::post('/ic-mode/toggle', [BusinessController::class, 'toggleIcMode'])->name('ic_mode.toggle');
    Route::get('/user/profile', [UserController::class, 'getProfile'])->name('user.getProfile');
    Route::post('/user/update', [UserController::class, 'updateProfile'])->name('user.updateProfile');
    Route::post('/user/update-password', [UserController::class, 'updatePassword'])->name('user.updatePassword');
    Route::post('/user/update-login-pin', [UserController::class, 'updateLoginPin'])->name('user.updateLoginPin');

    Route::resource('brands', BrandController::class);

    Route::resource('payment-account', 'PaymentAccountController');

    Route::resource('tax-rates', TaxRateController::class);

    Route::resource('units', UnitController::class);

    Route::resource('ledger-discount', LedgerDiscountController::class)->only('edit', 'destroy', 'store', 'update');

    Route::post('check-mobile', [ContactController::class, 'checkMobile']);
    Route::get('/get-contact-due/{contact_id}', [ContactController::class, 'getContactDue']);
    Route::get('/contacts/payments/{contact_id}', [ContactController::class, 'getContactPayments']);
    Route::get('/contacts/map', [ContactController::class, 'contactMap']);
    Route::get('/contacts/update-status/{id}', [ContactController::class, 'updateStatus']);
    Route::get('/contacts/stock-report/{supplier_id}', [ContactController::class, 'getSupplierStockReport']);
    Route::get('/contacts/ledger', [ContactController::class, 'getLedger']);
    Route::post('/contacts/send-ledger', [ContactController::class, 'sendLedger']);
    Route::get('/contacts/import', [ContactController::class, 'getImportContacts'])->name('contacts.import');
    Route::post('/contacts/import', [ContactController::class, 'postImportContacts']);
    Route::post('/contacts/check-contacts-id', [ContactController::class, 'checkContactId']);

    Route::post('/contacts/check-tax-number', [ContactController::class, 'checkTaxNumber']);

    Route::get('/contacts/customers', [ContactController::class, 'getCustomers']);
    Route::get('/contacts/{id}/vehicles', [ContactController::class, 'manageVehicles']);
    Route::post('/contacts/{id}/vehicles', [ContactController::class, 'storeVehicle']);
    Route::post('/contacts/{contact_id}/vehicles/{vehicle_id}', [ContactController::class, 'updateVehicle']);
    Route::delete('/contacts/{contact_id}/vehicles/{vehicle_id}', [ContactController::class, 'destroyVehicle']);
    Route::resource('contacts', ContactController::class);

    Route::get('taxonomies-ajax-index-page', [TaxonomyController::class, 'getTaxonomyIndexPage']);
    Route::resource('taxonomies', TaxonomyController::class);

    Route::resource('variation-templates', VariationTemplateController::class);

    Route::get('/products/download-excel', [ProductController::class, 'downloadExcel']);

    Route::get('/products/stock-history/{id}', [ProductController::class, 'productStockHistory']);
    Route::get('/delete-media/{media_id}', [ProductController::class, 'deleteMedia']);
    Route::post('/products/mass-deactivate', [ProductController::class, 'massDeactivate']);
    Route::get('/products/activate/{id}', [ProductController::class, 'activate']);
    Route::get('/products/view-product-group-price/{id}', [ProductController::class, 'viewGroupPrice']);
    Route::get('/products/add-selling-prices/{id}', [ProductController::class, 'addSellingPrices']);
    Route::post('/products/save-selling-prices', [ProductController::class, 'saveSellingPrices']);
    Route::post('/products/mass-delete', [ProductController::class, 'massDestroy']);
    Route::get('/products/view/{id}', [ProductController::class, 'view']);
    Route::get('/products/list', [ProductController::class, 'getProducts']);
    Route::get('/products/list-no-variation', [ProductController::class, 'getProductsWithoutVariations']);
    Route::post('/products/bulk-edit', [ProductController::class, 'bulkEdit']);
    Route::post('/products/bulk-update', [ProductController::class, 'bulkUpdate']);
    Route::post('/products/bulk-update-location', [ProductController::class, 'updateProductLocation']);
    Route::get('/products/get-product-to-edit/{product_id}', [ProductController::class, 'getProductToEdit']);

    Route::post('/products/get_sub_categories', [ProductController::class, 'getSubCategories']);
    Route::get('/products/get_sub_units', [ProductController::class, 'getSubUnits']);
    Route::post('/products/product_form_part', [ProductController::class, 'getProductVariationFormPart']);
    Route::post('/products/get_product_variation_row', [ProductController::class, 'getProductVariationRow']);
    Route::post('/products/get_variation_template', [ProductController::class, 'getVariationTemplate']);
    Route::get('/products/get_variation_value_row', [ProductController::class, 'getVariationValueRow']);
    Route::post('/products/check_product_sku', [ProductController::class, 'checkProductSku']);
    Route::post('/products/check_product_name', [ProductController::class, 'checkProductName']);
    Route::post('/products/validate_variation_skus', [ProductController::class, 'validateVaritionSkus']); //validates multiple skus at once
    Route::get('/products/quick_add', [ProductController::class, 'quickAdd']);
    Route::post('/products/save_quick_product', [ProductController::class, 'saveQuickProduct']);
    Route::get('/products/get-combo-product-entry-row', [ProductController::class, 'getComboProductEntryRow']);
    Route::post('/products/toggle-woocommerce-sync', [ProductController::class, 'toggleWooCommerceSync']);
    Route::post('/products/regenerate-cost-codes', [ProductController::class, 'regenerateCostCodes']);

    Route::resource('products', ProductController::class);
    Route::get('/toggle-subscription/{id}', 'SellPosController@toggleRecurringInvoices');
    Route::post('/sells/pos/get-types-of-service-details', 'SellPosController@getTypesOfServiceDetails');
    Route::get('/sells/subscriptions', 'SellPosController@listSubscriptions');
    Route::get('/sells/duplicate/{id}', 'SellController@duplicateSell');
    Route::get('/sells/drafts', 'SellController@getDrafts');
    Route::get('/sells/convert-to-draft/{id}', 'SellPosController@convertToInvoice');
    Route::get('/sells/convert-to-proforma/{id}', 'SellPosController@convertToProforma');
    Route::get('/sells/quotations', 'SellController@getQuotations');
    Route::get('/sells/draft-dt', 'SellController@getDraftDatables');
    Route::resource('sells', 'SellController')->except(['show']);
    Route::get('/sells/copy-quotation/{id}', [SellPosController::class, 'copyQuotation']);

    Route::post('/import-purchase-products', [PurchaseController::class, 'importPurchaseProducts']);
    Route::post('/purchases/update-status', [PurchaseController::class, 'updateStatus']);
    Route::get('/purchases/get_products', [PurchaseController::class, 'getProducts']);
    Route::get('/purchases/bulk-product-search', [PurchaseController::class, 'bulkProductSearch']);
    Route::get('/purchases/product-details', [PurchaseController::class, 'purchaseProductDetails']);
    Route::get('/purchases/get_suppliers', [PurchaseController::class, 'getSuppliers'])->middleware('auth');
    Route::post('/purchases/get_purchase_entry_row', [PurchaseController::class, 'getPurchaseEntryRow']);
    Route::get('/purchases/get_variation_stock_prices', [PurchaseController::class, 'getVariationStockPrices']);
    Route::post('/purchases/check_ref_number', [PurchaseController::class, 'checkRefNumber']);
    Route::resource('purchases', PurchaseController::class)->except(['show']);

    // Nutmeg: separate Product Expenses area (select purchase invoice, optional supplier filter)
    Route::get('/product-expenses', [ProductExpenseController::class, 'index']);
    Route::get('/product-expenses/create', [ProductExpenseController::class, 'create']);
    Route::post('/product-expenses', [ProductExpenseController::class, 'store']);
    Route::get('/product-expenses/invoices', [ProductExpenseController::class, 'getInvoices']);
    Route::get('/product-expenses/lines/{id}', [ProductExpenseController::class, 'getLines']);
    Route::get('/product-expenses/{id}', [ProductExpenseController::class, 'show']);

    // Nutmeg — drying + bags settings + reports
    Route::get('/nutmeg/settings', [NutmegSettingsController::class, 'settings']);
    Route::post('/nutmeg/settings', [NutmegSettingsController::class, 'updateSettings']);
    Route::get('/nutmeg/pos-bags', [NutmegSettingsController::class, 'posBags']);
    Route::get('/nutmeg/drying', [NutmegDryingController::class, 'index']);
    Route::get('/nutmeg/drying/products', [NutmegDryingController::class, 'products']);
    Route::get('/nutmeg/drying/preview', [NutmegDryingController::class, 'preview']);
    Route::post('/nutmeg/drying', [NutmegDryingController::class, 'store']);
    Route::get('/nutmeg/drying/history', [NutmegDryingController::class, 'history']);
    Route::get('/nutmeg/reports', [NutmegReportController::class, 'index']);
    Route::get('/nutmeg/reports/avg-cost', [NutmegReportController::class, 'avgCostByProduct']);
    Route::get('/nutmeg/reports/drying', [NutmegReportController::class, 'dryingReport']);
    Route::get('/nutmeg/reports/drying/data', [NutmegReportController::class, 'dryingReportData']);
    Route::get('/nutmeg/reports/wastage', [NutmegReportController::class, 'wastageReport']);
    Route::get('/nutmeg/reports/wastage/data', [NutmegReportController::class, 'wastageReportData']);
    Route::get('/nutmeg/reports/wastage/by-product', [NutmegReportController::class, 'wastageByProduct']);
    Route::get('/nutmeg/reports/expenses/data', [NutmegReportController::class, 'expenseReportData']);
    Route::get('/nutmeg/reports/summary', [NutmegReportController::class, 'reportSummary']);

    Route::get('/toggle-subscription/{id}', [SellPosController::class, 'toggleRecurringInvoices']);
    Route::post('/sells/pos/get-types-of-service-details', [SellPosController::class, 'getTypesOfServiceDetails']);
    Route::get('/sells/subscriptions', [SellPosController::class, 'listSubscriptions']);
    Route::get('/sells/duplicate/{id}', [SellController::class, 'duplicateSell']);
    Route::get('/sells/drafts', [SellController::class, 'getDrafts']);
    Route::get('/sells/convert-to-draft/{id}', [SellPosController::class, 'convertToInvoice']);
    Route::get('/sells/convert-to-proforma/{id}', [SellPosController::class, 'convertToProforma']);
    Route::get('/sells/quotations', [SellController::class, 'getQuotations']);
    Route::get('/sells/draft-dt', [SellController::class, 'getDraftDatables']);
    Route::resource('sells', SellController::class)->except(['show']);

    Route::get('/import-sales', [ImportSalesController::class, 'index']);
    Route::post('/import-sales/preview', [ImportSalesController::class, 'preview']);
    Route::post('/import-sales', [ImportSalesController::class, 'import']);
    Route::get('/revert-sale-import/{batch}', [ImportSalesController::class, 'revertSaleImport']);

    Route::get('/sells/pos/get_product_row/{variation_id}/{location_id}', [SellPosController::class, 'getProductRow']);
    Route::post('/sells/pos/get_payment_row', [SellPosController::class, 'getPaymentRow']);
    Route::post('/sells/pos/get-reward-details', [SellPosController::class, 'getRewardDetails']);
    Route::get('/sells/pos/customer-sales-history', [SellPosController::class, 'getCustomerSalesHistory']);
    Route::post('/sells/pos/loyalty/lookup', [SellPosController::class, 'loyaltyLookup']);
    Route::post('/sells/pos/loyalty/quick-create', [SellPosController::class, 'loyaltyQuickCreate']);
    Route::post('/sells/pos/loyalty/send-otp', [SellPosController::class, 'loyaltySendOtp']);
    Route::post('/sells/pos/loyalty/verify-otp', [SellPosController::class, 'loyaltyVerifyOtp']);
    Route::get('/sells/pos/get-recent-transactions', [SellPosController::class, 'getRecentTransactions']);
    Route::get('/sells/pos/get-product-suggestion', [SellPosController::class, 'getProductSuggestion']);
    Route::get('/sells/pos/bulk-product-search', [SellPosController::class, 'bulkProductSearch']);
    Route::get('/sells/pos/tyre-shop-services', [SellPosController::class, 'getTyreShopServices']);
    Route::get('/sells/pos/trade-in-options', [SellPosController::class, 'getTradeInOptions']);
    Route::get('/sells/pos/get-featured-products/{location_id}', [SellPosController::class, 'getFeaturedProducts']);
    Route::get('/reset-mapping', [SellController::class, 'resetMapping']);
    // pos display screen route
    Route::get('/customer-display', [SellPosController::class, 'posDisplay'])->name('pos_display');
    Route::post('/pos/display/link-customer', [SellPosController::class, 'linkCustomerFromDisplay'])->name('pos.display.linkCustomer');
    Route::get('/pos/display/pending-customer', [SellPosController::class, 'getPendingDisplayCustomer'])->name('pos.display.pendingCustomer');
    Route::post('/pos/display/clear-customer', [SellPosController::class, 'clearDisplayCustomer'])->name('pos.display.clearCustomer');

    Route::get('/pos/variations/bulk', [\App\Http\Controllers\ProductController::class, 'getVariationDetailsBulk']);
    // end pos display screen route
    Route::resource('pos', SellPosController::class);

    // Point of Purchase (POP) — POS-like purchase UI
    Route::get('/purchase-pos/get_product_row/{variation_id}/{location_id}', [PurchasePosController::class, 'getProductRow']);
    Route::get('/purchase-pos/contacts', [PurchasePosController::class, 'getContacts']);
    Route::get('/purchase-pos/get-recent-purchases', [PurchasePosController::class, 'getRecentPurchases']);
    Route::get('/purchase-pos/{transaction_id}/print', [PurchasePosController::class, 'printInvoice']);
    Route::get('/purchase-pos/create', [PurchasePosController::class, 'create'])->name('purchase-pos.create');
    Route::post('/purchase-pos', [PurchasePosController::class, 'store'])->name('purchase-pos.store');

    Route::resource('roles', RoleController::class);

    Route::resource('users', ManageUserController::class);

    Route::resource('group-taxes', GroupTaxController::class);

    Route::get('/barcodes/set_default/{id}', [BarcodeController::class, 'setDefault']);
    Route::resource('barcodes', BarcodeController::class);

    //Invoice schemes..
    Route::get('/invoice-schemes/set_default/{id}', [InvoiceSchemeController::class, 'setDefault']);
    Route::resource('invoice-schemes', InvoiceSchemeController::class);

    //Print Labels
    Route::get('/labels/show', [LabelsController::class, 'show']);
    Route::get('/labels/search-products', [LabelsController::class, 'searchProducts']);
    Route::get('/labels/add-product-row', [LabelsController::class, 'addProductRow']);
    Route::get('/labels/preview', [LabelsController::class, 'preview']);
    Route::get('/labels/preview-weighing-scale', [LabelsController::class, 'previewWeighingScale']);
    Route::match(['get', 'post'], '/labels/browser-print', [LabelsController::class, 'browserPrint']);
    Route::post('/labels/browser-print/prepare', [LabelsController::class, 'prepareBrowserPrint']);
    Route::post('/labels/browser-print/layout', [LabelsController::class, 'saveBrowserPrintLayout']);
    Route::post('/labels/browser-print/barcode', [LabelsController::class, 'browserPrintBarcode']);

    //Reports...
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.hub');
    Route::get('/reports/gst-purchase-report', [ReportController::class, 'gstPurchaseReport']);
    Route::get('/reports/gst-sales-report', [ReportController::class, 'gstSalesReport']);
    Route::get('/reports/get-stock-by-sell-price', [ReportController::class, 'getStockBySellingPrice']);
    Route::get('/reports/purchase-report', [ReportController::class, 'purchaseReport']);
    Route::get('/reports/sale-report', [ReportController::class, 'saleReport']);
    Route::get('/reports/service-staff-report', [ReportController::class, 'getServiceStaffReport']);
    Route::get('/reports/service-staff-line-orders', [ReportController::class, 'serviceStaffLineOrders']);
    Route::get('/reports/table-report', [ReportController::class, 'getTableReport']);
    Route::get('/reports/profit-loss', [ReportController::class, 'getProfitLoss']);
    Route::get('/reports/get-opening-stock', [ReportController::class, 'getOpeningStock']);
    Route::get('/reports/purchase-sell', [ReportController::class, 'getPurchaseSell']);
    Route::get('/reports/customer-supplier', [ReportController::class, 'getCustomerSuppliers']);
    Route::get('/reports/stock-report', [ReportController::class, 'getStockReport']);
    Route::get('/reports/stock-details', [ReportController::class, 'getStockDetails']);
    Route::get('/reports/tax-report', [ReportController::class, 'getTaxReport']);
    Route::get('/reports/tax-details', [ReportController::class, 'getTaxDetails']);
    Route::get('/reports/trending-products', [ReportController::class, 'getTrendingProducts']);
    Route::get('/reports/expense-report', [ReportController::class, 'getExpenseReport']);
    Route::get('/reports/stock-adjustment-report', [ReportController::class, 'getStockAdjustmentReport']);
    Route::get('/reports/register-report', [ReportController::class, 'getRegisterReport']);
    Route::get('/reports/sales-representative-report', [ReportController::class, 'getSalesRepresentativeReport']);
    Route::get('/reports/sales-representative-total-expense', [ReportController::class, 'getSalesRepresentativeTotalExpense']);
    Route::get('/reports/sales-representative-total-sell', [ReportController::class, 'getSalesRepresentativeTotalSell']);
    Route::get('/reports/sales-representative-total-commission', [ReportController::class, 'getSalesRepresentativeTotalCommission']);
    Route::get('/reports/stock-expiry', [ReportController::class, 'getStockExpiryReport']);
    Route::get('/reports/stock-expiry-edit-modal/{purchase_line_id}', [ReportController::class, 'getStockExpiryReportEditModal']);
    Route::post('/reports/stock-expiry-update', [ReportController::class, 'updateStockExpiryReport'])->name('updateStockExpiryReport');
    Route::get('/reports/customer-group', [ReportController::class, 'getCustomerGroup']);
    Route::get('/reports/loyalty-report', [ReportController::class, 'getLoyaltyReport']);
    Route::get('/reports/product-purchase-report', [ReportController::class, 'getproductPurchaseReport']);
    Route::get('/reports/product-sell-grouped-by', [ReportController::class, 'productSellReportBy']);
    Route::get('/reports/product-sell-report', [ReportController::class, 'getproductSellReport']);
    Route::get('/reports/product-sell-report-with-purchase', [ReportController::class, 'getproductSellReportWithPurchase']);
    Route::get('/reports/product-sell-grouped-report', [ReportController::class, 'getproductSellGroupedReport']);
    Route::get('/reports/lot-report', [ReportController::class, 'getLotReport']);
    Route::get('/reports/purchase-payment-report', [ReportController::class, 'purchasePaymentReport']);
    Route::get('/reports/sell-payment-report', [ReportController::class, 'sellPaymentReport']);
    Route::get('/reports/product-stock-details', [ReportController::class, 'productStockDetails']);
    Route::get('/reports/adjust-product-stock', [ReportController::class, 'adjustProductStock']);
    Route::get('/reports/get-profit/{by?}', [ReportController::class, 'getProfit']);
    Route::get('/reports/items-report', [ReportController::class, 'itemsReport']);
    Route::get('/reports/get-stock-value', [ReportController::class, 'getStockValue']);

    Route::get('business-location/activate-deactivate/{location_id}', [BusinessLocationController::class, 'activateDeactivateLocation']);

    //Business Location Settings...
    Route::prefix('business-location/{location_id}')->name('location.')->group(function () {
        Route::get('settings', [LocationSettingsController::class, 'index'])->name('settings');
        Route::post('settings', [LocationSettingsController::class, 'updateSettings'])->name('settings_update');
    });

    //Business Locations...
    Route::post('business-location/check-location-id', [BusinessLocationController::class, 'checkLocationId']);
    Route::resource('business-location', BusinessLocationController::class);

    //Invoice layouts..
    Route::match(['post', 'put'], 'invoice-layouts/live-preview', [InvoiceLayoutController::class, 'livePreview'])->name('invoice-layouts.live-preview');
    Route::resource('invoice-layouts', InvoiceLayoutController::class);

    Route::post('get-expense-sub-categories', [ExpenseCategoryController::class, 'getSubCategories']);

    //Expense Categories...
    Route::resource('expense-categories', ExpenseCategoryController::class);

    //Expenses...
    Route::resource('expenses', ExpenseController::class);
    Route::get('import-expense', [ExpenseController::class, 'importExpense']);
    Route::post('store-import-expense', [ExpenseController::class, 'storeExpenseImport']);

    //Transaction payments...
    // Route::get('/payments/opening-balance/{contact_id}', 'TransactionPaymentController@getOpeningBalancePayments');
    Route::get('/payments/show-child-payments/{payment_id}', [TransactionPaymentController::class, 'showChildPayments']);
    Route::get('/payments/view-payment/{payment_id}', [TransactionPaymentController::class, 'viewPayment']);
    Route::get('/payments/add_payment/{transaction_id}', [TransactionPaymentController::class, 'addPayment']);
    Route::get('/payments/pay-contact-due/{contact_id}', [TransactionPaymentController::class, 'getPayContactDue']);
    Route::post('/payments/pay-contact-due', [TransactionPaymentController::class, 'postPayContactDue']);
    Route::resource('payments', TransactionPaymentController::class);

    //Printers...
    Route::resource('printers', PrinterController::class);

    Route::get('/stock-adjustments/remove-expired-stock/{purchase_line_id}', [StockAdjustmentController::class, 'removeExpiredStock']);
    Route::post('/stock-adjustments/get_product_row', [StockAdjustmentController::class, 'getProductRow']);
    Route::resource('stock-adjustments', StockAdjustmentController::class);

    Route::get('/cash-register/register-details', [CashRegisterController::class, 'getRegisterDetails']);
    Route::get('/cash-register/close-register/{id?}', [CashRegisterController::class, 'getCloseRegister']);
    Route::post('/cash-register/close-register', [CashRegisterController::class, 'postCloseRegister']);
    Route::resource('cash-register', CashRegisterController::class);

    //Import products
    Route::get('/import-products', [ImportProductsController::class, 'index']);
    Route::post('/import-products/store', [ImportProductsController::class, 'store']);

    //Sales Commission Agent
    Route::resource('sales-commission-agents', SalesCommissionAgentController::class);

    //Stock Transfer
    Route::get('stock-transfers/print/{id}', [StockTransferController::class, 'printInvoice']);
    Route::post('stock-transfers/update-status/{id}', [StockTransferController::class, 'updateStatus']);
    Route::resource('stock-transfers', StockTransferController::class);

    Route::get('/opening-stock/add/{product_id}', [OpeningStockController::class, 'add']);
    Route::post('/opening-stock/save', [OpeningStockController::class, 'save']);

    //Customer Groups
    Route::resource('customer-group', CustomerGroupController::class);

    //Import opening stock
    Route::get('/import-opening-stock', [ImportOpeningStockController::class, 'index']);
    Route::post('/import-opening-stock/store', [ImportOpeningStockController::class, 'store']);

    //Sell return
    Route::get('sell-return/standalone/create', [SellReturnController::class, 'standaloneCreate']);
    Route::post('sell-return/standalone', [SellReturnController::class, 'storeStandalone']);
    Route::get('sell-return/standalone/lookup-invoice', [SellReturnController::class, 'lookupInvoiceForStandalone']);
    Route::post('sell-return/validate-credit-note', [SellReturnController::class, 'validateCreditNote']);
    Route::get('validate-invoice-to-return/{invoice_no}', [SellReturnController::class, 'validateInvoiceToReturn']);
    // service staff replacement
    Route::get('validate-invoice-to-service-staff-replacement/{invoice_no}', [SellPosController::class, 'validateInvoiceToServiceStaffReplacement']);
    Route::put('change-service-staff/{id}', [SellPosController::class, 'change_service_staff'])->name('change_service_staff');

    Route::resource('sell-return', SellReturnController::class);
    Route::get('sell-return/get-product-row', [SellReturnController::class, 'getProductRow']);
    Route::get('/sell-return/print/{id}', [SellReturnController::class, 'printInvoice']);
    Route::get('/sell-return/add/{id}', [SellReturnController::class, 'add']);

    //Backup
    Route::get('backup/download/{file_name}', [BackUpController::class, 'download']);
    Route::get('backup/{id}/delete', [BackUpController::class, 'delete'])->name('delete_backup');
    Route::resource('backup', BackUpController::class)->only('index', 'create', 'store');

    Route::get('selling-price-group/activate-deactivate/{id}', [SellingPriceGroupController::class, 'activateDeactivate']);
    Route::get('update-product-price', [SellingPriceGroupController::class, 'updateProductPrice'])->name('update-product-price');
    Route::get('/products/stock-prices', [\App\Http\Controllers\VariationStockPriceController::class, 'index']);
    Route::post('/products/stock-prices', [\App\Http\Controllers\VariationStockPriceController::class, 'store']);
    Route::delete('/products/stock-prices/{id}', [\App\Http\Controllers\VariationStockPriceController::class, 'destroy']);
    Route::get('export-product-price', [SellingPriceGroupController::class, 'export']);
    Route::post('import-product-price', [SellingPriceGroupController::class, 'import']);

    Route::resource('selling-price-group', SellingPriceGroupController::class);

    Route::resource('notification-templates', NotificationTemplateController::class)->only(['index', 'store']);
    Route::get('notification/get-template/{transaction_id}/{template_for}', [NotificationController::class, 'getTemplate']);
    Route::post('notification/send', [NotificationController::class, 'send']);

    Route::post('/purchase-return/update', [CombinedPurchaseReturnController::class, 'update']);
    Route::get('/purchase-return/edit/{id}', [CombinedPurchaseReturnController::class, 'edit']);
    Route::post('/purchase-return/save', [CombinedPurchaseReturnController::class, 'save']);
    Route::post('/purchase-return/get_product_row', [CombinedPurchaseReturnController::class, 'getProductRow']);
    Route::get('/purchase-return/create', [CombinedPurchaseReturnController::class, 'create']);
    Route::get('/purchase-return/add/{id}', [PurchaseReturnController::class, 'add']);
    Route::resource('/purchase-return', PurchaseReturnController::class)->except('create');

    Route::get('/discount/activate/{id}', [DiscountController::class, 'activate']);
    Route::post('/discount/mass-deactivate', [DiscountController::class, 'massDeactivate']);
    Route::resource('discount', DiscountController::class);

    Route::prefix('account')->group(function () {
        Route::resource('/account', AccountController::class);
        Route::get('/fund-transfer/{id}', [AccountController::class, 'getFundTransfer']);
        Route::post('/fund-transfer', [AccountController::class, 'postFundTransfer']);
        Route::get('/deposit/{id}', [AccountController::class, 'getDeposit']);
        Route::post('/deposit', [AccountController::class, 'postDeposit']);
        Route::get('/close/{id}', [AccountController::class, 'close']);
        Route::get('/activate/{id}', [AccountController::class, 'activate']);
        Route::get('/delete-account-transaction/{id}', [AccountController::class, 'destroyAccountTransaction']);
        Route::get('/edit-account-transaction/{id}', [AccountController::class, 'editAccountTransaction']);
        Route::post('/update-account-transaction/{id}', [AccountController::class, 'updateAccountTransaction']);
        Route::get('/get-account-balance/{id}', [AccountController::class, 'getAccountBalance']);
        Route::get('/balance-sheet', [AccountReportsController::class, 'balanceSheet']);
        Route::get('/trial-balance', [AccountReportsController::class, 'trialBalance']);
        Route::get('/payment-account-report', [AccountReportsController::class, 'paymentAccountReport']);
        Route::get('/link-account/{id}', [AccountReportsController::class, 'getLinkAccount']);
        Route::post('/link-account', [AccountReportsController::class, 'postLinkAccount']);
        Route::get('/cash-flow', [AccountController::class, 'cashFlow']);
    });

    Route::resource('account-types', AccountTypeController::class);

    // Capital Accounts (partnership investment)
    Route::prefix('capital-account')->group(function () {
        Route::get('/', [CapitalAccountController::class, 'index']);
        Route::get('/partners', [CapitalAccountController::class, 'partners']);
        Route::get('/partners/create', [CapitalAccountController::class, 'createPartner']);
        Route::post('/partners', [CapitalAccountController::class, 'storePartner']);
        Route::get('/partners/{id}/edit', [CapitalAccountController::class, 'editPartner']);
        Route::put('/partners/{id}', [CapitalAccountController::class, 'updatePartner']);
        Route::delete('/partners/{id}', [CapitalAccountController::class, 'destroyPartner']);
        Route::get('/entries', [CapitalAccountController::class, 'entries']);
        Route::get('/entries/create', [CapitalAccountController::class, 'createEntry']);
        Route::post('/entries', [CapitalAccountController::class, 'storeEntry']);
        Route::get('/entries/{id}/edit', [CapitalAccountController::class, 'editEntry']);
        Route::put('/entries/{id}', [CapitalAccountController::class, 'updateEntry']);
        Route::delete('/entries/{id}', [CapitalAccountController::class, 'destroyEntry']);
        Route::get('/settings', [CapitalAccountController::class, 'settings']);
        Route::post('/settings', [CapitalAccountController::class, 'updateSettings']);
    });

    //Restaurant module
    Route::prefix('modules')->group(function () {
        Route::resource('tables', Restaurant\TableController::class);
        Route::resource('modifiers', Restaurant\ModifierSetsController::class);

        //Map modifier to products
        Route::get('/product-modifiers/{id}/edit', [Restaurant\ProductModifierSetController::class, 'edit']);
        Route::post('/product-modifiers/{id}/update', [Restaurant\ProductModifierSetController::class, 'update']);
        Route::get('/product-modifiers/product-row/{product_id}', [Restaurant\ProductModifierSetController::class, 'product_row']);

        Route::get('/add-selected-modifiers', [Restaurant\ProductModifierSetController::class, 'add_selected_modifiers']);

        Route::get('/kitchen', [Restaurant\KitchenController::class, 'index']);
        Route::get('/kitchen/mark-as-cooked/{id}', [Restaurant\KitchenController::class, 'markAsCooked']);
        Route::post('/refresh-orders-list', [Restaurant\KitchenController::class, 'refreshOrdersList']);
        Route::post('/refresh-line-orders-list', [Restaurant\KitchenController::class, 'refreshLineOrdersList']);

        Route::get('/orders', [Restaurant\OrderController::class, 'index']);
        Route::get('/orders/mark-as-served/{id}', [Restaurant\OrderController::class, 'markAsServed']);
        Route::get('/data/get-pos-details', [Restaurant\DataController::class, 'getPosDetails']);
        Route::get('/data/check-staff-pin', [Restaurant\DataController::class, 'checkStaffPin']);
        Route::get('/orders/mark-line-order-as-served/{id}', [Restaurant\OrderController::class, 'markLineOrderAsServed']);
        Route::get('/print-line-order', [Restaurant\OrderController::class, 'printLineOrder']);
    });

    Route::get('bookings/get-todays-bookings', [Restaurant\BookingController::class, 'getTodaysBookings']);
    Route::resource('bookings', Restaurant\BookingController::class);

    Route::resource('types-of-service', TypesOfServiceController::class);
    Route::get('sells/edit-shipping/{id}', [SellController::class, 'editShipping']);
    Route::put('sells/update-shipping/{id}', [SellController::class, 'updateShipping']);
    Route::get('shipments', [SellController::class, 'shipments']);

    Route::post('upload-module', [Install\ModulesController::class, 'uploadModule']);
    Route::delete('manage-modules/destroy/{module_name}', [Install\ModulesController::class, 'destroy']);
    Route::resource('manage-modules', Install\ModulesController::class)
        ->only(['index', 'update']);
    Route::get('regenerate', [Install\ModulesController::class, 'regenerate']);

    Route::resource('warranties', WarrantyController::class);

    Route::prefix('communication')->group(function () {
        Route::get('/settings', [CommunicationController::class, 'settings']);
        Route::post('/settings', [CommunicationController::class, 'updateSettings']);
        Route::get('/pos-options', [CommunicationController::class, 'posOptions']);
    });

    Route::prefix('mobile-shop')->group(function () {
        Route::get('/', [MobileShopController::class, 'index']);
        Route::get('/imei', [MobileShopController::class, 'imeiIndex']);
        Route::get('/imei/create', [MobileShopController::class, 'imeiCreate']);
        Route::post('/imei', [MobileShopController::class, 'imeiStore']);
        Route::get('/imei/{id}/edit', [MobileShopController::class, 'imeiEdit']);
        Route::post('/imei/{id}', [MobileShopController::class, 'imeiUpdate']);
        Route::get('/available-imeis', [MobileShopController::class, 'availableImeis']);
        Route::get('/warranty', [MobileShopController::class, 'warrantyLookup']);
        Route::post('/warranty/assign', [MobileShopController::class, 'warrantyAssign']);
        Route::get('/repairs', [MobileShopController::class, 'repairIndex']);
        Route::get('/repairs/create', [MobileShopController::class, 'repairCreate']);
        Route::post('/repairs', [MobileShopController::class, 'repairStore']);
        Route::get('/repairs/{id}/edit', [MobileShopController::class, 'repairEdit']);
        Route::post('/repairs/{id}', [MobileShopController::class, 'repairUpdate']);
        Route::get('/repairs/pending-payment', [MobileShopController::class, 'repairPendingPayments']);
        Route::get('/repairs/{id}/pos-checkout', [MobileShopController::class, 'repairPosCheckout']);
        Route::get('/repairs/{id}/collect-payment', [MobileShopController::class, 'repairCollectPayment'])->name('mobile-shop.repair.collect');
        Route::get('/reload', [MobileShopController::class, 'reloadIndex']);
        Route::get('/bill-history', [MobileShopController::class, 'billHistory']);
        Route::post('/reload', [MobileShopController::class, 'reloadStore']);
    });

    Route::prefix('tyre-shop')->group(function () {
        Route::get('/', [TyreShopController::class, 'index']);
    });

    Route::prefix('vehicle-modification')->group(function () {
        Route::get('/', [VehicleModificationController::class, 'index']);
        Route::get('/vehicles', [VehicleModificationController::class, 'vehicleIndex']);
        Route::get('/vehicles/create', [VehicleModificationController::class, 'vehicleCreate']);
        Route::post('/vehicles', [VehicleModificationController::class, 'vehicleStore']);
        Route::get('/vehicles/{id}/edit', [VehicleModificationController::class, 'vehicleEdit']);
        Route::post('/vehicles/{id}', [VehicleModificationController::class, 'vehicleUpdate']);
        Route::get('/vehicles-for-customer', [VehicleModificationController::class, 'vehiclesForCustomer']);
        Route::get('/jobs', [VehicleModificationController::class, 'jobIndex']);
        Route::get('/jobs/create', [VehicleModificationController::class, 'jobCreate']);
        Route::post('/jobs', [VehicleModificationController::class, 'jobStore']);
        Route::get('/jobs/{id}/edit', [VehicleModificationController::class, 'jobEdit']);
        Route::post('/jobs/{id}', [VehicleModificationController::class, 'jobUpdate']);
        Route::get('/jobs/{id}/collect-payment', [VehicleModificationController::class, 'jobCollectPayment']);
        Route::get('/pos-services', [VehicleModificationController::class, 'posServices']);
        Route::get('/history/search', [VehicleModificationController::class, 'historySearch']);
        Route::get('/history/vehicle/{id}', [VehicleModificationController::class, 'historyVehicle']);
        Route::get('/history/job/{id}', [VehicleModificationController::class, 'historyJob']);
    });

    Route::prefix('vehicle-spare-parts')->group(function () {
        Route::get('/', [VehicleSparePartsController::class, 'index']);
    });

    Route::prefix('hardware-shop')->group(function () {
        Route::get('/', [HardwareShopController::class, 'index']);
    });

    // Standalone Reload module routes (same controller, separate URL)
    Route::prefix('reload')->group(function () {
        Route::get('/', [MobileShopController::class, 'reloadIndex']);
        Route::get('/bill-history', [MobileShopController::class, 'billHistory']);
        Route::post('/', [MobileShopController::class, 'reloadStore']);
    });

    Route::resource('dashboard-configurator', DashboardConfiguratorController::class)
    ->only(['edit', 'update']);

    Route::get('view-media/{model_id}', [SellController::class, 'viewMedia']);

    //common controller for document & note
    Route::get('get-document-note-page', [DocumentAndNoteController::class, 'getDocAndNoteIndexPage']);
    Route::post('post-document-upload', [DocumentAndNoteController::class, 'postMedia']);
    Route::resource('note-documents', DocumentAndNoteController::class);
    Route::resource('purchase-order', PurchaseOrderController::class);
    Route::get('get-purchase-orders/{contact_id}', [PurchaseOrderController::class, 'getPurchaseOrders']);
    Route::get('get-purchase-order-lines/{purchase_order_id}', [PurchaseController::class, 'getPurchaseOrderLines']);
    Route::get('edit-purchase-orders/{id}/status', [PurchaseOrderController::class, 'getEditPurchaseOrderStatus']);
    Route::put('update-purchase-orders/{id}/status', [PurchaseOrderController::class, 'postEditPurchaseOrderStatus']);
    Route::resource('sales-order', SalesOrderController::class)->only(['index']);
    Route::get('get-sales-orders/{customer_id}', [SalesOrderController::class, 'getSalesOrders']);
    Route::get('get-sales-order-lines', [SellPosController::class, 'getSalesOrderLines']);
    Route::get('edit-sales-orders/{id}/status', [SalesOrderController::class, 'getEditSalesOrderStatus']);
    Route::put('update-sales-orders/{id}/status', [SalesOrderController::class, 'postEditSalesOrderStatus']);
    Route::get('reports/activity-log', [ReportController::class, 'activityLog']);
    Route::get('user-location/{latlng}', [HomeController::class, 'getUserLocation']);
});

// Route::middleware(['EcomApi'])->prefix('api/ecom')->group(function () {
//     Route::get('products/{id?}', [ProductController::class, 'getProductsApi']);
//     Route::get('categories', [CategoryController::class, 'getCategoriesApi']);
//     Route::get('brands', [BrandController::class, 'getBrandsApi']);
//     Route::post('customers', [ContactController::class, 'postCustomersApi']);
//     Route::get('settings', [BusinessController::class, 'getEcomSettings']);
//     Route::get('variations', [ProductController::class, 'getVariationsApi']);
//     Route::post('orders', [SellPosController::class, 'placeOrdersApi']);
// });

//common route
Route::middleware(['auth'])->group(function () {
    Route::get('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
});

Route::middleware(['setData', 'auth', 'SetSessionData', 'language', 'timezone'])->group(function () {
    Route::get('/load-more-notifications', [HomeController::class, 'loadMoreNotifications']);
    Route::get('/get-total-unread', [HomeController::class, 'getTotalUnreadNotifications']);
    Route::get('/purchases/print/{id}', [PurchaseController::class, 'printInvoice']);
    Route::get('/purchases/{id}', [PurchaseController::class, 'show']);
    Route::get('/download-purchase-order/{id}/pdf', [PurchaseOrderController::class, 'downloadPdf'])->name('purchaseOrder.downloadPdf');
    Route::get('/sells/{id}', [SellController::class, 'show']);
    Route::get('/sells/{transaction_id}/print', [SellPosController::class, 'printInvoice'])->name('sell.printInvoice');
    Route::get('/download-sells/{transaction_id}/pdf', [SellPosController::class, 'downloadPdf'])->name('sell.downloadPdf');
    Route::get('/download-quotation/{id}/pdf', [SellPosController::class, 'downloadQuotationPdf'])
        ->name('quotation.downloadPdf');
    Route::get('/download-packing-list/{id}/pdf', [SellPosController::class, 'downloadPackingListPdf'])
        ->name('packing.downloadPdf');
    Route::get('/sells/invoice-url/{id}', [SellPosController::class, 'showInvoiceUrl']);
    Route::get('/show-notification/{id}', [HomeController::class, 'showNotification']);
    Route::post('/sell/check-invoice-number', [SellController::class, 'checkInvoiceNumber']);

    // Cash Register — Cash In / Cash Out
    Route::post('/cash-register/cash-in-out', [\App\Http\Controllers\CashRegisterController::class, 'postCashInOut'])->name('cash_register.cash_in_out');

    // eBill — Send digital bill manually
    Route::post('/ebill/send/{transaction_id}', [\App\Http\Controllers\SellPosController::class, 'sendEbill'])->name('ebill.send');

    // AI Assistant
    Route::post('/ai/chat', [\App\Http\Controllers\AiChatController::class, 'chat'])->name('ai.chat');
    Route::get('/ai/insights', [\App\Http\Controllers\AiChatController::class, 'insights'])->name('ai.insights');

    // Update/Migration Routes
    Route::get('/run-migration', [\App\Http\Controllers\UpdateController::class, 'runMigration']);
    Route::get('/clear-cache', [\App\Http\Controllers\UpdateController::class, 'clearCache']);
});