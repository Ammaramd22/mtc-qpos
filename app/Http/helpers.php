<?php

/**
 * boots pos.
 */
function pos_boot($ul, $pt, $lc, $em, $un, $type = 1, $pid = null)
{
	//bugs
}

if (! function_exists('humanFilesize')) {
    function humanFilesize($size, $precision = 2)
    {
        $units = ['B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $step = 1024;
        $i = 0;

        while (($size / $step) > 0.9) {
            $size = $size / $step;
            $i++;
        }

        return round($size, $precision).$units[$i];
    }
}

/**
 * Checks if the uploaded document is an image
 */
if (! function_exists('isFileImage')) {
    function isFileImage($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $array = ['png', 'PNG', 'jpg', 'JPG', 'jpeg', 'JPEG', 'gif', 'GIF'];
        $output = in_array($ext, $array) ? true : false;

        return $output;
    }
}

function isAppInstalled()
{
    $envPath = base_path('.env');

    return file_exists($envPath);
}

/**
 * Checks if pusher has credential or not
 *
 * and return boolean
 */
function isPusherEnabled()
{
    $is_pusher_enabled = false;

    if (! empty(config('broadcasting.connections.pusher.key')) && ! empty(config('broadcasting.connections.pusher.secret')) && ! empty(config('broadcasting.connections.pusher.app_id')) && ! empty(config('broadcasting.connections.pusher.options.cluster')) && (config('broadcasting.connections.pusher.driver') == 'pusher')) {
        $is_pusher_enabled = true;
    }

    return $is_pusher_enabled;
}

/**
 * Checks if user agent is mobile or not
 *
 * @return bool
 */
if (! function_exists('isMobile')) {
    function isMobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];

        if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * Whether the business has the tax system enabled.
 */
if (! function_exists('is_tax_enabled')) {
    function is_tax_enabled($business = null)
    {
        if ($business === null) {
            $business = session('business');
        }

        if (empty($business)) {
            return false;
        }

        if (is_array($business)) {
            $common_settings = $business['common_settings'] ?? [];
        } else {
            $common_settings = $business->common_settings ?? [];
        }

        if (is_string($common_settings)) {
            $common_settings = json_decode($common_settings, true) ?? [];
        }

        if (! is_array($common_settings)) {
            return false;
        }

        if (array_key_exists('enable_tax', $common_settings)) {
            return ! empty($common_settings['enable_tax']);
        }

        // Tax off by default until explicitly enabled in Business Settings → Tax
        return false;
    }
}

/**
 * Product price UI mode: full (exc/inc tax), simple (price only), hidden.
 */
if (! function_exists('product_price_tax_mode')) {
    function product_price_tax_mode()
    {
        if (! is_tax_enabled()) {
            return 'simple';
        }

        if (empty(session('business.enable_price_tax'))) {
            return 'hidden';
        }

        return 'full';
    }
}

if (! function_exists('str_ordinal')) {
    /**
     * Append an ordinal indicator to a numeric value.
     *
     * @param  string|int  $value
     * @param  bool  $superscript
     * @return string
     */
    function str_ordinal($value, $superscript = false)
    {
        $number = abs($value);

        $indicators = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];

        $suffix = $superscript ? '<sup>'.$indicators[$number % 10].'</sup>' : $indicators[$number % 10];
        if ($number % 100 >= 11 && $number % 100 <= 13) {
            $suffix = $superscript ? '<sup>th</sup>' : 'th';
        }

        return number_format($number).$suffix;
    }
}

if (! function_exists('can_access_settings_option')) {
    /**
     * Whether the current user may see a Settings menu/tab option.
     * Fail-open so settings stay usable if Soft Owner util is missing.
     */
    function can_access_settings_option(string $key): bool
    {
        try {
            if (! class_exists(\App\Utils\SoftwareOwnerUtil::class)) {
                return true;
            }

            return \App\Utils\SoftwareOwnerUtil::canAccess(auth()->user(), $key);
        } catch (\Throwable $e) {
            return true;
        }
    }
}

if (! function_exists('feature_enabled')) {
    /**
     * Whether an app feature is enabled for the current user.
     * Software owner always true. Fail-open if util missing.
     */
    function feature_enabled(string $key): bool
    {
        try {
            if (! auth()->check()) {
                return false;
            }
            if (! class_exists(\App\Utils\SoftwareOwnerUtil::class)) {
                return true;
            }

            return \App\Utils\SoftwareOwnerUtil::featureEnabled(auth()->user(), $key);
        } catch (\Throwable $e) {
            return true;
        }
    }
}

if (! function_exists('report_enabled')) {
    /**
     * Whether a Reports menu item is enabled for the current user (Soft Owner access).
     */
    function report_enabled(string $key): bool
    {
        try {
            if (! auth()->check()) {
                return false;
            }
            if (! class_exists(\App\Utils\SoftwareOwnerUtil::class)) {
                return true;
            }

            return \App\Utils\SoftwareOwnerUtil::reportEnabled(auth()->user(), $key);
        } catch (\Throwable $e) {
            return true;
        }
    }
}

if (! function_exists('nutmeg_enabled')) {
    /**
     * Soft Owner nutmeg feature + business module enabled.
     */
    function nutmeg_enabled(): bool
    {
        try {
            $modules = session('business.enabled_modules', []);
            if (! is_array($modules) || ! in_array('nutmeg', $modules, true)) {
                return false;
            }

            return ! function_exists('feature_enabled') || feature_enabled('nutmeg');
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('tyre_shop_enabled')) {
    /**
     * Business-level Tyre Shop module (same pattern as mobile_shop in enabled_modules).
     */
    function tyre_shop_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\TyreShopUtil())->moduleEnabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('shop_mode')) {
    /**
     * Current Shop Mode key for this business (normal_retail, tyre_shop, …).
     */
    function shop_mode(?int $business_id = null): string
    {
        try {
            return \App\Utils\ShopModeUtil::current($business_id);
        } catch (\Throwable $e) {
            return \App\Utils\ShopModeUtil::DEFAULT;
        }
    }
}

if (! function_exists('shop_mode_is')) {
    function shop_mode_is(string $mode, ?int $business_id = null): bool
    {
        try {
            return \App\Utils\ShopModeUtil::is($mode, $business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('shop_mode_allows')) {
    /**
     * Whether an industry module is allowed under the current Shop Mode.
     */
    function shop_mode_allows(string $module, ?int $business_id = null): bool
    {
        try {
            return \App\Utils\ShopModeUtil::allowsModule($module, $business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('mobile_shop_enabled')) {
    function mobile_shop_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\MobileShopUtil())->moduleEnabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('vehicle_modification_enabled')) {
    function vehicle_modification_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\VehicleModificationUtil())->moduleEnabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('customer_vehicles_enabled')) {
    /**
     * Whether customer ↔ vehicle fields are available (Vehicle Modification or Tyre Shop).
     */
    function customer_vehicles_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\CustomerVehicleUtil())->enabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('vehicle_spare_parts_enabled')) {
    function vehicle_spare_parts_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\VehicleSparePartsUtil())->moduleEnabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('hardware_shop_enabled')) {
    function hardware_shop_enabled(?int $business_id = null): bool
    {
        try {
            return (new \App\Utils\HardwareShopUtil())->moduleEnabled($business_id);
        } catch (\Throwable $e) {
            return false;
        }
    }
}

if (! function_exists('shop_mode_list_columns')) {
    /**
     * Safe industry list columns for product screens (never throws).
     *
     * @return array<int, string>
     */
    function shop_mode_list_columns(?int $business_id = null): array
    {
        try {
            if (! class_exists(\App\Utils\ShopModeUtil::class)) {
                return [];
            }

            return \App\Utils\ShopModeUtil::listColumns($business_id);
        } catch (\Throwable $e) {
            return [];
        }
    }
}

if (! function_exists('capital_account_enabled')) {
    /**
     * Whether Capital Accounts (partners / investment) is enabled.
     */
    function capital_account_enabled(): bool
    {
        try {
            return \App\Utils\CapitalAccountUtil::isEnabled();
        } catch (\Throwable $e) {
            return false;
        }
    }
}
