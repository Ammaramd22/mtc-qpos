<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Currency;
use App\InvoiceLayout;
use App\MobileImei;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\VariationLocationDetails;
use App\Warranty;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

/**
 * Demo: Mobile Shop — phones with IMEI, accessories, warranty, Classic 80mm mobile receipt.
 *
 * Run: php artisan pos:seed-mobile-shop
 */
class MobileShopSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Mobile Shop')->first();
        if ($existing) {
            $this->command?->warn('Mobile Shop already exists (id='.$existing->id.'). Updating…');
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
                'first_name' => 'Mobile',
                'last_name' => 'Admin',
                'username' => 'mobileshop',
                'email' => 'mobileshop@demo.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
            ]);

            $pos_settings = array_merge($businessUtil->defaultPosSettings(), [
                'pos_ui' => 'touch',
                'touch_ask_qty' => 1,
                'hide_product_suggestion' => 0,
                'show_pricing_on_product_sugesstion' => 1,
                'enable_serial_per_qty' => 1,
            ]);

            $business = $businessUtil->createNewBusiness([
                'name' => 'Mobile Shop',
                'currency_id' => $currency_id,
                'start_date' => $now->toDateString(),
                'tax_number_1' => 'VAT-MB-55201',
                'tax_label_1' => 'VAT',
                'owner_id' => $user->id,
                'time_zone' => config('app.timezone', 'Asia/Colombo'),
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'default_sales_discount' => 0,
                'sell_price_tax' => 'includes',
                'sku_prefix' => 'MB',
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
                'enabled_modules' => ['purchases', 'add_sale', 'pos_sale', 'stock_transfers', 'stock_adjustment', 'expenses', 'account', 'mobile_shop', 'reload'],
                'date_format' => 'd-m-Y',
                'time_format' => 24,
                'currency_symbol_placement' => 'before',
                'common_settings' => [
                    'enable_product_warranty' => 1,
                    'mobile_shop' => [],
                    'reload' => [
                        'myreload_enabled' => 1,
                        'myreload_api_username' => config('myreload.api_username', 'Demo'),
                        'myreload_api_key' => config('myreload.api_key', '316bacdc0b01ab3eed57620914ab0eb7'),
                        'myreload_api_url' => config('myreload.api_url'),
                        'myreload_callback_url' => '',
                    ],
                ],
            ]);

            $user->business_id = $business->id;
            $user->save();
            $businessUtil->newBusinessDefaultResources($business->id, $user->id);

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Main Store',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '00300',
                'landmark' => 'Duplication Road, Bambalapitiya',
                'website' => 'www.mobileshop.demo',
                'mobile' => '0112556677',
                'alternate_number' => '0771234567',
                'email' => 'mobileshop@demo.local',
            ]);

            Permission::findOrCreate('location.'.$location->id, 'web');
            $user->givePermissionTo('location.'.$location->id);
            $user->givePermissionTo('access_all_locations');
        }

        $business_id = $business->id;
        $user_id = $user->id;
        $location_id = $location->id;

        // Merge modules + POS settings on every run
        $modules = is_array($business->enabled_modules)
            ? $business->enabled_modules
            : (json_decode($business->enabled_modules ?: '[]', true) ?: []);
        foreach (['mobile_shop', 'reload', 'pos_sale', 'purchases', 'account'] as $mod) {
            if (! in_array($mod, $modules)) {
                $modules[] = $mod;
            }
        }
        $business->enabled_modules = $modules;

        $settings = json_decode($business->pos_settings ?: '{}', true) ?: [];
        $settings = array_merge($businessUtil->defaultPosSettings(), $settings, [
            'pos_ui' => 'touch',
            'touch_ask_qty' => 1,
            'enable_serial_per_qty' => 1,
        ]);
        $business->pos_settings = json_encode($settings);

        $common = is_array($business->common_settings)
            ? $business->common_settings
            : (json_decode($business->common_settings ?: '{}', true) ?: []);
        $common['enable_product_warranty'] = 1;
        $common['reload'] = array_merge($common['reload'] ?? [], [
            'myreload_enabled' => 1,
            'myreload_api_username' => $common['reload']['myreload_api_username']
                ?? config('myreload.api_username', 'Demo'),
            'myreload_api_key' => $common['reload']['myreload_api_key']
                ?? config('myreload.api_key', '316bacdc0b01ab3eed57620914ab0eb7'),
            'myreload_api_url' => $common['reload']['myreload_api_url']
                ?? config('myreload.api_url'),
            'myreload_callback_url' => $common['reload']['myreload_callback_url'] ?? '',
        ]);
        $business->common_settings = $common;
        $business->enable_brand = 1;
        $business->enable_category = 1;
        $business->save();

        // Warranties
        $warrantyMap = [];
        foreach ([
            ['name' => '6 Months Shop Warranty', 'duration' => 6, 'duration_type' => 'months', 'desc' => 'Covers manufacturing defects — shop service'],
            ['name' => '1 Year Standard', 'duration' => 1, 'duration_type' => 'years', 'desc' => 'Standard manufacturer warranty period'],
            ['name' => '2 Years Extended', 'duration' => 2, 'duration_type' => 'years', 'desc' => 'Extended care plan with priority repair'],
        ] as $w) {
            $record = Warranty::firstOrCreate(
                ['business_id' => $business_id, 'name' => $w['name']],
                ['description' => $w['desc'], 'duration' => $w['duration'], 'duration_type' => $w['duration_type']]
            );
            $warrantyMap[$w['name']] = $record->id;
        }

        // Invoice layout — Classic 80mm Mobile
        $classicBase = [
            'invoice_heading' => 'TAX INVOICE',
            'invoice_no_prefix' => 'Invoice No.',
            'date_label' => 'Date',
            'customer_label' => 'Customer',
            'sub_total_label' => 'Subtotal',
            'discount_label' => 'Discount',
            'tax_label' => 'Tax',
            'total_label' => 'Grand Total',
            'total_due_label' => 'Balance Due',
            'paid_label' => 'Amount Paid',
            'change_return_label' => 'Change Return',
            'table_product_label' => 'Description',
            'table_qty_label' => 'Qty',
            'table_unit_price_label' => 'Rate',
            'table_subtotal_label' => 'Amount',
            'highlight_color' => '#1e3a5f',
            'show_logo' => 1,
            'show_business_name' => 1,
            'show_location_name' => 1,
            'show_landmark' => 1,
            'show_city' => 1,
            'show_mobile_number' => 1,
            'show_email' => 1,
            'show_customer' => 1,
            'show_payments' => 1,
            'show_barcode' => 0,
            'show_sku' => 1,
            'show_time' => 1,
            'footer_text' => '<p style="text-align:center;margin:0;">Keep this receipt for warranty. IMEI must match device. Thank you — <strong>Mobile Shop</strong>.</p>',
            'header_text' => '<p style="margin:0;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#6b7280;">Phones · Accessories · Reload · Repair</p>',
            'sub_heading_line1' => 'Duplication Road, Bambalapitiya · Colombo 03',
            'sub_heading_line2' => 'Tel: 011 255 6677 · Hotline: 077 123 4567',
        ];
        $classicCommon = [
            'show_product_description' => 1,
            'show_mrp_savings' => 1,
            'mrp_savings_label' => 'Your discount is',
            'show_warranty_name' => 1,
            'show_warranty_exp_date' => 1,
        ];

        $layout = InvoiceLayout::where('business_id', $business_id)->where('is_default', 1)->first();
        if ($layout) {
            $layout->update(array_merge($classicBase, [
                'name' => 'Classic A4 — Mobile',
                'design' => 'classic_a4',
                'show_image' => 1,
                'common_settings' => array_merge(
                    is_array($layout->common_settings) ? $layout->common_settings : (json_decode($layout->common_settings ?: '{}', true) ?: []),
                    $classicCommon
                ),
            ]));
        }

        $layout80 = InvoiceLayout::firstOrCreate(
            ['business_id' => $business_id, 'name' => 'Classic 80mm — Mobile'],
            array_merge($classicBase, [
                'design' => 'classic_80mm_mobile',
                'is_default' => 0,
                'show_image' => 0,
                'common_settings' => $classicCommon,
            ])
        );
        $layout80->update(array_merge($classicBase, [
            'design' => 'classic_80mm_mobile',
            'show_image' => 0,
            'common_settings' => array_merge(
                is_array($layout80->common_settings) ? $layout80->common_settings : (json_decode($layout80->common_settings ?: '{}', true) ?: []),
                $classicCommon
            ),
        ]));

        $location->invoice_layout_id = $layout80->id;
        $location->save();

        $unit_pc = Unit::where('business_id', $business_id)->where('short_name', 'Pc(s)')->first()
            ?: Unit::where('business_id', $business_id)->first();

        $categories = [
            ['name' => 'Smartphones', 'short_code' => 'PHN'],
            ['name' => 'Tablets', 'short_code' => 'TAB'],
            ['name' => 'Accessories', 'short_code' => 'ACC'],
            ['name' => 'Chargers & Cables', 'short_code' => 'CHG'],
            ['name' => 'Audio', 'short_code' => 'AUD'],
        ];
        $categoryMap = [];
        foreach ($categories as $cat) {
            $record = Category::firstOrCreate(
                ['name' => $cat['name'], 'business_id' => $business_id, 'category_type' => 'product'],
                ['short_code' => $cat['short_code'], 'parent_id' => 0, 'created_by' => $user_id]
            );
            $categoryMap[$cat['name']] = $record->id;
        }

        $products = [
            ['name' => 'iPhone 15 128GB', 'category' => 'Smartphones', 'sku' => 'MB-IP15-128', 'purchase' => 285000, 'sell' => 329900, 'stock' => 4, 'image' => 'mobile_iphone15.jpg', 'desc' => '6.1" · A16 · 128GB · Dual SIM', 'imei' => 1, 'warranty' => '1 Year Standard', 'imeis' => ['354789012345601', '354789012345602', '354789012345603', '354789012345604'], 'colors' => [[30, 30, 35], [60, 60, 70]]],
            ['name' => 'Samsung Galaxy S24 256GB', 'category' => 'Smartphones', 'sku' => 'MB-S24-256', 'purchase' => 248000, 'sell' => 289900, 'stock' => 4, 'image' => 'mobile_s24.jpg', 'desc' => '6.2" AMOLED · 256GB · 5G', 'imei' => 1, 'warranty' => '1 Year Standard', 'imeis' => ['351234567890101', '351234567890102', '351234567890103', '351234567890104'], 'colors' => [[20, 40, 80], [40, 60, 120]]],
            ['name' => 'Xiaomi Redmi Note 13', 'category' => 'Smartphones', 'sku' => 'MB-RN13-128', 'purchase' => 52000, 'sell' => 64900, 'stock' => 5, 'image' => 'mobile_redmi13.jpg', 'desc' => '6.67" · 128GB · 108MP camera', 'imei' => 1, 'warranty' => '6 Months Shop Warranty', 'imeis' => ['861234567890111', '861234567890112', '861234567890113', '861234567890114', '861234567890115'], 'colors' => [[180, 40, 40], [40, 40, 40]]],
            ['name' => 'OPPO A78 5G', 'category' => 'Smartphones', 'sku' => 'MB-OPA78', 'purchase' => 68000, 'sell' => 79900, 'stock' => 3, 'image' => 'mobile_oppo_a78.jpg', 'desc' => '6.56" · 8GB/128GB · 50MP', 'imei' => 1, 'warranty' => '6 Months Shop Warranty', 'imeis' => ['861555666777001', '861555666777002', '861555666777003'], 'colors' => [[50, 120, 90], [30, 30, 30]]],
            ['name' => 'vivo Y27 128GB', 'category' => 'Smartphones', 'sku' => 'MB-VY27', 'purchase' => 58000, 'sell' => 69900, 'stock' => 3, 'image' => 'mobile_vivo_y27.jpg', 'desc' => '6.64" · 128GB · 50MP selfie', 'imei' => 1, 'warranty' => '6 Months Shop Warranty', 'imeis' => ['861777888999001', '861777888999002', '861777888999003'], 'colors' => [[100, 80, 160], [220, 220, 225]]],
            ['name' => 'Samsung Galaxy Tab A9', 'category' => 'Tablets', 'sku' => 'MB-TABA9', 'purchase' => 72000, 'sell' => 84900, 'stock' => 2, 'image' => 'mobile_tab_a9.jpg', 'desc' => '8.7" · WiFi · 64GB', 'imei' => 1, 'warranty' => '1 Year Standard', 'imeis' => ['359012345678901', '359012345678902'], 'colors' => [[50, 50, 55], [200, 200, 210]]],
            ['name' => 'USB-C Fast Charger 25W', 'category' => 'Chargers & Cables', 'sku' => 'MB-CHG-25W', 'purchase' => 2800, 'sell' => 4500, 'stock' => 40, 'image' => 'mobile_charger_25w.jpg', 'desc' => '25W PD · USB-C · with cable', 'imei' => 0, 'colors' => [[240, 240, 245], [200, 200, 210]]],
            ['name' => 'TPU Phone Case Universal', 'category' => 'Accessories', 'sku' => 'MB-CSE-TPU', 'purchase' => 450, 'sell' => 990, 'stock' => 80, 'image' => 'mobile_case_tpu.jpg', 'desc' => 'Clear TPU · shock absorb', 'imei' => 0, 'colors' => [[230, 235, 240], [180, 190, 200]]],
            ['name' => 'Tempered Glass Screen Guard', 'category' => 'Accessories', 'sku' => 'MB-SCR-GLS', 'purchase' => 350, 'sell' => 790, 'stock' => 100, 'image' => 'mobile_screen_guard.jpg', 'desc' => '9H · oleophobic · full glue', 'imei' => 0, 'colors' => [[210, 220, 230], [180, 190, 200]]],
            ['name' => 'TWS Earbuds Pro', 'category' => 'Audio', 'sku' => 'MB-EAR-TWS', 'purchase' => 4500, 'sell' => 6990, 'stock' => 25, 'image' => 'mobile_earbuds.jpg', 'desc' => 'BT 5.3 · ENC · 24hr case', 'imei' => 0, 'colors' => [[30, 30, 35], [60, 60, 70]]],
            ['name' => '10000mAh Power Bank', 'category' => 'Chargers & Cables', 'sku' => 'MB-PBK-10K', 'purchase' => 3800, 'sell' => 5490, 'stock' => 30, 'image' => 'mobile_powerbank.jpg', 'desc' => '10000mAh · 22.5W · dual USB', 'imei' => 0, 'colors' => [[40, 40, 45], [80, 80, 90]]],
            ['name' => 'Lightning Cable 1m MFi', 'category' => 'Chargers & Cables', 'sku' => 'MB-CBL-LGT', 'purchase' => 1200, 'sell' => 1990, 'stock' => 50, 'image' => 'mobile_lightning.jpg', 'desc' => 'MFi certified · braided 1m', 'imei' => 0, 'colors' => [[240, 240, 245], [200, 200, 210]]],
            ['name' => 'Car Phone Holder Magnetic', 'category' => 'Accessories', 'sku' => 'MB-HLD-CAR', 'purchase' => 1800, 'sell' => 2990, 'stock' => 20, 'image' => 'mobile_car_holder.jpg', 'desc' => 'Magnetic mount · vent clip', 'imei' => 0, 'colors' => [[50, 50, 55], [120, 120, 130]]],
        ];

        $imgDir = public_path('uploads/img');
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $image = $item['image'];
            $imagePath = $imgDir.DIRECTORY_SEPARATOR.$image;
            if (! is_file($imagePath) || $this->isPlaceholderProductImage($imagePath)) {
                // Keep real photos if present; only build GD stub when missing
                if (! is_file($imagePath)) {
                    $this->generatePhoneImage($imagePath, $item['name'], $item['colors'], ! empty($item['imei']));
                }
            }
            if (! is_file($imagePath)) {
                $image = null;
            }

            $warranty_id = ! empty($item['warranty']) ? ($warrantyMap[$item['warranty']] ?? null) : null;

            $product = Product::where('business_id', $business_id)->where('sku', $item['sku'])->first();
            if ($product) {
                $product->update([
                    'name' => $item['name'],
                    'category_id' => $categoryMap[$item['category']] ?? null,
                    'unit_id' => $unit_pc->id,
                    'image' => $image,
                    'product_description' => $item['desc'],
                    'enable_sr_no' => ! empty($item['imei']) ? 1 : 0,
                    'warranty_id' => $warranty_id,
                    'enable_stock' => 1,
                    'not_for_selling' => 0,
                    'is_inactive' => 0,
                ]);
                $product->product_locations()->sync([$location_id]);
                $variation = $product->variations()->first();
                $updated++;
            } else {
                $product = Product::create([
                    'name' => $item['name'],
                    'business_id' => $business_id,
                    'type' => 'single',
                    'unit_id' => $unit_pc->id,
                    'category_id' => $categoryMap[$item['category']] ?? null,
                    'tax_type' => 'inclusive',
                    'enable_stock' => 1,
                    'alert_quantity' => 2,
                    'sku' => $item['sku'],
                    'barcode_type' => 'C128',
                    'image' => $image,
                    'product_description' => $item['desc'],
                    'enable_sr_no' => ! empty($item['imei']) ? 1 : 0,
                    'warranty_id' => $warranty_id,
                    'created_by' => $user_id,
                    'not_for_selling' => 0,
                    'is_inactive' => 0,
                ]);

                $productUtil->createSingleProductVariation(
                    $product->id,
                    $product->sku,
                    $item['purchase'],
                    $item['purchase'],
                    0,
                    $item['sell'],
                    $item['sell']
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

                $total = $item['purchase'] * $item['stock'];
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
                    'purchase_price' => $item['purchase'],
                    'purchase_price_inc_tax' => $item['purchase'],
                    'pp_without_discount' => $item['purchase'],
                    'item_tax' => 0,
                ]);

                $created++;
            }

            // Seed IMEI inventory for phones
            if (! empty($item['imeis']) && ! empty($item['imei'])) {
                $variation = $product->variations()->first();
                foreach ($item['imeis'] as $imei) {
                    MobileImei::firstOrCreate(
                        ['business_id' => $business_id, 'imei' => $imei],
                        [
                            'product_id' => $product->id,
                            'variation_id' => $variation->id,
                            'location_id' => $location_id,
                            'status' => 'available',
                            'created_by' => $user_id,
                        ]
                    );
                }
            }
        }

        if (! CashRegister::where('user_id', $user_id)->where('status', 'open')->exists()) {
            CashRegister::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'user_id' => $user_id,
                'status' => 'open',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $mobileShopUtil = new \App\Utils\MobileShopUtil();
        $mobileShopUtil->getOrCreateRepairServiceProduct($business_id, $user_id, $location_id);
        $repairImg = 'mobile_repair_svc.jpg';
        if (is_file($imgDir.DIRECTORY_SEPARATOR.$repairImg)) {
            Product::where('business_id', $business_id)->where('sku', 'MB-REPAIR-SVC')->update(['image' => $repairImg]);
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('business', 'shop_mode')) {
            \App\Utils\ShopModeUtil::applyToBusiness($business->fresh(), 'mobile_shop');
            \App\Utils\ShopModeUtil::forgetCache($business_id);
        }

        $this->command?->info('Mobile Shop demo ready.');
        $this->command?->info('Business ID: '.$business_id.' | Location ID: '.$location_id);
        $this->command?->info('Login: mobileshop / 123456  (or mobileshop@demo.local)');
        $this->command?->info('Products created: '.$created.', updated: '.$updated);
        $this->command?->info('Modules: mobile_shop enabled · IMEI inventory seeded · Classic 80mm Mobile receipt');
    }

    /**
     * Detect old GD silhouette placeholders so we never treat them as real photos.
     */
    protected function isPlaceholderProductImage(string $path): bool
    {
        if (! is_file($path)) {
            return true;
        }
        $size = filesize($path);
        if ($size !== false && $size > 0 && $size < 18000) {
            $head = @file_get_contents($path, false, null, 0, 256) ?: '';
            if (stripos($head, 'gd-jpeg') !== false || stripos($head, 'CREATOR: gd-jpeg') !== false) {
                return true;
            }
        }

        return false;
    }

    protected function generatePhoneImage(string $path, string $label, array $colors, bool $is_phone = true): void
    {
        // Never overwrite a real product photo
        if (is_file($path) && ! $this->isPlaceholderProductImage($path)) {
            return;
        }

        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $w = 480;
        $h = 480;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 235, 238, 242);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        $body = imagecolorallocate($img, $colors[0][0] ?? 40, $colors[0][1] ?? 45, $colors[0][2] ?? 55);
        $screen = imagecolorallocate($img, $colors[1][0] ?? 20, $colors[1][1] ?? 25, $colors[1][2] ?? 35);
        $accent = imagecolorallocate($img, 30, 58, 95);

        if ($is_phone) {
            imagefilledrectangle($img, 160, 60, 320, 400, $body);
            imagerectangle($img, 160, 60, 320, 400, $accent);
            imagefilledrectangle($img, 175, 90, 305, 340, $screen);
            imagefilledellipse($img, 240, 370, 24, 24, $screen);
        } else {
            imagefilledrectangle($img, 100, 140, 380, 320, $body);
            imagerectangle($img, 100, 140, 380, 320, $accent);
            imagefilledellipse($img, 240, 230, 120, 120, $screen);
        }

        imagefilledrectangle($img, 0, $h - 64, $w, $h, $accent);
        $short = mb_strlen($label) > 34 ? mb_substr($label, 0, 32).'…' : $label;
        imagestring($img, 5, 16, $h - 42, $short, imagecolorallocate($img, 255, 255, 255));
        imagestring($img, 2, 16, $h - 22, 'Mobile Shop', imagecolorallocate($img, 180, 190, 200));

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Mobile',
            'last_name' => 'Admin',
            'username' => 'mobileshop',
            'email' => 'mobileshop@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
