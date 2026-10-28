<?php

namespace App\Utils;

use App\Media;
use App\PosTradeIn;
use App\Product;
use App\ProductRack;
use App\PurchaseLine;
use App\Transaction;
use App\TransactionSellLine;
use App\VariationLocationDetails;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Safe product deletion for QPOS.
 * Permanently deletes products with no historical transaction dependency;
 * refuses deletion when sales/purchases/adjustments would be corrupted.
 */
class ProductDeleteUtil
{
    /**
     * @return array{can_delete: bool, msg: string}
     */
    public function canDelete(int $business_id, Product $product, $moduleUtil = null): array
    {
        $id = (int) $product->id;

        // Real purchases (not opening stock)
        $purchaseCount = PurchaseLine::join('transactions as T', 'purchase_lines.transaction_id', '=', 'T.id')
            ->where('T.business_id', $business_id)
            ->where('purchase_lines.product_id', $id)
            ->whereIn('T.type', ['purchase', 'purchase_transfer', 'purchase_return'])
            ->count();
        if ($purchaseCount > 0) {
            return ['can_delete' => false, 'msg' => __('lang_v1.purchase_already_exist')];
        }

        // Opening stock that was already sold
        $openingSold = PurchaseLine::join('transactions as T', 'purchase_lines.transaction_id', '=', 'T.id')
            ->where('T.business_id', $business_id)
            ->where('T.type', 'opening_stock')
            ->where('purchase_lines.product_id', $id)
            ->where('purchase_lines.quantity_sold', '>', 0)
            ->count();
        if ($openingSold > 0) {
            return ['can_delete' => false, 'msg' => __('lang_v1.opening_stock_sold')];
        }

        // Stock adjusted from purchase/opening lines
        $adjusted = PurchaseLine::join('transactions as T', 'purchase_lines.transaction_id', '=', 'T.id')
            ->where('T.business_id', $business_id)
            ->where('purchase_lines.product_id', $id)
            ->where('purchase_lines.quantity_adjusted', '>', 0)
            ->count();
        if ($adjusted > 0) {
            return ['can_delete' => false, 'msg' => __('lang_v1.stock_adjusted')];
        }

        // Any sell history (stocked or non-stock) — do not cascade-destroy invoice lines
        $sellCount = TransactionSellLine::join('transactions as T', 'transaction_sell_lines.transaction_id', '=', 'T.id')
            ->where('T.business_id', $business_id)
            ->where('transaction_sell_lines.product_id', $id)
            ->count();
        if ($sellCount > 0) {
            return [
                'can_delete' => false,
                'msg' => 'Unable to delete product because it has existing sales records.',
            ];
        }

        // Stock adjustments referencing the product
        if (Schema::hasTable('stock_adjustment_lines')) {
            $adjLines = DB::table('stock_adjustment_lines')
                ->join('transactions as T', 'stock_adjustment_lines.transaction_id', '=', 'T.id')
                ->where('T.business_id', $business_id)
                ->where('stock_adjustment_lines.product_id', $id)
                ->count();
            if ($adjLines > 0) {
                return ['can_delete' => false, 'msg' => __('lang_v1.stock_adjusted')];
            }
        }

        // Trade-in / exchange records
        if (Schema::hasTable('pos_trade_ins')) {
            $ti = PosTradeIn::where('business_id', $business_id)
                ->where(function ($q) use ($id) {
                    $q->where('old_product_id', $id)->orWhere('new_product_id', $id);
                })
                ->count();
            if ($ti > 0) {
                return [
                    'can_delete' => false,
                    'msg' => 'Unable to delete product because it has existing exchange / trade-in records.',
                ];
            }
        }

        // Manufacturing ingredient
        if ($moduleUtil && $moduleUtil->isModuleInstalled('Manufacturing')) {
            $variation_ids = $product->variations->pluck('id');
            if ($variation_ids->count() > 0) {
                $exists = \Modules\Manufacturing\Entities\MfgRecipeIngredient::whereIn('variation_id', $variation_ids)->exists();
                if ($exists) {
                    return ['can_delete' => false, 'msg' => __('manufacturing::lang.added_as_ingredient')];
                }
            }
        }

        // Mobile shop IMEIs
        if (Schema::hasTable('mobile_shop_imeis')) {
            $imei = DB::table('mobile_shop_imeis')->where('product_id', $id)->count();
            if ($imei > 0) {
                return [
                    'can_delete' => false,
                    'msg' => 'Unable to delete product because it has related IMEI / serial records.',
                ];
            }
        }

        return ['can_delete' => true, 'msg' => ''];
    }

    /**
     * Permanently delete a product and safe related rows.
     * Caller must wrap in a DB transaction.
     */
    public function deleteProduct(int $business_id, Product $product): void
    {
        $id = (int) $product->id;
        $variationIds = $product->variations()->pluck('id')->all();

        // Remove unsold opening-stock lines + empty opening_stock transactions
        $this->removeUnusedOpeningStock($business_id, $id);

        // Variation location details (FK to variations is RESTRICT — must clear first)
        VariationLocationDetails::where('product_id', $id)->delete();
        if (! empty($variationIds)) {
            VariationLocationDetails::whereIn('variation_id', $variationIds)->delete();
        }

        // Stock price rows (cascade on variation, but product_id has no FK — clean explicitly)
        if (Schema::hasTable('variation_stock_prices')) {
            DB::table('variation_stock_prices')->where('product_id', $id)->delete();
            if (! empty($variationIds)) {
                DB::table('variation_stock_prices')->whereIn('variation_id', $variationIds)->delete();
            }
        }

        // Group prices cascade with variations; still safe to leave to cascade

        // Product locations pivot
        $product->product_locations()->detach();

        // Racks
        ProductRack::where('product_id', $id)->delete();

        // Modifier set links
        if (Schema::hasTable('res_product_modifier_sets')) {
            DB::table('res_product_modifier_sets')
                ->where('product_id', $id)
                ->orWhere('modifier_set_id', $id)
                ->delete();
        }

        // Media (morph)
        if (method_exists($product, 'media')) {
            foreach ($product->media as $m) {
                try {
                    Media::deleteMedia($business_id, $m->id);
                } catch (\Exception $e) {
                    // fall through — delete DB row if file missing
                    optional($m)->delete();
                }
            }
        }

        // Variation media
        foreach ($product->variations as $variation) {
            if (method_exists($variation, 'media')) {
                foreach ($variation->media as $m) {
                    try {
                        Media::deleteMedia($business_id, $m->id);
                    } catch (\Exception $e) {
                        optional($m)->delete();
                    }
                }
            }
        }

        // Clear opening_stock_product_id on transactions
        Transaction::where('business_id', $business_id)
            ->where('opening_stock_product_id', $id)
            ->update(['opening_stock_product_id' => null]);

        // Hard delete product (cascades product_variations + variations via FK)
        $product->delete();
    }

    protected function removeUnusedOpeningStock(int $business_id, int $product_id): void
    {
        $lines = PurchaseLine::join('transactions as T', 'purchase_lines.transaction_id', '=', 'T.id')
            ->where('T.business_id', $business_id)
            ->where('T.type', 'opening_stock')
            ->where('purchase_lines.product_id', $product_id)
            ->where(function ($q) {
                $q->whereNull('purchase_lines.quantity_sold')
                    ->orWhere('purchase_lines.quantity_sold', '<=', 0);
            })
            ->select('purchase_lines.id', 'purchase_lines.transaction_id')
            ->get();

        if ($lines->isEmpty()) {
            return;
        }

        $lineIds = $lines->pluck('id')->all();
        $txnIds = $lines->pluck('transaction_id')->unique()->all();

        if (Schema::hasTable('transaction_sell_lines_purchase_lines')) {
            DB::table('transaction_sell_lines_purchase_lines')
                ->whereIn('purchase_line_id', $lineIds)
                ->delete();
        }

        PurchaseLine::whereIn('id', $lineIds)->delete();

        // Remove opening_stock transactions that no longer have lines
        foreach ($txnIds as $txnId) {
            $remaining = PurchaseLine::where('transaction_id', $txnId)->count();
            if ($remaining === 0) {
                Transaction::where('id', $txnId)
                    ->where('business_id', $business_id)
                    ->where('type', 'opening_stock')
                    ->delete();
            }
        }
    }
}
