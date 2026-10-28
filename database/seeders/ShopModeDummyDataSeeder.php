<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Contact;
use App\Currency;
use App\CustomerVehicle;
use App\MobileImei;
use App\Product;
use App\PurchaseLine;
use App\Transaction;
use App\Unit;
use App\User;
use App\Utils\BusinessUtil;
use App\Utils\ProductUtil;
use App\Utils\ShopModeUtil;
use App\VariationLocationDetails;
use App\WorkshopJob;
use App\WorkshopJobItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

/**
 * Seeds 10 dummy products (and mode-specific extras) for every Shop Mode.
 *
 * Run: php artisan pos:seed-shop-modes
 *      php artisan pos:seed-shop-modes tyre_shop
 */
class ShopModeDummyDataSeeder extends Seeder
{
    protected BusinessUtil $businessUtil;

    protected ProductUtil $productUtil;

    protected Carbon $now;

    public function run(?string $onlyMode = null): void
    {
        $this->businessUtil = new BusinessUtil();
        $this->productUtil = new ProductUtil();
        $this->now = Carbon::now();

        if (! Schema::hasColumn('business', 'shop_mode')) {
            $this->command?->error('business.shop_mode missing. Run database/cpanel_shop_mode.sql first.');

            return;
        }

        $catalog = $this->catalog();
        if ($onlyMode) {
            if (! isset($catalog[$onlyMode])) {
                $this->command?->error("Unknown mode: {$onlyMode}. Valid: ".implode(', ', array_keys($catalog)));

                return;
            }
            $catalog = [$onlyMode => $catalog[$onlyMode]];
        }

        foreach ($catalog as $mode => $def) {
            $this->command?->info("── Seeding shop mode: {$mode} ({$def['business_name']})");
            $this->seedMode($mode, $def);
        }

        $this->command?->info('Done. All demo logins use password: 123456');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function catalog(): array
    {
        return [
            'normal_retail' => [
                'business_name' => 'Demo Normal Retail',
                'username' => 'demoretail',
                'email' => 'demoretail@demo.local',
                'sku_prefix' => 'NR',
                'location' => 'Retail Main',
                'categories' => [
                    ['name' => 'Grocery', 'short_code' => 'GRO'],
                    ['name' => 'Household', 'short_code' => 'HH'],
                    ['name' => 'Beverages', 'short_code' => 'BEV'],
                ],
                'products' => [
                    ['name' => 'Sunflower Oil 1L', 'category' => 'Grocery', 'sku' => 'NR-OIL-1L', 'purchase' => 680, 'sell' => 850, 'stock' => 80],
                    ['name' => 'White Sugar 1kg', 'category' => 'Grocery', 'sku' => 'NR-SUG-1KG', 'purchase' => 280, 'sell' => 360, 'stock' => 120],
                    ['name' => 'Basmati Rice 5kg', 'category' => 'Grocery', 'sku' => 'NR-RICE-5KG', 'purchase' => 2100, 'sell' => 2650, 'stock' => 45],
                    ['name' => 'Instant Noodles Pack', 'category' => 'Grocery', 'sku' => 'NR-NOD-PK', 'purchase' => 95, 'sell' => 140, 'stock' => 200],
                    ['name' => 'Toothpaste 100g', 'category' => 'Household', 'sku' => 'NR-TP-100', 'purchase' => 220, 'sell' => 320, 'stock' => 90],
                    ['name' => 'Laundry Detergent 1kg', 'category' => 'Household', 'sku' => 'NR-DET-1KG', 'purchase' => 540, 'sell' => 720, 'stock' => 60],
                    ['name' => 'Dish Wash Liquid 500ml', 'category' => 'Household', 'sku' => 'NR-DISH-500', 'purchase' => 180, 'sell' => 260, 'stock' => 75],
                    ['name' => 'Mineral Water 1.5L', 'category' => 'Beverages', 'sku' => 'NR-WAT-15', 'purchase' => 55, 'sell' => 90, 'stock' => 150],
                    ['name' => 'Cola Soft Drink 1.5L', 'category' => 'Beverages', 'sku' => 'NR-COLA-15', 'purchase' => 220, 'sell' => 310, 'stock' => 70],
                    ['name' => 'Tea Bags Box 100', 'category' => 'Beverages', 'sku' => 'NR-TEA-100', 'purchase' => 480, 'sell' => 650, 'stock' => 50],
                ],
                'customers' => $this->customerDefs('NR'),
            ],
            'tyre_shop' => [
                'business_name' => 'Demo Tyre Shop',
                'username' => 'demotyre',
                'email' => 'demotyre@demo.local',
                'sku_prefix' => 'TY',
                'location' => 'Tyre Bay',
                'categories' => [
                    ['name' => 'Car Tyres', 'short_code' => 'CAR'],
                    ['name' => 'Motorcycle Tyres', 'short_code' => 'MOTO'],
                    ['name' => 'Accessories', 'short_code' => 'ACC'],
                ],
                'products' => [
                    ['name' => 'Michelin Pilot Sport 205/55R16', 'category' => 'Car Tyres', 'sku' => 'DM-TY-MICH-205', 'purchase' => 28500, 'sell' => 34500, 'stock' => 20, 'fields' => ['tyre_size' => '205/55R16', 'tyre_note' => 'Car · XL']],
                    ['name' => 'Bridgestone Turanza 195/65R15', 'category' => 'Car Tyres', 'sku' => 'DM-TY-BRDG-195', 'purchase' => 22000, 'sell' => 26800, 'stock' => 24, 'fields' => ['tyre_size' => '195/65R15', 'tyre_note' => 'Sedan']],
                    ['name' => 'Goodyear EfficientGrip 185/65R15', 'category' => 'Car Tyres', 'sku' => 'DM-TY-GOOD-185', 'purchase' => 19500, 'sell' => 23800, 'stock' => 22, 'fields' => ['tyre_size' => '185/65R15', 'tyre_note' => 'Economy']],
                    ['name' => 'Continental Premium 215/60R16', 'category' => 'Car Tyres', 'sku' => 'DM-TY-CONT-215', 'purchase' => 31000, 'sell' => 37500, 'stock' => 14, 'fields' => ['tyre_size' => '215/60R16', 'tyre_note' => 'SUV']],
                    ['name' => 'Dunlop SP Sport 225/45R17', 'category' => 'Car Tyres', 'sku' => 'DM-TY-DUNL-225', 'purchase' => 33500, 'sell' => 39900, 'stock' => 12, 'fields' => ['tyre_size' => '225/45R17', 'tyre_note' => 'Performance']],
                    ['name' => 'CEAT Secura Drive 175/70R13', 'category' => 'Car Tyres', 'sku' => 'DM-TY-CEAT-175', 'purchase' => 12500, 'sell' => 15500, 'stock' => 30, 'fields' => ['tyre_size' => '175/70R13', 'tyre_note' => 'Compact']],
                    ['name' => 'MRF Zapper 100/90-17 Front', 'category' => 'Motorcycle Tyres', 'sku' => 'DM-TY-MRF-F', 'purchase' => 4200, 'sell' => 5500, 'stock' => 40, 'fields' => ['tyre_size' => '100/90-17', 'tyre_note' => 'Bike front']],
                    ['name' => 'MRF Nylogrip 120/80-17 Rear', 'category' => 'Motorcycle Tyres', 'sku' => 'DM-TY-MRF-R', 'purchase' => 4800, 'sell' => 6200, 'stock' => 38, 'fields' => ['tyre_size' => '120/80-17', 'tyre_note' => 'Bike rear']],
                    ['name' => 'Inner Tube 175/70R13', 'category' => 'Accessories', 'sku' => 'DM-TY-TUBE', 'purchase' => 1800, 'sell' => 2800, 'stock' => 50, 'fields' => ['tyre_size' => '175/70R13', 'tyre_note' => 'Tube']],
                    ['name' => 'Puncture Repair Kit', 'category' => 'Accessories', 'sku' => 'DM-TY-REPAIR', 'purchase' => 900, 'sell' => 1500, 'stock' => 60, 'fields' => ['tyre_size' => '', 'tyre_note' => 'Kit']],
                ],
                'customers' => $this->customerDefs('TY'),
            ],
            'mobile_shop' => [
                'business_name' => 'Demo Mobile Shop',
                'username' => 'demomobile',
                'email' => 'demomobile@demo.local',
                'sku_prefix' => 'MB',
                'location' => 'Phone Counter',
                'categories' => [
                    ['name' => 'Smartphones', 'short_code' => 'PHN'],
                    ['name' => 'Accessories', 'short_code' => 'ACC'],
                ],
                'products' => [
                    ['name' => 'Samsung Galaxy A15 128GB', 'category' => 'Smartphones', 'sku' => 'DM-MB-A15', 'purchase' => 52000, 'sell' => 59900, 'stock' => 10, 'imei' => true],
                    ['name' => 'Samsung Galaxy A35 256GB', 'category' => 'Smartphones', 'sku' => 'DM-MB-A35', 'purchase' => 89000, 'sell' => 99900, 'stock' => 8, 'imei' => true],
                    ['name' => 'Xiaomi Redmi Note 13', 'category' => 'Smartphones', 'sku' => 'DM-MB-RN13', 'purchase' => 48000, 'sell' => 55500, 'stock' => 12, 'imei' => true],
                    ['name' => 'Apple iPhone 13 128GB', 'category' => 'Smartphones', 'sku' => 'DM-MB-IP13', 'purchase' => 185000, 'sell' => 209000, 'stock' => 5, 'imei' => true],
                    ['name' => 'Oppo A78 128GB', 'category' => 'Smartphones', 'sku' => 'DM-MB-A78', 'purchase' => 62000, 'sell' => 71500, 'stock' => 9, 'imei' => true],
                    ['name' => 'Vivo Y27 128GB', 'category' => 'Smartphones', 'sku' => 'DM-MB-Y27', 'purchase' => 45000, 'sell' => 52500, 'stock' => 11, 'imei' => true],
                    ['name' => 'Tempered Glass Universal', 'category' => 'Accessories', 'sku' => 'DM-MB-TG', 'purchase' => 180, 'sell' => 450, 'stock' => 100],
                    ['name' => 'Silicone Case Assorted', 'category' => 'Accessories', 'sku' => 'DM-MB-CASE', 'purchase' => 220, 'sell' => 650, 'stock' => 80],
                    ['name' => 'Fast Charger 25W', 'category' => 'Accessories', 'sku' => 'DM-MB-CHG', 'purchase' => 1200, 'sell' => 2200, 'stock' => 40],
                    ['name' => 'Type-C Cable 1m', 'category' => 'Accessories', 'sku' => 'DM-MB-CABLE', 'purchase' => 350, 'sell' => 750, 'stock' => 90],
                ],
                'customers' => $this->customerDefs('MB'),
            ],
            'vehicle_modification' => [
                'business_name' => 'Demo Vehicle Modification',
                'username' => 'demomod',
                'email' => 'demomod@demo.local',
                'sku_prefix' => 'VM',
                'location' => 'Workshop Bay',
                'categories' => [
                    ['name' => 'Body Kits', 'short_code' => 'BODY'],
                    ['name' => 'Lighting', 'short_code' => 'LIGHT'],
                    ['name' => 'Audio', 'short_code' => 'AUDIO'],
                    ['name' => 'Services', 'short_code' => 'SVC'],
                ],
                'products' => [
                    ['name' => 'LED Headlight Kit H4', 'category' => 'Lighting', 'sku' => 'DM-VM-LED-H4', 'purchase' => 6500, 'sell' => 9800, 'stock' => 25, 'fields' => ['compatible_make' => 'Toyota', 'compatible_model' => 'Aqua']],
                    ['name' => 'LED Fog Lamp Pair', 'category' => 'Lighting', 'sku' => 'DM-VM-FOG', 'purchase' => 4200, 'sell' => 6500, 'stock' => 20, 'fields' => ['compatible_make' => 'Honda', 'compatible_model' => 'Fit']],
                    ['name' => 'Side Skirt Set ABS', 'category' => 'Body Kits', 'sku' => 'DM-VM-SKIRT', 'purchase' => 18000, 'sell' => 26500, 'stock' => 8, 'fields' => ['compatible_make' => 'Toyota', 'compatible_model' => 'Axio']],
                    ['name' => 'Front Lip Spoiler', 'category' => 'Body Kits', 'sku' => 'DM-VM-LIP', 'purchase' => 9500, 'sell' => 14500, 'stock' => 10, 'fields' => ['compatible_make' => 'Nissan', 'compatible_model' => 'March']],
                    ['name' => 'Roof Spoiler Universal', 'category' => 'Body Kits', 'sku' => 'DM-VM-ROOF', 'purchase' => 7200, 'sell' => 11000, 'stock' => 12, 'fields' => ['compatible_make' => 'Universal', 'compatible_model' => 'Hatch']],
                    ['name' => 'Android Head Unit 9"', 'category' => 'Audio', 'sku' => 'DM-VM-HU9', 'purchase' => 28500, 'sell' => 38500, 'stock' => 6, 'fields' => ['compatible_make' => 'Universal', 'compatible_model' => 'Double DIN']],
                    ['name' => '6.5" Component Speakers', 'category' => 'Audio', 'sku' => 'DM-VM-SPK', 'purchase' => 9800, 'sell' => 14500, 'stock' => 15, 'fields' => ['compatible_make' => 'Universal', 'compatible_model' => 'Door']],
                    ['name' => 'Parking Sensor Kit 4', 'category' => 'Lighting', 'sku' => 'DM-VM-PARK', 'purchase' => 5500, 'sell' => 8500, 'stock' => 18, 'fields' => ['compatible_make' => 'Universal', 'compatible_model' => 'Sedan']],
                    ['name' => 'Window Tint Labour (Service)', 'category' => 'Services', 'sku' => 'DM-VM-TINT', 'purchase' => 0, 'sell' => 12000, 'stock' => 999, 'enable_stock' => 0, 'fields' => ['compatible_make' => '', 'compatible_model' => '']],
                    ['name' => 'Body Kit Fitment Labour', 'category' => 'Services', 'sku' => 'DM-VM-FIT', 'purchase' => 0, 'sell' => 15000, 'stock' => 999, 'enable_stock' => 0, 'fields' => ['compatible_make' => '', 'compatible_model' => '']],
                ],
                'customers' => $this->customerDefs('VM'),
                'seed_workshop' => true,
            ],
            'vehicle_spare_parts' => [
                'business_name' => 'Demo Vehicle Spare Parts',
                'username' => 'demospares',
                'email' => 'demospares@demo.local',
                'sku_prefix' => 'SP',
                'location' => 'Parts Counter',
                'categories' => [
                    ['name' => 'Filters', 'short_code' => 'FIL'],
                    ['name' => 'Brakes', 'short_code' => 'BRK'],
                    ['name' => 'Engine', 'short_code' => 'ENG'],
                    ['name' => 'Electrical', 'short_code' => 'ELE'],
                ],
                'products' => [
                    ['name' => 'Oil Filter Toyota 1NZ', 'category' => 'Filters', 'sku' => 'DM-SP-OF-1NZ', 'purchase' => 850, 'sell' => 1450, 'stock' => 40, 'fields' => ['part_number' => '90915-YZZD2', 'oem_number' => '04152-YZZA1', 'compatible_make' => 'Toyota', 'compatible_model' => 'Vitz', 'year_from' => 2010, 'year_to' => 2020, 'engine_type' => '1NZ-FE']],
                    ['name' => 'Air Filter Honda L15', 'category' => 'Filters', 'sku' => 'DM-SP-AF-L15', 'purchase' => 1200, 'sell' => 1950, 'stock' => 35, 'fields' => ['part_number' => '17220-RTA-000', 'oem_number' => '17220RTA', 'compatible_make' => 'Honda', 'compatible_model' => 'Fit', 'year_from' => 2014, 'year_to' => 2022, 'engine_type' => 'L15B']],
                    ['name' => 'Cabin Filter Universal', 'category' => 'Filters', 'sku' => 'DM-SP-CAB', 'purchase' => 980, 'sell' => 1650, 'stock' => 50, 'fields' => ['part_number' => 'CF-UNI-01', 'oem_number' => '', 'compatible_make' => 'Universal', 'compatible_model' => '', 'year_from' => null, 'year_to' => null, 'engine_type' => '']],
                    ['name' => 'Front Brake Pad Set Aqua', 'category' => 'Brakes', 'sku' => 'DM-SP-BP-AQ', 'purchase' => 4500, 'sell' => 6800, 'stock' => 22, 'fields' => ['part_number' => '04465-52040', 'oem_number' => '0446552040', 'compatible_make' => 'Toyota', 'compatible_model' => 'Aqua', 'year_from' => 2012, 'year_to' => 2021, 'engine_type' => '1NZ-FXE']],
                    ['name' => 'Brake Disc Rotor Pair', 'category' => 'Brakes', 'sku' => 'DM-SP-BD-PR', 'purchase' => 9800, 'sell' => 14500, 'stock' => 12, 'fields' => ['part_number' => '43512-52050', 'oem_number' => '4351252050', 'compatible_make' => 'Toyota', 'compatible_model' => 'Axio', 'year_from' => 2007, 'year_to' => 2018, 'engine_type' => '1NZ']],
                    ['name' => 'Spark Plug Iridium x4', 'category' => 'Engine', 'sku' => 'DM-SP-SPK', 'purchase' => 3200, 'sell' => 4800, 'stock' => 30, 'fields' => ['part_number' => 'IK20', 'oem_number' => '90919-01210', 'compatible_make' => 'Toyota', 'compatible_model' => 'Corolla', 'year_from' => 2008, 'year_to' => 2019, 'engine_type' => '1ZR']],
                    ['name' => 'Timing Belt Kit', 'category' => 'Engine', 'sku' => 'DM-SP-TB', 'purchase' => 12500, 'sell' => 18500, 'stock' => 8, 'fields' => ['part_number' => 'TBK-1NZ', 'oem_number' => '13568-19025', 'compatible_make' => 'Toyota', 'compatible_model' => 'Vitz', 'year_from' => 2005, 'year_to' => 2014, 'engine_type' => '1NZ-FE']],
                    ['name' => 'Water Pump Assembly', 'category' => 'Engine', 'sku' => 'DM-SP-WP', 'purchase' => 7800, 'sell' => 11500, 'stock' => 10, 'fields' => ['part_number' => '16100-21030', 'oem_number' => '1610021030', 'compatible_make' => 'Toyota', 'compatible_model' => 'Premio', 'year_from' => 2007, 'year_to' => 2016, 'engine_type' => '1NZ']],
                    ['name' => 'Alternator 12V 80A', 'category' => 'Electrical', 'sku' => 'DM-SP-ALT', 'purchase' => 18500, 'sell' => 26500, 'stock' => 6, 'fields' => ['part_number' => '27060-21040', 'oem_number' => '2706021040', 'compatible_make' => 'Toyota', 'compatible_model' => 'Axio', 'year_from' => 2006, 'year_to' => 2015, 'engine_type' => '1NZ']],
                    ['name' => 'Battery 12V 45Ah', 'category' => 'Electrical', 'sku' => 'DM-SP-BAT', 'purchase' => 16500, 'sell' => 22500, 'stock' => 15, 'fields' => ['part_number' => 'NS60L', 'oem_number' => '', 'compatible_make' => 'Universal', 'compatible_model' => 'Sedan', 'year_from' => null, 'year_to' => null, 'engine_type' => '']],
                ],
                'customers' => $this->customerDefs('SP'),
            ],
            'hardware_shop' => [
                'business_name' => 'Demo Hardware Shop',
                'username' => 'demohardware',
                'email' => 'demohardware@demo.local',
                'sku_prefix' => 'HW',
                'location' => 'Hardware Yard',
                'categories' => [
                    ['name' => 'Fasteners', 'short_code' => 'FST'],
                    ['name' => 'Pipes & Fittings', 'short_code' => 'PIP'],
                    ['name' => 'Tools', 'short_code' => 'TL'],
                    ['name' => 'Paint', 'short_code' => 'PNT'],
                ],
                'products' => [
                    ['name' => 'Hex Bolt M8x40 Pack 50', 'category' => 'Fasteners', 'sku' => 'DM-HW-BOLT-M8', 'purchase' => 650, 'sell' => 980, 'stock' => 80, 'fields' => ['hw_size' => 'M8', 'hw_length' => '40mm', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Steel', 'hw_grade' => '8.8', 'hw_colour' => 'Zinc']],
                    ['name' => 'Wood Screw 2" Pack 100', 'category' => 'Fasteners', 'sku' => 'DM-HW-SCR-2', 'purchase' => 420, 'sell' => 680, 'stock' => 100, 'fields' => ['hw_size' => '8g', 'hw_length' => '2 inch', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Steel', 'hw_grade' => '', 'hw_colour' => 'Yellow']],
                    ['name' => 'PVC Pipe 1/2" x 4m', 'category' => 'Pipes & Fittings', 'sku' => 'DM-HW-PVC-12', 'purchase' => 380, 'sell' => 550, 'stock' => 60, 'fields' => ['hw_size' => '1/2 inch', 'hw_length' => '4m', 'hw_width' => '', 'hw_thickness' => 'Class D', 'hw_material' => 'PVC', 'hw_grade' => '', 'hw_colour' => 'Grey']],
                    ['name' => 'PVC Elbow 1/2"', 'category' => 'Pipes & Fittings', 'sku' => 'DM-HW-ELB-12', 'purchase' => 35, 'sell' => 65, 'stock' => 200, 'fields' => ['hw_size' => '1/2 inch', 'hw_length' => '', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'PVC', 'hw_grade' => '', 'hw_colour' => 'Grey']],
                    ['name' => 'GI Pipe 3/4" x 6m', 'category' => 'Pipes & Fittings', 'sku' => 'DM-HW-GI-34', 'purchase' => 2800, 'sell' => 3650, 'stock' => 25, 'fields' => ['hw_size' => '3/4 inch', 'hw_length' => '6m', 'hw_width' => '', 'hw_thickness' => 'Medium', 'hw_material' => 'Galvanized Iron', 'hw_grade' => '', 'hw_colour' => 'Silver']],
                    ['name' => 'Claw Hammer 16oz', 'category' => 'Tools', 'sku' => 'DM-HW-HAM', 'purchase' => 950, 'sell' => 1450, 'stock' => 30, 'fields' => ['hw_size' => '16oz', 'hw_length' => '', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Steel/Wood', 'hw_grade' => '', 'hw_colour' => '']],
                    ['name' => 'Adjustable Spanner 10"', 'category' => 'Tools', 'sku' => 'DM-HW-SPN', 'purchase' => 780, 'sell' => 1250, 'stock' => 28, 'fields' => ['hw_size' => '10 inch', 'hw_length' => '', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Chrome Vanadium', 'hw_grade' => '', 'hw_colour' => '']],
                    ['name' => 'Measuring Tape 5m', 'category' => 'Tools', 'sku' => 'DM-HW-TAPE', 'purchase' => 320, 'sell' => 550, 'stock' => 45, 'fields' => ['hw_size' => '5m', 'hw_length' => '5m', 'hw_width' => '25mm', 'hw_thickness' => '', 'hw_material' => 'ABS/Steel', 'hw_grade' => '', 'hw_colour' => 'Yellow']],
                    ['name' => 'Emulsion Paint White 4L', 'category' => 'Paint', 'sku' => 'DM-HW-PNT-W', 'purchase' => 3200, 'sell' => 4200, 'stock' => 35, 'fields' => ['hw_size' => '4L', 'hw_length' => '', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Emulsion', 'hw_grade' => 'Interior', 'hw_colour' => 'White']],
                    ['name' => 'Enamel Paint Black 1L', 'category' => 'Paint', 'sku' => 'DM-HW-PNT-B', 'purchase' => 980, 'sell' => 1450, 'stock' => 40, 'fields' => ['hw_size' => '1L', 'hw_length' => '', 'hw_width' => '', 'hw_thickness' => '', 'hw_material' => 'Enamel', 'hw_grade' => 'Gloss', 'hw_colour' => 'Black']],
                ],
                'customers' => $this->customerDefs('HW'),
            ],
        ];
    }

    /**
     * @return array<int, array{name: string, mobile: string, contact_id: string}>
     */
    protected function customerDefs(string $prefix): array
    {
        // Distinct phone prefix per mode so searches stay unique across demo businesses
        $prefixMap = [
            'NR' => '071', 'TY' => '072', 'MB' => '075',
            'VM' => '077', 'SP' => '076', 'HW' => '074',
        ];
        $phonePrefix = $prefixMap[$prefix] ?? '070';
        $names = [
            'Kasun Perera', 'Nimali Fernando', 'Ruwan Silva', 'Amali Jayasuriya', 'Dilshan Bandara',
            'Sajith Gunasekara', 'Ishara Wickramasinghe', 'Chamari Dias', 'Pradeep Rathnayake', 'Tharindu Weerasinghe',
        ];
        $out = [];
        foreach ($names as $i => $name) {
            $n = $i + 1;
            $out[] = [
                'name' => $name,
                'mobile' => $phonePrefix.str_pad((string) (1000000 + $n), 7, '0', STR_PAD_LEFT),
                'contact_id' => $prefix.'-C'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            ];
        }

        return $out;
    }

    protected function seedMode(string $mode, array $def): void
    {
        [$business, $user, $location] = $this->ensureBusiness($mode, $def);
        $business_id = $business->id;
        $user_id = $user->id;
        $location_id = $location->id;

        ShopModeUtil::applyToBusiness($business->fresh(), $mode);
        ShopModeUtil::forgetCache($business_id);

        $settings = json_decode($business->pos_settings ?: '{}', true) ?: [];
        $settings = array_merge($this->businessUtil->defaultPosSettings(), $settings, [
            'pos_ui' => 'touch',
            'touch_ask_qty' => 1,
            'hide_product_suggestion' => 0,
            'show_pricing_on_product_sugesstion' => 1,
        ]);
        if ($mode === 'mobile_shop') {
            $settings['enable_serial_per_qty'] = 1;
        }
        $business->pos_settings = json_encode($settings);
        $business->enable_category = 1;
        $business->save();

        $unit = Unit::firstOrCreate(
            ['business_id' => $business_id, 'actual_name' => 'Pieces'],
            ['short_name' => 'Pc', 'allow_decimal' => 0, 'created_by' => $user_id]
        );

        $categoryMap = [];
        foreach ($def['categories'] as $cat) {
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

        $productIds = $this->seedProducts(
            $def['products'],
            $categoryMap,
            $unit->id,
            $business_id,
            $user_id,
            $location_id
        );

        $contacts = $this->seedCustomers($def['customers'] ?? [], $business_id, $user_id);

        if ($mode === 'mobile_shop' && Schema::hasTable('mobile_imeis')) {
            $this->seedImeis($def['products'], $productIds, $business_id, $location_id, $user_id);
        }

        if (! empty($def['seed_workshop']) && Schema::hasTable('customer_vehicles')) {
            $this->seedWorkshopHistory($contacts, $productIds, $business_id, $user_id, $location_id);
        }

        $open = CashRegister::where('user_id', $user_id)->where('status', 'open')->first();
        if (! $open) {
            CashRegister::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'user_id' => $user_id,
                'status' => 'open',
                'created_at' => $this->now,
                'updated_at' => $this->now,
            ]);
        }

        $this->command?->info("  OK business #{$business_id} | login: {$def['username']} / 123456 | products: ".count($def['products']));
    }

    /**
     * @return array{0: Business, 1: User, 2: BusinessLocation}
     */
    protected function ensureBusiness(string $mode, array $def): array
    {
        $existing = Business::where('name', $def['business_name'])->first();
        if ($existing) {
            $user = User::where('business_id', $existing->id)->orderBy('id')->first();
            if (! $user) {
                $user = $this->createOwner($existing->id, $def);
            }
            $location = BusinessLocation::where('business_id', $existing->id)->first();
            if (! $location) {
                $location = $this->businessUtil->addLocation($existing->id, [
                    'name' => $def['location'],
                    'country' => 'Sri Lanka',
                    'state' => 'Western',
                    'city' => 'Colombo',
                    'zip_code' => '01000',
                    'landmark' => 'Demo',
                    'website' => '',
                    'mobile' => '0112000000',
                    'alternate_number' => '',
                    'email' => $def['email'],
                ]);
                Permission::findOrCreate('location.'.$location->id, 'web');
                $user->givePermissionTo('location.'.$location->id);
                $user->givePermissionTo('access_all_locations');
            }

            return [$existing, $user, $location];
        }

        $currency_id = Currency::where('code', 'LKR')->value('id')
            ?: Currency::where('code', 'USD')->value('id')
            ?: 2;

        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Demo',
            'last_name' => ucfirst(str_replace('_', ' ', $mode)),
            'username' => $def['username'],
            'email' => $def['email'],
            'password' => Hash::make('123456'),
            'language' => 'en',
        ]);

        $pos_settings = array_merge($this->businessUtil->defaultPosSettings(), [
            'pos_ui' => 'touch',
            'touch_ask_qty' => 1,
            'hide_product_suggestion' => 0,
            'show_pricing_on_product_sugesstion' => 1,
        ]);

        $business = $this->businessUtil->createNewBusiness([
            'name' => $def['business_name'],
            'currency_id' => $currency_id,
            'start_date' => $this->now->toDateString(),
            'tax_number_1' => '',
            'tax_label_1' => '',
            'owner_id' => $user->id,
            'time_zone' => config('app.timezone', 'Asia/Colombo'),
            'fy_start_month' => 1,
            'accounting_method' => 'fifo',
            'default_sales_discount' => 0,
            'sell_price_tax' => 'includes',
            'sku_prefix' => $def['sku_prefix'],
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
        $this->businessUtil->newBusinessDefaultResources($business->id, $user->id);

        $location = $this->businessUtil->addLocation($business->id, [
            'name' => $def['location'],
            'country' => 'Sri Lanka',
            'state' => 'Western',
            'city' => 'Colombo',
            'zip_code' => '01000',
            'landmark' => 'Demo Location',
            'website' => '',
            'mobile' => '0112000000',
            'alternate_number' => '',
            'email' => $def['email'],
        ]);

        Permission::findOrCreate('location.'.$location->id, 'web');
        $user->givePermissionTo('location.'.$location->id);
        $user->givePermissionTo('access_all_locations');

        return [$business, $user, $location];
    }

    protected function createOwner(int $business_id, array $def): User
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Demo',
            'last_name' => 'Admin',
            'username' => $def['username'],
            'email' => $def['email'],
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);
        $user->assignRole('Admin#'.$business_id);

        return $user;
    }

    /**
     * @return array<string, int> sku => product_id
     */
    protected function seedProducts(array $products, array $categoryMap, int $unit_id, int $business_id, int $user_id, int $location_id): array
    {
        $ids = [];
        $created = 0;
        $updated = 0;

        foreach ($products as $item) {
            $enableStock = array_key_exists('enable_stock', $item) ? (int) $item['enable_stock'] : 1;
            $fields = $this->filterExistingProductFields($item['fields'] ?? []);

            $product = Product::where('business_id', $business_id)->where('sku', $item['sku'])->first();
            if ($product) {
                $product->update(array_merge([
                    'name' => $item['name'],
                    'category_id' => $categoryMap[$item['category']] ?? null,
                    'unit_id' => $unit_id,
                    'enable_stock' => $enableStock,
                    'not_for_selling' => 0,
                    'is_inactive' => 0,
                ], $fields));
                $product->product_locations()->sync([$location_id]);
                $ids[$item['sku']] = $product->id;
                $updated++;
                continue;
            }

            $product = Product::create(array_merge([
                'name' => $item['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unit_id,
                'brand_id' => null,
                'category_id' => $categoryMap[$item['category']] ?? null,
                'tax' => null,
                'tax_type' => 'inclusive',
                'enable_stock' => $enableStock,
                'alert_quantity' => 4,
                'sku' => $item['sku'],
                'barcode_type' => 'C128',
                'image' => null,
                'created_by' => $user_id,
                'not_for_selling' => 0,
                'is_inactive' => 0,
            ], $fields));

            $purchase = (float) $item['purchase'];
            $sell = (float) $item['sell'];
            $this->productUtil->createSingleProductVariation(
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

            if ($enableStock) {
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
                    'transaction_date' => $this->now->format('Y-m-d H:i:s'),
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
            }

            $ids[$item['sku']] = $product->id;
            $created++;
        }

        $this->command?->info("  Products created: {$created}, updated: {$updated}");

        return $ids;
    }

    /**
     * @param  array<string, mixed>  $fields
     * @return array<string, mixed>
     */
    protected function filterExistingProductFields(array $fields): array
    {
        $out = [];
        foreach ($fields as $col => $val) {
            if (Schema::hasColumn('products', $col)) {
                $out[$col] = $val;
            }
        }

        return $out;
    }

    /**
     * @return array<int, Contact>
     */
    protected function seedCustomers(array $customers, int $business_id, int $user_id): array
    {
        $list = [];
        foreach ($customers as $c) {
            $contact = Contact::firstOrCreate(
                ['business_id' => $business_id, 'contact_id' => $c['contact_id']],
                [
                    'type' => 'customer',
                    'name' => $c['name'],
                    'mobile' => $c['mobile'],
                    'created_by' => $user_id,
                ]
            );
            if ($contact->mobile !== $c['mobile'] || $contact->name !== $c['name']) {
                $contact->update(['name' => $c['name'], 'mobile' => $c['mobile']]);
            }
            $list[] = $contact;
        }

        return $list;
    }

    protected function seedImeis(array $productDefs, array $productIds, int $business_id, int $location_id, int $user_id): void
    {
        $n = 0;
        foreach ($productDefs as $item) {
            if (empty($item['imei'])) {
                continue;
            }
            $productId = $productIds[$item['sku']] ?? null;
            if (! $productId) {
                continue;
            }
            $product = Product::with('variations')->find($productId);
            $variation = $product?->variations->first();
            if (! $variation) {
                continue;
            }
            for ($i = 1; $i <= 2; $i++) {
                $imei = '35'.str_pad((string) abs(crc32($item['sku'].$i.$business_id)), 13, '0', STR_PAD_LEFT);
                MobileImei::firstOrCreate(
                    ['business_id' => $business_id, 'imei' => $imei],
                    [
                        'product_id' => $productId,
                        'variation_id' => $variation->id,
                        'location_id' => $location_id,
                        'status' => 'available',
                        'created_by' => $user_id,
                    ]
                );
                $n++;
            }
        }
        $this->command?->info("  IMEIs seeded/updated: {$n}");
    }

    /**
     * 10 vehicles + 10 workshop jobs for Vehicle History demo.
     *
     * @param  array<int, Contact>  $contacts
     * @param  array<string, int>  $productIds
     */
    protected function seedWorkshopHistory(array $contacts, array $productIds, int $business_id, int $user_id, int $location_id): void
    {
        $vehiclesSpec = [
            ['reg' => 'WP CAB-1234', 'make' => 'Toyota', 'model' => 'Aqua', 'year' => 2015, 'type' => 'car'],
            ['reg' => 'WP ABC-5678', 'make' => 'Honda', 'model' => 'Fit', 'year' => 2018, 'type' => 'car'],
            ['reg' => 'CP KD-9012', 'make' => 'Toyota', 'model' => 'Axio', 'year' => 2012, 'type' => 'car'],
            ['reg' => 'WP EFG-3456', 'make' => 'Nissan', 'model' => 'March', 'year' => 2016, 'type' => 'car'],
            ['reg' => 'SB HIJ-7890', 'make' => 'Suzuki', 'model' => 'Wagon R', 'year' => 2019, 'type' => 'car'],
            ['reg' => 'WP KLM-1122', 'make' => 'Toyota', 'model' => 'Premio', 'year' => 2014, 'type' => 'car'],
            ['reg' => 'NW OPQ-3344', 'make' => 'Honda', 'model' => 'Vezel', 'year' => 2017, 'type' => 'suv'],
            ['reg' => 'WP RST-5566', 'make' => 'Mitsubishi', 'model' => 'Lancer', 'year' => 2011, 'type' => 'car'],
            ['reg' => 'CP UVW-7788', 'make' => 'Toyota', 'model' => 'Hiace', 'year' => 2013, 'type' => 'van'],
            ['reg' => 'WP XYZ-9900', 'make' => 'Bajaj', 'model' => 'Pulsar 150', 'year' => 2020, 'type' => 'bike'],
        ];

        $vehicles = [];
        foreach ($vehiclesSpec as $i => $v) {
            $contact = $contacts[$i % max(count($contacts), 1)] ?? null;
            if (! $contact) {
                break;
            }
            $vehicles[] = CustomerVehicle::firstOrCreate(
                [
                    'business_id' => $business_id,
                    'registration_number' => $v['reg'],
                ],
                [
                    'contact_id' => $contact->id,
                    'make' => $v['make'],
                    'model' => $v['model'],
                    'year' => $v['year'],
                    'vehicle_type' => $v['type'],
                    'notes' => 'Demo vehicle',
                ]
            );
        }

        // Give first customer a second vehicle for multi-vehicle phone search
        if (count($contacts) > 0 && count($vehiclesSpec) > 1) {
            CustomerVehicle::firstOrCreate(
                [
                    'business_id' => $business_id,
                    'registration_number' => 'WP DUAL-0001',
                ],
                [
                    'contact_id' => $contacts[0]->id,
                    'make' => 'Toyota',
                    'model' => 'Vitz',
                    'year' => 2016,
                    'vehicle_type' => 'car',
                    'notes' => 'Second vehicle for same phone',
                ]
            );
        }

        $partSkus = array_values(array_filter(array_keys($productIds), fn ($s) => ! in_array($s, ['DM-VM-TINT', 'DM-VM-FIT'], true)));
        $serviceSkus = ['DM-VM-TINT', 'DM-VM-FIT'];
        $statuses = ['completed', 'completed', 'in_progress', 'pending', 'completed', 'delivered', 'completed', 'completed', 'in_progress', 'completed'];
        $payments = ['paid', 'paid', 'partial', 'unpaid', 'paid', 'paid', 'partial', 'paid', 'unpaid', 'paid'];

        $jobsCreated = 0;
        foreach ($vehicles as $i => $vehicle) {
            $ref = 'JOB-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT);
            $job = WorkshopJob::firstOrCreate(
                [
                    'business_id' => $business_id,
                    'reference_no' => $ref,
                ],
                [
                    'contact_id' => $vehicle->contact_id,
                    'vehicle_id' => $vehicle->id,
                    'location_id' => $location_id,
                    'status' => $statuses[$i] ?? 'completed',
                    'description' => 'Demo modification job for '.$vehicle->registration_number,
                    'notes' => 'Seeded history record',
                    'payment_status' => $payments[$i] ?? 'paid',
                    'created_by' => $user_id,
                    'started_at' => $this->now->copy()->subDays(30 - $i),
                    'completed_at' => in_array($statuses[$i] ?? '', ['completed', 'delivered'], true)
                        ? $this->now->copy()->subDays(28 - $i)
                        : null,
                    'delivered_at' => ($statuses[$i] ?? '') === 'delivered'
                        ? $this->now->copy()->subDays(27 - $i)
                        : null,
                ]
            );

            if ($job->items()->count() === 0) {
                $partSku = $partSkus[$i % max(count($partSkus), 1)] ?? null;
                $svcSku = $serviceSkus[$i % 2];
                foreach (array_filter([$partSku, $svcSku]) as $sku) {
                    $pid = $productIds[$sku] ?? null;
                    if (! $pid) {
                        continue;
                    }
                    $product = Product::with('variations')->find($pid);
                    $variation = $product?->variations->first();
                    $price = (float) ($variation->sell_price_inc_tax ?? $variation->default_sell_price ?? 0);
                    WorkshopJobItem::create([
                        'workshop_job_id' => $job->id,
                        'product_id' => $pid,
                        'variation_id' => $variation?->id,
                        'item_type' => in_array($sku, $serviceSkus, true) ? 'service' : 'part',
                        'name' => $product->name,
                        'quantity' => 1,
                        'unit_price' => $price,
                        'notes' => null,
                    ]);
                }
                $job->recalcTotal();
            }
            $jobsCreated++;
        }

        $this->command?->info('  Vehicles: '.count($vehicles).' | Jobs: '.$jobsCreated);
    }
}
