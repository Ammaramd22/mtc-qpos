<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Software Owner access for Business Settings.
 *
 * - users.is_software_owner: flag for vendor / software owner login
 * - system.settings_menu_visibility: JSON enable/disable for each Settings option
 *   (shop admins only see enabled options; software owner always sees all)
 *
 * After migrate, mark your owner account:
 *   UPDATE users SET is_software_owner = 1 WHERE username = 'admin';
 *
 * Or set in .env:
 *   ADMINISTRATOR_USERNAMES=softwareowner,admin
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'is_software_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->boolean('is_software_owner')->default(0)->after('allow_login');
            });
        }

        $defaults = json_encode([
            // Sidebar / settings pages
            'business_settings' => 1,
            'business_locations' => 1,
            'invoice_settings' => 1,
            'barcode_settings' => 1,
            'receipt_printers' => 1,
            // Business Settings tabs
            'business' => 1,
            'tax' => 1,
            'product' => 1,
            'contact' => 1,
            'sale' => 1,
            'pos' => 1,
            'display_screen' => 1,
            'purchases' => 1,
            'payment' => 1,
            'dashboard' => 1,
            'system' => 1,
            'prefixes' => 1,
            'email_settings' => 1,
            'sms_settings' => 1,
            'reward_point_settings' => 1,
            'modules' => 1,
            'custom_labels' => 1,
            'api' => 1,
            'bartender' => 1,
            'weighing_scale_label' => 1,
            'weighing_scale_barcode_scan' => 1,
            'costcode' => 1,
            'offline_billing' => 1,
            'pwa' => 1,
            'ebill' => 1,
            'mobile_shop' => 1,
        ]);

        $exists = DB::table('system')->where('key', 'settings_menu_visibility')->exists();
        if ($exists) {
            DB::table('system')->where('key', 'settings_menu_visibility')->update(['value' => $defaults]);
        } else {
            DB::table('system')->insert([
                'key' => 'settings_menu_visibility',
                'value' => $defaults,
            ]);
        }

        // Promote known administrator usernames (from env) if present
        $names = array_filter(array_map('trim', explode(',', (string) env('ADMINISTRATOR_USERNAMES', 'softwareowner'))));
        if (! empty($names)) {
            DB::table('users')
                ->whereIn('username', $names)
                ->update(['is_software_owner' => 1]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'is_software_owner')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('is_software_owner');
            });
        }

        DB::table('system')->where('key', 'settings_menu_visibility')->delete();
    }
};
