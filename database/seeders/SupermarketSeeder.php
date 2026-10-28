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
 * Demo business "Supermarket" — packaged goods for Touch POS.
 *
 * Run: php artisan pos:seed-supermarket
 */
class SupermarketSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Supermarket')->first();
        if ($existing) {
            $this->command?->warn('Supermarket already exists (id='.$existing->id.'). Updating products / Touch UI…');
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
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'username' => 'supermarket',
                'email' => 'supermarket@demo.local',
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
                'name' => 'Supermarket',
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
                'sku_prefix' => 'SM',
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
                'name' => 'Supermarket Main',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '00700',
                'landmark' => 'High Street',
                'website' => '',
                'mobile' => '0773334455',
                'alternate_number' => '',
                'email' => 'supermarket@demo.local',
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

        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            ['short_name' => 'Pc', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_pk = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pack'],
            ['short_name' => 'Pk', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_bt = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Bottle'],
            ['short_name' => 'Btl', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryDefs = [
            ['name' => 'Beverages', 'short_code' => 'BEV'],
            ['name' => 'Snacks', 'short_code' => 'SNK'],
            ['name' => 'Household', 'short_code' => 'HSE'],
            ['name' => 'Personal Care', 'short_code' => 'PER'],
            ['name' => 'Frozen', 'short_code' => 'FRZ'],
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

        $unitMap = ['pc' => $unit_pc->id, 'pk' => $unit_pk->id, 'btl' => $unit_bt->id];

        $products = [
            ['name' => 'Cola 1.5L', 'category' => 'Beverages', 'sku' => 'SM-COLA', 'purchase' => 280, 'sell' => 420, 'stock' => 120, 'image' => 'super_cola.jpg', 'unit' => 'btl'],
            ['name' => 'Orange Juice 1L', 'category' => 'Beverages', 'sku' => 'SM-OJ', 'purchase' => 350, 'sell' => 520, 'stock' => 80, 'image' => 'super_orange_juice.jpg', 'unit' => 'btl'],
            ['name' => 'Mineral Water 500ml', 'category' => 'Beverages', 'sku' => 'SM-WATER', 'purchase' => 40, 'sell' => 80, 'stock' => 200, 'image' => 'super_water.jpg', 'unit' => 'btl'],
            ['name' => 'Potato Chips', 'category' => 'Snacks', 'sku' => 'SM-CHIPS', 'purchase' => 120, 'sell' => 190, 'stock' => 150, 'image' => 'super_chips.jpg', 'unit' => 'pk'],
            ['name' => 'Chocolate Bar', 'category' => 'Snacks', 'sku' => 'SM-CHOC', 'purchase' => 90, 'sell' => 150, 'stock' => 180, 'image' => 'super_chocolate.jpg', 'unit' => 'pc'],
            ['name' => 'Biscuits Pack', 'category' => 'Snacks', 'sku' => 'SM-BISCUIT', 'purchase' => 140, 'sell' => 220, 'stock' => 100, 'image' => 'super_biscuits.jpg', 'unit' => 'pk'],
            ['name' => 'Dish Wash Liquid', 'category' => 'Household', 'sku' => 'SM-DISH', 'purchase' => 220, 'sell' => 350, 'stock' => 70, 'image' => 'super_dishwash.jpg', 'unit' => 'btl'],
            ['name' => 'Laundry Detergent', 'category' => 'Household', 'sku' => 'SM-DETERGENT', 'purchase' => 480, 'sell' => 720, 'stock' => 50, 'image' => 'super_detergent.jpg', 'unit' => 'pk'],
            ['name' => 'Tissue Pack', 'category' => 'Household', 'sku' => 'SM-TISSUE', 'purchase' => 160, 'sell' => 250, 'stock' => 90, 'image' => 'super_tissue.jpg', 'unit' => 'pk'],
            ['name' => 'Shampoo 400ml', 'category' => 'Personal Care', 'sku' => 'SM-SHAMPOO', 'purchase' => 420, 'sell' => 650, 'stock' => 60, 'image' => 'super_shampoo.jpg', 'unit' => 'btl'],
            ['name' => 'Toothpaste', 'category' => 'Personal Care', 'sku' => 'SM-PASTE', 'purchase' => 180, 'sell' => 280, 'stock' => 100, 'image' => 'super_toothpaste.jpg', 'unit' => 'pc'],
            ['name' => 'Frozen Pizza', 'category' => 'Frozen', 'sku' => 'SM-PIZZA', 'purchase' => 650, 'sell' => 980, 'stock' => 40, 'image' => 'super_pizza.jpg', 'unit' => 'pc'],
        ];

        $imgDir = public_path('uploads/img');
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $unit_id = $unitMap[$item['unit']] ?? $unit_pc->id;
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

        $this->command?->info('Supermarket ready.');
        $this->command?->info("Business ID: {$business_id} | Login: supermarket / 123456");
        $this->command?->info("Products created: {$created}, updated: {$updated}");
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'username' => 'supermarket',
            'email' => 'supermarket@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
