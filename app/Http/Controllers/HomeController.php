<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Charts\CommonChart;
use App\Currency;
use App\Media;
use App\Transaction;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ModuleUtil;
use App\Utils\RestaurantUtil;
use App\Utils\TransactionUtil;
use App\Utils\ProductUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use Datatables;
use DB;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class HomeController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $businessUtil;

    protected $transactionUtil;

    protected $moduleUtil;

    protected $commonUtil;

    protected $restUtil;
    protected $productUtil;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        BusinessUtil $businessUtil,
        TransactionUtil $transactionUtil,
        ModuleUtil $moduleUtil,
        Util $commonUtil,
        RestaurantUtil $restUtil,
        ProductUtil $productUtil,
    ) {
        $this->businessUtil = $businessUtil;
        $this->transactionUtil = $transactionUtil;
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
        $this->restUtil = $restUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    private function commissionMaskPct($business_id): float
    {
        // Check if IC mode is active and if it has expired
        if (session('ic_mode', false)) {
            $activatedAt = session('ic_mode_activated_at');
            $timeoutMinutes = session('ic_mode_timeout_minutes', 5);

            if ($activatedAt) {
                $elapsedMinutes = (now()->timestamp - $activatedAt) / 60;
                if ($elapsedMinutes >= $timeoutMinutes) {
                    // Auto-disable IC mode - timeout expired
                    session()->put('ic_mode', false);
                    session()->forget('ic_mode_activated_at');
                    session()->forget('ic_mode_timeout_minutes');
                } else {
                    // Still active, show real values
                    return 1.0;
                }
            } else {
                return 1.0;
            }
        }

        // If income masking is disabled, show real values to everyone
        $biz = \App\Business::find($business_id);
        $cs = ! empty($biz->common_settings) ? $biz->common_settings : [];
        $maskingEnabled = !empty($cs['enable_commission_masking']);

        if (!$maskingEnabled) {
            return 1.0;
        }

        // Masking is enabled - admin sees real values, others see masked
        $is_admin = auth()->user()->hasRole('Admin#'.$business_id);
        if (auth()->user()->can('superadmin') || $is_admin) {
            return 1.0;
        }

        // Non-admin users see masked values
        if (!empty($cs['commission_percentage'])) {
            return floatval($cs['commission_percentage']) / 100;
        }
        return 1.0;
    }

    public function index()
    {
        $user = auth()->user();
        if ($user->user_type == 'user_customer') {
            return redirect()->action([\Modules\Crm\Http\Controllers\DashboardController::class, 'index']);
        }

        $business_id = request()->session()->get('user.business_id');

        $is_admin = $this->businessUtil->is_admin(auth()->user());

        if (! auth()->user()->can('dashboard.data')) {
            return view('home.index');
        }

        $fy = $this->businessUtil->getCurrentFinancialYear($business_id);

        $currency = Currency::where('id', request()->session()->get('business.currency_id'))->first();
        //ensure start date starts from at least 30 days before to get sells last 30 days
        $least_30_days = \Carbon::parse($fy['start'])->subDays(30)->format('Y-m-d');

        //get all sells
        $sells_this_fy = $this->transactionUtil->getSellsCurrentFy($business_id, $least_30_days, $fy['end']);

        $all_locations = BusinessLocation::forDropdown($business_id)->toArray();

        $chart_pct = $this->commissionMaskPct($business_id);

        //Chart for sells last 30 days
        $labels = [];
        $all_sell_values = [];
        $dates = [];
        for ($i = 29; $i >= 0; $i--) {
            $date = \Carbon::now()->subDays($i)->format('Y-m-d');
            $dates[] = $date;

            $labels[] = date('j M Y', strtotime($date));

            $total_sell_on_date = $sells_this_fy->where('date', $date)->sum('total_sells');

            if (! empty($total_sell_on_date)) {
                $all_sell_values[] = (float) $total_sell_on_date * $chart_pct;
            } else {
                $all_sell_values[] = 0;
            }
        }

        //Group sells by location
        $location_sells = [];
        foreach ($all_locations as $loc_id => $loc_name) {
            $values = [];
            foreach ($dates as $date) {
                $total_sell_on_date_location = $sells_this_fy->where('date', $date)->where('location_id', $loc_id)->sum('total_sells');

                if (! empty($total_sell_on_date_location)) {
                    $values[] = (float) $total_sell_on_date_location * $chart_pct;
                } else {
                    $values[] = 0;
                }
            }
            $location_sells[$loc_id]['loc_label'] = $loc_name;
            $location_sells[$loc_id]['values'] = $values;
        }

        $sells_chart_1 = new CommonChart;

        $sells_chart_1->labels($labels)
                        ->options($this->__chartOptions(__(
                            'home.total_sells',
                            ['currency' => $currency->code]
                            )));

        if (! empty($location_sells)) {
            foreach ($location_sells as $location_sell) {
                $sells_chart_1->dataset($location_sell['loc_label'], 'line', $location_sell['values']);
            }
        }

        if (count($all_locations) > 1) {
            $sells_chart_1->dataset(__('report.all_locations'), 'line', $all_sell_values);
        }

        $labels = [];
        $values = [];
        $date = strtotime($fy['start']);
        $last = date('m-Y', strtotime($fy['end']));
        $fy_months = [];
        do {
            $month_year = date('m-Y', $date);
            $fy_months[] = $month_year;

            $labels[] = \Carbon::createFromFormat('m-Y', $month_year)
                            ->format('M-Y');
            $date = strtotime('+1 month', $date);

            $total_sell_in_month_year = $sells_this_fy->where('yearmonth', $month_year)->sum('total_sells');

            if (! empty($total_sell_in_month_year)) {
                $values[] = (float) $total_sell_in_month_year * $chart_pct;
            } else {
                $values[] = 0;
            }
        } while ($month_year != $last);

        $fy_sells_by_location_data = [];

        foreach ($all_locations as $loc_id => $loc_name) {
            $values_data = [];
            foreach ($fy_months as $month) {
                $total_sell_in_month_year_location = $sells_this_fy->where('yearmonth', $month)->where('location_id', $loc_id)->sum('total_sells');

                if (! empty($total_sell_in_month_year_location)) {
                    $values_data[] = (float) $total_sell_in_month_year_location * $chart_pct;
                } else {
                    $values_data[] = 0;
                }
            }
            $fy_sells_by_location_data[$loc_id]['loc_label'] = $loc_name;
            $fy_sells_by_location_data[$loc_id]['values'] = $values_data;
        }

        $sells_chart_2 = new CommonChart;
        $sells_chart_2->labels($labels)
                    ->options($this->__chartOptions(__(
                        'home.total_sells',
                        ['currency' => $currency->code]
                            )));
        if (! empty($fy_sells_by_location_data)) {
            foreach ($fy_sells_by_location_data as $location_sell) {
                $sells_chart_2->dataset($location_sell['loc_label'], 'line', $location_sell['values']);
            }
        }
        if (count($all_locations) > 1) {
            $sells_chart_2->dataset(__('report.all_locations'), 'line', $values);
        }

        //Get Dashboard widgets from module
        $module_widgets = $this->moduleUtil->getModuleData('dashboard_widget');

        $widgets = [];

        foreach ($module_widgets as $widget_array) {
            if (! empty($widget_array['position'])) {
                $widgets[$widget_array['position']][] = $widget_array['widget'];
            }
        }

        $common_settings = ! empty(session('business.common_settings')) ? session('business.common_settings') : [];

        // Cheque reminders
        $cheque_stats = (new \App\Http\Controllers\ChequeController())->dashboardWidget();
        
        // Get due cheques list for dashboard table
        $business_id = auth()->user()->business_id;
        $today = now()->toDateString();
        $tomorrow = now()->addDay()->toDateString();
        $in7days = now()->addDays(7)->toDateString();
        
        $due_cheques = \App\TransactionPayment::where('transaction_payments.business_id', $business_id)
            ->where('transaction_payments.method', 'cheque')
            ->whereNull('transaction_payments.parent_id')
            ->where(function ($q) {
                $q->where('transaction_payments.is_return', 0)
                    ->orWhereNull('transaction_payments.is_return');
            })
            ->where('transaction_payments.cheque_status', 'pending')
            ->where(function($q) use ($today, $in7days) {
                $q->where('transaction_payments.cheque_date', '<', $today) // overdue
                  ->orWhereBetween('transaction_payments.cheque_date', [$today, $in7days]); // due within 7 days
            })
            ->leftJoin('transactions as t', 'transaction_payments.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id')
            ->leftJoin('contacts as c2', 'transaction_payments.payment_for', '=', 'c2.id')
            ->select([
                'transaction_payments.id',
                'transaction_payments.cheque_number',
                'transaction_payments.cheque_name',
                'transaction_payments.cheque_bank_name',
                'transaction_payments.cheque_date',
                'transaction_payments.amount',
                'transaction_payments.paid_on',
                'transaction_payments.cheque_type',
                DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
                DB::raw('COALESCE(c1.mobile, c2.mobile) as contact_mobile'),
                DB::raw('CASE 
                    WHEN transaction_payments.cheque_date < "'.$today.'" THEN "overdue"
                    WHEN transaction_payments.cheque_date = "'.$today.'" THEN "due_today"
                    WHEN transaction_payments.cheque_date = "'.$tomorrow.'" THEN "due_tomorrow"
                    ELSE "due_soon"
                END as due_status')
            ])
            ->orderBy('transaction_payments.cheque_date', 'asc')
            ->limit(10)
            ->get();
        
        // Count stats
        $due_tomorrow_count = $due_cheques->where('due_status', 'due_tomorrow')->count();
        $due_today_count = $due_cheques->where('due_status', 'due_today')->count();

        // Also surface cheques due in the next 7 days for "coming soon" reminders
        $coming_cheques_count = $due_cheques->whereIn('due_status', ['due_today', 'due_tomorrow', 'due_soon'])->count();

        // Local AI insights from in-app POS data (no external API)
        $ai_insights = null;
        $enabled_modules = session('business.enabled_modules', []);
        if (in_array('ai_assistant', $enabled_modules)) {
            try {
                $ai_insights = app(\App\Http\Controllers\AiChatController::class)->getDashboardInsights();
            } catch (\Throwable $e) {
                $ai_insights = null;
            }
        }

        return view('home.index', compact('sells_chart_1', 'sells_chart_2', 'widgets', 'all_locations', 'common_settings', 'is_admin', 'cheque_stats', 'due_cheques', 'ai_insights'));
    }

    /**
     * Retrieves purchase and sell details for a given time period.
     *
     * @return \Illuminate\Http\Response
     */
    public function getTotals()
    {
        if (request()->ajax()) {
            $start = request()->start;
            $end = request()->end;
            $location_id = request()->location_id;
            $business_id = request()->session()->get('user.business_id');

            // get user id parameter
            $created_by = request()->user_id;

            $purchase_details = $this->transactionUtil->getPurchaseTotals($business_id, $start, $end, $location_id, $created_by);

            $sell_details = $this->transactionUtil->getSellTotals($business_id, $start, $end, $location_id, $created_by);

            $total_ledger_discount = $this->transactionUtil->getTotalLedgerDiscount($business_id, $start, $end);

            $purchase_details['purchase_due'] = $purchase_details['purchase_due'] - $total_ledger_discount['total_purchase_discount'];

            $transaction_types = [
                'purchase_return', 'sell_return', 'expense',
            ];

            $transaction_totals = $this->transactionUtil->getTransactionTotals(
                $business_id,
                $transaction_types,
                $start,
                $end,
                $location_id,
                $created_by
            );

            $total_purchase_inc_tax = ! empty($purchase_details['total_purchase_inc_tax']) ? $purchase_details['total_purchase_inc_tax'] : 0;
            $total_purchase_return_inc_tax = $transaction_totals['total_purchase_return_inc_tax'];

            $output = $purchase_details;
            $output['total_purchase'] = $total_purchase_inc_tax;
            $output['total_purchase_return'] = $total_purchase_return_inc_tax;
            $output['total_purchase_return_paid'] = $this->transactionUtil->getTotalPurchaseReturnPaid($business_id, $start, $end, $location_id);

            $total_sell_inc_tax = ! empty($sell_details['total_sell_inc_tax']) ? $sell_details['total_sell_inc_tax'] : 0;
            $total_sell_return_inc_tax = ! empty($transaction_totals['total_sell_return_inc_tax']) ? $transaction_totals['total_sell_return_inc_tax'] : 0;
            $output['total_sell_return_paid'] = $this->transactionUtil->getTotalSellReturnPaid($business_id, $start, $end, $location_id);

            $output['total_sell'] = $total_sell_inc_tax;
            $output['total_sell_return'] = $total_sell_return_inc_tax;

            $output['invoice_due'] = $sell_details['invoice_due'] - $total_ledger_discount['total_sell_discount'];
            $output['total_expense'] = $transaction_totals['total_expense'];

            // Payment mode totals (customer receipts only)
            $payment_totals_query = DB::table('transaction_payments as tp')
                ->join('transactions as t', 'tp.transaction_id', '=', 't.id')
                ->where('t.business_id', $business_id)
                ->where('t.type', 'sell')
                ->whereNull('tp.parent_id');

            if (! empty($start) && ! empty($end)) {
                $payment_totals_query->whereDate('tp.paid_on', '>=', $start)
                    ->whereDate('tp.paid_on', '<=', $end);
            }

            if (! empty($location_id)) {
                $payment_totals_query->where('t.location_id', $location_id);
            }

            $output['total_cheque_received'] = (clone $payment_totals_query)
                ->where('tp.method', 'cheque')
                ->sum('tp.amount');
            $output['total_cash_received'] = (clone $payment_totals_query)
                ->where('tp.method', 'cash')
                ->sum('tp.amount');

            // Net Earnings = Gross Sales - Receivables Due - Operating Expense
            $output['net'] = $output['total_sell'] - $output['invoice_due'] - $output['total_expense'];

            $pct = $this->commissionMaskPct($business_id);
            if ($pct !== 1.0) {
                $mask_keys = ['total_purchase', 'total_purchase_return', 'total_sell', 'total_sell_return', 'invoice_due', 'total_expense', 'net', 'purchase_due', 'total_purchase_return_paid', 'total_sell_return_paid', 'total_cheque_received', 'total_cash_received'];
                foreach ($mask_keys as $key) {
                    if (isset($output[$key])) {
                        $output[$key] = $output[$key] * $pct;
                    }
                }
            }

            return $output;
        }
    }

    /**
     * Retrieves sell products whose available quntity is less than alert quntity.
     *
     * @return \Illuminate\Http\Response
     */
    public function getProductStockAlert()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $permitted_locations = auth()->user()->permitted_locations();
            $products = $this->productUtil->getProductAlert($business_id, $permitted_locations);

            return Datatables::of($products)
                ->editColumn('product', function ($row) {
                    if ($row->type == 'single') {
                        return $row->product.' ('.$row->sku.')';
                    } else {
                        return $row->product.' - '.$row->product_variation.' - '.$row->variation.' ('.$row->sub_sku.')';
                    }
                })
                ->editColumn('stock', function ($row) {
                    $stock = $row->stock ? $row->stock : 0;

                    return '<span data-is_quantity="true" data-orig-value="'.(float) $stock.'" class="display_currency" data-currency_symbol=false>'.(float) $stock.'</span> '.$row->unit;
                })
                ->removeColumn('sku')
                ->removeColumn('sub_sku')
                ->removeColumn('unit')
                ->removeColumn('type')
                ->removeColumn('product_variation')
                ->removeColumn('variation')
                ->rawColumns([2])
                ->make(false);
        }
    }

    /**
     * Lightweight JSON list of low-stock products for POS header dropdown.
     */
    public function getProductStockAlertList()
    {
        if (! request()->ajax()) {
            abort(404);
        }

        $business_id = request()->session()->get('user.business_id');
        $permitted_locations = auth()->user()->permitted_locations();
        $query = $this->productUtil->getProductAlert($business_id, $permitted_locations);
        $count = (clone $query)->get()->count();
        $rows = $query->limit(40)->get();

        $products = $rows->map(function ($row) {
            if ($row->type == 'single') {
                $name = $row->product;
                $sku = $row->sku;
            } else {
                $name = trim($row->product.' - '.$row->variation);
                $sku = $row->sub_sku;
            }

            return [
                'name' => $name,
                'sku' => $sku,
                'location' => $row->location,
                'stock' => (float) ($row->stock ?? 0),
                'unit' => $row->unit ?? '',
            ];
        })->values();

        return response()->json([
            'count' => $count,
            'products' => $products,
        ]);
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getPurchasePaymentDues()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today = \Carbon::now()->format('Y-m-d H:i:s');

            $query = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                    ->leftJoin(
                        'transaction_payments as tp',
                        'transactions.id',
                        '=',
                        'tp.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'purchase')
                    ->where('transactions.payment_status', '!=', 'paid')
                    ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! empty(request()->input('location_id'))) {
                $query->where('transactions.location_id', request()->input('location_id'));
            }

            $dues = $query->select(
                'transactions.id as id',
                'c.name as supplier',
                'c.supplier_business_name',
                'ref_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                        ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;

                    return '<span class="display_currency" data-currency_symbol="true">'.
                    $due.'</span>';
                })
                ->addColumn('action', '@can("purchase.create") <a href="{{action([\App\Http\Controllers\TransactionPaymentController::class, \'addPayment\'], [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a> @endcan')
                ->removeColumn('supplier_business_name')
                ->editColumn('supplier', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$supplier}}')
                ->editColumn('ref_no', function ($row) {
                    if (auth()->user()->can('purchase.view')) {
                        return  '<a href="#" data-href="'.action([\App\Http\Controllers\PurchaseController::class, 'show'], [$row->id]).'"
                                    class="btn-modal" data-container=".view_modal">'.$row->ref_no.'</a>';
                    }

                    return $row->ref_no;
                })
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([0, 1, 2, 3])
                ->make(false);
        }
    }

    /**
     * Retrieves payment dues for the purchases.
     *
     * @return \Illuminate\Http\Response
     */
    public function getSalesPaymentDues()
    {
        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');
            $today = \Carbon::now()->format('Y-m-d H:i:s');

            $query = Transaction::join(
                'contacts as c',
                'transactions.contact_id',
                '=',
                'c.id'
            )
                    ->leftJoin(
                        'transaction_payments as tp',
                        'transactions.id',
                        '=',
                        'tp.transaction_id'
                    )
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell')
                    ->where('transactions.payment_status', '!=', 'paid')
                    ->whereNotNull('transactions.pay_term_number')
                    ->whereNotNull('transactions.pay_term_type')
                    ->whereRaw("DATEDIFF( DATE_ADD( transaction_date, INTERVAL IF(transactions.pay_term_type = 'days', transactions.pay_term_number, 30 * transactions.pay_term_number) DAY), '$today') <= 7");

            //Check for permitted locations of a user
            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $query->whereIn('transactions.location_id', $permitted_locations);
            }

            if (! empty(request()->input('location_id'))) {
                $query->where('transactions.location_id', request()->input('location_id'));
            }

            $dues = $query->select(
                'transactions.id as id',
                'c.name as customer',
                'c.supplier_business_name',
                'transactions.invoice_no',
                'final_total',
                DB::raw('SUM(tp.amount) as total_paid')
            )
                        ->groupBy('transactions.id');

            return Datatables::of($dues)
                ->addColumn('due', function ($row) {
                    $total_paid = ! empty($row->total_paid) ? $row->total_paid : 0;
                    $due = $row->final_total - $total_paid;

                    return '<span class="display_currency" data-currency_symbol="true">'.
                    $due.'</span>';
                })
                ->editColumn('invoice_no', function ($row) {
                    if (auth()->user()->can('sell.view')) {
                        return  '<a href="#" data-href="'.action([\App\Http\Controllers\SellController::class, 'show'], [$row->id]).'"
                                    class="btn-modal" data-container=".view_modal">'.$row->invoice_no.'</a>';
                    }

                    return $row->invoice_no;
                })
                ->addColumn('action', '@if(auth()->user()->can("sell.create") || auth()->user()->can("direct_sell.access")) <a href="{{action([\App\Http\Controllers\TransactionPaymentController::class, \'addPayment\'], [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent add_payment_modal"><i class="fas fa-money-bill-alt"></i> @lang("purchase.add_payment")</a> @endif')
                ->editColumn('customer', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$customer}}')
                ->removeColumn('supplier_business_name')
                ->removeColumn('id')
                ->removeColumn('final_total')
                ->removeColumn('total_paid')
                ->rawColumns([0, 1, 2, 3])
                ->make(false);
        }
    }

    public function loadMoreNotifications()
    {
        $notifications = auth()->user()->notifications()->orderBy('created_at', 'DESC')->paginate(10);

        if (request()->input('page') == 1) {
            auth()->user()->unreadNotifications->markAsRead();
        }
        $notifications_data = $this->commonUtil->parseNotifications($notifications);

        return view('layouts.partials.notification_list', compact('notifications_data'));
    }

    /**
     * Function to count total number of unread notifications
     *
     * @return json
     */
    public function getTotalUnreadNotifications()
    {
        $unread_notifications = auth()->user()->unreadNotifications;
        $total_unread = $unread_notifications->count();

        $notification_html = '';
        $modal_notifications = [];
        foreach ($unread_notifications as $unread_notification) {
            if (isset($data['show_popup'])) {
                $modal_notifications[] = $unread_notification;
                $unread_notification->markAsRead();
            }
        }
        if (! empty($modal_notifications)) {
            $notification_html = view('home.notification_modal')->with(['notifications' => $modal_notifications])->render();
        }

        return [
            'total_unread' => $total_unread,
            'notification_html' => $notification_html,
        ];
    }

    private function __chartOptions($title)
    {
        return [
            'chart' => [
                'backgroundColor' => '#ffffff',
                'style' => ['fontFamily' => "'Inter','Segoe UI',sans-serif"],
                'spacingTop' => 20,
                'spacingBottom' => 20,
                'spacingLeft' => 10,
                'spacingRight' => 24,
            ],
            'title' => [
                'text' => '',
            ],
            'subtitle' => [
                'text' => '',
            ],
            'xAxis' => [
                'gridLineWidth' => 0,
                'lineColor' => '#e2e8f0',
                'tickColor' => '#e2e8f0',
                'labels' => [
                    'style' => ['color' => '#94a3b8', 'fontSize' => '11px'],
                ],
            ],
            'yAxis' => [
                'title' => ['text' => $title, 'style' => ['color' => '#cbd5e1', 'fontSize' => '11px']],
                'gridLineColor' => '#f1f5f9',
                'gridLineDashStyle' => 'Dash',
                'labels' => [
                    'style' => ['color' => '#94a3b8', 'fontSize' => '11px'],
                ],
            ],
            'legend' => [
                'align' => 'center',
                'verticalAlign' => 'bottom',
                'layout' => 'horizontal',
                'itemStyle' => ['color' => '#64748b', 'fontWeight' => '500', 'fontSize' => '12px'],
                'symbolRadius' => 4,
            ],
            'tooltip' => [
                'backgroundColor' => '#1e293b',
                'borderWidth' => 0,
                'borderRadius' => 10,
                'style' => ['color' => '#f8fafc', 'fontSize' => '13px'],
                'shadow' => ['offsetX' => 0, 'offsetY' => 4, 'opacity' => 0.15, 'width' => 16],
                'headerFormat' => '<span style="font-size:11px;color:#94a3b8">{point.key}</span><br/>',
                'pointFormat' => '<span style="color:{point.color}">●</span> <b style="color:#fff">{point.y}</b><br/>',
            ],
            'plotOptions' => [
                'line' => [
                    'lineWidth' => 2.5,
                    'marker' => [
                        'enabled' => false,
                        'symbol' => 'circle',
                        'radius' => 4,
                        'states' => ['hover' => ['enabled' => true, 'radius' => 6]],
                    ],
                    'states' => ['hover' => ['lineWidth' => 3]],
                ],
                'series' => [
                    'animation' => ['duration' => 800],
                ],
            ],
            'colors' => ['#2563eb', '#16a34a', '#d97706', '#dc2626', '#7c3aed', '#0891b2'],
            'credits' => ['enabled' => false],
        ];
    }

    public function getCalendar()
    {
        $business_id = request()->session()->get('user.business_id');
        $is_admin = $this->restUtil->is_admin(auth()->user(), $business_id);
        $is_superadmin = auth()->user()->can('superadmin');
        if (request()->ajax()) {
            $data = [
                'start_date' => request()->start,
                'end_date' => request()->end,
                'user_id' => ($is_admin || $is_superadmin) && ! empty(request()->user_id) ? request()->user_id : auth()->user()->id,
                'location_id' => ! empty(request()->location_id) ? request()->location_id : null,
                'business_id' => $business_id,
                'events' => request()->events ?? [],
                'color' => '#007FFF',
            ];
            $events = [];

            if (in_array('bookings', $data['events'])) {
                $events = $this->restUtil->getBookingsForCalendar($data);
            }

            $module_events = $this->moduleUtil->getModuleData('calendarEvents', $data);

            foreach ($module_events as $module_event) {
                $events = array_merge($events, $module_event);
            }

            return $events;
        }

        $all_locations = BusinessLocation::forDropdown($business_id)->toArray();
        $users = [];
        if ($is_admin) {
            $users = User::forDropdown($business_id, false);
        }

        $event_types = [
            'bookings' => [
                'label' => __('restaurant.bookings'),
                'color' => '#007FFF',
            ],
        ];
        $module_event_types = $this->moduleUtil->getModuleData('eventTypes');
        foreach ($module_event_types as $module_event_type) {
            $event_types = array_merge($event_types, $module_event_type);
        }

        return view('home.calendar')->with(compact('all_locations', 'users', 'event_types'));
    }

    public function showNotification($id)
    {
        $notification = DatabaseNotification::find($id);

        $data = $notification->data;

        $notification->markAsRead();

        return view('home.notification_modal')->with([
            'notifications' => [$notification],
        ]);
    }

    public function attachMediasToGivenModel(Request $request)
    {
        if ($request->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');

                $model_id = $request->input('model_id');
                $model = $request->input('model_type');
                $model_media_type = $request->input('model_media_type');

                DB::beginTransaction();

                //find model to which medias are to be attached
                $model_to_be_attached = $model::where('business_id', $business_id)
                                        ->findOrFail($model_id);

                Media::uploadMedia($business_id, $model_to_be_attached, $request, 'file', false, $model_media_type);

                DB::commit();

                $output = [
                    'success' => true,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (Exception $e) {
                DB::rollBack();

                \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

                $output = [
                    'success' => false,
                    'msg' => __('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    public function getUserLocation($latlng)
    {
        $latlng_array = explode(',', $latlng);

        $response = $this->moduleUtil->getLocationFromCoordinates($latlng_array[0], $latlng_array[1]);

        return ['address' => $response];
    }
}
