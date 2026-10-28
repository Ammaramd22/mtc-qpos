<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Currency;
use App\InvoiceLayout;
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
 * Demo business: Tiles & Ceramics — floor/wall tiles with photos + Classic A4 invoice.
 *
 * Run: php artisan db:seed --class=TilesCeramicsSeeder
 *   or: php artisan pos:seed-tiles-ceramics
 */
class TilesCeramicsSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();

        $existing = Business::where('name', 'Tiles & Ceramics')->first();
        if ($existing) {
            $this->command?->warn('Tiles & Ceramics already exists (id='.$existing->id.'). Updating products / layout…');
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
                'first_name' => 'Tiles',
                'last_name' => 'Admin',
                'username' => 'tilesshop',
                'email' => 'tiles@demo.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
            ]);

            $pos_settings = array_merge($businessUtil->defaultPosSettings(), [
                'pos_ui' => 'touch',
                'touch_ask_qty' => 1,
                'hide_product_suggestion' => 0,
                'show_pricing_on_product_sugesstion' => 1,
                'enable_pop' => 0,
            ]);

            $business = $businessUtil->createNewBusiness([
                'name' => 'Tiles & Ceramics',
                'currency_id' => $currency_id,
                'start_date' => $now->toDateString(),
                'tax_number_1' => 'VAT-TL-88421',
                'tax_label_1' => 'VAT',
                'owner_id' => $user->id,
                'time_zone' => config('app.timezone', 'Asia/Colombo'),
                'fy_start_month' => 1,
                'accounting_method' => 'fifo',
                'default_sales_discount' => 0,
                'sell_price_tax' => 'includes',
                'sku_prefix' => 'TL',
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
            $business->enable_brand = 1;
            $business->save();

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Showroom & Warehouse',
                'country' => 'Sri Lanka',
                'state' => 'Western',
                'city' => 'Colombo',
                'zip_code' => '01000',
                'landmark' => 'Baseline Road, Dematagoda',
                'website' => 'www.tilesceraics.demo',
                'mobile' => '0112345678',
                'alternate_number' => '0779887766',
                'email' => 'tiles@demo.local',
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

        // Shared classic invoice settings (A4 default + 80mm thermal twin)
        $classicCommon = [
            'show_product_description' => 1,
            'show_mrp_savings' => 1,
            'mrp_savings_label' => 'Your discount is',
        ];
        $classicBase = [
            'invoice_heading' => 'TAX INVOICE',
            'invoice_no_prefix' => 'Invoice No.',
            'date_label' => 'Date',
            'customer_label' => 'Bill To',
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
            'highlight_color' => '#1a3a4a',
            'show_logo' => 1,
            'show_business_name' => 1,
            'show_location_name' => 1,
            'show_landmark' => 1,
            'show_city' => 1,
            'show_state' => 1,
            'show_zip_code' => 1,
            'show_country' => 1,
            'show_mobile_number' => 1,
            'show_email' => 1,
            'show_customer' => 1,
            'show_payments' => 1,
            'show_barcode' => 0,
            'show_sku' => 1,
            'show_brand' => 0,
            'show_time' => 1,
            'show_sale_description' => 0,
            'footer_text' => '<p style="text-align:center;margin:0;">Tiles must be checked before fixing. Shade variation is natural. Thank you for choosing <strong>Tiles &amp; Ceramics</strong>.</p>',
            'header_text' => '<p style="margin:0;font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:#6b7280;">Premium Floor, Wall &amp; Bath Solutions</p>',
            'sub_heading_line1' => 'Showroom & Warehouse · Baseline Road, Dematagoda',
            'sub_heading_line2' => 'Tel: 011 234 5678 · Hotline: 077 988 7766',
        ];

        // Classic A4 (default)
        $layout = InvoiceLayout::where('business_id', $business_id)->where('is_default', 1)->first();
        if ($layout) {
            $layout->update(array_merge($classicBase, [
                'name' => 'Classic A4 — Tiles',
                'design' => 'classic_a4',
                'show_image' => 1,
                'common_settings' => array_merge(
                    is_array($layout->common_settings) ? $layout->common_settings : (json_decode($layout->common_settings ?: '{}', true) ?: []),
                    $classicCommon
                ),
            ]));
        }

        // Classic 80mm thermal twin (same branding; switch location printer layout to use it)
        $layout80 = InvoiceLayout::firstOrCreate(
            ['business_id' => $business_id, 'name' => 'Classic 80mm — Tiles'],
            array_merge($classicBase, [
                'design' => 'classic_80mm',
                'is_default' => 0,
                'show_image' => 0,
                'common_settings' => $classicCommon,
            ])
        );
        if (! $layout80->wasRecentlyCreated) {
            $layout80->update(array_merge($classicBase, [
                'design' => 'classic_80mm',
                'show_image' => 0,
                'common_settings' => array_merge(
                    is_array($layout80->common_settings) ? $layout80->common_settings : (json_decode($layout80->common_settings ?: '{}', true) ?: []),
                    $classicCommon
                ),
            ]));
        }

        // POS thermal default → Classic 80mm (A4 layout kept for letter printer)
        $location->invoice_layout_id = $layout80->id;
        $location->save();

        // Units: Box (floor tiles), Sq.Ft, Piece (accessories)
        $unit_box = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Box'],
            ['short_name' => 'Box', 'allow_decimal' => 0, 'created_by' => $user_id]
        );
        $unit_sqft = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Square Foot'],
            ['short_name' => 'Sq.Ft', 'allow_decimal' => 1, 'created_by' => $user_id]
        );
        $unit_pc = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Piece'],
            ['short_name' => 'Pc', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryDefs = [
            ['name' => 'Floor Tiles', 'short_code' => 'FLR'],
            ['name' => 'Wall Tiles', 'short_code' => 'WAL'],
            ['name' => 'Bathroom Tiles', 'short_code' => 'BTH'],
            ['name' => 'Outdoor / Porcelain', 'short_code' => 'OUT'],
            ['name' => 'Mosaic & Décor', 'short_code' => 'MOS'],
            ['name' => 'Grout & Accessories', 'short_code' => 'ACC'],
            ['name' => 'Bathware', 'short_code' => 'BWR'],
            ['name' => 'Toilet & Sanitary', 'short_code' => 'SAN'],
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

        // Colors for generated tile photos (RGB)
        $products = [
            ['name' => 'Carrara White Marble Look 60×60', 'category' => 'Floor Tiles', 'sku' => 'TL-CAR-6060', 'purchase' => 3200, 'sell' => 4500, 'stock' => 80, 'image' => 'tile_carrara_white.jpg', 'unit' => 'box', 'desc' => '60×60 cm · Glossy porcelain · 4 pcs/box (~1.44 m²)', 'colors' => [[232, 230, 225], [210, 208, 202]]],
            ['name' => 'Oak Wood Plank 20×120', 'category' => 'Floor Tiles', 'sku' => 'TL-OAK-20120', 'purchase' => 2800, 'sell' => 3950, 'stock' => 65, 'image' => 'tile_oak_plank.jpg', 'unit' => 'box', 'desc' => '20×120 cm · Matt wood-look · 5 pcs/box', 'colors' => [[166, 124, 82], [140, 98, 62]]],
            ['name' => 'Graphite Concrete 60×60', 'category' => 'Floor Tiles', 'sku' => 'TL-GRF-6060', 'purchase' => 2600, 'sell' => 3650, 'stock' => 90, 'image' => 'tile_graphite.jpg', 'unit' => 'box', 'desc' => '60×60 cm · Matt concrete · 4 pcs/box', 'colors' => [[90, 92, 96], [70, 72, 76]]],
            ['name' => 'Beige Travertine 30×60', 'category' => 'Wall Tiles', 'sku' => 'TL-TRV-3060', 'purchase' => 1450, 'sell' => 2100, 'stock' => 120, 'image' => 'tile_travertine.jpg', 'unit' => 'box', 'desc' => '30×60 cm · Soft beige stone · 8 pcs/box', 'colors' => [[214, 196, 168], [198, 178, 148]]],
            ['name' => 'Subway Gloss White 10×20', 'category' => 'Wall Tiles', 'sku' => 'TL-SUB-WHT', 'purchase' => 980, 'sell' => 1450, 'stock' => 150, 'image' => 'tile_subway_white.jpg', 'unit' => 'box', 'desc' => '10×20 cm · Classic subway · 25 pcs/box', 'colors' => [[248, 248, 248], [230, 230, 230]]],
            ['name' => 'Navy Metro Gloss 10×30', 'category' => 'Wall Tiles', 'sku' => 'TL-NAV-1030', 'purchase' => 1100, 'sell' => 1650, 'stock' => 100, 'image' => 'tile_navy_metro.jpg', 'unit' => 'box', 'desc' => '10×30 cm · Deep navy gloss', 'colors' => [[28, 55, 95], [18, 40, 75]]],
            ['name' => 'Hexagon Honeycomb Grey', 'category' => 'Bathroom Tiles', 'sku' => 'TL-HEX-GRY', 'purchase' => 3800, 'sell' => 5200, 'stock' => 40, 'image' => 'tile_hex_grey.jpg', 'unit' => 'box', 'desc' => 'Hex mosaic sheet · Anti-slip · Bathroom floor', 'colors' => [[160, 164, 168], [130, 134, 138]]],
            ['name' => 'Spa Stone Beige 30×30', 'category' => 'Bathroom Tiles', 'sku' => 'TL-SPA-3030', 'purchase' => 1650, 'sell' => 2350, 'stock' => 70, 'image' => 'tile_spa_beige.jpg', 'unit' => 'box', 'desc' => '30×30 cm · Textured spa stone', 'colors' => [[205, 190, 165], [185, 168, 142]]],
            ['name' => 'Outdoor Riven Grey 60×60', 'category' => 'Outdoor / Porcelain', 'sku' => 'TL-OUT-6060', 'purchase' => 4100, 'sell' => 5750, 'stock' => 55, 'image' => 'tile_outdoor_riven.jpg', 'unit' => 'box', 'desc' => '60×60 cm · R11 anti-slip outdoor porcelain', 'colors' => [[120, 122, 118], [95, 97, 93]]],
            ['name' => 'Pool Edge Coping Ivory', 'category' => 'Outdoor / Porcelain', 'sku' => 'TL-POOL-IVY', 'purchase' => 890, 'sell' => 1280, 'stock' => 200, 'image' => 'tile_pool_coping.jpg', 'unit' => 'pc', 'desc' => 'Bullnose coping · Ivory · Piece', 'colors' => [[235, 228, 210], [220, 210, 190]]],
            ['name' => 'Glass Mosaic Ocean Mix', 'category' => 'Mosaic & Décor', 'sku' => 'TL-MOS-OCN', 'purchase' => 2200, 'sell' => 3100, 'stock' => 45, 'image' => 'tile_mosaic_ocean.jpg', 'unit' => 'box', 'desc' => '300×300 mm sheet · Glass mosaic · Accent wall', 'colors' => [[40, 120, 160], [70, 170, 190], [200, 230, 240]]],
            ['name' => 'Gold Décor Border 5×60', 'category' => 'Mosaic & Décor', 'sku' => 'TL-DEC-GLD', 'purchase' => 650, 'sell' => 950, 'stock' => 180, 'image' => 'tile_decor_gold.jpg', 'unit' => 'pc', 'desc' => '5×60 cm · Metallic gold listello', 'colors' => [[196, 160, 80], [170, 130, 50]]],
            ['name' => 'Grout Grey 5kg', 'category' => 'Grout & Accessories', 'sku' => 'TL-GRT-GRY', 'purchase' => 850, 'sell' => 1250, 'stock' => 100, 'image' => 'tile_grout_grey.jpg', 'unit' => 'pc', 'desc' => 'Cementitious grout · Grey · 5 kg bag', 'colors' => [[140, 140, 140], [110, 110, 110]]],
            ['name' => 'Tile Spacer 2mm (Bag 500)', 'category' => 'Grout & Accessories', 'sku' => 'TL-SPC-2MM', 'purchase' => 320, 'sell' => 480, 'stock' => 250, 'image' => 'tile_spacer.jpg', 'unit' => 'pc', 'desc' => '2 mm plastic spacers · Pack of 500', 'colors' => [[245, 245, 245], [220, 220, 220]]],
            ['name' => 'Tile Adhesive Flex 25kg', 'category' => 'Grout & Accessories', 'sku' => 'TL-ADH-25', 'purchase' => 2100, 'sell' => 2950, 'stock' => 80, 'image' => 'tile_adhesive.jpg', 'unit' => 'pc', 'desc' => 'Flexible tile adhesive · 25 kg bag', 'colors' => [[180, 160, 130], [150, 130, 100]], 'style' => 'tile'],
            // Bathware & sanitary
            ['name' => 'Close Coupled Toilet White', 'category' => 'Toilet & Sanitary', 'sku' => 'TL-WC-CCW', 'purchase' => 18500, 'sell' => 26500, 'stock' => 18, 'image' => 'bath_wc_close.jpg', 'unit' => 'pc', 'desc' => 'S-trap close coupled WC · Dual flush · Ceramic white', 'colors' => [[248, 248, 250], [230, 230, 235]], 'style' => 'toilet'],
            ['name' => 'Wall Hung WC Pan Rimless', 'category' => 'Toilet & Sanitary', 'sku' => 'TL-WC-WHG', 'purchase' => 14200, 'sell' => 19800, 'stock' => 22, 'image' => 'bath_wc_wall.jpg', 'unit' => 'pc', 'desc' => 'Rimless wall-hung pan · Concealed fixings (frame sold separately)', 'colors' => [[252, 252, 254], [235, 235, 240]], 'style' => 'toilet'],
            ['name' => 'Soft Close WC Seat', 'category' => 'Toilet & Sanitary', 'sku' => 'TL-WC-SEAT', 'purchase' => 3200, 'sell' => 4800, 'stock' => 45, 'image' => 'bath_wc_seat.jpg', 'unit' => 'pc', 'desc' => 'Universal soft-close seat & cover · Quick release hinges', 'colors' => [[245, 245, 248], [220, 220, 225]], 'style' => 'toilet'],
            ['name' => 'One Piece Toilet Premium', 'category' => 'Toilet & Sanitary', 'sku' => 'TL-WC-1PC', 'purchase' => 22800, 'sell' => 31900, 'stock' => 12, 'image' => 'bath_wc_onepiece.jpg', 'unit' => 'pc', 'desc' => 'One-piece WC · Washdown · 3/6 L dual flush', 'colors' => [[250, 250, 252], [232, 232, 238]], 'style' => 'toilet'],
            ['name' => 'Countertop Wash Basin 60cm', 'category' => 'Bathware', 'sku' => 'TL-BSN-CT60', 'purchase' => 6800, 'sell' => 9500, 'stock' => 28, 'image' => 'bath_basin_counter.jpg', 'unit' => 'pc', 'desc' => '60 cm countertop basin · Gloss white ceramic · No tap hole', 'colors' => [[255, 255, 255], [240, 240, 245]], 'style' => 'bathware'],
            ['name' => 'Pedestal Basin Classic', 'category' => 'Bathware', 'sku' => 'TL-BSN-PED', 'purchase' => 9200, 'sell' => 12800, 'stock' => 20, 'image' => 'bath_basin_pedestal.jpg', 'unit' => 'pc', 'desc' => 'Full pedestal basin set · 1 tap hole · Classic oval', 'colors' => [[252, 252, 255], [235, 235, 240]], 'style' => 'bathware'],
            ['name' => 'Chrome Basin Mixer Tap', 'category' => 'Bathware', 'sku' => 'TL-TAP-BSN', 'purchase' => 4500, 'sell' => 6500, 'stock' => 35, 'image' => 'bath_tap_basin.jpg', 'unit' => 'pc', 'desc' => 'Single-lever basin mixer · Chrome finish · Ceramic cartridge', 'colors' => [[200, 205, 210], [160, 165, 175]], 'style' => 'tap'],
            ['name' => 'Rain Shower Head Set 200mm', 'category' => 'Bathware', 'sku' => 'TL-SHW-RAIN', 'purchase' => 7800, 'sell' => 10900, 'stock' => 25, 'image' => 'bath_shower_rain.jpg', 'unit' => 'pc', 'desc' => '200 mm square rain shower · Arm & flange included · SS/chrome', 'colors' => [[190, 195, 200], [140, 145, 155]], 'style' => 'tap'],
            ['name' => 'Shower Mixer Wall Set', 'category' => 'Bathware', 'sku' => 'TL-SHW-MIX', 'purchase' => 11200, 'sell' => 15600, 'stock' => 18, 'image' => 'bath_shower_mixer.jpg', 'unit' => 'pc', 'desc' => 'Exposed shower mixer · Hand shower + overhead · Chrome', 'colors' => [[185, 190, 195], [150, 155, 165]], 'style' => 'tap'],
            ['name' => 'Bathroom Mirror Cabinet 60cm', 'category' => 'Bathware', 'sku' => 'TL-MIR-60', 'purchase' => 8900, 'sell' => 12400, 'stock' => 15, 'image' => 'bath_mirror_cab.jpg', 'unit' => 'pc', 'desc' => '60 cm aluminium mirror cabinet · 2 doors · LED optional', 'colors' => [[220, 225, 230], [180, 185, 195]], 'style' => 'bathware'],
            ['name' => 'Stainless Towel Rail 60cm', 'category' => 'Bathware', 'sku' => 'TL-ACC-TWL', 'purchase' => 4200, 'sell' => 5900, 'stock' => 30, 'image' => 'bath_towel_rail.jpg', 'unit' => 'pc', 'desc' => '304 SS heated towel rail · 60 cm · Wall mount', 'colors' => [[195, 200, 205], [165, 170, 180]], 'style' => 'tap'],
            ['name' => 'Health Faucet / Bidet Spray', 'category' => 'Bathware', 'sku' => 'TL-ACC-HF', 'purchase' => 1800, 'sell' => 2650, 'stock' => 55, 'image' => 'bath_health_faucet.jpg', 'unit' => 'pc', 'desc' => 'Wall health faucet · 1.2 m hose · Chrome ABS trigger', 'colors' => [[200, 205, 210], [170, 175, 185]], 'style' => 'tap'],
        ];

        $imgDir = public_path('uploads/img');
        if (! is_dir($imgDir)) {
            mkdir($imgDir, 0755, true);
        }

        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $unit_id = match ($item['unit']) {
                'sqft' => $unit_sqft->id,
                'pc' => $unit_pc->id,
                default => $unit_box->id,
            };

            $image = $item['image'];
            $imagePath = $imgDir.DIRECTORY_SEPARATOR.$image;
            if (! is_file($imagePath)) {
                $style = $item['style'] ?? 'tile';
                if ($style === 'tile') {
                    $this->generateTileImage($imagePath, $item['name'], $item['colors']);
                } else {
                    $this->generateBathwareImage($imagePath, $item['name'], $item['colors'], $style);
                }
            }
            if (! is_file($imagePath)) {
                $image = null;
            }

            $product = Product::where('business_id', $business_id)->where('sku', $item['sku'])->first();
            if ($product) {
                $product->update([
                    'name' => $item['name'],
                    'category_id' => $categoryMap[$item['category']] ?? null,
                    'unit_id' => $unit_id,
                    'image' => $image,
                    'product_description' => $item['desc'],
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
                'product_description' => $item['desc'],
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

        $this->command?->info('Tiles & Ceramics demo ready.');
        $this->command?->info("Business ID: {$business_id} | Location ID: {$location_id}");
        $this->command?->info('Login: tilesshop / 123456  (or tiles@demo.local)');
        $this->command?->info("Products created: {$created}, updated: {$updated}");
        $this->command?->info('Invoice layouts: Classic A4 (default) + Classic 80mm — open /pos/create after login');
    }

    /**
     * Generate a simple tile-pattern product photo with GD.
     */
    protected function generateTileImage(string $path, string $label, array $colors): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $w = 480;
        $h = 480;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 245, 245, 245);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        $palette = [];
        foreach ($colors as $c) {
            $palette[] = imagecolorallocate($img, $c[0], $c[1], $c[2]);
        }
        $grout = imagecolorallocate($img, 210, 210, 210);
        $ink = imagecolorallocate($img, 40, 40, 40);
        $muted = imagecolorallocate($img, 90, 90, 90);

        // Tile grid
        $cols = count($palette) >= 3 ? 4 : 3;
        $rows = $cols;
        $pad = 18;
        $gap = 6;
        $cellW = (int) (($w - 2 * $pad - ($cols - 1) * $gap) / $cols);
        $cellH = (int) (($h - 2 * $pad - 70 - ($rows - 1) * $gap) / $rows);

        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                $x1 = $pad + $c * ($cellW + $gap);
                $y1 = $pad + $r * ($cellH + $gap);
                $x2 = $x1 + $cellW;
                $y2 = $y1 + $cellH;
                $color = $palette[($r + $c) % count($palette)];
                imagefilledrectangle($img, $x1, $y1, $x2, $y2, $color);
                imagerectangle($img, $x1, $y1, $x2, $y2, $grout);

                // subtle diagonal vein
                $vein = imagecolorallocatealpha($img, 255, 255, 255, 100);
                imageline($img, $x1 + 8, $y1 + 12, $x2 - 10, $y2 - 14, $vein);
            }
        }

        // Label bar
        imagefilledrectangle($img, 0, $h - 64, $w, $h, imagecolorallocate($img, 26, 58, 74));
        $short = mb_strlen($label) > 36 ? mb_substr($label, 0, 34).'…' : $label;
        imagestring($img, 5, 16, $h - 42, $short, imagecolorallocate($img, 255, 255, 255));
        imagestring($img, 2, 16, $h - 22, 'Tiles & Ceramics', $muted);

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    /**
     * Generate bathware / sanitary product photo (white ceramic, chrome accents).
     */
    protected function generateBathwareImage(string $path, string $label, array $colors, string $style = 'bathware'): void
    {
        if (! function_exists('imagecreatetruecolor')) {
            return;
        }

        $w = 480;
        $h = 480;
        $img = imagecreatetruecolor($w, $h);
        $bg = imagecolorallocate($img, 240, 242, 245);
        imagefilledrectangle($img, 0, 0, $w, $h, $bg);

        $white = imagecolorallocate($img, 252, 252, 255);
        $whiteShadow = imagecolorallocate($img, 225, 228, 232);
        $chrome = imagecolorallocate($img, $colors[0][0] ?? 190, $colors[0][1] ?? 195, $colors[0][2] ?? 200);
        $chromeDark = imagecolorallocate($img, $colors[1][0] ?? 150, $colors[1][1] ?? 155, $colors[1][2] ?? 165);
        $accent = imagecolorallocate($img, 26, 58, 74);
        $muted = imagecolorallocate($img, 100, 110, 120);

        $cx = (int) ($w / 2);

        if ($style === 'toilet') {
            // Bowl + tank silhouette
            imagefilledellipse($img, $cx, 300, 220, 140, $whiteShadow);
            imagefilledellipse($img, $cx, 295, 210, 130, $white);
            imagerectangle($img, $cx - 70, 120, $cx + 70, 220, $whiteShadow);
            imagefilledrectangle($img, $cx - 68, 122, $cx + 68, 218, $white);
            imagerectangle($img, $cx - 68, 122, $cx + 68, 218, $chromeDark);
            // Seat line
            imageline($img, $cx - 95, 250, $cx + 95, 250, $chrome);
        } elseif ($style === 'tap') {
            // Chrome faucet / shower arm
            imagefilledrectangle($img, $cx - 8, 100, $cx + 8, 280, $chrome);
            imagefilledellipse($img, $cx, 95, 36, 36, $chromeDark);
            imagefilledellipse($img, $cx, 290, 80, 40, $chrome);
            if (str_contains(strtolower($label), 'shower') || str_contains(strtolower($label), 'rain')) {
                imagefilledrectangle($img, $cx - 90, 140, $cx + 90, 200, $chromeDark);
                imagefilledrectangle($img, $cx - 85, 145, $cx + 85, 195, $chrome);
            } else {
                imageline($img, $cx - 60, 180, $cx + 60, 180, $chromeDark);
                imagefilledellipse($img, $cx + 55, 180, 24, 24, $chrome);
            }
        } else {
            // Basin / cabinet — oval bowl or rectangle mirror
            if (str_contains(strtolower($label), 'mirror') || str_contains(strtolower($label), 'cabinet')) {
                imagefilledrectangle($img, 80, 100, 400, 340, $whiteShadow);
                imagefilledrectangle($img, 90, 110, 390, 330, $white);
                imagerectangle($img, 90, 110, 390, 330, $chromeDark);
                imagefilledrectangle($img, 110, 130, 370, 280, imagecolorallocate($img, 210, 215, 220));
            } elseif (str_contains(strtolower($label), 'towel') || str_contains(strtolower($label), 'rail')) {
                imageline($img, 100, 200, 380, 200, $chromeDark);
                imageline($img, 100, 200, 100, 320, $chrome);
                imageline($img, 380, 200, 380, 320, $chrome);
                for ($i = 0; $i < 5; $i++) {
                    $x = 130 + $i * 55;
                    imageline($img, $x, 205, $x, 315, $chrome);
                }
            } else {
                // Wash basin oval
                imagefilledellipse($img, $cx, 260, 280, 120, $whiteShadow);
                imagefilledellipse($img, $cx, 255, 260, 100, $white);
                imageellipse($img, $cx, 255, 260, 100, $chromeDark);
                if (str_contains(strtolower($label), 'pedestal')) {
                    imagefilledrectangle($img, $cx - 35, 300, $cx + 35, 380, $white);
                    imagerectangle($img, $cx - 35, 300, $cx + 35, 380, $chromeDark);
                }
            }
        }

        // Label bar
        imagefilledrectangle($img, 0, $h - 64, $w, $h, $accent);
        $short = mb_strlen($label) > 36 ? mb_substr($label, 0, 34).'…' : $label;
        imagestring($img, 5, 16, $h - 42, $short, imagecolorallocate($img, 255, 255, 255));
        imagestring($img, 2, 16, $h - 22, 'Bathware & Sanitary', $muted);

        imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    protected function createOwner(int $business_id): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Tiles',
            'last_name' => 'Admin',
            'username' => 'tilesshop',
            'email' => 'tiles@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }
}
