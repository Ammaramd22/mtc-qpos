{{-- Format money string so currency symbol (Rs) uses a distinct font --}}
@php
    if (! function_exists('vspos_rcpt_money')) {
        function vspos_rcpt_money($amount): string
        {
            $raw = trim(html_entity_decode(strip_tags((string) $amount), ENT_QUOTES, 'UTF-8'));
            if ($raw === '') {
                return '';
            }
            // Match leading or trailing currency symbols/codes
            if (preg_match('/^([Rs\.₹$€£¥]+)\s*(.+)$/iu', $raw, $m)) {
                return '<span class="rcpt-cur">'.e($m[1]).'</span>&nbsp;'.e(trim($m[2]));
            }
            if (preg_match('/^(.+?)\s*([Rs\.₹$€£¥]+)$/iu', $raw, $m)) {
                return e(trim($m[1])).'&nbsp;<span class="rcpt-cur">'.e($m[2]).'</span>';
            }

            return e($raw);
        }
    }
@endphp
<style>
    .rcpt-cur {
        font-family: "Georgia", "Times New Roman", "Palatino Linotype", serif !important;
        font-weight: 700 !important;
        font-style: italic !important;
        letter-spacing: .02em;
        font-size: 0.92em;
    }
</style>
