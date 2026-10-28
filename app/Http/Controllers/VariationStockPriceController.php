<?php

namespace App\Http\Controllers;

use App\Product;
use App\Variation;
use App\VariationStockPrice;
use Illuminate\Http\Request;

class VariationStockPriceController extends Controller
{
    private function moduleEnabled(): bool
    {
        $enabled = session('business.enabled_modules') ?? [];
        return in_array('stock_price_management', $enabled);
    }

    /**
     * List all manual stock prices for a product (loaded in modal via AJAX).
     */
    public function index(Request $request)
    {
        if (! $this->moduleEnabled()) {
            abort(403);
        }

        $business_id = $request->session()->get('user.business_id');
        $product_id = $request->input('product_id');

        $prices = VariationStockPrice::where('business_id', $business_id)
            ->where('product_id', $product_id)
            ->with('variation')
            ->orderBy('variation_id')
            ->orderBy('created_at', 'desc')
            ->get();

        $product = Product::where('business_id', $business_id)
            ->with(['variations'])
            ->findOrFail($product_id);

        return view('product.partials.stock_prices_modal_content', compact('product', 'prices'));
    }

    /**
     * Save a new manual price entry.
     */
    public function store(Request $request)
    {
        if (! $this->moduleEnabled()) {
            abort(403);
        }

        $business_id = $request->session()->get('user.business_id');

        $request->validate([
            'product_id'       => 'required|integer',
            'variation_id'     => 'required|integer',
            'sell_price_inc_tax' => 'required|numeric|min:0',
        ]);

        $variation = Variation::where('product_id', $request->product_id)->findOrFail($request->variation_id);

        $sp = VariationStockPrice::create([
            'business_id'        => $business_id,
            'product_id'         => $request->product_id,
            'variation_id'       => $variation->id,
            'label'              => $request->input('label'),
            'sell_price_inc_tax' => $request->sell_price_inc_tax,
            'is_active'          => 1,
        ]);

        $product = \App\Product::find($request->product_id);
        $variation_name = $product->type == 'variable'
            ? (optional($variation->product_variation)->name . ' - ' . $variation->name)
            : 'Default';
        $variation_name .= ' (' . $variation->sub_sku . ')';

        return response()->json([
            'success'        => true,
            'msg'            => 'Price added successfully.',
            'id'             => $sp->id,
            'variation_name' => $variation_name,
            'label'          => $sp->label,
            'price_formatted' => app('App\Utils\TransactionUtil')->num_f($sp->sell_price_inc_tax, true),
            'created_at'     => $sp->created_at->format('d M Y'),
        ]);
    }

    /**
     * Delete a manual price entry.
     */
    public function destroy(Request $request, $id)
    {
        if (! $this->moduleEnabled()) {
            abort(403);
        }

        $business_id = $request->session()->get('user.business_id');

        VariationStockPrice::where('business_id', $business_id)->findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
