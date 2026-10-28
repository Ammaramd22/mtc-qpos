<?php

namespace App\Http\Middleware;

use App\Utils\SoftwareOwnerUtil;
use Closure;
use Illuminate\Http\Request;

/**
 * Blocks shop users from routes whose feature was disabled by Software Owner.
 * Software owner always passes.
 */
class CheckFeatureAccess
{
    public function handle(Request $request, Closure $next)
    {
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
            return $next($request);
        }

        if ($request->is('business/shop-mode') || $request->routeIs('business.postShopMode')) {
            abort(403, 'Unauthorized action.');
        }

        $feature = SoftwareOwnerUtil::featureForPath($request->segment(1));
        if ($feature && ! SoftwareOwnerUtil::featureEnabled($user, $feature)) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => 0,
                    'msg' => __('lang_v1.feature_disabled_by_owner'),
                ], 403);
            }

            abort(403, __('lang_v1.feature_disabled_by_owner'));
        }

        return $next($request);
    }
}
