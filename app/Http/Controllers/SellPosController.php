<?php
/* LICENSE: This source file belongs to The Web Fosters. The customer
 * is provided a licence to use it.
 * Permission is hereby granted, to any person obtaining the licence of this
 * software and associated documentation files (the "Software"), to use the
 * Software for personal or business purpose ONLY. The Software cannot be
 * copied, published, distribute, sublicense, and/or sell copies of the
 * Software, and to permit persons to whom the Software is furnished to do so,
 * subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. THE AUTHOR CAN FIX
 * ISSUES ON INTIMATION. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
 * OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH
 * THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 * @author     The Web Fosters <thewebfosters@gmail.com>
 * @owner      The Web Fosters <thewebfosters@gmail.com>
 * @copyright  2018 The Web Fosters
 * @license    As attached in zip file.
 */

namespace App\Http\Controllers;

use App\Account;
use App\Brands;
use App\Business;
use App\BusinessLocation;
use App\Category;
use App\Contact;
use App\CustomerGroup;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\Media;
use App\Product;
use App\SellingPriceGroup;
use App\TaxRate;
use App\Transaction;
use App\TransactionPayment;
use App\TransactionSellLine;
use App\TypesOfService;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\CashRegisterUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\NotificationUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\Warranty;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Razorpay\Api\Api;
use Stripe\Charge;
use Stripe\Stripe;
use Yajra\DataTables\Facades\DataTables;
use App\Events\SellCreatedOrModified;

class SellPosController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $contactUtil;

    protected $productUtil;

    protected $businessUtil;

    protected $transactionUtil;

    protected $cashRegisterUtil;

    protected $moduleUtil;

    protected $notificationUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(
        ContactUtil $contactUtil,
        ProductUtil $productUtil,
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        CashRegisterUtil $cashRegisterUtil,
        ModuleUtil $moduleUtil,
        NotificationUtil $notificationUtil
    ) {
        $this->contactUtil = $contactUtil;
        $this->productUtil = $productUtil;
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->cashRegisterUtil = $cashRegisterUtil;
        $this->moduleUtil = $moduleUtil;
        $this->notificationUtil = $notificationUtil;

        $this->dummyPaymentLine = ['method' => 'cash', 'amount' => 0, 'note' => '', 'card_transaction_number' => '', 'card_number' => '', 'card_type' => '', 'card_holder_name' => '', 'card_month' => '', 'card_year' => '', 'card_security' => '', 'cheque_number' => '', 'cheque_name' => '', 'cheque_date' => '', 'cheque_bank_name' => '', 'cheque_status' => 'pending', 'bank_account_number' => '',
            'is_return' => 0, 'transaction_no' => ''];
    }

    /**
     * Insert Loyalty payment type before custom_pay_* options (first payment row friendly).
     */
    protected function withLoyaltyPaymentType(array $payment_types): array
    {
        if (session('business.enable_rp') != 1) {
            return $payment_types;
        }

        $label = session('business.rp_name') ?: __('lang_v1.loyalty');
        unset($payment_types['loyalty']);

        $ordered = [];
        $inserted = false;
        foreach ($payment_types as $key => $value) {
            if (! $inserted && strpos((string) $key, 'custom_pay_') === 0) {
                $ordered['loyalty'] = $label;
                $inserted = true;
            }
            $ordered[$key] = $value;
        }
        if (! $inserted) {
            $ordered['loyalty'] = $label;
        }

        return $ordered;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        $is_cmsn_agent_enabled = request()->session()->get('business.sales_cmsn_agnt');
        $commission_agents = [];
        if (!empty($is_cmsn_agent_enabled)) {
            $commission_agents = User::forDropdown($business_id, false, true, true);
        }

        $is_tables_enabled = $this->transactionUtil->isModuleEnabled('tables');
        $is_service_staff_enabled = $this->transactionUtil->isModuleEnabled('service_staff');

        //Service staff filter
        $service_staffs = null;
        if ($is_service_staff_enabled) {
            $service_staffs = $this->productUtil->serviceStaffDropdown($business_id);
        }

        $is_types_service_enabled = $this->moduleUtil->isModuleEnabled('types_of_service');

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        return view('sale_pos.index')->with(compact('business_locations', 'customers', 'sales_representative', 'is_cmsn_agent_enabled', 'commission_agents', 'service_staffs', 'is_tables_enabled', 'is_service_staff_enabled', 'is_types_service_enabled', 'shipping_statuses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || auth()->user()->can('sell.create') || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') && auth()->user()->can('repair.create')))) {
            abort(403, 'Unauthorized action.');
        }

        //Check if subscribed or not, then check for users quota
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\HomeController::class, 'index']));
        } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action([\App\Http\Controllers\SellPosController::class, 'index']));
        }

        //like:repair
        $sub_type = request()->get('sub_type');

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create'], ['sub_type' => $sub_type]);
        }

        $register_details = $this->cashRegisterUtil->getCurrentCashRegister(auth()->user()->id);

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $payment_lines[] = $this->dummyPaymentLine;

        $default_location = !empty($register_details->location_id) ? BusinessLocation::findOrFail($register_details->location_id) : null;

        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'];
        $business_locations = $business_locations['locations'];

        //set first location as default locaton
        if (empty($default_location)) {
            foreach ($business_locations as $id => $name) {
                $default_location = BusinessLocation::findOrFail($id);
                break;
            }
        }

        $payment_types = $this->productUtil->payment_types($default_location, true, $business_id);
        if (!array_key_exists('credit_note', $payment_types)) {
            $payment_types['credit_note'] = __('lang_v1.credit_note');
        }
        $payment_types = $this->withLoyaltyPaymentType($payment_types);

        //Shortcuts
        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);
        $pos_settings = $this->businessUtil->getPosSettings($business_details);
        $pos_ui = $this->businessUtil->getPosUi($pos_settings);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::forDropdown($business_id)
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = Contact::getContactTypes();
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        //Selling Price Group Dropdown
        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $default_price_group_id = !empty($default_location->selling_price_group_id) && array_key_exists($default_location->selling_price_group_id, $price_groups) ? $default_location->selling_price_group_id : null;

        $wholesale_enabled = \App\Utils\WholesaleUtil::isEnabled($business_id);
        $wholesale_price_group_id = $wholesale_enabled
            ? \App\Utils\WholesaleUtil::getPriceGroupId($business_id, true)
            : null;

        //Types of service
        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $default_datetime = $this->businessUtil->format_date('now', true);

        $featured_products = !empty($default_location) ? $default_location->getFeaturedProducts() : [];

        //pos screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_pos_screen_view', ['sub_type' => $sub_type, 'job_sheet_id' => request()->get('job_sheet_id')]);
        $invoice_layouts = InvoiceLayout::forDropdown($business_id);

        $invoice_schemes = InvoiceScheme::forDropdown($business_id);
        $default_invoice_schemes = InvoiceScheme::getDefault($business_id);

        $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');

        //Added check because $users is of no use if enable_contact_assign if false
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];

        // Last POS sale for "Print Last Bill" widget
        $last_sell = \App\Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->orderBy('created_at', 'desc')
            ->select('id', 'invoice_no', 'final_total', 'created_at')
            ->first();

        $mobile_repair_checkout = request()->session()->pull('mobile_repair_checkout');
        $workshop_job_checkout = request()->session()->pull('workshop_job_checkout');

        return view('sale_pos.create')
            ->with(compact(
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
                'wholesale_enabled',
                'wholesale_price_group_id',
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
                'mobile_repair_checkout',
                'workshop_job_checkout',
            ));
    }

    /**
     * Display the POS screen.
     * @return \Illuminate\View\View
     */
    
    public function posDisplay(){
        $business_id = request()->session()->get('user.business_id');
        $business_details = $this->businessUtil->getDetails($business_id);
        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);
        $business_name = $business_details->name;
        $business_logo = $business_details->logo;
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $display_view = request()->get('view') === 'marketing' ? 'marketing' : 'cart';

        return view('sale_pos.display', compact('pos_settings', 'business_name', 'business_logo', 'walk_in_customer', 'display_view'));
    }

    /**
     * Customer display linked a customer — store in session for POS pickup.
     */
    public function linkCustomerFromDisplay(Request $request)
    {
        $request->validate([
            'customer_id' => 'required|integer',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $customer = $this->getContactForDisplaySync($business_id, (int) $request->input('customer_id'));

        if (empty($customer)) {
            return response()->json(['success' => 0, 'msg' => __('lang_v1.cd_customer_not_found')], 404);
        }

        $payload = $this->formatCustomerForDisplaySync($customer);
        $payload['ts'] = (int) round(microtime(true) * 1000);

        $request->session()->put('pos_display_pending_customer', $payload);

        return response()->json(['success' => 1, 'customer' => $payload]);
    }

    /**
     * POS polls for a customer linked on the customer display screen.
     */
    public function getPendingDisplayCustomer(Request $request)
    {
        $payload = $request->session()->get('pos_display_pending_customer');

        if (empty($payload) || empty($payload['id'])) {
            return response()->json(null);
        }

        return response()->json($payload);
    }

    /**
     * Clear pending customer display link (POS refresh / sale complete).
     */
    public function clearDisplayCustomer(Request $request)
    {
        $request->session()->forget('pos_display_pending_customer');

        return response()->json(['success' => 1]);
    }

    /**
     * Load contact with fields needed for POS customer select.
     */
    private function getContactForDisplaySync($business_id, $customer_id)
    {
        return Contact::where('contacts.business_id', $business_id)
            ->where('contacts.id', $customer_id)
            ->leftJoin('customer_groups as cg', 'cg.id', '=', 'contacts.customer_group_id')
            ->select(
                'contacts.id',
                'contacts.name',
                'contacts.prefix',
                'contacts.first_name',
                'contacts.contact_id',
                'contacts.mobile',
                'contacts.balance',
                'contacts.shipping_address',
                'contacts.pay_term_number',
                'contacts.pay_term_type',
                'cg.price_calculation_type',
                'cg.selling_price_group_id'
            )
            ->first();
    }

    /**
     * Format contact for POS / customer display sync payloads.
     */
    private function formatCustomerForDisplaySync($contact)
    {
        $text = (! empty($contact->contact_id))
            ? $contact->name.' ('.$contact->contact_id.')'
            : $contact->name;

        return [
            'id' => $contact->id,
            'text' => $text,
            'name' => $contact->name,
            'prefix' => $contact->prefix,
            'first_name' => $contact->first_name,
            'mobile' => $contact->mobile,
            'balance' => $contact->balance,
            'shipping_address' => $contact->shipping_address,
            'pay_term_number' => $contact->pay_term_number,
            'pay_term_type' => $contact->pay_term_type,
            'price_calculation_type' => $contact->price_calculation_type,
            'selling_price_group_id' => $contact->selling_price_group_id,
        ];
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('so.create')) {
            abort(403, 'Unauthorized action.');
        }

        $is_direct_sale = false;
        if (!empty($request->input('is_direct_sale'))) {
            $is_direct_sale = true;
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create']);
        }

        try {
            $input = $request->except('_token');

            // Offline billing idempotency — same offline_uuid must not create a second invoice
            $offline_uuid = ! empty($input['offline_uuid']) ? substr(trim((string) $input['offline_uuid']), 0, 80) : null;
            if (! empty($offline_uuid)) {
                $business_id_check = $request->session()->get('user.business_id');
                $existing_offline = Transaction::where('business_id', $business_id_check)
                    ->where('offline_uuid', $offline_uuid)
                    ->where('type', 'sell')
                    ->first();
                if (! empty($existing_offline)) {
                    $receipt = $this->receiptContent(
                        $business_id_check,
                        $existing_offline->location_id,
                        $existing_offline->id
                    );

                    return [
                        'success' => 1,
                        'msg' => __('lang_v1.offline_sale_already_synced', ['invoice' => $existing_offline->invoice_no]),
                        'receipt' => $receipt,
                        'transaction_id' => $existing_offline->id,
                        'invoice_no' => $existing_offline->invoice_no,
                        'offline_duplicate' => 1,
                    ];
                }
                $input['offline_uuid'] = $offline_uuid;
            }

            $input['is_quotation'] = 0;
            //status is send as quotation from Add sales screen.
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
                $input['sub_status'] = 'quotation';
            } elseif ($input['status'] == 'proforma') {
                $input['status'] = 'draft';
                $input['sub_status'] = 'proforma';
            }

            //Add change return
            $change_return = $this->dummyPaymentLine;
            if (!empty($input['payment']['change_return'])) {
                $change_return = $input['payment']['change_return'];
                unset($input['payment']['change_return']);
            }

            //Check Customer credit limit
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($input);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = ['success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                ];
                if (!$is_direct_sale) {
                    return $output;
                } else {
                    return redirect()
                        ->action([\App\Http\Controllers\SellController::class, 'index'])
                        ->with('status', $output);
                }
            }

            // Inline return (negative qty) must not make the bill negative; qty cannot be zero
            if (!empty($input['products']) && is_array($input['products'])) {
                foreach ($input['products'] as $product_line) {
                    if (!isset($product_line['quantity'])) {
                        continue;
                    }
                    $line_qty = $this->productUtil->num_uf($product_line['quantity']);
                    if ((float) $line_qty === 0.0) {
                        $output = [
                            'success' => 0,
                            'msg' => __('lang_v1.inline_return_qty_zero'),
                        ];
                        if (!$is_direct_sale) {
                            return $output;
                        }

                        return redirect()
                            ->action([\App\Http\Controllers\SellController::class, 'index'])
                            ->with('status', $output);
                    }
                }
            }
            if (isset($input['final_total']) && $this->productUtil->num_uf($input['final_total']) < 0) {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.inline_return_exceeds_sale'),
                ];
                if (!$is_direct_sale) {
                    return $output;
                }

                return redirect()
                    ->action([\App\Http\Controllers\SellController::class, 'index'])
                    ->with('status', $output);
            }

            // Block cheque payments for Walk-In Customer; require cheque name
            if (!empty($input['payment']) && is_array($input['payment'])) {
                $walk_in = $this->contactUtil->getWalkInCustomer($request->session()->get('user.business_id'));
                $walk_in_id = !empty($walk_in['id']) ? (string) $walk_in['id'] : null;
                $contact_id = !empty($input['contact_id']) ? (string) $input['contact_id'] : '';

                foreach ($input['payment'] as $pay_key => $payment) {
                    if ($pay_key === 'change_return' || empty($payment['method']) || $payment['method'] !== 'cheque') {
                        continue;
                    }
                    if ($walk_in_id && $contact_id === $walk_in_id) {
                        $output = [
                            'success' => 0,
                            'msg' => __('lang_v1.cheque_not_allowed_walk_in'),
                        ];
                        if (!$is_direct_sale) {
                            return $output;
                        }

                        return redirect()
                            ->action([\App\Http\Controllers\SellController::class, 'index'])
                            ->with('status', $output);
                    }
                    if (empty(trim((string) ($payment['cheque_name'] ?? '')))) {
                        $output = [
                            'success' => 0,
                            'msg' => __('lang_v1.cheque_name_required'),
                        ];
                        if (!$is_direct_sale) {
                            return $output;
                        }

                        return redirect()
                            ->action([\App\Http\Controllers\SellController::class, 'index'])
                            ->with('status', $output);
                    }
                }
            }

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not, then check for users quota
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse();
                } elseif (!$this->moduleUtil->isQuotaAvailable('invoices', $business_id)) {
                    return $this->moduleUtil->quotaExpiredResponse('invoices', $business_id, action([\App\Http\Controllers\SellPosController::class, 'index']));
                }

                $user_id = $request->session()->get('user.id');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                // Exchange / Trade-In credit (not a bill discount)
                $tradeInUtil = new \App\Utils\TradeInUtil();
                $trade_in_credit = $tradeInUtil->sumExchangeValueFromProducts($input['products'], $this->productUtil);
                if ($trade_in_credit > 0) {
                    $posted_final = $this->productUtil->num_uf($input['final_total'] ?? 0);
                    $expected_without_ti = (float) ($invoice_total['final_total'] ?? 0);
                    $expected_with_ti = max(0, $expected_without_ti - $trade_in_credit);
                    // Trust posted final if it is within tolerance of expected net; else force net
                    if (abs($posted_final - $expected_with_ti) > 0.5 && $posted_final > $expected_with_ti + 0.05) {
                        $input['final_total'] = $expected_with_ti;
                    } elseif ($posted_final < 0) {
                        $input['final_total'] = 0;
                    }
                }

                DB::beginTransaction();

                if (empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = \Carbon::now();
                } else {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }
                if ($is_direct_sale) {
                    $input['is_direct_sale'] = 1;
                }

                //Set commission agent
                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                //If default price group for the location exists
                $price_group_id = $price_group_id == 0 && $request->has('default_price_group') ? $request->input('default_price_group') : $price_group_id;

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                //Generate reference number
                if (!empty($input['is_recurring'])) {
                    //Update reference count
                    $ref_count = $this->transactionUtil->setAndGetReferenceCount('subscription');
                    $input['subscription_no'] = $this->transactionUtil->generateReferenceNumber('subscription', $ref_count);
                }

                if (!empty($request->input('invoice_scheme_id'))) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }

                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                    $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                    $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                    $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                    $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                    $request->input('service_custom_field_4') : null;
                    $input['service_custom_field_5'] = !empty($request->input('service_custom_field_5')) ?
                    $request->input('service_custom_field_5') : null;
                    $input['service_custom_field_6'] = !empty($request->input('service_custom_field_6')) ?
                    $request->input('service_custom_field_6') : null;
                }

                if ($request->input('additional_expense_value_1') != '') {
                    $input['additional_expense_key_1'] = $request->input('additional_expense_key_1');
                    $input['additional_expense_value_1'] = $request->input('additional_expense_value_1');
                }

                if ($request->input('additional_expense_value_2') != '') {
                    $input['additional_expense_key_2'] = $request->input('additional_expense_key_2');
                    $input['additional_expense_value_2'] = $request->input('additional_expense_value_2');
                }

                if ($request->input('additional_expense_value_3') != '') {
                    $input['additional_expense_key_3'] = $request->input('additional_expense_key_3');
                    $input['additional_expense_value_3'] = $request->input('additional_expense_value_3');
                }

                if ($request->input('additional_expense_value_4') != '') {
                    $input['additional_expense_key_4'] = $request->input('additional_expense_key_4');
                    $input['additional_expense_value_4'] = $request->input('additional_expense_value_4');
                }

                $input['selling_price_group_id'] = $price_group_id;

                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $input['res_table_id'] = request()->get('res_table_id');
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $input['res_waiter_id'] = request()->get('res_waiter_id');
                }

                if ($this->transactionUtil->isModuleEnabled('kitchen')) {
                    $input['is_kitchen_order'] = request()->get('is_kitchen_order');
                }

                //upload document
                $input['document'] = $this->transactionUtil->uploadFile($request, 'sell_document', 'documents');

                $transaction = $this->transactionUtil->createSellTransaction($business_id, $input, $invoice_total, $user_id);

                //Upload Shipping documents
                Media::uploadMedia($business_id, $transaction, $request, 'shipping_documents', false, 'shipping_document');

                $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id']);

                // Persist exchange / trade-in + receive old item into stock (final sales only)
                if ($input['status'] == 'final') {
                    try {
                        $tiUtil = new \App\Utils\TradeInUtil();
                        $tiUtil->processTradeInsForSale(
                            $transaction,
                            $input['products'],
                            $business_id,
                            $user_id,
                            true
                        );
                    } catch (\Exception $tiEx) {
                        \Log::emergency('Trade-in process: '.$tiEx->getMessage().' @ '.$tiEx->getFile().':'.$tiEx->getLine());
                        throw $tiEx;
                    }
                }

                app(\App\Utils\MobileShopUtil::class)->syncImeisFromSale($transaction, $input['products'], $input['location_id']);

                if (! empty($input['mobile_repair_id'])) {
                    app(\App\Utils\MobileShopUtil::class)->markRepairPaid(
                        $business_id,
                        (int) $input['mobile_repair_id'],
                        $transaction->id
                    );
                }

                if (! empty($input['workshop_job_id'])) {
                    app(\App\Utils\VehicleModificationUtil::class)->markJobPaid(
                        $business_id,
                        (int) $input['workshop_job_id'],
                        $transaction->id
                    );
                }

                $change_return['amount'] = $input['change_return'] ?? 0;
                $change_return['is_return'] = 1;

                // Only store change-return when customer was actually given change
                $change_return_amount = $this->transactionUtil->num_uf($change_return['amount'] ?? 0);
                if ($change_return_amount > 0) {
                    $input['payment'][] = $change_return;
                }

                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                if (!$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                    $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
                }

                $whatsapp_link = null;
                $ebill_prompt = false;

                //Check for final and do some processing.
                if ($input['status'] == 'final') {
                    if (!$is_direct_sale) {
                        //set service staff timer
                        foreach ($input['products'] as $product_line) {
                            if (!empty($product_line['res_service_staff_id'])) {
                                $product = Product::find($product_line['product_id']);

                                if (!empty($product->preparation_time_in_minutes)) {
                                    $service_staff = User::find($product_line['res_service_staff_id']);

                                    $base_time = \Carbon::parse($transaction->transaction_date);

                                    //if already assigned set base time as available_at
                                    if (!empty($service_staff->available_at) && \Carbon::parse($service_staff->available_at)->gt(\Carbon::now())) {
                                        $base_time = \Carbon::parse($service_staff->available_at);
                                    }

                                    $total_minutes = $product->preparation_time_in_minutes * $this->transactionUtil->num_uf($product_line['quantity']);

                                    $service_staff->available_at = $base_time->addMinutes($total_minutes);
                                    $service_staff->save();
                                }
                            }
                        }
                    }
                    //update product stock
                    foreach ($input['products'] as $product) {
                        $decrease_qty = $this->productUtil
                            ->num_uf($product['quantity']);
                        if (!empty($product['base_unit_multiplier'])) {
                            $decrease_qty = $decrease_qty * $product['base_unit_multiplier'];
                        }

                        if ($product['enable_stock']) {
                            $this->productUtil->decreaseProductQuantity(
                                $product['product_id'],
                                $product['variation_id'],
                                $input['location_id'],
                                $decrease_qty
                            );
                        }

                        if ($product['product_type'] == 'combo') {
                            //Decrease quantity of combo as well.
                            $this->productUtil
                                ->decreaseProductQuantityCombo(
                                    $product['combo'],
                                    $input['location_id']
                                );
                        }
                    }

                    //Add payments to Cash Register
                    if (!$is_direct_sale && !$transaction->is_suspend && !empty($input['payment']) && !$is_credit_sale) {
                        $this->cashRegisterUtil->addSellPayments($transaction, $input['payment']);
                    }

                    //Update payment status
                    $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

                    $transaction->payment_status = $payment_status;

                    if ($request->session()->get('business.enable_rp') == 1) {
                        $redeemed = !empty($input['rp_redeemed']) ? $input['rp_redeemed'] : 0;
                        if ($redeemed > 0) {
                            $cs = $request->session()->get('business.common_settings') ?? [];
                            if (is_string($cs)) {
                                $cs = json_decode($cs, true) ?? [];
                            }
                            if (! empty($cs['loyalty_otp_on_redeem'])) {
                                $has_card = ! empty($input['loyalty_has_card']);
                                $otp_ok = $request->session()->get('loyalty_otp_ok_'.$contact_id);
                                if (! $has_card && ! $otp_ok) {
                                    throw new \Exception(__('lang_v1.loyalty_otp_invalid'));
                                }
                                $request->session()->forget('loyalty_otp_ok_'.$contact_id);
                            }
                        }
                        $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, 0, $redeemed);
                    }

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings,
                    ];
                    $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');

                    //Auto send notification
                    $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);

                    // eBill — auto-send unless prompt_before_send is enabled
                    $ebill_settings = !empty($business_details->common_settings['ebill_settings']) ? $business_details->common_settings['ebill_settings'] : [];
                    $ebill_prompt = false;
                    if (empty($ebill_settings['prompt_before_send'])) {
                        $this->notificationUtil->autoSendEbill($business_id, $transaction, $transaction->contact);
                    } else {
                        $ebill_prompt = [
                            'transaction_id' => $transaction->id,
                            'contact_name'   => $transaction->contact->name ?? '',
                            'prefix'         => $transaction->contact->prefix ?? '',
                            'first_name'     => $transaction->contact->first_name ?? '',
                            'mobile'         => $transaction->contact->mobile ?? '',
                            'has_sms'        => !empty($ebill_settings['enable_sms_ebill']),
                            'has_whatsapp'   => !empty($ebill_settings['enable_whatsapp_ebill']),
                        ];
                    }

                    // zatca instant sync if status final type sell
                    if($transaction->type == 'sell'){
                            $this->moduleUtil->getModuleData('after_sales', ['transaction' => $transaction]);
                    }

                }

                if (!empty($transaction->sales_order_ids)) {
                    $this->transactionUtil->updateSalesOrderStatus($transaction->sales_order_ids);
                }

                $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                $this->transactionUtil->activityLog($transaction, 'added');

                

                DB::commit();

                SellCreatedOrModified::dispatch($transaction);

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($transaction->id, $business_id);

                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg = trans('sale.pos_sale_added');
                $receipt = '';
                $invoice_layout_id = $request->input('invoice_layout_id');
                $print_invoice = false;
                if (!$is_direct_sale) {
                    if ($input['status'] == 'draft') {
                        $msg = trans('sale.draft_added');

                        if ($input['is_quotation'] == 1) {
                            $msg = trans('lang_v1.quotation_added');
                            $print_invoice = true;
                        }
                    } elseif ($input['status'] == 'final') {
                        $print_invoice = true;
                    }
                }

                if ($transaction->is_suspend == 1 && empty($pos_settings['print_on_suspend'])) {
                    $print_invoice = false;
                }

                if (!auth()->user()->can('print_invoice')) {
                    $print_invoice = false;
                }

                if ($print_invoice) {
                    try {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                    } catch (\Throwable $printEx) {
                        // Sale already committed — do not fail the whole POS pay because receipt HTML failed
                        \Log::emergency('POS receipt failed after sale. File:'.$printEx->getFile().' Line:'.$printEx->getLine().' Message:'.$printEx->getMessage());
                        $receipt = [
                            'is_enabled' => false,
                            'print_type' => 'browser',
                            'html_content' => null,
                            'printer_config' => [],
                            'data' => [],
                            'print_error' => $printEx->getMessage(),
                        ];
                    }
                }

                $output = [
                    'success'        => 1,
                    'msg'            => $msg,
                    'receipt'        => $receipt,
                    'transaction_id' => $transaction->id,
                    'invoice_no'     => $transaction->invoice_no,
                    'final_total'    => $transaction->final_total,
                    'print_url'      => route('sell.printInvoice', $transaction->id),
                ];

                if (!empty($ebill_prompt)) {
                    $output['ebill_prompt'] = $ebill_prompt;
                }

                if (!empty($whatsapp_link)) {
                    $output['whatsapp_link'] = $whatsapp_link;
                }
            } else {
                $output = ['success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }
            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            }
            // Surface the real missing column (do not hide behind a generic schema message)
            if ($e instanceof \Illuminate\Database\QueryException) {
                $sqlMsg = $e->getMessage();
                if (preg_match("/Unknown column ['`]([^'`]+)['`]/i", $sqlMsg, $m)) {
                    $msg = 'Missing database column: '.$m[1].'. Run pending migrations (or cpanel_pos_print_fix.sql), then try Pay again.';
                } elseif (stripos($sqlMsg, 'Unknown column') !== false) {
                    $msg = 'Database schema is incomplete. Run pending migrations, then try Pay again.';
                }
            }
            if (config('app.debug')) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action([\App\Http\Controllers\SellController::class, 'getQuotations'])
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action([\App\Http\Controllers\SellController::class, 'getDrafts'])
                        ->with('status', $output);
                }
            } elseif ($input['status'] == 'quotation') {
                return redirect()
                    ->action([\App\Http\Controllers\SellController::class, 'getQuotations'])
                    ->with('status', $output);
            } elseif (isset($input['type']) && $input['type'] == 'sales_order') {
                return redirect()
                    ->action([\App\Http\Controllers\SalesOrderController::class, 'index'])
                    ->with('status', $output);
            } else {
                if (!empty($input['sub_type']) && $input['sub_type'] == 'repair') {
                    $redirect_url = $input['print_label'] == 1 ? action([\Modules\Repair\Http\Controllers\RepairController::class, 'printLabel'], [$transaction->id]) : action([\Modules\Repair\Http\Controllers\RepairController::class, 'index']);

                    return redirect($redirect_url)
                        ->with('status', $output);
                }

                return redirect()
                    ->action([\App\Http\Controllers\SellController::class, 'index'])
                    ->with('status', $output);
            }
        }
    }

    /**
     * Returns the content for the receipt
     *
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  int  $transaction_id
     * @param  string  $printer_type = null
     * @return array
     */
    private function receiptContent(
        $business_id,
        $location_id,
        $transaction_id,
        $printer_type = null,
        $is_package_slip = false,
        $from_pos_screen = true,
        $invoice_layout_id = null,
        $is_delivery_note = false
    ) {
        $output = ['is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => [],
        ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);

        $txn_for_receipt = \App\Transaction::where('id', $transaction_id)
            ->select('id', 'status', 'is_quotation', 'sub_status', 'is_direct_sale')
            ->first();
        $is_quotation_receipt = $txn_for_receipt
            && (int) $txn_for_receipt->is_quotation === 1
            && $txn_for_receipt->status === 'draft';
        $is_pos_sale_invoice = $txn_for_receipt && (int) ($txn_for_receipt->is_direct_sale ?? 0) === 0;

        // Quotations always print from POS even if "print receipt on invoice" is off
        if ($from_pos_screen && $location_details->print_receipt_on_invoice != 1 && !$is_quotation_receipt) {
            return $output;
        }
        //Check if printing of invoice is enabled or not.
        //If enabled, get print type.
        $output['is_enabled'] = true;

        $invoice_layout_id = !empty($invoice_layout_id) ? $invoice_layout_id : $location_details->invoice_layout_id;
        $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $invoice_layout_id);

        //Check if printer setting is provided.
        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);

        $currency_details = [
            'symbol' => $business_details->currency_symbol,
            'thousand_separator' => $business_details->thousand_separator,
            'decimal_separator' => $business_details->decimal_separator,
        ];
        $receipt_details->currency = $currency_details;

        $is_quotation_receipt = $is_quotation_receipt
            || !empty($receipt_details->is_quotation)
            || ((!empty($receipt_details->status) && $receipt_details->status === 'draft')
                && !empty($receipt_details->sub_status) && $receipt_details->sub_status === 'quotation');

        // Quotation slips never show payment / due lines
        if ($is_quotation_receipt) {
            $receipt_details->is_quotation = 1;
            $receipt_details->payments = [];
            $receipt_details->total_paid = null;
            $receipt_details->total_due = null;
            $receipt_details->total_previous_due = null;
            $receipt_details->all_due = null;
            if (empty($receipt_details->invoice_heading)) {
                $receipt_details->invoice_heading = __('lang_v1.quotation');
            }
        }

        if ($is_package_slip) {
            $output['html_content'] = view('sale_pos.receipts.packing_slip', compact('receipt_details'))->render();
            $output['print_title'] = ($receipt_details->invoice_no ?? '') . ' — ' . __('lang_v1.packing_slip');
            $output['paper_width'] = 80;

            return $output;
        }

        if ($is_delivery_note) {
            $output['html_content'] = view('sale_pos.receipts.delivery_note', compact('receipt_details'))->render();
            $output['print_title'] = ($receipt_details->invoice_no ?? '') . ' — ' . __('lang_v1.delivery_note');
            $output['paper_width'] = 80;
            $output['design'] = 'delivery_note';

            return $output;
        }

        $output['print_title'] = $receipt_details->invoice_no;
        //If print type browser - return the content, printer - return printer config data, and invoice format config
        if ($receipt_printer_type == 'printer') {
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $pos_settings = [];
            if (!empty($business_details->pos_settings)) {
                $pos_settings = is_array($business_details->pos_settings)
                    ? $business_details->pos_settings
                    : (json_decode($business_details->pos_settings, true) ?: []);
            }
            $handheld_58mm = !empty($pos_settings['enable_handheld_58mm']);
            $design = $receipt_details->design ?? 'classic';
            $thermal_designs = ['slim', 'slim2', 'classic_80mm', 'classic_80mm_mobile', 'quotation_80mm'];
            if ($is_quotation_receipt) {
                $design = 'quotation_80mm';
                $receipt_details->design = 'quotation_80mm';
            } elseif ($handheld_58mm) {
                $design = 'slim2';
                $receipt_details->design = 'slim2';
            } elseif (!in_array($design, $thermal_designs, true) && ($from_pos_screen || $is_pos_sale_invoice)) {
                // POS bill receipt: A5 landscape (checkout + reprint). Leaves thermal / quotation / direct-sale A4 alone.
                $design = 'a5_half_landscape';
                $receipt_details->design = 'a5_half_landscape';
            }

            $layout = !empty($design) ? 'sale_pos.receipts.' . $design : 'sale_pos.receipts.classic';

            $output['html_content'] = view($layout, compact('receipt_details'))->render();
            // Paper profile: A5 landscape / A4 / thermal 58|80
            if ($design === 'a5_half_landscape') {
                $output['paper_width'] = 210;
                $output['paper_height'] = 148;
                $output['paper_label'] = 'A5 Landscape';
            } elseif (in_array($design, ['classic_a4', 'classic', 'elegant', 'elegant_modified', 'detailed', 'columnize-taxes', 'english-arabic'], true)) {
                $output['paper_width'] = 210;
                $output['paper_height'] = 297;
                $output['paper_label'] = 'A4';
            } elseif ($design === 'slim2' || ($handheld_58mm && !$is_quotation_receipt)) {
                $output['paper_width'] = 58;
                $output['paper_height'] = null;
                $output['paper_label'] = '58mm thermal';
            } else {
                $output['paper_width'] = 80;
                $output['paper_height'] = null;
                $output['paper_label'] = '80mm thermal';
            }
            $output['design'] = $design;
            $output['immediate_print'] = !empty($pos_settings['handheld_immediate_print']);
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (!(auth()->user()->can('superadmin') || auth()->user()->can('sell.update')
            || auth()->user()->can('edit_pos_payment')
            || ($this->moduleUtil->hasThePermissionInSubscription($business_id, 'repair_module') &&
                auth()->user()->can('repair.update')))) {
            abort(403, 'Unauthorized action.');
        }

        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        //Check if the transaction can be edited or not.
        $edit_days = request()->session()->get('business.transaction_edit_days');
        if (!$this->transactionUtil->canBeEdited($id, $edit_days)) {
            return back()
                ->with('status', ['success' => 0,
                    'msg' => __('messages.transaction_edit_not_allowed', ['days' => $edit_days])]);
        }

        //Check if there is a open register, if no then redirect to Create Register screen.
        if ($this->cashRegisterUtil->countOpenedRegister() == 0) {
            return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create']);
        }

        //Check if return exist then not allowed
        if ($this->transactionUtil->isReturnExist($id)) {
            return back()->with('status', ['success' => 0,
                'msg' => __('lang_v1.return_exist')]);
        }

        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

        $business_details = $this->businessUtil->getDetails($business_id);

        $taxes = TaxRate::forBusinessDropdown($business_id, true, true);

        $transaction = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->with(['price_group', 'types_of_service', 'contact', 'location'])
            ->findorfail($id);

        // If ZATCA module is installed and this transaction is successfully synced, prevent edit
        $moduleUtil = new ModuleUtil();
        if ($moduleUtil->isModuleInstalled('ZatcaIntegrationKsa')) {
            if (!empty($transaction) && $transaction->zatca_status === 'success') {
                return back()->with('status', ['success' => 0,
                    'msg' => __('lang_v1.invoice_synced_to_zatca_cannot_be_edited')]);
            }
        }

        $location_id = $transaction->location_id;
        $business_location = BusinessLocation::find($location_id);
        $payment_types = $this->productUtil->payment_types($business_location, true);
        $payment_types = $this->withLoyaltyPaymentType($payment_types);
        $location_printer_type = $business_location->receipt_printer_type;
        $sell_details = TransactionSellLine::join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->leftjoin('units as u', 'p.secondary_unit_id', '=', 'u.id')
            ->where('transaction_sell_lines.transaction_id', $id)
            ->with(['warranties'])
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'p.id as product_id',
                'p.enable_stock',
                'p.image as product_image',
                'p.name as product_actual_name',
                'p.type as product_type',
                'pv.name as product_variation_name',
                'pv.is_dummy as is_dummy',
                'variations.name as variation_name',
                'variations.sub_sku',
                'p.barcode_type',
                'p.enable_sr_no',
                'variations.id as variation_id',
                'units.short_name as unit',
                'units.allow_decimal as unit_allow_decimal',
                'u.short_name as second_unit',
                'transaction_sell_lines.secondary_unit_quantity',
                'transaction_sell_lines.tax_id as tax_id',
                'transaction_sell_lines.item_tax as item_tax',
                'transaction_sell_lines.unit_price as default_sell_price',
                'transaction_sell_lines.unit_price_before_discount as unit_price_before_discount',
                'transaction_sell_lines.unit_price_inc_tax as sell_price_inc_tax',
                'transaction_sell_lines.id as transaction_sell_lines_id',
                'transaction_sell_lines.id',
                'transaction_sell_lines.quantity as quantity_ordered',
                'transaction_sell_lines.sell_line_note as sell_line_note',
                'transaction_sell_lines.parent_sell_line_id',
                'transaction_sell_lines.lot_no_line_id',
                'transaction_sell_lines.line_discount_type',
                'transaction_sell_lines.line_discount_amount',
                'transaction_sell_lines.res_service_staff_id',
                'units.id as unit_id',
                'transaction_sell_lines.sub_unit_id',

                //qty_available not added when negative to avoid max quanity getting decreased in edit and showing error in max quantity validation
                DB::raw('IF(vld.qty_available > 0, vld.qty_available + transaction_sell_lines.quantity, transaction_sell_lines.quantity) AS qty_available')
            )
            ->get();
        if (!empty($sell_details)) {
            foreach ($sell_details as $key => $value) {
                $variation = Variation::with('media')->findOrFail($value->variation_id);
                $sell_details[$key]->media = $variation->media;

                //If modifier or combo sell line then unset
                if (!empty($sell_details[$key]->parent_sell_line_id)) {
                    unset($sell_details[$key]);
                } else {
                    if ($transaction->status != 'final') {
                        $actual_qty_avlbl = $value->qty_available - $value->quantity_ordered;
                        $sell_details[$key]->qty_available = $actual_qty_avlbl;
                        $value->qty_available = $actual_qty_avlbl;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    //Add available lot numbers for dropdown to sell lines
                    $lot_numbers = [];
                    if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
                        $lot_number_obj = $this->transactionUtil->getLotNumbersFromVariation($value->variation_id, $business_id, $location_id);
                        foreach ($lot_number_obj as $lot_number) {
                            //If lot number is selected added ordered quantity to lot quantity available
                            if ($value->lot_no_line_id == $lot_number->purchase_line_id) {
                                $lot_number->qty_available += $value->quantity_ordered;
                            }

                            $lot_number->qty_formated = $this->productUtil->num_f($lot_number->qty_available);
                            $lot_numbers[] = $lot_number;
                        }
                    }
                    $sell_details[$key]->lot_numbers = $lot_numbers;

                    if (!empty($value->sub_unit_id)) {
                        $value = $this->productUtil->changeSellLineUnit($business_id, $value);
                        $sell_details[$key] = $value;
                    }

                    $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($value->qty_available, false, null, true);

                    if ($this->transactionUtil->isModuleEnabled('modifiers')) {
                        //Add modifier details to sel line details
                        $sell_line_modifiers = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'modifier')
                            ->get();
                        $modifiers_ids = [];
                        if (count($sell_line_modifiers) > 0) {
                            $sell_details[$key]->modifiers = $sell_line_modifiers;
                            foreach ($sell_line_modifiers as $sell_line_modifier) {
                                $modifiers_ids[] = $sell_line_modifier->variation_id;
                            }
                        }
                        $sell_details[$key]->modifiers_ids = $modifiers_ids;

                        //add product modifier sets for edit
                        $this_product = Product::find($sell_details[$key]->product_id);
                        if (count($this_product->modifier_sets) > 0) {
                            $sell_details[$key]->product_ms = $this_product->modifier_sets;
                        }
                    }

                    //Get details of combo items
                    if ($sell_details[$key]->product_type == 'combo') {
                        $sell_line_combos = TransactionSellLine::where('parent_sell_line_id', $sell_details[$key]->transaction_sell_lines_id)
                            ->where('children_type', 'combo')
                            ->get()
                            ->toArray();
                        if (!empty($sell_line_combos)) {
                            $sell_details[$key]->combo_products = $sell_line_combos;
                        }

                        //calculate quantity available if combo product
                        $combo_variations = [];
                        foreach ($sell_line_combos as $combo_line) {
                            $combo_variations[] = [
                                'variation_id' => $combo_line['variation_id'],
                                'quantity' => $combo_line['quantity'] / $sell_details[$key]->quantity_ordered,
                                'unit_id' => null,
                            ];
                        }
                        $sell_details[$key]->qty_available =
                        $this->productUtil->calculateComboQuantity($location_id, $combo_variations);

                        if ($transaction->status == 'final') {
                            $sell_details[$key]->qty_available = $sell_details[$key]->qty_available + $sell_details[$key]->quantity_ordered;
                        }

                        $sell_details[$key]->formatted_qty_available = $this->productUtil->num_f($sell_details[$key]->qty_available, false, null, true);
                    }
                }
            }
        }

        $featured_products = $business_location->getFeaturedProducts();

        $payment_lines = $this->transactionUtil->getPaymentDetails($id);
        //If no payment lines found then add dummy payment line.
        if (empty($payment_lines)) {
            $payment_lines[] = $this->dummyPaymentLine;
        }

        $shortcuts = json_decode($business_details->keyboard_shortcuts, true);

        $pos_settings = $this->businessUtil->getPosSettings($business_details);
        $pos_ui = $this->businessUtil->getPosUi($pos_settings);

        $commsn_agnt_setting = $business_details->sales_cmsn_agnt;
        $commission_agent = [];
        if ($commsn_agnt_setting == 'user') {
            $commission_agent = User::forDropdown($business_id, false);
        } elseif ($commsn_agnt_setting == 'cmsn_agnt') {
            $commission_agent = User::saleCommissionAgentsDropdown($business_id, false);
        }

        //If brands, category are enabled then send else false.
        $categories = (request()->session()->get('business.enable_category') == 1) ? Category::catAndSubCategories($business_id) : false;
        $brands = (request()->session()->get('business.enable_brand') == 1) ? Brands::forDropdown($business_id)
            ->prepend(__('lang_v1.all_brands'), 'all') : false;

        $change_return = $this->dummyPaymentLine;

        $types = [];
        if (auth()->user()->can('supplier.create')) {
            $types['supplier'] = __('report.supplier');
        }
        if (auth()->user()->can('customer.create')) {
            $types['customer'] = __('report.customer');
        }
        if (auth()->user()->can('supplier.create') && auth()->user()->can('customer.create')) {
            $types['both'] = __('lang_v1.both_supplier_customer');
        }
        $customer_groups = CustomerGroup::forDropdown($business_id);

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        $waiters = [];
        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id);
        }
        $redeem_details = [];
        if (request()->session()->get('business.enable_rp') == 1) {
            $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $transaction->contact_id);

            $redeem_details['points'] += $transaction->rp_redeemed;
            $redeem_details['points'] -= $transaction->rp_earned;
        }

        $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
        $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
        $shipping_statuses = $this->transactionUtil->shipping_statuses();

        $warranties = $this->productUtil->getWarrantiesForDropdown();
        $sub_type = request()->get('sub_type');

        //pos screen view from module
        $pos_module_data = $this->moduleUtil->getModuleData('get_pos_screen_view', ['sub_type' => $sub_type]);

        $invoice_schemes = [];
        $default_invoice_schemes = null;

        if ($transaction->status == 'draft') {
            $invoice_schemes = InvoiceScheme::forDropdown($business_id);
            $default_invoice_schemes = InvoiceScheme::getDefault($business_id);
        }

        $invoice_layouts = InvoiceLayout::forDropdown($business_id);

        $customer_due = $this->transactionUtil->getContactDue($transaction->contact_id, $transaction->business_id);

        $customer_due = $customer_due != 0 ? $this->transactionUtil->num_f($customer_due, true) : '';

        //Added check because $users is of no use if enable_contact_assign if false
        $users = config('constants.enable_contact_assign') ? User::forDropdown($business_id, false, false, false, true) : [];
        $only_payment = request()->segment(2) == 'payment';

        // Same screen as create — always respect Business Settings → POS UI (classic|touch)
        $edit = true;
        $default_location = $business_location;
        $business_locations = BusinessLocation::forDropdown($business_id, false, true);
        $bl_attributes = $business_locations['attributes'] ?? [];
        $business_locations = $business_locations['locations'] ?? $business_locations;

        $price_groups = SellingPriceGroup::forDropdown($business_id);
        $default_price_group_id = !empty($transaction->selling_price_group_id) && array_key_exists($transaction->selling_price_group_id, $price_groups)
            ? $transaction->selling_price_group_id
            : (!empty($default_location->selling_price_group_id) && array_key_exists($default_location->selling_price_group_id, $price_groups)
                ? $default_location->selling_price_group_id
                : null);

        $wholesale_enabled = \App\Utils\WholesaleUtil::isEnabled($business_id);
        $wholesale_price_group_id = $wholesale_enabled
            ? \App\Utils\WholesaleUtil::getPriceGroupId($business_id, true)
            : null;

        $types_of_service = [];
        if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
            $types_of_service = TypesOfService::forDropdown($business_id);
        }

        $default_datetime = $this->businessUtil->format_date('now', true);

        $last_sell = \App\Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->orderBy('created_at', 'desc')
            ->select('id', 'invoice_no', 'final_total', 'created_at')
            ->first();

        return view('sale_pos.create')
            ->with(compact(
                'edit',
                'pos_ui',
                'business_details',
                'taxes',
                'payment_types',
                'walk_in_customer',
                'sell_details',
                'transaction',
                'payment_lines',
                'location_printer_type',
                'shortcuts',
                'commission_agent',
                'categories',
                'pos_settings',
                'change_return',
                'types',
                'customer_groups',
                'brands',
                'accounts',
                'waiters',
                'redeem_details',
                'edit_price',
                'edit_discount',
                'shipping_statuses',
                'warranties',
                'sub_type',
                'pos_module_data',
                'invoice_schemes',
                'default_invoice_schemes',
                'invoice_layouts',
                'featured_products',
                'customer_due',
                'users',
                'only_payment',
                'default_location',
                'business_locations',
                'bl_attributes',
                'price_groups',
                'default_price_group_id',
                'wholesale_enabled',
                'wholesale_price_group_id',
                'types_of_service',
                'default_datetime',
                'last_sell'
            ));
    }

    /**
     * Update the specified resource in storage.
     * TODO: Add edit log.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (!auth()->user()->can('sell.update') && !auth()->user()->can('direct_sell.access') &&
            !auth()->user()->can('so.update') && !auth()->user()->can('edit_pos_payment')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            //status is send as quotation from edit sales screen.
            $input['is_quotation'] = 0;
            if ($input['status'] == 'quotation') {
                $input['status'] = 'draft';
                $input['is_quotation'] = 1;
                $input['sub_status'] = 'quotation';
            } elseif ($input['status'] == 'proforma') {
                $input['status'] = 'draft';
                $input['sub_status'] = 'proforma';
                $input['is_quotation'] = 0;
            } else {
                $input['sub_status'] = null;
                $input['is_quotation'] = 0;
            }

            $is_direct_sale = false;
            if (!empty($input['products'])) {
                //Get transaction value before updating.
                $transaction_before = Transaction::find($id);
                $status_before = $transaction_before->status;
                $rp_earned_before = $transaction_before->rp_earned;
                $rp_redeemed_before = $transaction_before->rp_redeemed;

                // If ZATCA module is installed and this transaction is successfully synced, prevent update
                $moduleUtil = new ModuleUtil();
                if ($moduleUtil->isModuleInstalled('ZatcaIntegrationKsa')) {
                    if (!empty($transaction_before) && $transaction_before->zatca_status === 'success') {
                        return back()->with('status', ['success' => 0,
                            'msg' => __('lang_v1.invoice_synced_to_zatca_cannot_be_edited')]);
                    }
                }

                if ($transaction_before->is_direct_sale == 1) {
                    $is_direct_sale = true;
                }

                $sales_order_ids = $transaction_before->sales_order_ids ?? [];

                //Add change return
                $change_return = $this->dummyPaymentLine;
                if (!empty($input['payment']['change_return'])) {
                    $change_return = $input['payment']['change_return'];
                    unset($input['payment']['change_return']);
                }

                //Check Customer credit limit
                $is_credit_limit_exeeded = $transaction_before->type == 'sell' ? $this->transactionUtil->isCustomerCreditLimitExeeded($input, $id) : false;

                if ($is_credit_limit_exeeded !== false) {
                    $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                    $output = ['success' => 0,
                        'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                    ];
                    if (!$is_direct_sale) {
                        return $output;
                    } else {
                        return redirect()
                            ->action([\App\Http\Controllers\SellController::class, 'index'])
                            ->with('status', $output);
                    }
                }

                //Check if there is a open register, if no then redirect to Create Register screen.
                if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                    return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create']);
                }

                $business_id = $request->session()->get('user.business_id');
                $user_id = $request->session()->get('user.id');
                $commsn_agnt_setting = $request->session()->get('business.sales_cmsn_agnt');

                $discount = ['discount_type' => $input['discount_type'],
                    'discount_amount' => $input['discount_amount'],
                ];
                $invoice_total = $this->productUtil->calculateInvoiceTotal($input['products'], $input['tax_rate_id'], $discount);

                if (!empty($request->input('transaction_date'))) {
                    $input['transaction_date'] = $this->productUtil->uf_date($request->input('transaction_date'), true);
                }

                $input['commission_agent'] = !empty($request->input('commission_agent')) ? $request->input('commission_agent') : null;
                if ($commsn_agnt_setting == 'logged_in_user') {
                    $input['commission_agent'] = $user_id;
                }

                if (isset($input['exchange_rate']) && $this->transactionUtil->num_uf($input['exchange_rate']) == 0) {
                    $input['exchange_rate'] = 1;
                }

                //Customer group details
                $contact_id = $request->get('contact_id', null);
                $cg = $this->contactUtil->getCustomerGroup($business_id, $contact_id);
                $input['customer_group_id'] = (empty($cg) || empty($cg->id)) ? null : $cg->id;

                //set selling price group id
                $price_group_id = $request->has('price_group') ? $request->input('price_group') : null;

                $input['is_suspend'] = isset($input['is_suspend']) && 1 == $input['is_suspend'] ? 1 : 0;
                if ($input['is_suspend']) {
                    $input['sale_note'] = !empty($input['additional_notes']) ? $input['additional_notes'] : null;
                }

                if ($status_before == 'draft' && !empty($request->input('invoice_scheme_id'))) {
                    $input['invoice_scheme_id'] = $request->input('invoice_scheme_id');
                }

                //Types of service
                if ($this->moduleUtil->isModuleEnabled('types_of_service')) {
                    $input['types_of_service_id'] = $request->input('types_of_service_id');
                    $price_group_id = !empty($request->input('types_of_service_price_group')) ? $request->input('types_of_service_price_group') : $price_group_id;
                    $input['packing_charge'] = !empty($request->input('packing_charge')) ?
                    $this->transactionUtil->num_uf($request->input('packing_charge')) : 0;
                    $input['packing_charge_type'] = $request->input('packing_charge_type');
                    $input['service_custom_field_1'] = !empty($request->input('service_custom_field_1')) ?
                    $request->input('service_custom_field_1') : null;
                    $input['service_custom_field_2'] = !empty($request->input('service_custom_field_2')) ?
                    $request->input('service_custom_field_2') : null;
                    $input['service_custom_field_3'] = !empty($request->input('service_custom_field_3')) ?
                    $request->input('service_custom_field_3') : null;
                    $input['service_custom_field_4'] = !empty($request->input('service_custom_field_4')) ?
                    $request->input('service_custom_field_4') : null;
                    $input['service_custom_field_5'] = !empty($request->input('service_custom_field_5')) ?
                    $request->input('service_custom_field_5') : null;
                    $input['service_custom_field_6'] = !empty($request->input('service_custom_field_6')) ?
                    $request->input('service_custom_field_6') : null;
                }

                $input['selling_price_group_id'] = $price_group_id;

                if ($this->transactionUtil->isModuleEnabled('tables')) {
                    $input['res_table_id'] = request()->get('res_table_id');
                }
                if ($this->transactionUtil->isModuleEnabled('service_staff')) {
                    $input['res_waiter_id'] = request()->get('res_waiter_id');
                }

                if ($this->transactionUtil->isModuleEnabled('kitchen')) {
                    $input['is_kitchen_order'] = request()->get('is_kitchen_order');
                }

                //upload document
                $document_name = $this->transactionUtil->uploadFile($request, 'sell_document', 'documents');
                if (!empty($document_name)) {
                    $input['document'] = $document_name;
                }

                if ($request->input('additional_expense_value_1') != '') {
                    $input['additional_expense_key_1'] = $request->input('additional_expense_key_1');
                    $input['additional_expense_value_1'] = $request->input('additional_expense_value_1');
                }

                if ($request->input('additional_expense_value_2') != '') {
                    $input['additional_expense_key_2'] = $request->input('additional_expense_key_2');
                    $input['additional_expense_value_2'] = $request->input('additional_expense_value_2');
                }

                if ($request->input('additional_expense_value_3') != '') {
                    $input['additional_expense_key_3'] = $request->input('additional_expense_key_3');
                    $input['additional_expense_value_3'] = $request->input('additional_expense_value_3');
                }

                if ($request->input('additional_expense_value_4') != '') {
                    $input['additional_expense_key_4'] = $request->input('additional_expense_key_4');
                    $input['additional_expense_value_4'] = $request->input('additional_expense_value_4');
                }
                $only_payment = !$is_direct_sale && !auth()->user()->can('sell.update') && auth()->user()->can('edit_pos_payment');

                //if edit pos not allowed and only edit payment allowed
                if ($only_payment) {
                    DB::beginTransaction();
                    $this->onlyUpdatePayment($transaction_before, $input);
                    DB::commit();

                    $can_print_invoice = auth()->user()->can('print_invoice');
                    $invoice_layout_id = $request->input('invoice_layout_id');

                    $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction_before->id, null, false, true, $invoice_layout_id);
                    $msg = trans('purchase.payment_updated_success');

                    $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];

                    return $output;
                }

                //Begin transaction
                DB::beginTransaction();

                $transaction = $this->transactionUtil->updateSellTransaction($id, $business_id, $input, $invoice_total, $user_id);

                //update service staff timer
                if (!$is_direct_sale && $transaction->status == 'final') {
                    foreach ($input['products'] as $product_line) {
                        if (!empty($product_line['res_service_staff_id'])) {
                            $product = Product::find($product_line['product_id']);

                            if (!empty($product->preparation_time_in_minutes)) {
                                //if quantity not increase skip line
                                $quantity = $this->transactionUtil->num_uf($product_line['quantity']);
                                if (!empty($product_line['transaction_sell_lines_id'])) {
                                    $sl = TransactionSellLine::find($product_line['transaction_sell_lines_id']);

                                    if ($sl->quantity >= $quantity && $sl->res_service_staff_id == $product_line['res_service_staff_id']) {
                                        continue;
                                    }

                                    //if same service staff assigned quantity is only increased quantity
                                    if ($sl->res_service_staff_id == $product_line['res_service_staff_id']) {
                                        $quantity = $quantity - $sl->quantity;
                                    }
                                }

                                $service_staff = User::find($product_line['res_service_staff_id']);

                                $base_time = \Carbon::parse($transaction->transaction_date);
                                //is transaction date is past take base time as now
                                if ($base_time->lt(\Carbon::now())) {
                                    $base_time = \Carbon::now();
                                }

                                //if already assigned set base time as available_at
                                if (!empty($service_staff->available_at) && \Carbon::parse($service_staff->available_at)->gt(\Carbon::now())) {
                                    $base_time = \Carbon::parse($service_staff->available_at);
                                }

                                $total_minutes = $product->preparation_time_in_minutes * $quantity;

                                $service_staff->available_at = $base_time->addMinutes($total_minutes);
                                $service_staff->save();
                            }
                        }
                    }
                }

                //Update Sell lines
                $deleted_lines = $this->transactionUtil->createOrUpdateSellLines($transaction, $input['products'], $input['location_id'], true, $status_before);

                // Draft → final: apply trade-ins once (skip if already recorded)
                if ($input['status'] == 'final'
                    && ! \App\PosTradeIn::where('transaction_id', $transaction->id)->where('status', 'completed')->exists()) {
                    try {
                        (new \App\Utils\TradeInUtil())->processTradeInsForSale(
                            $transaction->fresh(['sell_lines']),
                            $input['products'],
                            $business_id,
                            $request->session()->get('user.id'),
                            true
                        );
                    } catch (\Exception $tiEx) {
                        \Log::emergency('Trade-in process (update): '.$tiEx->getMessage());
                        throw $tiEx;
                    }
                }

                app(\App\Utils\MobileShopUtil::class)->syncImeisFromSale($transaction, $input['products'], $input['location_id']);

                //Update update lines
                $is_credit_sale = isset($input['is_credit_sale']) && $input['is_credit_sale'] == 1 ? true : false;

                $new_sales_order_ids = $transaction->sales_order_ids ?? [];
                $sales_order_ids = array_unique(array_merge($sales_order_ids, $new_sales_order_ids));

                if (!empty($sales_order_ids)) {
                    $this->transactionUtil->updateSalesOrderStatus($sales_order_ids);
                }

                if (!$transaction->is_suspend && !$is_credit_sale) {
                    //Add change return
                    $change_return['amount'] = $input['change_return'] ?? 0;
                    $change_return['is_return'] = 1;
                    if (!empty($input['change_return_id'])) {
                        $change_return['payment_id'] = $input['change_return_id'];
                    }
                    $input['payment'][] = $change_return;

                    if (!$is_direct_sale || auth()->user()->can('sell.payments')) {
                        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);

                        //Update cash register
                        if (!$is_direct_sale) {
                            $this->cashRegisterUtil->updateSellPayments($status_before, $transaction, $input['payment']);
                        }
                    }
                }

                if ($request->session()->get('business.enable_rp') == 1) {
                    $this->transactionUtil->updateCustomerRewardPoints($contact_id, $transaction->rp_earned, $rp_earned_before, $transaction->rp_redeemed, $rp_redeemed_before);
                }

                Media::uploadMedia($business_id, $transaction, $request, 'shipping_documents', false, 'shipping_document');

                if ($transaction->type == 'sell') {

                    //Update payment status
                    $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                    $transaction->payment_status = $payment_status;

                    //Update product stock
                    $this->productUtil->adjustProductStockForInvoice($status_before, $transaction, $input);

                    //Allocate the quantity from purchase and add mapping of
                    //purchase & sell lines in
                    //transaction_sell_lines_purchase_lines table
                    $business_details = $this->businessUtil->getDetails($business_id);
                    $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

                    $business = ['id' => $business_id,
                        'accounting_method' => $request->session()->get('business.accounting_method'),
                        'location_id' => $input['location_id'],
                        'pos_settings' => $pos_settings,
                    ];
                    $this->transactionUtil->adjustMappingPurchaseSell($status_before, $transaction, $business, $deleted_lines);

                    //Auto send notification
                    $whatsapp_link = $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
                    $this->notificationUtil->autoSendEbill($business_id, $transaction, $transaction->contact);

                    // zatca instant sync if status final type sell
                    if($input['status'] == 'final'){
                        $this->moduleUtil->getModuleData('after_sales', ['transaction' => $transaction]);
                    }

                }

                $log_properties = [];
                if (isset($input['repair_completed_on'])) {
                    $completed_on = !empty($input['repair_completed_on']) ? $this->transactionUtil->uf_date($input['repair_completed_on'], true) : null;
                    if ($transaction->repair_completed_on != $completed_on) {
                        $log_properties['completed_on_from'] = $transaction->repair_completed_on;
                        $log_properties['completed_on_to'] = $completed_on;
                    }
                }

                $this->moduleUtil->getModuleData('after_sale_saved', ['transaction' => $transaction, 'input' => $input]);

                Media::uploadMedia($business_id, $transaction, $request, 'documents');

                $this->transactionUtil->activityLog($transaction, 'edited', $transaction_before);

                SellCreatedOrModified::dispatch($transaction);

                DB::commit();

                if ($request->input('is_save_and_print') == 1) {
                    $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);

                    return redirect()->to($url . '?print_on_load=true');
                }

                $msg = __('lang_v1.updated_success');
                $receipt = '';
                $can_print_invoice = auth()->user()->can('print_invoice');
                $invoice_layout_id = $request->input('invoice_layout_id');

                if ($input['status'] == 'draft' && $input['is_quotation'] == 0) {
                    $msg = trans('sale.draft_added');
                } elseif ($input['status'] == 'draft' && $input['is_quotation'] == 1) {
                    $msg = trans('lang_v1.quotation_updated');
                    if (!$is_direct_sale && $can_print_invoice) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                    } else {
                        $receipt = '';
                    }
                } elseif ($input['status'] == 'final') {
                    $msg = trans('sale.pos_sale_updated');
                    if (!$is_direct_sale && $can_print_invoice) {
                        $receipt = $this->receiptContent($business_id, $input['location_id'], $transaction->id, null, false, true, $invoice_layout_id);
                    } else {
                        $receipt = '';
                    }
                }

                $output = ['success' => 1, 'msg' => $msg, 'receipt' => $receipt];

                if (!empty($whatsapp_link)) {
                    $output['whatsapp_link'] = $whatsapp_link;
                }
            } else {
                $output = ['success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = ['success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if (!$is_direct_sale) {
            return $output;
        } else {
            if ($input['status'] == 'draft') {
                if (isset($input['is_quotation']) && $input['is_quotation'] == 1) {
                    return redirect()
                        ->action([\App\Http\Controllers\SellController::class, 'getQuotations'])
                        ->with('status', $output);
                } else {
                    return redirect()
                        ->action([\App\Http\Controllers\SellController::class, 'getDrafts'])
                        ->with('status', $output);
                }
            } else {
                if (!empty($transaction->sub_type) && $transaction->sub_type == 'repair') {
                    return redirect()
                        ->action([\Modules\Repair\Http\Controllers\RepairController::class, 'index'])
                        ->with('status', $output);
                }

                if ($transaction->type == 'sales_order') {
                    return redirect()
                        ->action([\App\Http\Controllers\SalesOrderController::class, 'index'])
                        ->with('status', $output);
                }

                return redirect()
                    ->action([\App\Http\Controllers\SellController::class, 'index'])
                    ->with('status', $output);
            }
        }
    }

    /**
     * Function to add/edit payments for a pos sale
     */
    private function onlyUpdatePayment($transaction, $input)
    {
        //Add change return
        $change_return = $this->dummyPaymentLine;
        if (!empty($input['payment']['change_return'])) {
            $change_return = $input['payment']['change_return'];
            unset($input['payment']['change_return']);
        }

        //Add change return
        $change_return['amount'] = $input['change_return'] ?? 0;
        $change_return['is_return'] = 1;
        if (!empty($input['change_return_id'])) {
            $change_return['payment_id'] = $input['change_return_id'];
        }
        $input['payment'][] = $change_return;
        $this->transactionUtil->createOrUpdatePaymentLines($transaction, $input['payment']);
        $this->cashRegisterUtil->updateSellPayments($transaction->status, $transaction, $input['payment']);

        //Update payment status
        $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
        $transaction_before = $transaction;
        $transaction->payment_status = $payment_status;

        if ($payment_status == 'paid') {
            $transaction->is_suspend = 0;
            $transaction->save();
        }

        $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('sell.delete') && !auth()->user()->can('direct_sell.delete') && !auth()->user()->can('so.delete')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                //Begin transaction
                DB::beginTransaction();

                // Reverse trade-in stock before sale delete removes lines
                $txn_for_ti = Transaction::where('business_id', $business_id)->find($id);
                if ($txn_for_ti) {
                    (new \App\Utils\TradeInUtil())->reverseTradeInsForSale($txn_for_ti);
                }

                $output = $this->transactionUtil->deleteSale($business_id, $id);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output['success'] = false;
                $output['msg'] = trans('messages.something_went_wrong');
            }

            return $output;
        }
    }

    public function getSalesOrderLines()
    {
        $business_id = request()->session()->get('user.business_id');
        $sales_order_id = request()->input('sales_order_id');
        $row_count = request()->get('product_row');
        $is_serial_no = request()->get('is_serial_no');
        $row_count = $row_count + 1;

        $sales_order = Transaction::where('business_id', $business_id)
            ->where('type', 'sales_order')
            ->with(['sell_lines'])
            ->find($sales_order_id);

        $html = '<table>';

        if (!empty($sales_order)) {
            foreach ($sales_order->sell_lines as $sell_line) {
                $quantity = $sell_line->quantity - $sell_line->so_quantity_invoiced;
                $sell_line->qty_available = $quantity;
                $sell_line->formatted_qty_available = $this->transactionUtil->num_f($quantity);
                $sell_line_row = $this->productUtil->getSellLineRow($sell_line->variation_id, $sales_order->location_id, $quantity, $row_count, true, $is_serial_no, $sell_line);
                $html .= $sell_line_row['html_content'];
                $row_count++;
            }
        }
        $html .= '</table>';

        return [
            'html' => $html,
            'sales_order' => $sales_order,
        ];
    }

    /**
     * Returns the HTML row for a product in POS
     *
     * @param  int  $variation_id
     * @param  int  $location_id
     * @return \Illuminate\Http\Response
     */
    public function getProductRow($variation_id, $location_id)
    {
        // Delegate to ProductUtil - all business logic is there
        return $this->productUtil->getPosProductRow($variation_id, $location_id);
    }

    /**
     * Returns the HTML row for a payment in POS
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getPaymentRow(Request $request)
    {
        $business_id = request()->session()->get('user.business_id');

        $row_index = $request->input('row_index');
        $location_id = $request->input('location_id');
        $removable = true;
        $payment_types = $this->productUtil->payment_types($location_id, true);
        $payment_types = $this->withLoyaltyPaymentType($payment_types);

        $payment_line = $this->dummyPaymentLine;

        //Accounts
        $accounts = [];
        if ($this->moduleUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false, true);
        }

        return view('sale_pos.partials.payment_row')
            ->with(compact('payment_types', 'row_index', 'removable', 'payment_line', 'accounts'));
    }

    /**
     * Returns recent transactions
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getRecentTransactions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $transaction_status = $request->get('status');

        $register = $this->cashRegisterUtil->getCurrentCashRegister($user_id);

        $query = Transaction::where('business_id', $business_id)
            ->where('transactions.created_by', $user_id)
            ->where('transactions.type', 'sell')
            ->where('is_direct_sale', 0);

        if ($transaction_status == 'final') {
            //Commented as credit sales not showing
            // if (!empty($register->id)) {
            //     $query->leftjoin('cash_register_transactions as crt', 'transactions.id', '=', 'crt.transaction_id')
            //     ->where('crt.cash_register_id', $register->id);
            // }
        }

        if ($transaction_status == 'quotation') {
            $query->where('transactions.status', 'draft')
                ->where('sub_status', 'quotation');
        } elseif ($transaction_status == 'draft') {
            $query->where('transactions.status', 'draft')
                ->whereNull('sub_status');
        } else {
            $query->where('transactions.status', $transaction_status);
        }

        $transaction_sub_type = $request->get('transaction_sub_type');
        if (!empty($transaction_sub_type)) {
            $query->where('transactions.sub_type', $transaction_sub_type);
        } else {
            $query->where('transactions.sub_type', null);
        }

        // Retrieve the limit for displaying recent transactions from the configuration
        $limit = config('constants.pos_recent_transactions_display_limit', 10);
        
        $transactions = $query->orderBy('transactions.created_at', 'desc')
            ->groupBy('transactions.id')
            ->select('transactions.*')
            ->with(['contact', 'table'])
            ->limit($limit)
            ->get();

        return view('sale_pos.partials.recent_transactions')
            ->with(compact('transactions', 'transaction_sub_type'));
    }

    /**
     * Prints invoice for sell
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function printInvoice(Request $request, $transaction_id)
    {
        if (request()->ajax()) {
            try {
                $output = ['success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];

                $business_id = $request->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $transaction_id)
                    ->with(['location'])
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $printer_type = 'browser';
                if (!empty(request()->input('check_location')) && request()->input('check_location') == true) {
                    $printer_type = $transaction->location->receipt_printer_type;
                }

                $is_package_slip = !empty($request->input('package_slip')) ? true : false;
                $is_delivery_note = !empty($request->input('delivery_note')) ? true : false;

                $invoice_layout_id = $transaction->is_direct_sale ? $transaction->location->sale_invoice_layout_id : null;
                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, $printer_type, $is_package_slip, false, $invoice_layout_id, $is_delivery_note);

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Throwable $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

                $output = ['success' => 0,
                    'msg' => config('app.debug') ? $e->getMessage() : trans('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Gives suggetion for product based on category
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getProductSuggestion(Request $request)
    {
        if ($request->ajax()) {
            $category_id = $request->get('category_id');
            $brand_id = $request->get('brand_id');
            $location_id = $request->get('location_id');
            $term = $request->get('term');

            $check_qty = false;
            $business_id = $request->session()->get('user.business_id');
            $business = $request->session()->get('business');
            $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

            $products = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
                ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                ->join('units as u', 'p.unit_id', '=', 'u.id')
                ->leftJoin('brands', function ($join) {
                    $join->on('p.brand_id', '=', 'brands.id')
                        ->whereNull('brands.deleted_at');
                })
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (!empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });
                        }
                    }
                )
                ->where('p.business_id', $business_id)
                ->where('p.type', '!=', 'modifier')
                ->where('p.is_inactive', 0)
                ->where('p.not_for_selling', 0)
                // Hide internal tyre-shop quick-service products from the product grid
                ->where(function ($q) {
                    $q->whereNull('p.sku')
                        ->orWhere('p.sku', 'not like', 'QPOS-SVC-%');
                })
            //Hide products not available in the selected location
                ->where(function ($q) use ($location_id) {
                    $q->where('pl.location_id', $location_id);
                });

            //Include search
            if (!empty($term)) {
                $this->productUtil->applyPosProductTextSearch($products, $term, [
                    'product_alias' => 'p',
                    'variation_alias' => 'variations',
                    'search_fields' => ['name', 'sku', 'sub_sku'],
                    'search_type' => 'like',
                    'has_tyre_size' => \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size'),
                    'has_tyre_note' => \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_note'),
                    'include_brand' => true,
                ]);
            }

            //Include check for quantity
            if ($check_qty) {
                $products->where('VLD.qty_available', '>', 0);
            }

            if (!empty($category_id) && ($category_id != 'all')) {
                $products->where(function ($query) use ($category_id) {
                    $query->where('p.category_id', $category_id);
                    $query->orWhere('p.sub_category_id', $category_id);
                });
            }
            if (!empty($brand_id) && ($brand_id != 'all')) {
                $products->where('p.brand_id', $brand_id);
            }

            if (!empty($request->get('is_enabled_stock'))) {
                $is_enabled_stock = 0;
                if ($request->get('is_enabled_stock') == 'product') {
                    $is_enabled_stock = 1;
                }

                $products->where('p.enable_stock', $is_enabled_stock);
            }

            if (!empty($request->get('repair_model_id'))) {
                $products->where('p.repair_model_id', $request->get('repair_model_id'));
            }

            $for_pop = $request->get('for_pop') == '1' || $request->get('for_pop') == 'true';
            // POP product tiles: show purchase/AVCO cost (dpp_inc_tax), not sell price
            $price_col = $for_pop
                ? 'variations.dpp_inc_tax as selling_price'
                : 'variations.default_sell_price as selling_price';

            $products = $products->select(
                'p.id as product_id',
                'p.name',
                'p.tyre_size',
                'p.type',
                'p.enable_stock',
                'p.image as product_image',
                'variations.id',
                'variations.name as variation',
                'VLD.qty_available',
                DB::raw($price_col),
                'variations.sub_sku',
                'u.short_name as unit',
                DB::raw('(SELECT purchase_price_inc_tax FROM purchase_lines WHERE variation_id = variations.id ORDER BY id DESC LIMIT 1) as last_purchased_price')
            )
                ->with(['media', 'group_prices'])
                ->orderBy('p.name', 'asc')
                ->paginate(50);

            $price_groups = SellingPriceGroup::where('business_id', $business_id)->active()->pluck('name', 'id');

            $allowed_group_prices = [];
            foreach ($price_groups as $key => $value) {
                if (auth()->user()->can('selling_price_group.' . $key)) {
                    $allowed_group_prices[$key] = $value;
                }
            }

            $show_prices = !empty($pos_settings['show_pricing_on_product_sugesstion']) || $for_pop;
            $active_price_group_id = $for_pop ? null : $request->get('price_group');

            // Keep retail baseline on each tile, then optionally swap display to group price.
            if (! $for_pop) {
                foreach ($products as $product) {
                    $product->retail_price_baseline = $product->selling_price;
                    if (! empty($active_price_group_id) && (int) $active_price_group_id > 0) {
                        foreach ($product->group_prices as $group_price) {
                            if ((int) $group_price->price_group_id === (int) $active_price_group_id) {
                                $calc = $group_price->calculated_price;
                                if ($calc !== null && $calc !== '') {
                                    $product->selling_price = $calc;
                                }
                                break;
                            }
                        }
                    }
                }
            }

            return view('sale_pos.partials.product_list')
                ->with(compact('products', 'allowed_group_prices', 'show_prices', 'for_pop', 'active_price_group_id', 'term'));
        }
    }

    /**
     * Category / brand options for POS Exchange / Trade-In modal.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTradeInOptions(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        if (! (new \App\Utils\TyreShopUtil())->moduleEnabled()) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.tyre_shop_module_disabled'),
            ], 403);
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');

        try {
            $util = new \App\Utils\TradeInUtil();
            $opts = $util->getFormOptions($business_id, $user_id);

            return response()->json(array_merge(['success' => 1], $opts));
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * Ensure tyre-shop quick service products exist and return variation IDs for POS.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTyreShopServices(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        if (! (new \App\Utils\TyreShopUtil())->moduleEnabled()) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.tyre_shop_module_disabled'),
            ], 403);
        }

        $business_id = $request->session()->get('user.business_id');
        $user_id = $request->session()->get('user.id');
        $location_id = (int) $request->get('location_id');

        if (empty($location_id)) {
            return response()->json([
                'success' => 0,
                'msg' => 'Select a business location',
            ]);
        }

        try {
            $util = new \App\Utils\TyreShopServiceUtil();
            $services = $util->getServicesForPos($business_id, $user_id, $location_id);

            return response()->json([
                'success' => 1,
                'services' => $services,
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            return response()->json([
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ]);
        }
    }

    /**
     * POS bulk product search (name / SKU) with cost + available qty.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkProductSearch(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        $term = trim((string) $request->get('term', ''));

        if (empty($location_id)) {
            return response()->json(['products' => []]);
        }

        $can_view_cost = auth()->user()->can('view_purchase_price');

        $has_tyre_size = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size');
        $has_tyre_note = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_note');

        $query = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
            ->join('units as u', 'p.unit_id', '=', 'u.id')
            ->leftJoin('brands', function ($join) {
                $join->on('p.brand_id', '=', 'brands.id')
                    ->whereNull('brands.deleted_at');
            })
            ->leftJoin('variation_location_details as VLD', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'VLD.variation_id')
                    ->where('VLD.location_id', '=', $location_id);
            })
            ->where('p.business_id', $business_id)
            ->where('p.type', '!=', 'modifier')
            ->where('p.is_inactive', 0)
            ->where('p.not_for_selling', 0)
            ->where('pl.location_id', $location_id)
            ->where(function ($q) {
                $q->whereNull('p.sku')
                    ->orWhere('p.sku', 'not like', 'QPOS-SVC-%');
            });

        if ($term !== '') {
            $this->productUtil->applyPosProductTextSearch($query, $term, [
                'product_alias' => 'p',
                'variation_alias' => 'variations',
                'search_fields' => ['name', 'sku', 'sub_sku'],
                'search_type' => 'like',
                'has_tyre_size' => $has_tyre_size,
                'has_tyre_note' => $has_tyre_note,
                'include_brand' => true,
            ]);
        }

        $select = [
            'p.id as product_id',
            'p.name',
            $has_tyre_size ? 'p.tyre_size' : DB::raw('NULL as tyre_size'),
            'p.type',
            'p.enable_stock',
            'variations.id as variation_id',
            'variations.name as variation_name',
            'variations.sub_sku',
            'variations.default_sell_price as sell_price',
            'VLD.qty_available',
            'u.short_name as unit',
        ];

        if ($can_view_cost) {
            $select[] = 'variations.default_purchase_price as cost_price';
            $select[] = DB::raw('(SELECT purchase_price_inc_tax FROM purchase_lines WHERE variation_id = variations.id ORDER BY id DESC LIMIT 1) as last_purchased_price');
        }

        $for_pop = $request->get('for_pop') == '1' || $request->get('for_pop') == 'true'
            || $request->get('is_pop') == '1';

        $rows = $query->select($select)
            ->orderBy('p.name', 'asc')
            ->limit(40)
            ->get();

        $products = $rows->map(function ($row) use ($can_view_cost, $for_pop) {
            $name = \App\Product::formatNameWithTyreSize($row->name, $row->tyre_size ?? null);
            if ($row->type === 'variable' && ! empty($row->variation_name) && $row->variation_name !== 'DUMMY') {
                $name .= ' - '.$row->variation_name;
            }

            $item = [
                'variation_id' => (int) $row->variation_id,
                'name' => $name,
                'sku' => $row->sub_sku,
                'sell_price' => (float) $row->sell_price,
                'qty_available' => $row->enable_stock ? (float) ($row->qty_available ?? 0) : null,
                'enable_stock' => (int) $row->enable_stock,
                'unit' => $row->unit,
            ];

            if ($can_view_cost) {
                $last = $row->last_purchased_price !== null ? (float) $row->last_purchased_price : null;
                $cost = (float) ($row->cost_price ?? 0);
                // POP: prefer last purchase price for cost column / modal default
                if ($for_pop && $last !== null && $last > 0) {
                    $item['cost_price'] = $last;
                } else {
                    $item['cost_price'] = $cost;
                }
                $item['last_purchase_price'] = $last;
            }

            return $item;
        })->values();

        return response()->json([
            'products' => $products,
            'can_view_cost' => $can_view_cost,
            'for_pop' => (bool) $for_pop,
        ]);
    }

    /**
     * Shows invoice url.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showInvoiceUrl($id)
    {
        // if (!auth()->user()->can('sell.update')) {
        //     abort(403, 'Unauthorized action.');
        // }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->findorfail($id);
            $url = $this->transactionUtil->getInvoiceUrl($id, $business_id);

            return view('sale_pos.partials.invoice_url_modal')
                ->with(compact('transaction', 'url'));
        }
    }

    /**
     * Shows invoice to guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function showInvoice($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business', 'location'])->first();

        if (!empty($transaction)) {
            $invoice_layout_id = $transaction->is_direct_sale ? $transaction->location->sale_invoice_layout_id : null;

            $receipt = $this->receiptContent($transaction->business_id, $transaction->location_id, $transaction->id, 'browser', false, false, $invoice_layout_id);
            $pos_settings = empty($transaction->business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($transaction->business->pos_settings, true);
            $payment_link = '';
            if (!empty($pos_settings['enable_payment_link']) && $transaction->payment_status != 'paid') {
                $payment_link = $this->transactionUtil->getInvoicePaymentLink($transaction->id, $transaction->business_id);
            }

            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;

            $common_settings = !empty($transaction->business->common_settings) ? $transaction->business->common_settings : [];
            $ebill_settings = !empty($common_settings['ebill_settings']) ? $common_settings['ebill_settings'] : [];

            return view('sale_pos.partials.show_invoice')
                ->with(compact('receipt', 'title', 'payment_link', 'ebill_settings'));
        } else {
            exit(__('messages.something_went_wrong'));
        }
    }

    /**
     * Allows payment for the invoice by guest user.
     *
     * @param  string  $token
     * @return \Illuminate\Http\Response
     */
    public function invoicePayment($token)
    {
        $transaction = Transaction::where('invoice_token', $token)->with(['business', 'contact', 'location'])->first();
        $business = $transaction->business;
        $business_details = $this->businessUtil->getDetails($business->id);
        $pos_settings = empty($business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business->pos_settings, true);

        if (!empty($transaction) && $transaction->status == 'final' && !empty($pos_settings['enable_payment_link'])) {
            $title = $transaction->business->name . ' | ' . $transaction->invoice_no;
            $paid_amount = $this->transactionUtil->getTotalPaid($transaction->id);
            $total_payable = $transaction->final_total - $paid_amount;

            $total_payable_formatted = $this->transactionUtil->num_f($total_payable, true, $business_details);
            $date_formatted = $this->transactionUtil->format_date($transaction->transaction_date, true, $business_details);
            $total_amount = $this->transactionUtil->num_f($transaction->final_total, true, $business_details);
            $total_paid = $this->transactionUtil->num_f($paid_amount, true, $business_details);

            return view('sale_pos.partials.guest_payment_form')
                ->with(compact('transaction', 'title', 'pos_settings', 'total_payable', 'total_payable_formatted', 'date_formatted', 'total_amount', 'total_paid', 'business_details'));
        } else {
            exit(__('messages.something_went_wrong'));
        }
    }

    public function pay_razorpay($transaction, $total_payable, $request)
    {
        $pos_settings = empty($transaction->business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($transaction->business->pos_settings, true);

        $razorpay_payment_id = $request->razorpay_payment_id;
        $razorpay_api = new Api($pos_settings['razor_pay_key_id'], $pos_settings['razor_pay_key_secret']);
        $payment = $razorpay_api->payment->fetch($razorpay_payment_id)->capture(['amount' => $total_payable * 100]); // Captures a payment

        if (empty($payment->error_code)) {
            return $payment->id;
        } else {
            $error_description = $payment->error_description;

            \Log::emergency($payment->error_description);
            throw new \Exception($error_description);
        }
    }

    public function pay_stripe($transaction, $total_payable, $request)
    {
        $pos_settings = empty($transaction->business->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($transaction->business->pos_settings, true);

        Stripe::setApiKey($pos_settings['stripe_secret_key']);

        $metadata = ['stripe_email' => $request->stripeEmail];

        $business_details = $this->businessUtil->getDetails($transaction->business->id);

        $charge = Charge::create([
            'amount' => $total_payable * 100,
            'currency' => strtolower($business_details->currency_code),
            'source' => $request->stripeToken,
            'metadata' => $metadata,
        ]);

        return $charge->id;
    }

    public function confirmPayment($id, Request $request)
    {
        try {
            $transaction = Transaction::with(['business'])->find($id);

            $transaction_before = $transaction->replicate();

            $payment_link = $this->transactionUtil->getInvoicePaymentLink($transaction->id, $transaction->business_id);

            $paid_amount = $this->transactionUtil->getTotalPaid($transaction->id);
            $total_payable = $transaction->final_total - $paid_amount;

            $pay_function = 'pay_' . $request->gateway;

            $payment_id = $this->$pay_function($transaction, $total_payable, $request);

            if (!empty($payment_id)) {
                DB::beginTransaction();
                $ref_count = $this->transactionUtil->setAndGetReferenceCount('sell_payment', $transaction->business_id);
                $payment_ref_no = $this->transactionUtil->generateReferenceNumber('sell_payment', $ref_count, $transaction->business_id);

                $data = [
                    'paid_on' => \Carbon::now()->toDateTimeString(),
                    'transaction_id' => $transaction->id,
                    'amount' => $total_payable,
                    'payment_for' => $transaction->contact_id,
                    'method' => 'cash',
                    'note' => $payment_id,
                    'paid_through_link' => 1,
                    'gateway' => $request->gateway,
                    'business_id' => $transaction->business_id,
                    'payment_ref_no' => $payment_ref_no,
                ];

                $tp = TransactionPayment::create($data);

                $payment_status = $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);
                $transaction->payment_status = $payment_status;

                $this->transactionUtil->activityLog($transaction, 'payment_edited', $transaction_before);
                DB::commit();

                $output = [
                    'success' => 1,
                    'msg' => __('purchase.payment_added_success'),
                ];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return redirect($payment_link)->with('status', $output);
    }

    /**
     * Display a listing of the recurring invoices.
     *
     * @return \Illuminate\Http\Response
     */
    public function listSubscriptions()
    {
        if (!auth()->user()->can('sell.view') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('transaction_payments as tp', 'transactions.id', '=', 'tp.transaction_id')
                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell')
                ->where('transactions.status', 'final')
                ->where('transactions.is_recurring', 1)
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.is_direct_sale',
                    'transactions.invoice_no',
                    'contacts.name',
                    'transactions.subscription_no',
                    'bl.name as business_location',
                    'transactions.recur_parent_id',
                    'transactions.recur_stopped_on',
                    'transactions.is_recurring',
                    'transactions.recur_interval',
                    'transactions.recur_interval_type',
                    'transactions.recur_repetitions',
                    'transactions.subscription_repeat_on'
                )->with(['subscription_invoices']);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }
            if (!empty(request()->contact_id)) {
                $sells->where('transactions.contact_id', request()->contact_id);
            }
            $datatable = Datatables::of($sells)
                ->addColumn(
                    'action',
                    function ($row) {
                        $html = '';

                        if ($row->is_recurring == 1 && auth()->user()->can('sell.update')) {
                            $link_text = !empty($row->recur_stopped_on) ? __('lang_v1.start_subscription') : __('lang_v1.stop_subscription');
                            $link_class = !empty($row->recur_stopped_on) ? 'btn-success' : 'btn-danger';

                            $html .= '<a href="' . action([\App\Http\Controllers\SellPosController::class, 'toggleRecurringInvoices'], [$row->id]) . '" class="toggle_recurring_invoice btn btn-xs ' . $link_class . '"><i class="fa fa-power-off"></i> ' . $link_text . '</a>';

                            if ($row->is_direct_sale == 0) {
                                $html .= '<a target="_blank" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary" href="' . action([\App\Http\Controllers\SellPosController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a>';
                            } else {
                                $html .= '<a target="_blank" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary" href="' . action([\App\Http\Controllers\SellController::class, 'edit'], [$row->id]) . '"><i class="glyphicon glyphicon-edit"></i> ' . __('messages.edit') . '</a>';
                            }

                            if (auth()->user()->can('direct_sell.delete') || auth()->user()->can('sell.delete')) {
                                $html .= '&nbsp;<a href="' . action([\App\Http\Controllers\SellPosController::class, 'destroy'], [$row->id]) . '" class="delete-sale tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error"><i class="fas fa-trash"></i> ' . __('messages.delete') . '</a>';
                            }
                        }

                        return $html;
                    }
                )
                ->removeColumn('id')
                ->editColumn('transaction_date', '{{@format_date($transaction_date)}}')
                ->editColumn('recur_interval', function ($row) {
                    $type = $row->recur_interval == 1 ? Str::singular(__('lang_v1.' . $row->recur_interval_type)) : __('lang_v1.' . $row->recur_interval_type);
                    $recur_interval = $row->recur_interval . $type;

                    if ($row->recur_interval_type == 'months' && !empty($row->subscription_repeat_on)) {
                        $recur_interval .= '<br><small class="text-muted">' .
                        __('lang_v1.repeat_on') . ': ' . str_ordinal($row->subscription_repeat_on);
                    }

                    return $recur_interval;
                })
                ->editColumn('recur_repetitions', function ($row) {
                    return !empty($row->recur_repetitions) ? $row->recur_repetitions : '-';
                })
                ->addColumn('subscription_invoices', function ($row) {
                    $invoices = [];
                    if (!empty($row->subscription_invoices)) {
                        $invoices = $row->subscription_invoices->pluck('invoice_no')->toArray();
                    }

                    $html = '';
                    $count = 0;
                    if (!empty($invoices)) {
                        $imploded_invoices = '<span class="label bg-info">' . implode('</span>, <span class="label bg-info">', $invoices) . '</span>';
                        $count = count($invoices);
                        $html .= '<small>' . $imploded_invoices . '</small>';
                    }
                    if ($count > 0) {
                        $html .= '<br><small class="text-muted">' .
                        __('sale.total') . ': ' . $count . '</small>';
                    }

                    return $html;
                })
                ->addColumn('last_generated', function ($row) {
                    if (!empty($row->subscription_invoices)) {
                        $last_generated_date = $row->subscription_invoices->max('created_at');
                    }

                    return !empty($last_generated_date) ? $last_generated_date->diffForHumans() : '';
                })
                ->addColumn('upcoming_invoice', function ($row) {
                    if (empty($row->recur_stopped_on)) {
                        $last_generated = !empty(count($row->subscription_invoices))?\Carbon::parse($row->subscription_invoices->max('transaction_date')) : \Carbon::parse($row->transaction_date);
                        $last_generated_string = $last_generated->format('Y-m-d');
                        $last_generated = \Carbon::parse($last_generated_string);

                        if ($row->recur_interval_type == 'days') {
                            $upcoming_invoice = $last_generated->addDays($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'months') {
                            if (!empty($row->subscription_repeat_on)) {
                                $last_generated_string = $last_generated->format('Y-m');
                                $last_generated = \Carbon::parse($last_generated_string . '-' . $row->subscription_repeat_on);
                            }

                            $upcoming_invoice = $last_generated->addMonths($row->recur_interval);
                        } elseif ($row->recur_interval_type == 'years') {
                            $upcoming_invoice = $last_generated->addYears($row->recur_interval);
                        }
                    }

                    return !empty($upcoming_invoice) ? $this->transactionUtil->format_date($upcoming_invoice) : '';
                })
                ->rawColumns(['action', 'subscription_invoices', 'recur_interval'])
                ->make(true);

            return $datatable;
        }

        return view('sale_pos.subscriptions');
    }

    /**
     * Starts or stops a recurring invoice.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function toggleRecurringInvoices($id)
    {
        if (!auth()->user()->can('sell.create')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');
            $transaction = Transaction::where('business_id', $business_id)
                ->where('type', 'sell')
                ->where('is_recurring', 1)
                ->findorfail($id);

            if (empty($transaction->recur_stopped_on)) {
                $transaction->recur_stopped_on = \Carbon::now();
            } else {
                $transaction->recur_stopped_on = null;
            }
            $transaction->save();

            $output = ['success' => 1,
                'msg' => trans('lang_v1.updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    public function getRewardDetails(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return '';
        }

        $business_id = request()->session()->get('user.business_id');

        $customer_id = $request->input('customer_id');

        $redeem_details = $this->transactionUtil->getRewardRedeemDetails($business_id, $customer_id);

        return json_encode($redeem_details);
    }

    /**
     * POS right panel: sales history for the selected customer.
     */
    public function getCustomerSalesHistory(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $customer_id = (int) $request->input('customer_id');
        $limit = min(50, max(5, (int) $request->input('limit', 20)));

        if ($customer_id <= 0) {
            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.select_customer'),
                'sales' => [],
            ]);
        }

        $walk_in = $this->contactUtil->getWalkInCustomer($business_id);
        if (!empty($walk_in['id']) && (int) $walk_in['id'] === $customer_id) {
            return response()->json([
                'success' => true,
                'is_walk_in' => true,
                'sales' => [],
                'customer_name' => $walk_in['name'] ?? '',
                'total_count' => 0,
            ]);
        }

        $contact = Contact::where('business_id', $business_id)
            ->where('id', $customer_id)
            ->select('id', 'name', 'supplier_business_name', 'total_rp')
            ->first();

        if (empty($contact)) {
            return response()->json([
                'success' => false,
                'msg' => __('lang_v1.cd_customer_not_found'),
                'sales' => [],
            ]);
        }

        $query = Transaction::where('business_id', $business_id)
            ->where('contact_id', $customer_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');

        $total_count = (clone $query)->count();

        $sales = $query->limit($limit)
            ->get(['id', 'invoice_no', 'final_total', 'payment_status', 'transaction_date', 'created_at'])
            ->map(function ($sale) {
                $dt = $sale->transaction_date ?: $sale->created_at;
                $ps = (string) ($sale->payment_status ?? '');
                $ps_label = trans('lang_v1.'.$ps);
                if ($ps_label === 'lang_v1.'.$ps || $ps === '') {
                    $ps_label = ucfirst(str_replace('_', ' ', $ps));
                }

                return [
                    'id' => $sale->id,
                    'invoice_no' => $sale->invoice_no,
                    'final_total' => (float) $sale->final_total,
                    'final_total_formatted' => $this->transactionUtil->num_f($sale->final_total, true),
                    'payment_status' => $ps,
                    'payment_status_label' => $ps_label,
                    'date' => $dt ? $this->transactionUtil->format_date($dt, true) : '',
                    'date_short' => $dt ? $this->transactionUtil->format_date($dt, false) : '',
                ];
            })
            ->values();

        $customer_name = !empty($contact->supplier_business_name)
            ? $contact->supplier_business_name
            : $contact->name;

        return response()->json([
            'success' => true,
            'is_walk_in' => false,
            'customer_id' => $contact->id,
            'customer_name' => $customer_name,
            'loyalty_points' => (float) ($contact->total_rp ?? 0),
            'total_count' => $total_count,
            'sales' => $sales,
        ]);
    }

    /**
     * Lookup loyalty customer by mobile number.
     */
    public function loyaltyLookup(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_disabled')]);
        }

        $business_id = $request->session()->get('user.business_id');
        $mobile = preg_replace('/\s+/', '', (string) $request->input('mobile', ''));

        if ($mobile === '') {
            return response()->json(['success' => false, 'msg' => __('lang_v1.enter_mobile_number')]);
        }

        $contact = Contact::where('business_id', $business_id)
            ->onlyCustomers()
            ->active()
            ->where(function ($q) use ($mobile) {
                $q->where('mobile', $mobile)
                    ->orWhere('mobile', 'like', '%'.$mobile)
                    ->orWhere('alternate_number', $mobile)
                    ->orWhere('landline', $mobile);
            })
            ->select(
                'id',
                'name',
                'mobile',
                'email',
                'address_line_1',
                'address_line_2',
                'city',
                'state',
                'country',
                'zip_code',
                'shipping_address',
                'pay_term_number',
                'pay_term_type',
                'total_rp',
                'supplier_business_name',
                'contact_id',
                DB::raw("IF(contacts.contact_id IS NULL OR contacts.contact_id='', contacts.name, CONCAT(contacts.name, ' (', contacts.contact_id, ')')) AS text")
            )
            ->first();

        return response()->json([
            'success' => true,
            'customer' => $contact,
        ]);
    }

    /**
     * Quick-create loyalty customer from POS popup.
     */
    public function loyaltyQuickCreate(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_disabled')]);
        }

        if (! auth()->user()->can('customer.create') && ! auth()->user()->can('customer.view_own')) {
            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')], 403);
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $first_name = trim((string) $request->input('first_name', ''));
            $last_name = trim((string) $request->input('last_name', ''));
            $mobile = preg_replace('/\s+/', '', (string) $request->input('mobile', ''));

            if ($first_name === '' || $mobile === '') {
                return response()->json(['success' => false, 'msg' => 'Name and mobile are required']);
            }

            $exists = Contact::where('business_id', $business_id)
                ->onlyCustomers()
                ->where('mobile', $mobile)
                ->exists();
            if ($exists) {
                return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_mobile_exists')]);
            }

            $name = trim($first_name.' '.$last_name);
            $input = [
                'type' => 'customer',
                'business_id' => $business_id,
                'created_by' => $request->session()->get('user.id'),
                'first_name' => $first_name,
                'last_name' => $last_name,
                'name' => $name,
                'mobile' => $mobile,
                'email' => $request->input('email'),
                'address_line_1' => $request->input('address_line_1'),
                'total_rp' => 0,
                'total_rp_used' => 0,
                'total_rp_expired' => 0,
            ];

            $output = $this->contactUtil->createNewContact($input);
            $contact = $output['data'];
            $contact->text = $contact->name.(! empty($contact->contact_id) ? ' ('.$contact->contact_id.')' : '');

            return response()->json([
                'success' => true,
                'msg' => $output['msg'] ?? __('contact.added_success'),
                'customer' => $contact,
            ]);
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            return response()->json(['success' => false, 'msg' => __('messages.something_went_wrong')]);
        }
    }

    /**
     * Send OTP to customer mobile for loyalty redeem (no card).
     */
    public function loyaltySendOtp(Request $request)
    {
        if ($request->session()->get('business.enable_rp') != 1) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_disabled')]);
        }

        $business_id = $request->session()->get('user.business_id');
        $cs = $request->session()->get('business.common_settings') ?? [];
        if (is_string($cs)) {
            $cs = json_decode($cs, true) ?? [];
        }
        if (empty($cs['loyalty_otp_on_redeem'])) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_otp_disabled')]);
        }

        $customer_id = (int) $request->input('customer_id');
        $customer = Contact::where('business_id', $business_id)->find($customer_id);
        if (empty($customer) || empty($customer->mobile) || $customer->is_default == 1) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_invalid_customer')]);
        }

        $otp = (string) random_int(100000, 999999);
        $cache_key = 'loyalty_otp_'.$business_id.'_'.$customer_id;
        \Illuminate\Support\Facades\Cache::put($cache_key, $otp, now()->addMinutes(10));
        $request->session()->forget('loyalty_otp_ok_'.$customer_id);

        $business = Business::find($business_id);
        $sms_settings = $business->sms_settings ?? [];
        $rp_name = $business->rp_name ?: 'Loyalty';
        $sms_body = $rp_name.' redeem OTP: '.$otp.'. Valid for 10 minutes.';

        try {
            if (! empty($sms_settings) && ! empty($customer->mobile)) {
                $this->transactionUtil->sendSms([
                    'sms_settings' => $sms_settings,
                    'mobile_number' => $customer->mobile,
                    'sms_body' => $sms_body,
                ]);
            }
        } catch (\Exception $e) {
            \Log::warning('Loyalty OTP SMS failed: '.$e->getMessage());
        }

        $payload = [
            'success' => true,
            'msg' => __('lang_v1.loyalty_otp_sent'),
            'mobile' => $customer->mobile,
        ];
        // In local/debug, surface OTP so POS can be tested without SMS
        if (config('app.debug')) {
            $payload['debug_otp'] = $otp;
            $payload['msg'] = __('lang_v1.loyalty_otp_sent').' (debug: '.$otp.')';
        }

        return response()->json($payload);
    }

    /**
     * Verify loyalty redeem OTP.
     */
    public function loyaltyVerifyOtp(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $customer_id = (int) $request->input('customer_id');
        $otp = trim((string) $request->input('otp', ''));
        $cache_key = 'loyalty_otp_'.$business_id.'_'.$customer_id;
        $stored = \Illuminate\Support\Facades\Cache::get($cache_key);

        if (empty($stored) || $otp === '' || ! hash_equals((string) $stored, $otp)) {
            return response()->json(['success' => false, 'msg' => __('lang_v1.loyalty_otp_invalid')]);
        }

        \Illuminate\Support\Facades\Cache::forget($cache_key);
        $request->session()->put('loyalty_otp_ok_'.$customer_id, true);

        return response()->json(['success' => true, 'msg' => __('lang_v1.loyalty_otp_verified')]);
    }

    public function placeOrdersApi(Request $request)
    {
        try {
            $api_token = $request->header('API-TOKEN');
            $api_settings = $this->moduleUtil->getApiSettings($api_token);

            $business_id = $api_settings->business_id;
            $location_id = $api_settings->location_id;

            $input = $request->only(['products', 'customer_id', 'addresses']);

            //check if all stocks are available
            $variation_ids = [];
            foreach ($input['products'] as $product_data) {
                $variation_ids[] = $product_data['variation_id'];
            }

            $variations_details = $this->getVariationsDetails($business_id, $location_id, $variation_ids);
            $is_valid = true;
            $error_messages = [];
            $sell_lines = [];
            $final_total = 0;
            foreach ($variations_details as $variation_details) {
                if ($variation_details->product->enable_stock == 1) {
                    if (empty($variation_details->variation_location_details[0]) || $variation_details->variation_location_details[0]->qty_available < $input['products'][$variation_details->id]['quantity']) {
                        $is_valid = false;
                        $error_messages[] = 'Only ' . $variation_details->variation_location_details[0]->qty_available . ' ' . $variation_details->product->unit->short_name . ' of ' . $input['products'][$variation_details->id]['product_name'] . ' available';
                    }
                }

                //Create product line array
                $sell_lines[] = [
                    'product_id' => $variation_details->product->id,
                    'unit_price_before_discount' => $variation_details->unit_price_inc_tax,
                    'unit_price' => $variation_details->unit_price_inc_tax,
                    'unit_price_inc_tax' => $variation_details->unit_price_inc_tax,
                    'variation_id' => $variation_details->id,
                    'quantity' => $input['products'][$variation_details->id]['quantity'],
                    'item_tax' => 0,
                    'enable_stock' => $variation_details->product->enable_stock,
                    'tax_id' => null,
                ];

                $final_total += ($input['products'][$variation_details->id]['quantity'] * $variation_details->unit_price_inc_tax);
            }

            if (!$is_valid) {
                return $this->respond([
                    'success' => false,
                    'error_messages' => $error_messages,
                ]);
            }

            $business = Business::find($business_id);
            $user_id = $business->owner_id;

            $business_data = [
                'id' => $business_id,
                'accounting_method' => $business->accounting_method,
                'location_id' => $location_id,
            ];

            $customer = Contact::where('business_id', $business_id)
                ->whereIn('type', ['customer', 'both'])
                ->find($input['customer_id']);

            $order_data = [
                'business_id' => $business_id,
                'location_id' => $location_id,
                'contact_id' => $input['customer_id'],
                'final_total' => $final_total,
                'created_by' => $user_id,
                'status' => 'final',
                'payment_status' => 'due',
                'additional_notes' => '',
                'transaction_date' => \Carbon::now(),
                'customer_group_id' => $customer->customer_group_id,
                'tax_rate_id' => null,
                'sale_note' => null,
                'commission_agent' => null,
                'order_addresses' => json_encode($input['addresses']),
                'products' => $sell_lines,
                'is_created_from_api' => 1,
                'discount_type' => 'fixed',
                'discount_amount' => 0,
            ];

            $invoice_total = [
                'total_before_tax' => $final_total,
                'tax' => 0,
            ];

            DB::beginTransaction();

            $transaction = $this->transactionUtil->createSellTransaction($business_id, $order_data, $invoice_total, $user_id, false);

            //Create sell lines
            $this->transactionUtil->createOrUpdateSellLines($transaction, $order_data['products'], $order_data['location_id'], false, null, [], false);

            //update product stock
            foreach ($order_data['products'] as $product) {
                if ($product['enable_stock']) {
                    $this->productUtil->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $order_data['location_id'],
                        $product['quantity']
                    );
                }
            }

            $this->transactionUtil->mapPurchaseSell($business_data, $transaction->sell_lines, 'purchase');
            //Auto send notification
            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);

            // eBill — auto-send unless prompt_before_send is enabled
            $ebill_settings = !empty($business_details->common_settings['ebill_settings']) ? $business_details->common_settings['ebill_settings'] : [];
            $ebill_prompt = false;
            if (empty($ebill_settings['prompt_before_send'])) {
                $this->notificationUtil->autoSendEbill($business_id, $transaction, $transaction->contact);
            } else {
                $ebill_prompt = [
                    'transaction_id' => $transaction->id,
                    'contact_name'   => $transaction->contact->name ?? '',
                    'prefix'         => $transaction->contact->prefix ?? '',
                    'first_name'     => $transaction->contact->first_name ?? '',
                    'mobile'         => $transaction->contact->mobile ?? '',
                    'has_sms'        => !empty($ebill_settings['enable_sms_ebill']),
                    'has_whatsapp'   => !empty($ebill_settings['enable_whatsapp_ebill']),
                ];
            }

            DB::commit();

            $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction->id);

            $output = [
                'success' => 1,
                'transaction' => $transaction,
                'receipt' => $receipt,
            ];
            if ($ebill_prompt) {
                $output['ebill_prompt'] = $ebill_prompt;
            }
        } catch (\Exception $e) {
            DB::rollBack();

            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'error_messages' => [$msg],
            ];
        }

        return $this->respond($output);
    }

    /**
     * Manually send eBill (SMS/WhatsApp) for a given transaction.
     */
    public function sendEbill($transaction_id)
    {
        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::where('business_id', $business_id)
            ->with('contact')
            ->findOrFail($transaction_id);

        $options = [
            'mobile' => request()->input('mobile'),
            'send_sms' => (bool) request()->input('send_sms'),
            'send_whatsapp' => (bool) request()->input('send_whatsapp'),
        ];

        $errors = $this->notificationUtil->autoSendEbill($business_id, $transaction, $transaction->contact, $options);

        if (! empty($errors)) {
            return response()->json(['success' => 0, 'msg' => implode(' | ', $errors)]);
        }

        return response()->json(['success' => 1, 'msg' => 'eBill sent successfully.']);
    }

    private function getVariationsDetails($business_id, $location_id, $variation_ids)
    {
        $variation_details = Variation::whereIn('id', $variation_ids)
            ->with([
                'product' => function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                },
                'product.unit',
                'variation_location_details' => function ($q) use ($location_id) {
                    $q->where('location_id', $location_id);
                },
            ])->get();

        return $variation_details;
    }

    public function getTypesOfServiceDetails(Request $request)
    {
        $location_id = $request->input('location_id');
        $types_of_service_id = $request->input('types_of_service_id');

        $business_id = $request->session()->get('user.business_id');

        $types_of_service = TypesOfService::where('business_id', $business_id)
            ->where('id', $types_of_service_id)
            ->first();

        $price_group_id = !empty($types_of_service->location_price_group[$location_id])
        ? $types_of_service->location_price_group[$location_id] : '';
        $price_group_name = '';

        if (!empty($price_group_id)) {
            $price_group = SellingPriceGroup::find($price_group_id);
            $price_group_name = $price_group->name;
        }

        $modal_html = view('types_of_service.pos_form_modal')
            ->with(compact('types_of_service'))->render();

        return $this->respond([
            'price_group_id' => $price_group_id,
            'packing_charge' => $types_of_service->packing_charge,
            'packing_charge_type' => $types_of_service->packing_charge_type,
            'modal_html' => $modal_html,
            'price_group_name' => $price_group_name,
        ]);
    }

    public function getFeaturedProducts($id)
    {
        $location = BusinessLocation::findOrFail($id);
        $featured_products = $location->getFeaturedProducts();

        if (!empty($featured_products)) {
            return view('sale_pos.partials.featured_products')->with(compact('featured_products'));
        } else {
            return '';
        }
    }

    /**
     * Converts drafts and quotations to invoice
     */
    public function convertToInvoice($id)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::with(['sell_lines',
                'sell_lines.product',
                'sell_lines.variations',
                'contact'])
                ->where('business_id', $business_id)
                ->where('status', 'draft')
                ->findOrFail($id);

            $transaction_before = $transaction->replicate();
            $is_direct_sale = $transaction->is_direct_sale;
            //Check Customer credit limit
            $data = [
                'final_total' => $transaction->final_total,
                'contact_id' => $transaction->contact_id,
                'status' => 'final',
            ];
            $is_credit_limit_exeeded = $this->transactionUtil->isCustomerCreditLimitExeeded($data, $id);

            if ($is_credit_limit_exeeded !== false) {
                $credit_limit_amount = $this->transactionUtil->num_f($is_credit_limit_exeeded, true);
                $output = ['success' => 0,
                    'msg' => __('lang_v1.cutomer_credit_limit_exeeded', ['credit_limit' => $credit_limit_amount]),
                ];

                return redirect()
                    ->back()
                    ->with('status', $output);
            }

            DB::beginTransaction();
            //Check if there is a open register, if no then redirect to Create Register screen.
            if (!$is_direct_sale && $this->cashRegisterUtil->countOpenedRegister() == 0) {
                return redirect()->action([\App\Http\Controllers\CashRegisterController::class, 'create']);
            }

            $invoice_no = $this->transactionUtil->getInvoiceNumber($business_id, 'final', $transaction->location_id);

            $transaction->invoice_no = $invoice_no;
            $transaction->transaction_date = \Carbon::now();
            $transaction->status = 'final';
            $transaction->sub_status = null;
            $transaction->is_quotation = 0;
            $transaction->save();

            //update product stock
            foreach ($transaction->sell_lines as $sell_line) {
                $decrease_qty = $sell_line->quantity;

                if ($sell_line->product->enable_stock == 1) {
                    $this->productUtil->decreaseProductQuantity(
                        $sell_line->product_id,
                        $sell_line->variation_id,
                        $transaction->location_id,
                        $decrease_qty
                    );
                }

                if ($sell_line->product->type == 'combo') {
                    //Decrease quantity of combo as well.
                    $combo_variations = $sell_line->variations->combo_variations;

                    foreach ($combo_variations as $key => $value) {
                        $base_unit_multiplier = 1;

                        if (!empty($value['unit_id'])) {
                            $unit = Unit::find($value['unit_id']);
                            $base_unit_multiplier = !empty($unit->base_unit_multiplier) ? $unit->base_unit_multiplier : $base_unit_multiplier;
                        }

                        $combo_variations[$key]['product_id'] = $sell_line->product_id;
                        $combo_variations[$key]['product_id'] = $sell_line->product_id;
                        $combo_variations[$key]['quantity'] = $value['quantity'] * $decrease_qty * $base_unit_multiplier;
                    }
                    $this->productUtil
                        ->decreaseProductQuantityCombo(
                            $combo_variations,
                            $transaction->location_id
                        );
                }
            }

            //Update payment status
            $this->transactionUtil->updatePaymentStatus($transaction->id, $transaction->final_total);

            $business_details = $this->businessUtil->getDetails($business_id);
            $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

            $business = ['id' => $business_id,
                'accounting_method' => request()->session()->get('business.accounting_method'),
                'location_id' => $transaction->location_id,
                'pos_settings' => $pos_settings,
            ];

            try {
                $this->transactionUtil->mapPurchaseSell($business, $transaction->sell_lines, 'purchase');
            } catch (\Exception $e) {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $msg = trans('messages.something_went_wrong');

                if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                    $msg = $e->getMessage();
                }

                if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                    $msg = $e->getMessage();
                }

                $output = ['success' => 0,
                    'msg' => $msg,
                ];

                return redirect()
                    ->action([\App\Http\Controllers\SellController::class, 'index'])
                    ->with('status', $output);
            }

            //Auto send notification
            $this->notificationUtil->autoSendNotification($business_id, 'new_sale', $transaction, $transaction->contact);
            $this->notificationUtil->autoSendEbill($business_id, $transaction, $transaction->contact);

            $this->transactionUtil->activityLog($transaction, 'edited', $transaction_before);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.converted_to_invoice_successfully', ['invoice_no' => $transaction->invoice_no])];
        } catch (Exception $e) {
            DB::rollBack();
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
            $msg = trans('messages.something_went_wrong');

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            if (get_class($e) == \App\Exceptions\AdvanceBalanceNotAvailable::class) {
                $msg = $e->getMessage();
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
        }

        return redirect()
            ->action([\App\Http\Controllers\SellController::class, 'index'])
            ->with('status', $output);
    }

    /**
     * Converts drafts and quotations to invoice
     */
    public function convertToProforma($id)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                ->where('status', 'draft')
                ->findOrFail($id);

            $transaction_before = $transaction->replicate();

            $transaction->sub_status = 'proforma';
            $transaction->save();

            $this->transactionUtil->activityLog($transaction, 'edited', $transaction_before);

            $output = ['success' => 1, 'msg' => __('lang_v1.converted_to_proforma_successfully')];
        } catch (Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Copy quotation
     *
     */
    public function copyQuotation($id)
    {
        if (!auth()->user()->can('quotation.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $transaction = Transaction::where('business_id', $business_id)
                ->where('sub_status', 'quotation')
                ->findOrFail($id);

            DB::beginTransaction();
            $quotation = $transaction->replicate();

            $quotation->transaction_date = \Carbon::now()->format('Y-m-d H:i:s');
            $quotation->invoice_no = $this->transactionUtil->getInvoiceNumber($business_id, 'draft',
                $transaction->location_id);
            $quotation->save();

            $sell_lines = TransactionSellLine::where('transaction_id', $transaction->id)->get();
            $new_sell_lines = [];
            foreach ($sell_lines as $sell_line) {
                $sl = $sell_line->replicate();
                $sl->transaction_id = $quotation->id;

                $new_sell_lines[] = $sl;
            }

            $quotation->sell_lines()->saveMany($new_sell_lines);

            DB::commit();

            $output = ['success' => 1, 'msg' => __('lang_v1.converted_to_proforma_successfully')];

        } catch (Exception $e) {
            DB::rollBack();

            \Log::emergency("File:" . $e->getFile() . "Line:" . $e->getLine() . "Message:" . $e->getMessage());

            $output = ['success' => 0,
                'msg' => trans("messages.something_went_wrong"),
            ];
        }

        return redirect()->action([\App\Http\Controllers\SellController::class, 'getQuotations']);
    }

    /**
     * download pdf for given transaction
     */
    public function downloadPdf($id)
    {
        if (!(config('constants.enable_download_pdf') && auth()->user()->can('print_invoice'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $receipt_contents = $this->transactionUtil->getPdfContentsForGivenTransaction($business_id, $id);
        $receipt_details = $receipt_contents['receipt_details'];
        $location_details = $receipt_contents['location_details'];
        $is_email_attachment = false;

        $blade_file = 'download_pdf';
        if (!empty($receipt_details->is_export)) {
            $blade_file = 'download_export_pdf';
        }

        //Generate pdf
        $body = view('sale_pos.receipts.' . $blade_file)
            ->with(compact('receipt_details', 'location_details', 'is_email_attachment'))
            ->render();

        $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'format' => 'A4',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetWatermarkText($receipt_details->business_name, 0.1);
        $mpdf->showWatermarkText = true;
        $mpdf->SetTitle('INVOICE-' . $receipt_details->invoice_no . '.pdf');
        $mpdf->WriteHTML($body);
        $mpdf->Output('INVOICE-' . $receipt_details->invoice_no . '.pdf', 'I');
    }

    /**
     * download pdf for given quotation
     */
    public function downloadQuotationPdf($id)
    {
        if (!(config('constants.enable_download_pdf'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $sub_status = !empty(request()->input('sub_status')) ? request()->input('sub_status') : '';
        $receipt_contents = $this->transactionUtil->getPdfContentsForGivenTransaction($business_id, $id);
        $receipt_details = $receipt_contents['receipt_details'];
        $location_details = $receipt_contents['location_details'];

        //Generate pdf
        $body = view('sale_pos.receipts.download_quotation_pdf')
            ->with(compact('receipt_details', 'location_details', 'sub_status'))
            ->render();
        $pdf_name = (!empty($sub_status) && $sub_status == 'proforma') ? __('lang_v1.proforma_invoice') : 'QUOTATION';
        $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'format' => 'A4',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetWatermarkText($receipt_details->business_name, 0.1);
        $mpdf->showWatermarkText = true;
        $mpdf->SetTitle($pdf_name . '-' . $receipt_details->invoice_no . '.pdf');
        $mpdf->WriteHTML($body);
        $mpdf->Output($pdf_name . '-' . $receipt_details->invoice_no . '.pdf', 'I');
    }

    /**
     * download pdf for given shipment
     */
    public function downloadPackingListPdf($id)
    {
        if (!(config('constants.enable_download_pdf'))) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        $receipt_contents = $this->transactionUtil->getPdfContentsForGivenTransaction($business_id, $id);
        $receipt_details = $receipt_contents['receipt_details'];
        $location_details = $receipt_contents['location_details'];

        //Generate pdf
        $body = view('sale_pos.receipts.download_packing_list_pdf')
            ->with(compact('receipt_details', 'location_details'))
            ->render();

        $mpdf = new \Mpdf\Mpdf(['tempDir' => public_path('uploads/temp'),
            'mode' => 'utf-8',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'autoVietnamese' => true,
            'autoArabic' => true,
            'margin_top' => 8,
            'margin_bottom' => 8,
            'format' => 'A4',
        ]);

        $mpdf->useSubstitutions = true;
        $mpdf->SetWatermarkText($receipt_details->business_name, 0.1);
        $mpdf->showWatermarkText = true;
        $mpdf->SetTitle('PACKINGSLIP-' . $receipt_details->invoice_no . '.pdf');
        $mpdf->WriteHTML($body);
        $mpdf->Output('PACKINGSLIP-' . $receipt_details->invoice_no . '.pdf', 'I');
    }

    public function showServiceStaffAvailibility()
    {
        $location_id = request()->input('location_id');
        $business_id = request()->session()->get('user.business_id');

        $service_staffs = $this->productUtil->getServiceStaff($business_id, $location_id);

        return view('sale_pos.partials.service_staff_availability_modal')
            ->with(compact('service_staffs'));
    }

    public function pauseResumeServiceStaffTimer($user_id)
    {
        $service_staff = User::find($user_id);
        if (empty($service_staff->paused_at)) {
            $service_staff->paused_at = \Carbon::now();
        } else {
            //add diff to available_at
            $mins = \Carbon::now()->diffInMinutes(\Carbon::parse($service_staff->paused_at));
            $service_staff->available_at = \Carbon::parse($service_staff->available_at)->addMinutes($mins);
            $service_staff->paused_at = null;
        }

        $service_staff->save();

        return ['paused_at' => $service_staff->paused_at];
    }

    public function markAsAvailable($user_id)
    {
        $service_staff = User::where('id', $user_id)
            ->update(['paused_at' => null, 'available_at' => null]);

        return ['success' => true];
    }

    public function validateInvoiceToServiceStaffReplacement($invoice_no)
    {

        $business_id = request()->session()->get('user.business_id');
        $transaction = Transaction::where('business_id', $business_id)
            ->where('invoice_no', $invoice_no);

        $transaction = $transaction->first();

        if (empty($transaction)) {
            return ['success' => 0,
                'msg' => trans('lang_v1.sell_not_found'),
            ];
        }

        $location_id = $transaction->location_id;

        $sell_details = TransactionSellLine::join(
            'products AS p',
            'transaction_sell_lines.product_id',
            '=',
            'p.id'
        )
            ->join(
                'variations AS variations',
                'transaction_sell_lines.variation_id',
                '=',
                'variations.id'
            )
            ->join(
                'product_variations AS pv',
                'variations.product_variation_id',
                '=',
                'pv.id'
            )
            ->leftjoin('variation_location_details AS vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id')
                    ->where('vld.location_id', '=', $location_id);
            })
            ->leftjoin('units', 'units.id', '=', 'p.unit_id')
            ->leftjoin('units as u', 'p.secondary_unit_id', '=', 'u.id')
            ->where('transaction_sell_lines.transaction_id', $transaction->id)
            ->select(
                DB::raw("IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name) AS product_name"),
                'variations.sub_sku',
                'transaction_sell_lines.id',
                'transaction_sell_lines.res_service_staff_id',
            )
            ->get();

        $enabled_modules = $this->transactionUtil->allModulesEnabled();

        $business_details = $this->businessUtil->getDetails($business_id);

        $pos_settings = empty($business_details->pos_settings) ? $this->businessUtil->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $waiters = [];

        if ($this->productUtil->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->productUtil->serviceStaffDropdown($business_id, $location_id);
        }

        if ($transaction) {
            return ['success' => 1,
                'msg' => view('sale_pos.partials.service_staff_replacement_modal', compact('transaction', 'sell_details', 'waiters', 'enabled_modules', 'pos_settings'))->render(),
            ];
        }

    }

    public function change_service_staff($id, Request $request)
    {
        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $transaction = Transaction::where('business_id', $business_id)
                    ->where('id', $id)->findOrFail($id);

                DB::beginTransaction();

                $transaction->res_waiter_id = $request->res_waiter_id;

                $transaction->update();

                if ($request->sell_details) {
                    foreach ($request->sell_details as $sell) {

                        TransactionSellLine::where('id', $sell['id'])->update([
                            'res_service_staff_id' => $sell['res_service_staff_id'],
                        ]);
                    }
                }

                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.updated_success'),
                ];

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $output = ['success' => 0,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;

        }
    }
}
