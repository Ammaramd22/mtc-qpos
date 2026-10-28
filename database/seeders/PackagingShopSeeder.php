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
 * Demo: Packaging Shop — food packs, tissue, pizza boxes, tooth sticks, picks.
 *
 * Run: php artisan pos:seed-packaging-shop
 */
class PackagingShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Packaging Shop')->first();
        if ($existing) {
            $this->command?->warn('Packaging Shop already exists (id='.$existing->id.'). Updating products…');
            $business = $existing;
            $user = User::where('business_id', $business->id)->where('username', 'packshop')->first()
                ?: User::where('business_id', $business->id)->orderBy('id')->first();
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
                'first_name' => 'Pack',
                'last_name' => 'Admin',
                'username' => 'packshop',
                'email' => 'packshop@demo.local',
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
                'name' => 'Packaging Shop',
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
                'sku_prefix' => 'PK',
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
                'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account', 'wholesale'],
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
                'name' => 'Pack Main Store',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '01000',
                'landmark' => 'Packaging Avenue',
                'website' => '',
                'mobile' => '0775556677',
                'alternate_number' => '',
                'email' => 'packshop@demo.local',
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
        $enabled = $business->enabled_modules;
        if (is_string($enabled)) {
            $enabled = json_decode($enabled, true) ?: [];
        }
        if (! is_array($enabled)) {
            $enabled = [];
        }
        if (! in_array('wholesale', $enabled, true)) {
            $enabled[] = 'wholesale';
        }
        $business->enabled_modules = array_values(array_unique($enabled));
        $business->save();
        \App\Utils\WholesaleUtil::ensurePriceGroup((int) $business_id);

        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            ['short_name' => 'Pc(s)', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_pk = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pack'],
            ['short_name' => 'Pk', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_bx = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Box'],
            ['short_name' => 'Box', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryDefs = [
            ['name' => 'Food Packs', 'short_code' => 'FOOD'],
            ['name' => 'Tissue & Napkins', 'short_code' => 'TISS'],
            ['name' => 'Pizza Boxes', 'short_code' => 'PIZZ'],
            ['name' => 'Tooth Sticks & Picks', 'short_code' => 'PICK'],
            ['name' => 'Bags & Wrap', 'short_code' => 'BAGS'],
            ['name' => 'Cups & Containers', 'short_code' => 'CUPS'],
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

        $unitMap = ['pc' => $unit_pc->id, 'pk' => $unit_pk->id, 'bx' => $unit_bx->id];

        $this->ensureProductImages();

        $products = [
            ['name' => 'Food Pack Small', 'category' => 'Food Packs', 'sku' => 'PK-FOOD-S', 'purchase' => 8, 'sell' => 15, 'wholesale' => 11, 'stock' => 500, 'image' => 'pack_food_small.jpg', 'unit' => 'pc'],
            ['name' => 'Food Pack Medium', 'category' => 'Food Packs', 'sku' => 'PK-FOOD-M', 'purchase' => 12, 'sell' => 22, 'wholesale' => 16, 'stock' => 400, 'image' => 'pack_food_medium.jpg', 'unit' => 'pc'],
            ['name' => 'Food Pack Large', 'category' => 'Food Packs', 'sku' => 'PK-FOOD-L', 'purchase' => 18, 'sell' => 30, 'wholesale' => 22, 'stock' => 300, 'image' => 'pack_food_large.jpg', 'unit' => 'pc'],
            ['name' => 'Burger Box', 'category' => 'Food Packs', 'sku' => 'PK-BURGER', 'purchase' => 14, 'sell' => 25, 'wholesale' => 18, 'stock' => 250, 'image' => 'pack_burger_box.jpg', 'unit' => 'pc'],
            ['name' => 'Tissue Pack 200s', 'category' => 'Tissue & Napkins', 'sku' => 'PK-TISSUE', 'purchase' => 95, 'sell' => 150, 'wholesale' => 120, 'stock' => 120, 'image' => 'pack_tissue.jpg', 'unit' => 'pk'],
            ['name' => 'Napkin Pack 100s', 'category' => 'Tissue & Napkins', 'sku' => 'PK-NAPKIN', 'purchase' => 110, 'sell' => 180, 'wholesale' => 140, 'stock' => 100, 'image' => 'pack_napkin.jpg', 'unit' => 'pk'],
            ['name' => 'Kitchen Roll', 'category' => 'Tissue & Napkins', 'sku' => 'PK-KROLL', 'purchase' => 160, 'sell' => 250, 'wholesale' => 200, 'stock' => 80, 'image' => 'pack_kitchen_roll.jpg', 'unit' => 'pc'],
            ['name' => 'Pizza Box 9 inch', 'category' => 'Pizza Boxes', 'sku' => 'PK-PIZZA9', 'purchase' => 35, 'sell' => 55, 'wholesale' => 42, 'stock' => 200, 'image' => 'pack_pizza_9.jpg', 'unit' => 'pc'],
            ['name' => 'Pizza Box 12 inch', 'category' => 'Pizza Boxes', 'sku' => 'PK-PIZZA12', 'purchase' => 48, 'sell' => 75, 'wholesale' => 58, 'stock' => 180, 'image' => 'pack_pizza_12.jpg', 'unit' => 'pc'],
            ['name' => 'Pizza Box 14 inch', 'category' => 'Pizza Boxes', 'sku' => 'PK-PIZZA14', 'purchase' => 60, 'sell' => 95, 'wholesale' => 72, 'stock' => 150, 'image' => 'pack_pizza_14.jpg', 'unit' => 'pc'],
            ['name' => 'Tooth Sticks Box', 'category' => 'Tooth Sticks & Picks', 'sku' => 'PK-TOOTH', 'purchase' => 45, 'sell' => 75, 'wholesale' => 55, 'stock' => 200, 'image' => 'pack_toothstick.jpg', 'unit' => 'bx'],
            ['name' => 'Cocktail Picks Pack', 'category' => 'Tooth Sticks & Picks', 'sku' => 'PK-PICKS', 'purchase' => 55, 'sell' => 90, 'wholesale' => 68, 'stock' => 160, 'image' => 'pack_picks.jpg', 'unit' => 'pk'],
            ['name' => 'Bamboo Skewers Pack', 'category' => 'Tooth Sticks & Picks', 'sku' => 'PK-SKEWER', 'purchase' => 70, 'sell' => 110, 'wholesale' => 85, 'stock' => 140, 'image' => 'pack_skewer.jpg', 'unit' => 'pk'],
            ['name' => 'Paper Carry Bag', 'category' => 'Bags & Wrap', 'sku' => 'PK-BAG', 'purchase' => 6, 'sell' => 12, 'wholesale' => 9, 'stock' => 600, 'image' => 'pack_paper_bag.jpg', 'unit' => 'pc'],
            ['name' => 'Cling Film Roll', 'category' => 'Bags & Wrap', 'sku' => 'PK-CLING', 'purchase' => 220, 'sell' => 350, 'wholesale' => 280, 'stock' => 60, 'image' => 'pack_cling.jpg', 'unit' => 'pc'],
            ['name' => 'Aluminum Foil Pack', 'category' => 'Bags & Wrap', 'sku' => 'PK-FOIL', 'purchase' => 280, 'sell' => 420, 'wholesale' => 340, 'stock' => 50, 'image' => 'pack_foil.jpg', 'unit' => 'pk'],
            ['name' => 'Soup Cup 8oz', 'category' => 'Cups & Containers', 'sku' => 'PK-SOUP8', 'purchase' => 9, 'sell' => 16, 'wholesale' => 12, 'stock' => 400, 'image' => 'pack_soup_cup.jpg', 'unit' => 'pc'],
            ['name' => 'Plastic Cup 250ml', 'category' => 'Cups & Containers', 'sku' => 'PK-CUP250', 'purchase' => 4, 'sell' => 8, 'wholesale' => 6, 'stock' => 800, 'image' => 'pack_plastic_cup.jpg', 'unit' => 'pc'],
            ['name' => 'Straw Flexible Pack', 'category' => 'Cups & Containers', 'sku' => 'PK-STRAW', 'purchase' => 85, 'sell' => 140, 'wholesale' => 110, 'stock' => 90, 'image' => 'pack_straw.jpg', 'unit' => 'pk'],
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

        $this->command?->info('Packaging Shop ready.');
        $this->command?->info("Business ID: {$business_id} | Login: packshop / 123456");
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
                $variation = $product->variations()->first();
                if ($variation && array_key_exists('wholesale', $item)) {
                    $variation->default_sell_price = $item['sell'];
                    $variation->sell_price_inc_tax = $item['sell'];
                    $variation->default_purchase_price = $item['purchase'];
                    $variation->dpp_inc_tax = $item['purchase'];
                    $variation->save();
                    \App\Utils\WholesaleUtil::saveWholesalePrice((int) $variation->id, (int) $business_id, $item['wholesale']);
                }
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
                'alert_quantity' => 20,
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
            if ($variation && array_key_exists('wholesale', $item)) {
                \App\Utils\WholesaleUtil::saveWholesalePrice((int) $variation->id, (int) $business_id, $item['wholesale']);
            }
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
            'first_name' => 'Pack',
            'last_name' => 'Admin',
            'username' => 'packshop',
            'email' => 'packshop@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }

    /**
     * Generate simple placeholder product images if missing.
     */
    protected function ensureProductImages(): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $dir = public_path('uploads/img');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $palette = [
            'pack_food_small.jpg' => [0xE8, 0xA8, 0x3E],
            'pack_food_medium.jpg' => [0xD4, 0x8C, 0x2E],
            'pack_food_large.jpg' => [0xC0, 0x78, 0x1E],
            'pack_burger_box.jpg' => [0xB5, 0x6A, 0x2A],
            'pack_tissue.jpg' => [0xA8, 0xD5, 0xE5],
            'pack_napkin.jpg' => [0xF5, 0xF0, 0xE6],
            'pack_kitchen_roll.jpg' => [0xE0, 0xE0, 0xE0],
            'pack_pizza_9.jpg' => [0xC4, 0x3D, 0x2F],
            'pack_pizza_12.jpg' => [0xA8, 0x32, 0x28],
            'pack_pizza_14.jpg' => [0x8C, 0x28, 0x20],
            'pack_toothstick.jpg' => [0xD2, 0xB4, 0x8C],
            'pack_picks.jpg' => [0xE8, 0xC4, 0x7A],
            'pack_skewer.jpg' => [0xC4, 0xA4, 0x6A],
            'pack_paper_bag.jpg' => [0xC9, 0xB2, 0x8B],
            'pack_cling.jpg' => [0x9E, 0xC9, 0xD8],
            'pack_foil.jpg' => [0xB8, 0xC0, 0xC8],
            'pack_soup_cup.jpg' => [0xE8, 0xD5, 0xA3],
            'pack_plastic_cup.jpg' => [0xB8, 0xE0, 0xF0],
            'pack_straw.jpg' => [0xF0, 0x8A, 0x5C],
        ];

        foreach ($palette as $file => $rgb) {
            $path = $dir.DIRECTORY_SEPARATOR.$file;
            if (is_file($path)) {
                continue;
            }
            $w = 400;
            $h = 400;
            $img = imagecreatetruecolor($w, $h);
            $bg = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            imagefilledrectangle($img, 0, 0, $w, $h, $bg);
            $fg = imagecolorallocate($img, 255, 255, 255);
            $label = strtoupper(str_replace(['pack_', '.jpg', '_'], ['', '', ' '], $file));
            imagestring($img, 5, 24, 180, substr($label, 0, 22), $fg);
            imagejpeg($img, $path, 85);
            imagedestroy($img);
        }
    }
}
