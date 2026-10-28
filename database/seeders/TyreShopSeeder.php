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
 * Demo business "Tyre Shop" — car / bike / van tyres + accessories for Touch POS.
 *
 * Run: php artisan pos:seed-tyre-shop
 */
class TyreShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Tyre Shop')->first();
        if ($existing) {
            $this->command?->warn('Tyre Shop already exists (id='.$existing->id.'). Updating products / Touch UI…');
            $business = $existing;
            $mods = is_array($business->enabled_modules) ? $business->enabled_modules : (json_decode($business->enabled_modules ?? '[]', true) ?: []);
            if (! in_array('tyre_shop', $mods, true)) {
                $mods[] = 'tyre_shop';
                $business->enabled_modules = $mods;
                $business->save();
            }
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
                'first_name' => 'Tyre',
                'last_name' => 'Admin',
                'username' => 'tyreshop',
                'email' => 'tyreshop@demo.local',
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
                'name' => 'Tyre Shop',
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
                'sku_prefix' => 'TY',
                'enable_product_expiry' => 0,
                'enable_tooltip' => 1,
                'purchase_in_diff_currency' => 0,
                'purchase_currency_id' => $currency_id,
                'p_exchange_rate' => 1,
                'transaction_edit_days' => 30,
                'stock_expiry_alert_days' => 30,
                'keyboard_shortcuts' => null,
                'pos_settings' => json_encode($pos_settings),
                'enable_brand' => 1,
                'enable_category' => 1,
                'enable_sub_category' => 1,
                'enable_price_tax' => 1,
                'enable_purchase_status' => 1,
                'enable_lot_number' => 0,
                'enable_racks' => 0,
                'enable_row' => 0,
                'enable_position' => 0,
                'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account', 'tyre_shop'],
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
            $business->enable_brand = 1;
            $business->save();

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Tyre Shop Main',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '01000',
                'landmark' => 'Baseline Road',
                'website' => '',
                'mobile' => '0775556677',
                'alternate_number' => '',
                'email' => 'tyreshop@demo.local',
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
        $business->enable_brand = 1;
        $business->save();

        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            ['short_name' => 'Pc', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_set = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Set'],
            ['short_name' => 'Set', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryDefs = [
            ['name' => 'Car Tyres', 'short_code' => 'CAR'],
            ['name' => 'Motorcycle Tyres', 'short_code' => 'MOTO'],
            ['name' => 'Van & Light Truck', 'short_code' => 'VAN'],
            ['name' => 'Tubes & Accessories', 'short_code' => 'ACC'],
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

        $unitMap = ['pc' => $unit_pc->id, 'set' => $unit_set->id];

        // LKR demo prices typical for Sri Lanka tyre retail
        $products = [
            ['name' => 'Michelin Pilot Sport 205/55R16', 'category' => 'Car Tyres', 'sku' => 'TY-MICH-20555', 'purchase' => 28500, 'sell' => 34500, 'stock' => 24, 'image' => 'tyre_michelin_205.jpg', 'unit' => 'pc'],
            ['name' => 'Bridgestone Turanza 195/65R15', 'category' => 'Car Tyres', 'sku' => 'TY-BRDG-19565', 'purchase' => 22000, 'sell' => 26800, 'stock' => 32, 'image' => 'tyre_bridgestone_195.jpg', 'unit' => 'pc'],
            ['name' => 'Goodyear EfficientGrip 185/65R15', 'category' => 'Car Tyres', 'sku' => 'TY-GOOD-18565', 'purchase' => 19500, 'sell' => 23800, 'stock' => 28, 'image' => 'tyre_goodyear_185.jpg', 'unit' => 'pc'],
            ['name' => 'Continental Premium 215/60R16', 'category' => 'Car Tyres', 'sku' => 'TY-CONT-21560', 'purchase' => 31000, 'sell' => 37500, 'stock' => 16, 'image' => 'tyre_continental_215.jpg', 'unit' => 'pc'],
            ['name' => 'Dunlop SP Sport 225/45R17', 'category' => 'Car Tyres', 'sku' => 'TY-DUNL-22545', 'purchase' => 33500, 'sell' => 39900, 'stock' => 12, 'image' => 'tyre_dunlop_225.jpg', 'unit' => 'pc'],
            ['name' => 'CEAT Secura Drive 175/70R13', 'category' => 'Car Tyres', 'sku' => 'TY-CEAT-17570', 'purchase' => 12500, 'sell' => 15500, 'stock' => 40, 'image' => 'tyre_ceat_175.jpg', 'unit' => 'pc'],
            ['name' => 'MRF Zapper 100/90-17 (Front)', 'category' => 'Motorcycle Tyres', 'sku' => 'TY-MRF-10090', 'purchase' => 4200, 'sell' => 5500, 'stock' => 50, 'image' => 'tyre_moto_front.jpg', 'unit' => 'pc'],
            ['name' => 'MRF Nylogrip 120/80-17 (Rear)', 'category' => 'Motorcycle Tyres', 'sku' => 'TY-MRF-12080', 'purchase' => 4800, 'sell' => 6200, 'stock' => 48, 'image' => 'tyre_moto_rear.jpg', 'unit' => 'pc'],
            ['name' => 'Apollo Amazer 195R15C Van', 'category' => 'Van & Light Truck', 'sku' => 'TY-APOL-195R15', 'purchase' => 18500, 'sell' => 22500, 'stock' => 18, 'image' => 'tyre_van_195.jpg', 'unit' => 'pc'],
            ['name' => 'Inner Tube 175/70R13', 'category' => 'Tubes & Accessories', 'sku' => 'TY-TUBE-175', 'purchase' => 1800, 'sell' => 2800, 'stock' => 60, 'image' => 'tyre_tube.jpg', 'unit' => 'pc'],
            ['name' => 'Tubeless Valve Stem (Set of 4)', 'category' => 'Tubes & Accessories', 'sku' => 'TY-VALVE-4', 'purchase' => 350, 'sell' => 650, 'stock' => 100, 'image' => 'tyre_valve.jpg', 'unit' => 'set'],
            ['name' => 'Puncture Repair Kit', 'category' => 'Tubes & Accessories', 'sku' => 'TY-REPAIR-KIT', 'purchase' => 900, 'sell' => 1500, 'stock' => 75, 'image' => 'tyre_repair_kit.jpg', 'unit' => 'pc'],
            ['name' => 'Wheel Balancing Weights Kit', 'category' => 'Tubes & Accessories', 'sku' => 'TY-BALANCE', 'purchase' => 1200, 'sell' => 2000, 'stock' => 40, 'image' => 'tyre_balance.jpg', 'unit' => 'set'],
            ['name' => 'Tyre Shine Spray 500ml', 'category' => 'Tubes & Accessories', 'sku' => 'TY-SHINE', 'purchase' => 650, 'sell' => 1100, 'stock' => 55, 'image' => 'tyre_shine.jpg', 'unit' => 'pc'],
        ];

        $this->seedProducts($products, $unitMap, $categoryMap, $business_id, $user_id, $location_id, $productUtil, $now);

        if (\Illuminate\Support\Facades\Schema::hasColumn('business', 'shop_mode')) {
            \App\Utils\ShopModeUtil::applyToBusiness($business->fresh(), 'tyre_shop');
            \App\Utils\ShopModeUtil::forgetCache($business_id);
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

        $this->command?->info('Tyre Shop ready.');
        $this->command?->info("Business ID: {$business_id} | Login: tyreshop / 123456");
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
                'alert_quantity' => 4,
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
            'first_name' => 'Tyre',
            'last_name' => 'Admin',
            'username' => 'tyreshop',
            'email' => 'tyreshop@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
