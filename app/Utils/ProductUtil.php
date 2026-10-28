<?php

namespace App\Utils;

use App\Business;
use App\BusinessLocation;
use App\Discount;
use App\Media;
use App\Product;
use App\ProductRack;
use App\ProductVariation;
use App\PurchaseLine;
use App\TaxRate;
use App\Transaction;
use App\TransactionSellLine;
use App\TransactionSellLinesPurchaseLines;
use App\Unit;
use App\Variation;
use App\VariationGroupPrice;
use App\VariationLocationDetails;
use App\VariationTemplate;
use App\VariationValueTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProductUtil extends Util
{
    /**
     * Create single type product variation
     *
     * @param (int or object) $product
     * @param $sku
     * @param $purchase_price
     * @param $dpp_inc_tax (default purchase pric including tax)
     * @param $profit_percent
     * @param $selling_price
     * @param $combo_variations = []
     * @return bool
     */
    public function createSingleProductVariation($product, $sku, $purchase_price, $dpp_inc_tax, $profit_percent, $selling_price, $selling_price_inc_tax, $combo_variations = [])
    {
        if (! is_object($product)) {
            $product = Product::find($product);
        }

        //create product variations
        $product_variation_data = [
            'name' => 'DUMMY',
            'is_dummy' => 1,
        ];
        $product_variation = $product->product_variations()->create($product_variation_data);

        //create variations
        $variation_data = [
            'name' => 'DUMMY',
            'product_id' => $product->id,
            'sub_sku' => $sku,
            'default_purchase_price' => $this->num_uf($purchase_price),
            'dpp_inc_tax' => $this->num_uf($dpp_inc_tax),
            'profit_percent' => $this->num_uf($profit_percent),
            'default_sell_price' => $this->num_uf($selling_price),
            'sell_price_inc_tax' => $this->num_uf($selling_price_inc_tax),
            'combo_variations' => $combo_variations,
        ];
        $variation = $product_variation->variations()->create($variation_data);

        Media::uploadMedia($product->business_id, $variation, request(), 'variation_images');

        // Auto-generate cost code if enabled
        $this->generateAndSaveCostCode($product);

        return true;
    }

    /**
     * Generate a repeat-safe cost code from purchase price digits.
     * Every digit starts from its configured letter; repeated occurrences
     * rotate through the same 10-letter secret key so patterns are not exposed.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function generateAndSaveCostCode($product, $settings = null)
    {
        $common_settings = $settings;
        if ($common_settings === null) {
            $business = Business::find($product->business_id);
            $common_settings = ! empty($business->common_settings) ? $business->common_settings : [];
            if (is_string($common_settings)) {
                $common_settings = json_decode($common_settings, true) ?? [];
            }
        }
        if (! is_array($common_settings)) {
            $common_settings = [];
        }

        if (empty($common_settings['enable_costcode_generation'])) {
            return;
        }

        $mapping = ! empty($common_settings['costcode_mapping']) ? $common_settings['costcode_mapping'] : [
            '0' => 'P', '1' => 'R', '2' => 'O', '3' => 'F', '4' => 'I',
            '5' => 'T', '6' => 'A', '7' => 'B', '8' => 'L', '9' => 'E',
        ];
        if (! is_array($mapping)) {
            $mapping = [
                '0' => 'P', '1' => 'R', '2' => 'O', '3' => 'F', '4' => 'I',
                '5' => 'T', '6' => 'A', '7' => 'B', '8' => 'L', '9' => 'E',
            ];
        }
        // Normalize mapping keys/values (settings may store odd types)
        $cleanMap = [];
        foreach ($mapping as $digit => $letter) {
            $d = (string) $digit;
            if (is_array($letter)) {
                $letter = reset($letter);
            }
            $letter = strtoupper(trim((string) $letter));
            if ($letter === '' || ! preg_match('/^[A-Z]/', $letter)) {
                continue;
            }
            $cleanMap[$d] = $letter[0];
        }
        if (count($cleanMap) < 10) {
            $defaults = ['0'=>'P','1'=>'R','2'=>'O','3'=>'F','4'=>'I','5'=>'T','6'=>'A','7'=>'B','8'=>'L','9'=>'E'];
            $cleanMap = array_merge($defaults, $cleanMap);
        }
        $mapping = $cleanMap;

        // V10: any repeated digit uses special letters (not only zero).
        // 1st time a digit appears → main word letter
        // 2nd time same digit → repeat letter 1 (e.g. X)
        // 3rd+ time same digit → repeat letter 2 (e.g. Y)
        // If another product already has the same code, permute those repeat letters
        // (5000 → EKXY, next product with 5000 → EKYX).
        $mainLetters = array_values($mapping);
        $reserved = $mainLetters;

        $repeatLetter1 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_repeat_zero_letter'] ?? '')));
        if ($repeatLetter1 === '' || ! preg_match('/^[A-Z]$/', $repeatLetter1) || in_array($repeatLetter1, $reserved, true)) {
            $legacyKeyword = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_repeat_keyword'] ?? '')));
            if ($legacyKeyword !== '' && ! in_array($legacyKeyword[0], $reserved, true)) {
                $repeatLetter1 = $legacyKeyword[0];
            } else {
                $legacyMap = ! empty($common_settings['costcode_repeat_mapping']) && is_array($common_settings['costcode_repeat_mapping'])
                    ? $common_settings['costcode_repeat_mapping']
                    : [];
                $legacyZero = $legacyMap['0'] ?? $legacyMap[0] ?? '';
                if (is_array($legacyZero)) {
                    $legacyZero = reset($legacyZero);
                }
                $legacyZero = strtoupper(trim((string) $legacyZero));
                if (preg_match('/^[A-Z]$/', $legacyZero) && ! in_array($legacyZero, $reserved, true)) {
                    $repeatLetter1 = $legacyZero;
                } else {
                    $unusedLetters = array_values(array_diff(range('A', 'Z'), $reserved));
                    $repeatLetter1 = $unusedLetters[0] ?? 'X';
                }
            }
        }
        $reserved[] = $repeatLetter1;

        $repeatLetter2 = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_repeat2_letter'] ?? '')));
        if ($repeatLetter2 === '' || ! preg_match('/^[A-Z]$/', $repeatLetter2) || in_array($repeatLetter2, $reserved, true)) {
            $unusedLetters = array_values(array_diff(range('A', 'Z'), $reserved));
            $repeatLetter2 = $unusedLetters[0] ?? 'Y';
        }
        $reserved[] = $repeatLetter2;

        // When cost has fewer digits than selling price, prefix this letter
        // once per missing digit. It is NOT a real cost digit.
        $shortCostLetter = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_short_cost_letter'] ?? '')));
        if ($shortCostLetter === '' || ! preg_match('/^[A-Z]$/', $shortCostLetter) || in_array($shortCostLetter, $reserved, true)) {
            $unusedLetters = array_values(array_diff(range('A', 'Z'), $reserved));
            $shortCostLetter = $unusedLetters[0] ?? 'W';
        }
        $reserved[] = $shortCostLetter;

        // Letter for the decimal point in the middle (55.50 → …D…).
        $decimalLetter = strtoupper(preg_replace('/[^A-Z]/i', '', (string) ($common_settings['costcode_decimal_letter'] ?? '')));
        if ($decimalLetter === '' || ! preg_match('/^[A-Z]$/', $decimalLetter) || in_array($decimalLetter, $reserved, true)) {
            $unusedLetters = array_values(array_diff(range('A', 'Z'), $reserved));
            $decimalLetter = $unusedLetters[0] ?? 'D';
        }

        // Always reload variations — create flow may have an empty cached relation
        $product->unsetRelation('variations');
        $variations = $product->variations()->get();
        if ($variations->isEmpty()) {
            return;
        }

        $variation = $variations->first();
        $costTokens = $this->costcodePriceTokens($variation->default_purchase_price);
        $sellTokens = $this->costcodePriceTokens($variation->default_sell_price);

        if (empty($costTokens)) {
            return;
        }

        $costDigitCount = 0;
        foreach ($costTokens as $token) {
            if ($token !== '.') {
                $costDigitCount++;
            }
        }
        $sellDigitCount = 0;
        foreach ($sellTokens as $token) {
            if ($token !== '.') {
                $sellDigitCount++;
            }
        }

        // Encode tokens: digits use mapping/repeat letters; '.' uses decimal letter.
        $code = '';
        $repeatPositions = [];
        $digitOccurrences = [];
        foreach ($costTokens as $token) {
            if ($token === '.') {
                $code .= $decimalLetter;
                continue;
            }

            $digit = (string) $token;
            $occurrence = $digitOccurrences[$digit] ?? 0;
            $digitOccurrences[$digit] = $occurrence + 1;
            if ($occurrence === 0) {
                $code .= ! empty($mapping[$digit]) ? strtoupper((string) $mapping[$digit]) : $digit;
                continue;
            }

            // 2nd occurrence → letter 1; 3rd+ → letter 2
            $code .= ($occurrence === 1) ? $repeatLetter1 : $repeatLetter2;
            $repeatPositions[] = strlen($code) - 1;
        }

        // If selling price has more digits, prefix the short-cost letter.
        // Example: cost 500 (EKX) + sell 1450 => WEKX when pad letter is W.
        $padCount = 0;
        if ($sellDigitCount > $costDigitCount && $code !== '') {
            $padCount = $sellDigitCount - $costDigitCount;
            $code = str_repeat($shortCostLetter, $padCount).$code;
            if ($padCount > 0 && ! empty($repeatPositions)) {
                $repeatPositions = array_map(function ($pos) use ($padCount) {
                    return $pos + $padCount;
                }, $repeatPositions);
            }
        }

        if ($code === '') {
            return;
        }

        $candidates = $this->costcodeUniqueCandidates($code, $repeatPositions);
        $chosen = $candidates[0];
        foreach ($candidates as $candidate) {
            $exists = Product::where('business_id', $product->business_id)
                ->where('product_custom_field1', $candidate)
                ->when(! empty($product->id), function ($q) use ($product) {
                    $q->where('id', '!=', $product->id);
                })
                ->exists();
            if (! $exists) {
                $chosen = $candidate;
                break;
            }
        }

        $product->product_custom_field1 = $chosen;
        $product->save();
    }

    /**
     * Split a money value into cost-code tokens (digits + optional '.').
     * Keeps cents like 55.50 (does not drop trailing zero via float cast).
     *
     * @param  mixed  $price
     * @return array<int, string>
     */
    protected function costcodePriceTokens($price): array
    {
        if ($price === null || $price === '') {
            return [];
        }

        $raw = str_replace(',', '.', trim((string) $price));
        if (! is_numeric($raw)) {
            return [];
        }

        $float = (float) $raw;
        if (! is_finite($float)) {
            return [];
        }

        // Preserve fractional part when present (including .50). Whole numbers stay integer.
        $hasDot = strpos($raw, '.') !== false;
        if ($hasDot) {
            $frac = substr($raw, strpos($raw, '.') + 1);
            $fracSignificant = rtrim($frac, '0');
            if ($fracSignificant === '') {
                // 55.00 / 55.0000 → treat as whole number
                $formatted = (string) (int) round($float);
            } else {
                // Keep at least 2 decimal places for money (55.5 → 55.50, 55.50 stays)
                $decimals = max(2, strlen($fracSignificant));
                $decimals = min(4, $decimals);
                $formatted = number_format($float, $decimals, '.', '');
            }
        } else {
            $formatted = (string) (int) round($float);
        }

        $tokens = [];
        foreach (str_split($formatted) as $ch) {
            if ($ch === '.' || ctype_digit($ch)) {
                $tokens[] = $ch;
            }
        }

        return $tokens;
    }

    /**
     * Build unique cost-code candidates by permuting letters at repeat positions.
     * Example: EKXY (positions of X,Y) also yields EKYX.
     */
    protected function costcodeUniqueCandidates(string $code, array $repeatPositions): array
    {
        $repeatPositions = array_values(array_unique(array_filter($repeatPositions, function ($pos) use ($code) {
            return is_int($pos) && $pos >= 0 && $pos < strlen($code);
        })));

        if (count($repeatPositions) < 2) {
            return [$code];
        }

        $letters = [];
        foreach ($repeatPositions as $pos) {
            $letters[] = $code[$pos];
        }

        $perms = $this->costcodePermuteUnique($letters);
        $candidates = [];
        foreach ($perms as $perm) {
            $chars = str_split($code);
            foreach ($repeatPositions as $i => $pos) {
                $chars[$pos] = $perm[$i];
            }
            $candidates[] = implode('', $chars);
        }

        return array_values(array_unique($candidates));
    }

    /**
     * Unique permutations of a letter list (multiset-safe).
     */
    protected function costcodePermuteUnique(array $items): array
    {
        $items = array_values($items);
        $n = count($items);
        if ($n <= 1) {
            return [$items];
        }

        $results = [];
        $used = [];
        for ($i = 0; $i < $n; $i++) {
            if (isset($used[$items[$i]])) {
                continue;
            }
            $used[$items[$i]] = true;
            $rest = $items;
            array_splice($rest, $i, 1);
            foreach ($this->costcodePermuteUnique($rest) as $perm) {
                $results[] = array_merge([$items[$i]], $perm);
            }
        }

        return $results;
    }

    /**
     * Create variable type product variation
     *
     * @param (int or object) $product
     * @param $input_variations
     * @return bool
     */
    public function createVariableProductVariations($product, $input_variations, $sku_type, $business_id = null, )
    {
        if (! is_object($product)) {
            $product = Product::find($product);
        }




        //create product variations
        foreach ($input_variations as $key => $value) {
            $images = [];
            $variation_template_name = ! empty($value['name']) ? $value['name'] : null;
            $variation_template_id = ! empty($value['variation_template_id']) ? $value['variation_template_id'] : null;

            if (empty($variation_template_id)) {
                if ($variation_template_name != 'DUMMY') {
                    $variation_template = VariationTemplate::where('business_id', $business_id)
                                                        ->whereRaw('LOWER(name)="'.strtolower($variation_template_name).'"')
                                                        ->with(['values'])
                                                        ->first();
                    if (empty($variation_template)) {
                        $variation_template = VariationTemplate::create([
                            'name' => $variation_template_name,
                            'business_id' => $business_id,
                        ]);
                    }
                    $variation_template_id = $variation_template->id;
                }
            } else {
                $variation_template = VariationTemplate::with(['values'])->find($value['variation_template_id']);
                $variation_template_id = $variation_template->id;
                $variation_template_name = $variation_template->name;
            }

            $product_variation_data = [
                'name' => $variation_template_name,
                'product_id' => $product->id,
                'is_dummy' => 0,
                'variation_template_id' => $variation_template_id,
            ];
            $product_variation = ProductVariation::create($product_variation_data);

            //create variations
            if (! empty($value['variations'])) {
                $variation_data = [];

                $c = Variation::withTrashed()
                        ->where('product_id', $product->id)
                        ->count() + 1;

                foreach ($value['variations'] as $k => $v) {
                    //skip hidden variations
                    if (isset($v['is_hidden']) && $v['is_hidden'] == 1) {
                        continue;
                    }

                    $sub_sku = empty($v['sub_sku']) ? $this->generateSubSku($product->sku, $c, $product->barcode_type, $v['value'], $sku_type) : $v['sub_sku'];
                    $variation_value_id = ! empty($v['variation_value_id']) ? $v['variation_value_id'] : null;
                    $variation_value_name = ! empty($v['value']) ? $v['value'] : null;

                    if (! empty($variation_value_id)) {
                        $variation_value = $variation_template->values->filter(function ($item) use ($variation_value_id) {
                            return $item->id == $variation_value_id;
                        })->first();
                        $variation_value_name = $variation_value->name;
                    } else {
                        if (! empty($variation_template)) {
                            $variation_value = VariationValueTemplate::where('variation_template_id', $variation_template->id)
                                ->whereRaw('LOWER(name)="'.$variation_value_name.'"')
                                ->first();
                            if (empty($variation_value)) {
                                $variation_value = VariationValueTemplate::create([
                                    'name' => $variation_value_name,
                                    'variation_template_id' => $variation_template->id,
                                ]);
                            }
                            $variation_value_id = $variation_value->id;
                            $variation_value_name = $variation_value->name;
                        } else {
                            $variation_value_id = null;
                            $variation_value_name = $variation_value_name;
                        }
                    }

                    $variation_data[] = [
                        'name' => $variation_value_name,
                        'variation_value_id' => $variation_value_id,
                        'product_id' => $product->id,
                        'sub_sku' => $sub_sku,
                        'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax']),
                    ];
                    $c++;
                    $images[] = 'variation_images_'.$key.'_'.$k;
                }
                $variations = $product_variation->variations()->createMany($variation_data);

                $i = 0;
                foreach ($variations as $variation) {
                    Media::uploadMedia($product->business_id, $variation, request(), $images[$i]);
                    $i++;
                }
            }
        }
    }

    /**
     * Update variable type product variation
     *
     * @param $product_id
     * @param $input_variations_edit
     * @return bool
     */
    public function updateVariableProductVariations($product_id, $input_variations_edit, $sku_type)
    {
        $product = Product::find($product_id);

        //Update product variations
        $product_variation_ids = [];
        $variations_ids = [];

        foreach ($input_variations_edit as $key => $value) {
            $product_variation_ids[] = $key;

            $product_variation = ProductVariation::find($key);
            $product_variation->name = $value['name'];
            $product_variation->save();

            //Update existing variations
            if (! empty($value['variations_edit'])) {
                foreach ($value['variations_edit'] as $k => $v) {
                    $data = [
                        'name' => $v['value'],
                        'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax']),
                    ];
                    if (! empty($v['sub_sku'])) {
                        $data['sub_sku'] = $v['sub_sku'];
                    }
                    $variation = Variation::where('id', $k)
                            ->where('product_variation_id', $key)
                            ->first();

                    $variation->update($data);

                    Media::uploadMedia($product->business_id, $variation, request(), 'edit_variation_images_'.$key.'_'.$k);

                    $variations_ids[] = $k;
                }
            }

            //Add new variations
            if (! empty($value['variations'])) {
                $variation_data = [];
                $c = Variation::withTrashed()
                                ->where('product_id', $product->id)
                                ->count() + 1;
                $media = [];
                foreach ($value['variations'] as $k => $v) {
                    $sub_sku = empty($v['sub_sku']) ? $this->generateSubSku($product->sku, $c, $product->barcode_type, $v['value'] , $sku_type) : $v['sub_sku'];

                    $variation_value_name = ! empty($v['value']) ? $v['value'] : null;
                    $variation_value_id = null;

                    if (! empty($product_variation->variation_template_id)) {
                        $variation_value = VariationValueTemplate::where('variation_template_id', $product_variation->variation_template_id)
                                ->whereRaw('LOWER(name)="'.$v['value'].'"')
                                ->first();
                        if (empty($variation_value)) {
                            $variation_value = VariationValueTemplate::create([
                                'name' => $v['value'],
                                'variation_template_id' => $product_variation->variation_template_id,
                            ]);
                        }

                        $variation_value_id = $variation_value->id;
                    }

                    $variation_data[] = [
                        'name' => $variation_value_name,
                        'variation_value_id' => $variation_value_id,
                        'product_id' => $product->id,
                        'sub_sku' => $sub_sku,
                        'default_purchase_price' => $this->num_uf($v['default_purchase_price']),
                        'dpp_inc_tax' => $this->num_uf($v['dpp_inc_tax']),
                        'profit_percent' => $this->num_uf($v['profit_percent']),
                        'default_sell_price' => $this->num_uf($v['default_sell_price']),
                        'sell_price_inc_tax' => $this->num_uf($v['sell_price_inc_tax']),
                    ];
                    $c++;
                    $media[] = 'variation_images_'.$key.'_'.$k;
                }
                $new_variations = $product_variation->variations()->createMany($variation_data);

                $i = 0;
                foreach ($new_variations as $new_variation) {
                    $variations_ids[] = $new_variation->id;
                    Media::uploadMedia($product->business_id, $new_variation, request(), $media[$i]);
                    $i++;
                }
            }
        }

        //get removed variations
        $removed_variations = Variation::whereNotIn('id', $variations_ids)
                ->where('product_id', $product->id)
                ->pluck('id');

        foreach ($removed_variations as $removed_variation_id) {
            //Check if purchase or sell exist for the deletable variation
            $count_purchase = PurchaseLine::join(
                'transactions as T',
                'purchase_lines.transaction_id',
                '=',
                'T.id'
                )
                  ->where('T.type', 'purchase')
                  ->where('T.status', 'received')
                  ->where('T.business_id', $product->business_id)
                  ->where('purchase_lines.product_id', $product->id)
                  ->where('purchase_lines.variation_id', $removed_variation_id)
                  ->count();

            $count_sell = TransactionSellLine::join(
                'transactions as T',
                'transaction_sell_lines.transaction_id',
                '=',
                'T.id'
                )
                  ->where('T.type', 'sell')
                  ->where('T.status', 'final')
                  ->where('T.business_id', $product->business_id)
                  ->where('transaction_sell_lines.product_id', $product->id)
                  ->where('transaction_sell_lines.variation_id', $removed_variation_id)
                  ->count();

            $is_variation_delatable = $count_purchase > 0 || $count_sell > 0 ? false : true;

            //if purchase sell dont exists delete the variation
            if ($is_variation_delatable) {
                Variation::where('id', $removed_variation_id)
                    ->delete();
            } else {
                throw new \Exception(__('lang_v1.purchase_already_exist'));
            }
        }

        ProductVariation::where('product_id', $product_id)
                ->whereNotIn('id', $product_variation_ids)
                ->delete();
    }

    /**
     * Checks if products has manage stock enabled then Updates quantity for product and its
     * variations
     *
     * @param $location_id
     * @param $product_id
     * @param $variation_id
     * @param $new_quantity
     * @param $old_quantity = 0
     * @param $number_format = null
     * @param $uf_data = true, if false it will accept numbers in database format
     * @return bool
     */
    public function updateProductQuantity($location_id, $product_id, $variation_id, $new_quantity, $old_quantity = 0, $number_format = null, $uf_data = true)
    {
        if ($uf_data) {
            $qty_difference = $this->num_uf($new_quantity, $number_format) - $this->num_uf($old_quantity, $number_format);
        } else {
            $qty_difference = $new_quantity - $old_quantity;
        }

        $product = Product::find($product_id);

        //Check if stock is enabled or not.
        if ($product->enable_stock == 1 && $qty_difference != 0) {
            $variation = Variation::where('id', $variation_id)
                            ->where('product_id', $product_id)
                            ->first();

            //Add quantity in VariationLocationDetails
            $variation_location_d = VariationLocationDetails::where('variation_id', $variation->id)
                          ->where('product_id', $product_id)
                          ->where('product_variation_id', $variation->product_variation_id)
                          ->where('location_id', $location_id)
                          ->first();

            if (empty($variation_location_d)) {
                $variation_location_d = new VariationLocationDetails();
                $variation_location_d->variation_id = $variation->id;
                $variation_location_d->product_id = $product_id;
                $variation_location_d->location_id = $location_id;
                $variation_location_d->product_variation_id = $variation->product_variation_id;
                $variation_location_d->qty_available = 0;
            }

            $variation_location_d->qty_available += $qty_difference;
            $variation_location_d->save();
        }

        return true;
    }

    /**
     * Checks if products has manage stock enabled then Decrease quantity for product and its variations
     *
     * @param $product_id
     * @param $variation_id
     * @param $location_id
     * @param $new_quantity
     * @param $old_quantity = 0
     * @return bool
     */
    public function decreaseProductQuantity($product_id, $variation_id, $location_id, $new_quantity, $old_quantity = 0)
    {
        $qty_difference = $new_quantity - $old_quantity;

        $product = Product::find($product_id);

        //Check if stock is enabled or not.
        if ($product->enable_stock == 1) {
            //Decrement Quantity in variations location table
            $details = VariationLocationDetails::where('variation_id', $variation_id)
                ->where('product_id', $product_id)
                ->where('location_id', $location_id)
                ->first();

            //If location details not exists create new one
            if (empty($details)) {
                $variation = Variation::find($variation_id);
                $details = VariationLocationDetails::create([
                    'product_id' => $product_id,
                    'location_id' => $location_id,
                    'variation_id' => $variation_id,
                    'product_variation_id' => $variation->product_variation_id,
                    'qty_available' => 0,
                ]);
            }

            $details->decrement('qty_available', $qty_difference);
        }

        return true;
    }

    /**
     * Decrease the product quantity of combo sub-products
     *
     * @param $combo_details
     * @param $location_id
     * @return void
     */
    public function decreaseProductQuantityCombo($combo_details, $location_id)
    {
        //product_id = child product id
        //variation id is child product variation id
        foreach ($combo_details as $details) {
            $this->decreaseProductQuantity(
                $details['product_id'],
                $details['variation_id'],
                $location_id,
                $details['quantity']
            );
        }
    }

    /**
     * Get all details for a product from its variation id
     *
     * @param  int  $variation_id
     * @param  int  $business_id
     * @param  int  $location_id
     * @param  bool  $check_qty (If false qty_available is not checked)
     * @param  bool  $allow_zero_qty (If true, allow qty_available = 0 when $check_qty is true)
     * @return array
     */
    public function getDetailsFromVariation($variation_id, $business_id, $location_id = null, $check_qty = true, $allow_zero_qty = false)
    {
        $variation = Variation::with('media')->findOrFail($variation_id);

        $query = Variation::join('products AS p', 'variations.product_id', '=', 'p.id')
                ->join('product_variations AS pv', 'variations.product_variation_id', '=', 'pv.id')
                ->leftjoin('variation_location_details AS vld', 'variations.id', '=', 'vld.variation_id')
                ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                ->leftjoin('units as u', 'p.secondary_unit_id', '=', 'u.id')
                ->leftjoin('brands', function ($join) {
                    $join->on('p.brand_id', '=', 'brands.id')
                        ->whereNull('brands.deleted_at');
                })
                ->where('p.business_id', $business_id)
                ->where('variations.id', $variation_id);

        $has_supplier_col = \Illuminate\Support\Facades\Schema::hasColumn('products', 'supplier_id');
        if ($has_supplier_col) {
            $query->leftjoin('contacts as product_supplier', 'p.supplier_id', '=', 'product_supplier.id');
        }

        //Add condition for check of quantity. (if stock is not enabled or qty_available > 0)
        if ($check_qty) {
            //Default behavior: enforce positive qty when stock is enabled.
            //Opt-in override: when $allow_zero_qty is true, allow qty_available = 0.
            if (! $allow_zero_qty) {
                $query->where(function ($query) {
                    $query->where('p.enable_stock', '!=', 1)
                        ->orWhere('vld.qty_available', '>', 0);
                });
            }
        }

        if (! empty($location_id) && $check_qty) {
            //Check for enable stock, if enabled check for location id.
            $query->where(function ($query) use ($location_id) {
                $query->where('p.enable_stock', '!=', 1)
                            ->orWhere('vld.location_id', $location_id);
            });
        }

        $has_tyre_size = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size');
        // Avoid "215/55/17 Ceat Jambo - 215/55/17" when name already includes tyre size
        $product_name_sql = $has_tyre_size
            ? "IF(pv.is_dummy = 0, CONCAT(IF(p.tyre_size IS NULL OR TRIM(p.tyre_size) = '' OR LOCATE(LOWER(TRIM(p.tyre_size)), LOWER(p.name)) > 0, p.name, CONCAT(p.name, ' - ', p.tyre_size)), ' (', pv.name, ':',variations.name, ')'), IF(p.tyre_size IS NULL OR TRIM(p.tyre_size) = '' OR LOCATE(LOWER(TRIM(p.tyre_size)), LOWER(p.name)) > 0, p.name, CONCAT(p.name, ' - ', p.tyre_size)))"
            : "IF(pv.is_dummy = 0, CONCAT(p.name, ' (', pv.name, ':',variations.name, ')'), p.name)";

        $select = [
            DB::raw($product_name_sql.' AS product_name'),
            'p.id as product_id',
            'p.brand_id',
            'p.category_id',
            'p.tax as tax_id',
            'p.enable_stock',
            'p.enable_sr_no',
            'p.type as product_type',
            'p.name as product_actual_name',
            $has_tyre_size ? 'p.tyre_size' : DB::raw('NULL as tyre_size'),
            'p.warranty_id',
            'p.image as product_image',
            'p.product_custom_field1',
            'p.product_custom_field2',
            'p.product_custom_field3',
            'p.product_custom_field4',
            'p.product_custom_field5',
            'p.product_custom_field6',
            'p.product_custom_field7',
            'p.product_custom_field8',
            'p.product_custom_field9',
            'p.product_custom_field10',
            'p.product_custom_field11',
            'p.product_custom_field12',
            'p.product_custom_field13',
            'p.product_custom_field14',
            'p.product_custom_field15',
            'p.product_custom_field16',
            'p.product_custom_field17',
            'p.product_custom_field18',
            'p.product_custom_field19',
            'p.product_custom_field20',
            'pv.name as product_variation_name',
            'pv.is_dummy as is_dummy',
            'variations.name as variation_name',
            'variations.sub_sku',
            'p.barcode_type',
            'vld.qty_available',
            'variations.default_sell_price',
            'variations.default_purchase_price',
            'variations.dpp_inc_tax',
            'variations.sell_price_inc_tax',
            'variations.id as variation_id',
            'variations.combo_variations',  //Used in combo products
            'units.short_name as unit',
            'units.id as unit_id',
            'units.allow_decimal as unit_allow_decimal',
            'u.short_name as second_unit',
            'brands.name as brand',
            DB::raw('(SELECT purchase_price_inc_tax FROM purchase_lines WHERE 
                        variation_id=variations.id ORDER BY id DESC LIMIT 1) as last_purchased_price'),
        ];
        if ($has_supplier_col) {
            $select[] = 'p.supplier_id';
            $select[] = DB::raw("IF(product_supplier.supplier_business_name IS NULL OR product_supplier.supplier_business_name = '', product_supplier.name, product_supplier.supplier_business_name) as supplier_name");
        } else {
            $select[] = DB::raw('NULL as supplier_id');
            $select[] = DB::raw("'' as supplier_name");
        }

        $product = $query->select($select)
        ->firstOrFail();

        $product->media = $variation->media;

        if ($product->product_type == 'combo') {
            if ($check_qty) {
                $product->qty_available = $this->calculateComboQuantity($location_id, $product->combo_variations);
            }

            $product->combo_products = $this->calculateComboDetails($location_id, $product->combo_variations);
        }

        return $product;
    }

    /**
     * Calculates the quantity of combo products based on
     * the quantity of variation items used.
     *
     * @param  int  $location_id
     * @param  array  $combo_variations
     * @return int
     */
    public function calculateComboQuantity($location_id, $combo_variations)
    {
        //get stock of the items and calcuate accordingly.
        $combo_qty = 0;
        foreach ($combo_variations as $key => $value) {
            $variation = Variation::with(['product', 'variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])->findOrFail($value['variation_id']);

            $product = $variation->product;

            //Dont calculate stock if disabled
            if ($product->enable_stock != 1) {
                continue;
            }

            $vld = $variation->variation_location_details
                          ->first();

            $variation_qty = ! empty($vld) ? $vld->qty_available : 0;
            $multiplier = $this->getMultiplierOf2Units($product->unit_id, $value['unit_id']);

            if ($combo_qty == 0) {
                $combo_qty = ($variation_qty / $multiplier) / $combo_variations[$key]['quantity'];
            } else {
                $combo_qty = min($combo_qty, ($variation_qty / $multiplier) / $combo_variations[$key]['quantity']);
            }
        }

        return floor($combo_qty);
    }

    /**
     * Calculates the quantity of combo products based on
     * the quantity of variation items used.
     *
     * @param  int  $location_id
     * @param  array  $combo_variations
     * @return int
     */
    public function calculateComboDetails($location_id, $combo_variations)
    {
        $details = [];

        foreach ($combo_variations as $key => $value) {
            $variation = Variation::with(['product', 'variation_location_details' => function ($q) use ($location_id) {
                $q->where('location_id', $location_id);
            }])->findOrFail($value['variation_id']);

            $vld = $variation->variation_location_details->first();

            $variation_qty = ! empty($vld) ? $vld->qty_available : 0;
            $multiplier = $this->getMultiplierOf2Units($variation->product->unit_id, $value['unit_id']);

            $details[] = [
                'variation_id' => $value['variation_id'],
                'product_id' => $variation->product_id,
                'qty_required' => $this->num_uf($value['quantity']) * $multiplier,
                'enable_stock' => $variation->product->enable_stock,
            ];
        }

        return $details;
    }

    /**
     * Calculates the total amount of invoice
     *
     * @param  array  $products
     * @param  int  $tax_id
     * @param  array  $discount['discount_type', 'discount_amount']
     * @return mixed (false, array)
     */
    public function calculateInvoiceTotal($products, $tax_id, $discount = null, $uf_number = true)
    {
        if (empty($products)) {
            return false;
        }

        $output = ['total_before_tax' => 0, 'tax' => 0, 'discount' => 0, 'final_total' => 0];

        //Sub Total
        foreach ($products as $product) {
            $unit_price = isset($product['unit_price'])
                ? ($uf_number ? $this->num_uf($product['unit_price']) : $product['unit_price'])
                : 0;
            $unit_price_inc_tax = isset($product['unit_price_inc_tax'])
                ? ($uf_number ? $this->num_uf($product['unit_price_inc_tax']) : $product['unit_price_inc_tax'])
                : $unit_price;

            // No line tax: never let a stale unit_price_inc_tax inflate the invoice total
            if (empty($product['tax_id'])) {
                $discounted_unit_price = $unit_price;
                if (! empty($product['line_discount_type']) && ! empty($product['line_discount_amount'])) {
                    $discount_amount = $uf_number ? $this->num_uf($product['line_discount_amount']) : $product['line_discount_amount'];
                    if ($product['line_discount_type'] == 'fixed') {
                        $discounted_unit_price = $unit_price - $discount_amount;
                    } elseif ($product['line_discount_type'] == 'percentage') {
                        $discounted_unit_price = ((100 - $discount_amount) * $unit_price) / 100;
                    }
                }
                $unit_price_inc_tax = $discounted_unit_price;
            }

            $quantity = $uf_number ? $this->num_uf($product['quantity']) : $product['quantity'];

            $output['total_before_tax'] += $quantity * $unit_price_inc_tax;

            //Add modifier price to total if exists
            if (! empty($product['modifier_price'])) {
                foreach ($product['modifier_price'] as $key => $modifier_price) {
                    $modifier_price = $uf_number ? $this->num_uf($modifier_price) : $modifier_price;
                    $uf_modifier_price = $uf_number ? $this->num_uf($modifier_price) : $modifier_price;
                    $modifier_qty = isset($product['modifier_quantity'][$key]) ? $product['modifier_quantity'][$key] : 0;
                    $modifier_total = $uf_modifier_price * $modifier_qty;
                    $output['total_before_tax'] += $modifier_total;
                }
            }
        }

        //Calculate discount
        if (is_array($discount)) {
            $discount_amount = $uf_number ? $this->num_uf($discount['discount_amount']) : $discount['discount_amount'];
            if ($discount['discount_type'] == 'fixed') {
                $output['discount'] = $discount_amount;
            } else {
                $output['discount'] = ($discount_amount / 100) * $output['total_before_tax'];
            }
        }

        //Tax
        $output['tax'] = 0;
        if (! empty($tax_id)) {
            $tax_details = TaxRate::find($tax_id);
            if (! empty($tax_details)) {
                $output['tax_id'] = $tax_id;
                $output['tax'] = ($tax_details->amount / 100) * ($output['total_before_tax'] - $output['discount']);
            }
        }

        //Calculate total
        $output['final_total'] = $output['total_before_tax'] + $output['tax'] - $output['discount'];

        return $output;
    }

    /**
     * Generates product sku
     *
     * @param  string  $string
     * @return generated sku (string)
     */
    public function generateProductSku($string)
    {
        $business_id = request()->session()->get('user.business_id');
        $sku_prefix = Business::where('id', $business_id)->value('sku_prefix');

        return $sku_prefix.str_pad($string, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Gives list of trending products
     *
     * @param  int  $business_id
     * @param  array  $filters
     * @return Obj
     */
    public function getTrendingProducts($business_id, $filters = [])
    {
        $query = Transaction::join(
            'transaction_sell_lines as tsl',
            'transactions.id',
            '=',
            'tsl.transaction_id'
        )
                    ->join('products as p', 'tsl.product_id', '=', 'p.id')
                    ->leftjoin('units as u', 'u.id', '=', 'p.unit_id')
                    ->where('transactions.business_id', $business_id)
                    ->where('transactions.type', 'sell')
                    ->where('transactions.status', 'final');

        $permitted_locations = auth()->user()->permitted_locations();
        if ($permitted_locations != 'all') {
            $query->whereIn('transactions.location_id', $permitted_locations);
        }
        if (! empty($filters['location_id'])) {
            $query->where('transactions.location_id', $filters['location_id']);
        }
        if (! empty($filters['category'])) {
            $query->where('p.category_id', $filters['category']);
        }
        if (! empty($filters['sub_category'])) {
            $query->where('p.sub_category_id', $filters['sub_category']);
        }
        if (! empty($filters['brand'])) {
            $query->where('p.brand_id', $filters['brand']);
        }
        if (! empty($filters['unit'])) {
            $query->where('p.unit_id', $filters['unit']);
        }
        if (! empty($filters['limit'])) {
            $query->limit($filters['limit']);
        } else {
            $query->limit(5);
        }

        if (! empty($filters['product_type'])) {
            $query->where('p.type', $filters['product_type']);
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $query->whereBetween(DB::raw('date(transaction_date)'), [$filters['start_date'],
                $filters['end_date'], ]);
        }

        // $sell_return_query = "(SELECT SUM(TPL.quantity) FROM transactions AS T JOIN purchase_lines AS TPL ON T.id=TPL.transaction_id WHERE TPL.product_id=tsl.product_id AND T.type='sell_return'";
        // if ($permitted_locations != 'all') {
        //     $sell_return_query .= ' AND T.location_id IN ('
        //      . implode(',', $permitted_locations) . ') ';
        // }
        // if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        //     $sell_return_query .= ' AND date(T.transaction_date) BETWEEN \'' . $filters['start_date'] . '\' AND \'' . $filters['end_date'] . '\'';
        // }
        // $sell_return_query .= ')';

        $products = $query->select(
            DB::raw('(SUM(tsl.quantity) - COALESCE(SUM(tsl.quantity_returned), 0)) as total_unit_sold'),
            'p.name as product',
            'u.short_name as unit',
            'p.sku'
        )->whereNull('tsl.parent_sell_line_id')
                        ->groupBy('tsl.product_id')
                        ->orderBy('total_unit_sold', 'desc')
                        ->get();

        return $products;
    }

    /**
     * Gives list of products based on products id and variation id
     *
     * @param  int  $business_id
     * @param  int  $product_id
     * @param  int  $variation_id = null
     * @return Obj
     */
    public function getDetailsFromProduct($business_id, $product_id, $variation_id = null)
    {
        $product = Product::leftjoin('variations as v', 'products.id', '=', 'v.product_id')
                        ->whereNull('v.deleted_at')
                        ->where('products.business_id', $business_id);

        if (! is_null($variation_id) && $variation_id !== '0') {
            $product->where('v.id', $variation_id);
        }

        $product->where('products.id', $product_id);

        $products = $product->select(
            'products.id as product_id',
            'products.name as product_name',
            'v.id as variation_id',
            'v.name as variation_name'
        )
                    ->get();

        return $products;
    }

    /**
     * F => D (Previous product Increase)
     * D => F (All product decrease)
     * F => F (Newly added product drerease)
     *
     * @param  object  $transaction_before
     * @param  object  $transaction
     * @param  array  $input
     * @return void
     */
    public function adjustProductStockForInvoice($status_before, $transaction, $input, $uf_data = true)
    {
        if ($status_before == 'final' && $transaction->status == 'draft') {
            foreach ($input['products'] as $product) {
                if (! empty($product['transaction_sell_lines_id'])) {
                    $this->updateProductQuantity($input['location_id'], $product['product_id'], $product['variation_id'], $product['quantity'], 0, null, false);

                    //Adjust quantity for combo items.
                    if (isset($product['product_type']) && $product['product_type'] == 'combo') {
                        //Giving quantity in minus will increase the qty
                        foreach ($product['combo'] as $value) {
                            $this->updateProductQuantity($input['location_id'], $value['product_id'], $value['variation_id'], $value['quantity'], 0, null, false);
                        }

                        // $this->updateEditedSellLineCombo($product['combo'], $input['location_id']);
                    }
                }
            }
        } elseif ($status_before == 'draft' && $transaction->status == 'final') {
            foreach ($input['products'] as $product) {
                $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];

                $this->decreaseProductQuantity(
                    $product['product_id'],
                    $product['variation_id'],
                    $input['location_id'],
                    $uf_quantity
                );

                //Adjust quantity for combo items.
                if (isset($product['product_type']) && $product['product_type'] == 'combo') {
                    $this->decreaseProductQuantityCombo($product['combo'], $input['location_id']);

                    //$this->decreaseProductQuantityCombo($product['variation_id'], $input['location_id'], $uf_quantity);
                }
            }
        } elseif ($status_before == 'final' && $transaction->status == 'final') {
            foreach ($input['products'] as $product) {
                if (empty($product['transaction_sell_lines_id'])) {
                    $uf_quantity = $uf_data ? $this->num_uf($product['quantity']) : $product['quantity'];
                    $this->decreaseProductQuantity(
                        $product['product_id'],
                        $product['variation_id'],
                        $input['location_id'],
                        $uf_quantity
                    );

                    //Adjust quantity for combo items.
                    if (isset($product['product_type']) && $product['product_type'] == 'combo') {
                        $this->decreaseProductQuantityCombo($product['combo'], $input['location_id']);

                        //$this->decreaseProductQuantityCombo($product['variation_id'], $input['location_id'], $uf_quantity);
                    }
                }
            }
        }
    }

    /**
     * Updates variation from purchase screen
     *
     * @param  array  $variation_data
     * @return void
     */
    public function updateProductFromPurchase($variation_data)
    {
        $variation_details = Variation::where('id', $variation_data['variation_id'])
                                        ->with(['product', 'product.product_tax'])
                                        ->first();
        $tax_rate = 0;
        if (! empty($variation_details->product->product_tax->amount)) {
            $tax_rate = $variation_details->product->product_tax->amount;
        }

        if (! isset($variation_data['sell_price_inc_tax'])) {
            $variation_data['sell_price_inc_tax'] = $variation_details->sell_price_inc_tax;
        }

        if (($variation_details->default_purchase_price != $variation_data['pp_without_discount']) ||
            ($variation_details->sell_price_inc_tax != $variation_data['sell_price_inc_tax'])
            ) {
            //Set default purchase price exc. tax
            $variation_details->default_purchase_price = $variation_data['pp_without_discount'];

            //Set default purchase price inc. tax
            $variation_details->dpp_inc_tax = $this->calc_percentage($variation_details->default_purchase_price, $tax_rate, $variation_details->default_purchase_price);

            //Set default sell price inc. tax
            $variation_details->sell_price_inc_tax = $variation_data['sell_price_inc_tax'];

            //set sell price inc. tax
            $variation_details->default_sell_price = $this->calc_percentage_base($variation_details->sell_price_inc_tax, $tax_rate);

            //set profit margin
            $variation_details->profit_percent = $this->get_percent($variation_details->default_purchase_price, $variation_details->default_sell_price);

            $variation_details->save();
        }
    }

    /**
     * Generated SKU based on the barcode type.
     *
     * @param  string  $sku
     * @param  string  $c
     * @param  string  $barcode_type
     * @return void
     */
    public function generateSubSku($sku, $c, $barcode_type, $value, $sku_type)
    {
        
        // old formate
        if($sku_type == 'with_out_variation'){

            $sub_sku = $sku.$c;
            
            if (in_array($barcode_type, ['C128', 'C39'])) {
                $sub_sku = $sku.'-'.$c;
            }

        }else {

            // new formate
            $sub_sku = $sku.preg_replace('/[^a-zA-Z0-9]/', '', $value);
            
            if (in_array($barcode_type, ['C128', 'C39'])) {
                $sub_sku = $sku.preg_replace('/[^a-zA-Z0-9]/', '', $value);
            }
        }
        

        return $sub_sku;
    }

    /**
     * Add rack details.
     *
     * @param  int  $business_id
     * @param  int  $product_id
     * @param  array  $product_racks
     * @param  array  $product_racks
     * @return void
     */
    public function addRackDetails($business_id, $product_id, $product_racks)
    {
        if (! empty($product_racks)) {
            $data = [];
            foreach ($product_racks as $location_id => $detail) {
                $data[] = ['business_id' => $business_id,
                    'location_id' => $location_id,
                    'product_id' => $product_id,
                    'rack' => ! empty($detail['rack']) ? $detail['rack'] : null,
                    'row' => ! empty($detail['row']) ? $detail['row'] : null,
                    'position' => ! empty($detail['position']) ? $detail['position'] : null,
                    'created_at' => \Carbon::now()->toDateTimeString(),
                    'updated_at' => \Carbon::now()->toDateTimeString(),
                ];
            }

            ProductRack::insert($data);
        }
    }

    /**
     * Get rack details.
     *
     * @param  int  $business_id
     * @param  int  $product_id
     * @return void
     */
    public function getRackDetails($business_id, $product_id, $get_location = false)
    {
        $query = ProductRack::where('product_racks.business_id', $business_id)
                    ->where('product_id', $product_id);

        if ($get_location) {
            $racks = $query->join('business_locations AS BL', 'product_racks.location_id', '=', 'BL.id')
                ->select(['product_racks.rack',
                    'product_racks.row',
                    'product_racks.position',
                    'BL.name', ])
                ->get();
        } else {
            $racks = collect($query->select(['rack', 'row', 'position', 'location_id'])->get());

            $racks = $racks->mapWithKeys(function ($item, $key) {
                return [$item['location_id'] => $item->toArray()];
            })->toArray();
        }

        return $racks;
    }

    /**
     * Update rack details.
     *
     * @param  int  $business_id
     * @param  int  $product_id
     * @param  array  $product_racks
     * @return void
     */
    public function updateRackDetails($business_id, $product_id, $product_racks)
    {
        if (! empty($product_racks)) {
            foreach ($product_racks as $location_id => $details) {
                ProductRack::where('business_id', $business_id)
                    ->where('product_id', $product_id)
                    ->where('location_id', $location_id)
                    ->update(['rack' => ! empty($details['rack']) ? $details['rack'] : null,
                        'row' => ! empty($details['row']) ? $details['row'] : null,
                        'position' => ! empty($details['position']) ? $details['position'] : null,
                    ]);
            }
        }
    }

    /**
     * Retrieves selling price group price for a product variation.
     *
     * @param  int  $variation_id
     * @param  int  $price_group_id
     * @param  int  $tax_id
     * @return decimal
     */
    public function getVariationGroupPrice($variation_id, $price_group_id, $tax_id)
    {
        $price_group = VariationGroupPrice::where('variation_id', $variation_id)
                        ->where('price_group_id', $price_group_id)
                        ->select(['price_inc_tax', 'price_type'])
                        ->first();

        if(!$price_group){
            return [
                'price_inc_tax' => '',
                'price_exc_tax' => '',
            ];
        }

        if(isset($price_group->price_type) && $price_group->price_type == 'percentage'){
            //calculate the price
            $variation = Variation::find($variation_id);
            $price_inc_tax = $this->calc_percentage($variation->sell_price_inc_tax, $price_group->price_inc_tax);
        } else {
            $price_inc_tax = $price_group->price_inc_tax;
        }

        $price_exc_tax = $price_inc_tax;
        if (! empty($price_inc_tax) && ! empty($tax_id)) {
            $tax_amount = TaxRate::where('id', $tax_id)->value('amount');
            $price_exc_tax = $this->calc_percentage_base($price_inc_tax, $tax_amount);
        }

        return [
            'price_inc_tax' => $price_inc_tax,
            'price_exc_tax' => $price_exc_tax,
        ];
    }

    /**
     * Creates new variation if not exists.
     *
     * @param  int  $business_id
     * @param  string  $name
     * @return obj
     */
    public function createOrNewVariation($business_id, $name)
    {
        $variation = VariationTemplate::where('business_id', $business_id)
                                    ->where('name', 'like', $name)
                                    ->with(['values'])
                                    ->first();

        if (empty($variation)) {
            $variation = VariationTemplate::create([
                'business_id' => $business_id,
                'name' => $name,
            ]);
        }

        return $variation;
    }

    /**
     * Adds opening stock to a single product.
     *
     * @param  int  $business_id
     * @param  obj  $product
     * @param  array  $input
     * @param  obj  $transaction_date
     * @param  int  $user_id
     * @return void
     */
    public function addSingleProductOpeningStock($business_id, $product, $input, $transaction_date, $user_id)
    {
        $locations = BusinessLocation::forDropdown($business_id)->toArray();

        $tax_percent = ! empty($product->product_tax->amount) ? $product->product_tax->amount : 0;
        $tax_id = ! empty($product->product_tax->id) ? $product->product_tax->id : null;

        foreach ($input as $key => $value) {
            $location_id = $key;
            $purchase_total = 0;
            //Check if valid location
            if (array_key_exists($location_id, $locations)) {
                $purchase_lines = [];

                $purchase_price = $this->num_uf(trim($value['purchase_price']));
                $item_tax = $this->calc_percentage($purchase_price, $tax_percent);
                $purchase_price_inc_tax = $purchase_price + $item_tax;
                $qty = $this->num_uf(trim($value['quantity']));

                $exp_date = null;
                if (! empty($value['exp_date'])) {
                    $exp_date = \Carbon::createFromFormat('d-m-Y', $value['exp_date'])->format('Y-m-d');
                }

                $lot_number = null;
                if (! empty($value['lot_number'])) {
                    $lot_number = $value['lot_number'];
                }

                if ($qty > 0) {
                    $qty_formated = $this->num_f($qty);
                    //Calculate transaction total
                    $purchase_total += ($purchase_price_inc_tax * $qty);
                    $variation_id = $product->variations->first()->id;

                    $purchase_line = new PurchaseLine();
                    $purchase_line->product_id = $product->id;
                    $purchase_line->variation_id = $variation_id;
                    $purchase_line->item_tax = $item_tax;
                    $purchase_line->tax_id = $tax_id;
                    $purchase_line->quantity = $qty;
                    $purchase_line->pp_without_discount = $purchase_price;
                    $purchase_line->purchase_price = $purchase_price;
                    $purchase_line->purchase_price_inc_tax = $purchase_price_inc_tax;
                    $purchase_line->exp_date = $exp_date;
                    $purchase_line->lot_number = $lot_number;
                    $purchase_lines[] = $purchase_line;

                    $this->updateProductQuantity($location_id, $product->id, $variation_id, $qty_formated);
                }

                //create transaction & purchase lines
                if (! empty($purchase_lines)) {
                    $transaction = Transaction::create(
                        [
                            'type' => 'opening_stock',
                            'opening_stock_product_id' => $product->id,
                            'status' => 'received',
                            'business_id' => $business_id,
                            'transaction_date' => $transaction_date,
                            'total_before_tax' => $purchase_total,
                            'location_id' => $location_id,
                            'final_total' => $purchase_total,
                            'payment_status' => 'paid',
                            'created_by' => $user_id,
                        ]
              );
                    $transaction->purchase_lines()->saveMany($purchase_lines);
                }
            }
        }
    }

    /**
     * Add/Edit transaction purchase lines
     *
     * @param  object  $transaction
     * @param  array  $input_data
     * @param  array  $currency_details
     * @param  bool  $enable_product_editing
     * @param  string  $before_status = null
     * @return array
     */
    public function createOrUpdatePurchaseLines($transaction, $input_data, $currency_details, $enable_product_editing, $before_status = null)
    {
        $updated_purchase_lines = [];
        $updated_purchase_line_ids = [0];
        $exchange_rate = ! empty($transaction->exchange_rate) ? $transaction->exchange_rate : 1;

        foreach ($input_data as $data) {
            $multiplier = 1;
            if (isset($data['sub_unit_id']) && $data['sub_unit_id'] == $data['product_unit_id']) {
                unset($data['sub_unit_id']);
            }

            if (! empty($data['sub_unit_id'])) {
                $unit = Unit::find($data['sub_unit_id']);
                $multiplier = ! empty($unit->base_unit_multiplier) ? $unit->base_unit_multiplier : 1;
            }
            $new_quantity = $this->num_uf($data['quantity']) * $multiplier;

            $new_quantity_f = $this->num_f($new_quantity);
            $old_qty = 0;
            //update existing purchase line
            if (isset($data['purchase_line_id'])) {
                $purchase_line = PurchaseLine::findOrFail($data['purchase_line_id']);
                $updated_purchase_line_ids[] = $purchase_line->id;
                $old_qty = $purchase_line->quantity;

                $this->updateProductStock($before_status, $transaction, $data['product_id'], $data['variation_id'], $new_quantity, $purchase_line->quantity, $currency_details);
            } else {
                //create newly added purchase lines
                $purchase_line = new PurchaseLine();
                $purchase_line->product_id = $data['product_id'];
                $purchase_line->variation_id = $data['variation_id'];

                //Increase quantity only if status is received
                if ($transaction->status == 'received') {
                    $this->updateProductQuantity($transaction->location_id, $data['product_id'], $data['variation_id'], $new_quantity_f, 0, $currency_details);
                }
            }

            $purchase_line->quantity = $new_quantity;
            $purchase_line->pp_without_discount = ($this->num_uf($data['pp_without_discount'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->discount_percent = $this->num_uf($data['discount_percent'], $currency_details);
            $purchase_line->purchase_price = ($this->num_uf($data['purchase_price'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->purchase_price_inc_tax = ($this->num_uf($data['purchase_price_inc_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->item_tax = ($this->num_uf($data['item_tax'], $currency_details) * $exchange_rate) / $multiplier;
            $purchase_line->tax_id = $data['purchase_line_tax_id'];
            $purchase_line->lot_number = ! empty($data['lot_number']) ? $data['lot_number'] : null;
            $purchase_line->mfg_date = ! empty($data['mfg_date']) ? $this->uf_date($data['mfg_date']) : null;
            $purchase_line->exp_date = ! empty($data['exp_date']) ? $this->uf_date($data['exp_date']) : null;
            $purchase_line->sub_unit_id = ! empty($data['sub_unit_id']) ? $data['sub_unit_id'] : null;
            $purchase_line->purchase_order_line_id = ! empty($data['purchase_order_line_id']) ? $data['purchase_order_line_id'] : null;
            if (isset($data['default_sell_price'])) {
                $purchase_line->sell_price_inc_tax = ($this->num_uf($data['default_sell_price'], $currency_details)) / $multiplier;
            }
            $purchase_line->purchase_requisition_line_id = ! empty($data['purchase_requisition_line_id']) && $transaction->type == 'purchase_order' ? $data['purchase_requisition_line_id'] : null;

            if (! empty($data['secondary_unit_quantity'])) {
                $purchase_line->secondary_unit_quantity = $this->num_uf($data['secondary_unit_quantity']);
            }

            // Nutmeg: capitalize line product expenses into unit stock cost (AVCO), not P&L
            if ($this->nutmegProductExpensesActive() && Schema::hasColumn('purchase_lines', 'product_expenses')) {
                $applied = $this->applyProductExpensesToPurchasePrices($data, $currency_details, $exchange_rate, $multiplier);
                $purchase_line->pp_without_discount = $applied['pp_without_discount'];
                $purchase_line->purchase_price = $applied['purchase_price'];
                $purchase_line->purchase_price_inc_tax = $applied['purchase_price_inc_tax'];
                $purchase_line->product_expenses = $applied['product_expenses'];
            }

            $updated_purchase_lines[] = $purchase_line;

            //Edit product price
            if ($enable_product_editing == 1 && ($transaction->type == 'purchase' || $transaction->type == 'production_purchase')) {
                if (isset($data['default_sell_price'])) {
                    $variation_data['sell_price_inc_tax'] = ($this->num_uf($data['default_sell_price'], $currency_details)) / $multiplier;
                }
                $variation_data['pp_without_discount'] = $purchase_line->pp_without_discount;
                $variation_data['variation_id'] = $purchase_line->variation_id;
                $variation_data['purchase_price'] = $purchase_line->purchase_price;

                $this->updateProductFromPurchase($variation_data);
            }

            if ($transaction->type == 'purchase_order') {
                //Update purchase requisition line quantity received
                $this->updatePurchaseOrderLine($purchase_line->purchase_requisition_line_id, $purchase_line->quantity, $old_qty);
            }

            //Update purchase order line quantity received
            $this->updatePurchaseOrderLine($purchase_line->purchase_order_line_id, $purchase_line->quantity, $old_qty);
        }

        //unset deleted purchase lines
        $delete_purchase_line_ids = [];
        $delete_purchase_lines = null;
        if (! empty($updated_purchase_line_ids)) {
            $delete_purchase_lines = PurchaseLine::where('transaction_id', $transaction->id)
                    ->whereNotIn('id', $updated_purchase_line_ids)
                    ->get();

            if ($delete_purchase_lines->count()) {
                foreach ($delete_purchase_lines as $delete_purchase_line) {
                    $delete_purchase_line_ids[] = $delete_purchase_line->id;

                    //decrease deleted only if previous status was received
                    if ($before_status == 'received') {
                        $this->decreaseProductQuantity(
                            $delete_purchase_line->product_id,
                            $delete_purchase_line->variation_id,
                            $transaction->location_id,
                            $delete_purchase_line->quantity
                        );
                    }

                    //If purchase order line set decrease quntity
                    if (! empty($delete_purchase_line->purchase_order_line_id)) {
                        $this->updatePurchaseOrderLine($delete_purchase_line->purchase_order_line_id, 0, $delete_purchase_line->quantity);
                    }

                    //If purchase order line set decrease quntity
                    if (! empty($delete_purchase_line->purchase_requisition_line_id)) {
                        $this->updatePurchaseOrderLine($delete_purchase_line->purchase_requisition_line_id, 0, $delete_purchase_line->quantity);
                    }
                }

                //unset if purchase order line from purchase lines if exists
                if ($transaction->type == 'purchase_order') {
                    PurchaseLine::whereIn('purchase_order_line_id', $delete_purchase_line_ids)
                        ->update(['purchase_order_line_id' => null]);
                }

                //Delete deleted purchase lines
                PurchaseLine::where('transaction_id', $transaction->id)
                        ->whereIn('id', $delete_purchase_line_ids)
                        ->delete();
            }
        }

        //update purchase lines
        if (! empty($updated_purchase_lines)) {
            $transaction->purchase_lines()->saveMany($updated_purchase_lines);
        }

        return $delete_purchase_lines;
    }

    /**
     * Whether Nutmeg product expenses should capitalize into stock cost.
     */
    public function nutmegProductExpensesActive(): bool
    {
        return function_exists('nutmeg_enabled') && nutmeg_enabled();
    }

    /**
     * Normalize product expense rows from request (name + amount).
     *
     * @param  mixed  $raw
     * @param  mixed  $currency_details
     * @return array<int, array{name: string, amount: float}>
     */
    public function normalizeProductExpenses($raw, $currency_details = null): array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $amount = $this->num_uf($row['amount'] ?? 0, $currency_details);
            if ($name === '' && $amount == 0) {
                continue;
            }
            if ($name === '') {
                $name = 'Expense';
            }
            if ($amount == 0) {
                continue;
            }
            $out[] = [
                'name' => mb_substr($name, 0, 120),
                'amount' => (float) $amount,
            ];
        }

        return $out;
    }

    /**
     * Sum of product expense amounts.
     */
    public function productExpenseTotal(array $expenses): float
    {
        $total = 0.0;
        foreach ($expenses as $row) {
            $total += (float) ($row['amount'] ?? 0);
        }

        return $total;
    }

    /**
     * Apply line product expenses onto unit buy prices (capitalized stock cost).
     * Form prices are buy (farmer) prices; expenses are totals for the line qty entered.
     *
     * @param  array  $data  purchase line input (mutated prices left as buy — returns landed)
     * @return array{pp_without_discount: float, purchase_price: float, purchase_price_inc_tax: float, product_expenses: array}
     */
    public function applyProductExpensesToPurchasePrices(array $data, $currency_details, $exchange_rate = 1, $multiplier = 1): array
    {
        $expenses = $this->normalizeProductExpenses($data['product_expenses'] ?? null, $currency_details);
        $expense_total = $this->productExpenseTotal($expenses);
        $qty = $this->num_uf($data['quantity'] ?? 0, $currency_details);
        $per_unit = ($qty > 0 && $expense_total > 0) ? ($expense_total / $qty) : 0.0;

        $pp_without = ($this->num_uf($data['pp_without_discount'] ?? 0, $currency_details) + $per_unit) * $exchange_rate / $multiplier;
        $purchase_price = ($this->num_uf($data['purchase_price'] ?? 0, $currency_details) + $per_unit) * $exchange_rate / $multiplier;
        $purchase_price_inc_tax = ($this->num_uf($data['purchase_price_inc_tax'] ?? 0, $currency_details) + $per_unit) * $exchange_rate / $multiplier;

        // Persist expense amounts in business currency (after exchange)
        $stored = [];
        foreach ($expenses as $row) {
            $stored[] = [
                'name' => $row['name'],
                'amount' => round((float) $row['amount'] * $exchange_rate, 6),
            ];
        }

        return [
            'pp_without_discount' => $pp_without,
            'purchase_price' => $purchase_price,
            'purchase_price_inc_tax' => $purchase_price_inc_tax,
            'product_expenses' => $stored ?: null,
        ];
    }

    /**
     * Buy unit cost before product expenses (for edit forms).
     */
    public function purchaseLineBuyUnitCost(PurchaseLine $line): array
    {
        $expenses = is_array($line->product_expenses) ? $line->product_expenses : [];
        $expense_total = $this->productExpenseTotal($expenses);
        $qty = (float) $line->quantity;
        $per_unit = ($qty > 0 && $expense_total > 0) ? ($expense_total / $qty) : 0.0;

        return [
            'purchase_price' => max(0, (float) $line->purchase_price - $per_unit),
            'purchase_price_inc_tax' => max(0, (float) $line->purchase_price_inc_tax - $per_unit),
            'pp_without_discount' => max(0, (float) $line->pp_without_discount - $per_unit),
            'expense_total' => $expense_total,
            'product_expenses' => $expenses,
        ];
    }

    /**
     * Replace product expenses on an existing purchase line and recalculate landed unit cost.
     * Buy price is derived by stripping any previously capitalized expenses.
     *
     * @param  array  $expenses  [['name'=>..., 'amount'=>...], ...]
     */
    public function setPurchaseLineProductExpenses(PurchaseLine $line, array $expenses, $currency_details = null): void
    {
        $buy = $this->purchaseLineBuyUnitCost($line);
        $normalized = $this->normalizeProductExpenses($expenses, $currency_details);
        $expense_total = $this->productExpenseTotal($normalized);
        $qty = (float) $line->quantity;
        $per_unit = ($qty > 0 && $expense_total > 0) ? ($expense_total / $qty) : 0.0;

        $line->pp_without_discount = $buy['pp_without_discount'] + $per_unit;
        $line->purchase_price = $buy['purchase_price'] + $per_unit;
        $line->purchase_price_inc_tax = $buy['purchase_price_inc_tax'] + $per_unit;
        $line->product_expenses = $normalized ?: null;
        $line->save();
    }

    /**
     * Product expenses still sitting in remaining purchase-lot stock for a variation/location.
     * Pro-rates each line's expense totals by quantity remaining.
     *
     * @return array{remaining_expense_total: float, remaining_expense_per_unit: float, breakdown: array<int, array{name: string, amount: float}>}
     */
    public function getRemainingProductExpensesForStock($business_id, $variation_id, $location_id): array
    {
        $empty = [
            'remaining_expense_total' => 0.0,
            'remaining_expense_per_unit' => 0.0,
            'breakdown' => [],
        ];

        if (empty($variation_id) || empty($location_id)) {
            return $empty;
        }

        if (! \Illuminate\Support\Facades\Schema::hasColumn('purchase_lines', 'product_expenses')) {
            return $empty;
        }

        $lines = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
            ->where('t.business_id', $business_id)
            ->where('t.location_id', $location_id)
            ->where('t.status', 'received')
            ->where('t.type', '!=', 'purchase_order')
            ->where('purchase_lines.variation_id', $variation_id)
            ->whereNotNull('purchase_lines.product_expenses')
            ->select(
                'purchase_lines.id',
                'purchase_lines.quantity',
                'purchase_lines.quantity_sold',
                'purchase_lines.quantity_adjusted',
                'purchase_lines.quantity_returned',
                'purchase_lines.mfg_quantity_used',
                'purchase_lines.product_expenses'
            )
            ->get();

        $breakdown = [];
        $remaining_expense_total = 0.0;
        $remaining_qty = 0.0;

        foreach ($lines as $line) {
            $expenses = is_array($line->product_expenses)
                ? $line->product_expenses
                : (json_decode($line->product_expenses ?? '[]', true) ?: []);
            $line_expense_total = $this->productExpenseTotal($expenses);
            if ($line_expense_total <= 0) {
                continue;
            }

            $qty = (float) $line->quantity;
            $used = (float) $line->quantity_sold
                + (float) $line->quantity_adjusted
                + (float) $line->quantity_returned
                + (float) ($line->mfg_quantity_used ?? 0);
            $qty_rem = max(0, $qty - $used);
            if ($qty_rem <= 0 || $qty <= 0) {
                continue;
            }

            $ratio = $qty_rem / $qty;
            $remaining_qty += $qty_rem;

            foreach ($expenses as $exp) {
                $name = trim((string) ($exp['name'] ?? 'Expense'));
                $amount = (float) ($exp['amount'] ?? 0) * $ratio;
                if ($amount == 0) {
                    continue;
                }
                if (! isset($breakdown[$name])) {
                    $breakdown[$name] = 0.0;
                }
                $breakdown[$name] += $amount;
                $remaining_expense_total += $amount;
            }
        }

        $list = [];
        foreach ($breakdown as $name => $amount) {
            $list[] = ['name' => $name, 'amount' => round($amount, 4)];
        }

        return [
            'remaining_expense_total' => round($remaining_expense_total, 4),
            'remaining_expense_per_unit' => $remaining_qty > 0
                ? round($remaining_expense_total / $remaining_qty, 4)
                : 0.0,
            'breakdown' => $list,
        ];
    }

    /**
     * Whether business uses AVCO (average cost) as primary stock accounting method.
     */
    public function usesAverageCost($business_id = null): bool
    {
        $method = session('business.accounting_method');
        if (! empty($business_id)) {
            $method = Business::where('id', $business_id)->value('accounting_method') ?: $method;
        }

        return $method === 'avco';
    }

    /**
     * After a received purchase: refresh variation default cost.
     * AVCO → weighted average cost; otherwise last buy price on the line.
     */
    public function updateVariationCostsFromPurchase($transaction, $business_id = null)
    {
        if (empty($transaction) || $transaction->status != 'received') {
            return;
        }

        $business_id = $business_id ?: $transaction->business_id;
        $transaction->loadMissing('purchase_lines');
        $use_avco = $this->usesAverageCost($business_id);
        $as_of = \Carbon\Carbon::parse($transaction->transaction_date)->format('Y-m-d');
        $location_id = $transaction->location_id;

        foreach ($transaction->purchase_lines as $line) {
            $variation = Variation::find($line->variation_id);
            if (empty($variation)) {
                continue;
            }

            $cost_inc = null;
            if ($use_avco) {
                $avg = PurchaseLine::join('transactions as t', 'purchase_lines.transaction_id', '=', 't.id')
                    ->where('t.business_id', $business_id)
                    ->where('t.type', '!=', 'purchase_order')
                    ->where('purchase_lines.variation_id', $line->variation_id)
                    ->whereDate('t.transaction_date', '<=', $as_of)
                    ->when($location_id, function ($q) use ($location_id) {
                        $q->where('t.location_id', $location_id);
                    })
                    ->selectRaw('SUM(purchase_lines.quantity * purchase_lines.purchase_price_inc_tax) / NULLIF(SUM(purchase_lines.quantity), 0) as avg_cost')
                    ->value('avg_cost');
                $cost_inc = $avg !== null ? (float) $avg : null;
            }

            if ($cost_inc === null || $cost_inc <= 0) {
                $cost_inc = (float) $line->purchase_price_inc_tax;
            }

            $cost_exc = (float) $line->purchase_price;
            if ($use_avco && $cost_inc > 0 && (float) $line->purchase_price_inc_tax > 0) {
                $ratio = (float) $line->purchase_price / (float) $line->purchase_price_inc_tax;
                $cost_exc = $cost_inc * $ratio;
            }

            $variation->default_purchase_price = $cost_exc;
            $variation->dpp_inc_tax = $cost_inc;
            if ((float) $variation->default_sell_price > 0 && $cost_exc > 0) {
                $variation->profit_percent = $this->get_percent($cost_exc, $variation->default_sell_price);
            }
            $variation->save();
        }
    }

    public function updatePurchaseOrderLine($purchase_order_line_id, $new_qty, $old_qty = 0)
    {
        $diff = $new_qty - $old_qty;
        if (! empty($purchase_order_line_id) && ! empty($diff)) {
            $purchase_order_line = PurchaseLine::find($purchase_order_line_id);
            $purchase_order_line->po_quantity_purchased += ($diff);
            $purchase_order_line->save();
        }
    }

    /**
     * Updates product stock after adding or updating purchase
     *
     * @param  string  $status_before
     * @param  obj  $transaction
     * @param  int  $product_id
     * @param  int  $variation_id
     * @param  decimal  $new_quantity in database format
     * @param  decimal  $old_quantity in database format
     * @param  array  $currency_details
     */
    public function updateProductStock($status_before, $transaction, $product_id, $variation_id, $new_quantity, $old_quantity, $currency_details)
    {
        $new_quantity_f = $this->num_f($new_quantity);
        $old_qty = $this->num_f($old_quantity);
        //Update quantity for existing products
        if ($status_before == 'received' && $transaction->status == 'received') {
            //if status received update existing quantity
            $this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, $old_qty, $currency_details);
        } elseif ($status_before == 'received' && $transaction->status != 'received') {
            //decrease quantity only if status changed from received to not received
            $this->decreaseProductQuantity(
                $product_id,
                $variation_id,
                $transaction->location_id,
                $old_quantity
            );
        } elseif ($status_before != 'received' && $transaction->status == 'received') {
            $this->updateProductQuantity($transaction->location_id, $product_id, $variation_id, $new_quantity_f, 0, $currency_details);
        }
    }

    /**
     * Recalculates purchase line data according to subunit data
     *
     * @param  int  $purchase_line
     * @param  int  $business_id
     * @return array
     */
    public function changePurchaseLineUnit($purchase_line, $business_id)
    {
        $base_unit = $purchase_line->product->unit;
        $sub_units = $base_unit->sub_units;

        $sub_unit_id = $purchase_line->sub_unit_id;

        $sub_unit = $sub_units->filter(function ($item) use ($sub_unit_id) {
            return $item->id == $sub_unit_id;
        })->first();

        if (! empty($sub_unit)) {
            $multiplier = $sub_unit->base_unit_multiplier;
            $purchase_line->quantity = $purchase_line->quantity / $multiplier;
            $purchase_line->pp_without_discount = $purchase_line->pp_without_discount * $multiplier;
            $purchase_line->purchase_price = $purchase_line->purchase_price * $multiplier;
            $purchase_line->purchase_price_inc_tax = $purchase_line->purchase_price_inc_tax * $multiplier;
            $purchase_line->item_tax = $purchase_line->item_tax * $multiplier;
            $purchase_line->quantity_returned = $purchase_line->quantity_returned / $multiplier;
            $purchase_line->quantity_sold = $purchase_line->quantity_sold / $multiplier;
            $purchase_line->quantity_adjusted = $purchase_line->quantity_adjusted / $multiplier;
        }

        //SubUnits
        $purchase_line->sub_units_options = $this->getSubUnits($business_id, $base_unit->id, false, $purchase_line->product_id);

        return $purchase_line;
    }

    /**
     * Recalculates sell line data according to subunit data
     *
     * @param  int  $unit_id
     * @return array
     */
    public function changeSellLineUnit($business_id, $sell_line)
    {
        $unit_details = $this->getSubUnits($business_id, $sell_line->unit_id, false, $sell_line->product_id);

        $sub_unit = null;
        $sub_unit_id = $sell_line->sub_unit_id;
        foreach ($unit_details as $key => $value) {
            if ($key == $sub_unit_id) {
                $sub_unit = $value;
            }
        }

        if (! empty($sub_unit)) {
            $multiplier = $sub_unit['multiplier'];
            $sell_line->quantity_ordered = $sell_line->quantity_ordered / $multiplier;
            $sell_line->item_tax = $sell_line->item_tax * $multiplier;
            $sell_line->default_sell_price = $sell_line->default_sell_price * $multiplier;
            $sell_line->unit_price_before_discount = $sell_line->unit_price_before_discount * $multiplier;
            $sell_line->sell_price_inc_tax = $sell_line->sell_price_inc_tax * $multiplier;
            $sell_line->sub_unit_multiplier = $multiplier;

            $sell_line->unit_details = $unit_details;
        }

        return $sell_line;
    }

    /**
     * Retrieves current stock of a variation for the given location
     *
     * @param  int  $variation_id, int location_id
     * @return float
     */
    public function getCurrentStock($variation_id, $location_id)
    {
        $current_stock = VariationLocationDetails::where('variation_id', $variation_id)
                                              ->where('location_id', $location_id)
                                              ->value('qty_available');

        if (null == $current_stock) {
            $current_stock = 0;
        }

        return $current_stock;
    }

    /**
     * Adjusts stock over selling with purchases, opening stocks andstock transfers
     * Also maps with respective sells
     *
     * @param  obj  $transaction
     * @return void
     */
    public function adjustStockOverSelling($transaction)
    {
        if ($transaction->status != 'received') {
            return false;
        }

        foreach ($transaction->purchase_lines as $purchase_line) {
            if ($purchase_line->product->enable_stock == 1) {

        //Available quantity in the purchase line
                $purchase_line_qty_avlbl = $purchase_line->quantity_remaining;

                if ($purchase_line_qty_avlbl <= 0) {
                    continue;
                }

                //update sell line purchase line mapping
                $sell_line_purchase_lines =
        TransactionSellLinesPurchaseLines::where('purchase_line_id', 0)
                ->join('transaction_sell_lines as tsl', 'tsl.id', '=', 'transaction_sell_lines_purchase_lines.sell_line_id')
                ->join('transactions as t', 'tsl.transaction_id', '=', 't.id')
                ->where('t.location_id', $transaction->location_id)
                ->where('tsl.variation_id', $purchase_line->variation_id)
                ->where('tsl.product_id', $purchase_line->product_id)

                ->select('transaction_sell_lines_purchase_lines.*')
                ->get();

                foreach ($sell_line_purchase_lines as $slpl) {
                    if ($purchase_line_qty_avlbl > 0) {
                        if ($slpl->quantity <= $purchase_line_qty_avlbl) {
                            $purchase_line_qty_avlbl -= $slpl->quantity;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->save();
                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();
                        } else {
                            $diff = $slpl->quantity - $purchase_line_qty_avlbl;
                            $slpl->purchase_line_id = $purchase_line->id;
                            $slpl->quantity = $purchase_line_qty_avlbl;
                            $slpl->save();

                            //update purchase line quantity sold
                            $purchase_line->quantity_sold += $slpl->quantity;
                            $purchase_line->save();

                            TransactionSellLinesPurchaseLines::create([
                                'sell_line_id' => $slpl->sell_line_id,
                                'purchase_line_id' => 0,
                                'quantity' => $diff,
                            ]);
                            break;
                        }
                    }
                }
            }
        }
    }

    /**
     * Finds out most relevant descount for the product
     *
     * @param  obj  $product, int $business_id, int $location_id, bool $is_cg,
     * bool $is_spg
     * @return obj discount
     */
    public function getProductDiscount($product, $business_id, $location_id, $is_cg = false, $price_group = null, $variation_id = null)
    {
        $now = \Carbon::now()->toDateTimeString();

        //Search if both category and brand matches
        $query = Discount::where('business_id', $business_id)
                    ->where('location_id', $location_id)
                    ->where('is_active', 1)
                    ->where('starts_at', '<=', $now)
                    ->where('ends_at', '>=', $now)
                    ->where(function ($q) use ($product, $variation_id) {
                        $q->where(function ($sub_q) use ($product) {
                            if (! empty($product->brand_id)) {
                                $sub_q->where('brand_id', $product->brand_id);
                            }
                            if (! empty($product->category_id)) {
                                $sub_q->where('category_id', $product->category_id);
                            }
                        })
                            ->orWhere(function ($sub_q) use ($product) {
                                $sub_q->whereRaw('(brand_id="'.$product->brand_id.'" AND category_id IS NULL)')
                                ->orWhereRaw('(category_id="'.$product->category_id.'" AND brand_id IS NULL)');
                            });

                        if (! empty($variation_id)) {
                            $q->orWhereHas('variations', function ($sub_q) use ($variation_id) {
                                $sub_q->where('variation_id', $variation_id);
                            });
                        }
                    })
                    ->orderBy('priority', 'desc')
                    ->latest();
        if ($is_cg) {
            $query->where('applicable_in_cg', 1);
        }
        if (! is_null($price_group)) {
            $query->where(function ($q) use ($price_group) {
                $q->whereNull('spg')
                    ->orWhere('spg', (string) $price_group);
            });
        } else {
            $query->whereNull('spg');
        }

        $discount = $query->first();

        if (! empty($discount)) {
            $discount->formated_starts_at = $this->format_date($discount->starts_at->toDateTimeString(), true);
            $discount->formated_ends_at = $this->format_date($discount->ends_at->toDateTimeString(), true);
        }

        return $discount;
    }

    /**
     * Filters product as per the given inputs and return the details.
     *
     * @param  string  $search_type (like or exact)
     * @return object
     */
    /**
     * POS / catalog text search for tyre naming + classic SKU/barcode.
     *
     * Supports product name, SKU, sub_sku, tyre_size, tyre_note, brand name.
     * Multi-word terms use AND across tokens so "215/55/17 Ceat" and "Ceat Jambo" match.
     *
     * @param  \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder  $query
     * @param  string  $search_term
     * @param  array  $opts
     * @return void
     */
    public function applyPosProductTextSearch($query, $search_term, array $opts = [])
    {
        $search_term = trim((string) $search_term);
        if ($search_term === '') {
            return;
        }

        $p = $opts['product_alias'] ?? 'products';
        $v = $opts['variation_alias'] ?? 'variations';
        $search_fields = $opts['search_fields'] ?? ['name', 'sku', 'sub_sku'];
        if (! is_array($search_fields) || empty($search_fields)) {
            $search_fields = ['name', 'sku', 'sub_sku'];
        }
        // Always allow sub_sku when sku is enabled (barcode scans)
        if (in_array('sku', $search_fields, true) && ! in_array('sub_sku', $search_fields, true)) {
            $search_fields[] = 'sub_sku';
        }

        $search_type = $opts['search_type'] ?? 'like';
        $has_tyre_size = array_key_exists('has_tyre_size', $opts)
            ? (bool) $opts['has_tyre_size']
            : \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size');
        $has_tyre_note = array_key_exists('has_tyre_note', $opts)
            ? (bool) $opts['has_tyre_note']
            : \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_note');
        $include_brand = array_key_exists('include_brand', $opts) ? (bool) $opts['include_brand'] : true;

        $industry_search = $opts['industry_search_fields'] ?? null;
        if ($industry_search === null) {
            try {
                $industry_search = \App\Utils\ShopModeUtil::posSearchFields();
            } catch (\Throwable $e) {
                $industry_search = [];
            }
        }
        if (! is_array($industry_search)) {
            $industry_search = [];
        }
        $tyre_search = function_exists('tyre_shop_enabled') ? tyre_shop_enabled() : false;

        $tokens = ($search_type === 'like')
            ? preg_split('/\s+/u', $search_term, -1, PREG_SPLIT_NO_EMPTY)
            : [$search_term];
        if (empty($tokens)) {
            $tokens = [$search_term];
        }

        $applyOne = function ($q, $term) use ($p, $v, $search_fields, $search_type, $has_tyre_size, $has_tyre_note, $include_brand, $industry_search, $tyre_search) {
            $first = true;
            $add = function ($column, $exact = false) use (&$first, $q, $term, $search_type) {
                if ($search_type === 'exact' || $exact) {
                    if ($first) {
                        $q->where($column, $term);
                        $first = false;
                    } else {
                        $q->orWhere($column, $term);
                    }
                } else {
                    $like = '%'.$term.'%';
                    if ($first) {
                        $q->where($column, 'like', $like);
                        $first = false;
                    } else {
                        $q->orWhere($column, 'like', $like);
                    }
                }
            };

            if (in_array('name', $search_fields, true)) {
                $add($p.'.name');
            }
            if (in_array('sku', $search_fields, true)) {
                $add($p.'.sku');
            }
            if (in_array('sub_sku', $search_fields, true)) {
                $add($v.'.sub_sku');
            }
            if (in_array('lot', $search_fields, true)) {
                $add('pl.lot_number');
            }
            foreach (['product_custom_field1', 'product_custom_field2', 'product_custom_field3', 'product_custom_field4'] as $cf) {
                if (in_array($cf, $search_fields, true)) {
                    $add($p.'.'.$cf);
                }
            }
            // Tyre fields searchable only in Tyre Shop mode
            if ($tyre_search && $has_tyre_size) {
                $add($p.'.tyre_size');
            }
            if ($tyre_search && $has_tyre_note) {
                $add($p.'.tyre_note');
            }
            foreach ($industry_search as $extraCol) {
                if ($extraCol === 'tyre_size' || $extraCol === 'tyre_note') {
                    continue;
                }
                $add($p.'.'.$extraCol);
            }
            if ($include_brand) {
                $add('brands.name');
            }
            // Variation name (variable products)
            $add($v.'.name');

            // Leading-zero SKU tolerance for pure numeric barcode/SKU terms
            if ($search_type === 'like' && ctype_digit($term)) {
                $stripped = ltrim($term, '0') ?: '0';
                $q->orWhereRaw('TRIM(LEADING \'0\' FROM '.$v.'.sub_sku) = ?', [$stripped])
                    ->orWhereRaw('TRIM(LEADING \'0\' FROM '.$p.'.sku) = ?', [$stripped]);
            }
        };

        // Each token must match at least one searchable field (AND across tokens)
        foreach ($tokens as $token) {
            $query->where(function ($q) use ($applyOne, $token) {
                $applyOne($q, $token);
            });
        }
    }

    public function filterProduct($business_id, $search_term, $location_id = null, $not_for_selling = null, $price_group_id = null, $product_types = [], $search_fields = [], $check_qty = false, $search_type = 'like')
    {
        $query = Product::join('variations', 'products.id', '=', 'variations.product_id')
                ->active()
                ->whereNull('variations.deleted_at')
                ->leftjoin('units as U', 'products.unit_id', '=', 'U.id')
                ->leftjoin(
                    'variation_location_details AS VLD',
                    function ($join) use ($location_id) {
                        $join->on('variations.id', '=', 'VLD.variation_id');

                        //Include Location
                        if (! empty($location_id)) {
                            $join->where(function ($query) use ($location_id) {
                                $query->where('VLD.location_id', '=', $location_id);
                                //Check null to show products even if no quantity is available in a location.
                                //TODO: Maybe add a settings to show product not available at a location or not.
                                $query->orWhereNull('VLD.location_id');
                            });
                        }
                    }
                );

        if (! is_null($not_for_selling)) {
            $query->where('products.not_for_selling', $not_for_selling);
        }

        if (! empty($price_group_id)) {
            $query->leftjoin(
                'variation_group_prices AS VGP',
                function ($join) use ($price_group_id) {
                    $join->on('variations.id', '=', 'VGP.variation_id')
                        ->where('VGP.price_group_id', '=', $price_group_id);
                }
            );
        }

        $query->where('products.business_id', $business_id)
                ->where('products.type', '!=', 'modifier');

        if (! empty($product_types)) {
            $query->whereIn('products.type', $product_types);
        }

        if (in_array('lot', $search_fields)) {
            $query->leftjoin('purchase_lines as pl', 'variations.id', '=', 'pl.variation_id');
        }

        $has_tyre_size = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size');
        $has_tyre_note = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_note');

        // Brand name search (e.g. "Ceat") — soft join so products without brand still appear
        $query->leftJoin('brands', function ($join) {
            $join->on('products.brand_id', '=', 'brands.id')
                ->whereNull('brands.deleted_at');
        });

        //Include search
        if (! empty($search_term)) {
            $this->applyPosProductTextSearch($query, $search_term, [
                'product_alias' => 'products',
                'variation_alias' => 'variations',
                'search_fields' => $search_fields,
                'search_type' => $search_type,
                'has_tyre_size' => $has_tyre_size,
                'has_tyre_note' => $has_tyre_note,
                'include_brand' => true,
            ]);
        }

        //Include check for quantity
        if ($check_qty) {
            $query->where('VLD.qty_available', '>', 0);
        }

        if (! empty($location_id)) {
            $query->ForLocation($location_id);
        }

        $query->select(
                'products.id as product_id',
                'products.name',
                $has_tyre_size ? 'products.tyre_size' : \DB::raw('NULL as tyre_size'),
                $has_tyre_note ? 'products.tyre_note' : \DB::raw('NULL as tyre_note'),
                'products.type',
                'products.enable_stock',
                'variations.id as variation_id',
                'variations.name as variation',
                'VLD.qty_available',
                'variations.sell_price_inc_tax as selling_price',
                'variations.sub_sku',
                'U.short_name as unit'
            );

        if (! empty($price_group_id)) {
            $query->addSelect(DB::raw('IF (VGP.price_type = "fixed", VGP.price_inc_tax, VGP.price_inc_tax * variations.sell_price_inc_tax / 100) as variation_group_price'));
        }

        if (in_array('lot', $search_fields)) {
            $query->addSelect('pl.id as purchase_line_id', 'pl.lot_number');
        }

        $data = $query->groupBy('variations.id')
             ->orderBy('VLD.qty_available', 'desc')
             ->get();

        // 🔐 Escape `name`, `variation`, `sub_sku`
        $data->transform(function ($item) {
            $item->tyre_size = e($item->tyre_size ?? '');
            $item->name = e(\App\Product::formatNameWithTyreSize($item->name, $item->tyre_size));
            $item->variation = e($item->variation);
            $item->sub_sku = e($item->sub_sku);
            return $item;
        });

        return $data;

    }

    public function getProductStockDetails($business_id, $filters, $for)
    {
        $has_tyre_size = \Illuminate\Support\Facades\Schema::hasColumn('products', 'tyre_size');
        $query = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                  ->join('units', 'p.unit_id', '=', 'units.id')
                  ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                  ->leftjoin('business_locations as l', 'vld.location_id', '=', 'l.id')
                  ->leftjoin('categories as c', 'p.category_id', '=', 'c.id')
                  ->join('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                  ->where('p.business_id', $business_id)
                  ->whereIn('p.type', ['single', 'variable']);

        $permitted_locations = auth()->user()->permitted_locations();
        $location_filter = '';

        if ($permitted_locations != 'all') {
            $query->whereIn('vld.location_id', $permitted_locations);

            $locations_imploded = implode(', ', $permitted_locations);
            $location_filter .= "AND transactions.location_id IN ($locations_imploded) ";
        }

        if (! empty($filters['location_id'])) {
            $location_id = $filters['location_id'];

            $query->where('vld.location_id', $location_id);

            $location_filter .= "AND transactions.location_id=$location_id";

            //If filter by location then hide products not available in that location
            $query->join('product_locations as pl', 'pl.product_id', '=', 'p.id')
                  ->where(function ($q) use ($location_id) {
                      $q->where('pl.location_id', $location_id);
                  });
        }

        if (! empty($filters['category_id'])) {
            $query->where('p.category_id', $filters['category_id']);
        }
        if (! empty($filters['sub_category_id'])) {
            $query->where('p.sub_category_id', $filters['sub_category_id']);
        }
        if (! empty($filters['brand_id'])) {
            $query->where('p.brand_id', $filters['brand_id']);
        }
        if (! empty($filters['tyre_size']) && $has_tyre_size) {
            $query->where('p.tyre_size', 'like', '%'.$filters['tyre_size'].'%');
        }
        if (! empty($filters['unit_id'])) {
            $query->where('p.unit_id', $filters['unit_id']);
        }

        if (! empty($filters['tax_id'])) {
            $query->where('p.tax', $filters['tax_id']);
        }

        if (! empty($filters['type'])) {
            $query->where('p.type', $filters['type']);
        }

        if (isset($filters['only_mfg_products']) && $filters['only_mfg_products'] == 1) {
            $query->join('mfg_recipes as mr', 'mr.variation_id', '=', 'variations.id');
        }

        if (isset($filters['active_state']) && $filters['active_state'] == 'active') {
            $query->where('p.is_inactive', 0);
        }
        if (isset($filters['active_state']) && $filters['active_state'] == 'inactive') {
            $query->where('p.is_inactive', 1);
        }
        if (isset($filters['not_for_selling']) && $filters['not_for_selling'] == 1) {
            $query->where('p.not_for_selling', 1);
        }

        if (! empty($filters['repair_model_id'])) {
            $query->where('p.repair_model_id', request()->get('repair_model_id'));
        }

        //TODO::Check if result is correct after changing LEFT JOIN to INNER JOIN
        $pl_query_string = $this->get_pl_quantity_sum_string('pl');

        if ($for == 'view_product' && ! empty(request()->input('product_id'))) {
            $location_filter = 'AND transactions.location_id=l.id';
        }

        $products = $query->select(
            // DB::raw("(SELECT SUM(quantity) FROM transaction_sell_lines LEFT JOIN transactions ON transaction_sell_lines.transaction_id=transactions.id WHERE transactions.status='final' $location_filter AND
            //     transaction_sell_lines.product_id=products.id) as total_sold"),

            DB::raw("(SELECT SUM(TSL.quantity - TSL.quantity_returned) FROM transactions 
                  JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                  WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=vld.location_id
                  AND TSL.variation_id=variations.id) as total_sold"),
            DB::raw("(SELECT SUM(IF(transactions.type='sell_transfer', TSL.quantity, 0) ) FROM transactions 
                  JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                  WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=vld.location_id AND (TSL.variation_id=variations.id)) as total_transfered"),
            DB::raw("(SELECT SUM(IF(transactions.type='stock_adjustment', SAL.quantity, 0) ) FROM transactions 
                  JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                  WHERE transactions.type='stock_adjustment' AND transactions.location_id=vld.location_id 
                    AND (SAL.variation_id=variations.id)) as total_adjusted"),
            DB::raw("(SELECT SUM( COALESCE(pl.quantity - ($pl_query_string), 0) * purchase_price_inc_tax) FROM transactions 
                  JOIN purchase_lines AS pl ON transactions.id=pl.transaction_id
                  WHERE (transactions.status='received' OR transactions.type='purchase_return')  AND transactions.location_id=vld.location_id 
                  AND (pl.variation_id=variations.id)) as stock_price"),
            DB::raw('SUM(vld.qty_available) as stock'),
            'variations.sub_sku as sku',
            'p.name as product',
            $has_tyre_size ? 'p.tyre_size' : DB::raw('NULL as tyre_size'),
            'p.type',
            'p.alert_quantity',
            'p.id as product_id',
            'units.short_name as unit',
            'p.enable_stock as enable_stock',
            'variations.sell_price_inc_tax as unit_price',
            'variations.dpp_inc_tax as dpp_inc_tax',
            'variations.default_purchase_price as default_purchase_price',
            'pv.name as product_variation',
            'variations.name as variation_name',
            'l.name as location_name',
            'l.id as location_id',
            'variations.id as variation_id',
            'c.name as category_name',
            'p.product_custom_field1',
            'p.product_custom_field2',
            'p.product_custom_field3',
            'p.product_custom_field4'
        )->groupBy('variations.id', 'vld.location_id');

        if (isset($filters['show_manufacturing_data']) && $filters['show_manufacturing_data']) {
            $pl_query_string = $this->get_pl_quantity_sum_string('PL');
            $products->addSelect(
                DB::raw("(SELECT COALESCE(SUM(PL.quantity - ($pl_query_string)), 0) FROM transactions 
                    JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=vld.location_id  
                    AND (PL.variation_id=variations.id)) as total_mfg_stock")
            );
        }

        if (! empty($filters['product_id'])) {
            $products->where('p.id', $filters['product_id'])
                    ->groupBy('l.id');
        }

        if ($for == 'view_product') {
            return $products->get();
        } elseif ($for == 'api') {
            return $products->paginate();
        } else {
            return $products;
        }
    }

    /**
     * Gives the details of combo product
     *
     * @param  array  $combo_variations
     * @param  int  $business_id
     * @return array
     */
    public function __getComboProductDetails($combo_variations, $business_id)
    {
        foreach ($combo_variations as $key => $value) {
            $combo_variations[$key]['variation'] =
                Variation::with(['product'])
                    ->find($value['variation_id']);

            $combo_variations[$key]['sub_units'] = $this->getSubUnits($business_id, $combo_variations[$key]['variation']['product']->unit_id, true);

            $combo_variations[$key]['multiplier'] = 1;

            if (! empty($combo_variations[$key]['sub_units'])) {
                if (isset($combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']])) {
                    $combo_variations[$key]['multiplier'] = $combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']]['multiplier'];
                    $combo_variations[$key]['unit_name'] = $combo_variations[$key]['sub_units'][$combo_variations[$key]['unit_id']]['name'];
                }
            }
        }

        return $combo_variations;
    }

    public function getVariationStockDetails($business_id, $variation_id, $location_id)
    {
        $purchase_details = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                    ->join('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('units as u', 'p.secondary_unit_id', '=', 'u.id')
                    ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->leftjoin('purchase_lines as pl', 'pl.variation_id', '=', 'variations.id')
                    ->leftjoin('transactions as t', 'pl.transaction_id', '=', 't.id')
                    ->where('t.location_id', $location_id)
                    //->where('t.status', 'received')
                    ->where('p.business_id', $business_id)
                    ->where('variations.id', $variation_id)
                    ->select(
                        DB::raw("SUM(IF(t.type='purchase' AND t.status='received', pl.quantity, 0)) as total_purchase"),
                        DB::raw("SUM(IF(t.type='purchase' OR t.type='purchase_return', pl.quantity_returned, 0)) as total_purchase_return"),
                        DB::raw('SUM(pl.quantity_adjusted) as total_adjusted'),
                        DB::raw("SUM(IF(t.type='opening_stock', pl.quantity, 0)) as total_opening_stock"),
                        DB::raw("SUM(IF(t.type='purchase_transfer', pl.quantity, 0)) as total_purchase_transfer"),
                        'variations.sub_sku as sub_sku',
                        'p.name as product',
                        'p.type',
                        'p.sku',
                        'p.id as product_id',
                        'units.short_name as unit',
                        'u.short_name as second_unit',
                        'pv.name as product_variation',
                        'variations.name as variation_name',
                        'variations.id as variation_id'
                    )
                  ->get()->first();

        $sell_details = Variation::join('products as p', 'p.id', '=', 'variations.product_id')
                    ->leftjoin('transaction_sell_lines as sl', 'sl.variation_id', '=', 'variations.id')
                    ->join('transactions as t', 'sl.transaction_id', '=', 't.id')
                    ->where('t.location_id', $location_id)
                    ->where('t.status', 'final')
                    ->where('p.business_id', $business_id)
                    ->where('variations.id', $variation_id)
                    ->select(
                        DB::raw("SUM(IF(t.type='sell', sl.quantity, 0)) as total_sold"),
                        DB::raw("SUM(IF(t.type='sell', sl.quantity_returned, 0)) as total_sell_return"),
                        DB::raw("SUM(IF(t.type='sell_transfer', sl.quantity, 0)) as total_sell_transfer")
                    )
                  ->get()->first();

        $current_stock = VariationLocationDetails::where('variation_id',
                                            $variation_id)
                                        ->where('location_id', $location_id)
                                        ->first();

        if ($purchase_details->type == 'variable') {
            $product_name = $purchase_details->product.' - '.$purchase_details->product_variation.' - '.$purchase_details->variation_name.' ('.$purchase_details->sub_sku.')';
        } else {
            $product_name = $purchase_details->product.' ('.$purchase_details->sku.')';
        }

        $output = [
            'variation' => $product_name,
            'unit' => $purchase_details->unit,
            'second_unit' => $purchase_details->second_unit,
            'total_purchase' => $purchase_details->total_purchase,
            'total_purchase_return' => $purchase_details->total_purchase_return,
            'total_adjusted' => $purchase_details->total_adjusted,
            'total_opening_stock' => $purchase_details->total_opening_stock,
            'total_purchase_transfer' => $purchase_details->total_purchase_transfer,
            'total_sold' => $sell_details->total_sold,
            'total_sell_return' => $sell_details->total_sell_return,
            'total_sell_transfer' => $sell_details->total_sell_transfer,
            'current_stock' => $current_stock->qty_available ?? 0,
        ];

        return $output;
    }

    public function getVariationStockHistory($business_id, $variation_id, $location_id)
    {
        $stock_history = Transaction::leftjoin('transaction_sell_lines as sl',
            'sl.transaction_id', '=', 'transactions.id')
                                ->leftjoin('purchase_lines as pl',
                                    'pl.transaction_id', '=', 'transactions.id')
                                ->leftjoin('stock_adjustment_lines as al',
                                    'al.transaction_id', '=', 'transactions.id')
                                ->leftjoin('transactions as return', 'transactions.return_parent_id', '=', 'return.id')
                                ->leftjoin('purchase_lines as rpl',
                                    'rpl.transaction_id', '=', 'return.id')
                                ->leftjoin('transaction_sell_lines as rsl',
                                        'rsl.transaction_id', '=', 'return.id')
                                ->leftjoin('contacts as c', 'transactions.contact_id', '=', 'c.id')
                                ->where('transactions.location_id', $location_id)
                                ->where(function ($q) use ($variation_id) {
                                    $q->where('sl.variation_id', $variation_id)
                                        ->orWhere('pl.variation_id', $variation_id)
                                        ->orWhere('al.variation_id', $variation_id)
                                        ->orWhere('rpl.variation_id', $variation_id)
                                        ->orWhere('rsl.variation_id', $variation_id);
                                })
                                ->whereIn('transactions.type', ['sell', 'purchase', 'stock_adjustment', 'opening_stock', 'sell_transfer', 'purchase_transfer', 'production_purchase', 'purchase_return', 'sell_return', 'production_sell'])
                                ->select(
                                    'transactions.id as transaction_id',
                                    'transactions.type as transaction_type',
                                    'sl.quantity as sell_line_quantity',
                                    'pl.quantity as purchase_line_quantity',
                                    'rsl.quantity_returned as sell_return',
                                    'rpl.quantity_returned as purchase_return',
                                    'al.quantity as stock_adjusted',
                                    'pl.quantity_returned as combined_purchase_return',
                                    'transactions.return_parent_id',
                                    'transactions.transaction_date',
                                    'transactions.status',
                                    'transactions.invoice_no',
                                    'transactions.ref_no',
                                    'transactions.additional_notes',
                                    'c.name as contact_name',
                                    'c.supplier_business_name',
                                    'pl.secondary_unit_quantity as purchase_secondary_unit_quantity',
                                    'sl.secondary_unit_quantity as sell_secondary_unit_quantity'
                                )
                                ->orderBy('transactions.transaction_date', 'asc')
                                ->get();

        $stock_history_array = [];
        $stock = 0;
        $stock_in_second_unit = 0;
        foreach ($stock_history as $stock_line) {
            $temp_array = [
                'date' => $stock_line->transaction_date,
                'transaction_id' => $stock_line->transaction_id,
                'contact_name' => $stock_line->contact_name,
                'supplier_business_name' => $stock_line->supplier_business_name,
            ];
            if ($stock_line->transaction_type == 'sell') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;

                $stock_in_second_unit -= $stock_line->sell_secondary_unit_quantity;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('sale.sale'),
                    'ref_no' => $stock_line->invoice_no,
                    'sell_secondary_unit_quantity' => ! empty($stock_line->sell_secondary_unit_quantity) ? $this->roundQuantity($stock_line->sell_secondary_unit_quantity) : 0,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'purchase') {
                if ($stock_line->status != 'received') {
                    continue;
                }
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_in_second_unit += $stock_line->purchase_secondary_unit_quantity;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase',
                    'type_label' => __('lang_v1.purchase'),
                    'ref_no' => $stock_line->ref_no,
                    'purchase_secondary_unit_quantity' => ! empty($stock_line->purchase_secondary_unit_quantity) ? $this->roundQuantity($stock_line->purchase_secondary_unit_quantity) : 0,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'stock_adjustment') {
                $quantity_change = -1 * $stock_line->stock_adjusted;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'stock_adjustment',
                    'type_label' => __('stock_adjustment.stock_adjustment'),
                    'ref_no' => $stock_line->ref_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'opening_stock') {
                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_in_second_unit += $stock_line->purchase_secondary_unit_quantity;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'opening_stock',
                    'type_label' => __('report.opening_stock'),
                    'ref_no' => $stock_line->ref_no ?? '',
                    'additional_notes' => $stock_line->additional_notes,
                    'purchase_secondary_unit_quantity' => ! empty($stock_line->purchase_secondary_unit_quantity) ? $this->roundQuantity($stock_line->purchase_secondary_unit_quantity) : 0,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'sell_transfer') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell_transfer',
                    'type_label' => __('lang_v1.stock_transfers').' ('.__('lang_v1.out').')',
                    'ref_no' => $stock_line->ref_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'purchase_transfer') {
                if ($stock_line->status != 'received') {
                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.stock_transfers').' ('.__('lang_v1.in').')',
                    'ref_no' => $stock_line->ref_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'production_sell') {
                if ($stock_line->status != 'final') {
                    continue;
                }
                $quantity_change = -1 * $stock_line->sell_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'sell',
                    'type_label' => __('manufacturing::lang.ingredient'),
                    'ref_no' => '',
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'production_purchase') {

                // ISSUE: There is a data mismatch between the stock calculation in product history and the fix mismatch functionality because in getVariationStockMisMatch only received transactions are considered 
                if ($stock_line->status != 'received') {
                    continue;
                }

                $quantity_change = $stock_line->purchase_line_quantity;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'production_purchase',
                    'type_label' => __('manufacturing::lang.manufactured'),
                    'ref_no' => $stock_line->ref_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'purchase_return') {
                $quantity_change = -1 * ($stock_line->combined_purchase_return + $stock_line->purchase_return);
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_return',
                    'type_label' => __('lang_v1.purchase_return'),
                    'ref_no' => $stock_line->ref_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            } elseif ($stock_line->transaction_type == 'sell_return') {
                $quantity_change = $stock_line->sell_return;
                $stock += $quantity_change;
                $stock_history_array[] = array_merge($temp_array, [
                    'quantity_change' => $quantity_change,
                    'stock' => $this->roundQuantity($stock),
                    'type' => 'purchase_transfer',
                    'type_label' => __('lang_v1.sell_return'),
                    'ref_no' => $stock_line->invoice_no,
                    'stock_in_second_unit' => $this->roundQuantity($stock_in_second_unit),
                ]);
            }
        }

        return array_reverse($stock_history_array);
    }

    /**
     * Function to calculate stock mismatches for all the variations or
     * the given variation in a location
     */
    public function getVariationStockMisMatch($business_id, $variation_id, $location_id)
    {
        $query = Variation::leftjoin('products as p', 'p.id', '=', 'variations.product_id')
                    ->leftjoin('units', 'p.unit_id', '=', 'units.id')
                    ->leftjoin('variation_location_details as vld', 'variations.id', '=', 'vld.variation_id')
                    ->leftjoin('product_variations as pv', 'variations.product_variation_id', '=', 'pv.id')
                    ->where('p.business_id', $business_id)
                    ->where('vld.location_id', $location_id);

        if (! is_null($variation_id)) {
            $query->where('variations.id', $variation_id);
        }

        $stock_details = $query->select(
            DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                    LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                    AND TSL.variation_id=variations.id) as total_sold"),
            DB::raw("(SELECT SUM(COALESCE(TSL.quantity_returned, 0)) FROM transactions 
                    LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell' AND transactions.location_id=$location_id 
                    AND TSL.variation_id=variations.id) as total_sell_return"),
            DB::raw("(SELECT SUM(COALESCE(TSL.quantity,0)) FROM transactions 
                    LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='sell_transfer' AND transactions.location_id=$location_id 
                    AND TSL.variation_id=variations.id) as total_sell_transfered"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity,0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase_transfer' AND transactions.location_id=$location_id 
                    AND PL.variation_id=variations.id) as total_purchase_transfered"),
            DB::raw("(SELECT SUM(COALESCE(SAL.quantity, 0)) FROM transactions 
                    LEFT JOIN stock_adjustment_lines AS SAL ON transactions.id=SAL.transaction_id
                    WHERE transactions.type='stock_adjustment' AND transactions.location_id=$location_id 
                    AND SAL.variation_id=variations.id) as total_adjusted"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                    AND PL.variation_id=variations.id) as total_purchased"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.status='received' AND transactions.type='purchase' AND transactions.location_id=$location_id
                    AND PL.variation_id=variations.id) as total_purchase_return"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity_returned, 0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.type='purchase_return' AND transactions.location_id=$location_id
                    AND PL.variation_id=variations.id) as total_combined_purchase_return"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.type='opening_stock' AND transactions.status='received' AND transactions.location_id=$location_id
                    AND PL.variation_id=variations.id) as total_opening_stock"),
            DB::raw("(SELECT SUM(COALESCE(PL.quantity, 0)) FROM transactions 
                    LEFT JOIN purchase_lines AS PL ON transactions.id=PL.transaction_id
                    WHERE transactions.status='received' AND transactions.type='production_purchase' AND transactions.location_id=$location_id
                    AND PL.variation_id=variations.id) as total_manufactured"),
            DB::raw("(SELECT SUM(COALESCE(TSL.quantity, 0)) FROM transactions 
                    LEFT JOIN transaction_sell_lines AS TSL ON transactions.id=TSL.transaction_id
                    WHERE transactions.status='final' AND transactions.type='production_sell' AND transactions.location_id=$location_id 
                    AND TSL.variation_id=variations.id) as total_ingredients_used"),
            DB::raw('SUM(vld.qty_available) as stock'),
            'variations.sub_sku as sub_sku',
            'p.name as product',
            'p.id as product_id',
            'p.type',
            'p.sku as sku',
            'units.short_name as unit',
            'p.enable_stock as enable_stock',
            'variations.sell_price_inc_tax as unit_price',
            'pv.name as product_variation',
            'variations.name as variation_name',
            'variations.id as variation_id'
        )
        ->groupBy('variations.id')
        ->get();

        foreach ($stock_details as $index => $row) {
            $total_sold = $row->total_sold ?: 0;
            $total_sell_return = $row->total_sell_return ?: 0;
            $total_sell_transfered = $row->total_sell_transfered ?: 0;

            $total_purchase_transfered = $row->total_purchase_transfered ?: 0;
            $total_adjusted = $row->total_adjusted ?: 0;
            $total_purchased = $row->total_purchased ?: 0;
            $total_purchase_return = $row->total_purchase_return ?: 0;
            $total_combined_purchase_return = $row->total_combined_purchase_return ?: 0;
            $total_opening_stock = $row->total_opening_stock ?: 0;
            $total_manufactured = $row->total_manufactured ?: 0;
            $total_ingredients_used = $row->total_ingredients_used ?: 0;

            $total_stock_calculated = $total_opening_stock + $total_purchased + $total_purchase_transfered + $total_sell_return + $total_manufactured
            - ($total_sold + $total_sell_transfered + $total_adjusted + $total_purchase_return + $total_combined_purchase_return + $total_ingredients_used);

            $stock_details[$index]->total_stock_calculated = $total_stock_calculated;
        }

        return $stock_details;
    }

    public function fixVariationStockMisMatch($business_id, $variation_id, $location_id, $stock)
    {
        $vld = VariationLocationDetails::join(
            'business_locations as bl',
            'bl.id',
            '=',
            'variation_location_details.location_id'
        )
                ->where('variation_location_details.location_id', $location_id)
                    ->where('variation_id', $variation_id)
                    ->where('bl.business_id', $business_id)
                    ->select('variation_location_details.*')
                    ->first();

        if (! empty($vld)) {
            $vld->qty_available = $stock;
            $vld->save();
        }

        //check if there are more such rows then delete it because one location can have only one variation_location_details
        $vld_duplicate = VariationLocationDetails::join(
            'business_locations as bl',
            'bl.id',
            '=',
            'variation_location_details.location_id'
        )
                ->where('variation_location_details.location_id', $location_id)
                ->where('variation_id', $variation_id)
                ->where('bl.business_id', $business_id)
                ->where('variation_location_details.id', '!=', $vld->id)
                ->delete();
    }

    /**
     * Return the products less than alert quntity.
     *
     * @return array
     */
    public function getProductAlert($business_id, $permitted_locations = null)
    {
        $query = VariationLocationDetails::join(
            'product_variations as pv',
            'variation_location_details.product_variation_id',
            '=',
            'pv.id'
        )
                ->join(
                    'variations as v',
                    'variation_location_details.variation_id',
                    '=',
                    'v.id'
                )
                ->join(
                    'products as p',
                    'variation_location_details.product_id',
                    '=',
                    'p.id'
                )
                ->leftjoin(
                    'business_locations as l',
                    'variation_location_details.location_id',
                    '=',
                    'l.id'
                )
                ->leftjoin('units as u', 'p.unit_id', '=', 'u.id')
                ->where('p.business_id', $business_id)
                ->where('p.enable_stock', 1)
                ->where('p.is_inactive', 0)
                ->whereNull('v.deleted_at')
                ->whereNotNull('p.alert_quantity')
                ->whereRaw('variation_location_details.qty_available <= p.alert_quantity');

             //Check for permitted locations of a user
             if(!empty($permitted_locations)){
                if ($permitted_locations != 'all') {
                    $query->whereIn('variation_location_details.location_id', $permitted_locations);
                }
            }
        

        if (! empty(request()->input('location_id'))) {
            $query->where('variation_location_details.location_id', request()->input('location_id'));
        }

        $products = $query->select(
            'p.name as product',
            'p.type',
            'p.sku',
            'pv.name as product_variation',
            'v.name as variation',
            'v.sub_sku',
            'l.name as location',
            'variation_location_details.qty_available as stock',
            'u.short_name as unit'
        )
                ->groupBy('variation_location_details.id')
                ->orderBy('stock', 'asc');

        return $products;
    }

    /**
     * Parse weighing scale barcode
     * Supports multiple formats:
     * 1. Legacy format: prefix + SKU + qty_int + qty_decimal
     * 2. EAN-13 format (0000 + PLU + Weight in grams): 0000 + 5-digit PLU + 4-digit weight
     * 3. EAN-13 format (02 prefix): 02 + 5-digit PLU + 5-digit price/weight
     * 4. 6-digit SKU + weight format: First 6 digits = SKU, remaining = weight in grams
     *
     * @param string $scale_barcode
     * @return array
     */
    public function parseWeighingBarcode($scale_barcode)
    {
        $business_id = session()->get('user.business_id');
        $scale_setting = session()->get('business.weighing_scale_setting');
        
        // Handle common_settings as either array or JSON string
        $common_settings = session()->get('business.common_settings', []);
        if (is_string($common_settings)) {
            $common_settings = json_decode($common_settings, true) ?? [];
        }
        if (!is_array($common_settings)) {
            $common_settings = [];
        }
        
        $error_msg = trans('messages.something_went_wrong');

        // Try to detect barcode format automatically
        $barcode_length = strlen($scale_barcode);
        
        // Check if 6-digit SKU + weight format is enabled
        if (!empty($common_settings['enable_weighing_scale_scan']) && 
            $barcode_length >= 7 && 
            !empty($common_settings['weighing_scale_sku_length'])) {
            $sku_length = (int) $common_settings['weighing_scale_sku_length'];
            $weight_digits = $common_settings['weighing_scale_weight_digits'] ?? 4;
            
            // Accept barcodes with at least SKU length + 1 digit for weight
            if ($barcode_length >= $sku_length + 1) {
                // Calculate actual weight digits from barcode length
                $actual_weight_digits = $barcode_length - $sku_length;
                return $this->parseFixedLengthSkuWeightBarcode($scale_barcode, $business_id, $sku_length, $actual_weight_digits);
            }
        }
        
        // EAN-13 format with 0000 prefix (Multy Fresh Mart style): 0000XXXXXYYYYC
        // Where XXXXX = 5-digit PLU, YYYY = weight in grams, C = check digit
        if ($barcode_length == 13 && substr($scale_barcode, 0, 4) === '0000') {
            return $this->parseEan13WeightBarcode($scale_barcode, $business_id);
        }
        
        // Standard EAN-13 with 02 prefix (common weighing scale format): 02XXXXXYYYYYC
        // Where XXXXX = 5-digit PLU, YYYYY = price in cents/weight
        if ($barcode_length == 13 && substr($scale_barcode, 0, 2) === '02') {
            return $this->parseEan13WeightBarcode($scale_barcode, $business_id, '02');
        }
        
        // Legacy custom format
        if (!empty($scale_setting) && is_array($scale_setting)) {
            return $this->parseLegacyWeighingBarcode($scale_barcode, $scale_setting, $business_id);
        }

        return [
            'success' => false,
            'msg' => $error_msg,
        ];
    }
    
    /**
     * Parse fixed-length SKU + weight barcode format
     * Format: [SKU of fixed length] + [weight in grams of fixed digits]
     * Example: 1234560115 = SKU 123456 + 115g (0.115kg)
     * 
     * @param string $barcode
     * @param int $business_id
     * @param int $sku_length Length of SKU portion (default 6)
     * @param int $weight_digits Number of digits for weight (default 4 for grams)
     * @return array
     */
    private function parseFixedLengthSkuWeightBarcode($barcode, $business_id, $sku_length = 6, $weight_digits = 4)
    {
        // Extract raw SKU (first N digits with leading zeros)
        $sku_raw = substr($barcode, 0, $sku_length);
        
        // Extract trimmed SKU (without leading zeros)
        $sku_trimmed = ltrim($sku_raw, '0');
        if (empty($sku_trimmed)) {
            $sku_trimmed = '0';
        }
        
        // Extract weight (next M digits)
        $weight_part = substr($barcode, $sku_length, $weight_digits);
        $weight_grams = (int) $weight_part;
        
        // Convert grams to kg
        $weight_kg = $weight_grams / 1000;
        
        // Try to find product - first with raw SKU (with leading zeros)
        $result = $this->filterProduct($business_id, $sku_raw, null, false, null, [], ['sub_sku', 'sku'], false, 'exact')->first();
        
        // If not found, try with trimmed SKU (without leading zeros)
        if (empty($result) && $sku_raw !== $sku_trimmed) {
            $result = $this->filterProduct($business_id, $sku_trimmed, null, false, null, [], ['sub_sku', 'sku'], false, 'exact')->first();
        }
        
        // If still not found, try partial match with raw SKU
        if (empty($result)) {
            $result = $this->filterProduct($business_id, $sku_raw, null, false, null, [], ['sub_sku', 'sku'], false, 'like')->first();
        }
        
        // If still not found, try partial match with trimmed SKU
        if (empty($result) && $sku_raw !== $sku_trimmed) {
            $result = $this->filterProduct($business_id, $sku_trimmed, null, false, null, [], ['sub_sku', 'sku'], false, 'like')->first();
        }

        if (!empty($result)) {
            return [
                'variation_id' => $result->variation_id,
                'qty' => $weight_kg,
                'success' => true,
                'sku' => $result->sub_sku ?? $result->sku ?? $sku_trimmed,
                'barcode_type' => 'fixed_sku_weight',
                'weight_kg' => $weight_kg,
            ];
        }
        
        return [
            'success' => false,
            'msg' => trans('lang_v1.sku_not_match', ['sku' => $sku_trimmed]),
        ];
    }
    
    /**
     * Parse EAN-13 weighing scale barcode with embedded weight
     * 
     * @param string $barcode
     * @param int $business_id
     * @param string $prefix
     * @return array
     */
    private function parseEan13WeightBarcode($barcode, $business_id, $prefix = '0000')
    {
        // Remove check digit (last digit) for processing
        $barcode_without_check = substr($barcode, 0, 12);
        
        if ($prefix === '0000') {
            // Format: 0000 + 5-digit PLU + 4-digit weight (in grams)
            $plu_code = ltrim(substr($barcode_without_check, 4, 5), '0');
            $weight_grams = (int) substr($barcode_without_check, 9, 4);
            $weight_kg = $weight_grams / 1000;
        } elseif ($prefix === '02') {
            // Format: 02 + 5-digit PLU + 5-digit weight/price
            $plu_code = ltrim(substr($barcode_without_check, 2, 5), '0');
            $weight_value = (int) substr($barcode_without_check, 7, 5);
            // Assume weight is in grams if < 10000, otherwise price in cents
            $weight_kg = $weight_value < 10000 ? $weight_value / 1000 : $weight_value / 100000;
        } else {
            return [
                'success' => false,
                'msg' => 'Unsupported barcode prefix: ' . $prefix,
            ];
        }
        
        // Find product by PLU/SKU
        $result = $this->filterProduct($business_id, $plu_code, null, false, null, [], ['sub_sku', 'sku'], false, 'exact')->first();
        
        // If not found with exact match, try partial match
        if (empty($result)) {
            $result = $this->filterProduct($business_id, $plu_code, null, false, null, [], ['sub_sku', 'sku'], false, 'like')->first();
        }

        if (!empty($result)) {
            return [
                'variation_id' => $result->variation_id,
                'qty' => $weight_kg,
                'success' => true,
                'plu_code' => $plu_code,
                'barcode_type' => 'ean13_weight',
            ];
        }
        
        return [
            'success' => false,
            'msg' => trans('lang_v1.sku_not_match', ['sku' => $plu_code]),
        ];
    }
    
    /**
     * Parse legacy weighing scale barcode format
     * 
     * @param string $scale_barcode
     * @param array $scale_setting
     * @param int $business_id
     * @return array
     */
    private function parseLegacyWeighingBarcode($scale_barcode, $scale_setting, $business_id)
    {
        $error_msg = trans('messages.something_went_wrong');
        
        //Check for prefix.
        if ((strlen($scale_setting['label_prefix']) == 0) || \Illuminate\Support\Str::startsWith($scale_barcode, $scale_setting['label_prefix'])) {
            $scale_barcode = substr($scale_barcode, strlen($scale_setting['label_prefix']));

            //Get product sku, trim left side 0
            $sku = ltrim(substr($scale_barcode, 0, $scale_setting['product_sku_length'] + 1), '0');

            //Get quantity integer
            $qty_int = substr($scale_barcode, $scale_setting['product_sku_length'] + 1, $scale_setting['qty_length'] + 1);

            //Get quantity decimal
            $qty_decimal = '0.' . substr($scale_barcode, $scale_setting['product_sku_length'] + $scale_setting['qty_length'] + 2, $scale_setting['qty_length_decimal'] + 1);

            $qty = (float) $qty_int + (float) $qty_decimal;

            //Find the variation id
            $result = $this->filterProduct($business_id, $sku, null, false, null, [], ['sub_sku'], false, 'exact')->first();

            if (!empty($result)) {
                return [
                    'variation_id' => $result->variation_id,
                    'qty' => $qty,
                    'success' => true,
                    'sku' => $sku,
                    'barcode_type' => 'legacy',
                ];
            } else {
                $error_msg = trans('lang_v1.sku_not_match', ['sku' => $sku]);
            }
        } else {
            $error_msg = trans('lang_v1.prefix_did_not_match');
        }

        return [
            'success' => false,
            'msg' => $error_msg,
        ];
    }

    /**
     * Get warranties for dropdown
     * 
     * @return array
     */
    public function getWarrantiesForDropdown()
    {
        $business_id = session()->get('user.business_id');
        $common_settings = session()->get('business.common_settings');
        $is_warranty_enabled = !empty($common_settings['enable_product_warranty']) ? true : false;
        $warranties = $is_warranty_enabled ? \App\Warranty::forDropdown($business_id) : [];

        return $warranties;
    }

    /**
     * Get last sell line for a customer
     * 
     * @param int $variation_id
     * @param int $customer_id
     * @param int $location_id
     * @return object|null
     */
    public function getLastSellLineForCustomer($variation_id, $customer_id, $location_id)
    {
        $sell_line = \App\TransactionSellLine::join('transactions as t', 't.id', '=', 'transaction_sell_lines.transaction_id')
            ->where('t.location_id', $location_id)
            ->where('t.contact_id', $customer_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->where('transaction_sell_lines.variation_id', $variation_id)
            ->orderBy('t.transaction_date', 'desc')
            ->select('transaction_sell_lines.*')
            ->first();

        return $sell_line;
    }

    /**
     * Generate sell line row for POS
     * This is the core business logic for generating a product row
     * 
     * @param int $variation_id
     * @param int $location_id
     * @param int $quantity
     * @param int $row_count
     * @param bool $is_direct_sell
     * @param bool $is_serial_no
     * @param object|null $so_line
     * @return array
     */
    public function getSellLineRow($variation_id, $location_id, $quantity, $row_count, $is_direct_sell, $is_serial_no, $so_line = null)
    {
        $business_id = request()->session()->get('user.business_id');
        $business_util = new BusinessUtil();
        $transaction_util = new TransactionUtil();
        $contact_util = new ContactUtil();
        
        $business_details = $business_util->getDetails($business_id);
        $weighing_barcode = request()->get('weighing_scale_barcode');

        $pos_settings = empty($business_details->pos_settings) ? $business_util->defaultPosSettings() : json_decode($business_details->pos_settings, true);

        $check_qty = !empty($pos_settings['allow_overselling']) ? false : true;

        $is_sales_order = request()->has('is_sales_order') && request()->input('is_sales_order') == 'true' ? true : false;
        $is_draft = request()->has('is_draft') && request()->input('is_draft') == 'true' ? true : false;
        $is_pop = request()->get('for_pop') == '1' || request()->get('for_pop') == 'true';

        if ($is_sales_order || !empty($so_line) || $is_draft || $is_pop) {
            $check_qty = false;
        }

        // Inline POS return (negative qty): restocks, do not require available stock
        $quantity = is_numeric($quantity) ? (float) $quantity : 1;
        if ($quantity < 0) {
            $check_qty = false;
        }

        if (request()->input('disable_qty_alert') === 'true') {
            $pos_settings['allow_overselling'] = true;
        }

        $product = $this->getDetailsFromVariation($variation_id, $business_id, $location_id, $check_qty);

        if (!isset($product->quantity_ordered)) {
            $product->quantity_ordered = $quantity;
        }

        $product->secondary_unit_quantity = !isset($product->secondary_unit_quantity) ? 0 : $product->secondary_unit_quantity;

        $product->formatted_qty_available = $this->num_f($product->qty_available, false, null, true);

        $sub_units = $this->getSubUnits($business_id, $product->unit_id, false, $product->product_id);

        //Get customer group and change the price accordingly
        $customer_id = request()->get('customer_id', null);
        $cg = $contact_util->getCustomerGroup($business_id, $customer_id);
        $percent = (empty($cg) || empty($cg->amount) || $cg->price_calculation_type != 'percentage') ? 0 : $cg->amount;

        if ($is_pop) {
            // Point of Purchase: show AVCO cost when business uses average cost, else last buy
            $pp = (float) ($product->default_purchase_price ?? 0);
            $pp_inc = (float) ($product->dpp_inc_tax ?? $pp);
            $accounting_method = request()->session()->get('business.accounting_method', 'fifo');
            if (! empty($product->last_purchased_price)) {
                // Prefer last purchase as the editable buy rate (classic purchase form behavior)
                if ($accounting_method !== 'avco') {
                    $pp_inc = (float) $product->last_purchased_price;
                    $pp = $pp_inc;
                }
            }
            $product->default_sell_price = $pp;
            $product->sell_price_inc_tax = $pp_inc > 0 ? $pp_inc : $pp;
        } else {
            $product->default_sell_price = $product->default_sell_price + ($percent * $product->default_sell_price / 100);
            $product->sell_price_inc_tax = $product->sell_price_inc_tax + ($percent * $product->sell_price_inc_tax / 100);
        }

        $tax_dropdown = TaxRate::forBusinessDropdown($business_id, true, true);

        $enabled_modules = $transaction_util->allModulesEnabled();

        //Get lot number dropdown if enabled
        $lot_numbers = [];
        if (request()->session()->get('business.enable_lot_number') == 1 || request()->session()->get('business.enable_product_expiry') == 1) {
            $lot_number_obj = $transaction_util->getLotNumbersFromVariation($variation_id, $business_id, $location_id, true);
            foreach ($lot_number_obj as $lot_number) {
                $lot_number->qty_formated = $this->num_f($lot_number->qty_available);
                $lot_numbers[] = $lot_number;
            }
        }
        $product->lot_numbers = $lot_numbers;

        $purchase_line_id = request()->get('purchase_line_id');

        $price_group = request()->input('price_group');
        if (! $is_pop && ! empty($price_group)) {
            $variation_group_prices = $this->getVariationGroupPrice($variation_id, $price_group, $product->tax_id);

            if (!empty($variation_group_prices['price_inc_tax'])) {
                $product->sell_price_inc_tax = $variation_group_prices['price_inc_tax'];
                $product->default_sell_price = $variation_group_prices['price_exc_tax'];
            }
        }

        $warranties = $this->getWarrantiesForDropdown();

        $output['success'] = true;
        $output['enable_sr_no'] = $product->enable_sr_no;

        $waiters = [];
        $waiters_enabled = false;
        if ($this->isModuleEnabled('service_staff') && !empty($pos_settings['inline_service_staff'])) {
            $waiters_enabled = true;
            $waiters = $this->serviceStaffDropdown($business_id, $location_id);
        }

        $last_sell_line = null;
        if ($is_direct_sell) {
            $last_sell_line = $this->getLastSellLineForCustomer($variation_id, $customer_id, $location_id);
        }

        if (request()->get('type') == 'sell-return') {
            $output['html_content'] = view('sell_return.partials.product_row')
                ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'sub_units'))
                ->render();
        } else {
            $is_cg = !empty($cg->id) ? true : false;

            $discount = $this->getProductDiscount($product, $business_id, $location_id, $is_cg, $price_group, $variation_id);

            if ($is_direct_sell) {
                $edit_discount = auth()->user()->can('edit_product_discount_from_sale_screen');
                $edit_price = auth()->user()->can('edit_product_price_from_sale_screen');
            } else {
                $edit_discount = auth()->user()->can('edit_product_discount_from_pos_screen');
                $edit_price = auth()->user()->can('edit_product_price_from_pos_screen');
            }

            $output['html_content'] = view('sale_pos.product_row')
                ->with(compact('product', 'row_count', 'tax_dropdown', 'enabled_modules', 'pos_settings', 'sub_units', 'discount', 'waiters', 'edit_discount', 'edit_price', 'purchase_line_id', 'warranties', 'quantity', 'is_direct_sell', 'so_line', 'is_sales_order', 'last_sell_line', 'is_serial_no', 'waiters_enabled', 'is_pop'))
                ->render();
        }

        return $output;
    }

    /**
     * Get POS product row (wrapper method)
     * Handles weighing barcode parsing and modifiers
     * 
     * @param int $variation_id
     * @param int $location_id
     * @return array
     */
    public function getPosProductRow($variation_id, $location_id)
    {
        $output = [];

        try {
            $row_count = request()->get('product_row');
            $row_count = $row_count + 1;
            $quantity = request()->get('quantity', 1);
            $weighing_barcode = request()->get('weighing_scale_barcode', null);

            $is_direct_sell = false;
            if (request()->get('is_direct_sell') == 'true') {
                $is_direct_sell = true;
            }

            $is_serial_no = false;
            if (request()->get('is_serial_no') == 'true') {
                $is_serial_no = true;
            }

            if ($variation_id == 'null' && !empty($weighing_barcode)) {
                $product_details = $this->parseWeighingBarcode($weighing_barcode);
                if ($product_details['success']) {
                    $variation_id = $product_details['variation_id'];
                    $quantity = $product_details['qty'];
                } else {
                    $output['success'] = false;
                    $output['msg'] = $product_details['msg'];

                    return $output;
                }
            }

            $output = $this->getSellLineRow($variation_id, $location_id, $quantity, $row_count, $is_direct_sell, $is_serial_no);

            // Add modifiers if restaurant module is enabled
            $transaction_util = new TransactionUtil();
            if ($transaction_util->isModuleEnabled('modifiers') && !$is_direct_sell) {
                $variation = \App\Variation::find($variation_id);
                $business_id = request()->session()->get('user.business_id');
                $this_product = \App\Product::where('business_id', $business_id)
                    ->with(['modifier_sets'])
                    ->find($variation->product_id);
                if ($this_product && count($this_product->modifier_sets) > 0) {
                    $product_ms = $this_product->modifier_sets;
                    $output['html_modifier'] = view('restaurant.product_modifier_set.modifier_for_product')
                        ->with(compact('product_ms', 'row_count'))->render();
                }
            }
        } catch (\Exception $e) {
            \Log::emergency('File:' . $e->getFile() . 'Line:' . $e->getLine() . 'Message:' . $e->getMessage());

            $output['success'] = false;
            $output['msg'] = __('lang_v1.item_out_of_stock');
        }

        return $output;
    }
  
}
