<?php

namespace App\Utils;

/**
 * Tyre Shop business module — same pattern as MobileShopUtil.
 * Gate: business.enabled_modules contains "tyre_shop".
 */
class TyreShopUtil extends Util
{
    public function moduleEnabled(?int $business_id = null): bool
    {
        return $this->isModuleEnabled('tyre_shop', $business_id)
            && ShopModeUtil::allowsModule('tyre_shop', $business_id);
    }
}
