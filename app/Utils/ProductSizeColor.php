<?php

namespace App\Utils;

use App\Product;
use App\ProductColor;
use App\Size;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Product-level sizes and colours.
 *
 * Sizes are a business catalog (many products, many sizes).
 * Colours are free-text tags stored per product.
 * products.size and products.color stay as a comma-separated copy so
 * product list, POS and labels keep reading the same columns.
 * This does not create stock variations and does not change quantities or prices.
 */
class ProductSizeColor
{
    public static function tablesReady(): bool
    {
        return Schema::hasTable('sizes')
            && Schema::hasTable('product_size')
            && Schema::hasTable('product_colors');
    }

    public static function sellLineColumnsReady(): bool
    {
        static $ready = null;
        if ($ready === null) {
            $ready = Schema::hasTable('transaction_sell_lines')
                && Schema::hasColumn('transaction_sell_lines', 'line_size')
                && Schema::hasColumn('transaction_sell_lines', 'line_color');
        }

        return $ready;
    }

    /**
     * Split a stored "S, M, L" string into unique names, preserving order.
     *
     * @param  mixed  $value
     * @return array<int, string>
     */
    public static function splitList($value): array
    {
        $value = trim((string) ($value ?? ''));
        if ($value === '') {
            return [];
        }

        $parts = preg_split('/\s*,\s*/u', $value) ?: [];
        $out = [];
        $seen = [];
        foreach ($parts as $part) {
            $part = self::cleanName($part);
            if ($part === null) {
                continue;
            }
            $key = mb_strtolower($part);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $part;
        }

        return $out;
    }

    /**
     * @param  array<int, string>  $names
     */
    public static function joinList(array $names): ?string
    {
        $clean = [];
        foreach ($names as $name) {
            $name = self::cleanName($name);
            if ($name !== null) {
                $clean[] = $name;
            }
        }

        return empty($clean) ? null : implode(', ', $clean);
    }

    /**
     * @param  mixed  $value
     */
    public static function cleanName($value): ?string
    {
        $value = preg_replace('/\s+/u', ' ', trim((string) ($value ?? '')));
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 150);
    }

    /**
     * Case-insensitive duplicate colour names inside one submission.
     *
     * @param  array<int, mixed>  $colors
     */
    public static function duplicateColor(array $colors): ?string
    {
        $seen = [];
        foreach ($colors as $color) {
            $name = self::cleanName($color);
            if ($name === null) {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                return $name;
            }
            $seen[$key] = true;
        }

        return null;
    }

    /**
     * Write size and colour onto the product attribute array before insert/update.
     *
     * @param  array<string, mixed>  $product_details
     */
    public static function assignColumns(array &$product_details, Request $request, int $businessId): void
    {
        $hasSize = Schema::hasColumn('products', 'size');
        $hasColor = Schema::hasColumn('products', 'color');

        if ($request->input('size_color_managed') && self::tablesReady()) {
            $sizeNames = self::namesForSizeIds($businessId, (array) $request->input('size_ids', []));
            $colorNames = self::uniqueNames((array) $request->input('colors', []));
            if ($hasSize) {
                $product_details['size'] = self::joinList($sizeNames);
            } else {
                unset($product_details['size']);
            }
            if ($hasColor) {
                $product_details['color'] = self::joinList($colorNames);
            } else {
                unset($product_details['color']);
            }

            return;
        }

        if ($hasSize && $request->exists('size')) {
            $product_details['size'] = self::joinList(self::splitList($request->input('size')));
        } elseif (! $hasSize) {
            unset($product_details['size']);
        }

        if ($hasColor && $request->exists('color')) {
            $product_details['color'] = self::joinList(self::splitList($request->input('color')));
        } elseif (! $hasColor) {
            unset($product_details['color']);
        }
    }

    /**
     * Save the many-to-many sizes and colour rows, then refresh the text columns.
     */
    public static function persist(Product $product, Request $request, int $businessId, ?int $userId = null): void
    {
        if (! self::tablesReady()) {
            return;
        }

        if ($request->input('size_color_managed')) {
            self::sync(
                $product,
                (array) $request->input('size_ids', []),
                (array) $request->input('colors', []),
                $businessId,
                $userId
            );

            return;
        }

        if ($request->exists('size') || $request->exists('color')) {
            self::syncFromNames(
                $product,
                self::splitList($request->input('size')),
                self::splitList($request->input('color')),
                $businessId,
                $userId
            );
        }
    }

    /**
     * Import path: the product row already has size and color text.
     */
    public static function syncFromLegacyColumns(Product $product, ?int $userId = null): void
    {
        if (! self::tablesReady()) {
            return;
        }

        $sizeNames = Schema::hasColumn('products', 'size') ? self::splitList($product->size) : [];
        $colorNames = Schema::hasColumn('products', 'color') ? self::splitList($product->color) : [];
        if (empty($sizeNames) && empty($colorNames)) {
            return;
        }

        self::syncFromNames($product, $sizeNames, $colorNames, (int) $product->business_id, $userId);
    }

    /**
     * @param  array<int, mixed>  $sizeIds
     * @param  array<int, mixed>  $colorNames
     */
    public static function sync(Product $product, array $sizeIds, array $colorNames, int $businessId, ?int $userId = null): void
    {
        $orderedIds = [];
        foreach ($sizeIds as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $orderedIds, true)) {
                $orderedIds[] = $id;
            }
        }

        $valid = empty($orderedIds)
            ? collect()
            : Size::where('business_id', $businessId)->whereIn('id', $orderedIds)->pluck('name', 'id');

        $sync = [];
        $names = [];
        $position = 0;
        foreach ($orderedIds as $id) {
            if (! isset($valid[$id])) {
                continue;
            }
            $sync[$id] = ['position' => $position];
            $names[] = $valid[$id];
            $position++;
        }

        $product->sizes()->sync($sync);
        self::replaceColors($product, $colorNames, $businessId);
        self::writeDenormalized($product, $names, self::uniqueNames($colorNames));
    }

    /**
     * @param  array<int, string>  $sizeNames
     * @param  array<int, string>  $colorNames
     */
    public static function syncFromNames(Product $product, array $sizeNames, array $colorNames, int $businessId, ?int $userId = null): void
    {
        $ids = [];
        foreach (self::uniqueNames($sizeNames) as $name) {
            $ids[] = self::findOrCreateSize($businessId, $name, $userId)->id;
        }

        self::sync($product, $ids, $colorNames, $businessId, $userId);
    }

    public static function findOrCreateSize(int $businessId, string $name, ?int $userId = null): Size
    {
        $name = self::cleanName($name);
        $existing = Size::where('business_id', $businessId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $name)])
            ->first();
        if ($existing) {
            return $existing;
        }

        return Size::create([
            'business_id' => $businessId,
            'name' => $name,
            'created_by' => $userId,
        ]);
    }

    public static function nameTaken(int $businessId, string $name, ?int $ignoreId = null): bool
    {
        $query = Size::where('business_id', $businessId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower(trim($name))]);
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Rebuild products.size for every product that uses this size.
     */
    public static function refreshProductsForSize(Size $size): void
    {
        if (! Schema::hasColumn('products', 'size')) {
            return;
        }

        $productIds = DB::table('product_size')->where('size_id', $size->id)->pluck('product_id');
        foreach ($productIds as $productId) {
            $names = DB::table('product_size')
                ->join('sizes', 'sizes.id', '=', 'product_size.size_id')
                ->where('product_size.product_id', $productId)
                ->orderBy('product_size.position')
                ->orderBy('sizes.name')
                ->pluck('sizes.name')
                ->all();
            DB::table('products')->where('id', $productId)->update([
                'size' => self::joinList($names),
            ]);
        }
    }

    /**
     * Copy the cashier's size/colour choice onto a new sell line.
     * Quantity and price are left untouched.
     *
     * @param  array<string, mixed>  $line
     * @param  array<string, mixed>  $input
     */
    public static function mergeSellLineSelection(array &$line, array $input): void
    {
        if (! self::sellLineColumnsReady()) {
            return;
        }

        $line['line_size'] = self::cleanName($input['line_size'] ?? null);
        $line['line_color'] = self::cleanName($input['line_color'] ?? null);
    }

    /**
     * @param  \App\TransactionSellLine  $sellLine
     * @param  array<string, mixed>  $input
     */
    public static function applySelectionToSellLine($sellLine, array $input): void
    {
        if (! self::sellLineColumnsReady()) {
            return;
        }

        $sellLine->line_size = self::cleanName($input['line_size'] ?? null);
        $sellLine->line_color = self::cleanName($input['line_color'] ?? null);
    }

    /**
     * Sizes and colours actually assigned to these products.
     * Missing tables return empty lists so the label page can fall back to the text columns.
     *
     * @param  array<int, mixed>  $productIds
     * @return array<int, array{sizes: array<int, string>, colors: array<int, string>}>
     */
    public static function listsForProducts(int $businessId, array $productIds): array
    {
        $ids = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        $out = [];
        foreach ($ids as $id) {
            $out[$id] = ['sizes' => [], 'colors' => []];
        }
        if ($businessId < 1 || $ids === []) {
            return $out;
        }

        $idList = array_values($ids);
        try {
            $rows = DB::table('product_size')
                ->join('sizes', 'sizes.id', '=', 'product_size.size_id')
                ->where('sizes.business_id', $businessId)
                ->whereIn('product_size.product_id', $idList)
                ->orderBy('product_size.position')
                ->orderBy('sizes.name')
                ->get(['product_size.product_id as product_id', 'sizes.name as name']);
            foreach ($rows as $row) {
                $pid = (int) $row->product_id;
                $name = self::cleanName($row->name ?? '');
                if ($name !== null && isset($out[$pid])) {
                    $out[$pid]['sizes'][] = $name;
                }
            }
        } catch (\Throwable $e) {
        }

        try {
            $rows = DB::table('product_colors')
                ->where('business_id', $businessId)
                ->whereIn('product_id', $idList)
                ->orderBy('id')
                ->get(['product_id', 'name']);
            foreach ($rows as $row) {
                $pid = (int) $row->product_id;
                $name = self::cleanName($row->name ?? '');
                if ($name !== null && isset($out[$pid])) {
                    $out[$pid]['colors'][] = $name;
                }
            }
        } catch (\Throwable $e) {
        }

        foreach ($out as $pid => $lists) {
            $out[$pid]['sizes'] = self::uniqueNames($lists['sizes']);
            $out[$pid]['colors'] = self::uniqueNames($lists['colors']);
        }

        return $out;
    }

    /**
     * Catalog rows win. The comma-separated product text is only a fallback.
     *
     * @param  array<int, string>  $fromCatalog
     * @param  mixed  $legacyText
     * @return array<int, string>
     */
    public static function optionList(array $fromCatalog, $legacyText): array
    {
        $catalog = self::uniqueNames($fromCatalog);
        if ($catalog !== []) {
            return $catalog;
        }

        return self::splitList($legacyText);
    }

    /**
     * One chosen value for a label. A joined list such as "Small, Medium, Large"
     * is not a selection, so the first assigned value is used instead.
     *
     * @param  array<int, mixed>  $options
     * @param  mixed  $requested
     */
    public static function chooseOne(array $options, $requested): string
    {
        $clean = self::uniqueNames($options);
        if ($clean === []) {
            return '';
        }

        $requested = self::cleanName($requested);
        if ($requested !== null) {
            $needle = mb_strtolower($requested);
            foreach ($clean as $option) {
                if (mb_strtolower($option) === $needle) {
                    return $option;
                }
            }
        }

        return $clean[0];
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int, string>
     */
    public static function namesForSizeIds(int $businessId, array $ids): array
    {
        $ordered = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && ! in_array($id, $ordered, true)) {
                $ordered[] = $id;
            }
        }
        if (empty($ordered)) {
            return [];
        }

        $valid = Size::where('business_id', $businessId)->whereIn('id', $ordered)->pluck('name', 'id');
        $names = [];
        foreach ($ordered as $id) {
            if (isset($valid[$id])) {
                $names[] = $valid[$id];
            }
        }

        return $names;
    }

    /**
     * @param  array<int, mixed>  $names
     * @return array<int, string>
     */
    public static function uniqueNames(array $names): array
    {
        $out = [];
        $seen = [];
        foreach ($names as $name) {
            $name = self::cleanName($name);
            if ($name === null) {
                continue;
            }
            $key = mb_strtolower($name);
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $out[] = $name;
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $colorNames
     */
    protected static function replaceColors(Product $product, array $colorNames, int $businessId): void
    {
        ProductColor::where('product_id', $product->id)->delete();
        foreach (self::uniqueNames($colorNames) as $name) {
            ProductColor::create([
                'business_id' => $businessId,
                'product_id' => $product->id,
                'name' => $name,
                'name_key' => mb_strtolower($name),
            ]);
        }
    }

    /**
     * @param  array<int, string>  $sizeNames
     * @param  array<int, string>  $colorNames
     */
    protected static function writeDenormalized(Product $product, array $sizeNames, array $colorNames): void
    {
        $dirty = false;
        if (Schema::hasColumn('products', 'size')) {
            $product->size = self::joinList($sizeNames);
            $dirty = true;
        }
        if (Schema::hasColumn('products', 'color')) {
            $product->color = self::joinList($colorNames);
            $dirty = true;
        }
        if ($dirty) {
            $product->save();
        }
    }
}
