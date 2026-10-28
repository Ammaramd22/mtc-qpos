<?php

namespace App\Http\Controllers;

use App\BusinessLocation;
use App\NutmegDrying;
use App\Product;
use App\PurchaseLine;
use App\Utils\ProductUtil;
use App\VariationLocationDetails;
use DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class NutmegReportController extends Controller
{
    protected ProductUtil $productUtil;

    public function __construct(ProductUtil $productUtil)
    {
        $this->productUtil = $productUtil;
    }

    protected function assertNutmeg(): void
    {
        if (! function_exists('nutmeg_enabled') || ! nutmeg_enabled()) {
            abort(403, __('lang_v1.nutmeg_module_disabled'));
        }
    }

    protected function assertCanView(): void
    {
        if (! auth()->user()->can('product.view') && ! auth()->user()->can('purchase.view') && ! auth()->user()->can('stock_report.view')) {
            abort(403, 'Unauthorized action.');
        }
    }

    protected function dryingBaseQuery(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        $query = NutmegDrying::where('nutmeg_dryings.business_id', $business_id)
            ->leftJoin('products as p', 'nutmeg_dryings.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'nutmeg_dryings.variation_id', '=', 'v.id')
            ->leftJoin('business_locations as bl', 'nutmeg_dryings.location_id', '=', 'bl.id')
            ->leftJoin('units', 'p.unit_id', '=', 'units.id');

        if (! empty($request->location_id)) {
            $query->where('nutmeg_dryings.location_id', $request->location_id);
        }

        if (! empty($request->product_id)) {
            $query->where('nutmeg_dryings.product_id', $request->product_id);
        }

        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $query->whereDate('nutmeg_dryings.created_at', '>=', $request->start_date)
                ->whereDate('nutmeg_dryings.created_at', '<=', $request->end_date);
        }

        return $query;
    }

    /**
     * Combined drying + product expenses → average cost report.
     */
    public function index()
    {
        $this->assertNutmeg();
        $this->assertCanView();

        $business_id = request()->session()->get('user.business_id');
        $business_locations = BusinessLocation::forDropdown($business_id, true);
        $products = Product::where('business_id', $business_id)
            ->where('type', '!=', 'modifier')
            ->orderBy('name')
            ->pluck('name', 'id');

        return view('nutmeg.reports.cost', compact('business_locations', 'products'));
    }

    /** @deprecated Keep old URLs working */
    public function dryingReport()
    {
        return redirect('/nutmeg/reports');
    }

    /** @deprecated Keep old URLs working */
    public function wastageReport()
    {
        return redirect('/nutmeg/reports');
    }

    /**
     * Summary cards for combined report.
     */
    public function reportSummary(Request $request)
    {
        $this->assertNutmeg();

        $drying = $this->dryingBaseQuery($request)
            ->select(
                DB::raw('COUNT(nutmeg_dryings.id) as drying_count'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_before), 0) as total_before'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_after), 0) as total_after'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_wastage), 0) as total_wastage'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_wastage * nutmeg_dryings.unit_cost_before), 0) as total_wastage_value')
            )
            ->first();

        $total_before = (float) ($drying->total_before ?? 0);
        $total_wastage = (float) ($drying->total_wastage ?? 0);
        $expense_total = $this->expenseTotalForFilters($request);

        return response()->json([
            'drying_count' => (int) ($drying->drying_count ?? 0),
            'total_before' => $total_before,
            'total_after' => (float) ($drying->total_after ?? 0),
            'total_wastage' => $total_wastage,
            'wastage_pct' => $total_before > 0 ? round(($total_wastage / $total_before) * 100, 2) : 0,
            'total_wastage_value' => (float) ($drying->total_wastage_value ?? 0),
            'total_expense' => $expense_total,
        ]);
    }

    /**
     * By-product avg cost: expenses / unit + drying wastage impact.
     */
    public function avgCostByProduct(Request $request)
    {
        $this->assertNutmeg();

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');
        $product_id = $request->get('product_id');

        $stockQuery = VariationLocationDetails::join('variations as v', 'variation_location_details.variation_id', '=', 'v.id')
            ->join('products as p', 'v.product_id', '=', 'p.id')
            ->leftJoin('business_locations as bl', 'variation_location_details.location_id', '=', 'bl.id')
            ->leftJoin('units', 'p.unit_id', '=', 'units.id')
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.type', '!=', 'modifier')
            ->where('variation_location_details.qty_available', '>', 0)
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'v.id as variation_id',
                'v.sub_sku',
                'v.dpp_inc_tax as avg_cost',
                'variation_location_details.location_id',
                'bl.name as location_name',
                'units.short_name as unit',
                'variation_location_details.qty_available as stock'
            );

        if (! empty($location_id)) {
            $stockQuery->where('variation_location_details.location_id', $location_id);
        }
        if (! empty($product_id)) {
            $stockQuery->where('p.id', $product_id);
        }

        $rows = $stockQuery->get();

        $dryingByKey = $this->dryingBaseQuery($request)
            ->select(
                'nutmeg_dryings.product_id',
                'nutmeg_dryings.variation_id',
                'nutmeg_dryings.location_id',
                DB::raw('COUNT(nutmeg_dryings.id) as drying_count'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_before), 0) as qty_before'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_after), 0) as qty_after'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_wastage), 0) as qty_wastage'),
                DB::raw('COALESCE(SUM(nutmeg_dryings.qty_wastage * nutmeg_dryings.unit_cost_before), 0) as wastage_value'),
                DB::raw('AVG(nutmeg_dryings.unit_cost_before) as avg_cost_before'),
                DB::raw('AVG(nutmeg_dryings.unit_cost_after) as avg_cost_after')
            )
            ->groupBy('nutmeg_dryings.product_id', 'nutmeg_dryings.variation_id', 'nutmeg_dryings.location_id')
            ->get()
            ->keyBy(function ($r) {
                return $r->variation_id.'_'.$r->location_id;
            });

        $data = [];
        foreach ($rows as $row) {
            $key = $row->variation_id.'_'.$row->location_id;
            $drying = $dryingByKey->get($key);
            $pe = $this->productUtil->getRemainingProductExpensesForStock(
                $business_id,
                $row->variation_id,
                $row->location_id
            );

            $stock = (float) $row->stock;
            $avg = (float) $row->avg_cost;
            $exp_total = (float) ($pe['remaining_expense_total'] ?? 0);
            $exp_per_unit = (float) ($pe['remaining_expense_per_unit'] ?? 0);
            // Approx buy cost in AVCO before expenses still in stock
            $buy_in_avg = max(0, $avg - $exp_per_unit);

            $qty_wastage = (float) ($drying->qty_wastage ?? 0);
            $qty_before = (float) ($drying->qty_before ?? 0);
            $wastage_pct = $qty_before > 0 ? round(($qty_wastage / $qty_before) * 100, 2) : 0;

            $data[] = [
                'product_name' => $row->product_name,
                'sub_sku' => $row->sub_sku,
                'location_name' => $row->location_name,
                'unit' => $row->unit,
                'stock' => $stock,
                'avg_cost' => $avg,
                'buy_in_avg' => round($buy_in_avg, 4),
                'expense_total' => $exp_total,
                'expense_per_unit' => $exp_per_unit,
                'drying_count' => (int) ($drying->drying_count ?? 0),
                'qty_before' => $qty_before,
                'qty_after' => (float) ($drying->qty_after ?? 0),
                'qty_wastage' => $qty_wastage,
                'wastage_pct' => $wastage_pct,
                'wastage_value' => (float) ($drying->wastage_value ?? 0),
                'avg_cost_before' => (float) ($drying->avg_cost_before ?? 0),
                'avg_cost_after' => (float) ($drying->avg_cost_after ?? $avg),
                'stock_value' => round($stock * $avg, 4),
            ];
        }

        // Also include products that had drying in range but now zero stock
        foreach ($dryingByKey as $drying) {
            $matched = false;
            foreach ($rows as $row) {
                if ($row->variation_id == $drying->variation_id && $row->location_id == $drying->location_id) {
                    $matched = true;
                    break;
                }
            }
            if ($matched) {
                continue;
            }

            $product = Product::with('unit')->find($drying->product_id);
            $location = BusinessLocation::find($drying->location_id);
            $variation = \App\Variation::find($drying->variation_id);
            $qty_before = (float) $drying->qty_before;
            $qty_wastage = (float) $drying->qty_wastage;

            $data[] = [
                'product_name' => $product->name ?? '—',
                'sub_sku' => $variation->sub_sku ?? '',
                'location_name' => $location->name ?? '—',
                'unit' => $product->unit->short_name ?? '',
                'stock' => 0,
                'avg_cost' => (float) ($variation->dpp_inc_tax ?? 0),
                'buy_in_avg' => (float) ($drying->avg_cost_before ?? 0),
                'expense_total' => 0,
                'expense_per_unit' => 0,
                'drying_count' => (int) $drying->drying_count,
                'qty_before' => $qty_before,
                'qty_after' => (float) $drying->qty_after,
                'qty_wastage' => $qty_wastage,
                'wastage_pct' => $qty_before > 0 ? round(($qty_wastage / $qty_before) * 100, 2) : 0,
                'wastage_value' => (float) $drying->wastage_value,
                'avg_cost_before' => (float) $drying->avg_cost_before,
                'avg_cost_after' => (float) $drying->avg_cost_after,
                'stock_value' => 0,
            ];
        }

        return DataTables::of(collect($data))
            ->addColumn('product_label', function ($row) {
                return e($row['product_name']).' <small class="text-muted">('.e($row['sub_sku']).')</small>';
            })
            ->editColumn('stock', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row['stock'].'</span> '.e($row['unit']);
            })
            ->editColumn('avg_cost', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['avg_cost'].'</span>';
            })
            ->editColumn('buy_in_avg', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['buy_in_avg'].'</span>';
            })
            ->editColumn('expense_total', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['expense_total'].'</span>';
            })
            ->editColumn('expense_per_unit', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['expense_per_unit'].'</span>';
            })
            ->editColumn('qty_wastage', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row['qty_wastage'].'</span> '
                    .e($row['unit']).' <small class="text-muted">('.$row['wastage_pct'].'%)</small>';
            })
            ->editColumn('wastage_value', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['wastage_value'].'</span>';
            })
            ->editColumn('avg_cost_before', function ($row) {
                if ($row['drying_count'] <= 0) {
                    return '—';
                }

                return '<span class="display_currency" data-currency_symbol="true">'.$row['avg_cost_before'].'</span>';
            })
            ->editColumn('avg_cost_after', function ($row) {
                if ($row['drying_count'] <= 0) {
                    return '—';
                }

                return '<span class="display_currency" data-currency_symbol="true">'.$row['avg_cost_after'].'</span>';
            })
            ->editColumn('stock_value', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row['stock_value'].'</span>';
            })
            ->rawColumns([
                'product_label', 'stock', 'avg_cost', 'buy_in_avg', 'expense_total', 'expense_per_unit',
                'qty_wastage', 'wastage_value', 'avg_cost_before', 'avg_cost_after', 'stock_value',
            ])
            ->make(true);
    }

    public function dryingReportData(Request $request)
    {
        $this->assertNutmeg();

        $query = $this->dryingBaseQuery($request)
            ->select(
                'nutmeg_dryings.*',
                'p.name as product_name',
                'v.sub_sku',
                'bl.name as location_name',
                'units.short_name as unit'
            )
            ->orderBy('nutmeg_dryings.id', 'desc');

        return DataTables::of($query)
            ->editColumn('created_at', '{{@format_datetime($created_at)}}')
            ->addColumn('product_label', function ($row) {
                return e($row->product_name).' <small class="text-muted">('.e($row->sub_sku).')</small>';
            })
            ->editColumn('qty_before', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row->qty_before.'</span> '.e($row->unit);
            })
            ->editColumn('qty_after', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row->qty_after.'</span> '.e($row->unit);
            })
            ->editColumn('qty_wastage', function ($row) {
                $pct = $row->qty_before > 0 ? round(($row->qty_wastage / $row->qty_before) * 100, 2) : 0;

                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row->qty_wastage.'</span> '.e($row->unit)
                    .' <small class="text-muted">('.$pct.'%)</small>';
            })
            ->editColumn('unit_cost_before', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->unit_cost_before.'</span>';
            })
            ->editColumn('unit_cost_after', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->unit_cost_after.'</span>';
            })
            ->addColumn('wastage_value', function ($row) {
                $val = (float) $row->qty_wastage * (float) $row->unit_cost_before;

                return '<span class="display_currency" data-currency_symbol="true">'.$val.'</span>';
            })
            ->rawColumns(['product_label', 'qty_before', 'qty_after', 'qty_wastage', 'unit_cost_before', 'unit_cost_after', 'wastage_value'])
            ->make(true);
    }

    /**
     * Expense lines (purchase) for the combined report.
     */
    public function expenseReportData(Request $request)
    {
        $this->assertNutmeg();

        if (! Schema::hasColumn('purchase_lines', 'product_expenses')) {
            return DataTables::of(collect([]))->make(true);
        }

        $business_id = $request->session()->get('user.business_id');

        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->join('products as p', 'purchase_lines.product_id', '=', 'p.id')
            ->leftJoin('variations as v', 'purchase_lines.variation_id', '=', 'v.id')
            ->leftJoin('business_locations as bl', 't.location_id', '=', 'bl.id')
            ->leftJoin('contacts', 't.contact_id', '=', 'contacts.id')
            ->leftJoin('units', 'p.unit_id', '=', 'units.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereNotNull('purchase_lines.product_expenses')
            ->where('purchase_lines.product_expenses', '!=', '[]')
            ->where('purchase_lines.product_expenses', '!=', 'null')
            ->select(
                'purchase_lines.id',
                'purchase_lines.quantity',
                'purchase_lines.purchase_price_inc_tax',
                'purchase_lines.product_expenses',
                't.transaction_date',
                't.ref_no',
                't.location_id',
                'p.name as product_name',
                'v.sub_sku',
                'bl.name as location_name',
                'units.short_name as unit',
                'contacts.name as supplier_name',
                'contacts.supplier_business_name'
            );

        if (! empty($request->location_id)) {
            $query->where('t.location_id', $request->location_id);
        }
        if (! empty($request->product_id)) {
            $query->where('purchase_lines.product_id', $request->product_id);
        }
        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $query->whereDate('t.transaction_date', '>=', $request->start_date)
                ->whereDate('t.transaction_date', '<=', $request->end_date);
        }

        return DataTables::of($query)
            ->editColumn('transaction_date', '{{@format_datetime($transaction_date)}}')
            ->addColumn('product_label', function ($row) {
                return e($row->product_name).' <small class="text-muted">('.e($row->sub_sku).')</small>';
            })
            ->addColumn('supplier', function ($row) {
                return e($row->supplier_business_name ?: ($row->supplier_name ?: '—'));
            })
            ->addColumn('expense_total', function ($row) {
                $expenses = is_array($row->product_expenses)
                    ? $row->product_expenses
                    : (json_decode($row->product_expenses ?? '[]', true) ?: []);
                $total = $this->productUtil->productExpenseTotal($expenses);

                return '<span class="display_currency" data-currency_symbol="true">'.$total.'</span>';
            })
            ->addColumn('expense_per_unit', function ($row) {
                $expenses = is_array($row->product_expenses)
                    ? $row->product_expenses
                    : (json_decode($row->product_expenses ?? '[]', true) ?: []);
                $total = $this->productUtil->productExpenseTotal($expenses);
                $qty = (float) $row->quantity;
                $per = $qty > 0 ? $total / $qty : 0;

                return '<span class="display_currency" data-currency_symbol="true">'.$per.'</span>';
            })
            ->addColumn('buy_price', function ($row) {
                $expenses = is_array($row->product_expenses)
                    ? $row->product_expenses
                    : (json_decode($row->product_expenses ?? '[]', true) ?: []);
                $total = $this->productUtil->productExpenseTotal($expenses);
                $qty = (float) $row->quantity;
                $landed = (float) $row->purchase_price_inc_tax;
                $buy = $qty > 0 ? $landed - ($total / $qty) : $landed;

                return '<span class="display_currency" data-currency_symbol="true">'.$buy.'</span>';
            })
            ->addColumn('landed_unit', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->purchase_price_inc_tax.'</span>';
            })
            ->addColumn('breakdown', function ($row) {
                $expenses = is_array($row->product_expenses)
                    ? $row->product_expenses
                    : (json_decode($row->product_expenses ?? '[]', true) ?: []);
                $parts = [];
                foreach ($expenses as $exp) {
                    $name = e($exp['name'] ?? 'Expense');
                    $amt = (float) ($exp['amount'] ?? 0);
                    $parts[] = $name.': <span class="display_currency" data-currency_symbol="true">'.$amt.'</span>';
                }

                return $parts ? implode('<br>', $parts) : '—';
            })
            ->editColumn('quantity', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row->quantity.'</span> '.e($row->unit);
            })
            ->rawColumns(['product_label', 'expense_total', 'expense_per_unit', 'buy_price', 'landed_unit', 'breakdown', 'quantity'])
            ->make(true);
    }

    /** Legacy aliases */
    public function wastageReportData(Request $request)
    {
        return $this->dryingReportData($request);
    }

    public function wastageByProduct(Request $request)
    {
        return $this->avgCostByProduct($request);
    }

    protected function expenseTotalForFilters(Request $request): float
    {
        if (! Schema::hasColumn('purchase_lines', 'product_expenses')) {
            return 0.0;
        }

        $business_id = $request->session()->get('user.business_id');
        $query = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'purchase')
            ->where('t.status', 'received')
            ->whereNotNull('purchase_lines.product_expenses')
            ->select('purchase_lines.product_expenses');

        if (! empty($request->location_id)) {
            $query->where('t.location_id', $request->location_id);
        }
        if (! empty($request->product_id)) {
            $query->where('purchase_lines.product_id', $request->product_id);
        }
        if (! empty($request->start_date) && ! empty($request->end_date)) {
            $query->whereDate('t.transaction_date', '>=', $request->start_date)
                ->whereDate('t.transaction_date', '<=', $request->end_date);
        }

        $total = 0.0;
        foreach ($query->get() as $line) {
            $expenses = is_array($line->product_expenses)
                ? $line->product_expenses
                : (json_decode($line->product_expenses ?? '[]', true) ?: []);
            $total += $this->productUtil->productExpenseTotal($expenses);
        }

        return $total;
    }
}
