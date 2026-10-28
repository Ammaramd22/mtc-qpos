<?php

namespace App\Http\Controllers;

use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\Events\PurchaseCreatedOrModified;
use App\TaxRate;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use DB;
use Illuminate\Http\Request;

/**
 * Point of Purchase (POP) — POS-like UI for purchases.
 * Buys from customers and/or suppliers; links cash payments to the open cash drawer.
 */
class PurchasePosController extends Controller
{
    protected $contactUtil;

    protected $productUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $cashRegisterUtil;

    protected $moduleUtil;

    protected $dummyPaymentLine = [
        'method' => 'cash',
        'amount' => 0,
        'note' => '',
        'card_transaction_number' => '',
        'card_number' => '',
        'card_type' => '',
        'card_holder_name' => '',
        'card_month' => '',
        'card_year' => '',
        'card_security' => '',
        'cheque_number' => '',
        'cheque_name' => '',
        'cheque_date' => '',
        'cheque_bank_name' => '',
        'cheque_status' => 'pending',
        'bank_account_number' => '',
        'is_return' => 0,
        'transaction_no' => '',
    ];

    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * POP create screen (reuses sale_pos UI with is_pop flag).
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\HomeController::class, 'index']));
        }

        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = $this->businessUtil->getPosSettings($business_details);

        if (empty($pos_settings['enable_pop']) && ! in_array('pos_purchase', (array) session('business.enabled_modules', []))) {
            return redirect()->action([\App\Http\Controllers\PurchaseController::class, 'create'])
                ->with('status', [
                    'success' => 0,
                    'msg' => __('lang_v1.enable_pop_in_settings'),
                ]);
        }

        // Require open cash register (same as POS) when drawer linking is on
        if (! empty($pos_settings['pop_link_cash_register']) && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create'], ['sub_type' => 'pop']);
        }

        $register_details = $this->cashRegisterUtil->getCurrentCashRegister(auth()->user()->id);
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);
        $payment_lines[] = $this->dummyPaymentLine;

        $default_location = ! empty($register_details->location_id) ? BusinessLocation::findOrFail($register_details->location_id) : null;

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        if (empty($default_location)) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
                break;
            }
        }

        $payment_types = $this->productUtil->payment_types($default_location, true, $business_id);
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_ui = $this->businessUtil->getPopUi($pos_settings);

        $commission_agent = [];
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::forDropdown($business_id)
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;
        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);

        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        $price_groups = [];
        $default_price_group_id = null;
        $types_of_service = [];
        $shipping_statuses = $this->transactionUtil->shipping_statuses();
        $default_datetime = $this->businessUtil->format_date('now', true);
        $featured_products = ! empty($default_location) ? $default_location->getFeaturedProducts() : [];
        $pos_module_data = [];
        $invoice_layouts = [];
        $invoice_schemes = [];
        $default_invoice_schemes = null;
        $edit_discount = true;
        $edit_price = true; // Nutmeg: buy at different prices from customers
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];
        $last_sell = $this->getLastPurchaseForHub($business_id, $default_location->id ?? null);
        $sub_type = 'pop';
        $is_pop = true;

        return view('sale_pos.create')->with(compact(
            'edit_discount',
            'edit_price',
            'business_locations',
            'bl_attributes',
            'business_details',
            'taxes',
            'payment_types',
            'walk_in_customer',
            'payment_lines',
            'default_location',
            'shortcuts',
            'commission_agent',
            'categories',
            'brands',
            'pos_settings',
            'pos_ui',
            'change_return',
            'types',
            'customer_groups',
            'accounts',
            'price_groups',
            'types_of_service',
            'default_price_group_id',
            'shipping_statuses',
            'default_datetime',
            'featured_products',
            'sub_type',
            'pos_module_data',
            'invoice_schemes',
            'default_invoice_schemes',
            'invoice_layouts',
            'users',
            'last_sell',
            'is_pop'
        ));
    }

    /**
     * Store POP purchase (maps POS product lines → purchase lines).
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $pos_settings = $this->businessUtil->getPosSettings($this->businessUtil->getDetails($business_id));

            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            }

            $user_id = $request->session()->get('user.id');
            // Always refresh product cost from this buy (AVCO or last purchase)
            $enable_product_editing = 1;

            $input = $request->except('_token');
            $location_id = $input['location_id'] ?? null;
            $contact_id = $input['contact_id'] ?? ($input['customer_id'] ?? null);

            if (empty($location_id) || empty($contact_id)) {
                return [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            $products = $request->input('products', []);
            if (empty($products)) {
                return [
                    'success' => 0,
                    'msg' => __('lang_v1.no_products_added'),
                ];
            }

            $currency_details = $this->transactionUtil->purchaseCurrencyDetails($business_id);
            $exchange_rate = 1;

            // Map POS product rows → purchase line payload
            $purchases = $this->mapPosProductsToPurchaseLines($products);

            $final_total = $this->productUtil->num_uf($request->input('final_total', 0));
            $total_before_tax = $this->productUtil->num_uf($request->input('total_before_tax', $final_total));
            $discount_type = $request->input('discount_type', 'fixed');
            $discount_amount = $this->productUtil->num_uf($request->input('discount_amount', 0));
            $tax_amount = $this->productUtil->num_uf($request->input('tax_amount', 0));

            $transaction_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $contact_id,
                'type' => 'purchase',
                'status' => 'received',
                'payment_status' => 'due',
                'transaction_date' => ! empty($input['transaction_date'])
                    ? $this->productUtil->uf_date($input['transaction_date'], true)
                    : \Carbon\Carbon::now(),
                'total_before_tax' => $total_before_tax * $exchange_rate,
                'discount_type' => $discount_type,
                'discount_amount' => $discount_amount,
                'tax_id' => $request->input('tax_rate_id') ?: $request->input('tax_id'),
                'tax_amount' => $tax_amount * $exchange_rate,
                'shipping_charges' => $this->productUtil->num_uf($request->input('shipping_charges', 0)) * $exchange_rate,
                'final_total' => $final_total * $exchange_rate,
                'additional_notes' => $request->input('sale_note') ?: $request->input('additional_notes'),
                'exchange_rate' => $exchange_rate,
                'created_by' => $user_id,
                'ref_no' => $request->input('ref_no'),
            ];

            DB::beginTransaction();

            $ref_count = $this->productUtil->setAndGetReferenceCount('purchase');
            if (empty($transaction_data['ref_no'])) {
                $transaction_data['ref_no'] = $this->productUtil->generateReferenceNumber('purchase', $ref_count);
            }

            $transaction = Transaction::create($transaction_data);

            $this->productUtil->createOrUpdatePurchaseLines(
                $transaction,
                $purchases,
                $currency_details,
                $enable_product_editing
            );

            $payments = $request->input('payment', []);
            if (! is_array($payments)) {
                $payments = [];
            }

            // Same as Sell POS: change-return method lives under payment[change_return]
            // without amount; amount is the separate change_return field.
            $change_return = $this->dummyPaymentLine;
            if (! empty($payments['change_return']) && is_array($payments['change_return'])) {
                $change_return = array_merge($change_return, $payments['change_return']);
                unset($payments['change_return']);
            }
            $change_return['amount'] = $request->input('change_return', 0);
            $change_return['is_return'] = 1;
            if (! empty($request->input('change_return_id'))) {
                $change_return['payment_id'] = $request->input('change_return_id');
            }
            $payments[] = $change_return;

            // Drop incomplete rows so TransactionUtil never sees missing amount/method
            $payments = array_values(array_filter($payments, function ($payment) {
                return is_array($payment) && array_key_exists('amount', $payment);
            }));

            // Filter empty / change-return-only noise for register
            $payments_for_register = [];
            if (! empty($payments) && empty($request->input('is_credit_sale'))) {
                $this->transactionUtil->createOrUpdatePaymentLines($transaction, $payments);
                foreach ($payments as $payment) {
                    if (! empty($payment['is_return']) && (int) $payment['is_return'] === 1) {
                        continue;
                    }
                    if ($this->productUtil->num_uf($payment['amount'] ?? 0) != 0) {
                        $payments_for_register[] = $payment;
                    }
                }
            }

            $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
            $transaction->payment_status = $payment_status;

            $this->productUtil->adjustStockOverSelling($transaction);

            // Refresh variation cost: AVCO when business uses average cost, else last buy
            $this->productUtil->updateVariationCostsFromPurchase($transaction, $business_id);

            // Debit open cash register (opening float → purchases)
            if (! empty($pos_settings['pop_link_cash_register'])
                && ! empty($payments_for_register)
                && $this->cashRegisterUtil->countOpenedRegister() > 0
            ) {
                $this->cashRegisterUtil->addSellPayments($transaction, $payments_for_register);
            }

            $this->transactionUtil->activityLog($transaction, 'added');
            PurchaseCreatedOrModified::dispatch($transaction);

            DB::commit();

            $receipt = $this->buildPurchaseReceipt80mm($transaction->id, $business_id);

            $output = [
                'success' => 1,
                'msg' => __('purchase.purchase_add_success'),
                'receipt' => $receipt,
                'transaction_id' => $transaction->id,
                'invoice_no' => $transaction->ref_no,
                'final_total' => $transaction->final_total,
                'is_pop' => 1,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        // Always JSON for POP pay (never PDF / file download / HTML redirect)
        if ($request->ajax() || $request->wantsJson() || $request->expectsJson()) {
            return response()->json($output);
        }

        return redirect()->action([self::class, 'create'])->with('status', $output);
    }

    /**
     * Recent POP purchases for Bills & Reprint hub.
     */
    public function getRecentPurchases(Request $request)
    {
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $limit = config('constants.pos_recent_transactions_display_limit', 10);

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received');

        if (! auth()->user()->can('purchase.view') && auth()->user()->can('view_own_purchase')) {
            $query->where('created_by', auth()->user()->id);
        }

        $location_id = $request->get('location_id');
        if (! empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $transactions = $query->with(['contact'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        return view('sale_pos.partials.recent_purchases')
            ->with(compact('transactions'));
    }

    /**
     * Reprint 80mm purchase invoice (JSON for pos_print).
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (! request()->ajax()) {
            abort(404);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->where('id', $transaction_id)
                ->first();

            if (empty($purchase)) {
                return [
                    'success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }

            if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('purchase.create')) {
                return [
                    'success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }

            $receipt = $this->buildPurchaseReceipt80mm($transaction_id, $business_id);

            if (! empty($receipt) && ! empty($receipt['is_enabled'])) {
                return [
                    'success' => 1,
                    'receipt' => $receipt,
                ];
            }

            return [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }
    }

    /**
     * Last received purchase for last-bill / reprint hub (maps ref_no → invoice_no for shared UI).
     */
    protected function getLastPurchaseForHub($business_id, $location_id = null)
    {
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->where('status', 'received');

        if (! empty($location_id)) {
            $query->where('location_id', $location_id);
        }

        $purchase = $query->orderBy('created_at', 'desc')->first();
        if (! empty($purchase)) {
            $purchase->setAttribute('invoice_no', $purchase->ref_no);
        }

        return $purchase;
    }

    /**
     * Product row for POP (purchase cost as unit price).
     */
    public function getProductRow($variation_id, $location_id)
    {
        request()->merge(['for_pop' => '1']);

        return $this->productUtil->getPosProductRow($variation_id, $location_id);
    }

    /**
     * Contact search: customers and/or suppliers for Nutmeg-style trading.
     */
    public function getContacts()
    {
        if (! request()->ajax()) {
            abort(404);
        }

        $term = request()->input('q', '');
        $business_id = request()->session()->get('user.business_id');
        $pos_settings = $this->businessUtil->getPosSettings($this->businessUtil->getDetails($business_id));
        $both = ! empty($pos_settings['pop_contact_both']);

        $contacts = Contact::where('contacts.business_id', $business_id)
            ->leftjoin('customer_groups as cg', 'cg.id', '=', 'contacts.customer_group_id')
            ->active();

        if ($both) {
            $contacts->whereIn('contacts.type', ['customer', 'supplier', 'both']);
        } else {
            $contacts->onlySuppliers();
        }

        if (request()->filled('id')) {
            $contacts->where('contacts.id', (int) request()->input('id'));
        } elseif (! empty($term)) {
            $contacts->where(function ($query) use ($term) {
                $query->where('contacts.name', 'like', '%'.$term.'%')
                    ->orWhere('supplier_business_name', 'like', '%'.$term.'%')
                    ->orWhere('mobile', 'like', '%'.$term.'%')
                    ->orWhere('contacts.contact_id', 'like', '%'.$term.'%');
            });
        }

        $contacts = $contacts->select(
            'contacts.id',
            DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', 
                CONCAT(contacts.name, IF(contacts.type='supplier' OR contacts.type='both', ' [S]', IF(contacts.type='customer', ' [C]', ''))),
                CONCAT(contacts.name, ' (', contacts.contact_id, ')', IF(contacts.type='supplier' OR contacts.type='both', ' [S]', IF(contacts.type='customer', ' [C]', '')))
            ) AS text"),
            'contacts.name',
            'contacts.type',
            'mobile',
            'supplier_business_name',
            'balance',
            'shipping_address',
            'cg.amount as discount_percent',
            'cg.price_calculation_type',
            'cg.selling_price_group_id'
        )->limit(50)->get();

        return response()->json($contacts);
    }

    /**
     * Convert POS products[] fields into purchases[] expected by createOrUpdatePurchaseLines.
     */
    protected function mapPosProductsToPurchaseLines(array $products)
    {
        $purchases = [];
        foreach ($products as $product) {
            $qty = $this->productUtil->num_uf($product['quantity'] ?? 0);
            if ($qty == 0) {
                continue;
            }

            $unit_price = $this->productUtil->num_uf($product['unit_price'] ?? 0);
            $unit_price_inc_tax = $this->productUtil->num_uf($product['unit_price_inc_tax'] ?? $unit_price);
            $item_tax = $this->productUtil->num_uf($product['item_tax'] ?? 0);

            $purchases[] = [
                'product_id' => $product['product_id'],
                'variation_id' => $product['variation_id'],
                'quantity' => $qty,
                'product_unit_id' => $product['product_unit_id'] ?? null,
                'sub_unit_id' => $product['sub_unit_id'] ?? null,
                'pp_without_discount' => $unit_price,
                'discount_percent' => 0,
                'purchase_price' => $unit_price,
                'purchase_price_inc_tax' => $unit_price_inc_tax,
                'item_tax' => $item_tax,
                'purchase_line_tax_id' => $product['tax_id'] ?? null,
                'lot_number' => $product['lot_number'] ?? null,
                'mfg_date' => $product['mfg_date'] ?? null,
                'exp_date' => $product['exp_date'] ?? null,
                'default_sell_price' => $product['default_sell_price'] ?? null,
                'secondary_unit_quantity' => $product['secondary_unit_quantity'] ?? null,
                'product_expenses' => $product['product_expenses'] ?? ($product['product_expenses_json'] ?? null),
            ];
        }

        return $purchases;
    }

    /**
     * Build 80mm purchase invoice receipt for print preview.
     */
    protected function buildPurchaseReceipt80mm($transaction_id, $business_id)
    {
        try {
            $purchase = Transaction::where('business_id', $business_id)
                ->where('id', $transaction_id)
                ->with([
                    'contact',
                    'purchase_lines',
                    'purchase_lines.product',
                    'purchase_lines.variations',
                    'location',
                    'payment_lines',
                ])
                ->first();

            if (empty($purchase)) {
                return ['is_enabled' => 0];
            }

            $business = Business::find($business_id);
            $business_name = $business->name ?? session('business.name');
            $location = $purchase->location;
            $contact = $purchase->contact;
            $contact_name = '';
            if (! empty($contact)) {
                $contact_name = trim(($contact->supplier_business_name ?? '').' '.($contact->name ?? ''));
            }

            $payment_types = $this->productUtil->payment_types($location, true, $business_id);
            $payments = [];
            foreach ($purchase->payment_lines as $pl) {
                if (! empty($pl->is_return) && (int) $pl->is_return === 1) {
                    continue;
                }
                if ((float) $pl->amount == 0) {
                    continue;
                }
                // Recover mis-tagged cash rows that actually carry cheque details
                $method = $pl->method;
                if ($method === 'cash' && (! empty($pl->cheque_number) || ! empty($pl->cheque_bank_name) || ! empty($pl->cheque_name))) {
                    $method = 'cheque';
                }
                $label = $payment_types[$method] ?? ucfirst($method);
                if ($method === 'cheque') {
                    $bits = array_filter([
                        ! empty($pl->cheque_number) ? '#'.$pl->cheque_number : null,
                        $pl->cheque_bank_name ?? null,
                        ! empty($pl->cheque_date) ? \Carbon\Carbon::parse($pl->cheque_date)->format(session('business.date_format', 'd-m-Y')) : null,
                    ]);
                    if (! empty($bits)) {
                        $label .= ' ('.implode(' · ', $bits).')';
                    }
                }
                $payments[] = [
                    'amount' => $pl->amount,
                    'method_label' => $label,
                ];
            }

            $html = view('purchase.receipts.thermal_80mm', compact(
                'purchase',
                'business_name',
                'location',
                'contact_name',
                'payments'
            ))->render();

            return [
                'is_enabled' => 1,
                'print_type' => 'browser',
                'html_content' => $html,
                'print_title' => __('lang_v1.purchase_invoice').' '.$purchase->ref_no,
                'paper_width' => 80,
            ];
        } catch (\Exception $e) {
            \Log::emergency('POP receipt File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return ['is_enabled' => 0];
        }
    }
}
