<?php

namespace App\Http\Controllers;

use App\Barcode;
use App\Business;
use App\Product;
use App\SellingPriceGroup;
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

                $price_groups = SellingPriceGroup::where('business_id', $business_id)
                                            ->active()
                                            ->pluck('name', 'id');

                return view('labels.partials.show_table_rows')
                        ->with(compact('products', 'index', 'price_groups'));
            }
        }
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
        $saved_layout = array_merge([
            'label_w' => '38',
            'label_h' => '25',
            'business_name_size' => '8',
            'name_size' => '9',
            'sku_size' => '8',
            'price_size' => '12',
            'costcode_size' => '8',
            'barcode_height' => '42',
            'barcode_width' => '94',
            'barcode_bar_width' => '4',
            'barcode_position' => 'bottom',
            'price_type' => 'inclusive',
            'label_business_name' => $default_business_name,
            'show_business_name' => '1',
            'show_name' => '1',
            'show_sku' => '1',
            'show_barcode' => '1',
            'show_price' => '1',
            'show_costcode' => '1',
        ], $saved_layout);

        // Prefer the business-saved layout. Request values only override when present
        // (Rebuild / Apply from the print toolbar). Opening from Labels must NOT pass
        // stale barcode_width/height in the query string or they wipe the saved layout.
        $pick = function (string $key, $default = null) use ($request, $saved_layout) {
            if ($request->exists($key)) {
                return $request->input($key);
            }

            return $saved_layout[$key] ?? $default;
        };

        $business_name = trim((string) $pick('label_business_name', $default_business_name));
        if ($business_name === '') {
            $business_name = $default_business_name;
        }
        $business_name = mb_substr(strip_tags($business_name), 0, 80);
        $currency_symbol = session('currency')['symbol'] ?? '';

        $opts = [
            'label_w' => max(20, min(100, (float) $pick('label_w', 38))),
            'label_h' => max(15, min(80, (float) $pick('label_h', 25))),
            'business_name_size' => max(6, min(28, (int) $pick('business_name_size', 8))),
            'name_size' => max(6, min(32, (int) $pick('name_size', 9))),
            'sku_size' => max(5, min(22, (int) $pick('sku_size', 8))),
            'price_size' => max(6, min(36, (int) $pick('price_size', 12))),
            'costcode_size' => max(5, min(22, (int) $pick('costcode_size', 8))),
            'barcode_height' => max(16, min(90, (int) $pick('barcode_height', 42))),
            'barcode_width' => max(40, min(100, (int) $pick('barcode_width', 94))),
            'barcode_bar_width' => max(1, min(5, (int) $pick('barcode_bar_width', 4))),
            'barcode_position' => in_array((string) $pick('barcode_position', 'bottom'), ['top', 'middle', 'bottom'], true)
                ? (string) $pick('barcode_position', 'bottom')
                : 'bottom',
            'show_business_name' => true,
            'show_name' => true,
            'show_sku' => true,
            'show_barcode' => true,
            'show_price' => true,
            'show_costcode' => true,
            'price_type' => (string) $pick('price_type', 'inclusive') === 'exclusive' ? 'exclusive' : 'inclusive',
            'auto_print' => $request->boolean('auto_print'),
        ];

        foreach (['show_business_name', 'show_name', 'show_sku', 'show_barcode', 'show_price', 'show_costcode'] as $flag) {
            $v = $pick($flag, $saved_layout[$flag] ?? '1');
            $opts[$flag] = ! in_array((string) $v, ['0', 'false', 'off', ''], true);
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
        foreach ($products as $value) {
            if (empty($value['variation_id'])) {
                continue;
            }
            $qty = max(1, (int) ceil((float) ($value['quantity'] ?? 1)));
            $details = $this->productUtil->getDetailsFromVariation($value['variation_id'], $business_id, null, false);
            if (empty($details)) {
                continue;
            }

            $sku = $details->sub_sku ?? '';

            $price = $opts['price_type'] === 'exclusive'
                ? ($details->default_sell_price ?? 0)
                : ($details->sell_price_inc_tax ?? $details->default_sell_price ?? 0);

            $name = $details->product_actual_name ?? $details->product_name ?? '';
            if (! empty($details->variation_name) && ($details->variation_name ?? '') !== 'DUMMY' && (int) ($details->is_dummy ?? 1) !== 1) {
                $name .= ' '.$details->variation_name;
            }

            $barcodePng = '';
            try {
                if ($sku !== '') {
                    $barW = max(1, (int) $opts['barcode_bar_width']);
                    $barH = max(20, (int) $opts['barcode_height']);
                    $barcodePng = \DNS1D::getBarcodePNG($sku, 'C128', $barW, $barH, [0, 0, 0], false);
                }
            } catch (\Throwable $e) {
                $barcodePng = '';
            }

            for ($i = 0; $i < $qty; $i++) {
                $stickers[] = [
                    'name' => $name,
                    'sku' => $sku,
                    'price' => $price,
                    'barcode_png' => $barcodePng,
                    'costcode' => trim((string) ($details->product_custom_field1 ?? '')),
                    'supplier_code' => trim((string) ($details->product_custom_field2 ?? '')),
                    'supplier_name' => trim((string) ($details->supplier_name ?? '')),
                ];
            }
        }

        if (empty($stickers)) {
            return redirect()->action([\App\Http\Controllers\LabelsController::class, 'show'])
                ->with('status', ['success' => 0, 'msg' => __('lang_v1.label_no_product_error')]);
        }

        return view('labels.browser_print')
            ->with(compact('stickers', 'business_name', 'currency_symbol', 'opts', 'costcodeLabel', 'supplierCodeLabel', 'printId'));
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

        $layout = [
            'label_w' => (string) max(20, min(100, (float) $get('label_w', 38))),
            'label_h' => (string) max(15, min(80, (float) $get('label_h', 25))),
            'business_name_size' => (string) max(6, min(28, (int) $get('business_name_size', 8))),
            'name_size' => (string) max(6, min(32, (int) $get('name_size', 9))),
            'sku_size' => (string) max(5, min(22, (int) $get('sku_size', 8))),
            'price_size' => (string) max(6, min(36, (int) $get('price_size', 12))),
            'costcode_size' => (string) max(5, min(22, (int) $get('costcode_size', 8))),
            'barcode_height' => (string) max(16, min(90, (int) $get('barcode_height', 42))),
            'barcode_width' => (string) max(40, min(100, (int) $get('barcode_width', 94))),
            'barcode_bar_width' => (string) max(1, min(5, (int) $get('barcode_bar_width', 4))),
            'barcode_position' => in_array((string) $get('barcode_position', 'bottom'), ['top', 'middle', 'bottom'], true)
                ? (string) $get('barcode_position', 'bottom')
                : 'bottom',
            'price_type' => (string) $get('price_type', 'inclusive') === 'exclusive' ? 'exclusive' : 'inclusive',
            'label_business_name' => mb_substr(strip_tags(trim((string) $get('label_business_name', ''))), 0, 80),
        ];

        foreach (['show_business_name', 'show_name', 'show_sku', 'show_barcode', 'show_price', 'show_costcode'] as $flag) {
            $raw = $get($flag, '0');
            $layout[$flag] = ! in_array((string) $raw, ['0', 'false', 'off', '', 'null'], true) ? '1' : '0';
        }

        DB::transaction(function () use ($business_id, $layout, $request) {
            $business = Business::where('id', $business_id)->lockForUpdate()->firstOrFail();
            $common_settings = $business->common_settings;
            if (is_string($common_settings)) {
                $common_settings = json_decode($common_settings, true) ?: [];
            }
            if (! is_array($common_settings)) {
                $common_settings = [];
            }
            $common_settings['browser_label_layout'] = $layout;
            $business->common_settings = $common_settings;
            $business->save();

            // Keep session business in sync so Labels page / other screens see new layout.
            $request->session()->put('business', $business->fresh());
        });

        $saved = Business::find($business_id);
        $savedLayout = is_array($saved->common_settings ?? null)
            ? ($saved->common_settings['browser_label_layout'] ?? $layout)
            : $layout;

        return response()->json([
            'success' => true,
            'layout' => $savedLayout,
            'msg' => __('lang_v1.label_layout_saved_server'),
            'barcode_width' => $savedLayout['barcode_width'] ?? null,
            'barcode_height' => $savedLayout['barcode_height'] ?? null,
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
}
