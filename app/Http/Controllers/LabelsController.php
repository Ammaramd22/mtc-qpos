<?php

namespace App\Http\Controllers;

use App\Barcode;
use App\Business;
use App\Product;
use App\SellingPriceGroup;
use App\Utils\BrowserLabelLayout;
use App\Utils\LabelProductSearch;
use App\Utils\ProductSizeColor;
use App\Utils\ProductUtil;
use App\Utils\TransactionUtil;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class LabelsController extends Controller
{
    /**
     * All Utils instance.
     */
    protected $transactionUtil;

    protected $productUtil;

    /**
     * Column names from SHOW COLUMNS, cached for this request.
     * information_schema (used by Schema::hasColumn) is empty on some cPanel accounts.
     *
     * @var array<string, bool>|null
     */
    protected $productColumnCache = null;

    /**
     * @var array<string, bool>
     */
    protected $tableExistsCache = [];

    /**
     * Constructor
     *
     * @param  TransactionUtil  $TransactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, ProductUtil $productUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->productUtil = $productUtil;
    }

    /**
     * Display labels
     *
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');
        $purchase_id = $request->get('purchase_id', false);
        $product_id = $request->get('product_id', false);

        //Get products for the business
        $products = [];
        $price_groups = [];
        if ($purchase_id) {
            $products = $this->transactionUtil->getPurchaseProducts($business_id, $purchase_id);
        } elseif ($product_id) {
            $products = $this->productUtil->getDetailsFromProduct($business_id, $product_id);
        }
        if (! empty($products)) {
            $products = $this->attachSizeColor($products, $business_id);
        }

        //get price groups
        $price_groups = [];
        if (! empty($purchase_id) || ! empty($product_id)) {
            $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                    ->active()
                                    ->pluck('name', 'id');
        }

        // Bartender settings
        $biz = \App\Business::find($business_id);
        $common_settings = ! empty($biz->common_settings) ? $biz->common_settings : [];
        $enable_bartender = false;
        $bartender_batch_launcher_url = ! empty($common_settings['bartender_batch_launcher_url']) ? $common_settings['bartender_batch_launcher_url'] : '';
        $browser_label_layout = ! empty($common_settings['browser_label_layout']) && is_array($common_settings['browser_label_layout'])
            ? $common_settings['browser_label_layout']
            : [];

        return view('labels.show')
            ->with(compact('products', 'price_groups', 'enable_bartender', 'bartender_batch_launcher_url', 'browser_label_layout'));
    }

    /**
     * Returns the html for product row
     *
     * @return \Illuminate\Http\Response
     */
    public function addProductRow(Request $request)
    {
        if ($request->ajax()) {
            $product_id = $request->input('product_id');
            $variation_id = $request->input('variation_id');
            $business_id = $request->session()->get('user.business_id');

            if (! empty($product_id)) {
                $index = $request->input('row_count');
                $products = $this->productUtil->getDetailsFromProduct($business_id, $product_id, $variation_id);
                $products = $this->attachSizeColor(
                    $products,
                    $business_id,
                    (string) $request->input('label_size', ''),
                    (string) $request->input('label_color', '')
                );

                $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                            ->active()
                                            ->pluck('name', 'id');

                return view('labels.partials.show_table_rows')
                        ->with(compact('products', 'index', 'price_groups'));
            }
        }
    }

    /**
     * Suggestions for Print Labels. A product is added only after an explicit selection.
     *
     * The product list and this search use the same catalog: every non-modifier product
     * that has a variation, including inactive products and products with no stock.
     * Results are one row per product. The dropdown cap is not a page of the product list.
     */
    public function searchProducts(Request $request)
    {
        if (! auth()->check()) {
            return response()->json(['results' => [], 'truncated' => false]);
        }

        $term = mb_substr(trim((string) $request->input('term', '')), 0, 80);
        if (mb_strlen($term) < 2) {
            return response()->json(['results' => [], 'truncated' => false]);
        }

        $business_id = (int) $request->session()->get('user.business_id');
        if ($business_id < 1) {
            return response()->json(['results' => [], 'truncated' => false]);
        }

        try {
            return response()->json($this->labelProductSuggestions($business_id, $term));
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'results' => [],
                'truncated' => false,
                'error' => true,
            ], 500);
        }
    }

    /**
     * @return array{results: array<int, array<string, mixed>>, truncated: bool}
     */
    protected function labelProductSuggestions(int $businessId, string $term): array
    {
        $tokens = LabelProductSearch::tokens($term);
        if ($tokens === []) {
            return ['results' => [], 'truncated' => false];
        }

        $hasTyre = $this->productsHaveColumn('tyre_size');
        $query = Product::query()
            ->where('products.business_id', $businessId)
            ->where('products.type', '!=', 'modifier')
            ->whereExists(function ($sub) {
                $sub->select(DB::raw('1'))
                    ->from('variations')
                    ->whereColumn('variations.product_id', 'products.id')
                    ->whereNull('variations.deleted_at');
            });

        $this->limitLabelSearchToPermittedLocations($query);

        foreach ($tokens as $token) {
            $query->where(function ($q) use ($token, $businessId) {
                $this->applyLabelSearchToken($q, $token, $businessId);
            });
        }

        $select = [
            'products.id',
            'products.name',
            'products.sku',
            'products.is_inactive',
        ];
        if ($hasTyre) {
            $select[] = 'products.tyre_size';
        }

        $cap = LabelProductSearch::SUGGESTION_CAP;
        $rank = $this->labelSearchRankSql($term, $businessId);
        $rows = $query->select($select)
            ->orderByRaw($rank['sql'], $rank['bindings'])
            ->orderBy('products.name')
            ->orderBy('products.id')
            ->limit($cap + 1)
            ->get();

        $truncated = $rows->count() > $cap;
        if ($truncated) {
            $rows = $rows->slice(0, $cap)->values();
        }

        $ids = [];
        foreach ($rows as $row) {
            $ids[] = (int) $row->id;
        }
        $assigned = $this->loadAssignedAttributes($businessId, $ids);
        $variations = $this->labelSearchVariations($ids);

        $result = [];
        $seen = [];
        foreach ($rows as $row) {
            $productId = (int) $row->id;
            if (isset($seen[$productId])) {
                continue;
            }
            $seen[$productId] = true;

            $productVariations = $variations[$productId] ?? [];
            $chosen = $this->chooseLabelSearchVariation($productVariations, $term, $tokens);
            if ($chosen === null) {
                continue;
            }

            $attrs = $assigned[$productId] ?? ['sizes' => [], 'colors' => []];
            $sizes = array_values($attrs['sizes'] ?? []);
            $colors = array_values($attrs['colors'] ?? []);
            $matchSize = LabelProductSearch::matchedValue($sizes, $tokens, false);
            $matchColor = LabelProductSearch::matchedValue($colors, $tokens, true);
            $name = Product::formatNameWithTyreSize($row->name, $hasTyre ? ($row->tyre_size ?? null) : null);
            $sku = trim((string) ($row->sku ?? ''));
            $barcode = trim((string) ($chosen->sub_sku ?? ''));
            if ($barcode === '') {
                $barcode = $sku;
            }

            $variationLabel = '';
            $variationName = trim((string) ($chosen->name ?? ''));
            $isDummy = (int) ($chosen->is_dummy ?? 1) === 1 || strtoupper($variationName) === 'DUMMY';
            if (! $isDummy && $variationName !== '') {
                $template = trim((string) ($chosen->template_name ?? ''));
                $variationLabel = $template !== '' ? ($template.': '.$variationName) : $variationName;
            }

            $result[] = [
                'label' => $name,
                'value' => $name,
                'product_id' => $productId,
                'variation_id' => (int) $chosen->id,
                'name' => $name,
                'sku' => $sku,
                'barcode' => $barcode,
                'sizes' => $sizes,
                'colors' => $colors,
                'size' => $matchSize ?? '',
                'color' => $matchColor ?? '',
                'match_size' => $matchSize ?? '',
                'match_color' => $matchColor ?? '',
                'inactive' => (int) ($row->is_inactive ?? 0) === 1,
                'variation' => $variationLabel,
            ];
        }

        return ['results' => $result, 'truncated' => $truncated];
    }

    /**
     * Same location rule as Products > List Products.
     * Stock rows are not required, so zero-stock products stay searchable.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function limitLabelSearchToPermittedLocations($query): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'permitted_locations')) {
            return;
        }

        $permitted = $user->permitted_locations();
        if ($permitted == 'all') {
            return;
        }

        $locationIds = [];
        foreach ((array) $permitted as $locationId) {
            $locationId = (int) $locationId;
            if ($locationId > 0) {
                $locationIds[] = $locationId;
            }
        }
        if ($locationIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereExists(function ($sub) use ($locationIds) {
            $sub->select(DB::raw('1'))
                ->from('product_locations')
                ->whereColumn('product_locations.product_id', 'products.id')
                ->whereIn('product_locations.location_id', $locationIds);
        });
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     */
    protected function applyLabelSearchToken($query, string $token, int $businessId): void
    {
        $like = '%'.LabelProductSearch::escapeLike($token).'%';
        $numeric = LabelProductSearch::isNumericToken($token);

        if (LabelProductSearch::tokenMayMatchName($token)) {
            $query->where('products.name', 'like', $like);
        } else {
            $query->where('products.sku', 'like', $like);
        }

        if (LabelProductSearch::tokenMayMatchName($token)) {
            $query->orWhere('products.sku', 'like', $like);
        }

        if ($numeric) {
            $stripped = ltrim($token, '0');
            if ($stripped === '') {
                $stripped = '0';
            }
            $query->orWhereRaw("TRIM(LEADING '0' FROM products.sku) = ?", [$stripped]);
        }

        $query->orWhereExists(function ($sub) use ($like, $numeric, $token) {
            $sub->select(DB::raw('1'))
                ->from('variations as v')
                ->whereColumn('v.product_id', 'products.id')
                ->whereNull('v.deleted_at')
                ->where(function ($w) use ($like, $numeric, $token) {
                    $w->where('v.sub_sku', 'like', $like);
                    if ($numeric) {
                        $stripped = ltrim($token, '0');
                        if ($stripped === '') {
                            $stripped = '0';
                        }
                        $w->orWhereRaw("TRIM(LEADING '0' FROM v.sub_sku) = ?", [$stripped]);
                    }
                });
        });

        $sizeSql = $this->labelSizeMatchSql($token, $businessId);
        if ($sizeSql !== null) {
            $query->orWhereRaw($sizeSql['sql'], $sizeSql['bindings']);
        }

        $colorSql = $this->labelColorMatchSql($token, $businessId);
        if ($colorSql !== null) {
            $query->orWhereRaw($colorSql['sql'], $colorSql['bindings']);
        }

        if (! $numeric && $this->productsHaveColumn('tyre_size')) {
            $query->orWhere('products.tyre_size', 'like', $like);
        }
    }

    /**
     * @return array{sql: string, bindings: array<int, mixed>}
     */
    protected function labelSearchRankSql(string $term, int $businessId): array
    {
        $bindings = [$term, $term];
        $sizeRank = '0';
        $sizeSql = $this->labelSizeMatchSql($term, $businessId);
        if ($sizeSql !== null && LabelProductSearch::isNumericToken($term)) {
            $sizeRank = $sizeSql['sql'];
            $bindings = array_merge($bindings, $sizeSql['bindings']);
        }
        $prefix = LabelProductSearch::escapeLike($term).'%';
        $bindings[] = $prefix;

        return [
            'sql' => 'CASE
                WHEN products.sku = ? OR EXISTS (
                    SELECT 1 FROM variations AS vrank
                    WHERE vrank.product_id = products.id
                      AND vrank.deleted_at IS NULL
                      AND vrank.sub_sku = ?
                ) THEN 0
                WHEN '.$sizeRank.' THEN 1
                WHEN products.name LIKE ? THEN 2
                ELSE 3
            END',
            'bindings' => $bindings,
        ];
    }

    /**
     * Exact assigned size. A stored "154" or "540" does not match "54".
     *
     * @return array{sql: string, bindings: array<int, mixed>}|null
     */
    protected function labelSizeMatchSql(string $token, int $businessId): ?array
    {
        $parts = [];
        $bindings = [];
        $needle = mb_strtolower(trim($token));
        if ($needle === '') {
            return null;
        }

        if ($this->productsHaveColumn('size')) {
            $parts[] = $this->commaListSql('products.size').' LIKE ?';
            $bindings[] = '%,'.LabelProductSearch::escapeLike($needle).',%';
        }
        if ($this->productsHaveColumn('hw_size')) {
            $parts[] = $this->commaListSql('products.hw_size').' LIKE ?';
            $bindings[] = '%,'.LabelProductSearch::escapeLike($needle).',%';
        }
        if ($this->databaseTableExists('product_size') && $this->databaseTableExists('sizes')) {
            $parts[] = 'EXISTS (
                SELECT 1 FROM product_size AS ps
                INNER JOIN sizes AS sz ON sz.id = ps.size_id
                WHERE ps.product_id = products.id
                  AND sz.business_id = ?
                  AND LOWER(sz.name) = ?
            )';
            $bindings[] = $businessId;
            $bindings[] = $needle;
        }
        if ($this->databaseTableExists('variations') && $this->databaseTableExists('product_variations')) {
            $names = self::attributeTemplateNames('size');
            $placeholders = implode(',', array_fill(0, count($names), '?'));
            $parts[] = "EXISTS (
                SELECT 1 FROM variations AS vsz
                INNER JOIN product_variations AS pvsz ON pvsz.id = vsz.product_variation_id
                WHERE vsz.product_id = products.id
                  AND vsz.deleted_at IS NULL
                  AND LOWER(TRIM(vsz.name)) = ?
                  AND LOWER(TRIM(pvsz.name)) IN ({$placeholders})
            )";
            $bindings[] = $needle;
            foreach ($names as $name) {
                $bindings[] = $name;
            }
        }

        if ($parts === []) {
            return null;
        }

        return ['sql' => '('.implode(' OR ', $parts).')', 'bindings' => $bindings];
    }

    /**
     * Assigned color, exact or prefix. Does not scan the product description.
     *
     * @return array{sql: string, bindings: array<int, mixed>}|null
     */
    protected function labelColorMatchSql(string $token, int $businessId): ?array
    {
        $parts = [];
        $bindings = [];
        $needle = mb_strtolower(trim($token));
        if ($needle === '') {
            return null;
        }

        $numeric = LabelProductSearch::isNumericToken($token);
        $escaped = LabelProductSearch::escapeLike($needle);
        if ($this->productsHaveColumn('color')) {
            if ($numeric) {
                $parts[] = $this->commaListSql('products.color').' LIKE ?';
                $bindings[] = '%,'.$escaped.',%';
            } else {
                $parts[] = $this->commaListSql('products.color').' REGEXP ?';
                $bindings[] = ','.self::regexFragment($needle).'[^,]*,';
            }
        }
        if ($this->productsHaveColumn('hw_colour')) {
            if ($numeric) {
                $parts[] = $this->commaListSql('products.hw_colour').' LIKE ?';
                $bindings[] = '%,'.$escaped.',%';
            } else {
                $parts[] = $this->commaListSql('products.hw_colour').' REGEXP ?';
                $bindings[] = ','.self::regexFragment($needle).'[^,]*,';
            }
        }
        if ($this->databaseTableExists('product_color') && $this->databaseTableExists('colors')) {
            $parts[] = 'EXISTS (
                SELECT 1 FROM product_color AS pc
                INNER JOIN colors AS cl ON cl.id = pc.color_id
                WHERE pc.product_id = products.id
                  AND cl.business_id = ?
                  AND (LOWER(cl.name) = ? OR LOWER(cl.name) LIKE ?)
            )';
            $bindings[] = $businessId;
            $bindings[] = $needle;
            $bindings[] = $numeric ? $needle : ($escaped.'%');
        }
        if ($this->databaseTableExists('product_colors')) {
            $parts[] = 'EXISTS (
                SELECT 1 FROM product_colors AS pcl
                WHERE pcl.product_id = products.id
                  AND pcl.business_id = ?
                  AND (LOWER(pcl.name) = ? OR LOWER(pcl.name) LIKE ?)
            )';
            $bindings[] = $businessId;
            $bindings[] = $needle;
            $bindings[] = $numeric ? $needle : ($escaped.'%');
        }
        if ($this->databaseTableExists('variations') && $this->databaseTableExists('product_variations')) {
            $names = self::attributeTemplateNames('color');
            $placeholders = implode(',', array_fill(0, count($names), '?'));
            $parts[] = "EXISTS (
                SELECT 1 FROM variations AS vcl
                INNER JOIN product_variations AS pvcl ON pvcl.id = vcl.product_variation_id
                WHERE vcl.product_id = products.id
                  AND vcl.deleted_at IS NULL
                  AND (LOWER(TRIM(vcl.name)) = ? OR LOWER(TRIM(vcl.name)) LIKE ?)
                  AND LOWER(TRIM(pvcl.name)) IN ({$placeholders})
            )";
            $bindings[] = $needle;
            $bindings[] = $numeric ? $needle : ($escaped.'%');
            foreach ($names as $name) {
                $bindings[] = $name;
            }
        }

        if ($parts === []) {
            return null;
        }

        return ['sql' => '('.implode(' OR ', $parts).')', 'bindings' => $bindings];
    }

    /**
     * Normalized ",54,56,58," so token boundaries stay exact.
     */
    protected function commaListSql(string $column): string
    {
        $expr = "LOWER(IFNULL({$column}, ''))";
        $expr = "REPLACE(REPLACE(REPLACE(REPLACE({$expr}, ', ', ','), ', ', ','), ' ,', ','), ' ,', ',')";

        return "CONCAT(',', {$expr}, ',')";
    }

    protected static function regexFragment(string $value): string
    {
        return preg_quote(mb_strtolower($value), '/');
    }

    /**
     * @param  array<int, int>  $productIds
     * @return array<int, array<int, object>>
     */
    protected function labelSearchVariations(array $productIds): array
    {
        $ids = [];
        foreach ($productIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return [];
        }

        $rows = DB::table('variations as v')
            ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
            ->whereIn('v.product_id', array_values($ids))
            ->whereNull('v.deleted_at')
            ->orderBy('v.id')
            ->get([
                'v.id',
                'v.product_id',
                'v.name',
                'v.sub_sku',
                'pv.name as template_name',
                'pv.is_dummy',
            ]);

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row->product_id][] = $row;
        }

        return $grouped;
    }

    /**
     * @param  array<int, object>  $variations
     * @param  array<int, string>  $tokens
     */
    protected function chooseLabelSearchVariation(array $variations, string $term, array $tokens)
    {
        $exact = null;
        $dummy = null;
        $first = null;
        $termKey = mb_strtolower(trim($term));
        $tokenKeys = [];
        foreach ($tokens as $token) {
            $tokenKeys[mb_strtolower($token)] = true;
        }

        foreach ($variations as $variation) {
            if ($first === null) {
                $first = $variation;
            }
            $name = trim((string) ($variation->name ?? ''));
            if ((int) ($variation->is_dummy ?? 0) === 1 || strtoupper($name) === 'DUMMY') {
                if ($dummy === null) {
                    $dummy = $variation;
                }
            }
            $sku = mb_strtolower(trim((string) ($variation->sub_sku ?? '')));
            if ($sku !== '' && ($sku === $termKey || isset($tokenKeys[$sku]))) {
                $exact = $variation;
            }
        }

        return $exact ?? $dummy ?? $first;
    }

    /**
     * Export selected products as a Bartender-compatible CSV
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function exportBartenderCsv(Request $request)
    {
        $products = $request->get('products');
        $business_id = $request->session()->get('user.business_id');

        $biz = \App\Business::find($business_id);
        $common_settings = ! empty($biz->common_settings) ? $biz->common_settings : [];
        $delimiter = ! empty($common_settings['bartender_delimiter']) ? $common_settings['bartender_delimiter'] : ',';
        $encoding = ! empty($common_settings['bartender_encoding']) ? $common_settings['bartender_encoding'] : 'UTF-8-BOM';
        $fields = $this->normalizeBartenderFieldList(
            $common_settings['bartender_fields'] ?? null,
            ['product_name', 'sku', 'barcode', 'barcode_value', 'price']
        );
        $customFields = $this->normalizeBartenderFieldList(
            $common_settings['bartender_custom_fields'] ?? null,
            []
        );
        // Always include Cost Code + Supplier Code columns for label CSV when not already selected
        foreach (['product_custom_field1', 'product_custom_field2'] as $mustCf) {
            if (! in_array($mustCf, $customFields, true)) {
                $customFields[] = $mustCf;
            }
        }

        $customLabels = json_decode($biz->custom_labels, true);
        $productCustomLabels = ! empty($customLabels['product']) ? $customLabels['product'] : [];

        $headers = [];
        foreach ($fields as $f) {
            if ($f === 'supplier') {
                $headers[] = __('lang_v1.supplier');
            } else {
                $headers[] = ucwords(str_replace('_', ' ', $f));
            }
        }
        foreach ($customFields as $cf) {
            if ($cf === 'product_custom_field1') {
                $headers[] = __('lang_v1.costcode');
            } elseif ($cf === 'product_custom_field2') {
                $headers[] = __('lang_v1.supplier_code');
            } else {
                $idx = (int) str_replace('product_custom_field', '', $cf);
                $headers[] = ! empty($productCustomLabels['custom_field_'.$idx])
                    ? $productCustomLabels['custom_field_'.$idx]
                    : (! empty($productCustomLabels[$idx]) ? $productCustomLabels[$idx] : $cf);
            }
        }

        $filename = 'bartender_labels_' . date('Ymd_His') . '.csv';
        $save_fixed_url = ! empty($common_settings['bartender_save_fixed_url']);
        $download_browser = ! empty($common_settings['bartender_download_browser']);
        $fixed_relative_path = 'exports/barcode_labels.csv';
        $fixed_public_path = public_path($fixed_relative_path);

        $buildRows = function () use ($products, $business_id, $fields, $customFields) {
            $rows = [];
            foreach ($products as $value) {
                $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);
                $row = [];
                foreach ($fields as $field) {
                    $val = '';
                    switch ($field) {
                        case 'product_name':
                            $val = $details->product_name ?? '';
                            break;
                        case 'sku':
                            $val = $details->sub_sku ?? '';
                            break;
                        case 'barcode':
                            $val = $details->barcode_type ?? '';
                            break;
                        case 'barcode_value':
                            $val = $details->sub_sku ?? '';
                            break;
                        case 'price':
                            $val = $details->default_sell_price ?? '';
                            break;
                        case 'category':
                            $val = '';
                            if (! empty($details->category_id)) {
                                $cat = \App\Category::find($details->category_id);
                                $val = $cat ? $cat->name : '';
                            }
                            break;
                        case 'brand':
                            $val = $details->brand ?? '';
                            break;
                        case 'unit':
                            $val = $details->unit ?? '';
                            break;
                        case 'weight':
                            $val = $details->weight ?? '';
                            break;
                        case 'description':
                            $val = '';
                            if (! empty($details->product_id)) {
                                $prod = \App\Product::find($details->product_id);
                                $val = $prod ? ($prod->product_description ?? '') : '';
                            }
                            break;
                        case 'quantity':
                            $val = $value['quantity'] ?? 1;
                            break;
                        case 'supplier':
                            $val = $details->supplier_name ?? '';
                            break;
                        default:
                            $val = '';
                            break;
                    }
                    $row[] = $val;
                }

                foreach ($customFields as $cf) {
                    $row[] = $details->{$cf} ?? '';
                }
                $rows[] = $row;
            }

            return $rows;
        };

        if ($save_fixed_url && ! $request->boolean('download')) {
            if (! File::isDirectory(dirname($fixed_public_path))) {
                File::makeDirectory(dirname($fixed_public_path), 0755, true);
            }

            $fh = fopen($fixed_public_path, 'w');
            fputcsv($fh, $headers, $delimiter);
            foreach ($buildRows() as $row) {
                fputcsv($fh, $row, $delimiter);
            }
            fclose($fh);

            $fixed_url = url($fixed_relative_path);
            if ($download_browser) {
                return response()->json([
                    'success' => true,
                    'mode' => 'fixed_and_download',
                    'fixed_url' => $fixed_url,
                    'download_url' => action([\App\Http\Controllers\LabelsController::class, 'exportBartenderCsv'], array_merge($request->all(), ['download' => 1])),
                    'launcher_url' => ! empty($common_settings['bartender_batch_launcher_url']) ? $common_settings['bartender_batch_launcher_url'] : '',
                    'message' => 'Barcodes loaded successfully. Please open barcode print batch file.',
                ]);
            }

            return response()->json([
                'success' => true,
                'mode' => 'fixed_only',
                'fixed_url' => $fixed_url,
                'launcher_url' => ! empty($common_settings['bartender_batch_launcher_url']) ? $common_settings['bartender_batch_launcher_url'] : '',
                'message' => 'Barcodes loaded successfully. Please open barcode print batch file.',
            ]);
        }

        return response()->stream(function () use ($products, $business_id, $fields, $customFields, $delimiter, $headers) {
            $fh = fopen('php://output', 'w');

            // Output header row
            fputcsv($fh, $headers, $delimiter);

            foreach ($buildRows() as $row) {
                fputcsv($fh, $row, $delimiter);
            }

            fclose($fh);
        }, 200, [
            'Content-Type' => 'text/csv; charset=' . ($encoding == 'UTF-8-BOM' ? 'UTF-8' : $encoding),
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Returns the html for labels preview
     *
     * @return \Illuminate\Http\Response
     */
    public function preview(Request $request)
    {
        try {
            $products = $request->get('products');
            $print = $request->get('print');
            $barcode_setting = $request->get('barcode_setting');
            $business_id = $request->session()->get('user.business_id');

            $barcode_details = Barcode::find($barcode_setting);
            $barcode_details->stickers_in_one_sheet = $barcode_details->is_continuous ? $barcode_details->stickers_in_one_row : $barcode_details->stickers_in_one_sheet;
            $barcode_details->paper_height = $barcode_details->is_continuous ? $barcode_details->height : $barcode_details->paper_height;
            if ($barcode_details->stickers_in_one_row == 1) {
                $barcode_details->col_distance = 0;
                $barcode_details->row_distance = 0;
            }
            // if($barcode_details->is_continuous){
            //     $barcode_details->row_distance = 0;
            // }

            $business_name = $request->session()->get('business.name');

            $product_details_page_wise = [];
            $total_qty = 0;
            foreach ($products as $value) {
                $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);

                if (! empty($value['exp_date'])) {
                    $details->exp_date = $value['exp_date'];
                }
                if (! empty($value['packing_date'])) {
                    $details->packing_date = $value['packing_date'];
                }
                if (! empty($value['lot_number'])) {
                    $details->lot_number = $value['lot_number'];
                }

                if (! empty($value['price_group_id'])) {
                    $tax_id = $print['price_type'] == 'inclusive' ?: $details->tax_id;

                    $group_prices = $this->productUtil->getVariationGroupPrice($value['variation_id'], $value['price_group_id'], $tax_id);

                    $details->sell_price_inc_tax = $group_prices['price_inc_tax'];
                    $details->default_sell_price = $group_prices['price_exc_tax'];
                }

                for ($i = 0; $i < $value['quantity']; $i++) {
                    $page = intdiv($total_qty, $barcode_details->stickers_in_one_sheet);

                    if ($total_qty % $barcode_details->stickers_in_one_sheet == 0) {
                        $product_details_page_wise[$page] = [];
                    }

                    $product_details_page_wise[$page][] = $details;
                    $total_qty++;
                }
            }

            $margin_top = $barcode_details->is_continuous ? 0 : $barcode_details->top_margin * 1;
            $margin_left = $barcode_details->is_continuous ? 0 : $barcode_details->left_margin * 1;
            $paper_width = $barcode_details->paper_width * 1;
            $paper_height = $barcode_details->paper_height * 1;

            // print_r($paper_height);
            // echo "==";
            // print_r($margin_left);exit;

            // $mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8',
            //             'format' => [$paper_width, $paper_height],
            //             'margin_top' => $margin_top,
            //             'margin_bottom' => $margin_top,
            //             'margin_left' => $margin_left,
            //             'margin_right' => $margin_left,
            //             'autoScriptToLang' => true,
            //             // 'disablePrintCSS' => true,
            // 'autoLangToFont' => true,
            // 'autoVietnamese' => true,
            // 'autoArabic' => true
            //             ]
            //         );
            //print_r($mpdf);exit;

            $i = 0;
            $len = count($product_details_page_wise);
            $is_first = false;
            $is_last = false;

            //$original_aspect_ratio = 4;//(w/h)
            $factor = (($barcode_details->width / $barcode_details->height)) / ($barcode_details->is_continuous ? 2 : 4);
            $html = '';
            foreach ($product_details_page_wise as $page => $page_products) {
                if ($i == 0) {
                    $is_first = true;
                }

                if ($i == $len - 1) {
                    $is_last = true;
                }

                $output = view('labels.partials.preview_2')
                            ->with(compact('print', 'page_products', 'business_name', 'barcode_details', 'margin_top', 'margin_left', 'paper_width', 'paper_height', 'is_first', 'is_last', 'factor'))->render();
                print_r($output);
                //$mpdf->WriteHTML($output);

                // if($i < $len - 1){
                //     // '', '', '', '', '', '', $margin_left, $margin_left, $margin_top, $margin_top, '', '', '', '', '', '', 0, 0, 0, 0, '', [$barcode_details->paper_width*1, $barcode_details->paper_height*1]
                //     $mpdf->AddPage();
                // }

                $i++;
            }

            print_r('<script>window.print()</script>');
            exit;
            //return $output;

            //$mpdf->Output();

            // $page_height = null;
            // if ($barcode_details->is_continuous) {
            //     $rows = ceil($total_qty/$barcode_details->stickers_in_one_row) + 0.4;
            //     $barcode_details->paper_height = $barcode_details->top_margin + ($rows*$barcode_details->height) + ($rows*$barcode_details->row_distance);
            // }

            // $output = view('labels.partials.preview')
            //     ->with(compact('print', 'product_details', 'business_name', 'barcode_details', 'product_details_page_wise'))->render();

            // $output = ['html' => $html,
            //                 'success' => true,
            //                 'msg' => ''
            //             ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = __('lang_v1.barcode_label_error');
        }

        //return $output;
    }

    /**
     * Browser print for fixed-size product labels (default 38mm x 25mm).
     * Separate from BarTender export — opens a print-ready HTML page.
     */
    /**
     * Store product list in the session and return a short print_id.
     * Used so browser-print can open with ?print_id=xxx (avoids 414 URI too large).
     */
    public function prepareBrowserPrint(Request $request)
    {
        if (! auth()->check()) {
            return response()->json(['success' => 0, 'msg' => 'Unauthorized'], 403);
        }

        $products = $request->input('products', []);
        if (empty($products) || ! is_array($products)) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.label_no_product_error'),
            ], 422);
        }

        $clean = [];
        foreach ($products as $p) {
            if (! is_array($p)) {
                continue;
            }
            if (empty($p['variation_id']) && empty($p['product_id'])) {
                continue;
            }
            $row = [
                'product_id' => $p['product_id'] ?? null,
                'variation_id' => $p['variation_id'] ?? null,
                'quantity' => $p['quantity'] ?? 1,
            ];
            $labelSize = ProductSizeColor::cleanName($p['label_size'] ?? '');
            $labelColor = ProductSizeColor::cleanName($p['label_color'] ?? '');
            if ($labelSize !== null) {
                $row['label_size'] = $labelSize;
            }
            if ($labelColor !== null) {
                $row['label_color'] = $labelColor;
            }
            if (! empty($p['lot_number'])) {
                $row['lot_number'] = $p['lot_number'];
            }
            if (! empty($p['exp_date'])) {
                $row['exp_date'] = $p['exp_date'];
            }
            $clean[] = $row;
        }

        if (empty($clean)) {
            return response()->json([
                'success' => 0,
                'msg' => __('lang_v1.label_no_product_error'),
            ], 422);
        }

        $printId = bin2hex(random_bytes(8));
        $request->session()->put('browser_label_print.'.$printId, [
            'products' => $clean,
            'business_id' => (int) $request->session()->get('user.business_id'),
            'created_at' => time(),
        ]);

        return response()->json([
            'success' => 1,
            'print_id' => $printId,
        ]);
    }

    public function browserPrint(Request $request)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        // Short ?print_id= from prepare (preferred) — avoids 414 on shared LiteSpeed.
        // Fall back to POST/GET products for rebuild toolbar / legacy.
        $products = $request->input('products', []);
        $printId = (string) $request->input('print_id', '');
        if ($printId !== '' && (empty($products) || ! is_array($products))) {
            $batch = $request->session()->get('browser_label_print.'.$printId);
            $biz = (int) $request->session()->get('user.business_id');
            if (is_array($batch)
                && ! empty($batch['products'])
                && is_array($batch['products'])
                && (int) ($batch['business_id'] ?? 0) === $biz) {
                $products = $batch['products'];
            }
        }

        if (empty($products) || ! is_array($products)) {
            return redirect()->action([\App\Http\Controllers\LabelsController::class, 'show'])
                ->with('status', ['success' => 0, 'msg' => __('lang_v1.label_no_product_error')]);
        }

        $business_id = $request->session()->get('user.business_id');
        $business = Business::findOrFail($business_id);
        $common_settings = ! empty($business->common_settings) && is_array($business->common_settings)
            ? $business->common_settings
            : [];
        $saved_layout = ! empty($common_settings['browser_label_layout']) && is_array($common_settings['browser_label_layout'])
            ? $common_settings['browser_label_layout']
            : [];
        $default_business_name = (string) $request->session()->get('business.name');
        $profiles = BrowserLabelLayout::normalizeProfiles(
            is_array($common_settings['browser_label_profiles'] ?? null) ? $common_settings['browser_label_profiles'] : [],
            $default_business_name
        );

        // Saved layout, then toolbar values when the form is rebuilt.
        $incoming = $saved_layout;
        foreach (array_keys(BrowserLabelLayout::defaults($default_business_name)) as $key) {
            if ($request->exists($key)) {
                $incoming[$key] = $request->input($key);
            }
        }
        $layout = BrowserLabelLayout::normalize($incoming, $default_business_name);
        $pageBox = BrowserLabelLayout::pageBox($layout);

        $business_name = $layout['label_business_name'] !== ''
            ? $layout['label_business_name']
            : $default_business_name;
        $currency_symbol = session('currency')['symbol'] ?? '';

        $opts = $layout;
        $opts['auto_print'] = $request->boolean('auto_print');
        foreach (BrowserLabelLayout::showFlags() as $flag) {
            $opts[$flag] = ($layout[$flag] ?? '0') === '1';
        }

        $customLabels = session('business.custom_labels', []);
        if (is_string($customLabels)) {
            $customLabels = json_decode($customLabels, true) ?? [];
        }
        if (! is_array($customLabels)) {
            $customLabels = [];
        }
        $productLabels = ! empty($customLabels['product']) && is_array($customLabels['product']) ? $customLabels['product'] : [];
        $costcodeLabel = ! empty($productLabels['custom_field_1']) ? $productLabels['custom_field_1'] : __('lang_v1.costcode');
        $supplierCodeLabel = ! empty($productLabels['custom_field_2']) ? $productLabels['custom_field_2'] : __('lang_v1.supplier_code');

        $stickers = [];
        $productIds = [];
        foreach ($products as $value) {
            if (is_array($value) && ! empty($value['product_id'])) {
                $productIds[] = (int) $value['product_id'];
            }
        }
        $assignedByProduct = $this->loadAssignedAttributes((int) $business_id, $productIds);
        $labelOverrides = $this->labelDisplayOverrides($request->input('label_overrides'));
        $rowIndex = 0;
        foreach ($products as $value) {
            if (empty($value['variation_id']) && empty($value['product_id'])) {
                continue;
            }
            $qty = max(1, (int) ceil((float) ($value['quantity'] ?? 1)));
            $productId = (int) ($value['product_id'] ?? 0);
            $assigned = $assignedByProduct[$productId] ?? [
                'sizes' => [],
                'colors' => [],
                'size_variations' => [],
                'color_variations' => [],
            ];
            $override = $labelOverrides[(string) $rowIndex] ?? $labelOverrides[$rowIndex] ?? [];
            if (! is_array($override)) {
                $override = [];
            }
            $options = [
                'sizes' => $assigned['sizes'],
                'colors' => $assigned['colors'],
            ];
            $size = ProductSizeColor::chooseOne(
                $options['sizes'],
                $override['size'] ?? ($value['label_size'] ?? '')
            );
            $color = ProductSizeColor::chooseOne(
                $options['colors'],
                $override['color'] ?? ($value['label_color'] ?? '')
            );

            $variationId = (int) ($value['variation_id'] ?? 0);
            $mappedId = $this->mappedLabelVariationId($assigned, $size, $color);
            if ($mappedId > 0) {
                $variationId = $mappedId;
            }
            if ($variationId < 1) {
                continue;
            }

            try {
                $details = $this->productUtil->getDetailsFromVariation($variationId, $business_id, null, false);
            } catch (\Throwable $e) {
                $originalId = (int) ($value['variation_id'] ?? 0);
                if ($mappedId < 1 || $originalId < 1 || $originalId === $variationId) {
                    throw $e;
                }
                $variationId = $originalId;
                $details = $this->productUtil->getDetailsFromVariation($variationId, $business_id, null, false);
            }
            if (empty($details)) {
                continue;
            }
            if ($productId < 1) {
                $productId = (int) ($details->product_id ?? 0);
            }
            $currentRow = $rowIndex;
            $rowIndex++;

            $sku = trim((string) ($details->sub_sku ?? ''));
            $name = $details->product_actual_name ?? $details->product_name ?? '';
            if (! empty($details->variation_name) && ($details->variation_name ?? '') !== 'DUMMY' && (int) ($details->is_dummy ?? 1) !== 1) {
                $name .= ' '.$details->variation_name;
            }
            $brand = trim((string) ($details->brand ?? ''));
            $category = '';
            if (! empty($details->category_id)) {
                $category = trim((string) optional(\App\Category::find($details->category_id))->name);
            }
            $costcode = trim((string) ($details->product_custom_field1 ?? ''));
            $price = $opts['price_type'] === 'exclusive'
                ? ($details->default_sell_price ?? 0)
                : ($details->sell_price_inc_tax ?? $details->default_sell_price ?? 0);
            $priceText = '';

            // Display text for this print job only. Product prices are not written.
            if (array_key_exists('name', $override)) {
                $name = $this->clipLabelText($override['name'], 120);
            }
            if (array_key_exists('sku', $override) && $this->clipLabelText($override['sku'], 80) !== '') {
                $sku = $this->clipLabelText($override['sku'], 80);
            }
            if (array_key_exists('brand', $override)) {
                $brand = $this->clipLabelText($override['brand'], 80);
            }
            if (array_key_exists('category', $override)) {
                $category = $this->clipLabelText($override['category'], 80);
            }
            if (array_key_exists('costcode', $override)) {
                $costcode = $this->clipLabelText($override['costcode'], 40);
            }
            if (array_key_exists('price_text', $override)) {
                $priceText = $this->clipLabelText($override['price_text'], 40);
            }
            $barcodeValue = $sku;
            if (array_key_exists('barcode_value', $override) && $this->clipLabelText($override['barcode_value'], 80) !== '') {
                $barcodeValue = $this->clipLabelText($override['barcode_value'], 80);
            }

            $requestedType = ($layout['barcode_format'] ?? 'auto') === 'auto'
                ? (string) ($details->barcode_type ?? 'C128')
                : (string) $layout['barcode_format'];
            $barcodeType = BrowserLabelLayout::resolveBarcodeType($requestedType, (string) $barcodeValue);

            $barcodePng = '';
            try {
                if ($barcodeValue !== '') {
                    $barW = max(1, min(4, (int) $opts['barcode_bar_width']));
                    $barH = max(16, min(80, (int) $opts['barcode_height']));
                    $barcodePng = \DNS1D::getBarcodePNG($barcodeValue, $barcodeType, $barW, $barH, [0, 0, 0], false);
                }
            } catch (\Throwable $e) {
                try {
                    if ($barcodeValue !== '' && $barcodeType !== 'C128') {
                        $barcodePng = \DNS1D::getBarcodePNG($barcodeValue, 'C128', 2, 40, [0, 0, 0], false);
                        $barcodeType = 'C128';
                    }
                } catch (\Throwable $e2) {
                    $barcodePng = '';
                }
            }

            for ($i = 0; $i < $qty; $i++) {
                $stickers[] = [
                    'row' => $currentRow,
                    'name' => $name,
                    'sku' => $sku,
                    'barcode_value' => $barcodeValue,
                    'price' => $price,
                    'price_text' => $priceText,
                    'barcode_png' => $barcodePng,
                    'barcode_type' => $barcodeType,
                    'costcode' => $costcode,
                    'supplier_code' => trim((string) ($details->product_custom_field2 ?? '')),
                    'supplier_name' => trim((string) ($details->supplier_name ?? '')),
                    'brand' => $brand,
                    'category' => $category,
                    'size' => $size,
                    'color' => $color,
                    'size_options' => $options['sizes'],
                    'color_options' => $options['colors'],
                ];
            }
        }

        if (empty($stickers)) {
            return redirect()->action([\App\Http\Controllers\LabelsController::class, 'show'])
                ->with('status', ['success' => 0, 'msg' => __('lang_v1.label_no_product_error')]);
        }

        // The design form posts show_size / show_color (hidden 0 plus the checkbox).
        // The first preview does not, so a chosen size or color is shown on that label.
        // Unchecking Size or Color and rebuilding keeps them hidden. Nothing is saved here.
        if (! $request->exists('show_size') && ! $request->exists('show_color')) {
            foreach ($stickers as $sticker) {
                if (trim((string) ($sticker['size'] ?? '')) !== '') {
                    $opts['show_size'] = true;
                    $layout['show_size'] = '1';
                }
                if (trim((string) ($sticker['color'] ?? '')) !== '') {
                    $opts['show_color'] = true;
                    $layout['show_color'] = '1';
                }
            }
        }

        $presets = BrowserLabelLayout::presets();

        return view('labels.browser_print')
            ->with(compact(
                'stickers',
                'business_name',
                'currency_symbol',
                'opts',
                'costcodeLabel',
                'supplierCodeLabel',
                'printId',
                'profiles',
                'presets',
                'pageBox',
                'layout'
            ));
    }

    /**
     * Save the browser label layout for the business so every device uses it.
     */
    public function saveBrowserPrintLayout(Request $request)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = $request->session()->get('user.business_id');

        // Accept JSON body (Save Layout) and form posts.
        $in = $request->all();
        if ($request->isJson() || $request->header('Content-Type') === 'application/json') {
            $json = $request->json()->all();
            if (is_array($json) && ! empty($json)) {
                $in = array_merge($in, $json);
            }
        }

        $get = function (string $key, $default = null) use ($in, $request) {
            if (array_key_exists($key, $in)) {
                return $in[$key];
            }

            return $request->input($key, $default);
        };

        $existingLayout = [];
        $businessForMerge = Business::find($business_id);
        $existingSettings = $businessForMerge && is_array($businessForMerge->common_settings)
            ? $businessForMerge->common_settings
            : [];
        if (is_array($existingSettings['browser_label_layout'] ?? null)) {
            $existingLayout = $existingSettings['browser_label_layout'];
        }
        $raw = $existingLayout;
        foreach (array_keys(BrowserLabelLayout::defaults()) as $key) {
            if (array_key_exists($key, $in) || $request->exists($key)) {
                $raw[$key] = $get($key);
            }
        }
        $businessName = (string) $request->session()->get('business.name');
        $layout = BrowserLabelLayout::normalize($raw, $businessName);

        $profileName = mb_substr(strip_tags(trim((string) $get('profile_name', ''))), 0, 40);
        $profileId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $get('profile_id', ''));
        $deleteProfileId = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) $get('delete_profile_id', ''));

        DB::transaction(function () use ($business_id, $layout, $request, $profileName, $profileId, $deleteProfileId, $businessName) {
            $business = Business::where('id', $business_id)->lockForUpdate()->firstOrFail();
            $common_settings = $business->common_settings;
            if (is_string($common_settings)) {
                $common_settings = json_decode($common_settings, true) ?: [];
            }
            if (! is_array($common_settings)) {
                $common_settings = [];
            }
            $profiles = BrowserLabelLayout::normalizeProfiles(
                is_array($common_settings['browser_label_profiles'] ?? null) ? $common_settings['browser_label_profiles'] : [],
                $businessName
            );
            if ($deleteProfileId !== '') {
                $profiles = array_values(array_filter($profiles, function ($profile) use ($deleteProfileId) {
                    return ($profile['id'] ?? '') !== $deleteProfileId;
                }));
            }
            if ($profileName !== '') {
                $found = false;
                foreach ($profiles as &$profile) {
                    if ($profileId !== '' && ($profile['id'] ?? '') === $profileId) {
                        $profile['name'] = $profileName;
                        $profile['layout'] = $layout;
                        $found = true;
                        break;
                    }
                }
                unset($profile);
                if (! $found && count($profiles) < 12) {
                    $profiles[] = [
                        'id' => 'p'.substr(bin2hex(random_bytes(4)), 0, 8),
                        'name' => $profileName,
                        'layout' => $layout,
                    ];
                }
            }
            $common_settings['browser_label_layout'] = $layout;
            $common_settings['browser_label_profiles'] = $profiles;
            $business->common_settings = $common_settings;
            $business->save();

            $request->session()->put('business', $business->fresh());
        });

        $saved = Business::find($business_id);
        $savedSettings = is_array($saved->common_settings ?? null) ? $saved->common_settings : [];
        $savedLayout = $savedSettings['browser_label_layout'] ?? $layout;
        $savedProfiles = $savedSettings['browser_label_profiles'] ?? [];

        return response()->json([
            'success' => true,
            'layout' => $savedLayout,
            'profiles' => $savedProfiles,
            'msg' => __('lang_v1.label_layout_saved_server'),
            'barcode_width' => $savedLayout['barcode_width'] ?? null,
            'barcode_height' => $savedLayout['barcode_height'] ?? null,
        ]);
    }

    /**
     * Rebuild one barcode image for the live preview. Does not change the product.
     */
    public function browserPrintBarcode(Request $request)
    {
        if (! auth()->check()) {
            abort(403, 'Unauthorized action.');
        }

        $sku = mb_substr(trim((string) $request->input('sku', '')), 0, 80);
        if ($sku === '' || preg_match('/[\x00-\x1F]/', $sku)) {
            return response()->json(['success' => false, 'msg' => 'Invalid barcode value'], 422);
        }

        $requested = (string) $request->input('barcode_format', 'C128');
        if (strtolower($requested) === 'auto') {
            $requested = 'C128';
        }
        $barcodeType = BrowserLabelLayout::resolveBarcodeType($requested, $sku);
        $barW = max(1, min(4, (int) $request->input('barcode_bar_width', 2)));
        $barH = max(16, min(80, (int) $request->input('barcode_height', 42)));

        try {
            $png = \DNS1D::getBarcodePNG($sku, $barcodeType, $barW, $barH, [0, 0, 0], false);
        } catch (\Throwable $e) {
            try {
                $png = \DNS1D::getBarcodePNG($sku, 'C128', $barW, $barH, [0, 0, 0], false);
                $barcodeType = 'C128';
            } catch (\Throwable $e2) {
                return response()->json(['success' => false, 'msg' => 'Barcode could not be drawn'], 422);
            }
        }

        return response()->json([
            'success' => true,
            'png' => $png,
            'barcode_type' => $barcodeType,
        ]);
    }

    /**
     * Returns the html for weighing scale labels preview
     * Special format for weighing scale stickers with product name, barcode, weight, price
     *
     * @return \Illuminate\Http\Response
     */
    public function previewWeighingScale(Request $request)
    {
        try {
            $products = $request->get('products');
            $business_id = $request->session()->get('user.business_id');
            $business_name = $request->session()->get('business.name');
            
            $product_details = [];
            
            foreach ($products as $value) {
                $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);
                
                // Use provided weight or default to 0.100 kg
                $weight_kg = !empty($value['weight_kg']) ? (float)$value['weight_kg'] : 0.100;
                
                $product_details[] = [
                    'details' => $details,
                    'qty' => $value['quantity'] ?? 1,
                    'weight_kg' => $weight_kg,
                ];
            }
            
            return view('labels.partials.weighing_scale_preview')
                ->with(compact('product_details', 'business_name'));
                
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());
            return __('lang_v1.barcode_label_error');
        }
    }

    /**
     * Flatten + de-duplicate bartender export field lists (guards against duplicated saves).
     */
    protected function normalizeBartenderFieldList($fields, array $default = []): array
    {
        if ($fields === null || $fields === '' || $fields === []) {
            return array_values($default);
        }

        if (is_string($fields)) {
            $decoded = json_decode($fields, true);
            if (is_array($decoded)) {
                $fields = $decoded;
            } else {
                $fields = preg_split('/\s*,\s*/', $fields) ?: [];
            }
        }

        if (! is_array($fields)) {
            return array_values($default);
        }

        $flat = [];
        array_walk_recursive($fields, function ($item) use (&$flat) {
            if (is_string($item) || is_int($item)) {
                $val = trim((string) $item);
                if ($val !== '') {
                    $flat[] = $val;
                }
            }
        });

        $flat = array_values(array_unique($flat));

        return $flat !== [] ? $flat : array_values($default);
    }

    /**
     * Size and color for the label list. Only values assigned to that product.
     *
     * @param  iterable  $products
     * @param  int  $business_id
     * @return iterable
     */
    protected function attachSizeColor($products, $business_id, string $preferredSize = '', string $preferredColor = '')
    {
        $ids = [];
        foreach ($products as $product) {
            $ids[] = (int) ($product->product_id ?? 0);
        }
        $lists = $this->loadAssignedAttributes((int) $business_id, $ids);
        foreach ($products as $product) {
            $productId = (int) ($product->product_id ?? 0);
            $assigned = $lists[$productId] ?? ['sizes' => [], 'colors' => []];
            $product->size_options = $assigned['sizes'];
            $product->color_options = $assigned['colors'];
            $product->size = ProductSizeColor::chooseOne($assigned['sizes'], $preferredSize);
            $product->color = ProductSizeColor::chooseOne($assigned['colors'], $preferredColor);
        }

        return $products;
    }

    /**
     * Assigned sizes and colours for one product. The printed label uses one of each.
     *
     * @param  array<string, array{sizes?: array<int, string>, colors?: array<int, string>}>  $cache
     * @param  array{sizes?: array<int, string>, colors?: array<int, string>}  $catalog
     * @return array{sizes: array<int, string>, colors: array<int, string>}
     */
    protected function labelAttributeOptions(int $productId, int $variationId, int $businessId, array &$cache, array $catalog = []): array
    {
        $key = ($variationId > 0 ? 'v'.$variationId : 'p'.$productId).'@'.$businessId;
        if (isset($cache[$key]) && isset($cache[$key]['sizes'])) {
            return $cache[$key];
        }

        $empty = ['sizes' => [], 'colors' => []];
        if ($businessId < 1 || ($productId < 1 && $variationId < 1)) {
            return $cache[$key] = $empty;
        }

        if ($productId < 1) {
            $productId = (int) DB::table('variations')->where('id', $variationId)->value('product_id');
        }
        $loaded = $this->loadAssignedAttributes($businessId, [$productId]);
        $assigned = $loaded[$productId] ?? $empty;
        if (($assigned['sizes'] ?? []) === [] && ($assigned['colors'] ?? []) === [] && $catalog !== []) {
            $resolvedCache = [];
            $resolved = $this->sizeColorForLabel($productId, $variationId, $businessId, $resolvedCache);
            $assigned = [
                'sizes' => ProductSizeColor::optionList($catalog['sizes'] ?? [], $resolved['size'] ?? ''),
                'colors' => ProductSizeColor::optionList($catalog['colors'] ?? [], $resolved['color'] ?? ''),
            ];
        }

        return $cache[$key] = [
            'sizes' => array_values($assigned['sizes'] ?? []),
            'colors' => array_values($assigned['colors'] ?? []),
        ];
    }

    /**
     * Sizes and colors actually assigned to these products.
     * Catalog links win, then the product text copy, then size/color variations.
     *
     * @param  array<int, mixed>  $productIds
     * @return array<int, array{sizes: array<int, string>, colors: array<int, string>, size_variations: array<string, int>, color_variations: array<string, int>}>
     */
    protected function loadAssignedAttributes(int $businessId, array $productIds): array
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
            $out[$id] = [
                'sizes' => [],
                'colors' => [],
                'size_variations' => [],
                'color_variations' => [],
            ];
        }
        if ($businessId < 1 || $ids === []) {
            return $out;
        }

        $idList = array_values($ids);
        $catalog = ProductSizeColor::listsForProducts($businessId, $idList);
        $this->fillCatalogSizes($catalog, $businessId, $idList);
        $this->fillCatalogColors($catalog, $businessId, $idList);
        $legacy = $this->legacyAttributeColumns($businessId, $idList);
        $fromVariations = $this->variationAttributeMaps($idList);

        foreach ($idList as $id) {
            $textSize = trim((string) ($legacy[$id]['size'] ?? ''));
            if ($textSize === '') {
                $textSize = trim((string) ($legacy[$id]['hw_size'] ?? ''));
            }
            $textColor = trim((string) ($legacy[$id]['color'] ?? ''));
            if ($textColor === '') {
                $textColor = trim((string) ($legacy[$id]['hw_colour'] ?? ''));
            }
            $variation = $fromVariations[$id] ?? [
                'size_names' => [],
                'color_names' => [],
                'size_ids' => [],
                'color_ids' => [],
            ];
            $sizes = LabelProductSearch::resolveAssigned($catalog[$id]['sizes'] ?? [], $textSize, $variation['size_names']);
            $colors = LabelProductSearch::resolveAssigned($catalog[$id]['colors'] ?? [], $textColor, $variation['color_names']);
            $out[$id]['sizes'] = $sizes['values'];
            $out[$id]['colors'] = $colors['values'];
            if ($sizes['source'] === 'variation') {
                $out[$id]['size_variations'] = $variation['size_ids'];
            }
            if ($colors['source'] === 'variation') {
                $out[$id]['color_variations'] = $variation['color_ids'];
            }
        }

        return $out;
    }

    /**
     * @param  array<int, array{sizes: array<int, string>, colors: array<int, string>}>  $lists
     * @param  array<int, int>  $ids
     */
    protected function fillCatalogSizes(array &$lists, int $businessId, array $ids): void
    {
        $missing = $this->idsMissingList($lists, $ids, 'sizes');
        if ($missing === [] || ! $this->databaseTableExists('product_size') || ! $this->databaseTableExists('sizes')) {
            return;
        }

        try {
            $rows = DB::table('product_size')
                ->join('sizes', 'sizes.id', '=', 'product_size.size_id')
                ->where('sizes.business_id', $businessId)
                ->whereIn('product_size.product_id', $missing)
                ->orderBy('sizes.name')
                ->get(['product_size.product_id as product_id', 'sizes.name as name']);
            foreach ($rows as $row) {
                $this->pushCatalogName($lists, (int) $row->product_id, 'sizes', $row->name ?? '');
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param  array<int, array{sizes: array<int, string>, colors: array<int, string>}>  $lists
     * @param  array<int, int>  $ids
     */
    protected function fillCatalogColors(array &$lists, int $businessId, array $ids): void
    {
        $missing = $this->idsMissingList($lists, $ids, 'colors');
        if ($missing !== [] && $this->databaseTableExists('product_color') && $this->databaseTableExists('colors')) {
            try {
                $rows = DB::table('product_color')
                    ->join('colors', 'colors.id', '=', 'product_color.color_id')
                    ->where('colors.business_id', $businessId)
                    ->whereIn('product_color.product_id', $missing)
                    ->orderBy('colors.name')
                    ->get(['product_color.product_id as product_id', 'colors.name as name']);
                foreach ($rows as $row) {
                    $this->pushCatalogName($lists, (int) $row->product_id, 'colors', $row->name ?? '');
                }
            } catch (\Throwable $e) {
            }
            $missing = $this->idsMissingList($lists, $missing, 'colors');
        }

        if ($missing === [] || ! $this->databaseTableExists('product_colors')) {
            return;
        }

        try {
            $rows = DB::table('product_colors')
                ->where('business_id', $businessId)
                ->whereIn('product_id', $missing)
                ->orderBy('id')
                ->get(['product_id', 'name']);
            foreach ($rows as $row) {
                $this->pushCatalogName($lists, (int) $row->product_id, 'colors', $row->name ?? '');
            }
        } catch (\Throwable $e) {
        }
    }

    /**
     * @param  array<int, array{sizes?: array<int, string>, colors?: array<int, string>}>  $lists
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    protected function idsMissingList(array $lists, array $ids, string $key): array
    {
        $missing = [];
        foreach ($ids as $id) {
            $id = (int) $id;
            if ($id > 0 && empty($lists[$id][$key])) {
                $missing[] = $id;
            }
        }

        return $missing;
    }

    /**
     * @param  array<int, array{sizes: array<int, string>, colors: array<int, string>}>  $lists
     */
    protected function pushCatalogName(array &$lists, int $productId, string $key, $name): void
    {
        $clean = ProductSizeColor::cleanName($name);
        if ($clean === null || ! isset($lists[$productId])) {
            return;
        }
        $lists[$productId][$key][] = $clean;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array{size: string, color: string, hw_size: string, hw_colour: string}>
     */
    protected function legacyAttributeColumns(int $businessId, array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[(int) $id] = ['size' => '', 'color' => '', 'hw_size' => '', 'hw_colour' => ''];
        }
        if ($ids === []) {
            return $out;
        }

        $select = ['id'];
        foreach (['size', 'color', 'hw_size', 'hw_colour'] as $column) {
            if ($this->productsHaveColumn($column)) {
                $select[] = $column;
            }
        }
        if (count($select) === 1) {
            return $out;
        }

        try {
            $rows = DB::table('products')
                ->where('business_id', $businessId)
                ->whereIn('id', $ids)
                ->get($select);
        } catch (\Throwable $e) {
            return $out;
        }

        foreach ($rows as $row) {
            $id = (int) $row->id;
            if (! isset($out[$id])) {
                continue;
            }
            foreach (['size', 'color', 'hw_size', 'hw_colour'] as $column) {
                if (isset($row->{$column})) {
                    $out[$id][$column] = trim((string) $row->{$column});
                }
            }
        }

        return $out;
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, array{size_names: array<int, string>, color_names: array<int, string>, size_ids: array<string, int>, color_ids: array<string, int>}>
     */
    protected function variationAttributeMaps(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $out[(int) $id] = [
                'size_names' => [],
                'color_names' => [],
                'size_ids' => [],
                'color_ids' => [],
            ];
        }
        if ($ids === [] || ! $this->databaseTableExists('variations')) {
            return $out;
        }

        try {
            $rows = DB::table('variations as v')
                ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                ->whereIn('v.product_id', $ids)
                ->whereNull('v.deleted_at')
                ->orderBy('v.id')
                ->get(['v.id', 'v.product_id', 'v.name', 'pv.name as template_name']);
        } catch (\Throwable $e) {
            return $out;
        }

        foreach ($rows as $row) {
            $productId = (int) $row->product_id;
            if (! isset($out[$productId])) {
                continue;
            }
            $kind = self::labelAttributeKind((string) ($row->template_name ?? ''));
            $name = ProductSizeColor::cleanName($row->name ?? '');
            if ($name === null || $kind === '' || strtoupper($name) === 'DUMMY') {
                continue;
            }
            $key = mb_strtolower($name);
            if ($kind === 'size' && ! isset($out[$productId]['size_ids'][$key])) {
                $out[$productId]['size_names'][] = $name;
                $out[$productId]['size_ids'][$key] = (int) $row->id;
            }
            if ($kind === 'color' && ! isset($out[$productId]['color_ids'][$key])) {
                $out[$productId]['color_names'][] = $name;
                $out[$productId]['color_ids'][$key] = (int) $row->id;
            }
        }

        return $out;
    }

    /**
     * Use the variation barcode only when the chosen value is a real size or color variation.
     * Catalog sizes keep the product barcode and print the chosen size as text.
     *
     * @param  array{size_variations?: array<string, int>, color_variations?: array<string, int>}  $assigned
     */
    protected function mappedLabelVariationId(array $assigned, string $size, string $color): int
    {
        $sizeKey = mb_strtolower(trim($size));
        if ($sizeKey !== '' && ! empty($assigned['size_variations'][$sizeKey])) {
            return (int) $assigned['size_variations'][$sizeKey];
        }
        $colorKey = mb_strtolower(trim($color));
        if ($colorKey !== '' && ! empty($assigned['color_variations'][$colorKey])) {
            return (int) $assigned['color_variations'][$colorKey];
        }

        return 0;
    }

    /**
     * Label-only text sent back when the print page is rebuilt.
     * This is never written to the product, its price, or past sales.
     *
     * @param  mixed  $raw
     * @return array<int|string, array<string, mixed>>
     */
    protected function labelDisplayOverrides($raw): array
    {
        if (is_string($raw) && $raw !== '') {
            $raw = json_decode($raw, true);
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        $count = 0;
        foreach ($raw as $row => $fields) {
            if ($count >= 200 || ! is_array($fields)) {
                continue;
            }
            $key = is_int($row) ? $row : (string) $row;
            if (is_string($key) && ! preg_match('/^\d{1,4}$/', $key)) {
                continue;
            }
            $out[$key] = $fields;
            $count++;
        }

        return $out;
    }

    /**
     * @param  mixed  $value
     */
    protected function clipLabelText($value, int $max): string
    {
        $text = trim(strip_tags((string) $value));
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text) ?? '';

        return mb_substr($text, 0, $max);
    }

    /**
     * Product Size and Color, then the selected variation's Size or Color template.
     * Hardware size/colour fills a gap only when the dedicated value is empty.
     *
     * @param  array<string, array{size: string, color: string}>  $cache
     * @return array{size: string, color: string}
     */
    protected function sizeColorForLabel(int $productId, int $variationId, int $businessId, array &$cache): array
    {
        $key = ($variationId > 0 ? 'v'.$variationId : 'p'.$productId).'@'.$businessId;
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $empty = ['size' => '', 'color' => ''];
        if ($businessId < 1 || ($productId < 1 && $variationId < 1)) {
            return $cache[$key] = $empty;
        }

        $columns = [
            'size' => $this->productsHaveColumn('size'),
            'color' => $this->productsHaveColumn('color'),
            'hw_size' => $this->productsHaveColumn('hw_size'),
            'hw_colour' => $this->productsHaveColumn('hw_colour'),
        ];

        try {
            if ($variationId > 0) {
                $query = DB::table('variations as v')
                    ->join('products as p', 'p.id', '=', 'v.product_id')
                    ->leftJoin('product_variations as pv', 'pv.id', '=', 'v.product_variation_id')
                    ->where('p.business_id', $businessId)
                    ->where('v.id', $variationId)
                    ->whereNull('v.deleted_at');
                $select = ['pv.name as template_name', 'pv.is_dummy', 'v.name as variation_name'];
            } else {
                $query = DB::table('products as p')
                    ->where('p.business_id', $businessId)
                    ->where('p.id', $productId);
                $select = [
                    DB::raw("'' as template_name"),
                    DB::raw('1 as is_dummy'),
                    DB::raw("'' as variation_name"),
                ];
            }
            foreach ($columns as $col => $present) {
                if ($present) {
                    $select[] = 'p.'.$col;
                }
            }
            $row = $query->select($select)->first();
        } catch (\Throwable $e) {
            return $cache[$key] = $empty;
        }

        if (! $row) {
            return $cache[$key] = $empty;
        }

        return $cache[$key] = self::resolveLabelSizeColor(
            $columns['size'] ? ($row->size ?? '') : '',
            $columns['color'] ? ($row->color ?? '') : '',
            $row->template_name ?? '',
            $row->variation_name ?? '',
            $row->is_dummy ?? 1,
            $columns['hw_size'] ? ($row->hw_size ?? '') : '',
            $columns['hw_colour'] ? ($row->hw_colour ?? '') : ''
        );
    }

    /**
     * @param  mixed  $size
     * @param  mixed  $color
     * @param  mixed  $templateName
     * @param  mixed  $variationName
     * @param  mixed  $isDummy
     * @param  mixed  $hwSize
     * @param  mixed  $hwColour
     * @return array{size: string, color: string}
     */
    public static function resolveLabelSizeColor($size, $color, $templateName = '', $variationName = '', $isDummy = 1, $hwSize = '', $hwColour = ''): array
    {
        $size = trim((string) $size);
        $color = trim((string) $color);
        $variationName = trim((string) $variationName);
        $dummy = (int) $isDummy === 1 || strtoupper($variationName) === 'DUMMY' || $variationName === '';
        if (! $dummy) {
            $kind = self::labelAttributeKind((string) $templateName);
            if ($kind === 'size' && $size === '') {
                $size = $variationName;
            } elseif ($kind === 'color' && $color === '') {
                $color = $variationName;
            }
        }
        if ($size === '') {
            $size = trim((string) $hwSize);
        }
        if ($color === '') {
            $color = trim((string) $hwColour);
        }

        return ['size' => $size, 'color' => $color];
    }

    public static function labelAttributeKind(string $template): string
    {
        $normalized = strtolower(trim($template));
        $normalized = preg_replace('/[^a-z]+/i', ' ', $normalized);
        $normalized = trim(preg_replace('/\s+/', ' ', (string) $normalized));
        if (in_array($normalized, self::attributeTemplateNames('size'), true)) {
            return 'size';
        }
        if (in_array($normalized, self::attributeTemplateNames('color'), true)) {
            return 'color';
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    protected static function attributeTemplateNames(string $kind): array
    {
        if ($kind === 'size') {
            return ['size', 'sizes', 'product size', 'item size'];
        }

        return ['color', 'colour', 'colors', 'colours', 'product color', 'product colour', 'item color', 'item colour'];
    }

    /**
     * SHOW TABLES, because information_schema is empty on some cPanel accounts.
     */
    protected function databaseTableExists(string $table): bool
    {
        $table = strtolower($table);
        $allowed = [
            'variations',
            'product_variations',
            'product_size',
            'sizes',
            'product_color',
            'colors',
            'product_colors',
            'product_locations',
        ];
        if (! in_array($table, $allowed, true)) {
            return false;
        }
        if (array_key_exists($table, $this->tableExistsCache)) {
            return $this->tableExistsCache[$table];
        }

        try {
            $name = DB::connection()->getTablePrefix().$table;
            $quoted = DB::connection()->getPdo()->quote($name);
            $rows = DB::select('SHOW TABLES LIKE '.$quoted);
            $this->tableExistsCache[$table] = $rows !== [];
        } catch (\Throwable $e) {
            $this->tableExistsCache[$table] = false;
        }

        return $this->tableExistsCache[$table];
    }

    protected function productsHaveColumn(string $column): bool
    {
        if ($this->productColumnCache === null) {
            $this->productColumnCache = [];
            try {
                $table = DB::connection()->getTablePrefix().'products';
                $wrapped = DB::connection()->getQueryGrammar()->wrapTable($table);
                $rows = DB::select('SHOW COLUMNS FROM '.$wrapped);
                foreach ($rows as $row) {
                    $field = strtolower((string) ($row->Field ?? ''));
                    if ($field !== '') {
                        $this->productColumnCache[$field] = true;
                    }
                }
            } catch (\Throwable $e) {
                $this->productColumnCache = [];
            }
        }

        $column = strtolower($column);
        if ($this->productColumnCache !== []) {
            return isset($this->productColumnCache[$column]);
        }

        try {
            return \Illuminate\Support\Facades\Schema::hasColumn('products', $column);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
