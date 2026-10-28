<?php

namespace App\Utils;

use App\System;
use App\User;

class SoftwareOwnerUtil
{
    /**
     * Settings tabs / settings menu pages (shop admin Settings screen).
     */
    public static function optionDefinitions(): array
    {
        return [
            'business_settings' => ['group' => 'menu', 'label' => 'Business Settings'],
            'business_locations' => ['group' => 'menu', 'label' => 'Business Locations'],
            'invoice_settings' => ['group' => 'menu', 'label' => 'Invoice Settings'],
            'barcode_settings' => ['group' => 'menu', 'label' => 'Barcode Settings'],
            'receipt_printers' => ['group' => 'menu', 'label' => 'Receipt Printers'],
            'business' => ['group' => 'tab', 'label' => 'Business'],
            'tax' => ['group' => 'tab', 'label' => 'Tax'],
            'product' => ['group' => 'tab', 'label' => 'Product'],
            'contact' => ['group' => 'tab', 'label' => 'Contact'],
            'sale' => ['group' => 'tab', 'label' => 'Sale'],
            'pos' => ['group' => 'tab', 'label' => 'POS'],
            'display_screen' => ['group' => 'tab', 'label' => 'Display Screen'],
            'purchases' => ['group' => 'tab', 'label' => 'Purchases'],
            'payment' => ['group' => 'tab', 'label' => 'Payment'],
            'dashboard' => ['group' => 'tab', 'label' => 'Dashboard'],
            'system' => ['group' => 'tab', 'label' => 'System'],
            'prefixes' => ['group' => 'tab', 'label' => 'Prefixes'],
            'email_settings' => ['group' => 'tab', 'label' => 'Email Settings'],
            'sms_settings' => ['group' => 'tab', 'label' => 'SMS Settings'],
            'reward_point_settings' => ['group' => 'tab', 'label' => 'Reward Point Settings'],
            'modules' => ['group' => 'tab', 'label' => 'Modules'],
            'custom_labels' => ['group' => 'tab', 'label' => 'Custom Labels'],
            'api' => ['group' => 'tab', 'label' => 'API'],
            'bartender' => ['group' => 'tab', 'label' => 'Barcode & Sticker'],
            'weighing_scale_label' => ['group' => 'tab', 'label' => 'Weighing Scale Label'],
            'weighing_scale_barcode_scan' => ['group' => 'tab', 'label' => 'Weighing Scale Barcode Scan'],
            'costcode' => ['group' => 'tab', 'label' => 'Cost Code'],
            'offline_billing' => ['group' => 'tab', 'label' => 'Offline Billing'],
            'pwa' => ['group' => 'tab', 'label' => 'PWA (Mobile App Mode)'],
            'ebill' => ['group' => 'tab', 'label' => 'eBill'],
            'mobile_shop' => ['group' => 'tab', 'label' => 'Mobile Shop'],
            'tyre_shop' => ['group' => 'tab', 'label' => 'Tyre Shop'],
            'vehicle_modification' => ['group' => 'tab', 'label' => 'Vehicle Modification'],
            'vehicle_spare_parts' => ['group' => 'tab', 'label' => 'Vehicle Spare Parts'],
            'hardware_shop' => ['group' => 'tab', 'label' => 'Hardware Shop'],
            'reload' => ['group' => 'tab', 'label' => 'Reload Module'],
        ];
    }

    /**
     * App-wide features (sidebar + route gate). Soft-disabled for shop users when off.
     * Software owner always retains access.
     */
    public static function featureDefinitions(): array
    {
        return [
            'users' => ['group' => 'core', 'label' => 'Users & Roles'],
            'contacts' => ['group' => 'core', 'label' => 'Contacts (Customers / Suppliers)'],
            'products' => ['group' => 'inventory', 'label' => 'Products'],
            'product_labels' => ['group' => 'inventory', 'label' => 'Product Labels Print'],
            'variations' => ['group' => 'inventory', 'label' => 'Variations / Import Products'],
            'purchases' => ['group' => 'ops', 'label' => 'Purchases'],
            'sales' => ['group' => 'ops', 'label' => 'Sales / POS'],
            'stock_transfers' => ['group' => 'ops', 'label' => 'Stock Transfers'],
            'stock_adjustment' => ['group' => 'ops', 'label' => 'Stock Adjustment'],
            'expenses' => ['group' => 'ops', 'label' => 'Expenses'],
            'accounts' => ['group' => 'finance', 'label' => 'Accounts'],
            'reports' => ['group' => 'finance', 'label' => 'Reports menu'],
            'cheque' => ['group' => 'finance', 'label' => 'Cheque Management'],
            'backup' => ['group' => 'system', 'label' => 'Backup'],
            'manage_modules' => ['group' => 'system', 'label' => 'Manage Modules'],
            'notification_templates' => ['group' => 'system', 'label' => 'Notification Templates'],
            'business_settings' => ['group' => 'settings', 'label' => 'Business Settings'],
            'business_locations' => ['group' => 'settings', 'label' => 'Business Locations'],
            'invoice_settings' => ['group' => 'settings', 'label' => 'Invoice Settings'],
            'barcode_settings' => ['group' => 'settings', 'label' => 'Barcode Settings'],
            'receipt_printers' => ['group' => 'settings', 'label' => 'Receipt Printers'],
            'tax_rates' => ['group' => 'settings', 'label' => 'Tax Rates'],
            'booking' => ['group' => 'extra', 'label' => 'Bookings'],
            'kitchen' => ['group' => 'extra', 'label' => 'Kitchen'],
            'service_staff' => ['group' => 'extra', 'label' => 'Service Staff Orders'],
            'tables' => ['group' => 'extra', 'label' => 'Tables'],
            'modifiers' => ['group' => 'extra', 'label' => 'Modifiers'],
            'communication' => ['group' => 'extra', 'label' => 'Communication (Photocopy / Printout)'],
            'nutmeg' => ['group' => 'extra', 'label' => 'Nutmeg / Spices Trading'],
            'capital_account' => ['group' => 'finance', 'label' => 'Capital Accounts (Partners)'],
        ];
    }

    /**
     * Individual Reports menu items Soft Owner can show/hide for shop users.
     */
    public static function reportDefinitions(): array
    {
        return [
            'profit_loss' => 'Profit / Loss Report',
            'purchase_sell' => 'Purchase & Sale',
            'tax' => 'Tax Report',
            'contacts' => 'Supplier & Customer Report',
            'customer_groups' => 'Customer Groups Report',
            'loyalty' => 'Loyalty Report',
            'stock' => 'Stock Report',
            'stock_expiry' => 'Stock Expiry Report',
            'lot' => 'Lot Report',
            'stock_adjustment' => 'Stock Adjustment Report',
            'trending_products' => 'Trending Products',
            'items' => 'Items Report',
            'product_purchase' => 'Product Purchase Report',
            'product_sell' => 'Product Sell Report',
            'purchase_payment' => 'Purchase Payment Report',
            'sell_payment' => 'Sell Payment Report',
            'cheque' => 'Cheque Report',
            'expense' => 'Expense Report',
            'register' => 'Register Report',
            'sales_representative' => 'Sales Representative Report',
            'table' => 'Table Report',
            'gst_sales' => 'GST Sales Report',
            'gst_purchase' => 'GST Purchase Report',
            'service_staff' => 'Service Staff Report',
            'activity_log' => 'Activity Log',
            'nutmeg' => 'Nutmeg Report',
        ];
    }

    public static function defaultReports(): array
    {
        $out = [];
        foreach (array_keys(self::reportDefinitions()) as $key) {
            $out[$key] = 1;
        }

        return $out;
    }

    public static function getReports(): array
    {
        try {
            $raw = System::getProperty('app_report_visibility');
        } catch (\Throwable $e) {
            return self::defaultReports();
        }

        $decoded = [];
        if (! empty($raw)) {
            $decoded = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        }

        return array_merge(self::defaultReports(), $decoded);
    }

    public static function saveReports(array $input): void
    {
        $reports = self::defaultReports();
        foreach (array_keys(self::reportDefinitions()) as $key) {
            $reports[$key] = ! empty($input[$key]) ? 1 : 0;
        }
        System::addProperty('app_report_visibility', json_encode($reports));
    }

    /**
     * Soft Owner always true. Shop users need the report enabled.
     */
    public static function reportEnabled(?User $user, string $reportKey): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        $reports = self::getReports();
        if (array_key_exists($reportKey, $reports) && empty($reports[$reportKey])) {
            return false;
        }

        return true;
    }

    /**
     * Map first URL segment → feature key (route gate).
     */
    public static function routeFeatureMap(): array
    {
        return [
            'users' => 'users',
            'roles' => 'users',
            'sales-commission-agents' => 'users',
            'contacts' => 'contacts',
            'customer-group' => 'contacts',
            'products' => 'products',
            'labels' => 'product_labels',
            'variation-templates' => 'variations',
            'import-products' => 'variations',
            'import-opening-stock' => 'products',
            'selling-price-group' => 'products',
            'update-product-price' => 'products',
            'units' => 'products',
            'taxonomies' => 'products',
            'brands' => 'products',
            'warranties' => 'products',
            'purchases' => 'purchases',
            'purchase-order' => 'purchases',
            'purchase-requisition' => 'purchases',
            'purchase-return' => 'purchases',
            'purchase-pos' => 'purchases',
            'sells' => 'sales',
            'pos' => 'sales',
            'sell-return' => 'sales',
            'stock-transfers' => 'stock_transfers',
            'stock-adjustments' => 'stock_adjustment',
            'expenses' => 'expenses',
            'account' => 'accounts',
            'account-types' => 'accounts',
            'reports' => 'reports',
            'cheque' => 'cheque',
            'backup' => 'backup',
            'manage-modules' => 'manage_modules',
            'notification-templates' => 'notification_templates',
            'business' => 'business_settings',
            'business-location' => 'business_locations',
            'invoice-schemes' => 'invoice_settings',
            'invoice-layouts' => 'invoice_settings',
            'printers' => 'receipt_printers',
            'tax-rates' => 'tax_rates',
            'bookings' => 'booking',
            'communication' => 'communication',
            'product-expenses' => 'nutmeg',
            'nutmeg' => 'nutmeg',
            'capital-account' => 'capital_account',
            'mobile-shop' => 'sales',
            'tyre-shop' => 'sales',
            'vehicle-modification' => 'sales',
            'vehicle-spare-parts' => 'products',
            'hardware-shop' => 'products',
        ];
    }

    public static function defaultVisibility(): array
    {
        $out = [];
        foreach (array_keys(self::optionDefinitions()) as $key) {
            $out[$key] = 1;
        }

        return $out;
    }

    public static function defaultFeatures(): array
    {
        $out = [];
        foreach (array_keys(self::featureDefinitions()) as $key) {
            $out[$key] = 1;
        }

        return $out;
    }

    public static function getVisibility(): array
    {
        try {
            $raw = System::getProperty('settings_menu_visibility');
        } catch (\Throwable $e) {
            return self::defaultVisibility();
        }

        $decoded = [];
        if (! empty($raw)) {
            $decoded = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        }

        return array_merge(self::defaultVisibility(), $decoded);
    }

    public static function getFeatures(): array
    {
        try {
            $raw = System::getProperty('app_feature_visibility');
        } catch (\Throwable $e) {
            return self::defaultFeatures();
        }

        $decoded = [];
        if (! empty($raw)) {
            $decoded = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        }

        return array_merge(self::defaultFeatures(), $decoded);
    }

    public static function saveVisibility(array $input): void
    {
        $visibility = self::defaultVisibility();
        foreach (array_keys(self::optionDefinitions()) as $key) {
            $visibility[$key] = ! empty($input[$key]) ? 1 : 0;
        }
        System::addProperty('settings_menu_visibility', json_encode($visibility));

        // Keep shared keys (e.g. receipt_printers, invoice_settings) in sync with app features
        self::syncSharedKeysToFeatures($visibility);
    }

    public static function saveFeatures(array $input): void
    {
        $features = self::defaultFeatures();
        foreach (array_keys(self::featureDefinitions()) as $key) {
            $features[$key] = ! empty($input[$key]) ? 1 : 0;
        }
        System::addProperty('app_feature_visibility', json_encode($features));

        // Keep shared keys in sync with settings menu visibility
        self::syncSharedKeysToVisibility($features);
    }

    /**
     * Keys that exist in both Settings options and App features.
     */
    public static function sharedKeys(): array
    {
        return array_values(array_intersect(
            array_keys(self::optionDefinitions()),
            array_keys(self::featureDefinitions())
        ));
    }

    protected static function syncSharedKeysToFeatures(array $visibility): void
    {
        $features = self::getFeatures();
        $changed = false;
        foreach (self::sharedKeys() as $key) {
            $val = ! empty($visibility[$key]) ? 1 : 0;
            if (($features[$key] ?? 1) !== $val) {
                $features[$key] = $val;
                $changed = true;
            }
        }
        if ($changed) {
            System::addProperty('app_feature_visibility', json_encode($features));
        }
    }

    protected static function syncSharedKeysToVisibility(array $features): void
    {
        $visibility = self::getVisibility();
        $changed = false;
        foreach (self::sharedKeys() as $key) {
            $val = ! empty($features[$key]) ? 1 : 0;
            if (($visibility[$key] ?? 1) !== $val) {
                $visibility[$key] = $val;
                $changed = true;
            }
        }
        if ($changed) {
            System::addProperty('settings_menu_visibility', json_encode($visibility));
        }
    }

    /**
     * Software owner always allowed; others need the Settings option enabled.
     */
    public static function canAccess(?User $user, string $optionKey): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        $visibility = self::getVisibility();

        if (! array_key_exists($optionKey, $visibility)) {
            return true;
        }

        return ! empty($visibility[$optionKey]);
    }

    /**
     * Whether an app feature is enabled for this user.
     * Software owner always true. Unknown keys stay enabled (fail-open).
     * Also respects Settings-option toggles for shared keys (receipt_printers, etc.).
     */
    public static function featureEnabled(?User $user, string $featureKey): bool
    {
        if (! $user) {
            return false;
        }

        try {
            if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        $features = self::getFeatures();
        if (array_key_exists($featureKey, $features) && empty($features[$featureKey])) {
            return false;
        }

        // Shared with Settings menu options — either toggle can hide the feature
        $visibility = self::getVisibility();
        if (array_key_exists($featureKey, $visibility) && empty($visibility[$featureKey])) {
            return false;
        }

        return true;
    }

    /**
     * Resolve feature key for current request path (or null if unmapped).
     */
    public static function featureForPath(?string $segment): ?string
    {
        if ($segment === null || $segment === '') {
            return null;
        }

        $map = self::routeFeatureMap();

        return $map[$segment] ?? null;
    }

    /**
     * Login left-panel background (system-wide Soft Owner setting).
     * Returns absolute URL or null when using theme default.
     */
    public static function loginBackgroundUrl(): ?string
    {
        try {
            $file = System::getProperty('login_bg_image');
        } catch (\Throwable $e) {
            return null;
        }

        if (empty($file)) {
            return null;
        }

        $rel = 'uploads/login/' . ltrim((string) $file, '/');
        if (! is_file(public_path($rel))) {
            return null;
        }

        return asset($rel);
    }

    public static function loginBackgroundFilename(): ?string
    {
        try {
            $file = System::getProperty('login_bg_image');
        } catch (\Throwable $e) {
            return null;
        }

        return ! empty($file) ? (string) $file : null;
    }

    public static function setLoginBackground(?string $filename): void
    {
        if ($filename === null || $filename === '') {
            System::removeProperty('login_bg_image');

            return;
        }

        System::addProperty('login_bg_image', $filename);
    }

    /**
     * Soft Owner personal module toggles (this login only).
     * Does not change Business Settings → Modules for shop users.
     */
    public static function softOwnerModuleDefinitions(): array
    {
        return [
            'pos_sale' => 'POS',
            'pos_purchase' => 'Point of Purchase (POP)',
            'purchases' => 'Purchases',
            'add_sale' => 'Add Sale',
            'stock_transfers' => 'Stock Transfers',
            'stock_adjustment' => 'Stock Adjustment',
            'expenses' => 'Expenses',
            'account' => 'Accounts',
            'tables' => 'Tables',
            'modifiers' => 'Modifiers',
            'service_staff' => 'Service Staff',
            'booking' => 'Bookings',
            'kitchen' => 'Kitchen',
            'subscription' => 'Subscriptions',
            'types_of_service' => 'Types of Service',
            'reload' => 'Reload Module',
            'wholesale' => 'Wholesale',
            'communication' => 'Communication',
            'nutmeg' => 'Nutmeg / Spices Trading',
            'capital_account' => 'Capital Accounts',
            'mobile_shop' => 'Mobile Shop',
            'tyre_shop' => 'Tyre Shop',
            'vehicle_modification' => 'Vehicle Modification',
            'vehicle_spare_parts' => 'Vehicle Spare Parts',
            'hardware_shop' => 'Hardware Shop',
            'stock_price_management' => 'Stock Price Management',
            'ai_assistant' => 'AI Assistant',
        ];
    }

    public static function defaultSoftOwnerModulePrefs(): array
    {
        $out = [];
        foreach (array_keys(self::softOwnerModuleDefinitions()) as $key) {
            $out[$key] = 1;
        }

        return $out;
    }

    public static function getSoftOwnerModulePrefs(): array
    {
        try {
            $raw = System::getProperty('soft_owner_modules');
        } catch (\Throwable $e) {
            return self::defaultSoftOwnerModulePrefs();
        }

        $decoded = [];
        if (! empty($raw)) {
            $decoded = is_array($raw) ? $raw : (json_decode($raw, true) ?: []);
        }

        return array_merge(self::defaultSoftOwnerModulePrefs(), $decoded);
    }

    public static function saveSoftOwnerModulePrefs(array $input): void
    {
        $prefs = self::defaultSoftOwnerModulePrefs();
        foreach (array_keys(self::softOwnerModuleDefinitions()) as $key) {
            $prefs[$key] = ! empty($input[$key]) ? 1 : 0;
        }
        System::addProperty('soft_owner_modules', json_encode($prefs));
    }

    /**
     * Soft Owner login: hide modules they disabled for themselves.
     * Shop users: unchanged (Business Modules + App Features).
     */
    public static function applyModulePrefsForUser(?User $user, array $enabled_modules): array
    {
        if (! $user) {
            return $enabled_modules;
        }

        try {
            if (! method_exists($user, 'isSoftwareOwner') || ! $user->isSoftwareOwner()) {
                return $enabled_modules;
            }
        } catch (\Throwable $e) {
            return $enabled_modules;
        }

        $prefs = self::getSoftOwnerModulePrefs();
        $filtered = [];
        foreach ($enabled_modules as $mod) {
            if (array_key_exists($mod, $prefs) && empty($prefs[$mod])) {
                continue;
            }
            $filtered[] = $mod;
        }

        return array_values($filtered);
    }

    /**
     * Available shop modules Soft Owner can enable for other users.
     */
    public static function shopModuleDefinitions(): array
    {
        try {
            $modules = (new ModuleUtil)->availableModules();
            $out = [];
            foreach ($modules as $key => $def) {
                $out[$key] = is_array($def) ? ($def['name'] ?? $key) : (string) $def;
            }

            return $out;
        } catch (\Throwable $e) {
            return self::softOwnerModuleDefinitions();
        }
    }

    /**
     * Current enabled modules for Soft Owner's business (shop users).
     */
    public static function getShopModulesForBusiness(int $businessId): array
    {
        $prefs = self::defaultSoftOwnerModulePrefs();
        foreach (array_keys(self::shopModuleDefinitions()) as $key) {
            $prefs[$key] = 0;
        }

        try {
            $business = \App\Business::find($businessId);
            $enabled = [];
            if ($business) {
                $raw = $business->enabled_modules;
                $enabled = is_array($raw) ? $raw : [];
            }
            foreach ($enabled as $key) {
                $prefs[$key] = 1;
            }
        } catch (\Throwable $e) {
            // keep defaults off
        }

        return $prefs;
    }

    /**
     * Soft Owner sets which modules shop users (Admin etc.) get.
     * Writes business.enabled_modules for the Soft Owner's current business.
     */
    public static function saveShopModulesForBusiness(int $businessId, array $input): array
    {
        $enabled = [];
        foreach (array_keys(self::shopModuleDefinitions()) as $key) {
            if (! empty($input[$key])) {
                $enabled[] = $key;
            }
        }
        $enabled = array_values(array_unique($enabled));

        $business = \App\Business::find($businessId);
        if ($business) {
            if (class_exists(\App\Utils\ShopModeUtil::class)) {
                $enabled = \App\Utils\ShopModeUtil::overlayIndustryModules($enabled, $businessId);
            }
            $business->enabled_modules = $enabled;
            $business->save();
        }

        // Snapshot for Soft Owner panel
        $prefs = [];
        foreach (array_keys(self::shopModuleDefinitions()) as $key) {
            $prefs[$key] = in_array($key, $enabled, true) ? 1 : 0;
        }
        System::addProperty('shop_user_modules', json_encode($prefs));

        return $enabled;
    }

    /**
     * Whether shop users may open Business Settings → Modules.
     * Soft Owner always can; others only if Soft Owner left the tab enabled.
     */
    public static function canAccessModulesTab(?User $user): bool
    {
        if (! $user) {
            return false;
        }
        try {
            if (method_exists($user, 'isSoftwareOwner') && $user->isSoftwareOwner()) {
                return true;
            }
        } catch (\Throwable $e) {
            return true;
        }

        // Soft Owner keeps Modules under Soft Owner permissions — hide from shop users by default
        return self::canAccess($user, 'modules');
    }
}
