@extends('layouts.guest')
@section('title', $title)
@section('content')

<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@700;800;900&display=swap');

    /* ── Professional 80mm eBill Invoice Page ── */
    .ebill-page {
        background: #f1f5f9;
        min-height: 100vh;
        padding: 16px 6px;
        display: flex;
        flex-direction: column;
        align-items: center;
        font-family: 'Inter', Arial, Helvetica, sans-serif;
    }

    /* Action Buttons */
    .ebill-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 10px;
        flex-wrap: wrap;
        justify-content: center;
    }
    .ebill-actions .btn {
        border-radius: 10px;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 8px 16px;
        border: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all .2s ease;
        cursor: pointer;
        text-decoration: none;
    }
    .ebill-actions .btn:active { transform: scale(0.96); }
    .btn-share {
        background: linear-gradient(135deg, #7c3aed, #6366f1);
        color: #fff;
        box-shadow: 0 4px 14px rgba(99,102,241,0.4);
    }
    .btn-pay {
        background: linear-gradient(135deg, #0ea5e9, #0284c7);
        color: #fff;
        box-shadow: 0 4px 14px rgba(14,165,233,0.4);
    }
    .btn-back {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }

    /* Receipt Paper - 80mm (302px at 96dpi) */
    .ebill-receipt-paper {
        width: 100%;
        max-width: 302px;
        background: #fff;
        border-radius: 2px;
        padding: 12px 8px 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,.1), 0 10px 40px rgba(0,0,0,.08);
        position: relative;
        margin: 0 auto;
        overflow: hidden;
        box-sizing: border-box;
    }

    /* Serrated top/bottom edges */
    .ebill-receipt-paper::before,
    .ebill-receipt-paper::after {
        content: '';
        position: absolute;
        left: 0; right: 0;
        height: 10px;
        background: radial-gradient(circle at 10px 15px, transparent 6px, #fff 6.5px);
        background-size: 20px 20px;
    }
    .ebill-receipt-paper::before { top: -10px; transform: rotate(180deg); }
    .ebill-receipt-paper::after { bottom: -10px; }

    /* ── Inner Receipt — match slim 80mm template ── */
    .ebill-receipt-paper #invoice_content {
        font-family: 'Inter', Arial, Helvetica, sans-serif !important;
        font-size: 11px !important;
        line-height: 1.35 !important;
        color: #111 !important;
        box-sizing: border-box !important;
    }
    .ebill-receipt-paper #invoice_content *,
    .ebill-receipt-paper #invoice_content *::before,
    .ebill-receipt-paper #invoice_content *::after {
        box-sizing: border-box !important;
    }
    .ebill-receipt-paper #invoice_content .ticket,
    .ebill-receipt-paper #invoice_content > div {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        overflow: hidden !important;
    }
    .ebill-receipt-paper #invoice_content p {
        margin: 0 !important;
    }

    /* Logo */
    .ebill-receipt-paper #invoice_content img,
    .ebill-receipt-paper #invoice_content .receipt-logo {
        max-width: 58mm !important;
        max-height: 22mm !important;
        width: auto !important;
        height: auto !important;
        margin: 0 auto 5px !important;
        display: block !important;
        object-fit: contain !important;
    }

    /* Header */
    .ebill-receipt-paper #invoice_content .text-box {
        text-align: center !important;
        margin-bottom: 6px !important;
        padding-bottom: 6px !important;
        border-bottom: 1px solid #111 !important;
    }
    .ebill-receipt-paper #invoice_content .text-box p {
        text-align: center !important;
        margin: 0 0 2px !important;
        font-size: 10px !important;
        color: #333 !important;
        line-height: 1.4 !important;
        max-width: 100% !important;
        word-wrap: break-word !important;
        white-space: normal !important;
        font-weight: 400 !important;
    }
    .ebill-receipt-paper #invoice_content .text-box p b,
    .ebill-receipt-paper #invoice_content .text-box p strong {
        color: #111 !important;
        font-weight: 600 !important;
        font-size: inherit !important;
    }

    /* Company name — Montserrat Black */
    .ebill-receipt-paper #invoice_content .headings,
    .ebill-receipt-paper #invoice_content .business-name,
    .ebill-receipt-paper #invoice_content .text-box .headings,
    .ebill-receipt-paper #invoice_content .text-box .business-name {
        font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif !important;
        font-size: 18px !important;
        font-weight: 900 !important;
        text-align: center !important;
        margin: 2px 0 4px !important;
        color: #000 !important;
        letter-spacing: 0.8px !important;
        text-transform: uppercase !important;
        line-height: 1.15 !important;
        display: block !important;
    }
    .ebill-receipt-paper #invoice_content .sub-headings {
        font-family: 'Montserrat', 'Inter', Arial, Helvetica, sans-serif !important;
        font-size: 12px !important;
        font-weight: 800 !important;
        font-style: normal !important;
        color: #111 !important;
        text-align: center !important;
        display: block !important;
        margin: 5px 0 0 !important;
        letter-spacing: 1.8px !important;
        text-transform: uppercase !important;
    }

    .ebill-receipt-paper #invoice_content br + br { display: none !important; }

    /* Info rows */
    .ebill-receipt-paper #invoice_content .textbox-info,
    .ebill-receipt-paper #invoice_content .invoice-info,
    .ebill-receipt-paper #invoice_content .info-row,
    .ebill-receipt-paper #invoice_content .flex-box,
    .ebill-receipt-paper #invoice_content .table-info {
        display: flex !important;
        justify-content: space-between !important;
        align-items: flex-start !important;
        gap: 6px !important;
        padding: 3px 0 !important;
        font-size: 10.5px !important;
        border-bottom: 1px dotted #999 !important;
        width: 100% !important;
    }
    .ebill-receipt-paper #invoice_content .flex-box,
    .ebill-receipt-paper #invoice_content .table-info {
        border-bottom: none !important;
        align-items: baseline !important;
        font-size: 11px !important;
    }
    .ebill-receipt-paper #invoice_content .textbox-info > p:first-child,
    .ebill-receipt-paper #invoice_content .flex-box > p:first-child,
    .ebill-receipt-paper #invoice_content .table-info > p:first-child,
    .ebill-receipt-paper #invoice_content .f-left {
        flex: 0 1 auto !important;
        max-width: 42% !important;
        text-align: left !important;
        width: auto !important;
        font-weight: 700 !important;
        color: #111 !important;
        font-size: inherit !important;
    }
    .ebill-receipt-paper #invoice_content .textbox-info > p:last-child,
    .ebill-receipt-paper #invoice_content .flex-box > p:last-child,
    .ebill-receipt-paper #invoice_content .table-info > p:last-child,
    .ebill-receipt-paper #invoice_content .f-right {
        flex: 1 1 auto !important;
        min-width: 0 !important;
        text-align: right !important;
        width: auto !important;
        color: #111 !important;
        word-break: break-word !important;
        overflow-wrap: anywhere !important;
        font-size: inherit !important;
        font-weight: 400 !important;
    }
    .ebill-receipt-paper #invoice_content .flex-box > p:last-child,
    .ebill-receipt-paper #invoice_content .table-info > p:last-child {
        flex: 0 0 auto !important;
        white-space: nowrap !important;
        font-variant-numeric: tabular-nums !important;
    }
    .ebill-receipt-paper #invoice_content .flex-box.total-row {
        border-top: 1.5px solid #111 !important;
        border-bottom: 1.5px solid #111 !important;
        margin: 4px 0 !important;
        padding: 5px 0 !important;
    }
    .ebill-receipt-paper #invoice_content .flex-box.total-row > p {
        font-size: 13px !important;
        font-weight: 800 !important;
        color: #000 !important;
    }
    .ebill-receipt-paper #invoice_content .width-50 {
        width: auto !important;
    }

    /* Product table */
    .ebill-receipt-paper #invoice_content table {
        width: 100% !important;
        border-collapse: collapse !important;
        font-size: 10px !important;
        margin: 8px 0 4px !important;
        table-layout: fixed !important;
    }
    .ebill-receipt-paper #invoice_content thead th {
        border-top: 1.5px solid #111 !important;
        border-bottom: 1.5px solid #111 !important;
        padding: 4px 1px !important;
        font-weight: 700 !important;
        text-align: left !important;
        color: #111 !important;
        font-size: 8.5px !important;
        text-transform: uppercase !important;
        letter-spacing: 0 !important;
        white-space: nowrap !important;
        overflow: visible !important;
        text-overflow: clip !important;
    }
    .ebill-receipt-paper #invoice_content thead th:nth-child(1),
    .ebill-receipt-paper #invoice_content tbody td:nth-child(1) { width: 4mm !important; text-align: left !important; }
    .ebill-receipt-paper #invoice_content thead th:nth-child(2),
    .ebill-receipt-paper #invoice_content tbody td:nth-child(2) { width: auto !important; text-align: left !important; }
    .ebill-receipt-paper #invoice_content thead th:nth-child(3),
    .ebill-receipt-paper #invoice_content tbody td:nth-child(3),
    .ebill-receipt-paper #invoice_content thead th:nth-child(4),
    .ebill-receipt-paper #invoice_content tbody td:nth-child(4) { width: 14mm !important; text-align: right !important; }
    .ebill-receipt-paper #invoice_content thead th:nth-child(5),
    .ebill-receipt-paper #invoice_content tbody td:nth-child(5) { width: 15mm !important; text-align: right !important; }
    .ebill-receipt-paper #invoice_content thead th.text-right,
    .ebill-receipt-paper #invoice_content thead th.quantity,
    .ebill-receipt-paper #invoice_content thead th.unit_price,
    .ebill-receipt-paper #invoice_content thead th.price { text-align: right !important; }

    .ebill-receipt-paper #invoice_content tbody td {
        padding: 4px 1px !important;
        border-bottom: 1px dotted #bbb !important;
        vertical-align: top !important;
        color: #111 !important;
        word-wrap: break-word !important;
        overflow-wrap: anywhere !important;
        font-weight: 400 !important;
        font-size: 10px !important;
    }
    .ebill-receipt-paper #invoice_content tbody td.quantity,
    .ebill-receipt-paper #invoice_content tbody td:nth-child(3) {
        font-size: 9px !important;
        white-space: normal !important;
        text-align: right !important;
    }
    .ebill-receipt-paper #invoice_content tbody td.unit_price,
    .ebill-receipt-paper #invoice_content tbody td.price,
    .ebill-receipt-paper #invoice_content tbody td.text-right {
        text-align: right !important;
        font-variant-numeric: tabular-nums !important;
    }

    .ebill-receipt-paper #invoice_content hr,
    .ebill-receipt-paper #invoice_content .line,
    .ebill-receipt-paper #invoice_content .border-bottom {
        border: none !important;
        border-top: 1px solid #999 !important;
        margin: 6px 0 !important;
    }

    /* Software / copyright footer — bold */
    .ebill-receipt-paper #invoice_content .software-footer,
    .ebill-receipt-paper #invoice_content .software-footer p,
    .ebill-receipt-paper #invoice_content .ticket > .software-footer,
    .ebill-receipt-paper #invoice_content .ticket > *:last-child.software-footer {
        margin-top: 8px !important;
        border-top: 1px solid #ddd !important;
        padding-top: 5px !important;
        text-align: center !important;
        font-size: 9px !important;
        color: #111 !important;
        line-height: 1.4 !important;
        font-weight: 700 !important;
        word-break: keep-all !important;
        white-space: normal !important;
    }
    .ebill-receipt-paper #invoice_content .software-footer strong,
    .ebill-receipt-paper #invoice_content .software-footer p strong {
        color: #000 !important;
        font-weight: 800 !important;
        font-size: 9px !important;
    }

    .ebill-receipt-paper #invoice_content .discount-highlight {
        margin: 6px 0 !important;
        padding: 5px !important;
        border: 1.5px solid #111 !important;
        text-align: center !important;
        font-size: 10px !important;
        font-weight: 700 !important;
        color: #111 !important;
    }
    .ebill-receipt-paper #invoice_content .f-8 {
        font-size: 8px !important;
        font-weight: 400 !important;
    }
    .ebill-receipt-paper #invoice_content .center-block {
        display: block !important;
        margin: 0 auto !important;
        max-width: 100% !important;
    }

    /* Social icons in footer */
    .ebill-social {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 16px;
        margin: 10px 0 6px;
    }
    .ebill-social a {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 16px;
        text-decoration: none;
        transition: transform .2s;
    }
    .ebill-social a:hover { transform: translateY(-2px); }
    .ebill-social .fb { background: #1877f2; }
    .ebill-social .ig { background: linear-gradient(45deg, #f09433, #e6683c, #dc2743, #cc2366, #bc1888); }
    .ebill-social .web { background: #0f172a; }

    /* Page footer */
    .ebill-footer {
        margin-top: 12px;
        text-align: center;
        font-size: 10px;
        font-weight: 700;
        color: #334155;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }
    .ebill-footer strong {
        font-weight: 800;
        color: #0f172a;
    }

    /* Match the high-contrast Slim thermal receipt in browser/e-bill preview */
    .ebill-receipt-paper #invoice_content,
    .ebill-receipt-paper #invoice_content p,
    .ebill-receipt-paper #invoice_content span,
    .ebill-receipt-paper #invoice_content td,
    .ebill-receipt-paper #invoice_content th,
    .ebill-receipt-paper #invoice_content div {
        color: #000 !important;
    }
    .ebill-receipt-paper #invoice_content .text-box p {
        color: #000 !important;
        font-weight: 500 !important;
    }
    .ebill-receipt-paper #invoice_content .textbox-info {
        color: #000 !important;
        font-weight: 600 !important;
        border-bottom-color: #555 !important;
    }
    .ebill-receipt-paper #invoice_content table thead th {
        color: #000 !important;
        font-weight: 900 !important;
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
    }
    .ebill-receipt-paper #invoice_content table tbody td {
        color: #000 !important;
        font-weight: 600 !important;
        border-bottom-color: #777 !important;
    }
    .ebill-receipt-paper #invoice_content .flex-box > p:last-child,
    .ebill-receipt-paper #invoice_content .table-info > p:last-child {
        color: #000 !important;
        font-weight: 800 !important;
        font-variant-numeric: tabular-nums;
    }
    .ebill-receipt-paper #invoice_content .total-row {
        border-top: 2px solid #000 !important;
        border-bottom: 2px solid #000 !important;
    }
    .ebill-receipt-paper #invoice_content .total-row > p {
        color: #000 !important;
        font-size: 14px !important;
        font-weight: 900 !important;
    }

    @media print {
        .ebill-page { background: #fff; padding: 0; }
        .ebill-actions,
        .ebill-social,
        .ebill-footer { display: none !important; }
        .ebill-receipt-paper {
            box-shadow: none;
            max-width: 80mm;
            width: 80mm;
            padding: 2mm;
        }
        .ebill-receipt-paper::before,
        .ebill-receipt-paper::after { display: none; }
    }
</style>

<div class="ebill-page">
    {{-- Action buttons --}}
    <div class="ebill-actions no-print">
        <button type="button" class="btn btn-share" id="share_invoice">
            <i class="fas fa-share-alt"></i> Share
        </button>
        @if(!empty($payment_link))
            <a href="{{$payment_link}}" class="btn btn-pay">
                <i class="fas fa-money-check-alt"></i> @lang('lang_v1.pay')
            </a>
        @endif
        @auth
            <a href="{{action([\App\Http\Controllers\SellController::class, 'index'])}}" class="btn btn-back">
                <i class="fas fa-backward"></i>
            </a>
        @endauth
    </div>

    {{-- 80mm Thermal Receipt --}}
    <div class="ebill-receipt-paper">
        <div id="invoice_content">
            {!! $receipt['html_content'] !!}
        </div>

        {{-- Social Media Icons --}}
        @php
            $hasSocial = !empty($ebill_settings['social_facebook']) || !empty($ebill_settings['social_instagram']) || !empty($ebill_settings['social_website']);
        @endphp
        @if($hasSocial)
            <div class="ebill-social no-print">
                @if(!empty($ebill_settings['social_facebook']))
                    <a href="{{ $ebill_settings['social_facebook'] }}" target="_blank" class="fb" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                @endif
                @if(!empty($ebill_settings['social_instagram']))
                    <a href="{{ $ebill_settings['social_instagram'] }}" target="_blank" class="ig" title="Instagram"><i class="fab fa-instagram"></i></a>
                @endif
                @if(!empty($ebill_settings['social_website']))
                    <a href="{{ $ebill_settings['social_website'] }}" target="_blank" class="web" title="Website"><i class="fas fa-globe"></i></a>
                @endif
            </div>
        @endif
    </div>

    <div class="ebill-footer no-print">
        Powered by <strong>QPOS</strong>
    </div>
</div>
@stop
@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Share button
        $(document).on('click', '#share_invoice', function(){
            var shareData = {
                title: document.title,
                text: 'View your bill from {{ $receipt["business_name"] ?? config("app.name", "QPOS") }}',
                url: window.location.href
            };
            if (navigator.share) {
                navigator.share(shareData).catch(function(err){
                    if (err.name !== 'AbortError') {
                        console.log('Share failed:', err);
                    }
                });
            } else {
                // Fallback: copy link to clipboard
                var tempInput = document.createElement('input');
                tempInput.value = window.location.href;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                alert('Link copied to clipboard!');
            }
        });
    });
</script>
@endsection