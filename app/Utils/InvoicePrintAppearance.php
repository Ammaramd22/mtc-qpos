<?php

namespace App\Utils;

use App\InvoiceLayout;

/**
 * Colour / black-and-white appearance stored on an invoice layout.
 * Black and white is applied to the rendered receipt HTML.
 * Uploaded logo and letterhead files are not modified.
 */
class InvoicePrintAppearance
{
    public const COLOUR = 'colour';

    public const BLACK_WHITE = 'black_white';

    public static function normalize($value): string
    {
        return $value === self::BLACK_WHITE ? self::BLACK_WHITE : self::COLOUR;
    }

    /**
     * @param  InvoiceLayout|object|null  $layout
     */
    public static function fromLayout($layout): string
    {
        if (empty($layout)) {
            return self::COLOUR;
        }
        $settings = $layout->common_settings ?? [];
        if (is_string($settings)) {
            $decoded = json_decode($settings, true);
            $settings = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($settings)) {
            return self::COLOUR;
        }

        return self::normalize($settings['print_appearance'] ?? null);
    }

    /**
     * Attach the saved appearance to receipt details.
     * Colour mode leaves existing accent colours untouched.
     *
     * @param  array<string, mixed>  $output
     * @param  InvoiceLayout|object|null  $layout
     * @return array<string, mixed>
     */
    public static function applyToDetails(array $output, $layout): array
    {
        $mode = self::fromLayout($layout);
        $output['print_appearance'] = $mode;
        $skin = self::skinFromLayout($layout);
        $output['design_skin'] = $skin;
        if ($mode === self::BLACK_WHITE) {
            // Pure black for thermal / monochrome printers. Template CSS keeps
            // filled bars white with black borders so text stays readable.
            $output['color'] = '#000000';
        } elseif (! empty($skin['use_colours']) && $skin['primary'] !== '') {
            $output['color'] = $skin['primary'];
        }

        return $output;
    }

    /**
     * Render a receipt blade and, when needed, apply the monochrome treatment.
     *
     * @param  array<string, mixed>  $data
     */
    public static function render(string $view, array $data): string
    {
        $html = view($view, $data)->render();
        $details = $data['receipt_details'] ?? null;
        $mode = self::COLOUR;
        $skin = self::normalizeSkin([]);
        if (is_object($details)) {
            if (isset($details->print_appearance)) {
                $mode = self::normalize($details->print_appearance);
            }
            if (isset($details->design_skin)) {
                $skin = self::normalizeSkin($details->design_skin);
            }
        }
        if ($mode === self::BLACK_WHITE) {
            $html = self::decorate($html);
        }

        $html = self::applySkin($html, $skin, $mode);

        // 80mm thermal templates must print pure black regardless of theme /
        // designer accent colours (physical printers are monochrome).
        return self::thermalInk($html);
    }

    /**
     * Force high-contrast black ink on Classic 80mm thermal markup.
     * Safe to call for every receipt — only affects .t80 nodes.
     */
    public static function thermalInk(string $html): string
    {
        if (! preg_match('/class=(["\'])[^"\']*\bt80\b/i', $html)) {
            return $html;
        }

        $css = '<style id="qpos-thermal-ink">'
            .'.t80,.t80 *,.qpos-design-skin .t80,.qpos-design-skin .t80 *,'
            .'.qpos-print-bw .t80,.qpos-print-bw .t80 *{'
            .'color:#000 !important;}'
            .'.t80,.qpos-design-skin .t80,.qpos-print-bw .t80{'
            .'background:#fff !important;}'
            .'.t80 .t80-bar,.t80 .t80-pay,.t80 .t80-pay-head,.t80 .t80-party,'
            .'.t80 .t80-save,.t80 .t80-pay-row.is-change,'
            .'.qpos-design-skin .t80 .t80-bar,.qpos-design-skin .t80 .t80-pay,'
            .'.qpos-design-skin .t80 .t80-pay-head,.qpos-design-skin .t80 .t80-party,'
            .'.qpos-design-skin .t80 .t80-save{'
            .'background:#fff !important;background-color:#fff !important;'
            .'color:#000 !important;border-color:#000 !important;}'
            .'.t80 .rcpt-cur{font-style:normal !important;font-family:Arial,Helvetica,sans-serif !important;}'
            .'</style>';

        return $html.$css;
    }

    /**
     * Monochrome treatment for an already rendered receipt.
     * Text and borders are darkened, brand fills become light gray,
     * and images are filtered in the browser. Source image files stay as uploaded.
     */
    public static function decorate(string $html): string
    {
        $html = self::rewriteColours($html);
        $css = '<style id="qpos-print-bw">'
            .'.qpos-print-bw,.qpos-print-bw *{background-color:transparent;}'
            .'.qpos-print-bw{background:#fff !important;color:#000 !important;}'
            .'.qpos-print-bw,.qpos-print-bw p,.qpos-print-bw span,.qpos-print-bw td,'
            .'.qpos-print-bw th,.qpos-print-bw div,.qpos-print-bw strong,.qpos-print-bw b,'
            .'.qpos-print-bw label,.qpos-print-bw small{color:#000 !important;}'
            .'.qpos-print-bw img{filter:grayscale(1) contrast(1.35);-webkit-filter:grayscale(1) contrast(1.35);}'
            /* A4 / A5 table headers stay lightly shaded for screen+laser; thermal .t80 stays white */
            .'.qpos-print-bw .a4-badge,'
            .'.qpos-print-bw .a4-pay-head,'
            .'.qpos-print-bw .a4-inv table.a4-lines thead th,'
            .'.qpos-print-bw .a5-sheet table.a4-lines thead th,'
            .'.qpos-print-bw .a5-sheet .a4-pay-head,'
            .'.qpos-print-bw .a5-sheet .a4-badge,'
            .'.qpos-print-bw thead th{'
            .'background:#e8e8e8 !important;background-color:#e8e8e8 !important;color:#000 !important;border-color:#000 !important;}'
            .'.qpos-print-bw .t80,'
            .'.qpos-print-bw .t80 .t80-bar,'
            .'.qpos-print-bw .t80 .t80-pay,'
            .'.qpos-print-bw .t80 .t80-pay-head,'
            .'.qpos-print-bw .t80 .t80-party,'
            .'.qpos-print-bw .t80 .t80-save,'
            .'.qpos-print-bw .t80 .t80-pay-row.is-change{'
            .'background:#fff !important;background-color:#fff !important;color:#000 !important;border-color:#000 !important;}'
            .'.qpos-print-bw .a4-inv .a4-pay-row.is-change .a4-pay-method,'
            .'.qpos-print-bw .a4-inv .a4-pay-row.is-change .a4-pay-amt,'
            .'.qpos-print-bw .a5-sheet .a4-pay-row.is-change .a4-pay-method,'
            .'.qpos-print-bw .a5-sheet .a4-pay-row.is-change .a4-pay-amt,'
            .'.qpos-print-bw .t80 .t80-pay-row.is-change .t80-pay-method,'
            .'.qpos-print-bw .t80 .t80-pay-row.is-change .t80-pay-amt,'
            .'.qpos-print-bw .t80 .t80-tag,'
            .'.qpos-print-bw .t80 .t80-pay-meta,'
            .'.qpos-print-bw .t80 .t80-meta,'
            .'.qpos-print-bw .t80 .t80-row .l,'
            .'.qpos-print-bw .t80 .t80-item-sub,'
            .'.qpos-print-bw .t80 .t80-totals .lab,'
            .'.qpos-print-bw .a4-inv .a4-item-sub,'
            .'.qpos-print-bw .a4-inv .a4-item-repair-meta,'
            .'.qpos-print-bw .a5-sheet .a4-item-sub,'
            .'.qpos-print-bw .a5-sheet .a4-item-repair-meta,'
            .'.qpos-print-bw .a4-foot,'
            .'.qpos-print-bw .t80-foot{color:#000 !important;}'
            .'</style>';

        return '<div class="qpos-print-bw">'.$html.$css.'</div>';
    }

    private static function rewriteColours(string $html): string
    {
        $replacements = [
            '#357ca5' => '#000000',
            '#2d2360' => '#000000',
            '#eae7f2' => '#ffffff',
            '#f7f8fa' => '#ffffff',
            '#f8fafc' => '#ffffff',
            '#1e3a5f' => '#000000',
            '#1a3a4a' => '#000000',
            '#b45309' => '#000000',
            '#b91c1c' => '#000000',
            '#64748b' => '#000000',
            '#6b7280' => '#000000',
            '#555555' => '#000000',
            '#555' => '#000000',
            '#333333' => '#000000',
            '#333' => '#000000',
            '#222222' => '#000000',
            '#222' => '#000000',
            '#111111' => '#000000',
            '#111' => '#000000',
            '#e5e7eb' => '#000000',
            '#d1d5db' => '#000000',
            '#bbbbbb' => '#000000',
            '#bbb' => '#000000',
        ];
        foreach ($replacements as $from => $to) {
            $html = preg_replace('/'.preg_quote($from, '/').'\b/i', $to, $html);
        }

        // Soft fills used as section backgrounds stay white on thermal/B&W.
        $html = preg_replace(
            '/background(-color)?\s*:\s*(?:#e6e6e6|#e8e8e8|#dcdcdc|#f3f3f3)\b/i',
            'background$1:#ffffff',
            $html
        );

        // White ink on former coloured bars → solid black text.
        $html = preg_replace(
            '/color\s*:\s*(?:white|#fff|#ffffff)\b\s*(?:!important)?/i',
            'color:#000000 !important',
            $html
        );

        return $html;
    }

    /**
     * Print-safe font choices. The key is the CSS font-family value.
     *
     * @return array<string, string>
     */
    public static function fontChoices(): array
    {
        return [
            '' => 'Template default',
            'Arial, Helvetica, sans-serif' => 'Arial',
            'Tahoma, Arial, sans-serif' => 'Tahoma',
            'Verdana, Geneva, sans-serif' => 'Verdana',
            '"Times New Roman", Times, serif' => 'Times New Roman',
            '"Courier New", Courier, monospace' => 'Courier New',
        ];
    }

    /**
     * @param  InvoiceLayout|object|null  $layout
     * @return array<string, int|string>
     */
    public static function skinFromLayout($layout): array
    {
        $settings = [];
        if (! empty($layout)) {
            $settings = $layout->common_settings ?? [];
            if (is_string($settings)) {
                $decoded = json_decode($settings, true);
                $settings = is_array($decoded) ? $decoded : [];
            }
        }
        if (! is_array($settings)) {
            $settings = [];
        }

        return self::normalizeSkin($settings['designer'] ?? []);
    }

    /**
     * Keep only values that can be printed safely. Blank sizes and colours
     * mean "leave the template alone", so existing layouts do not change
     * until someone sets them.
     *
     * @param  mixed  $value
     * @return array<string, int|string>
     */
    public static function normalizeSkin($value): array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($value)) {
            $value = [];
        }

        $fonts = self::fontChoices();
        $font = (string) ($value['font_family'] ?? '');
        if (! array_key_exists($font, $fonts)) {
            $font = '';
        }

        $align = function ($raw) {
            $raw = (string) $raw;

            return in_array($raw, ['left', 'center', 'right'], true) ? $raw : '';
        };
        $size = function ($raw, $min, $max) {
            if ($raw === '' || $raw === null) {
                return 0;
            }
            $number = (int) $raw;
            if ($number === 0) {
                return 0;
            }

            return max($min, min($max, $number));
        };
        $hex = function ($raw) {
            $raw = strtolower(trim((string) $raw));

            return preg_match('/^#[0-9a-f]{6}$/', $raw) ? $raw : '';
        };
        $gap = function ($raw, $max) {
            $number = (int) $raw;
            if ($number < 0) {
                return 0;
            }

            return min($max, $number);
        };

        return [
            'use_colours' => ! empty($value['use_colours']) ? 1 : 0,
            'font_family' => $font,
            'business_size' => $size($value['business_size'] ?? 0, 10, 28),
            'heading_size' => $size($value['heading_size'] ?? 0, 12, 32),
            'table_size' => $size($value['table_size'] ?? 0, 8, 16),
            'totals_size' => $size($value['totals_size'] ?? 0, 10, 22),
            'footer_size' => $size($value['footer_size'] ?? 0, 8, 16),
            'primary' => $hex($value['primary'] ?? ''),
            'text' => $hex($value['text'] ?? ''),
            'table_header_bg' => $hex($value['table_header_bg'] ?? ''),
            'table_header_text' => $hex($value['table_header_text'] ?? ''),
            'border' => $hex($value['border'] ?? ''),
            'total_highlight' => $hex($value['total_highlight'] ?? ''),
            'footer_color' => $hex($value['footer_color'] ?? ''),
            'align_business' => $align($value['align_business'] ?? ''),
            'align_heading' => $align($value['align_heading'] ?? ''),
            'align_footer' => $align($value['align_footer'] ?? ''),
            'logo_mm' => $gap($value['logo_mm'] ?? 0, 40),
            'section_gap' => $gap($value['section_gap'] ?? 0, 24),
            'cell_pad' => $gap($value['cell_pad'] ?? 0, 12),
            'border_width' => $gap($value['border_width'] ?? 0, 3),
            'page_margin' => $gap($value['page_margin'] ?? 0, 20),
        ];
    }

    /**
     * @param  array<string, int|string>  $skin
     */
    public static function skinStyle(array $skin, string $mode): string
    {
        $skin = self::normalizeSkin($skin);
        $mono = self::normalize($mode) === self::BLACK_WHITE;
        $rules = [];

        if ($skin['font_family'] !== '') {
            $rules[] = '.qpos-design-skin,.qpos-design-skin body,.qpos-design-skin .t80,.qpos-design-skin .a4-inv{font-family:'.$skin['font_family'].' !important;}';
        }
        if ($skin['business_size'] > 0) {
            $px = (int) $skin['business_size'];
            $rules[] = '.qpos-design-skin .t80-biz,.qpos-design-skin .a4-biz-name,.qpos-design-skin .a4-shop{font-size:'.$px.'px !important;}';
        }
        if ($skin['heading_size'] > 0) {
            $px = (int) $skin['heading_size'];
            $rules[] = '.qpos-design-skin .t80-doc-title,.qpos-design-skin .a4-doc-title{font-size:'.$px.'px !important;}';
        }
        if ($skin['table_size'] > 0) {
            $px = (int) $skin['table_size'];
            $rules[] = '.qpos-design-skin .t80-item,.qpos-design-skin .t80-bar,.qpos-design-skin table.a4-lines,.qpos-design-skin table.a4-lines th,.qpos-design-skin table.a4-lines td,.qpos-design-skin table{font-size:'.$px.'px !important;}';
        }
        if ($skin['totals_size'] > 0) {
            $px = (int) $skin['totals_size'];
            $rules[] = '.qpos-design-skin .t80-totals,.qpos-design-skin .t80-totals td,.qpos-design-skin .t80-pay,.qpos-design-skin .a4-pay,.qpos-design-skin .a4-total{font-size:'.$px.'px !important;}';
        }
        if ($skin['footer_size'] > 0) {
            $px = (int) $skin['footer_size'];
            $rules[] = '.qpos-design-skin .t80-foot,.qpos-design-skin .a4-foot,.qpos-design-skin .t80-thanks{font-size:'.$px.'px !important;}';
        }
        if ($skin['align_business'] !== '') {
            $rules[] = '.qpos-design-skin .t80-head,.qpos-design-skin .a4-top-left,.qpos-design-skin .a4-biz-name{text-align:'.$skin['align_business'].' !important;}';
        }
        if ($skin['align_heading'] !== '') {
            $rules[] = '.qpos-design-skin .t80-doc,.qpos-design-skin .t80-doc-title,.qpos-design-skin .a4-doc-title,.qpos-design-skin .a4-top-right{text-align:'.$skin['align_heading'].' !important;}';
        }
        if ($skin['align_footer'] !== '') {
            $rules[] = '.qpos-design-skin .t80-foot,.qpos-design-skin .a4-foot,.qpos-design-skin .t80-thanks{text-align:'.$skin['align_footer'].' !important;}';
        }
        if ($skin['logo_mm'] > 0) {
            $mm = (int) $skin['logo_mm'];
            $rules[] = '.qpos-design-skin .t80-logo,.qpos-design-skin .a4-logo{max-height:'.$mm.'mm !important;width:auto !important;}';
        }
        if ($skin['section_gap'] > 0) {
            $px = (int) $skin['section_gap'];
            $rules[] = '.qpos-design-skin .t80-head,.qpos-design-skin .t80-doc,.qpos-design-skin .a4-top,.qpos-design-skin .a4-parties{margin-bottom:'.$px.'px !important;}';
        }
        if ($skin['cell_pad'] > 0) {
            $px = (int) $skin['cell_pad'];
            $rules[] = '.qpos-design-skin table th,.qpos-design-skin table td,.qpos-design-skin .t80-item{padding:'.$px.'px !important;}';
        }
        if ($skin['border_width'] > 0) {
            $px = (int) $skin['border_width'];
            $rules[] = '.qpos-design-skin table,.qpos-design-skin table th,.qpos-design-skin table td{border-width:'.$px.'px !important;border-style:solid !important;}';
        }
        if ($skin['page_margin'] > 0) {
            $mm = (int) $skin['page_margin'];
            $rules[] = '@page{margin:'.$mm.'mm;}';
            $rules[] = '.qpos-design-skin .t80,.qpos-design-skin .a4-inv{padding:'.$mm.'mm !important;}';
        }

        if (! $mono && ! empty($skin['use_colours'])) {
            if ($skin['text'] !== '') {
                $rules[] = '.qpos-design-skin,.qpos-design-skin .t80,.qpos-design-skin .a4-inv{color:'.$skin['text'].' !important;}';
            }
            if ($skin['primary'] !== '') {
                $rules[] = '.qpos-design-skin .t80-doc-title,.qpos-design-skin .a4-doc-title,.qpos-design-skin .a4-biz-name,.qpos-design-skin .t80-biz{color:'.$skin['primary'].' !important;}';
            }
            $headerBg = $skin['table_header_bg'] !== '' ? $skin['table_header_bg'] : $skin['primary'];
            $headerText = $skin['table_header_text'] !== '' ? $skin['table_header_text'] : '';
            if ($headerBg !== '') {
                $color = $headerText !== '' ? 'color:'.$headerText.' !important;' : '';
                $rules[] = '.qpos-design-skin .t80-bar,.qpos-design-skin .t80-pay-head,.qpos-design-skin thead th,.qpos-design-skin .a4-badge{background:'.$headerBg.' !important;background-color:'.$headerBg.' !important;'.$color.'}';
            }
            if ($skin['border'] !== '') {
                $rules[] = '.qpos-design-skin table,.qpos-design-skin table th,.qpos-design-skin table td,.qpos-design-skin .t80-head,.qpos-design-skin .t80-doc{border-color:'.$skin['border'].' !important;}';
            }
            if ($skin['total_highlight'] !== '') {
                $rules[] = '.qpos-design-skin .t80-totals tr:last-child td,.qpos-design-skin .a4-total{color:'.$skin['total_highlight'].' !important;}';
            }
            if ($skin['footer_color'] !== '') {
                $rules[] = '.qpos-design-skin .t80-foot,.qpos-design-skin .a4-foot,.qpos-design-skin .t80-thanks{color:'.$skin['footer_color'].' !important;}';
            }
        }

        if ($rules === []) {
            return '';
        }

        return '<style id="qpos-design-skin">'.implode('', $rules).'</style>';
    }

    /**
     * @param  array<string, int|string>  $skin
     */
    private static function applySkin(string $html, array $skin, string $mode): string
    {
        $css = self::skinStyle($skin, $mode);
        if ($css === '') {
            return $html;
        }

        return '<div class="qpos-design-skin">'.$html.$css.'</div>';
    }
}
