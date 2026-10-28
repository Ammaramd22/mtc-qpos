<?php

namespace App\Utils;

use App\SellingPriceGroup;
use App\VariationGroupPrice;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class WholesaleUtil
{
    public const GROUP_NAME = 'Wholesale';

    /**
     * Whether the wholesale module is enabled for the business.
     */
    public static function isEnabled($business_id = null): bool
    {
        if (empty($business_id)) {
            $enabled = session('business.enabled_modules', []);
            if (is_array($enabled)) {
                return in_array('wholesale', $enabled, true);
            }
        }

        $util = new Util();

        return $util->isModuleEnabled('wholesale', $business_id);
    }

    /**
     * Ensure a Wholesale selling-price group (+ permission) exists for the business.
     */
    public static function ensurePriceGroup(int $business_id): SellingPriceGroup
    {
        $group = SellingPriceGroup::withTrashed()
            ->where('business_id', $business_id)
            ->where('name', self::GROUP_NAME)
            ->first();

        if ($group) {
            if (method_exists($group, 'trashed') && $group->trashed()) {
                $group->restore();
            }
            if ((int) $group->is_active !== 1) {
                $group->is_active = 1;
                $group->save();
            }
        } else {
            $group = SellingPriceGroup::create([
                'name' => self::GROUP_NAME,
                'description' => 'Auto-created for Wholesale module',
                'business_id' => $business_id,
                'is_active' => 1,
            ]);
        }

        $perm_name = 'selling_price_group.'.$group->id;
        $permission = Permission::where('name', $perm_name)->first();
        if (empty($permission)) {
            $permission = Permission::create(['name' => $perm_name]);
        }

        // Cashier (and any role that can use default selling price) needs access on POS.
        $roles = Role::where('business_id', $business_id)->get();
        foreach ($roles as $role) {
            $is_cashier = strpos($role->name, 'Cashier#') === 0;
            $can_default = false;
            try {
                $can_default = $role->hasPermissionTo('access_default_selling_price');
            } catch (\Throwable $e) {
                $can_default = false;
            }
            if ($is_cashier || $can_default) {
                try {
                    if (! $role->hasPermissionTo($permission)) {
                        $role->givePermissionTo($permission);
                    }
                } catch (\Throwable $e) {
                    // ignore duplicate / cache issues
                }
            }
        }

        return $group;
    }

    /**
     * Wholesale price group id, or null if missing.
     */
    public static function getPriceGroupId(int $business_id, bool $create = false): ?int
    {
        if ($create || self::isEnabled($business_id)) {
            return (int) self::ensurePriceGroup($business_id)->id;
        }

        $group = SellingPriceGroup::where('business_id', $business_id)
            ->where('name', self::GROUP_NAME)
            ->active()
            ->first();

        return $group ? (int) $group->id : null;
    }

    /**
     * Save / update wholesale price for a variation (fixed price, same as MRP field).
     */
    public static function saveWholesalePrice(int $variation_id, int $business_id, $price): void
    {
        if (! self::isEnabled($business_id)) {
            return;
        }

        $group_id = self::getPriceGroupId($business_id, true);
        if (empty($group_id)) {
            return;
        }

        $util = new Util();
        $price_uf = $util->num_uf($price);

        // Empty / null clears wholesale price so retail is used as fallback in POS.
        if ($price === null || $price === '') {
            VariationGroupPrice::where('variation_id', $variation_id)
                ->where('price_group_id', $group_id)
                ->delete();

            return;
        }

        VariationGroupPrice::updateOrCreate(
            [
                'variation_id' => $variation_id,
                'price_group_id' => $group_id,
            ],
            [
                'price_inc_tax' => $price_uf,
                'price_type' => 'fixed',
            ]
        );
    }

    /**
     * Current wholesale price for a variation (null if none).
     */
    public static function getWholesalePrice(int $variation_id, int $business_id)
    {
        $group_id = self::getPriceGroupId($business_id, false);
        if (empty($group_id)) {
            return null;
        }

        $row = VariationGroupPrice::where('variation_id', $variation_id)
            ->where('price_group_id', $group_id)
            ->first();

        return $row ? $row->price_inc_tax : null;
    }
}
