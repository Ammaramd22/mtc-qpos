<?php

namespace App\Http\Controllers;

use App\Business;
use App\BusinessLocation;
use App\NutmegDrying;
use App\Product;
use App\Transaction;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use App\Variation;
use App\VariationLocationDetails;
use DB;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class NutmegDryingController extends Controller
{
    protected ProductUtil $productUtil;

    protected TransactionUtil $transactionUtil;

    public function __construct(ProductUtil $productUtil, TransactionUtil $transactionUtil)
    {
        $this->productUtil = $productUtil;
        $this->transactionUtil = $transactionUtil;
    }

    protected function assertNutmeg(): void
    {
        if (! function_exists('nutmeg_enabled') || ! nutmeg_enabled()) {
            abort(403, __('lang_v1.nutmeg_module_disabled'));
        }
    }

    /**
     * Drying hub — product stock list + history.
     */
    public function index()
    {
        $this->assertNutmeg();

        if (! auth()->user()->can('product.view') && ! auth()->user()->can('purchase.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $locations = BusinessLocation::forDropdown($business_id, false);

        return view('nutmeg.drying.index', compact('locations'));
    }

    /**
     * Products with stock for drying table.
     */
    public function products(Request $request)
    {
        $this->assertNutmeg();

        $business_id = $request->session()->get('user.business_id');
        $location_id = $request->get('location_id');

        $query = Variation::join('products as p', 'variations.product_id', '=', 'p.id')
            ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
            ->leftJoin('units', 'p.unit_id', '=', 'units.id')
            ->leftJoin('variation_location_details as vld', function ($join) use ($location_id) {
                $join->on('variations.id', '=', 'vld.variation_id');
                if (! empty($location_id)) {
                    $join->where('vld.location_id', '=', $location_id);
                }
            })
            ->where('p.business_id', $business_id)
            ->where('p.enable_stock', 1)
            ->where('p.type', '!=', 'modifier')
            ->select(
                'p.id as product_id',
                'p.name as product_name',
                'p.type',
                'variations.id as variation_id',
                'variations.name as variation_name',
                'variations.sub_sku',
                'variations.dpp_inc_tax',
                'variations.default_purchase_price',
                'pv.name as product_variation',
                'units.short_name as unit',
                DB::raw('COALESCE(SUM(vld.qty_available), 0) as stock')
            )
            ->groupBy('variations.id');

        if (! empty($location_id)) {
            $query->havingRaw('COALESCE(SUM(vld.qty_available), 0) > 0');
        }

        return DataTables::of($query)
            ->addColumn('product_label', function ($row) {
                $name = $row->product_name;
                if ($row->type === 'variable') {
                    $name .= ' - '.$row->product_variation.' - '.$row->variation_name;
                }

                return $name;
            })
            ->editColumn('stock', function ($row) {
                return '<span class="display_currency" data-is_quantity="true" data-currency_symbol="false">'.$row->stock.'</span> '.e($row->unit);
            })
            ->editColumn('dpp_inc_tax', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->dpp_inc_tax.'</span>';
            })
            ->addColumn('action', function ($row) use ($location_id) {
                return '<button type="button" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-primary tw-text-white btn-open-drying"
                    data-product-id="'.$row->product_id.'"
                    data-variation-id="'.$row->variation_id.'"
                    data-location-id="'.e($location_id).'"
                    data-product-name="'.e($row->product_name).'"
                    data-sku="'.e($row->sub_sku).'"
                    data-unit="'.e($row->unit).'"
                    data-stock="'.$row->stock.'"
                    data-avg-cost="'.$row->dpp_inc_tax.'">'
                    .__('lang_v1.dry_product').'</button>';
            })
            ->rawColumns(['stock', 'dpp_inc_tax', 'action'])
            ->make(true);
    }

    /**
     * Live stock + projected AVCO for drying popup.
     */
    public function preview(Request $request)
    {
        $this->assertNutmeg();

        $request->validate([
            'variation_id' => 'required|integer',
            'location_id' => 'required|integer',
            'qty_before' => 'nullable|numeric',
            'qty_after' => 'nullable|numeric',
        ]);

        $business_id = $request->session()->get('user.business_id');
        $variation_id = (int) $request->variation_id;
        $location_id = (int) $request->location_id;

        $details = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, false, true);
        if (empty($details)) {
            return response()->json(['success' => 0, 'msg' => __('messages.something_went_wrong')], 404);
        }

        $stock = (float) ($details->qty_available ?? 0);
        $avg = (float) ($details->dpp_inc_tax ?? 0);
        $qty_before = (float) $this->productUtil->num_uf($request->input('qty_before', 0));
        $qty_after = (float) $this->productUtil->num_uf($request->input('qty_after', 0));
        $wastage = max(0, $qty_before - $qty_after);

        $projected_stock = $stock;
        $projected_avg = $avg;
        $total_value = $stock * $avg;

        if ($qty_before > 0 && $wastage > 0 && $stock >= $wastage) {
            $projected_stock = $stock - $wastage;
            $projected_avg = $projected_stock > 0 ? ($total_value / $projected_stock) : $avg;
        }

        return response()->json([
            'success' => 1,
            'stock' => $stock,
            'avg_cost' => $avg,
            'stock_value' => $total_value,
            'qty_before' => $qty_before,
            'qty_after' => $qty_after,
            'wastage' => $wastage,
            'wastage_pct' => $qty_before > 0 ? round(($wastage / $qty_before) * 100, 2) : 0,
            'projected_stock' => $projected_stock,
            'projected_avg_cost' => round($projected_avg, 4),
            'projected_stock_value' => round($projected_stock * $projected_avg, 4),
            'unit' => $details->unit ?? '',
            'product_name' => $details->product_name ?? ($details->name ?? ''),
        ]);
    }

    /**
     * Save drying: reduce wastage from stock, roll cost into remaining AVCO.
     */
    public function store(Request $request)
    {
        $this->assertNutmeg();

        if (! auth()->user()->can('purchase.create') && ! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'location_id' => 'required|integer',
            'product_id' => 'required|integer',
            'variation_id' => 'required|integer',
            'qty_before' => 'required|numeric|gt:0',
            'qty_after' => 'required|numeric|gte:0',
        ]);

        try {
            $business_id = $request->session()->get('user.business_id');
            $user_id = $request->session()->get('user.id');
            $location_id = (int) $request->location_id;
            $product_id = (int) $request->product_id;
            $variation_id = (int) $request->variation_id;
            $qty_before = (float) $this->productUtil->num_uf($request->qty_before);
            $qty_after = (float) $this->productUtil->num_uf($request->qty_after);
            $wastage = $qty_before - $qty_after;

            if ($wastage < 0) {
                return response()->json(['success' => 0, 'msg' => __('lang_v1.drying_after_must_be_lte_before')]);
            }
            if ($wastage == 0) {
                return response()->json(['success' => 0, 'msg' => __('lang_v1.drying_no_wastage')]);
            }

            $details = $this->productUtil->getDetailsFromVariation($variation_id, $business_id, $location_id, false, true);
            $stock = (float) ($details->qty_available ?? 0);
            $avg = (float) ($details->dpp_inc_tax ?? 0);

            if ($qty_before > $stock + 0.0001) {
                return response()->json(['success' => 0, 'msg' => __('lang_v1.drying_before_exceeds_stock')]);
            }
            if ($wastage > $stock + 0.0001) {
                return response()->json(['success' => 0, 'msg' => __('lang_v1.drying_wastage_exceeds_stock')]);
            }

            $total_value = $stock * $avg;
            $stock_after = $stock - $wastage;
            $avg_after = $stock_after > 0 ? ($total_value / $stock_after) : $avg;

            DB::beginTransaction();

            $adjustment = null;
            if ($wastage > 0) {
                $ref_count = $this->productUtil->setAndGetReferenceCount('stock_adjustment');
                $adjustment = Transaction::create([
                    'type' => 'stock_adjustment',
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'location_id' => $location_id,
                    'transaction_date' => \Carbon\Carbon::now(),
                    'adjustment_type' => 'normal',
                    'final_total' => $wastage * $avg,
                    'total_amount_recovered' => 0,
                    'additional_notes' => __('lang_v1.drying_wastage_note', [
                        'before' => $qty_before,
                        'after' => $qty_after,
                    ]),
                    'ref_no' => $this->productUtil->generateReferenceNumber('stock_adjustment', $ref_count),
                ]);

                $adjustment->stock_adjustment_lines()->create([
                    'product_id' => $product_id,
                    'variation_id' => $variation_id,
                    'quantity' => $wastage,
                    'unit_price' => $avg,
                ]);

                $this->productUtil->decreaseProductQuantity($product_id, $variation_id, $location_id, $wastage);

                $business = [
                    'id' => $business_id,
                    'accounting_method' => $request->session()->get('business.accounting_method'),
                    'location_id' => $location_id,
                ];
                $this->transactionUtil->mapPurchaseSell($business, $adjustment->stock_adjustment_lines, 'stock_adjustment');
            }

            $variation = Variation::findOrFail($variation_id);
            $variation->dpp_inc_tax = $avg_after;
            $variation->default_purchase_price = $avg_after;
            if ((float) $variation->default_sell_price > 0 && $avg_after > 0) {
                $variation->profit_percent = $this->productUtil->get_percent($avg_after, $variation->default_sell_price);
            }
            $variation->save();

            $drying = NutmegDrying::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'qty_before' => $qty_before,
                'qty_after' => $qty_after,
                'qty_wastage' => $wastage,
                'unit_cost_before' => $avg,
                'unit_cost_after' => $avg_after,
                'stock_before' => $stock,
                'stock_after' => $stock_after,
                'stock_adjustment_id' => $adjustment->id ?? null,
                'notes' => $request->input('notes'),
                'created_by' => $user_id,
            ]);

            DB::commit();

            return response()->json([
                'success' => 1,
                'msg' => __('lang_v1.drying_saved'),
                'drying' => [
                    'id' => $drying->id,
                    'wastage' => $wastage,
                    'unit_cost_before' => $avg,
                    'unit_cost_after' => round($avg_after, 4),
                    'stock_after' => $stock_after,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());

            $msg = __('messages.something_went_wrong');
            if (get_class($e) == \App\Exceptions\PurchaseSellMismatch::class) {
                $msg = $e->getMessage();
            }

            return response()->json(['success' => 0, 'msg' => $msg]);
        }
    }

    /**
     * Recent drying history.
     */
    public function history()
    {
        $this->assertNutmeg();

        $business_id = request()->session()->get('user.business_id');

        $query = NutmegDrying::where('business_id', $business_id)
            ->with(['product:id,name', 'variation:id,sub_sku,name', 'location:id,name'])
            ->orderBy('id', 'desc');

        return DataTables::of($query)
            ->addColumn('product_label', function ($row) {
                return ($row->product->name ?? '').' ('.($row->variation->sub_sku ?? '').')';
            })
            ->editColumn('created_at', '{{@format_datetime($created_at)}}')
            ->editColumn('unit_cost_before', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->unit_cost_before.'</span>';
            })
            ->editColumn('unit_cost_after', function ($row) {
                return '<span class="display_currency" data-currency_symbol="true">'.$row->unit_cost_after.'</span>';
            })
            ->rawColumns(['unit_cost_before', 'unit_cost_after'])
            ->make(true);
    }
}
