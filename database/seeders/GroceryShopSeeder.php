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
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

/**
 * Demo business "Grocery Shop" — fresh groceries for Touch POS.
 *
 * Run: php artisan pos:seed-grocery-shop
 */
class GroceryShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Grocery Shop')->first();
        if ($existing) {
            $this->command?->warn('Grocery Shop already exists (id='.$existing->id.'). Updating products / Touch UI…');
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
                'first_name' => 'Grocery',
                'last_name' => 'Admin',
                'username' => 'grocery',
                'email' => 'grocery@demo.local',
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
                'name' => 'Grocery Shop',
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
                'sku_prefix' => 'GR',
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
                'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account'],
                'date_format' => 'd-m-Y',
                'time_format' => 24,
                'currency_symbol_placement' => 'before',
            ]);

            $user->business_id = $business->id;
            $user->save();

            $businessUtil->newBusinessDefaultResources($business->id, $user->id);

            $business->pos_settings = json_encode($pos_settings);
            $business->enable_category = 1;
            $business->enable_sub_category = 1;
            $business->save();

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Grocery Main',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '00300',
                'landmark' => 'Market Road',
                'website' => '',
                'mobile' => '0772223344',
                'alternate_number' => '',
                'email' => 'grocery@demo.local',
            ]);

            Permission::findOrCreate('location.'.$location->id, 'web');
            $user->givePermissionTo('location.'.$location->id);
            $user->givePermissionTo('access_all_locations');
        }

        $business_id = $business->id;
        $user_id = $user->id;
        $location_id = $location->id;

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

        $unit_kg = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Kilogram'],
            ['short_name' => 'Kg', 'allow_decimal' => 1, 'created_by' => $user_id]
        );
        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            ['short_name' => 'Pc', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_pk = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pack'],
            ['short_name' => 'Pk', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryDefs = [
            ['name' => 'Vegetables', 'short_code' => 'VEG'],
            ['name' => 'Fruits', 'short_code' => 'FRU'],
            ['name' => 'Dairy & Eggs', 'short_code' => 'DRY'],
            ['name' => 'Staples', 'short_code' => 'STP'],
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

        $unitMap = ['kg' => $unit_kg->id, 'pc' => $unit_pc->id, 'pk' => $unit_pk->id];

        $products = [
            ['name' => 'Tomatoes', 'category' => 'Vegetables', 'sku' => 'GR-TOMATO', 'purchase' => 180, 'sell' => 280, 'stock' => 80, 'image' => 'grocery_tomato.jpg', 'unit' => 'kg'],
            ['name' => 'Onions', 'category' => 'Vegetables', 'sku' => 'GR-ONION', 'purchase' => 120, 'sell' => 190, 'stock' => 100, 'image' => 'grocery_onion.jpg', 'unit' => 'kg'],
            ['name' => 'Potatoes', 'category' => 'Vegetables', 'sku' => 'GR-POTATO', 'purchase' => 140, 'sell' => 220, 'stock' => 90, 'image' => 'grocery_potato.jpg', 'unit' => 'kg'],
            ['name' => 'Green Beans', 'category' => 'Vegetables', 'sku' => 'GR-BEANS', 'purchase' => 250, 'sell' => 380, 'stock' => 40, 'image' => 'grocery_beans.jpg', 'unit' => 'kg'],
            ['name' => 'Bananas', 'category' => 'Fruits', 'sku' => 'GR-BANANA', 'purchase' => 160, 'sell' => 250, 'stock' => 70, 'image' => 'grocery_banana.jpg', 'unit' => 'kg'],
            ['name' => 'Apples', 'category' => 'Fruits', 'sku' => 'GR-APPLE', 'purchase' => 450, 'sell' => 650, 'stock' => 50, 'image' => 'grocery_apple.jpg', 'unit' => 'kg'],
            ['name' => 'Oranges', 'category' => 'Fruits', 'sku' => 'GR-ORANGE', 'purchase' => 320, 'sell' => 480, 'stock' => 55, 'image' => 'grocery_orange.jpg', 'unit' => 'kg'],
            ['name' => 'Fresh Milk 1L', 'category' => 'Dairy & Eggs', 'sku' => 'GR-MILK', 'purchase' => 220, 'sell' => 320, 'stock' => 60, 'image' => 'grocery_milk.jpg', 'unit' => 'pc'],
            ['name' => 'Eggs (Pack 10)', 'category' => 'Dairy & Eggs', 'sku' => 'GR-EGGS', 'purchase' => 380, 'sell' => 520, 'stock' => 80, 'image' => 'grocery_eggs.jpg', 'unit' => 'pk'],
            ['name' => 'Yoghurt Cup', 'category' => 'Dairy & Eggs', 'sku' => 'GR-YOGHURT', 'purchase' => 80, 'sell' => 120, 'stock' => 100, 'image' => 'grocery_yoghurt.jpg', 'unit' => 'pc'],
            ['name' => 'Rice 5kg', 'category' => 'Staples', 'sku' => 'GR-RICE5', 'purchase' => 1200, 'sell' => 1650, 'stock' => 40, 'image' => 'grocery_rice.jpg', 'unit' => 'pk'],
            ['name' => 'Sugar 1kg', 'category' => 'Staples', 'sku' => 'GR-SUGAR', 'purchase' => 280, 'sell' => 380, 'stock' => 70, 'image' => 'grocery_sugar.jpg', 'unit' => 'pk'],
        ];

        $this->seedProducts($products, $unitMap, $categoryMap, $business_id, $user_id, $location_id, $productUtil, $now);

        $open = CashRegister::where('user_id', $user_id)->where('status', 'open')->first();
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

        $this->command?->info('Grocery Shop ready.');
        $this->command?->info("Business ID: {$business_id} | Login: grocery / 123456");
    }

    protected function seedProducts(array $products, array $unitMap, array $categoryMap, $business_id, $user_id, $location_id, ProductUtil $productUtil, $now): void
    {
        $imgDir = public_path('uploads/img');
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }
        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $unit_id = $unitMap[$item['unit']] ?? $unitMap['pc'];
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

        $this->command?->info("Products created: {$created}, updated: {$updated}");
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Grocery',
            'last_name' => 'Admin',
            'username' => 'grocery',
            'email' => 'grocery@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
