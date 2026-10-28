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
 * Creates demo business "Carpet Shop" with carpet products sold by Foot / Yard + images.
 *
 * Run: php artisan db:seed --class=CarpetShopSeeder
 *   or: php artisan pos:seed-carpet-shop
 */
class CarpetShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Carpet Shop')->first();
        if ($existing) {
            $this->command?->warn('Carpet Shop already exists (id='.$existing->id.'). Updating products / Touch UI…');
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
                'first_name' => 'Carpet',
                'last_name' => 'Admin',
                'username' => 'carpetshop',
                'email' => 'carpetshop@demo.local',
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
                'name' => 'Carpet Shop',
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
                'sku_prefix' => 'CP',
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
                'name' => 'Carpet Shop Main',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '00500',
                'landmark' => 'Galle Road',
                'website' => '',
                'mobile' => '0771112233',
                'alternate_number' => '',
                'email' => 'carpetshop@demo.local',
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

        // Selling units: Foot & Yard (decimal qty for cut lengths)
        $unit_ft = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Foot'],
            [
                'short_name' => 'Ft',
                'allow_decimal' => 1,
                'created_by' => $user_id,
            ]
        );
        $unit_yd = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Yard'],
            [
                'short_name' => 'Yd',
                'allow_decimal' => 1,
                'created_by' => $user_id,
            ]
        );

        $categoryDefs = [
            ['name' => 'Wall to Wall', 'short_code' => 'W2W'],
            ['name' => 'Area Rugs', 'short_code' => 'RUG'],
            ['name' => 'Runners', 'short_code' => 'RUN'],
            ['name' => 'Outdoor', 'short_code' => 'OUT'],
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

        // Prices are per selling unit (Ft or Yd). Stock is length available in that unit.
        $products = [
            ['name' => 'Persian Red Medallion', 'category' => 'Area Rugs', 'sku' => 'CP-PERS-RED', 'purchase' => 4200, 'sell' => 5800, 'stock' => 45, 'image' => 'carpet_persian_red.jpg', 'unit' => 'yd'],
            ['name' => 'Wool Berber Beige', 'category' => 'Wall to Wall', 'sku' => 'CP-BERB-BEI', 'purchase' => 1800, 'sell' => 2650, 'stock' => 120, 'image' => 'carpet_berber_beige.jpg', 'unit' => 'yd'],
            ['name' => 'Nylon Plush Grey', 'category' => 'Wall to Wall', 'sku' => 'CP-PLUSH-GRY', 'purchase' => 1500, 'sell' => 2200, 'stock' => 150, 'image' => 'carpet_plush_grey.jpg', 'unit' => 'yd'],
            ['name' => 'Luxury Ivory Silk-Look', 'category' => 'Area Rugs', 'sku' => 'CP-IVORY', 'purchase' => 5500, 'sell' => 7800, 'stock' => 30, 'image' => 'carpet_ivory_silk.jpg', 'unit' => 'yd'],
            ['name' => 'Shaggy Soft Blue', 'category' => 'Area Rugs', 'sku' => 'CP-SHAG-BLU', 'purchase' => 2800, 'sell' => 3950, 'stock' => 55, 'image' => 'carpet_shaggy_blue.jpg', 'unit' => 'yd'],
            ['name' => 'Jute Natural Weave', 'category' => 'Area Rugs', 'sku' => 'CP-JUTE', 'purchase' => 1200, 'sell' => 1750, 'stock' => 80, 'image' => 'carpet_jute_natural.jpg', 'unit' => 'yd'],
            ['name' => 'Stair Runner Stripe', 'category' => 'Runners', 'sku' => 'CP-STAIR', 'purchase' => 450, 'sell' => 680, 'stock' => 200, 'image' => 'carpet_stair_runner.jpg', 'unit' => 'ft'],
            ['name' => 'Hallway Runner Navy', 'category' => 'Runners', 'sku' => 'CP-HALL-NVY', 'purchase' => 380, 'sell' => 560, 'stock' => 180, 'image' => 'carpet_hallway_navy.jpg', 'unit' => 'ft'],
            ['name' => 'Kids Play Soft Foam', 'category' => 'Runners', 'sku' => 'CP-KIDS', 'purchase' => 320, 'sell' => 480, 'stock' => 160, 'image' => 'carpet_kids_play.jpg', 'unit' => 'ft'],
            ['name' => 'Commercial Loop Grey', 'category' => 'Wall to Wall', 'sku' => 'CP-COMM-LOOP', 'purchase' => 280, 'sell' => 420, 'stock' => 250, 'image' => 'carpet_commercial_loop.jpg', 'unit' => 'ft'],
            ['name' => 'Outdoor Grass Green', 'category' => 'Outdoor', 'sku' => 'CP-OUT-GRASS', 'purchase' => 900, 'sell' => 1350, 'stock' => 100, 'image' => 'carpet_outdoor_grass.jpg', 'unit' => 'yd'],
            ['name' => 'Outdoor Patio Stripe', 'category' => 'Outdoor', 'sku' => 'CP-OUT-PATIO', 'purchase' => 750, 'sell' => 1100, 'stock' => 90, 'image' => 'carpet_outdoor_patio.jpg', 'unit' => 'yd'],
        ];

        $imgDir = public_path('uploads/img');
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $unit_id = ($item['unit'] === 'ft') ? $unit_ft->id : $unit_yd->id;
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
                'alert_quantity' => 10,
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

        $this->command?->info('Carpet Shop ready for Touch POS.');
        $this->command?->info("Business ID: {$business_id} | Location ID: {$location_id}");
        $this->command?->info('Login: carpetshop / 123456  (or carpetshop@demo.local)');
        $this->command?->info("Products created: {$created}, updated: {$updated}");
        $this->command?->info('Units: Foot (Ft), Yard (Yd) — open /pos/create after login');
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Carpet',
            'last_name' => 'Admin',
            'username' => 'carpetshop',
            'email' => 'carpetshop@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
