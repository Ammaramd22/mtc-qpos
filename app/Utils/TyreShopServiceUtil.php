<?php

namespace App\Utils;

use App\Product;
use App\Unit;
use App\Variation;

/**
 * Non-stock tyre-shop service items for POS quick billing.
 * Architecture requires product_id/variation_id on sell lines; these
 * products have enable_stock=0 so inventory is never deducted.
 */
class TyreShopServiceUtil
{
    public const SKU_PREFIX = 'QPOS-SVC-';

    public const SERVICES = [
        'air_fill' => [
            'sku' => 'QPOS-SVC-AIR',
            'name' => 'Air Fill',
        ],
        'nitrogen_fill' => [
            'sku' => 'QPOS-SVC-NITRO',
            'name' => 'Nitrogen Fill',
        ],
        'tyre_fix' => [
            'sku' => 'QPOS-SVC-FIX',
            'name' => 'Tyre Fix',
        ],
        'addons' => [
            'sku' => 'QPOS-SVC-ADDON',
            'name' => 'Addons',
        ],
    ];

    /**
     * Ensure all tyre-shop service products exist and return POS payload.
     *
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  int  $location_id
     * @return array
     */
    public function getServicesForPos(int $business_id, int $user_id, int $location_id): array
    {
        $out = [];
        foreach (self::SERVICES as $key => $meta) {
            $product = $this->getOrCreateServiceProduct(
                $business_id,
                $user_id,
                $location_id,
                $meta['sku'],
                $meta['name']
            );
            $variation = $product->variations()->first();
            $out[$key] = [
                'key' => $key,
                'name' => $meta['name'],
                'sku' => $meta['sku'],
                'product_id' => $product->id,
                'variation_id' => $variation ? (int) $variation->id : null,
                'needs_custom_name' => $key === 'addons',
            ];
        }

        return $out;
    }

    /**
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  int  $location_id
     * @param  string  $sku
     * @param  string  $name
     * @return Product
     */
    public function getOrCreateServiceProduct(int $business_id, int $user_id, int $location_id, string $sku, string $name): Product
    {
        $product = Product::where('business_id', $business_id)->where('sku', $sku)->first();
        if ($product) {
            // Keep service definition consistent
            $dirty = false;
            if ((int) $product->enable_stock !== 0) {
                $product->enable_stock = 0;
                $dirty = true;
            }
            if ($product->name !== $name) {
                $product->name = $name;
                $dirty = true;
            }
            if ($dirty) {
                $product->save();
            }
            $product->product_locations()->syncWithoutDetaching([$location_id]);

            return $product->fresh(['variations']);
        }

        $unit = Unit::where('business_id', $business_id)->whereNull('base_unit_id')->first();
        if (empty($unit)) {
            $unit = Unit::create([
                'business_id' => $business_id,
                'actual_name' => 'Pieces',
                'short_name' => 'Pc',
                'allow_decimal' => 0,
                'created_by' => $user_id,
            ]);
        }

        $productUtil = new ProductUtil();

        $product = Product::create([
            'name' => $name,
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => $unit->id,
            'tax_type' => 'inclusive',
            'enable_stock' => 0,
            'sku' => $sku,
            'barcode_type' => 'C128',
            'product_description' => 'Tyre shop quick service (non-stock)',
            'enable_sr_no' => 0,
            'not_for_selling' => 0,
            'is_inactive' => 0,
            'created_by' => $user_id,
        ]);

        $productUtil->createSingleProductVariation($product->id, $product->sku, 0, 0, 0, 0, 0);
        $product->product_locations()->sync([$location_id]);

        return $product->fresh(['variations']);
    }

    public static function isServiceSku(?string $sku): bool
    {
        return ! empty($sku) && str_starts_with($sku, self::SKU_PREFIX);
    }
}
