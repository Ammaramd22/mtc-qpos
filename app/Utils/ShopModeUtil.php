<?php

namespace App\Utils;

use App\Business;
use Illuminate\Support\Facades\Schema;

/**
 * High-level Shop Mode / business-type configuration.
 *
 * Source of truth: business.shop_mode (Software Owner only).
 * Industry keys in business.enabled_modules are derived from the selected mode
 * so existing helpers (tyre_shop_enabled(), MobileShopUtil::moduleEnabled(), …)
 * keep working without a second module system.
 */
class ShopModeUtil
{
    public const DEFAULT = 'normal_retail';

    /** @var array<string, string> request-level cache */
    protected static array $currentCache = [];

    /**
     * Registry of shop modes. Add a new mode here — do not scatter if/elseif.
     *
     * @return array<string, array{
     *     label: string,
     *     help: string,
     *     industry_modules: string[],
     *     product_fields: array<string, array<string, mixed>>,
     *     pos_search_fields: string[],
     *     list_columns: string[],
     *     settings_tabs: string[],
     *     route_prefixes: string[],
     *     product_flags: array<string, int>
     * }>
     */
    public static function definitions(): array
    {
        return [
            self::DEFAULT => [
                'label' => __('lang_v1.shop_mode_normal_retail'),
                'help' => __('lang_v1.shop_mode_normal_retail_help'),
                'industry_modules' => [],
                'product_fields' => [],
                'pos_search_fields' => [],
                'list_columns' => [],
                'settings_tabs' => [],
                'route_prefixes' => [],
                'product_flags' => [],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder',
                'dashboard_links' => [],
            ],
            'tyre_shop' => [
                'label' => __('lang_v1.shop_mode_tyre_shop'),
                'help' => __('lang_v1.shop_mode_tyre_shop_help'),
                'industry_modules' => ['tyre_shop'],
                'product_fields' => [
                    'tyre_size' => [
                        'label' => 'product.tyre_size',
                        'placeholder' => 'product.tyre_size_placeholder',
                        'maxlength' => 50,
                    ],
                    'tyre_note' => [
                        'label' => 'product.tyre_note',
                        'placeholder' => 'product.tyre_note_placeholder',
                        'maxlength' => 100,
                    ],
                ],
                'pos_search_fields' => ['tyre_size', 'tyre_note'],
                'list_columns' => ['tyre_size'],
                'settings_tabs' => ['tyre_shop'],
                'route_prefixes' => ['tyre-shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_product_warranty' => 1,
                ],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder_tyre',
                'dashboard_links' => [
                    ['url' => '/tyre-shop', 'label' => 'lang_v1.tyre_shop', 'icon' => 'fa fa-circle'],
                ],
            ],
            'mobile_shop' => [
                'label' => __('lang_v1.shop_mode_mobile_shop'),
                'help' => __('lang_v1.shop_mode_mobile_shop_help'),
                'industry_modules' => ['mobile_shop'],
                'product_fields' => [],
                'pos_search_fields' => [],
                'list_columns' => [],
                'settings_tabs' => ['mobile_shop'],
                'route_prefixes' => ['mobile-shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_product_warranty' => 1,
                ],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder_mobile',
                'dashboard_links' => [
                    ['url' => '/mobile-shop', 'label' => 'lang_v1.mobile_shop', 'icon' => 'fa fa-mobile-alt'],
                    ['url' => '/mobile-shop/imei', 'label' => 'lang_v1.mobile_imei_inventory', 'icon' => ''],
                    ['url' => '/mobile-shop/repairs', 'label' => 'lang_v1.mobile_repairs', 'icon' => ''],
                ],
            ],
            'vehicle_modification' => [
                'label' => __('lang_v1.shop_mode_vehicle_modification'),
                'help' => __('lang_v1.shop_mode_vehicle_modification_help'),
                'industry_modules' => ['vehicle_modification'],
                'product_fields' => [
                    'compatible_make' => [
                        'label' => 'product.compatible_make',
                        'placeholder' => 'product.compatible_make_placeholder',
                        'maxlength' => 80,
                    ],
                    'compatible_model' => [
                        'label' => 'product.compatible_model',
                        'placeholder' => 'product.compatible_model_placeholder',
                        'maxlength' => 80,
                    ],
                ],
                'pos_search_fields' => ['compatible_make', 'compatible_model'],
                'list_columns' => [],
                'settings_tabs' => ['vehicle_modification'],
                'route_prefixes' => ['vehicle-modification'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_category' => 1,
                ],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder_modification',
                'dashboard_links' => [
                    ['url' => '/vehicle-modification', 'label' => 'lang_v1.vehicle_modification', 'icon' => 'fa fa-car'],
                    ['url' => '/vehicle-modification/jobs', 'label' => 'lang_v1.workshop_jobs', 'icon' => ''],
                    ['url' => '/vehicle-modification/vehicles', 'label' => 'lang_v1.customer_vehicles', 'icon' => ''],
                ],
            ],
            'vehicle_spare_parts' => [
                'label' => __('lang_v1.shop_mode_vehicle_spare_parts'),
                'help' => __('lang_v1.shop_mode_vehicle_spare_parts_help'),
                'industry_modules' => ['vehicle_spare_parts'],
                'product_fields' => [
                    'part_number' => [
                        'label' => 'product.part_number',
                        'placeholder' => 'product.part_number_placeholder',
                        'maxlength' => 80,
                    ],
                    'oem_number' => [
                        'label' => 'product.oem_number',
                        'placeholder' => 'product.oem_number_placeholder',
                        'maxlength' => 80,
                    ],
                    'compatible_make' => [
                        'label' => 'product.compatible_make',
                        'placeholder' => 'product.compatible_make_placeholder',
                        'maxlength' => 80,
                    ],
                    'compatible_model' => [
                        'label' => 'product.compatible_model',
                        'placeholder' => 'product.compatible_model_placeholder',
                        'maxlength' => 80,
                    ],
                    'year_from' => [
                        'label' => 'product.year_from',
                        'placeholder' => 'product.year_from_placeholder',
                        'maxlength' => 4,
                        'type' => 'number',
                    ],
                    'year_to' => [
                        'label' => 'product.year_to',
                        'placeholder' => 'product.year_to_placeholder',
                        'maxlength' => 4,
                        'type' => 'number',
                    ],
                    'engine_type' => [
                        'label' => 'product.engine_type',
                        'placeholder' => 'product.engine_type_placeholder',
                        'maxlength' => 80,
                    ],
                ],
                'pos_search_fields' => [
                    'part_number', 'oem_number', 'compatible_make',
                    'compatible_model', 'engine_type',
                ],
                'list_columns' => ['part_number', 'oem_number'],
                'settings_tabs' => ['vehicle_spare_parts'],
                'route_prefixes' => ['vehicle-spare-parts'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_category' => 1,
                ],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder_spare_parts',
                'dashboard_links' => [
                    ['url' => '/vehicle-spare-parts', 'label' => 'lang_v1.vehicle_spare_parts', 'icon' => 'fa fa-cogs'],
                ],
            ],
            'hardware_shop' => [
                'label' => __('lang_v1.shop_mode_hardware_shop'),
                'help' => __('lang_v1.shop_mode_hardware_shop_help'),
                'industry_modules' => ['hardware_shop'],
                'product_fields' => [
                    'hw_size' => [
                        'label' => 'product.hw_size',
                        'placeholder' => 'product.hw_size_placeholder',
                        'maxlength' => 50,
                    ],
                    'hw_length' => [
                        'label' => 'product.hw_length',
                        'placeholder' => 'product.hw_length_placeholder',
                        'maxlength' => 50,
                    ],
                    'hw_width' => [
                        'label' => 'product.hw_width',
                        'placeholder' => 'product.hw_width_placeholder',
                        'maxlength' => 50,
                    ],
                    'hw_thickness' => [
                        'label' => 'product.hw_thickness',
                        'placeholder' => 'product.hw_thickness_placeholder',
                        'maxlength' => 50,
                    ],
                    'hw_material' => [
                        'label' => 'product.hw_material',
                        'placeholder' => 'product.hw_material_placeholder',
                        'maxlength' => 80,
                    ],
                    'hw_grade' => [
                        'label' => 'product.hw_grade',
                        'placeholder' => 'product.hw_grade_placeholder',
                        'maxlength' => 50,
                    ],
                    'hw_colour' => [
                        'label' => 'product.hw_colour',
                        'placeholder' => 'product.hw_colour_placeholder',
                        'maxlength' => 50,
                    ],
                ],
                'pos_search_fields' => [
                    'hw_size', 'hw_length', 'hw_width', 'hw_thickness',
                    'hw_material', 'hw_grade', 'hw_colour',
                ],
                'list_columns' => ['hw_size', 'hw_material'],
                'settings_tabs' => ['hardware_shop'],
                'route_prefixes' => ['hardware-shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_category' => 1,
                ],
                'pos_search_placeholder' => 'lang_v1.search_product_placeholder_hardware',
                'dashboard_links' => [
                    ['url' => '/hardware-shop', 'label' => 'lang_v1.hardware_shop', 'icon' => 'fa fa-hammer'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::definitions());
    }

    public static function isValid(string $mode): bool
    {
        return array_key_exists($mode, self::definitions());
    }

    /**
     * All industry module keys controlled exclusively by Shop Mode.
     *
     * @return array<int, string>
     */
    public static function industryModuleKeys(): array
    {
        $keys = [];
        foreach (self::definitions() as $def) {
            foreach ($def['industry_modules'] as $mod) {
                $keys[$mod] = true;
            }
        }

        return array_keys($keys);
    }

    /**
     * Map first URL segment → required shop mode (or null if unmapped).
     */
    public static function modeForRoutePrefix(?string $segment): ?string
    {
        if ($segment === null || $segment === '') {
            return null;
        }

        foreach (self::definitions() as $mode => $def) {
            if (in_array($segment, $def['route_prefixes'], true)) {
                return $mode;
            }
        }

        return null;
    }

    /**
     * Current shop mode for a business. Infers from enabled_modules when unset
     * so existing tyre/mobile businesses keep working until Soft Owner saves.
     */
    public static function current(?int $business_id = null): string
    {
        $business_id = $business_id ?: (int) (session('user.business_id') ?? 0);
        $cacheKey = (string) $business_id;
        if (array_key_exists($cacheKey, self::$currentCache)) {
            return self::$currentCache[$cacheKey];
        }

        $stored = null;
        $modules = [];

        try {
            if ($business_id > 0) {
                $sessionBiz = session('business');
                $sessionId = is_object($sessionBiz)
                    ? (int) ($sessionBiz->id ?? 0)
                    : (int) ($sessionBiz['id'] ?? 0);

                if ($sessionId === $business_id && $sessionBiz) {
                    if (is_object($sessionBiz)) {
                        $stored = $sessionBiz->shop_mode ?? null;
                        $modules = is_array($sessionBiz->enabled_modules ?? null)
                            ? $sessionBiz->enabled_modules
                            : [];
                    } else {
                        $stored = $sessionBiz['shop_mode'] ?? null;
                        $modules = is_array($sessionBiz['enabled_modules'] ?? null)
                            ? $sessionBiz['enabled_modules']
                            : [];
                    }
                } else {
                    $business = Business::find($business_id);
                    if ($business) {
                        $stored = $business->shop_mode ?? null;
                        $modules = is_array($business->enabled_modules)
                            ? $business->enabled_modules
                            : [];
                    }
                }
            } else {
                $sessionBiz = session('business');
                if (is_object($sessionBiz)) {
                    $stored = $sessionBiz->shop_mode ?? null;
                    $modules = is_array($sessionBiz->enabled_modules ?? null)
                        ? $sessionBiz->enabled_modules
                        : [];
                } elseif (is_array($sessionBiz)) {
                    $stored = $sessionBiz['shop_mode'] ?? null;
                    $modules = is_array($sessionBiz['enabled_modules'] ?? null)
                        ? $sessionBiz['enabled_modules']
                        : [];
                }
            }
        } catch (\Throwable $e) {
            $stored = null;
        }

        $mode = self::DEFAULT;
        if (is_string($stored) && self::isValid($stored)) {
            $mode = $stored;
        } else {
            $mode = self::inferFromModules(is_array($modules) ? $modules : []);
        }

        self::$currentCache[$cacheKey] = $mode;

        return $mode;
    }

    public static function forgetCache(?int $business_id = null): void
    {
        if ($business_id === null) {
            self::$currentCache = [];

            return;
        }
        unset(self::$currentCache[(string) $business_id]);
    }

    /**
     * Whether shop_mode has been explicitly saved (vs inferred).
     */
    public static function isExplicit(?int $business_id = null): bool
    {
        try {
            $business_id = $business_id ?: (int) (session('user.business_id') ?? 0);
            if ($business_id <= 0 || ! Schema::hasColumn('business', 'shop_mode')) {
                return false;
            }
            $business = Business::find($business_id);

            return $business && is_string($business->shop_mode) && self::isValid($business->shop_mode);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @param  array<int, string>  $enabled_modules
     */
    public static function inferFromModules(array $enabled_modules): string
    {
        foreach (self::definitions() as $mode => $def) {
            if ($mode === self::DEFAULT) {
                continue;
            }
            foreach ($def['industry_modules'] as $mod) {
                if (in_array($mod, $enabled_modules, true)) {
                    return $mode;
                }
            }
        }

        return self::DEFAULT;
    }

    public static function is(string $mode, ?int $business_id = null): bool
    {
        return self::current($business_id) === $mode;
    }

    public static function definition(?string $mode = null, ?int $business_id = null): array
    {
        $mode = $mode ?: self::current($business_id);
        $defs = self::definitions();

        return $defs[$mode] ?? $defs[self::DEFAULT];
    }

    /**
     * Whether an industry module is allowed for the current (or given) shop mode.
     * Before Shop Mode is saved, falls back to enabled_modules so dual-pack
     * legacy installs keep working.
     */
    public static function allowsModule(string $module, ?int $business_id = null): bool
    {
        if (! in_array($module, self::industryModuleKeys(), true)) {
            return true;
        }

        if (! self::isExplicit($business_id)) {
            try {
                return (new Util())->isModuleEnabled($module, $business_id);
            } catch (\Throwable $e) {
                return in_array($module, session('business.enabled_modules', []) ?? [], true);
            }
        }

        $def = self::definition(null, $business_id);

        return in_array($module, $def['industry_modules'], true);
    }

    public static function hasProductField(string $field, ?int $business_id = null): bool
    {
        $def = self::definition(null, $business_id);

        return array_key_exists($field, $def['product_fields'] ?? []);
    }

    /**
     * Product form fields for the current mode, excluding tyre fields which
     * already have dedicated partials + auto-name logic.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function extraProductFields(?int $business_id = null): array
    {
        try {
            $fields = self::definition(null, $business_id)['product_fields'] ?? [];
            unset($fields['tyre_size'], $fields['tyre_note']);

            $out = [];
            foreach ($fields as $column => $meta) {
                if (self::productColumnExists($column)) {
                    $out[$column] = $meta;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    public static function extraProductFieldNames(?int $business_id = null): array
    {
        return array_keys(self::extraProductFields($business_id));
    }

    /**
     * Extra POS search columns for the current mode (schema-aware).
     *
     * @return array<int, string>
     */
    public static function posSearchFields(?int $business_id = null): array
    {
        $fields = self::definition(null, $business_id)['pos_search_fields'] ?? [];
        $out = [];
        foreach ($fields as $column) {
            if (self::productColumnExists($column)) {
                $out[] = $column;
            }
        }

        return $out;
    }

    /**
     * Extra product-list columns for the current mode.
     *
     * @return array<int, string>
     */
    public static function listColumns(?int $business_id = null): array
    {
        try {
            $cols = self::definition(null, $business_id)['list_columns'] ?? [];
            $out = [];
            foreach ($cols as $column) {
                if ($column === 'tyre_size') {
                    continue;
                }
                if (self::productColumnExists($column)) {
                    $out[] = $column;
                }
            }

            return $out;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public static function productColumnExists(string $column): bool
    {
        static $cache = [];
        if (array_key_exists($column, $cache)) {
            return $cache[$column];
        }
        try {
            $cache[$column] = Schema::hasColumn('products', $column);
        } catch (\Throwable $e) {
            $cache[$column] = false;
        }

        return $cache[$column];
    }

    /**
     * Sync industry modules onto an enabled_modules array for a given mode.
     * Core / orthogonal packs (purchases, nutmeg, restaurant, reload, …) are kept.
     *
     * @param  array<int, string>  $enabled_modules
     * @return array<int, string>
     */
    public static function syncIndustryModules(array $enabled_modules, string $mode): array
    {
        if (! self::isValid($mode)) {
            $mode = self::DEFAULT;
        }

        $industry = self::industryModuleKeys();
        $kept = [];
        foreach ($enabled_modules as $mod) {
            if (! in_array($mod, $industry, true)) {
                $kept[] = $mod;
            }
        }

        $def = self::definitions()[$mode];
        foreach ($def['industry_modules'] as $mod) {
            $kept[] = $mod;
        }

        return array_values(array_unique($kept));
    }

    /**
     * When Shop Mode is saved, industry keys follow the mode.
     * Before that, keep any industry modules already on the business
     * (legacy dual-pack installs) even if the Modules form hid those checkboxes.
     *
     * @param  array<int, string>  $enabled_modules
     * @return array<int, string>
     */
    public static function overlayIndustryModules(array $enabled_modules, ?int $business_id = null): array
    {
        if (self::isExplicit($business_id)) {
            return self::syncIndustryModules($enabled_modules, self::current($business_id));
        }

        $business_id = $business_id ?: (int) (session('user.business_id') ?? 0);
        $existing = [];
        try {
            if ($business_id > 0) {
                $business = Business::find($business_id);
                $existing = is_array($business->enabled_modules ?? null) ? $business->enabled_modules : [];
            }
        } catch (\Throwable $e) {
            $existing = [];
        }

        foreach (self::industryModuleKeys() as $mod) {
            if (in_array($mod, $existing, true)) {
                $enabled_modules[] = $mod;
            }
        }

        return array_values(array_unique($enabled_modules));
    }

    /**
     * Dashboard shortcut links for the current mode (empty for normal retail).
     *
     * @return array<int, array{url: string, label: string, icon: string}>
     */
    public static function dashboardLinks(?int $business_id = null): array
    {
        $def = self::definition(null, $business_id);
        $module = $def['industry_modules'][0] ?? null;
        if ($module && ! self::allowsModule($module, $business_id)) {
            return [];
        }

        return $def['dashboard_links'] ?? [];
    }

    public static function posSearchPlaceholder(?int $business_id = null): string
    {
        $key = self::definition(null, $business_id)['pos_search_placeholder'] ?? 'lang_v1.search_product_placeholder';

        return __($key);
    }

    /**
     * Apply a shop mode to a business: set shop_mode, sync industry modules,
     * apply product flags. Never deletes products/sales/customers.
     */
    public static function applyToBusiness(Business $business, string $mode, bool $save = true): Business
    {
        if (! self::isValid($mode)) {
            $mode = self::DEFAULT;
        }

        $existing = $business->enabled_modules;
        if (is_string($existing)) {
            $existing = json_decode($existing, true) ?: [];
        }
        if (! is_array($existing)) {
            $existing = [];
        }

        if (! Schema::hasColumn('business', 'shop_mode')) {
            throw new \RuntimeException(
                'Database column business.shop_mode is missing. Run database/cpanel_shop_mode.sql in phpMyAdmin (or open /cpanel_run_migrations.php?key=...), then try again.'
            );
        }

        $business->shop_mode = $mode;
        $business->enabled_modules = self::syncIndustryModules($existing, $mode);

        $def = self::definitions()[$mode];
        $flags = $def['product_flags'] ?? [];
        if (array_key_exists('enable_brand', $flags) && Schema::hasColumn('business', 'enable_brand')) {
            $business->enable_brand = (int) $flags['enable_brand'];
        }
        if (array_key_exists('enable_category', $flags) && Schema::hasColumn('business', 'enable_category')) {
            $business->enable_category = (int) $flags['enable_category'];
        }

        if (array_key_exists('enable_product_warranty', $flags)) {
            $common = $business->common_settings;
            if (is_string($common)) {
                $common = json_decode($common, true) ?: [];
            }
            if (! is_array($common)) {
                $common = [];
            }
            $common['enable_product_warranty'] = (int) $flags['enable_product_warranty'];
            $business->common_settings = $common;
        }

        if ($save) {
            $business->save();
        }

        self::forgetCache((int) $business->id);

        return $business;
    }

    /**
     * Sanitize extra industry product fields from a request payload.
     *
     * @param  array<string, mixed>  $product_details
     * @return array<string, mixed>
     */
    public static function sanitizeProductFields(array $product_details, $request): array
    {
        $allowed = self::extraProductFieldNames();
        $allIndustry = [];
        foreach (self::definitions() as $def) {
            foreach (array_keys($def['product_fields'] ?? []) as $col) {
                if ($col === 'tyre_size' || $col === 'tyre_note') {
                    continue;
                }
                $allIndustry[$col] = true;
            }
        }

        foreach (array_keys($allIndustry) as $col) {
            if (! in_array($col, $allowed, true) || ! self::productColumnExists($col)) {
                unset($product_details[$col]);
                continue;
            }
            $raw = $request->input($col, $product_details[$col] ?? null);
            if ($raw === null || $raw === '') {
                $product_details[$col] = null;
                continue;
            }
            $meta = self::extraProductFields()[$col] ?? [];
            $max = (int) ($meta['maxlength'] ?? 80);
            if (($meta['type'] ?? 'text') === 'number') {
                $num = (int) preg_replace('/[^0-9]/', '', (string) $raw);
                $product_details[$col] = $num > 0 ? $num : null;
                continue;
            }
            $product_details[$col] = mb_substr(trim((string) $raw), 0, $max);
        }

        return $product_details;
    }
}
