<?php

namespace App\Utils;

/**
 * Validates browser barcode-label settings stored on the business record.
 * No extra table: values live in business.common_settings.
 */
class BrowserLabelLayout
{
    public const BARCODE_TYPES = ['C128', 'C39', 'EAN13', 'EAN8', 'UPCA', 'UPCE'];

    public const FONTS = ['Arial', 'Helvetica', 'Tahoma', 'Verdana', 'Courier New'];

    public static function defaults(string $businessName = ''): array
    {
        return [
            'label_w' => '38',
            'label_h' => '25',
            'margin_top' => '0.6',
            'margin_right' => '1',
            'margin_bottom' => '0.5',
            'margin_left' => '1',
            'offset_x' => '0',
            'offset_y' => '0',
            'gap_x' => '2',
            'gap_y' => '2',
            'columns' => '1',
            'rows' => '1',
            'media' => 'roll',
            // 0 = page height is the sticker length along the feed.
            // 90/270 only if the printer itself turns the page.
            'print_rotate' => '0',
            'layout_preset' => 'standard',
            'font_family' => 'Arial',
            'align' => 'center',
            'business_name_size' => '8',
            'name_size' => '9',
            'sku_size' => '8',
            'price_size' => '12',
            'costcode_size' => '8',
            'brand_size' => '7',
            'business_name_weight' => '900',
            'name_weight' => '800',
            'price_weight' => '900',
            'barcode_height' => '42',
            'barcode_width' => '94',
            'barcode_bar_width' => '2',
            'barcode_position' => 'middle',
            'barcode_format' => 'auto',
            'quiet_zone' => '0.4',
            'price_type' => 'inclusive',
            'label_business_name' => mb_substr(strip_tags($businessName), 0, 80),
            'show_business_name' => '1',
            'show_name' => '1',
            'show_sku' => '1',
            'show_barcode' => '1',
            'show_price' => '1',
            'show_costcode' => '1',
            'show_brand' => '0',
            'show_category' => '0',
            'show_size' => '0',
            'show_color' => '0',
        ];
    }

    /**
     * Visibility flags stored with the layout. Missing keys stay off.
     */
    public static function showFlags(): array
    {
        return [
            'show_business_name', 'show_name', 'show_sku', 'show_barcode', 'show_price',
            'show_costcode', 'show_brand', 'show_category', 'show_size', 'show_color',
        ];
    }

    public static function presets(): array
    {
        return [
            'standard' => [
                'label_w' => '38', 'label_h' => '25', 'print_rotate' => '0', 'media' => 'roll',
                'columns' => '1', 'rows' => '1', 'align' => 'center', 'barcode_position' => 'middle',
                'price_size' => '12', 'name_size' => '9', 'business_name_size' => '8',
                'show_business_name' => '1', 'show_name' => '1', 'show_barcode' => '1',
                'show_sku' => '1', 'show_price' => '1', 'show_costcode' => '1',
                'show_brand' => '0', 'show_category' => '0', 'show_size' => '0', 'show_color' => '0',
            ],
            'portrait_roll' => [
                'label_w' => '25', 'label_h' => '38', 'print_rotate' => '0', 'media' => 'roll',
                'columns' => '1', 'rows' => '1', 'align' => 'center', 'barcode_position' => 'middle',
                'show_business_name' => '1', 'show_name' => '1', 'show_barcode' => '1',
                'show_sku' => '1', 'show_price' => '1', 'show_costcode' => '1',
                'show_size' => '0', 'show_color' => '0',
            ],
            'compact' => [
                'label_w' => '38', 'label_h' => '25', 'print_rotate' => '0', 'media' => 'roll',
                'business_name_size' => '7', 'name_size' => '8', 'price_size' => '11', 'sku_size' => '7',
                'barcode_height' => '28', 'show_business_name' => '1', 'show_name' => '1',
                'show_barcode' => '1', 'show_sku' => '1', 'show_price' => '1', 'show_costcode' => '0',
                'show_brand' => '0', 'show_category' => '0', 'show_size' => '0', 'show_color' => '0',
            ],
            'price' => [
                'label_w' => '38', 'label_h' => '25', 'print_rotate' => '0', 'media' => 'roll',
                'price_size' => '16', 'price_weight' => '900', 'name_size' => '8',
                'show_business_name' => '0', 'show_name' => '1', 'show_barcode' => '1',
                'show_sku' => '1', 'show_price' => '1', 'show_costcode' => '0',
                'show_brand' => '0', 'show_category' => '0', 'show_size' => '0', 'show_color' => '0',
                'barcode_position' => 'top',
            ],
            'barcode_only' => [
                'label_w' => '38', 'label_h' => '25', 'print_rotate' => '0', 'media' => 'roll',
                'barcode_height' => '48', 'barcode_width' => '96',
                'show_business_name' => '0', 'show_name' => '0', 'show_barcode' => '1',
                'show_sku' => '1', 'show_price' => '0', 'show_costcode' => '0',
                'show_brand' => '0', 'show_category' => '0', 'show_size' => '0', 'show_color' => '0',
                'barcode_position' => 'middle',
            ],
            'two_column' => [
                'media' => 'sheet', 'columns' => '2', 'rows' => '1', 'print_rotate' => '0',
                'label_w' => '38', 'label_h' => '25', 'gap_x' => '2', 'gap_y' => '0',
                'show_business_name' => '1', 'show_name' => '1', 'show_barcode' => '1',
                'show_sku' => '1', 'show_price' => '1', 'show_costcode' => '1',
                'show_size' => '0', 'show_color' => '0',
            ],
        ];
    }

    public static function normalize(array $in, string $businessName = ''): array
    {
        $d = self::defaults($businessName);
        $src = array_merge($d, $in);

        $num = function (string $key, float $min, float $max, int $precision = 2) use ($src, $d) {
            $v = (float) ($src[$key] ?? $d[$key]);
            if (! is_finite($v)) {
                $v = (float) $d[$key];
            }

            return (string) round(max($min, min($max, $v)), $precision);
        };
        $int = function (string $key, int $min, int $max) use ($src, $d) {
            return (string) max($min, min($max, (int) ($src[$key] ?? $d[$key])));
        };
        $enum = function (string $key, array $allowed, string $fallback) use ($src) {
            $v = (string) ($src[$key] ?? $fallback);

            return in_array($v, $allowed, true) ? $v : $fallback;
        };
        $flag = function (string $key) use ($src) {
            $raw = $src[$key] ?? '0';

            return ! in_array((string) $raw, ['0', 'false', 'off', '', 'null'], true) ? '1' : '0';
        };
        $weight = function (string $key, string $fallback) use ($src) {
            $v = (string) ($src[$key] ?? $fallback);

            return in_array($v, ['400', '700', '800', '900'], true) ? $v : $fallback;
        };

        $layout = [
            'label_w' => $num('label_w', 15, 120),
            'label_h' => $num('label_h', 10, 120),
            'margin_top' => $num('margin_top', 0, 8),
            'margin_right' => $num('margin_right', 0, 8),
            'margin_bottom' => $num('margin_bottom', 0, 8),
            'margin_left' => $num('margin_left', 0, 8),
            'offset_x' => $num('offset_x', -15, 15),
            'offset_y' => $num('offset_y', -15, 15),
            'gap_x' => $num('gap_x', 0, 20),
            'gap_y' => $num('gap_y', 0, 20),
            'columns' => $int('columns', 1, 6),
            'rows' => $int('rows', 1, 20),
            'media' => $enum('media', ['roll', 'sheet'], 'roll'),
            'print_rotate' => $enum('print_rotate', ['0', '90', '180', '270'], '0'),
            'layout_preset' => $enum('layout_preset', array_keys(self::presets()), 'standard'),
            'font_family' => $enum('font_family', self::FONTS, 'Arial'),
            'align' => $enum('align', ['left', 'center', 'right'], 'center'),
            'business_name_size' => $int('business_name_size', 6, 28),
            'name_size' => $int('name_size', 6, 32),
            'sku_size' => $int('sku_size', 5, 22),
            'price_size' => $int('price_size', 6, 36),
            'costcode_size' => $int('costcode_size', 5, 22),
            'brand_size' => $int('brand_size', 5, 18),
            'business_name_weight' => $weight('business_name_weight', '900'),
            'name_weight' => $weight('name_weight', '800'),
            'price_weight' => $weight('price_weight', '900'),
            'barcode_height' => $int('barcode_height', 16, 80),
            'barcode_width' => $int('barcode_width', 40, 100),
            'barcode_bar_width' => $int('barcode_bar_width', 1, 4),
            'barcode_position' => $enum('barcode_position', ['top', 'middle', 'bottom'], 'middle'),
            'barcode_format' => $enum('barcode_format', array_merge(['auto'], self::BARCODE_TYPES), 'auto'),
            'quiet_zone' => $num('quiet_zone', 0, 3),
            'price_type' => $enum('price_type', ['inclusive', 'exclusive'], 'inclusive'),
            'label_business_name' => mb_substr(strip_tags(trim((string) ($src['label_business_name'] ?? $businessName))), 0, 80),
        ];

        foreach (self::showFlags() as $flagKey) {
            $layout[$flagKey] = $flag($flagKey);
        }

        // Rotation turns the artwork inside each sticker. It does not change the page size.
        if ($layout['media'] !== 'sheet') {
            $layout['columns'] = '1';
            $layout['rows'] = '1';
        }

        // Inner padding cannot eat the whole sticker.
        $maxPadW = max(0, ((float) $layout['label_w'] / 2) - 2);
        $maxPadH = max(0, ((float) $layout['label_h'] / 2) - 2);
        $layout['margin_left'] = (string) min((float) $layout['margin_left'], $maxPadW);
        $layout['margin_right'] = (string) min((float) $layout['margin_right'], $maxPadW);
        $layout['margin_top'] = (string) min((float) $layout['margin_top'], $maxPadH);
        $layout['margin_bottom'] = (string) min((float) $layout['margin_bottom'], $maxPadH);

        return $layout;
    }

    /**
     * Printed page size. For a roll, height is the sticker length along the feed
     * and width is across the roll, so the gap between stickers falls between pages.
     */
    public static function pageBox(array $layout): array
    {
        $layout = self::normalize($layout);
        $lw = (float) $layout['label_w'];
        $lh = (float) $layout['label_h'];
        $cols = (int) $layout['columns'];
        $rows = (int) $layout['rows'];
        $gapX = (float) $layout['gap_x'];
        $gapY = (float) $layout['gap_y'];
        $rot = $layout['print_rotate'];

        if ($layout['media'] === 'sheet') {
            $pageW = ($cols * $lw) + (max(0, $cols - 1) * $gapX);
            $pageH = ($rows * $lh) + (max(0, $rows - 1) * $gapY);
            $per = $cols * $rows;
        } else {
            $cols = 1;
            $rows = 1;
            $gapX = 0;
            $gapY = 0;
            $per = 1;
            $pageW = $lw;
            $pageH = $lh;
        }

        return [
            'page_w' => round($pageW, 2),
            'page_h' => round($pageH, 2),
            'label_w' => $lw,
            'label_h' => $lh,
            'columns' => $cols,
            'rows' => $rows,
            'gap_x' => $gapX,
            'gap_y' => $gapY,
            'per_page' => $per,
            'rotate' => $rot,
        ];
    }

    public static function resolveBarcodeType(string $requested, string $code): string
    {
        $type = strtoupper(trim($requested));
        if ($type === '' || $type === 'AUTO') {
            $type = 'C128';
        }
        if (! in_array($type, self::BARCODE_TYPES, true)) {
            $type = 'C128';
        }
        $digits = preg_replace('/\D/', '', $code);
        if ($type === 'EAN13' && ! in_array(strlen((string) $digits), [12, 13], true)) {
            return 'C128';
        }
        if ($type === 'EAN8' && ! in_array(strlen((string) $digits), [7, 8], true)) {
            return 'C128';
        }
        if ($type === 'UPCA' && ! in_array(strlen((string) $digits), [11, 12], true)) {
            return 'C128';
        }
        if ($type === 'UPCE' && strlen((string) $digits) < 6) {
            return 'C128';
        }
        if ($type === 'C39' && ! preg_match('/^[0-9A-Z\-\.\ \$\/\+\%]+$/', strtoupper($code))) {
            return 'C128';
        }

        return $type;
    }

    public static function normalizeProfiles(array $profiles, string $businessName = ''): array
    {
        $out = [];
        foreach ($profiles as $profile) {
            if (! is_array($profile)) {
                continue;
            }
            $name = mb_substr(strip_tags(trim((string) ($profile['name'] ?? ''))), 0, 40);
            if ($name === '') {
                continue;
            }
            $id = preg_replace('/[^a-zA-Z0-9_\-]/', '', (string) ($profile['id'] ?? ''));
            if ($id === '') {
                $id = 'p'.substr(bin2hex(random_bytes(4)), 0, 8);
            }
            $layoutIn = is_array($profile['layout'] ?? null) ? $profile['layout'] : [];
            $out[] = [
                'id' => $id,
                'name' => $name,
                'layout' => self::normalize($layoutIn, $businessName),
            ];
            if (count($out) >= 12) {
                break;
            }
        }

        return $out;
    }
}
