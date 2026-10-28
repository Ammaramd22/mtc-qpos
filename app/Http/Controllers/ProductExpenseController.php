<?php

namespace App\Http\Controllers;

use App\Contact;
use App\PurchaseLine;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\Util;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductExpenseController extends Controller
{
    protected ProductUtil $productUtil;

    protected Util $commonUtil;

    public function __construct(ProductUtil $productUtil, Util $commonUtil)
    {
        $this->productUtil = $productUtil;
        $this->commonUtil = $commonUtil;
    }

    protected function assertNutmeg(): void
    {
        if (! function_exists('nutmeg_enabled') || ! nutmeg_enabled()) {
            abort(403, __('lang_v1.nutmeg_module_disabled'));
        }
        if (! auth()->user()->can('purchase.view') && ! auth()->user()->can('view_own_purchase')
            && ! auth()->user()->can('purchase.create') && ! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }
    }

    /**
     * Purchases that already have product expenses.
     */
    public function index()
    {
        $this->assertNutmeg();

        if (request()->ajax() || request()->wantsJson() || request()->has('draw')) {
            $business_id = request()->session()->get('user.business_id');

            $query = Transaction::where('transactions.business_id', $business_id)
                ->where('transactions.type', 'purchase')
                ->where('transactions.status', '!=', 'purchase_order')
                ->whereHas('purchase_lines', function ($q) {
                    $q->whereNotNull('product_expenses')
                        ->where('product_expenses', '!=', '[]')
                        ->where('product_expenses', '!=', 'null');
                })
                ->leftJoin('contacts', 'transactions.contact_id', '=', 'contacts.id')
                ->leftJoin('business_locations as bl', 'transactions.location_id', '=', 'bl.id')
                ->select(
                    'transactions.id',
                    'transactions.transaction_date',
                    'transactions.ref_no',
                    'transactions.final_total',
                    'transactions.status',
                    'bl.name as location_name',
                    'contacts.name as supplier_name',
                    'contacts.supplier_business_name'
                );

            if (auth()->user()->can('view_own_purchase') && ! auth()->user()->can('purchase.view')) {
                $query->where('transactions.created_by', request()->session()->get('user.id'));
            }

            return DataTables::of($query)
                ->addColumn('supplier', function ($row) {
                    return $row->supplier_business_name ?: ($row->supplier_name ?: '—');
                })
                ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
                ->editColumn('final_total', function ($row) {
                    return '<span class="display_currency" data-currency_symbol="true">'.$row->final_total.'</span>';
                })
                ->addColumn('expense_total', function ($row) {
                    $total = 0;
                    $lines = PurchaseLine::where('transaction_id', $row->id)->get(['product_expenses', 'quantity']);
                    foreach ($lines as $line) {
                        $total += $this->productUtil->productExpenseTotal(
                            is_array($line->product_expenses) ? $line->product_expenses : []
                        );
                    }

                    return '<span class="display_currency" data-currency_symbol="true">'.$total.'</span>';
                })
                ->addColumn('action', function ($row) {
                    $html = '<a href="'.action([self::class, 'create'], ['purchase_id' => $row->id]).'" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary">'
                        .__('messages.edit').'</a> ';
                    $html .= '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info btn-modal" data-href="'
                        .action([self::class, 'show'], [$row->id]).'" data-container=".view_modal">'
                        .__('messages.view').'</button>';

                    return $html;
                })
                ->rawColumns(['final_total', 'expense_total', 'action'])
                ->make(true);
        }

        return view('product_expense.index');
    }

    /**
     * View purchase product expenses (modal).
     */
    public function show($id)
    {
        $this->assertNutmeg();

        $business_id = request()->session()->get('user.business_id');
        $purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->with(['contact', 'location', 'purchase_lines.product', 'purchase_lines.variations'])
            ->findOrFail($id);

        $lines = [];
        $expense_grand = 0.0;
        foreach ($purchase->purchase_lines as $line) {
            $buy = $this->productUtil->purchaseLineBuyUnitCost($line);
            $expense_grand += $buy['expense_total'];
            $name = $line->product->name ?? '';
            if (! empty($line->variations) && ($line->product->type ?? '') === 'variable') {
                $name .= ' - '.($line->variations->name ?? '');
            }
            $lines[] = [
                'name' => $name,
                'sku' => $line->variations->sub_sku ?? ($line->product->sku ?? ''),
                'quantity' => (float) $line->quantity,
                'buy_unit_cost' => $buy['purchase_price_inc_tax'],
                'expense_total' => $buy['expense_total'],
                'landed_unit_cost' => (float) $line->purchase_price_inc_tax,
                'product_expenses' => $buy['product_expenses'],
            ];
        }

        return view('product_expense.show', compact('purchase', 'lines', 'expense_grand'));
    }

    /**
     * Form: optional supplier → purchase invoice → line product expenses.
     */
    public function create(Request $request)
    {
        $this->assertNutmeg();

        if (! auth()->user()->can('purchase.create') && ! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');
        $suppliers = Contact::suppliersDropdown($business_id, false);
        $purchase_id = $request->get('purchase_id');

        return view('product_expense.create', compact('suppliers', 'purchase_id'));
    }

    /**
     * AJAX: purchase invoices (optional supplier filter).
     */
    public function getInvoices(Request $request)
    {
        $this->assertNutmeg();

        $business_id = $request->session()->get('user.business_id');
        $term = $request->get('q', $request->get('term', ''));
        $contact_id = $request->get('contact_id');

        $query = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->whereIn('status', ['received', 'pending', 'ordered'])
            ->orderBy('transaction_date', 'desc')
            ->with('contact:id,name,supplier_business_name');

        if (! empty($contact_id)) {
            $query->where('contact_id', $contact_id);
        }

        if (auth()->user()->can('view_own_purchase') && ! auth()->user()->can('purchase.view')) {
            $query->where('created_by', $request->session()->get('user.id'));
        }

        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('ref_no', 'like', '%'.$term.'%')
                    ->orWhere('id', $term);
            });
        }

        $rows = $query->limit(50)->get(['id', 'ref_no', 'transaction_date', 'final_total', 'contact_id', 'status']);

        $results = [];
        foreach ($rows as $row) {
            $supplier = '';
            if ($row->contact) {
                $supplier = $row->contact->supplier_business_name ?: $row->contact->name;
            }
            $label = ($row->ref_no ?: '#'.$row->id)
                .' — '.$this->commonUtil->format_date($row->transaction_date, true)
                .' — '.$this->commonUtil->num_f($row->final_total, true)
                .($supplier ? ' ('.$supplier.')' : '');
            $results[] = [
                'id' => $row->id,
                'text' => $label,
                'contact_id' => $row->contact_id,
            ];
        }

        return response()->json(['results' => $results]);
    }

    /**
     * AJAX: purchase lines + current product expenses (buy unit cost stripped).
     */
    public function getLines($id)
    {
        $this->assertNutmeg();

        $business_id = request()->session()->get('user.business_id');
        $purchase = Transaction::where('business_id', $business_id)
            ->where('type', 'purchase')
            ->with(['purchase_lines.product', 'purchase_lines.variations', 'contact'])
            ->findOrFail($id);

        $lines = [];
        foreach ($purchase->purchase_lines as $line) {
            $buy = $this->productUtil->purchaseLineBuyUnitCost($line);
            $name = $line->product->name ?? '';
            if (! empty($line->variations) && ($line->product->type ?? '') === 'variable') {
                $name .= ' - '.($line->variations->name ?? '');
            }
            $lines[] = [
                'purchase_line_id' => $line->id,
                'product_name' => $name,
                'sku' => $line->variations->sub_sku ?? ($line->product->sku ?? ''),
                'quantity' => (float) $line->quantity,
                'buy_unit_cost' => $buy['purchase_price_inc_tax'],
                'expense_total' => $buy['expense_total'],
                'landed_unit_cost' => (float) $line->purchase_price_inc_tax,
                'product_expenses' => $buy['product_expenses'] ?: [['name' => '', 'amount' => '']],
            ];
        }

        $supplier = '';
        if ($purchase->contact) {
            $supplier = $purchase->contact->supplier_business_name ?: $purchase->contact->name;
        }

        return response()->json([
            'purchase' => [
                'id' => $purchase->id,
                'ref_no' => $purchase->ref_no,
                'transaction_date' => $this->commonUtil->format_date($purchase->transaction_date, true),
                'supplier' => $supplier,
                'contact_id' => $purchase->contact_id,
                'final_total' => (float) $purchase->final_total,
                'status' => $purchase->status,
            ],
            'lines' => $lines,
        ]);
    }

    /**
     * Save product expenses onto purchase lines → stock / AVCO only.
     */
    public function store(Request $request)
    {
        $this->assertNutmeg();

        if (! auth()->user()->can('purchase.create') && ! auth()->user()->can('purchase.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'purchase_id' => 'required|integer',
            'lines' => 'required|array',
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $purchase = Transaction::where('business_id', $business_id)
                ->where('type', 'purchase')
                ->findOrFail($request->input('purchase_id'));

            DB::beginTransaction();

            foreach ($request->input('lines', []) as $line_id => $payload) {
                $line = PurchaseLine::where('transaction_id', $purchase->id)
                    ->where('id', $line_id)
                    ->first();
                if (empty($line)) {
                    continue;
                }
                $expenses = $payload['product_expenses'] ?? [];
                $this->productUtil->setPurchaseLineProductExpenses($line, $expenses);
            }

            $purchase->refresh();
            $this->productUtil->updateVariationCostsFromPurchase($purchase, $business_id);

            DB::commit();

            $output = [
                'success' => 1,
                'msg' => __('lang_v1.product_expenses_saved'),
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            $output = [
                'success' => 0,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        if ($request->ajax()) {
            return response()->json($output);
        }

        if (! empty($output['success'])) {
            return redirect()
                ->action([self::class, 'index'])
                ->with('status', $output);
        }

        return redirect()->back()->with('status', $output);
    }
}
