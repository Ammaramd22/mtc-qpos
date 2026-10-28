<?php

namespace App\Http\Controllers;

use App\Business;
use App\Product;
use App\Variation;
use App\VariationLocationDetails;
use Illuminate\Http\Request;

class CommunicationController extends Controller
{
    /**
     * Settings page under Products → Communication.
     */
    public function settings()
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        if (function_exists('feature_enabled') && ! feature_enabled('communication')) {
            abort(403, __('lang_v1.feature_disabled_by_owner'));
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        $common = is_array($business->common_settings) ? $business->common_settings : [];
        $settings = ! empty($common['communication']) && is_array($common['communication'])
            ? $common['communication']
            : [];

        $enabled_modules = (array) ($business->enabled_modules ?? []);
        $module_enabled = in_array('communication', $enabled_modules, true);

        // Paper candidates: stock-managed products
        $products = Product::where('business_id', $business_id)
            ->where('enable_stock', 1)
            ->where('type', '!=', 'modifier')
            ->with(['variations' => function ($q) {
                $q->select('id', 'product_id', 'name', 'sub_sku', 'default_purchase_price', 'sell_price_inc_tax', 'default_sell_price');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'enable_stock']);

        $paperOptions = [];
        foreach ($products as $product) {
            foreach ($product->variations as $v) {
                $label = $product->name;
                if (! empty($v->name) && $v->name !== 'DUMMY') {
                    $label .= ' — '.$v->name;
                }
                if (! empty($v->sub_sku)) {
                    $label .= ' ('.$v->sub_sku.')';
                }
                $paperOptions[$v->id] = [
                    'label' => $label,
                    'product_id' => $product->id,
                    'cost' => (float) ($v->default_purchase_price ?? 0),
                    'sell' => (float) ($v->sell_price_inc_tax ?? $v->default_sell_price ?? 0),
                ];
            }
        }

        return view('communication.settings', compact('settings', 'module_enabled', 'paperOptions', 'business'));
    }

    /**
     * Save communication settings.
     */
    public function updateSettings(Request $request)
    {
        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        if (function_exists('feature_enabled') && ! feature_enabled('communication')) {
            abort(403, __('lang_v1.feature_disabled_by_owner'));
        }

        $business_id = $request->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        $common = is_array($business->common_settings) ? $business->common_settings : [];

        $photocopy = array_values(array_filter(array_map('intval', (array) $request->input('photocopy_variation_ids', []))));
        $printout = array_values(array_filter(array_map('intval', (array) $request->input('printout_variation_ids', []))));

        $num = function ($key, $default = 0) use ($request) {
            $v = $request->input($key, $default);
            if (is_string($v)) {
                $v = str_replace(',', '', $v);
            }

            return round((float) $v, 4);
        };

        $common['communication'] = [
            'photocopy_variation_ids' => $photocopy,
            'printout_variation_ids' => $printout,
            'ink_cost_photocopy' => $num('ink_cost_photocopy'),
            'ink_cost_print_bw' => $num('ink_cost_print_bw'),
            'ink_cost_print_color' => $num('ink_cost_print_color'),
            'default_price_photocopy_single' => $num('default_price_photocopy_single'),
            'default_price_photocopy_double' => $num('default_price_photocopy_double'),
            'default_price_print_bw_single' => $num('default_price_print_bw_single'),
            'default_price_print_bw_double' => $num('default_price_print_bw_double'),
            'default_price_print_color_single' => $num('default_price_print_color_single'),
            'default_price_print_color_double' => $num('default_price_print_color_double'),
        ];

        $business->common_settings = $common;

        // Keep module flag in sync with checkbox on this page
        $modules = (array) ($business->enabled_modules ?? []);
        $enable = $request->has('enable_communication') && (
            $request->input('enable_communication') == 1
            || $request->input('enable_communication') === '1'
            || $request->input('enable_communication') === true
        );
        if ($enable) {
            if (! in_array('communication', $modules, true)) {
                $modules[] = 'communication';
            }
        } else {
            $modules = array_values(array_filter($modules, function ($m) {
                return $m !== 'communication';
            }));
        }
        $business->enabled_modules = $modules;
        $business->save();

        // Refresh session business as a whole object (nested put converts model → array)
        $request->session()->put('business', $business);

        return redirect()
            ->action([self::class, 'settings'])
            ->with('status', ['success' => 1, 'msg' => __('lang_v1.communication_settings_saved')]);
    }

    /**
     * JSON: paper options + costs for POS modal (location-aware stock).
     */
    public function posOptions(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $location_id = (int) $request->get('location_id', $request->session()->get('user.current_location_id'));
        $business = Business::findOrFail($business_id);
        $common = is_array($business->common_settings) ? $business->common_settings : [];
        $settings = ! empty($common['communication']) && is_array($common['communication'])
            ? $common['communication']
            : [];

        $modules = (array) ($business->enabled_modules ?? []);
        if (! in_array('communication', $modules, true)) {
            return response()->json(['success' => 0, 'msg' => __('lang_v1.communication_module_disabled')], 403);
        }

        $buildPapers = function (array $ids) use ($business_id, $location_id) {
            if (empty($ids)) {
                return [];
            }
            $variations = Variation::with('product:id,name,sku,enable_stock')
                ->whereIn('id', $ids)
                ->whereHas('product', function ($q) use ($business_id) {
                    $q->where('business_id', $business_id);
                })
                ->get();

            $out = [];
            foreach ($variations as $v) {
                $stock = 0;
                if ($location_id) {
                    $stock = (float) VariationLocationDetails::where('variation_id', $v->id)
                        ->where('location_id', $location_id)
                        ->value('qty_available');
                }
                $productName = $v->product->name ?? '';
                $label = $productName;
                if (! empty($v->name) && $v->name !== 'DUMMY') {
                    $label .= ' — '.$v->name;
                }
                $out[] = [
                    'variation_id' => (int) $v->id,
                    'product_id' => (int) $v->product_id,
                    'label' => $label,
                    'sku' => $v->sub_sku,
                    'cost' => (float) ($v->default_purchase_price ?? 0),
                    'stock' => $stock,
                ];
            }

            return $out;
        };

        return response()->json([
            'success' => 1,
            'photocopy_papers' => $buildPapers((array) ($settings['photocopy_variation_ids'] ?? [])),
            'printout_papers' => $buildPapers((array) ($settings['printout_variation_ids'] ?? [])),
            'ink' => [
                'photocopy' => (float) ($settings['ink_cost_photocopy'] ?? 0),
                'print_bw' => (float) ($settings['ink_cost_print_bw'] ?? 0),
                'print_color' => (float) ($settings['ink_cost_print_color'] ?? 0),
            ],
            'defaults' => [
                'photocopy_single' => (float) ($settings['default_price_photocopy_single'] ?? 0),
                'photocopy_double' => (float) ($settings['default_price_photocopy_double'] ?? 0),
                'print_bw_single' => (float) ($settings['default_price_print_bw_single'] ?? 0),
                'print_bw_double' => (float) ($settings['default_price_print_bw_double'] ?? 0),
                'print_color_single' => (float) ($settings['default_price_print_color_single'] ?? 0),
                'print_color_double' => (float) ($settings['default_price_print_color_double'] ?? 0),
            ],
        ]);
    }
}
