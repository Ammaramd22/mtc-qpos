<?php

namespace App\Utils;

use App\Brands;
use App\Category;
use App\PosTradeIn;
use App\Product;
use App\Transaction;
use App\TransactionSellLine;
use App\Unit;
use App\Variation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * POS Exchange / Trade-In: receive old item into stock + credit exchange value on sale.
 * Separate from return → credit-note exchange flow.
 */
class TradeInUtil
{
    public const SKU_PREFIX = 'QPOS-TI-';

    /**
     * Sum exchange values from products[] payload.
     */
    public function sumExchangeValueFromProducts(array $products, ProductUtil $productUtil): float
    {
        if (! (new TyreShopUtil())->moduleEnabled()) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($products as $product) {
            if (empty($product['trade_in']) || ! is_array($product['trade_in'])) {
                continue;
            }
            $ti = $product['trade_in'];
            if (empty($ti['enabled']) && empty($ti['exchange_value'])) {
                continue;
            }
            $qty = ! empty($ti['quantity']) ? (float) $productUtil->num_uf($ti['quantity']) : 1.0;
            $val = ! empty($ti['exchange_value']) ? (float) $productUtil->num_uf($ti['exchange_value']) : 0.0;
            if ($qty <= 0) {
                $qty = 1.0;
            }
            $total += max(0, $val) * $qty;
        }

        return round($total, 4);
    }

    /**
     * Process trade-ins after sell lines are created (final sales only for stock).
     *
     * @param  Transaction  $transaction
     * @param  array  $products  Request products[]
     * @param  int  $business_id
     * @param  int  $user_id
     * @param  bool  $adjust_stock
     * @return float  Total exchange credit applied
     */
    public function processTradeInsForSale(
        Transaction $transaction,
        array $products,
        int $business_id,
        int $user_id,
        bool $adjust_stock = true
    ): float {
        if (! (new TyreShopUtil())->moduleEnabled($business_id)) {
            return 0.0;
        }

        $productUtil = new ProductUtil();
        $sellLines = $transaction->sell_lines()
            ->whereNull('parent_sell_line_id')
            ->orderBy('id')
            ->get()
            ->values();

        $lineIndex = 0;
        $totalCredit = 0.0;

        foreach ($products as $product) {
            // Skip modifiers / combo children in the products payload alignment
            if (! empty($product['parent_sell_line_id'])) {
                continue;
            }

            $sellLine = $sellLines->get($lineIndex);
            $lineIndex++;

            if (empty($product['trade_in']) || ! is_array($product['trade_in'])) {
                continue;
            }

            $ti = $product['trade_in'];
            $exchangeValue = ! empty($ti['exchange_value']) ? (float) $productUtil->num_uf($ti['exchange_value']) : 0.0;
            if ($exchangeValue <= 0 && empty($ti['enabled'])) {
                continue;
            }
            if ($exchangeValue <= 0) {
                continue;
            }

            $qty = ! empty($ti['quantity']) ? (float) $productUtil->num_uf($ti['quantity']) : 1.0;
            if ($qty <= 0) {
                $qty = 1.0;
            }

            $lineCredit = $exchangeValue * $qty;
            $totalCredit += $lineCredit;

            $oldProduct = null;
            $oldVariation = null;
            $openingTxnId = null;

            if ($adjust_stock && $transaction->status === 'final') {
                $resolved = $this->receiveOldItem(
                    $business_id,
                    $user_id,
                    (int) $transaction->location_id,
                    $ti,
                    $exchangeValue,
                    $qty,
                    $productUtil
                );
                $oldProduct = $resolved['product'];
                $oldVariation = $resolved['variation'];
                $openingTxnId = $resolved['opening_stock_transaction_id'];
            }

            $newName = null;
            if ($sellLine) {
                $p = Product::find($sellLine->product_id);
                $newName = $p ? $p->name : null;
            }

            PosTradeIn::create([
                'business_id' => $business_id,
                'transaction_id' => $transaction->id,
                'sell_line_id' => $sellLine ? $sellLine->id : null,
                'location_id' => $transaction->location_id,
                'contact_id' => $transaction->contact_id,
                'created_by' => $user_id,
                'new_product_id' => $sellLine ? $sellLine->product_id : ($product['product_id'] ?? null),
                'new_variation_id' => $sellLine ? $sellLine->variation_id : ($product['variation_id'] ?? null),
                'new_product_name' => $newName,
                'new_unit_price' => $sellLine ? (float) $sellLine->unit_price_inc_tax : 0,
                'new_quantity' => $sellLine ? (float) $sellLine->quantity : (float) ($product['quantity'] ?? 1),
                'old_product_id' => $oldProduct ? $oldProduct->id : null,
                'old_variation_id' => $oldVariation ? $oldVariation->id : null,
                'opening_stock_transaction_id' => $openingTxnId,
                'old_product_name' => $ti['item_name'] ?? ($ti['product_name'] ?? null),
                'brand_name' => $ti['brand_name'] ?? null,
                'brand_id' => ! empty($ti['brand_id']) ? (int) $ti['brand_id'] : null,
                'tyre_size' => $ti['tyre_size'] ?? null,
                'category_id' => ! empty($ti['category_id']) ? (int) $ti['category_id'] : null,
                'sub_category_id' => ! empty($ti['sub_category_id']) ? (int) $ti['sub_category_id'] : null,
                'condition' => $ti['condition'] ?? 'Used',
                'usage_percent' => isset($ti['usage_percent']) && $ti['usage_percent'] !== ''
                    ? (float) $productUtil->num_uf($ti['usage_percent']) : null,
                'barcode' => $ti['barcode'] ?? null,
                'quantity' => $qty,
                'exchange_value' => $lineCredit,
                'notes' => $ti['notes'] ?? null,
                'reason' => $ti['reason'] ?? 'Exchange / Trade-In',
                'status' => 'completed',
            ]);
        }

        return round($totalCredit, 4);
    }

    /**
     * Find or create used item product and increase stock (with opening_stock audit trail).
     *
     * @return array{product: Product, variation: Variation, opening_stock_transaction_id: int|null}
     */
    public function receiveOldItem(
        int $business_id,
        int $user_id,
        int $location_id,
        array $ti,
        float $exchangeValueUnit,
        float $qty,
        ProductUtil $productUtil
    ): array {
        $categoryId = ! empty($ti['category_id']) ? (int) $ti['category_id'] : $this->getOrCreateUsedTyresCategoryId($business_id, $user_id);
        $subCategoryId = ! empty($ti['sub_category_id']) ? (int) $ti['sub_category_id'] : null;
        $brandId = ! empty($ti['brand_id']) ? (int) $ti['brand_id'] : null;
        $brandName = trim((string) ($ti['brand_name'] ?? ''));
        $tyreSize = trim((string) ($ti['tyre_size'] ?? ''));
        $condition = trim((string) ($ti['condition'] ?? 'Used'));
        $usage = isset($ti['usage_percent']) && $ti['usage_percent'] !== ''
            ? (float) $productUtil->num_uf($ti['usage_percent']) : null;

        if ($brandId && $brandName === '') {
            $b = Brands::find($brandId);
            $brandName = $b ? $b->name : '';
        }

        if (! $brandId && $brandName !== '') {
            $brandId = $this->getOrCreateBrandId($business_id, $user_id, $brandName);
        }

        $itemName = trim((string) ($ti['item_name'] ?? $ti['product_name'] ?? ''));
        if ($itemName === '') {
            $itemName = trim(implode(' ', array_filter([
                $brandName,
                $tyreSize,
                $condition,
            ])));
            if ($itemName === '') {
                $itemName = 'Trade-In Item';
            }
            if ($usage !== null) {
                $itemName .= ' '.$usage.'%';
            }
        }

        // Prefer explicit product id; else match stockable used product by category + size + brand
        $product = null;
        if (! empty($ti['old_product_id'])) {
            $product = Product::where('business_id', $business_id)
                ->where('id', (int) $ti['old_product_id'])
                ->first();
        }

        if (! $product && $tyreSize !== '') {
            $q = Product::where('business_id', $business_id)
                ->where('enable_stock', 1)
                ->where('category_id', $categoryId);
            if ($subCategoryId) {
                $q->where('sub_category_id', $subCategoryId);
            }
            if (Schema::hasColumn('products', 'tyre_size')) {
                $q->where('tyre_size', $tyreSize);
            } else {
                $q->where('name', 'like', '%'.$tyreSize.'%');
            }
            if ($brandId) {
                $q->where('brand_id', $brandId);
            }
            $product = $q->orderBy('id')->first();
        }

        if (! $product) {
            $product = $this->createUsedProduct(
                $business_id,
                $user_id,
                $location_id,
                $itemName,
                $categoryId,
                $subCategoryId,
                $brandId,
                $tyreSize,
                $exchangeValueUnit,
                $productUtil,
                $ti['barcode'] ?? null,
                $condition,
                $usage
            );
        } else {
            $product->product_locations()->syncWithoutDetaching([$location_id]);
        }

        $variation = $product->variations()->first();
        if (! $variation) {
            $sku = $product->sku ?: (self::SKU_PREFIX.$product->id);
            $productUtil->createSingleProductVariation(
                $product->id,
                $sku,
                $exchangeValueUnit,
                $exchangeValueUnit,
                0,
                $exchangeValueUnit,
                $exchangeValueUnit
            );
            $variation = $product->fresh()->variations()->first();
        }

        $openingTxnId = null;
        if ($qty > 0 && $variation) {
            // Opening stock line for stock history
            $productUtil->addSingleProductOpeningStock(
                $business_id,
                $product->load('variations', 'product_tax'),
                [
                    $location_id => [
                        'quantity' => $productUtil->num_f($qty),
                        'purchase_price' => $productUtil->num_f($exchangeValueUnit),
                        'exp_date' => null,
                        'lot_number' => 'TRADEIN-'.date('YmdHis'),
                    ],
                ],
                \Carbon::now()->toDateTimeString(),
                $user_id
            );

            $openingTxn = Transaction::where('business_id', $business_id)
                ->where('type', 'opening_stock')
                ->where('opening_stock_product_id', $product->id)
                ->where('location_id', $location_id)
                ->orderBy('id', 'desc')
                ->first();
            $openingTxnId = $openingTxn ? $openingTxn->id : null;
        }

        return [
            'product' => $product,
            'variation' => $variation,
            'opening_stock_transaction_id' => $openingTxnId,
        ];
    }

    protected function createUsedProduct(
        int $business_id,
        int $user_id,
        int $location_id,
        string $name,
        ?int $categoryId,
        ?int $subCategoryId,
        ?int $brandId,
        string $tyreSize,
        float $exchangeValue,
        ProductUtil $productUtil,
        ?string $barcode,
        string $condition,
        $usage
    ): Product {
        $unit = Unit::where('business_id', $business_id)->whereNull('base_unit_id')->first();
        if (empty($unit)) {
            $unit = Unit::create([
                'business_id' => $business_id,
                'actual_name' => 'Pieces',
                'short_name' => 'Pc',
                'allow_decimal' => 0,
                'created_by' => $user_id,
            ]);
        }

        $skuBase = self::SKU_PREFIX.Str::upper(Str::slug(substr($tyreSize ?: $name, 0, 24), ''));
        if ($brandId) {
            $skuBase .= '-B'.$brandId;
        }
        $sku = $skuBase;
        $n = 1;
        while (Product::where('business_id', $business_id)->where('sku', $sku)->exists()) {
            $sku = $skuBase.'-'.$n;
            $n++;
        }

        $data = [
            'name' => $name,
            'business_id' => $business_id,
            'type' => 'single',
            'unit_id' => $unit->id,
            'tax_type' => 'inclusive',
            'enable_stock' => 1,
            'sku' => $barcode ?: $sku,
            'barcode_type' => 'C128',
            'created_by' => $user_id,
            'category_id' => $categoryId,
            'sub_category_id' => $subCategoryId,
            'brand_id' => $brandId,
        ];

        if (Schema::hasColumn('products', 'tyre_size') && $tyreSize !== '') {
            $data['tyre_size'] = $tyreSize;
        }
        if (Schema::hasColumn('products', 'tyre_note')) {
            $noteParts = array_filter([$condition, $usage !== null ? $usage.'% used' : null, 'Trade-In']);
            $data['tyre_note'] = implode(' · ', $noteParts);
        }

        $product = Product::create($data);
        $product->product_locations()->sync([$location_id]);

        $productUtil->createSingleProductVariation(
            $product->id,
            $product->sku,
            $exchangeValue,
            $exchangeValue,
            0,
            $exchangeValue,
            $exchangeValue
        );

        return $product->fresh(['variations']);
    }

    public function getOrCreateUsedTyresCategoryId(int $business_id, int $user_id): int
    {
        $cat = Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->where(function ($q) {
                $q->where('name', 'like', '%Used Tyre%')
                    ->orWhere('name', 'like', '%Used Tire%')
                    ->orWhere('name', 'Used');
            })
            ->orderBy('id')
            ->first();

        if ($cat) {
            return (int) $cat->id;
        }

        $cat = Category::create([
            'name' => 'Used Tyres',
            'business_id' => $business_id,
            'short_code' => 'USED-TYRE',
            'parent_id' => 0,
            'created_by' => $user_id,
            'category_type' => 'product',
        ]);

        return (int) $cat->id;
    }

    public function getOrCreateBrandId(int $business_id, int $user_id, string $name): int
    {
        $brand = Brands::where('business_id', $business_id)
            ->where('name', $name)
            ->first();
        if ($brand) {
            return (int) $brand->id;
        }

        $brand = Brands::create([
            'business_id' => $business_id,
            'name' => $name,
            'created_by' => $user_id,
        ]);

        return (int) $brand->id;
    }

    /**
     * Reverse stock for trade-ins when a sale is deleted/voided.
     */
    public function reverseTradeInsForSale(Transaction $transaction): void
    {
        $productUtil = new ProductUtil();
        $rows = PosTradeIn::where('transaction_id', $transaction->id)
            ->where('status', 'completed')
            ->get();

        foreach ($rows as $row) {
            if ($row->old_product_id && $row->old_variation_id && $row->quantity > 0) {
                try {
                    $productUtil->decreaseProductQuantity(
                        $row->old_product_id,
                        $row->old_variation_id,
                        $row->location_id,
                        $row->quantity
                    );
                } catch (\Exception $e) {
                    \Log::warning('Trade-in stock reverse failed: '.$e->getMessage());
                }
            }
            $row->status = 'reversed';
            $row->save();
        }
    }

    /**
     * Dropdown data for POS trade-in modal.
     */
    public function getFormOptions(int $business_id, int $user_id): array
    {
        $categories = Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', 0)
            ->orderBy('name')
            ->get(['id', 'name']);

        $usedId = $this->getOrCreateUsedTyresCategoryId($business_id, $user_id);

        $subcategories = Category::where('business_id', $business_id)
            ->where('category_type', 'product')
            ->where('parent_id', '>', 0)
            ->orderBy('name')
            ->get(['id', 'name', 'parent_id']);

        $brands = Brands::where('business_id', $business_id)
            ->orderBy('name')
            ->get(['id', 'name']);

        return [
            'categories' => $categories,
            'subcategories' => $subcategories,
            'brands' => $brands,
            'default_category_id' => $usedId,
            'conditions' => [
                'Used',
                'Returned / Exchange',
                'Defective',
                'Warranty Return',
                'Good',
                'Fair',
                'Poor',
            ],
        ];
    }
}
