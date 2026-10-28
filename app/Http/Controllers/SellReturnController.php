<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\Contact;
use App\Events\TransactionPaymentDeleted;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ContactUtil;
use App\Utils\ModuleUtil;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class SellReturnController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $productUtil;

    protected $transactionUtil;

    protected $contactUtil;

    protected $businessUtil;

    protected $moduleUtil;

    /**
     * Constructor
     *
     * @param  ProductUtils  $product
     * @return void
     */
    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil, ContactUtil $contactUtil, BusinessUtil $businessUtil, ModuleUtil $moduleUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
        $this->contactUtil = $contactUtil;
        $this->businessUtil = $businessUtil;
        $this->moduleUtil = $moduleUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        if (request()->ajax()) {
            $sells = Transaction::leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')

                ->join(
                    'business_locations AS bl',
                    'transactions.location_id',
                    '=',
                    'bl.id'
                )
                ->leftJoin(
                    'transactions as T1',
                    'transactions.return_parent_id',
                    '=',
                    'T1.id'
                )
                ->leftJoin(
                    'transaction_payments AS TP',
                    'transactions.id',
                    '=',
                    'TP.transaction_id'
                )
                ->where('transactions.business_id', $business_id)
                ->where('transactions.type', 'sell_return')
                ->where('transactions.status', 'final')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.invoice_no',
                    'contacts.name',
                    'contacts.supplier_business_name',
                    'transactions.final_total',
                    'transactions.payment_status',
                    'transactions.credit_note_status',
                    'bl.name as business_location',
                    'T1.invoice_no as parent_sale',
                    'T1.id as parent_sale_id',
                    DB::raw('SUM(TP.amount) as amount_paid')
                );

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $sells->whereIn('transactions.location_id', $permitted_locations);
            }

            if (!auth()->user()->can('access_sell_return') && auth()->user()->can('access_own_sell_return')) {
                $sells->where('transactions.created_by', request()->session()->get('user.id'));
            }

            //Add condition for created_by,used in sales representative sales report
            if (request()->has('created_by')) {
                $created_by = request()->get('created_by');
                if (!empty($created_by)) {
                    $sells->where('transactions.created_by', $created_by);
                }
            }

            //Add condition for location,used in sales representative expense report
            if (request()->has('location_id')) {
                $location_id = request()->get('location_id');
                if (!empty($location_id)) {
                    $sells->where('transactions.location_id', $location_id);
                }
            }

            if (!empty(request()->customer_id)) {
                $customer_id = request()->customer_id;
                $sells->where('contacts.id', $customer_id);
            }
            if (!empty(request()->start_date) && !empty(request()->end_date)) {
                $start = request()->start_date;
                $end = request()->end_date;
                $sells->whereDate('transactions.transaction_date', '>=', $start)
                    ->whereDate('transactions.transaction_date', '<=', $end);
            }

            $sells->groupBy('transactions.id');

           //for zatca module Retrieve the 'is_zatca' parameter from the request; default to 0 if not provided and only comes 1 from zatca module
            $is_zatca = !empty(request()->input('is_zatca')) ? request()->input('is_zatca') : 0;

            if ($is_zatca) {
                // Include 'zatca_status' in the selected columns
                $sells->addSelect('transactions.zatca_status');

                // Check if 'zatca_status' filter is provided in the request
                if (!empty(request()->input('zatca_status'))) {
                    // If 'zatca_status' is 'pending', filter transactions where 'zatca_status' is NULL
                    if (request()->input('zatca_status') == 'pending') {
                        $sells->whereNull('transactions.zatca_status');
                    } else {
                        // Otherwise, filter transactions by the given 'zatca_status' value
                        $sells->where('transactions.zatca_status', request()->input('zatca_status'));
                    }
                }

                // Only include locations that have a non-empty sync_from_datetime in the dedicated column
                $sells->whereNotNull('bl.zatca_sync_from_datetime');
                // Include transactions on or after the location's sync_from_datetime
                $sells->whereRaw('T1.transaction_date >= bl.zatca_sync_from_datetime');
            }


            return Datatables::of($sells, $is_zatca)
                ->addColumn(
                    'action',
                    function ($row) use ($is_zatca) {
                        if ($is_zatca) {
                            if ($row->zatca_status == 'success') {
                                return '<div class="btn-group">
                                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle"
                                    data-toggle="dropdown" aria-expanded="false">' .
                                    __('messages.actions') .
                                    '<span class="caret"></span><span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-left" role="menu">
                                    <li>
                                        <a class="download-xml" href="'.action([\Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'downloadXml'], [$row->id]).'">
                                            <i class="fas fa-file-download"></i> '.__('zatcaintegrationksa::lang.download_xml').'
                                        </a>
                                    </li>
                                    <li>
                                        <a class="download-a3-pdf" target="_blank"  href="'.action([\Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'return_print_pdf'], [$row->id]).'">
                                            <i class="fas fa-file-download"></i> '.__('zatcaintegrationksa::lang.download_a3_pdf').'
                                        </a>
                                    </li>
                                </ul></div>';                            
                            } else {
                                return '<a href="' . action([\Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'sync_sale_return'], [$row->id]) . '" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max return_sale_sycs">' . __('zatcaintegrationksa::lang.sync') . '</a>';
                            }
                        }
            $returnString = '<div class="btn-group">
                                <button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info tw-w-max dropdown-toggle"
                                    data-toggle="dropdown" aria-expanded="false">' . 
                                    __('messages.actions') . 
                                    '<span class="caret"></span>
                                    <span class="sr-only">Toggle Dropdown</span>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-right" role="menu">
                                    <li>
                                        <a href="#" class="btn-modal" data-container=".view_modal"
                                            data-href="' . route('sell-return.show', ['sell_return' => $row->parent_sale_id ?: $row->id]) . '">
                                            <i class="fas fa-eye" aria-hidden="true"></i> ' . __('messages.view') . '
                                        </a>
                                    </li>
                                    <li>
                                        <a href="' . (!empty($row->parent_sale_id) ? action('App\Http\Controllers\SellReturnController@add', [$row->parent_sale_id]) : '#') . '" class="' . (empty($row->parent_sale_id) ? 'disabled' : '') . '">
                                            <i class="fa fa-edit" aria-hidden="true"></i> ' . __('messages.edit') . '
                                        </a>
                                    </li>
                                    <li>
                                        <a href="' . action('App\Http\Controllers\SellReturnController@destroy', [$row->id]) . '" class="delete_sell_return">
                                            <i class="fa fa-trash" aria-hidden="true"></i> ' . __('messages.delete') . '
                                        </a>
                                    </li>
                                    <li>
                                        <a href="#" class="print-invoice" data-href="' . action('App\Http\Controllers\SellReturnController@printInvoice', [$row->id]) . '">
                                            <i class="fa fa-print" aria-hidden="true"></i> ' . __('messages.print') . '
                                        </a>
                                    </li>';
                            if ($row->payment_status != "paid") {
                    $returnString .= '<li>
                                        <a href="' . action('App\Http\Controllers\TransactionPaymentController@addPayment', [$row->id]) . '" class="add_payment_modal">
                                            <i class="fas fa-money-bill-alt"></i> ' . __('purchase.add_payment') . '
                                        </a>
                                    </li>';
                            }

                $returnString .= '<li>
                                    <a href="' . action('App\Http\Controllers\TransactionPaymentController@show', [$row->id]) . '" class="view_payment_modal">
                                        <i class="fas fa-money-bill-alt"></i> ' . __('purchase.view_payments') . '
                                    </a>
                                </li>
                                </ul>
                            </div>';

                        return $returnString;

                    }
                )
                ->removeColumn('id')
                ->editColumn(
                    'final_total',
                    '<span class="display_currency final_total" data-currency_symbol="true" data-orig-value="{{$final_total}}">{{$final_total}}</span>'
                )
                ->editColumn('parent_sale', function ($row) {
                    if (empty($row->parent_sale_id)) {
                        return '<span class="label label-info">' . __('lang_v1.standalone_return') . '</span>';
                    }
                    return '<button type="button" class="btn btn-link btn-modal" data-container=".view_modal" data-href="' . action([\App\Http\Controllers\SellController::class, 'show'], [$row->parent_sale_id]) . '">' . $row->parent_sale . '</button>';
                })
                ->editColumn('name', '@if(!empty($supplier_business_name)) {{$supplier_business_name}}, <br> @endif {{$name}}')
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn(
                    'payment_status',
                    '<a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, \'show\'], [$id])}}" class="view_payment_modal payment-status payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__(\'lang_v1.\' . $payment_status)}}"><span class="label @payment_status($payment_status)">{{__(\'lang_v1.\' . $payment_status)}}</span></a>'
                )
                ->addColumn('payment_due', function ($row) {
                    $due = $row->final_total - $row->amount_paid;

                    return '<span class="display_currency payment_due" data-currency_symbol="true" data-orig-value="' . $due . '">' . $due . '</sapn>';
                })
                ->editColumn('credit_note_status', function ($row) {
                    $status = $row->credit_note_status ?? 'not_used';
                    $labelClass = [
                        'not_used' => 'bg-gray',
                        'partially_used' => 'bg-yellow',
                        'used' => 'bg-green',
                    ][$status] ?? 'bg-gray';

                    return '<span class="label ' . $labelClass . '">' . ucfirst(str_replace('_', ' ', $status)) . '</span>';
                })
                ->editColumn('zatca_status', function ($row) use ($is_zatca) {
                    $status = '';
                    if($is_zatca){
                        if (empty($row->zatca_status) || is_null($row->zatca_status)) {
                            $status = '<small class="label bg-primary tw-dw-btn-xs no-print">'.__('zatcaintegrationksa::lang.pending').'</small>';
                        } elseif ($row->zatca_status == 'success') {
                            $status = '<small class="label bg-light-green tw-dw-btn-xs no-print">' . ucfirst($row->zatca_status) . '</small>';
                        } elseif ($row->zatca_status == 'failed') {
                                $lastDoc = \Modules\ZatcaIntegrationKsa\Entities\ZatcaDocument::where('transaction_id', $row->id)
                                    ->where('sent_to_zatca_status', 'failed')
                                    ->orderBy('created_at', 'desc')
                                    ->latest()
                                    ->first();

                                if ($lastDoc && $lastDoc->response_source == 'self' && !empty($lastDoc->response)) {
                                    $safeMsg = htmlspecialchars($lastDoc->response, ENT_QUOTES, 'UTF-8');
                                    $status = '<small class="label bg-red tw-dw-btn-xs no-print mb-1">' . ucfirst($row->zatca_status) . '</small><br><span class="text-danger">' . $safeMsg . '</span>';
                                } else if ($lastDoc) {
                                    $label = '<small class="label bg-red tw-dw-btn-xs no-print mb-1">' . ucfirst($row->zatca_status) . '</small>';
                                    $button = '<a href="' . action([\Modules\ZatcaIntegrationKsa\Http\Controllers\ZatcaInvoiceController::class, 'showInvoiceError'], ['id' => $row->id]) . '" class="btn btn-xs btn-danger no-print mt-2 status_fail" style="margin-top: 10px;">' . e(__('zatcaintegrationksa::lang.view_error')) . '</a>';
                                    $status = $label . '<br>' . $button;
                                }
                        }
                    }
                    return $status;
                })
                ->setRowAttr([
                    'data-href' => function ($row) {
                        if (auth()->user()->can('sell.view')) {
                            return route('sell-return.show', ['sell_return' => $row->parent_sale_id ?: $row->id]);
                        } else {
                            return '';
                        }
                    }])
                ->rawColumns(['final_total', 'action', 'parent_sale', 'payment_status', 'payment_due', 'name', 'zatca_status', 'credit_note_status'])
                ->make(true);
        }
        $business_locations = BusinessLocation::forDropdown($business_id, false);
        $customers = Contact::customersDropdown($business_id, false);

        $sales_representative = User::forDropdown($business_id, false, false, true);

        return view('sell_return.index')->with(compact('business_locations', 'customers', 'sales_representative'));
    }

    /**
     * Show the form for creating a standalone return (no invoice required).
     *
     * @return \Illuminate\Http\Response
     */
    public function standaloneCreate()
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');

        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $business_locations = BusinessLocation::forDropdown($business_id);
        $default_location = BusinessLocation::forDropdown($business_id, true);
        $walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);
        $taxes = TaxRate::forBusinessDropdown($business_id);

        return view('sell_return.standalone_create')
            ->with(compact('business_locations', 'default_location', 'walk_in_customer', 'taxes'));
    }

    /**
     * Lookup an existing POS sale invoice for Standalone Return (AJAX).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookupInvoiceForStandalone(Request $request)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.permission_denied'),
            ], 403);
        }

        $invoice_no = trim((string) $request->input('invoice_no', ''));
        if ($invoice_no === '') {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.invoice_could_not_be_found') ?: 'Invoice could not be found.',
            ]);
        }

        $business_id = $request->session()->get('user.business_id');

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where(function ($q) use ($invoice_no) {
                $q->where('invoice_no', $invoice_no)
                    ->orWhere('invoice_no', ltrim($invoice_no, '0'))
                    ->orWhere('invoice_no', str_pad($invoice_no, 4, '0', STR_PAD_LEFT));
            });

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('location_id', $permitted_locations);
        }

        if (!auth()->user()->can('access_sell_return') && auth()->user()->can('access_own_sell_return')) {
            $query->where('created_by', auth()->user()->id);
        }

        $sell = $query->with([
            'contact',
            'location',
            'sell_lines.product',
            'sell_lines.variations',
            'sell_lines.sub_unit',
        ])->first();

        if (empty($sell)) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.sell_not_found') ?: 'Invoice could not be found.',
            ]);
        }

        $lines = [];
        foreach ($sell->sell_lines as $line) {
            if (!empty($line->parent_sell_line_id)) {
                continue;
            }

            $sold = (float) $line->quantity;
            $returned = (float) ($line->quantity_returned ?? 0);
            $remaining = max(0, $sold - $returned);
            if ($remaining <= 0) {
                continue;
            }

            $name = optional($line->product)->name_with_tyre_size
                ?? optional($line->product)->name
                ?? 'Item';
            if (!empty($line->variations) && !empty($line->variations->name) && $line->variations->name !== 'DUMMY') {
                $name .= ' - '.$line->variations->name;
            }

            $lines[] = [
                'sell_line_id' => $line->id,
                'product_id' => $line->product_id,
                'variation_id' => $line->variation_id,
                'name' => $name,
                'sub_sku' => optional($line->variations)->sub_sku ?? '',
                'tyre_size' => optional($line->product)->tyre_size ?? '',
                'unit_price' => (float) $line->unit_price_inc_tax,
                'qty_sold' => $sold,
                'qty_returned' => $returned,
                'qty_returnable' => $remaining,
            ];
        }

        if (empty($lines)) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.no_products_available_for_return') ?: 'No returnable products left on this invoice.',
            ]);
        }

        return response()->json([
            'success' => 1,
            'msg' => __('lang_v1.success'),
            'sale' => [
                'transaction_id' => $sell->id,
                'invoice_no' => $sell->invoice_no,
                'transaction_date' => $sell->transaction_date,
                'location_id' => $sell->location_id,
                'location_name' => optional($sell->location)->name,
                'contact_id' => $sell->contact_id,
                'contact_name' => optional($sell->contact)->name,
                'contact_mobile' => optional($sell->contact)->mobile,
            ],
            'lines' => $lines,
        ]);
    }

    /**
     * Store a standalone sell return (no parent invoice), or invoice-linked return when transaction_id is set.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function storeStandalone(Request $request)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\SellReturnController::class, 'index']));
                }

                $user_id = $request->session()->get('user.id');

                DB::beginTransaction();

                // Invoice-linked return: reuse existing parent-sale return logic (qty + stock)
                if (!empty($input['transaction_id'])) {
                    $sell = Transaction::where('business_id', $business_id)
                        ->where('type', 'sell')
                        ->where('status', 'final')
                        ->with('sell_lines')
                        ->find($input['transaction_id']);

                    if (empty($sell)) {
                        throw new \Exception(__('lang_v1.sell_not_found') ?: 'Invoice could not be found.');
                    }

                    $sell_lines_by_id = $sell->sell_lines->keyBy('id');
                    $submitted_by_line = [];
                    foreach ($input['products'] as $product_line) {
                        $sell_line_id = $product_line['sell_line_id'] ?? null;
                        if (empty($sell_line_id)) {
                            continue;
                        }
                        $submitted_by_line[$sell_line_id] = $this->transactionUtil->num_uf($product_line['quantity'] ?? 0);
                    }

                    $return_products = [];
                    foreach ($sell->sell_lines as $sell_line) {
                        if (!empty($sell_line->parent_sell_line_id)) {
                            continue;
                        }

                        $already = (float) ($sell_line->quantity_returned ?? 0);
                        $sold = (float) $sell_line->quantity;
                        $qty_this_return = $submitted_by_line[$sell_line->id] ?? 0;
                        if ($qty_this_return < 0) {
                            $qty_this_return = 0;
                        }

                        $remaining = max(0, $sold - $already);
                        if ($qty_this_return > $remaining + 0.0001) {
                            throw new \Exception(
                                __('lang_v1.return_quantity_exceeds_remaining')
                                ?: 'Return quantity exceeds the remaining returnable quantity.'
                            );
                        }

                        $absolute = $already + $qty_this_return;
                        if ($absolute <= 0) {
                            continue;
                        }

                        $return_products[] = [
                            'sell_line_id' => $sell_line->id,
                            'product_id' => $sell_line->product_id,
                            'variation_id' => $sell_line->variation_id,
                            'quantity' => $absolute,
                            'unit_price' => $sell_line->unit_price_inc_tax,
                            'unit_price_inc_tax' => $sell_line->unit_price_inc_tax,
                        ];
                    }

                    if (empty($return_products) || empty(array_filter($submitted_by_line, function ($q) {
                        return $q > 0;
                    }))) {
                        throw new \Exception(__('lang_v1.no_products_added'));
                    }

                    // Return note number is auto-generated; do not overwrite with against-invoice no.
                    unset($input['invoice_no']);
                    $input['products'] = $return_products;
                    $input['location_id'] = $sell->location_id;
                    $input['contact_id'] = $sell->contact_id;

                    $sell_return = $this->transactionUtil->addSellReturn($input, $business_id, $user_id);
                } else {
                    // True standalone: no parent invoice
                    foreach ($input['products'] as $product_line) {
                        if (empty($product_line['product_id']) || empty($product_line['variation_id'])) {
                            throw new \Exception(__('lang_v1.product_could_not_be_found') ?: 'Product could not be found.');
                        }
                    }
                    // Auto-generate return note number unless a custom one is provided
                    if (empty($input['return_note_no'])) {
                        unset($input['invoice_no']);
                    } else {
                        $input['invoice_no'] = $input['return_note_no'];
                    }

                    $sell_return = $this->transactionUtil->addStandaloneSellReturn($input, $business_id, $user_id);
                }

                DB::commit();

                $receipt = $this->build80mmReceipt($sell_return, $business_id);

                $output = [
                    'success' => 1,
                    'msg' => __('lang_v1.success'),
                    'receipt' => $receipt,
                    'return_note_no' => $sell_return->invoice_no,
                ];
            } else {
                $output = [
                    'success' => 0,
                    'msg' => __('lang_v1.no_products_added'),
                ];
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $msg = $e->getMessage() ?: __('messages.something_went_wrong');
                // Keep generic message only for unexpected infra errors
                if (stripos($msg, 'SQLSTATE') !== false || stripos($msg, 'Stack') !== false) {
                    $msg = __('messages.something_went_wrong');
                }
            }

            $output = ['success' => 0, 'msg' => $msg];
        }

        return $output;
    }

    /**
     * Validate a credit note / return note for POS payment.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function validateCreditNote(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $note_no = $request->input('credit_note_no');

        $sell_return = Transaction::where('business_id', $business_id)
            ->where('type', 'sell_return')
            ->where('status', 'final')
            ->where(function ($q) use ($note_no) {
                $q->where('invoice_no', $note_no)
                  ->orWhere('ref_no', $note_no);
            })
            ->first();

        if (empty($sell_return)) {
            return ['success' => 0, 'msg' => __('lang_v1.credit_note_not_found')];
        }

        if ($sell_return->credit_note_status == 'used') {
            return ['success' => 0, 'msg' => __('lang_v1.credit_note_fully_used')];
        }

        //Calculate remaining credit (final_total - amount used in other sales)
        $amount_used = \App\TransactionPayment::where('credit_note_id', $sell_return->id)
            ->where('method', 'credit_note')
            ->sum('amount');
        $remaining = $sell_return->final_total - $amount_used;

        if ($remaining <= 0) {
            return ['success' => 0, 'msg' => __('lang_v1.credit_note_fully_used')];
        }

        return [
            'success' => 1,
            'credit_note_id' => $sell_return->id,
            'credit_note_no' => $sell_return->invoice_no,
            'total_amount' => (float) $sell_return->final_total,
            'amount_paid' => (float) $amount_used,
            'remaining' => (float) $remaining,
        ];
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    // public function create()
    // {
    //     if (!auth()->user()->can('sell.create')) {
    //         abort(403, 'Unauthorized action.');
    //     }

    //     $business_id = request()->session()->get('user.business_id');

    //     //Check if subscribed or not
    //     if (!$this->moduleUtil->isSubscribed($business_id)) {
    //         return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\SellReturnController::class, 'index']));
    //     }

    //     $business_locations = BusinessLocation::forDropdown($business_id);
    //     //$walk_in_customer = $this->contactUtil->getWalkInCustomer($business_id);

    //     return view('sell_return.create')
    //         ->with(compact('business_locations'));
    // }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function add($id)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        //Check if subscribed or not
        if (!$this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        }

        $sell = Transaction::where('business_id', $business_id)
            ->with(['sell_lines', 'location', 'return_parent', 'contact', 'tax', 'sell_lines.sub_unit', 'sell_lines.product', 'sell_lines.product.unit'])
            ->find($id);

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }

            $sell->sell_lines[$key]->formatted_qty = $this->transactionUtil->num_f($value->quantity, false, null, true);
        }

        $is_exchange = request()->boolean('exchange') || request()->get('exchange') == '1';

        return view('sell_return.add')
            ->with(compact('sell', 'is_exchange'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->except('_token');

            if (!empty($input['products'])) {
                $business_id = $request->session()->get('user.business_id');

                //Check if subscribed or not
                if (!$this->moduleUtil->isSubscribed($business_id)) {
                    return $this->moduleUtil->expiredResponse(action([\App\Http\Controllers\SellReturnController::class, 'index']));
                }

                $user_id = $request->session()->get('user.id');

                DB::beginTransaction();

                $sell_return = $this->transactionUtil->addSellReturn($input, $business_id, $user_id);

                $receipt = $this->receiptContent($business_id, $sell_return->location_id, $sell_return->id);

                // for zatca invoice response
                $this->moduleUtil->getModuleData('after_sales_return', ['transaction' => $sell_return]);

                DB::commit();

                $output = ['success' => 1,
                    'msg' => __('lang_v1.success'),
                    'receipt' => $receipt,
                ];

                // Exchange flow: return items, then continue on POS with credit note for other items
                if (!empty($input['is_exchange']) || !empty($input['exchange'])) {
                    $sell_return->loadMissing('contact');
                    $amount_used = \App\TransactionPayment::where('credit_note_id', $sell_return->id)
                        ->where('method', 'credit_note')
                        ->sum('amount');
                    $remaining = max(0, (float) $sell_return->final_total - (float) $amount_used);

                    $pos_params = http_build_query([
                        'exchange_cn' => $sell_return->invoice_no,
                        'exchange_cn_id' => $sell_return->id,
                        'exchange_remaining' => $remaining,
                        'contact_id' => $sell_return->contact_id,
                        'contact_name' => optional($sell_return->contact)->name,
                    ]);

                    $output['exchange'] = 1;
                    $output['credit_note_no'] = $sell_return->invoice_no;
                    $output['credit_note_id'] = $sell_return->id;
                    $output['remaining'] = $remaining;
                    $output['contact_id'] = $sell_return->contact_id;
                    $output['contact_name'] = optional($sell_return->contact)->name;
                    $output['msg'] = __('lang_v1.pos_exchange_return_saved');
                    $output['pos_url'] = action([\App\Http\Controllers\SellPosController::class, 'create']) . '?' . $pos_params;
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();

            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            } else {
                \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                $msg = __('messages.something_went_wrong');
            }

            $output = ['success' => 0,
                'msg' => $msg,
            ];
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
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $query = Transaction::where('business_id', $business_id)
            ->where('id', $id)
            ->with(
                'contact',
                'return_parent',
                'tax',
                'sell_lines',
                'sell_lines.product',
                'sell_lines.variations',
                'sell_lines.sub_unit',
                'sell_lines.product',
                'sell_lines.product.unit',
                'location'
            );

        if (!auth()->user()->can('access_sell_return') && auth()->user()->can('access_own_sell_return')) {
            $sells->where('created_by', request()->session()->get('user.id'));
        }
        $sell = $query->first();

        foreach ($sell->sell_lines as $key => $value) {
            if (!empty($value->sub_unit_id)) {
                $formated_sell_line = $this->transactionUtil->recalculateSellLineTotals($business_id, $value);
                $sell->sell_lines[$key] = $formated_sell_line;
            }
        }

        $sell_taxes = [];
        if (!empty($sell->return_parent->tax)) {
            if ($sell->return_parent->tax->is_tax_group) {
                $sell_taxes = $this->transactionUtil->sumGroupTaxDetails($this->transactionUtil->groupTaxDetails($sell->return_parent->tax, $sell->return_parent->tax_amount));
            } else {
                $sell_taxes[$sell->return_parent->tax->name] = $sell->return_parent->tax_amount;
            }
        }

        $total_discount = 0;
        if ($sell->return_parent->discount_type == 'fixed') {
            $total_discount = $sell->return_parent->discount_amount;
        } elseif ($sell->return_parent->discount_type == 'percentage') {
            $discount_percent = $sell->return_parent->discount_amount;
            if ($discount_percent == 100) {
                $total_discount = $sell->return_parent->total_before_tax;
            } else {
                $total_after_discount = $sell->return_parent->final_total - $sell->return_parent->tax_amount;
                $total_before_discount = $total_after_discount * 100 / (100 - $discount_percent);
                $total_discount = $total_before_discount - $total_after_discount;
            }
        }

        $activities = Activity::forSubject($sell->return_parent)
            ->with(['causer', 'subject'])
            ->latest()
            ->get();

        return view('sell_return.show')
            ->with(compact('sell', 'sell_taxes', 'total_discount', 'activities'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if (!auth()->user()->can('access_sell_return') && !auth()->user()->can('access_own_sell_return')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            try {
                $business_id = request()->session()->get('user.business_id');
                //Begin transaction
                DB::beginTransaction();

                $query = Transaction::where('id', $id)
                    ->where('business_id', $business_id)
                    ->where('type', 'sell_return')
                    ->with(['sell_lines', 'payment_lines']);

                if (!auth()->user()->can('access_sell_return') && auth()->user()->can('access_own_sell_return')) {
                    $sells->where('created_by', request()->session()->get('user.id'));
                }
                $sell_return = $query->first();

                $sell_lines = TransactionSellLine::where('transaction_id',
                    $sell_return->return_parent_id)
                    ->get();

                if (!empty($sell_return)) {
                    $transaction_payments = $sell_return->payment_lines;

                    foreach ($sell_lines as $sell_line) {
                        if ($sell_line->quantity_returned > 0) {
                            $quantity = 0;
                            $quantity_before = $this->transactionUtil->num_f($sell_line->quantity_returned);

                            $sell_line->quantity_returned = 0;
                            $sell_line->save();

                            //update quantity sold in corresponding purchase lines
                            $this->transactionUtil->updateQuantitySoldFromSellLine($sell_line, 0, $quantity_before);

                            // Update quantity in variation location details
                            $this->productUtil->updateProductQuantity($sell_return->location_id, $sell_line->product_id, $sell_line->variation_id, 0, $quantity_before);
                        }
                    }

                    $sell_return->delete();
                    foreach ($transaction_payments as $payment) {
                        event(new TransactionPaymentDeleted($payment));
                    }
                }

                DB::commit();
                $output = ['success' => 1,
                    'msg' => __('lang_v1.success'),
                ];
            } catch (\Exception $e) {
                DB::rollBack();

                if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                    $msg = $e->getMessage();
                } else {
                    \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());
                    $msg = __('messages.something_went_wrong');
                }

                $output = ['success' => 0,
                    'msg' => $msg,
                ];
            }

            return $output;
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
        $printer_type = null
    ) {
        $output = [
            'is_enabled' => false,
            'print_type' => 'browser',
            'html_content' => null,
            'printer_config' => [],
            'data' => [],
            'paper_width' => 80,
        ];

        $business_details = $this->businessUtil->getDetails($business_id);
        $location_details = BusinessLocation::find($location_id);
        $transaction = Transaction::where('business_id', $business_id)->find($transaction_id);

        if (empty($transaction) || empty($location_details)) {
            return $output;
        }

        $receipt_printer_type = is_null($printer_type) ? $location_details->receipt_printer_type : $printer_type;

        // Always print credit note / sell return as 80mm thermal
        $output['is_enabled'] = true;
        $output['print_title'] = $transaction->invoice_no;
        $output['paper_width'] = 80;

        if ($receipt_printer_type == 'printer') {
            $invoice_layout = $this->businessUtil->invoiceLayout($business_id, $location_details->invoice_layout_id);
            $receipt_details = $this->transactionUtil->getReceiptDetails($transaction_id, $location_id, $invoice_layout, $business_details, $location_details, $receipt_printer_type);
            $output['print_type'] = 'printer';
            $output['printer_config'] = $this->businessUtil->printerConfig($business_id, $location_details->printer_id);
            $output['data'] = $receipt_details;
        } else {
            $output['html_content'] = $this->build80mmReceipt($transaction, $business_id);
        }

        return $output;
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
                    ->first();

                if (empty($transaction)) {
                    return $output;
                }

                $receipt = $this->receiptContent($business_id, $transaction->location_id, $transaction_id, 'browser');

                if (!empty($receipt)) {
                    $output = ['success' => 1, 'receipt' => $receipt];
                }
            } catch (\Exception $e) {
                $output = ['success' => 0,
                    'msg' => trans('messages.something_went_wrong'),
                ];
            }

            return $output;
        }
    }

    /**
     * Function to validate sell for sell return
     */
    public function validateInvoiceToReturn($invoice_no)
    {
        if (!auth()->user()->can('sell.create') && !auth()->user()->can('direct_sell.access') && !auth()->user()->can('view_own_sell_only')) {
            return ['success' => 0,
                'msg' => trans('lang_v1.permission_denied'),
            ];
        }

        $business_id = request()->session()->get('user.business_id');
        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('invoice_no', $invoice_no);

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }

        if (!auth()->user()->can('direct_sell.access') && auth()->user()->can('view_own_sell_only')) {
            $query->where('created_by', auth()->user()->id);
        }

        $sell = $query->first();

        if (empty($sell)) {
            return ['success' => 0,
                'msg' => trans('lang_v1.sell_not_found'),
            ];
        }

        return ['success' => 1,
            'redirect_url' => action([\App\Http\Controllers\SellReturnController::class, 'add'], [$sell->id])
                . (request()->boolean('exchange') || request()->get('exchange') == '1' ? '?exchange=1' : ''),
        ];
    }

    /**
     * Build a simple 80mm thermal receipt for standalone returns.
     *
     * @param  Transaction  $transaction
     * @param  int  $business_id
     * @return string
     */
    private function build80mmReceipt($transaction, $business_id)
    {
        $business = \App\Business::with('currency')->find($business_id);
        $location = BusinessLocation::find($transaction->location_id);
        $lines = \App\TransactionSellLine::where('transaction_id', $transaction->id)
            ->with(['product', 'variations'])
            ->get();
        $customer = \App\Contact::find($transaction->contact_id);
        $parent = ! empty($transaction->return_parent_id)
            ? Transaction::find($transaction->return_parent_id)
            : null;

        $symbol = $business->currency->symbol ?? 'Rs';
        $date = \Carbon::parse($transaction->transaction_date)->format('d-m-Y H:i');
        $status = $transaction->credit_note_status ?? 'not_used';
        $status_label = [
            'used' => 'Used',
            'partially_used' => 'Partially Used',
            'not_used' => 'Not Used',
        ][$status] ?? 'Not Used';

        $fmt = function ($n) use ($symbol) {
            return $symbol.' '.number_format((float) $n, 2, '.', ',');
        };

        $html = '<div id="printable_receipt" class="vspos-cn-receipt" data-paper-width="80" style="width:80mm;max-width:80mm;margin:0 auto;padding:3mm 2.5mm 4mm;font-family:Arial,Helvetica,sans-serif;font-size:11px;line-height:1.35;color:#111;background:#fff;box-sizing:border-box;">';
        $html .= '<style>@page{size:80mm auto;margin:0}.vspos-cn-receipt *{box-sizing:border-box}</style>';

        $html .= '<div style="text-align:center;margin-bottom:6px;">';
        if (! empty($business->logo) && file_exists(public_path('uploads/business_logos/'.$business->logo))) {
            $html .= '<img src="'.asset('uploads/business_logos/'.$business->logo).'" style="max-width:48mm;max-height:18mm;margin:0 auto 4px;display:block;" alt="">';
        }
        $html .= '<div style="font-size:14px;font-weight:800;letter-spacing:.3px;">'.e($business->name ?? '').'</div>';
        if (! empty($location)) {
            $addr = trim(implode(', ', array_filter([$location->name, $location->landmark, $location->city])));
            if ($addr !== '') {
                $html .= '<div style="font-size:10px;color:#333;margin-top:2px;">'.e($addr).'</div>';
            }
            if (! empty($location->mobile)) {
                $html .= '<div style="font-size:10px;color:#333;">Tel: '.e($location->mobile).'</div>';
            }
        }
        $html .= '</div>';

        $html .= '<div style="text-align:center;border-top:1.5px dashed #111;border-bottom:1.5px dashed #111;padding:6px 0;margin:6px 0;">';
        $html .= '<div style="font-size:12px;font-weight:800;text-transform:uppercase;">Credit Note</div>';
        $html .= '<div style="font-size:13px;font-weight:700;margin-top:2px;">'.e($transaction->invoice_no).'</div>';
        $html .= '<div style="font-size:10px;margin-top:2px;">'.$date.'</div>';
        if ($parent) {
            $html .= '<div style="font-size:10px;margin-top:2px;">Against Invoice: <strong>'.e($parent->invoice_no).'</strong></div>';
        }
        $html .= '</div>';

        if ($customer) {
            $html .= '<div style="margin-bottom:6px;font-size:11px;">';
            $html .= '<div><strong>Customer:</strong> '.e($customer->name ?? '').'</div>';
            if (! empty($customer->mobile)) {
                $html .= '<div><strong>Mobile:</strong> '.e($customer->mobile).'</div>';
            }
            $html .= '</div>';
        }

        $html .= '<table style="width:100%;border-collapse:collapse;margin-bottom:6px;font-size:10.5px;">';
        $html .= '<thead><tr style="border-bottom:1px solid #111;">';
        $html .= '<th style="text-align:left;padding:3px 0;">Item</th>';
        $html .= '<th style="text-align:right;padding:3px 0;">Qty</th>';
        $html .= '<th style="text-align:right;padding:3px 0;">Rate</th>';
        $html .= '<th style="text-align:right;padding:3px 0;">Amt</th>';
        $html .= '</tr></thead><tbody>';

        foreach ($lines as $line) {
            $name = optional($line->product)->name ?? 'Item';
            if (! empty($line->variations) && ! empty($line->variations->name) && $line->variations->name != 'DUMMY') {
                $name .= ' - '.$line->variations->name;
            }
            $qty = (float) $line->quantity;
            $price = (float) $line->unit_price_inc_tax;
            if ($price <= 0) {
                $price = (float) $line->unit_price;
            }
            $total = $qty * $price;
            $html .= '<tr>';
            $html .= '<td style="text-align:left;padding:3px 0;vertical-align:top;word-break:break-word;">'.e($name).'</td>';
            $html .= '<td style="text-align:right;padding:3px 0;vertical-align:top;">'.rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.').'</td>';
            $html .= '<td style="text-align:right;padding:3px 0;vertical-align:top;">'.number_format($price, 2, '.', ',').'</td>';
            $html .= '<td style="text-align:right;padding:3px 0;vertical-align:top;">'.number_format($total, 2, '.', ',').'</td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        $html .= '<div style="border-top:1.5px dashed #111;padding-top:6px;">';
        if ((float) $transaction->discount_amount > 0) {
            $html .= '<div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span>Discount</span><span>- '.$fmt($transaction->discount_amount).'</span></div>';
        }
        if ((float) $transaction->tax_amount > 0) {
            $html .= '<div style="display:flex;justify-content:space-between;font-size:11px;margin-bottom:2px;"><span>Tax</span><span>+ '.$fmt($transaction->tax_amount).'</span></div>';
        }
        $html .= '<div style="display:flex;justify-content:space-between;font-size:13px;font-weight:800;margin-top:4px;"><span>CREDIT TOTAL</span><span>'.$fmt($transaction->final_total).'</span></div>';
        $html .= '</div>';

        $html .= '<div style="text-align:center;margin-top:8px;border-top:1.5px dashed #111;padding-top:8px;font-size:10.5px;">';
        $html .= '<div>This credit note can be used for future purchases.</div>';
        $html .= '<div style="margin-top:4px;font-weight:700;">Status: '.e($status_label).'</div>';
        $html .= '</div>';

        $html .= '<div style="text-align:center;margin-top:8px;font-size:10px;color:#444;">Thank you</div>';
        $html .= '</div>';

        return $html;
    }
}
