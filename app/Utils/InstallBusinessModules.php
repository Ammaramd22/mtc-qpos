<?php

namespace App\Utils;

use App\Business;
use App\System;

/**
 * Install-time specialty business modules (Nutmeg, Mobile Shop, …).
 */
class InstallBusinessModules
{
    public const PREFS_PATH = 'install_preferences.json';

    public const SYSTEM_KEY = 'install_default_modules';

    /**
     * Specialty packs shown during install.
     * Each pack maps to Soft Owner feature keys + business enabled_modules keys.
     *
     * @return array<string, array{label: string, help: string, features: string[], modules: string[], product_flags?: array<string, int>}>
     */
    public static function packs(): array
    {
        return [
            'general' => [
                'label' => 'General retail / wholesale',
                'help' => 'Standard POS — purchases, sales, stock. Always included.',
                'features' => [],
                'settings_tabs' => [],
                'modules' => [],
                'always' => true,
            ],
            'nutmeg' => [
                'label' => 'Nutmeg / Spices trading',
                'help' => 'Product expenses into AVCO, drying & wastage, packing bags on POS.',
                'features' => ['nutmeg'],
                'settings_tabs' => [],
                'modules' => ['nutmeg', 'pos_purchase'],
                'product_flags' => [
                    'enable_brand' => 0,
                    'enable_product_warranty' => 0,
                ],
            ],
            'mobile_shop' => [
                'label' => 'Mobile shop',
                'help' => 'IMEI inventory, repairs, warranty lookup.',
                'features' => [],
                'settings_tabs' => ['mobile_shop'],
                'modules' => ['mobile_shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_product_warranty' => 1,
                ],
            ],
            'tyre_shop' => [
                'label' => 'Tyre shop',
                'help' => 'Tyre size / notes, POS quick services, exchange / trade-in.',
                'features' => [],
                'settings_tabs' => ['tyre_shop'],
                'modules' => ['tyre_shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                    'enable_product_warranty' => 1,
                ],
            ],
            'vehicle_modification' => [
                'label' => 'Vehicle modification shop',
                'help' => 'Workshop jobs, customer vehicles, parts and installation services.',
                'features' => [],
                'settings_tabs' => ['vehicle_modification'],
                'modules' => ['vehicle_modification'],
                'product_flags' => [
                    'enable_brand' => 1,
                ],
            ],
            'vehicle_spare_parts' => [
                'label' => 'Vehicle spare parts shop',
                'help' => 'Part / OEM numbers and vehicle compatibility on products and POS search.',
                'features' => [],
                'settings_tabs' => ['vehicle_spare_parts'],
                'modules' => ['vehicle_spare_parts'],
                'product_flags' => [
                    'enable_brand' => 1,
                ],
            ],
            'hardware_shop' => [
                'label' => 'Hardware shop',
                'help' => 'Size, material, grade and colour on products for hardware retailers.',
                'features' => [],
                'settings_tabs' => ['hardware_shop'],
                'modules' => ['hardware_shop'],
                'product_flags' => [
                    'enable_brand' => 1,
                ],
            ],
            'reload' => [
                'label' => 'Reload / bill payments',
                'help' => 'MyReload / bill payment services (often with Mobile shop).',
                'features' => [],
                'settings_tabs' => ['reload'],
                'modules' => ['reload'],
            ],
            'communication' => [
                'label' => 'Communication (photocopy / printout)',
                'help' => 'Photocopy & printout services in POS with paper stock.',
                'features' => ['communication'],
                'settings_tabs' => [],
                'modules' => ['communication'],
            ],
            'wholesale' => [
                'label' => 'Wholesale',
                'help' => 'Wholesale pricing and wholesale workflows.',
                'features' => [],
                'settings_tabs' => [],
                'modules' => ['wholesale'],
            ],
            'restaurant' => [
                'label' => 'Restaurant',
                'help' => 'Tables, kitchen, modifiers, bookings, service staff.',
                'features' => ['tables', 'kitchen', 'modifiers', 'booking', 'service_staff'],
                'settings_tabs' => [],
                'modules' => ['tables', 'kitchen', 'modifiers', 'booking', 'service_staff'],
            ],
            'subscription' => [
                'label' => 'Subscriptions',
                'help' => 'Recurring subscription sales.',
                'features' => [],
                'settings_tabs' => [],
                'modules' => ['subscription'],
            ],
            'types_of_service' => [
                'label' => 'Types of service',
                'help' => 'Delivery / packing / service charges on sales.',
                'features' => [],
                'settings_tabs' => [],
                'modules' => ['types_of_service'],
            ],
            'stock_price_management' => [
                'label' => 'Stock price management',
                'help' => 'Multiple selling prices per product with POS picker.',
                'features' => [],
                'settings_tabs' => [],
                'modules' => ['stock_price_management'],
            ],
        ];
    }

    public static function baseModules(): array
    {
        return ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'];
    }

    /**
     * Persist pack selection during install (before DB exists).
     *
     * @param  array<int, string>  $pack_ids
     */
    public static function savePreferences(array $pack_ids): void
    {
        $allowed = array_keys(self::packs());
        $pack_ids = array_values(array_intersect($pack_ids, $allowed));
        if (! in_array('general', $pack_ids, true)) {
            array_unshift($pack_ids, 'general');
        }

        $dir = storage_path('app');
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $payload = [
            'packs' => $pack_ids,
            'modules' => self::modulesForPacks($pack_ids),
            'features' => self::featuresForPacks($pack_ids),
            'settings_tabs' => self::settingsTabsForPacks($pack_ids),
            'product_flags' => self::productFlagsForPacks($pack_ids),
            'saved_at' => date('c'),
        ];

        file_put_contents($dir.'/'.self::PREFS_PATH, json_encode($payload, JSON_PRETTY_PRINT));
    }

    public static function loadPreferences(): ?array
    {
        $path = storage_path('app/'.self::PREFS_PATH);
        if (! is_file($path)) {
            return null;
        }
        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : null;
    }

    /**
     * After migrate/seed: Soft Owner features + System default modules for new businesses.
     */
    public static function applyAfterInstall(): void
    {
        $prefs = self::loadPreferences();
        if (empty($prefs)) {
            return;
        }

        $selected_features = $prefs['features'] ?? [];
        $selected_modules = $prefs['modules'] ?? self::baseModules();
        $selected_tabs = $prefs['settings_tabs'] ?? [];

        // Soft Owner: specialty extras on only if selected; keep core features on.
        $specialty_features = [];
        $specialty_tabs = [];
        foreach (self::packs() as $pack) {
            foreach ($pack['features'] ?? [] as $f) {
                $specialty_features[$f] = true;
            }
            foreach ($pack['settings_tabs'] ?? [] as $t) {
                $specialty_tabs[$t] = true;
            }
        }

        $features = SoftwareOwnerUtil::defaultFeatures();
        foreach (array_keys($specialty_features) as $fkey) {
            $features[$fkey] = in_array($fkey, $selected_features, true) ? 1 : 0;
        }
        SoftwareOwnerUtil::saveFeatures($features);

        $visibility = SoftwareOwnerUtil::defaultVisibility();
        foreach (array_keys($specialty_tabs) as $tkey) {
            $visibility[$tkey] = in_array($tkey, $selected_tabs, true) ? 1 : 0;
        }
        SoftwareOwnerUtil::saveVisibility($visibility);

        System::addProperty(self::SYSTEM_KEY, json_encode([
            'modules' => array_values(array_unique($selected_modules)),
            'packs' => $prefs['packs'] ?? [],
            'product_flags' => $prefs['product_flags'] ?? [],
        ]));

        // If a demo/default business already exists, enable modules on the first one.
        $business = Business::orderBy('id')->first();
        if ($business) {
            self::applyToBusiness($business);
        }
    }

    /**
     * Default enabled_modules for a newly registered business.
     *
     * @return array<int, string>
     */
    public static function defaultEnabledModules(): array
    {
        $prefs = self::loadPreferences();
        if (! empty($prefs['modules']) && is_array($prefs['modules'])) {
            return array_values(array_unique(array_merge(self::baseModules(), $prefs['modules'])));
        }

        try {
            $raw = System::getProperty(self::SYSTEM_KEY);
            $decoded = is_array($raw) ? $raw : (json_decode($raw ?: '[]', true) ?: []);
            if (! empty($decoded['modules']) && is_array($decoded['modules'])) {
                return array_values(array_unique(array_merge(self::baseModules(), $decoded['modules'])));
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return self::baseModules();
    }

    /**
     * Optional product flags (brand/warranty) from install packs.
     *
     * @return array<string, int>
     */
    public static function defaultProductFlags(): array
    {
        $prefs = self::loadPreferences();
        if (! empty($prefs['product_flags']) && is_array($prefs['product_flags'])) {
            return $prefs['product_flags'];
        }

        try {
            $raw = System::getProperty(self::SYSTEM_KEY);
            $decoded = is_array($raw) ? $raw : (json_decode($raw ?: '[]', true) ?: []);
            if (! empty($decoded['product_flags']) && is_array($decoded['product_flags'])) {
                return $decoded['product_flags'];
            }
        } catch (\Throwable $e) {
            // ignore
        }

        return [];
    }

    public static function applyToBusiness(Business $business): void
    {
        $modules = self::defaultEnabledModules();
        $existing = $business->enabled_modules;
        if (is_string($existing)) {
            $existing = json_decode($existing, true) ?: [];
        }
        if (! is_array($existing)) {
            $existing = [];
        }
        $business->enabled_modules = array_values(array_unique(array_merge($existing, $modules)));

        $flags = self::defaultProductFlags();
        if (array_key_exists('enable_brand', $flags)) {
            $business->enable_brand = (int) $flags['enable_brand'];
        }

        $common = $business->common_settings;
        if (is_string($common)) {
            $common = json_decode($common, true) ?: [];
        }
        if (! is_array($common)) {
            $common = [];
        }
        if (array_key_exists('enable_product_warranty', $flags)) {
            $common['enable_product_warranty'] = (int) $flags['enable_product_warranty'];
            $business->common_settings = $common;
        }

        $business->save();
    }

    /**
     * @param  array<int, string>  $pack_ids
     * @return array<int, string>
     */
    public static function modulesForPacks(array $pack_ids): array
    {
        $modules = self::baseModules();
        $packs = self::packs();
        foreach ($pack_ids as $id) {
            if (empty($packs[$id])) {
                continue;
            }
            foreach ($packs[$id]['modules'] as $m) {
                $modules[] = $m;
            }
        }

        return array_values(array_unique($modules));
    }

    /**
     * @param  array<int, string>  $pack_ids
     * @return array<int, string>
     */
    public static function featuresForPacks(array $pack_ids): array
    {
        $features = [];
        $packs = self::packs();
        foreach ($pack_ids as $id) {
            if (empty($packs[$id])) {
                continue;
            }
            foreach ($packs[$id]['features'] ?? [] as $f) {
                $features[] = $f;
            }
        }

        return array_values(array_unique($features));
    }

    /**
     * @param  array<int, string>  $pack_ids
     * @return array<int, string>
     */
    public static function settingsTabsForPacks(array $pack_ids): array
    {
        $tabs = [];
        $packs = self::packs();
        foreach ($pack_ids as $id) {
            if (empty($packs[$id])) {
                continue;
            }
            foreach ($packs[$id]['settings_tabs'] ?? [] as $t) {
                $tabs[] = $t;
            }
        }

        return array_values(array_unique($tabs));
    }

    /**
     * Later packs override earlier flags (e.g. mobile_shop after nutmeg turns brand back on).
     *
     * @param  array<int, string>  $pack_ids
     * @return array<string, int>
     */
    public static function productFlagsForPacks(array $pack_ids): array
    {
        $flags = [];
        $packs = self::packs();
        foreach ($pack_ids as $id) {
            if (empty($packs[$id]['product_flags'])) {
                continue;
            }
            foreach ($packs[$id]['product_flags'] as $k => $v) {
                $flags[$k] = (int) $v;
            }
        }

        return $flags;
    }
}
