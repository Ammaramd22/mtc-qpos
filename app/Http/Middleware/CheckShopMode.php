<?php

namespace App\Http\Middleware;

use App\Utils\ShopModeUtil;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks industry routes that do not belong to the current Shop Mode.
 * Direct URL access is rejected — not CSS hiding.
 *
 * Before Shop Mode is saved, falls back to enabled_modules so existing
 * tyre/mobile businesses keep working.
 */
class CheckShopMode
{
    public function handle(Request $request, Closure $next)
    {
        $required = ShopModeUtil::modeForRoutePrefix($request->segment(1));
        if (! $required) {
            return $next($request);
        }

        $allowed = false;
        if (ShopModeUtil::isExplicit()) {
            $allowed = ShopModeUtil::current() === $required;
        } else {
            $def = ShopModeUtil::definition($required);
            foreach ($def['industry_modules'] as $mod) {
                if (ShopModeUtil::allowsModule($mod)) {
                    $allowed = true;
                    break;
                }
            }
        }

        if (! $allowed) {
            $msg = __('lang_v1.shop_mode_feature_disabled');
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => 0,
                    'msg' => $msg,
                ], 403);
            }

            abort(403, $msg);
        }

        return $next($request);
    }
}
