<?php

namespace App\Http\Controllers;

use App\Business;
use App\Product;
use Illuminate\Http\Request;

class NutmegSettingsController extends Controller
{
    protected function assertNutmegFeature(): void
    {
        if (function_exists('feature_enabled') && ! feature_enabled('nutmeg')) {
            abort(403, __('lang_v1.feature_disabled_by_owner'));
        }
    }

    public function settings()
    {
        $this->assertNutmegFeature();

        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('product.view')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        $common = is_array($business->common_settings) ? $business->common_settings : [];
        $settings = ! empty($common['nutmeg']) && is_array($common['nutmeg']) ? $common['nutmeg'] : [];
        $enabled_modules = (array) ($business->enabled_modules ?? []);
        $module_enabled = in_array('nutmeg', $enabled_modules, true);

        $products = Product::where('business_id', $business_id)
            ->where('enable_stock', 1)
            ->where('type', '!=', 'modifier')
            ->with(['variations' => function ($q) {
                $q->select('id', 'product_id', 'name', 'sub_sku', 'default_purchase_price', 'sell_price_inc_tax', 'dpp_inc_tax');
            }])
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        $productOptions = [];
        foreach ($products as $product) {
            foreach ($product->variations as $v) {
                $label = $product->name;
                if (! empty($v->name) && $v->name !== 'DUMMY') {
                    $label .= ' — '.$v->name;
                }
                if (! empty($v->sub_sku)) {
                    $label .= ' ('.$v->sub_sku.')';
                }
                $productOptions[$v->id] = [
                    'label' => $label,
                    'product_id' => $product->id,
                    'cost' => (float) ($v->dpp_inc_tax ?? $v->default_purchase_price ?? 0),
                    'sell' => (float) ($v->sell_price_inc_tax ?? 0),
                ];
            }
        }

        return view('nutmeg.settings', compact('settings', 'module_enabled', 'productOptions', 'business'));
    }

    public function updateSettings(Request $request)
    {
        $this->assertNutmegFeature();

        if (! auth()->user()->can('business_settings.access') && ! auth()->user()->can('product.update')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');
            $business = Business::findOrFail($business_id);

            $modules = (array) ($business->enabled_modules ?? []);
            $enable = $request->boolean('enable_nutmeg_module');
            if ($enable && ! in_array('nutmeg', $modules, true)) {
                $modules[] = 'nutmeg';
            } elseif (! $enable) {
                $modules = array_values(array_filter($modules, fn ($m) => $m !== 'nutmeg'));
            }
            $business->enabled_modules = $modules;

            $bags = [];
            foreach ((array) $request->input('bags', []) as $row) {
                $vid = (int) ($row['variation_id'] ?? 0);
                $fill = (float) ($row['fill_weight'] ?? 0);
                if ($vid <= 0 || $fill <= 0) {
                    continue;
                }
                $bags[] = [
                    'variation_id' => $vid,
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'label' => trim((string) ($row['label'] ?? '')),
                    'fill_weight' => $fill,
                ];
            }

            $common = is_array($business->common_settings) ? $business->common_settings : [];
            $common['nutmeg'] = [
                'bags' => $bags,
                'ask_bag_on_sell' => $request->boolean('ask_bag_on_sell', true),
            ];
            $business->common_settings = $common;
            $business->save();

            $request->session()->put('business', $business);

            $output = ['success' => 1, 'msg' => __('lang_v1.nutmeg_settings_saved')];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().' Line:'.$e->getLine().' Message:'.$e->getMessage());
            $output = ['success' => 0, 'msg' => __('messages.something_went_wrong')];
        }

        return redirect()
            ->action([self::class, 'settings'])
            ->with('status', $output);
    }

    /**
     * POS JSON: configured bags.
     */
    public function posBags()
    {
        if (! function_exists('nutmeg_enabled') || ! nutmeg_enabled()) {
            return response()->json(['enabled' => false, 'bags' => []]);
        }

        $business_id = request()->session()->get('user.business_id');
        $business = Business::find($business_id);
        $common = is_array($business->common_settings ?? null) ? $business->common_settings : [];
        $settings = $common['nutmeg'] ?? [];
        $bags = [];

        foreach ((array) ($settings['bags'] ?? []) as $bag) {
            $vid = (int) ($bag['variation_id'] ?? 0);
            if ($vid <= 0) {
                continue;
            }
            $product = Product::with('variations')->find($bag['product_id'] ?? null);
            $variation = null;
            if ($product) {
                $variation = $product->variations->firstWhere('id', $vid);
            }
            if (! $variation) {
                $variation = \App\Variation::with('product')->find($vid);
                $product = $variation->product ?? null;
            }
            if (! $variation || ! $product) {
                continue;
            }
            $label = $bag['label'] ?: ($product->name.(! empty($variation->name) && $variation->name !== 'DUMMY' ? ' — '.$variation->name : ''));
            $bags[] = [
                'variation_id' => $vid,
                'product_id' => (int) $product->id,
                'label' => $label,
                'fill_weight' => (float) ($bag['fill_weight'] ?? 0),
                'sell_price' => (float) ($variation->sell_price_inc_tax ?? 0),
                'sub_sku' => $variation->sub_sku,
            ];
        }

        return response()->json([
            'enabled' => true,
            'ask_bag_on_sell' => ! isset($settings['ask_bag_on_sell']) || ! empty($settings['ask_bag_on_sell']),
            'bags' => $bags,
        ]);
    }
}
