<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Product;
use App\Category;
use App\Brands;
use App\Unit;
use App\BusinessLocation;
use App\Transaction;
use App\PurchaseLine;
use App\VariationLocationDetails;
use App\Utils\ProductUtil;
use Carbon\Carbon;

class SampleProductsSeeder extends Seeder
{
    public function run()
    {
        $business_id = 1;
        $location_id = BusinessLocation::where('business_id', $business_id)->value('id') ?: 1;
        $user_id = 1;
        $now = Carbon::now();

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // -- Ensure default unit exists --
        $unit = Unit::firstOrCreate(
            ['id' => 1, 'business_id' => $business_id],
            [
                'actual_name' => 'Pieces',
                'short_name' => 'Pc',
                'allow_decimal' => 0,
                'created_by' => $user_id,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $unit_id = $unit->id;

        // -- Categories --
        $categories = [
            ['name' => 'Groceries', 'short_code' => 'GROC', 'parent_id' => 0, 'category_type' => 'product'],
            ['name' => 'Beverages', 'short_code' => 'BEV', 'parent_id' => 0, 'category_type' => 'product'],
            ['name' => 'Electronics', 'short_code' => 'ELEC', 'parent_id' => 0, 'category_type' => 'product'],
            ['name' => 'Household', 'short_code' => 'HOUSE', 'parent_id' => 0, 'category_type' => 'product'],
            ['name' => 'Snacks', 'short_code' => 'SNACK', 'parent_id' => 0, 'category_type' => 'product'],
        ];
        $categoryMap = [];
        foreach ($categories as $cat) {
            $record = Category::firstOrCreate(
                [
                    'name' => $cat['name'],
                    'business_id' => $business_id,
                    'category_type' => $cat['category_type'],
                ],
                array_merge($cat, [
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
            $categoryMap[$cat['name']] = $record->id;
        }

        // -- Brands --
        $brands = [
            ['name' => 'Generic'],
            ['name' => 'Nestle'],
            ['name' => 'PepsiCo'],
            ['name' => 'Coca-Cola'],
            ['name' => 'Samsung'],
            ['name' => 'Unilever'],
        ];
        $brandMap = [];
        foreach ($brands as $b) {
            $record = Brands::firstOrCreate(
                ['name' => $b['name'], 'business_id' => $business_id],
                array_merge($b, [
                    'business_id' => $business_id,
                    'created_by' => $user_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
            );
            $brandMap[$b['name']] = $record->id;
        }

        // -- Sample products data --
        $sampleProducts = [
            ['name' => 'Rice (5kg)', 'category' => 'Groceries', 'brand' => 'Generic', 'sku' => 'RICE-5KG', 'purchase' => 12.00, 'sell' => 15.00, 'stock' => 50],
            ['name' => 'Wheat Flour (1kg)', 'category' => 'Groceries', 'brand' => 'Generic', 'sku' => 'WF-1KG', 'purchase' => 1.50, 'sell' => 2.00, 'stock' => 100],
            ['name' => 'Sugar (1kg)', 'category' => 'Groceries', 'brand' => 'Generic', 'sku' => 'SUG-1KG', 'purchase' => 1.20, 'sell' => 1.50, 'stock' => 80],
            ['name' => 'Cooking Oil (1L)', 'category' => 'Groceries', 'brand' => 'Unilever', 'sku' => 'OIL-1L', 'purchase' => 3.50, 'sell' => 4.50, 'stock' => 60],
            ['name' => 'Salt (500g)', 'category' => 'Groceries', 'brand' => 'Generic', 'sku' => 'SALT-500', 'purchase' => 0.50, 'sell' => 0.75, 'stock' => 120],
            ['name' => 'Coca-Cola (1.5L)', 'category' => 'Beverages', 'brand' => 'Coca-Cola', 'sku' => 'COKE-1.5', 'purchase' => 1.80, 'sell' => 2.50, 'stock' => 40],
            ['name' => 'Pepsi (1.5L)', 'category' => 'Beverages', 'brand' => 'PepsiCo', 'sku' => 'PEPSI-1.5', 'purchase' => 1.80, 'sell' => 2.50, 'stock' => 40],
            ['name' => 'Mineral Water (500ml)', 'category' => 'Beverages', 'brand' => 'Generic', 'sku' => 'WATER-500', 'purchase' => 0.30, 'sell' => 0.50, 'stock' => 200],
            ['name' => 'Orange Juice (1L)', 'category' => 'Beverages', 'brand' => 'Generic', 'sku' => 'OJ-1L', 'purchase' => 2.00, 'sell' => 2.80, 'stock' => 30],
            ['name' => 'USB Cable', 'category' => 'Electronics', 'brand' => 'Generic', 'sku' => 'USB-CABLE', 'purchase' => 3.00, 'sell' => 5.00, 'stock' => 100],
            ['name' => 'Phone Charger', 'category' => 'Electronics', 'brand' => 'Samsung', 'sku' => 'CHARGER-01', 'purchase' => 8.00, 'sell' => 12.00, 'stock' => 50],
            ['name' => 'AA Batteries (Pack 4)', 'category' => 'Electronics', 'brand' => 'Generic', 'sku' => 'AA-BAT-4', 'purchase' => 2.50, 'sell' => 4.00, 'stock' => 75],
            ['name' => 'Dish Soap', 'category' => 'Household', 'brand' => 'Unilever', 'sku' => 'DSH-SOAP', 'purchase' => 1.50, 'sell' => 2.20, 'stock' => 60],
            ['name' => 'Laundry Detergent', 'category' => 'Household', 'brand' => 'Unilever', 'sku' => 'LND-DET', 'purchase' => 5.00, 'sell' => 7.00, 'stock' => 45],
            ['name' => 'Toilet Paper (Roll)', 'category' => 'Household', 'brand' => 'Generic', 'sku' => 'TP-ROLL', 'purchase' => 0.80, 'sell' => 1.20, 'stock' => 150],
            ['name' => 'Potato Chips', 'category' => 'Snacks', 'brand' => 'PepsiCo', 'sku' => 'CHIPS-01', 'purchase' => 1.00, 'sell' => 1.50, 'stock' => 80],
            ['name' => 'Chocolate Bar', 'category' => 'Snacks', 'brand' => 'Nestle', 'sku' => 'CHOC-01', 'purchase' => 1.20, 'sell' => 1.80, 'stock' => 90],
            ['name' => 'Biscuits (Pack)', 'category' => 'Snacks', 'brand' => 'Generic', 'sku' => 'BISC-01', 'purchase' => 1.00, 'sell' => 1.40, 'stock' => 100],
            ['name' => 'Milk (1L)', 'category' => 'Beverages', 'brand' => 'Nestle', 'sku' => 'MILK-1L', 'purchase' => 1.50, 'sell' => 2.00, 'stock' => 40],
            ['name' => 'Tea Bags (Box 25)', 'category' => 'Groceries', 'brand' => 'Generic', 'sku' => 'TEA-25', 'purchase' => 2.00, 'sell' => 3.00, 'stock' => 55],
        ];

        $productUtil = new ProductUtil();

        foreach ($sampleProducts as $item) {
            // Check if product already exists by SKU
            if (Product::where('sku', $item['sku'])->where('business_id', $business_id)->exists()) {
                continue;
            }

            $productData = [
                'name' => $item['name'],
                'business_id' => $business_id,
                'type' => 'single',
                'unit_id' => $unit_id,
                'brand_id' => $brandMap[$item['brand']] ?? null,
                'category_id' => $categoryMap[$item['category']] ?? null,
                'tax' => null,
                'tax_type' => 'exclusive',
                'enable_stock' => 1,
                'alert_quantity' => 10,
                'sku' => $item['sku'],
                'barcode_type' => 'C128',
                'created_by' => $user_id,
                'created_at' => $now,
                'updated_at' => $now,
                'not_for_selling' => 0,
                'product_description' => null,
            ];

            $product = Product::create($productData);

            // Generate SKU if empty (shouldn't be since we provided it)
            if (empty(trim($product->sku))) {
                $product->sku = $productUtil->generateProductSku($product->id);
                $product->save();
            }

            // Create single variation
            $purchase_price = $item['purchase'];
            $profit_percent = 0;
            $selling_price = $item['sell'];
            $tax_percent = 0;
            $purchase_price_inc_tax = $purchase_price + ($purchase_price * $tax_percent / 100);
            $selling_price_inc_tax = $selling_price + ($selling_price * $tax_percent / 100);

            $productUtil->createSingleProductVariation(
                $product->id,
                $product->sku,
                $purchase_price,
                $purchase_price_inc_tax,
                $profit_percent,
                $selling_price,
                $selling_price_inc_tax
            );

            // Refresh product to load variation relationship
            $product = Product::find($product->id);
            $variation = $product->variations->first();

            // Create VariationLocationDetails record
            VariationLocationDetails::create([
                'product_id' => $product->id,
                'product_variation_id' => $variation->product_variation_id,
                'variation_id' => $variation->id,
                'location_id' => $location_id,
                'qty_available' => $item['stock'],
            ]);

            // Create opening stock transaction
            $total = $purchase_price * $item['stock'];
            $transaction = Transaction::create([
                'type' => 'opening_stock',
                'opening_stock_product_id' => $product->id,
                'status' => 'received',
                'business_id' => $business_id,
                'transaction_date' => $now->format('Y-m-d'),
                'total_before_tax' => $total,
                'location_id' => $location_id,
                'final_total' => $total,
                'payment_status' => 'paid',
                'created_by' => $user_id,
            ]);

            // Create purchase line for stock mapping
            PurchaseLine::create([
                'transaction_id' => $transaction->id,
                'product_id' => $product->id,
                'variation_id' => $variation->id,
                'quantity' => $item['stock'],
                'purchase_price' => $purchase_price,
                'purchase_price_inc_tax' => $purchase_price_inc_tax,
                'pp_without_discount' => $purchase_price,
                'item_tax' => 0,
            ]);
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');

        $this->command->info('Sample products seeded successfully!');
    }
}
