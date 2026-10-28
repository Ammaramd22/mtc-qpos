<?php

namespace Database\Seeders;

use App\Business;
use App\BusinessLocation;
use App\CashRegister;
use App\Category;
use App\Contact;
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
 * Nutmeg Buying & Selling Centre — demo for Point of Purchase + AVCO trading.
 * Buys nutmeg from customers (farmers) at varying prices; sells to suppliers/buyers.
 *
 * Run: php artisan db:seed --class=NutmegTradingSeeder
 *   or: php artisan pos:seed-nutmeg
 */
class NutmegTradingSeeder extends Seeder
{
    public function run()
    {
        $now = Carbon::now();
        $businessUtil = new BusinessUtil();
        $productUtil = new ProductUtil();
        $businessName = 'Nutmeg Buying & Selling Centre';

        $existing = Business::where('name', $businessName)->first();
        if ($existing) {
            $this->command?->warn($businessName.' already exists (id='.$existing->id.'). Updating POP / AVCO settings…');
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
                'first_name' => 'Nutmeg',
                'last_name' => 'Admin',
                'username' => 'nutmeg',
                'email' => 'nutmeg@demo.local',
                'password' => Hash::make('123456'),
                'language' => 'en',
            ]);

            $pos_settings = array_merge($businessUtil->defaultPosSettings(), [
                'pos_ui' => 'touch',
                'pop_ui' => 'touch',
                'enable_pop' => 1,
                'purchase_ui' => 'pop',
                'pop_contact_both' => 1,
                'pop_link_cash_register' => 1,
                'touch_ask_qty' => 1,
                'hide_product_suggestion' => 0,
                'show_pricing_on_product_sugesstion' => 1,
                'allow_overselling' => 0,
            ]);

            $business = $businessUtil->createNewBusiness([
                'name' => $businessName,
                'currency_id' => $currency_id,
                'start_date' => $now->toDateString(),
                'tax_number_1' => '',
                'tax_label_1' => '',
                'owner_id' => $user->id,
                'time_zone' => config('app.timezone', 'Asia/Colombo'),
                'fy_start_month' => 1,
                'accounting_method' => 'avco',
                'default_sales_discount' => 0,
                'sell_price_tax' => 'includes',
                'sku_prefix' => 'NM',
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
                'enable_sub_category' => 0,
                'enable_price_tax' => 1,
                'enable_purchase_status' => 0,
                'enable_lot_number' => 0,
                'enable_racks' => 0,
                'enable_row' => 0,
                'enable_position' => 0,
                'enabled_modules' => [
                    'purchases', 'add_sale', 'pos_sale', 'pos_purchase',
                    'stock_transfers', 'stock_adjustment', 'expenses',
                    'nutmeg',
                ],
                'date_format' => 'd-m-Y',
                'time_format' => 12,
                'currency_symbol_placement' => 'before',
            ]);

            $user->business_id = $business->id;
            $user->save();

            $businessUtil->newBusinessDefaultResources($business->id, $user->id);

            $business->pos_settings = json_encode($pos_settings);
            $business->accounting_method = 'avco';
            $business->enable_category = 1;
            $business->save();

            $location = $businessUtil->addLocation($business->id, [
                'name' => 'Nutmeg Main',
                'country' => 'Sri Lanka',
                'state' => 'Southern',
                'city' => 'Matara',
                'zip_code' => '81000',
                'landmark' => 'Buying Centre',
                'website' => '',
                'mobile' => '0771112233',
                'alternate_number' => '',
                'email' => 'nutmeg@demo.local',
            ]);

            Permission::findOrCreate('location.'.$location->id, 'web');
            $user->givePermissionTo('location.'.$location->id);
            $user->givePermissionTo('access_all_locations');
        }

        $business_id = $business->id;
        $user_id = $user->id;
        $location_id = $location->id;

        // Always refresh POP + AVCO
        $settings = json_decode($business->pos_settings ?: '{}', true) ?: [];
        $settings = array_merge($businessUtil->defaultPosSettings(), $settings, [
            'pos_ui' => 'touch',
            'pop_ui' => 'touch',
            'enable_pop' => 1,
            'purchase_ui' => 'pop',
            'pop_contact_both' => 1,
            'pop_link_cash_register' => 1,
            'touch_ask_qty' => 1,
        ]);
        $business->pos_settings = json_encode($settings);
        $business->accounting_method = 'avco';
        $modules = is_array($business->enabled_modules)
            ? $business->enabled_modules
            : (json_decode($business->enabled_modules ?: '[]', true) ?: []);
        foreach (['purchases', 'pos_sale', 'pos_purchase', 'add_sale', 'nutmeg'] as $mod) {
            if (! in_array($mod, $modules)) {
                $modules[] = $mod;
            }
        }
        $business->enabled_modules = $modules;
        $business->enable_category = 1;
        $business->save();

        $unit = Unit::where('business_id', $business_id)->where('actual_name', 'Kg')->first();
        if (! $unit) {
            $unit = Unit::create([
                'business_id' => $business_id,
                'actual_name' => 'Kg',
                'short_name' => 'kg',
                'allow_decimal' => 1,
                'created_by' => $user_id,
            ]);
        }

        $cat = Category::where('business_id', $business_id)->where('name', 'Nutmeg')->first();
        if (! $cat) {
            $cat = Category::create([
                'name' => 'Nutmeg',
                'business_id' => $business_id,
                'created_by' => $user_id,
                'parent_id' => 0,
                'category_type' => 'product',
            ]);
        } else {
            if (empty($cat->category_type)) {
                $cat->category_type = 'product';
                $cat->save();
            }
        }

        $products = [
            ['name' => 'Nutmeg Whole (With Shell)', 'sku' => 'NM-WHOLE', 'buy' => 1800, 'sell' => 2200, 'stock' => 50, 'image' => 'nutmeg_whole.jpg'],
            ['name' => 'Nutmeg Kernel (Without Shell)', 'sku' => 'NM-KERNEL', 'buy' => 3200, 'sell' => 3800, 'stock' => 30, 'image' => 'nutmeg_kernel.jpg'],
            ['name' => 'Mace (Jathipath)', 'sku' => 'NM-MACE', 'buy' => 4500, 'sell' => 5200, 'stock' => 15, 'image' => 'nutmeg_mace.jpg'],
            ['name' => 'Nutmeg Powder', 'sku' => 'NM-POWDER', 'buy' => 2800, 'sell' => 3400, 'stock' => 20, 'image' => 'nutmeg_powder.jpg'],
            ['name' => 'Dried Nutmeg Grade A', 'sku' => 'NM-GRADE-A', 'buy' => 3600, 'sell' => 4200, 'stock' => 25, 'image' => 'nutmeg_grade_a.jpg'],
        ];

        $imgDir = public_path('uploads/img');

        foreach ($products as $p) {
            $image = $p['image'];
            if (! is_file($imgDir.DIRECTORY_SEPARATOR.$image)) {
                $image = null;
            }

            $product = Product::where('business_id', $business_id)->where('sku', $p['sku'])->first();
            if (! $product) {
                $product = Product::create([
                    'name' => $p['name'],
                    'business_id' => $business_id,
                    'type' => 'single',
                    'unit_id' => $unit->id,
                    'tax_type' => 'inclusive',
                    'barcode_type' => 'C128',
                    'sku' => $p['sku'],
                    'category_id' => $cat->id,
                    'enable_stock' => 1,
                    'image' => $image,
                    'created_by' => $user_id,
                ]);
                $productUtil->createSingleProductVariation(
                    $product,
                    $p['sku'],
                    $p['buy'],
                    $p['buy'],
                    0,
                    $p['sell'],
                    $p['sell']
                );
            } else {
                $product->update([
                    'name' => $p['name'],
                    'category_id' => $cat->id,
                    'image' => $image,
                    'enable_stock' => 1,
                    'not_for_selling' => 0,
                    'is_inactive' => 0,
                ]);
            }

            // POS product grid requires product_locations + category_type=product
            $product->product_locations()->sync([$location_id]);
            if (empty($product->category_id)) {
                $product->category_id = $cat->id;
                $product->save();
            }

            $variation = $product->variations()->first();
            if ($variation) {
                $vld = VariationLocationDetails::where('variation_id', $variation->id)
                    ->where('location_id', $location_id)
                    ->first();
                if (! $vld) {
                    VariationLocationDetails::create([
                        'product_id' => $product->id,
                        'product_variation_id' => $variation->product_variation_id,
                        'variation_id' => $variation->id,
                        'location_id' => $location_id,
                        'qty_available' => $p['stock'],
                    ]);
                }

                // Opening stock lot if none
                $has_os = PurchaseLine::where('variation_id', $variation->id)
                    ->whereHas('transaction', function ($q) use ($business_id) {
                        $q->where('business_id', $business_id)->where('type', 'opening_stock');
                    })
                    ->exists();
                if (! $has_os && $p['stock'] > 0) {
                    $os = Transaction::create([
                        'business_id' => $business_id,
                        'location_id' => $location_id,
                        'type' => 'opening_stock',
                        'status' => 'received',
                        'payment_status' => 'paid',
                        'transaction_date' => $now,
                        'total_before_tax' => $p['buy'] * $p['stock'],
                        'final_total' => $p['buy'] * $p['stock'],
                        'created_by' => $user_id,
                        'opening_stock_product_id' => $product->id,
                    ]);
                    PurchaseLine::create([
                        'transaction_id' => $os->id,
                        'product_id' => $product->id,
                        'variation_id' => $variation->id,
                        'quantity' => $p['stock'],
                        'pp_without_discount' => $p['buy'],
                        'purchase_price' => $p['buy'],
                        'purchase_price_inc_tax' => $p['buy'],
                        'item_tax' => 0,
                    ]);
                }
            }
        }

        // Sample farmer (customer we buy from) + exporter (supplier we sell to)
        Contact::firstOrCreate(
            ['business_id' => $business_id, 'mobile' => '0772001001'],
            [
                'type' => 'customer',
                'name' => 'Farmer Silva',
                'first_name' => 'Silva',
                'contact_id' => 'FARM-001',
                'created_by' => $user_id,
            ]
        );
        Contact::firstOrCreate(
            ['business_id' => $business_id, 'mobile' => '0772002002'],
            [
                'type' => 'supplier',
                'name' => 'Export Buyer Co',
                'supplier_business_name' => 'Export Buyer Co',
                'contact_id' => 'BUY-001',
                'created_by' => $user_id,
            ]
        );
        Contact::firstOrCreate(
            ['business_id' => $business_id, 'mobile' => '0772003003'],
            [
                'type' => 'both',
                'name' => 'Local Trader Perera',
                'first_name' => 'Perera',
                'contact_id' => 'TRD-001',
                'created_by' => $user_id,
            ]
        );

        // Open cash register with float for buying
        $open = CashRegister::where('business_id', $business_id)
            ->where('user_id', $user_id)
            ->where('status', 'open')
            ->first();
        if (! $open) {
            $register = CashRegister::create([
                'business_id' => $business_id,
                'location_id' => $location_id,
                'user_id' => $user_id,
                'status' => 'open',
            ]);
            $register->cash_register_transactions()->create([
                'amount' => 100000,
                'pay_method' => 'cash',
                'type' => 'credit',
                'transaction_type' => 'initial',
            ]);
        }

        $this->command?->info($businessName.' ready.');
        $this->command?->info('Login: nutmeg / 123456');
        $this->command?->info('Accounting: AVCO | POP: enabled (Touch) | Cash float: Rs 100,000');
    }

    protected function createOwner($business_id)
    {
        $user = User::create([
            'surname' => 'Mr',
            'first_name' => 'Nutmeg',
            'last_name' => 'Admin',
            'username' => 'nutmeg_'.substr((string) $business_id, -3),
            'email' => 'nutmeg'.$business_id.'@demo.local',
            'password' => Hash::make('123456'),
            'language' => 'en',
            'business_id' => $business_id,
        ]);

        return $user;
    }
}
