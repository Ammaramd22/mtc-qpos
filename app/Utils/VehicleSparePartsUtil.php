<?php

namespace App\Utils;

/**
 * Vehicle Spare Parts Shop — same pattern as TyreShopUtil.
 * Gate: business.enabled_modules contains "vehicle_spare_parts".
 */
class VehicleSparePartsUtil extends Util
{
    public function moduleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('vehicle_spare_parts', $business_id)
            && ShopModeUtil::allowsModule('vehicle_spare_parts', $business_id);
    }
}
