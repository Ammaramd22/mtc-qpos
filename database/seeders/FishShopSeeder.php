<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Currency;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\VariationLocationDetails;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

/**
 * Creates demo business "Fish Shop" with fish products + images for Touch POS.
 *
 * Run: php artisan db:seed --class=FishShopSeeder
 *   or: php artisan pos:seed-fish-shop
 */
class FishShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        // Idempotent: reuse existing Fish Shop if present
        $existing = Business::where('name', 'Fish Shop')->first();
        if ($existing) {
            $this->command?->warn('Fish Shop already exists (id='.$existing->id.'). Updating products / Touch UI…');
            $business = $existing;
            $user = User::where('business_id', $business->id)->orderBy('id')->first();
            if (! $user) {
                $user = $this->createOwner($business->id);
            }
            $location = BusinessLocation::where('business_id', $business->id)->first();
        } else {
            $currency_id = Currency::where('code', 'LKR')->value('id')
                ?: Currency::where('code', 'USD')->value('id')
                ?: 2;

            $user = User::create([
                'surname' => 'Mr',
                'first_name' => 'Fish',
                'last_name' => 'Admin',
                'username' => 'fishshop',
                'email' => 'fishshop@demo.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
            ]);

            $pos_settings = array_merge($businessUtil->defaultPosSettings(), [
                'pos_ui' => 'touch',
                'touch_ask_qty' => 1,
                'hide_product_suggestion' => 0,
                'show_pricing_on_product_sugesstion' => 1,
            ]);

            $business = $businessUtil->createNewBusiness([
                'name' => 'Fish Shop',
                'currency_id' => $currency_id,
                'start_date' => $now->toDateString(),
                'tax_number_1' => '',
                'tax_label_1' => '',
                'owner_id' => $user->id,
                'time_zone' => config('app.timezone', 'Asia/Colombo'),
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'default_sales_discount' => 0,
                'sell_price_tax' => 'includes',
                'sku_prefix' => 'FS',
                'enable_product_expiry' => 0,
                'enable_tooltip' => 1,
                'purchase_in_diff_currency' => 0,
                'purchase_currency_id' => $currency_id,
                'p_exchange_rate' => 1,
                'transaction_edit_days' => 30,
                'stock_expiry_alert_days' => 30,
                'keyboard_shortcuts' => null,
                'pos_settings' => json_encode($pos_settings),
                'enable_brand' => 0,
                'enable_category' => 1,
                'enable_sub_category' => 1,
                'enable_price_tax' => 1,
                'enable_purchase_status' => 1,
                'enable_lot_number' => 0,
                'enable_racks' => 0,
                'enable_row' => 0,
                'enable_position' => 0,
                'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses'],
                'date_format' => 'd-m-Y',
                'time_format' => 24,
                'currency_symbol_placement' => 'before',
            ]);

            $user->business_id = $business->id;
            $user->save();

            $businessUtil->newBusinessDefaultResources($business->id, $user->id);

            // Ensure Touch POS + pricing on tiles
            $business->pos_settings = json_encode($pos_settings);
            $business->enable_category = 1;
            $business->enable_sub_category = 1;
            $business->save();

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Fish Shop Main',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '00100',
                'landmark' => 'Harbour Road',
                'website' => '',
                'mobile' => '0770000000',
                'alternate_number' => '',
                'email' => 'fishshop@demo.local',
            ]);

            Permission::findOrCreate('location.'.$location->id, 'web');
            $user->givePermissionTo('location.'.$location->id);
            $user->givePermissionTo('access_all_locations');
        }

        $business_id = $business->id;
        $user_id = $user->id;
        $location_id = $location->id;

        // Force touch UI on every run
        $settings = json_decode($business->pos_settings ?: '{}', true) ?: [];
        $settings = array_merge($businessUtil->defaultPosSettings(), $settings, [
            'pos_ui' => 'touch',
            'touch_ask_qty' => 1,
            'hide_product_suggestion' => 0,
            'show_pricing_on_product_sugesstion' => 1,
        ]);
        $business->pos_settings = json_encode($settings);
        $business->enable_category = 1;
        $business->save();

        // Units
        $unit_kg = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Kilogram'],
            [
                'short_name' => 'Kg',
                'allow_decimal' => 1,
                'created_by' => $user_id,
            ]
        );
        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            [
                'short_name' => 'Pc',
                'allow_decimal' => 0,
                'created_by' => $user_id,
            ]
        );

        // Categories
        $categoryDefs = [
            ['name' => 'Fresh Fish', 'short_code' => 'FRESH'],
            ['name' => 'Shellfish', 'short_code' => 'SHELL'],
            ['name' => 'Prepared', 'short_code' => 'PREP'],
        ];
        $categoryMap = [];
        foreach ($categoryDefs as $cat) {
            $record = Category::firstOrCreate(
                [
                    'name' => $cat['name'],
                    'business_id' => $business_id,
                    'category_type' => 'product',
                ],
                [
                    'short_code' => $cat['short_code'],
                    'parent_id' => 0,
                    'created_by' => $user_id,
                ]
            );
            $categoryMap[$cat['name']] = $record->id;
        }

        // Products: name, category, sku, purchase, sell, stock, image, unit
        $products = [
            ['name' => 'Salmon Fillet', 'category' => 'Fresh Fish', 'sku' => 'FS-SALMON', 'purchase' => 1800, 'sell' => 2400, 'stock' => 40, 'image' => 'fish_salmon.jpg', 'unit' => 'kg'],
            ['name' => 'Tuna Steak', 'category' => 'Fresh Fish', 'sku' => 'FS-TUNA', 'purchase' => 1500, 'sell' => 2100, 'stock' => 35, 'image' => 'fish_tuna.jpg', 'unit' => 'kg'],
            ['name' => 'Seer Fish', 'category' => 'Fresh Fish', 'sku' => 'FS-SEER', 'purchase' => 1200, 'sell' => 1650, 'stock' => 50, 'image' => 'fish_seer.jpg', 'unit' => 'kg'],
            ['name' => 'Mackerel', 'category' => 'Fresh Fish', 'sku' => 'FS-MACK', 'purchase' => 450, 'sell' => 650, 'stock' => 80, 'image' => 'fish_mackerel.jpg', 'unit' => 'kg'],
            ['name' => 'Pomfret', 'category' => 'Fresh Fish', 'sku' => 'FS-POMF', 'purchase' => 900, 'sell' => 1250, 'stock' => 45, 'image' => 'fish_pomfret.jpg', 'unit' => 'kg'],
            ['name' => 'Tilapia', 'category' => 'Fresh Fish', 'sku' => 'FS-TILA', 'purchase' => 550, 'sell' => 780, 'stock' => 60, 'image' => 'fish_tilapia.jpg', 'unit' => 'kg'],
            ['name' => 'Prawns', 'category' => 'Shellfish', 'sku' => 'FS-PRAWN', 'purchase' => 1600, 'sell' => 2200, 'stock' => 30, 'image' => 'fish_prawns.jpg', 'unit' => 'kg'],
            ['name' => 'Crab', 'category' => 'Shellfish', 'sku' => 'FS-CRAB', 'purchase' => 1400, 'sell' => 1950, 'stock' => 25, 'image' => 'fish_crab.jpg', 'unit' => 'kg'],
            ['name' => 'Squid', 'category' => 'Shellfish', 'sku' => 'FS-SQUID', 'purchase' => 800, 'sell' => 1100, 'stock' => 40, 'image' => 'fish_squid.jpg', 'unit' => 'kg'],
            ['name' => 'Lobster', 'category' => 'Shellfish', 'sku' => 'FS-LOB', 'purchase' => 3500, 'sell' => 4800, 'stock' => 12, 'image' => 'fish_lobster.jpg', 'unit' => 'kg'],
            ['name' => 'Anchovies', 'category' => 'Fresh Fish', 'sku' => 'FS-ANCH', 'purchase' => 350, 'sell' => 520, 'stock' => 70, 'image' => 'fish_anchovy.jpg', 'unit' => 'kg'],
            ['name' => 'Fish Cutlets (Pack 6)', 'category' => 'Prepared', 'sku' => 'FS-CUTLET', 'purchase' => 280, 'sell' => 420, 'stock' => 100, 'image' => 'fish_cutlets.jpg', 'unit' => 'pc'],
        ];

        $imgDir = public_path('uploads/img');
        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $unit_id = ($item['unit'] === 'pc') ? $unit_pc->id : $unit_kg->id;
            $image = $item['image'];
            if (! is_file($imgDir.DIRECTORY_SEPARATOR.$image)) {
                $image = null;
            }

            $product = Product::where('business_id', $business_id)->where('sku', $item['sku'])->first();
            if ($product) {
                $product->update([
                    'name' => $item['name'],
                    'category_id' => $categoryMap[$item['category']] ?? null,
                    'unit_id' => $unit_id,
                    'image' => $image,
                    'enable_stock' => 1,
                    'not_for_selling' => 0,
                    'is_inactive' => 0,
                ]);
                $product->product_locations()->sync([$location_id]);
                $updated++;
                continue;
            }

            $product = Product::create([
                'name' => $item['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unit_id,
                'brand_id' => null,
                'category_id' => $categoryMap[$item['category']] ?? null,
                'tax' => null,
                'tax_type' => 'inclusive',
                'enable_stock' => 1,
                'alert_quantity' => 5,
                'sku' => $item['sku'],
                'barcode_type' => 'C128',
                'image' => $image,
                'created_by' => $user_id,
                'not_for_selling' => 0,
                'is_inactive' => 0,
            ]);

            $purchase = $item['purchase'];
            $sell = $item['sell'];
            $productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                $purchase,
                $purchase,
                0,
                $sell,
                $sell
            );

            $product = Product::with('variations')->find($product->id);
            $variation = $product->variations->first();

            $product->product_locations()->sync([$location_id]);

            VariationLocationDetails::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'variation_id' => $variation->id,
                    'location_id' => $location_id,
                ],
                [
                    'product_variation_id' => $variation->product_variation_id,
                    'qty_available' => $item['stock'],
                ]
            );

            $total = $purchase * $item['stock'];
            $transaction = Transaction::create([
                'type' => 'opening_stock',
                'opening_stock_product_id' => $product->id,
                'status' => 'received',
                'business_id' => $business_id,
                'transaction_date' => $now->format('Y-m-d H:i:s'),
                'total_before_tax' => $total,
                'location_id' => $location_id,
                'final_total' => $total,
                'payment_status' => 'paid',
                'created_by' => $user_id,
            ]);

            PurchaseLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $item['stock'],
                'purchase_price' => $purchase,
                'purchase_price_inc_tax' => $purchase,
                'pp_without_discount' => $purchase,
                'item_tax' => 0,
            ]);

            $created++;
        }

        // Open cash register for demo user
        $open = CashRegister::where('user_id', $user_id)
            ->where('status', 'open')
            ->first();
        if (! $open) {
            CashRegister::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'user_id' => $user_id,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $this->command?->info('Fish Shop ready for Touch POS.');
        $this->command?->info("Business ID: {$business_id} | Location ID: {$location_id}");
        $this->command?->info('Login: fishshop / 123456  (or fishshop@demo.local)');
        $this->command?->info("Products created: {$created}, updated: {$updated}");
        $this->command?->info('POS UI: touch — open /pos/create after login');
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Fish',
            'last_name' => 'Admin',
            'username' => 'fishshop',
            'email' => 'fishshop@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
