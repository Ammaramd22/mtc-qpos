<?php

namespace App\Utils;

/**
 * Hardware Shop — same pattern as TyreShopUtil.
 * Gate: business.enabled_modules contains "hardware_shop".
 */
class HardwareShopUtil extends Util
{
    public function moduleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('hardware_shop', $business_id)
            && ShopModeUtil::allowsModule('hardware_shop', $business_id);
    }
}
