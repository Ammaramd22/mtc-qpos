@php
    $business = (!empty($transaction) && !empty($transaction->business)) ? $transaction->business : null;
    $business_name = !empty($business->name) ? $business->name : session('business.name');
    $location = (!empty($transaction) && !empty($transaction->location)) ? $transaction->location : null;
    $payment_method_label = $payment_types[$single_payment_line->method] ?? ($single_payment_line->method ?? '');
    $paid_on_label = @format_datetime($single_payment_line->paid_on);
@endphp

<style>
    .vspos-pay-modal .modal-dialog {
        max-width: 420px;
        width: 95%;
    }
    .vspos-pay-receipt {
        font-family: 'Courier New', Courier, monospace;
        color: #e5e5e5;
        font-size: 13px;
        line-height: 1.4;
    }
    .vspos-pay-receipt .pr-head {
        text-align: center;
        margin-bottom: 10px;
        padding-bottom: 8px;
        border-bottom: 1px dashed #444;
    }
    .vspos-pay-receipt .pr-head .pr-biz {
        font-size: 16px;
        font-weight: 700;
        color: #fafafa;
        text-transform: uppercase;
        letter-spacing: 0.4px;
        margin: 0 0 4px;
    }
    .vspos-pay-receipt .pr-head .pr-sub {
        margin: 0;
        font-size: 11px;
        color: #a3a3a3;
    }
    .vspos-pay-receipt .pr-title {
        text-align: center;
        font-weight: 700;
        font-size: 13px;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin: 8px 0 10px;
        color: #f5c518;
    }
    .vspos-pay-receipt .pr-row {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        padding: 4px 0;
        border-bottom: 1px dotted #333;
        font-size: 12px;
    }
    .vspos-pay-receipt .pr-row .pr-label {
        color: #a3a3a3;
        flex: 0 0 auto;
    }
    .vspos-pay-receipt .pr-row .pr-value {
        text-align: right;
        color: #f5f5f5;
        word-break: break-word;
        flex: 1 1 auto;
    }
    .vspos-pay-receipt .pr-amount {
        text-align: center;
        margin: 12px 0;
        padding: 10px 0;
        border-top: 1px dashed #444;
        border-bottom: 1px dashed #444;
    }
    .vspos-pay-receipt .pr-amount .pr-amt-label {
        display: block;
        font-size: 11px;
        color: #a3a3a3;
        margin-bottom: 2px;
    }
    .vspos-pay-receipt .pr-amount .pr-amt-value {
        font-size: 22px;
        font-weight: 700;
        color: #fafafa;
    }
    .vspos-pay-receipt .pr-block {
        margin-top: 8px;
        font-size: 12px;
    }
    .vspos-pay-receipt .pr-block strong {
        color: #f5f5f5;
    }
    .vspos-pay-receipt .pr-foot {
        text-align: center;
        margin-top: 12px;
        padding-top: 8px;
        border-top: 1px dashed #444;
        font-size: 10px;
        color: #737373;
    }
</style>

<div class="modal-dialog vspos-pay-modal" role="document">
  <div class="modal-content">
    <div class="modal-header">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h4 class="modal-title no-print">
        @lang( 'lang_v1.view_payment' )
        @if(!empty($single_payment_line->payment_ref_no))
          ( @lang('purchase.ref_no'): {{ $single_payment_line->payment_ref_no }} )
        @endif
      </h4>
    </div>
    <div class="modal-body">
      <div id="payment_receipt_80mm" class="vspos-pay-receipt">
        <div class="pr-head">
          <p class="pr-biz">{{ $business_name }}</p>
          @if(!empty($location))
            <p class="pr-sub">{{ $location->name }}</p>
            @if(!empty($location->landmark))
              <p class="pr-sub">{{ $location->landmark }}</p>
            @endif
            @if(!empty($location->city) || !empty($location->state) || !empty($location->country))
              <p class="pr-sub">{{ implode(', ', array_filter([$location->city, $location->state, $location->country])) }}</p>
            @endif
            @if(!empty($location->mobile))
              <p class="pr-sub">@lang('contact.mobile'): {{ $location->mobile }}</p>
            @endif
          @endif
          @if(!empty($business->tax_number_1))
            <p class="pr-sub">{{ $business->tax_label_1 }}: {{ $business->tax_number_1 }}</p>
          @endif
          @if(!empty($business->tax_number_2))
            <p class="pr-sub">{{ $business->tax_label_2 }}: {{ $business->tax_number_2 }}</p>
          @endif
        </div>

        <div class="pr-title">@lang('lang_v1.payment_receipt')</div>

        @if(!empty($transaction))
          @if(in_array($transaction->type, ['purchase', 'purchase_return']) && !empty($transaction->contact))
            <div class="pr-row">
              <span class="pr-label">@lang('purchase.supplier')</span>
              <span class="pr-value">{{ trim(($transaction->contact->supplier_business_name ?? '') . ' ' . ($transaction->contact->name ?? '')) }}</span>
            </div>
          @elseif($transaction->type != 'payroll' && !empty($transaction->contact))
            <div class="pr-row">
              <span class="pr-label">@lang('contact.customer')</span>
              <span class="pr-value">{{ $transaction->contact->name ?? '' }}</span>
            </div>
          @elseif(!empty($transaction->transaction_for))
            <div class="pr-row">
              <span class="pr-label">@lang('essentials::lang.payroll_for')</span>
              <span class="pr-value">{{ $transaction->transaction_for->user_full_name }}</span>
            </div>
          @endif

          @if(!empty($transaction->invoice_no))
            <div class="pr-row">
              <span class="pr-label">@lang('sale.invoice_no')</span>
              <span class="pr-value">{{ $transaction->invoice_no }}</span>
            </div>
          @elseif(!empty($transaction->ref_no))
            <div class="pr-row">
              <span class="pr-label">@lang('purchase.ref_no')</span>
              <span class="pr-value">{{ $transaction->ref_no }}</span>
            </div>
          @endif
        @endif

        <div class="pr-row">
          <span class="pr-label">@lang('purchase.ref_no')</span>
          <span class="pr-value">{{ !empty($single_payment_line->payment_ref_no) ? $single_payment_line->payment_ref_no : '--' }}</span>
        </div>
        <div class="pr-row">
          <span class="pr-label">@lang('lang_v1.paid_on')</span>
          <span class="pr-value">{{ $paid_on_label }}</span>
        </div>
        <div class="pr-row">
          <span class="pr-label">@lang('lang_v1.payment_method')</span>
          <span class="pr-value">{{ $payment_method_label }}</span>
        </div>

        @if($single_payment_line->method == 'card')
          @if(!empty($single_payment_line->card_holder_name))
            <div class="pr-row">
              <span class="pr-label">@lang('lang_v1.card_holder_name')</span>
              <span class="pr-value">{{ $single_payment_line->card_holder_name }}</span>
            </div>
          @endif
          @if(!empty($single_payment_line->card_number))
            <div class="pr-row">
              <span class="pr-label">@lang('lang_v1.card_number')</span>
              <span class="pr-value">{{ $single_payment_line->card_number }}</span>
            </div>
          @endif
          @if(!empty($single_payment_line->card_transaction_number))
            <div class="pr-row">
              <span class="pr-label">@lang('lang_v1.card_transaction_number')</span>
              <span class="pr-value">{{ $single_payment_line->card_transaction_number }}</span>
            </div>
          @endif
        @elseif($single_payment_line->method == 'cheque' && !empty($single_payment_line->cheque_number))
          <div class="pr-row">
            <span class="pr-label">@lang('lang_v1.cheque_number')</span>
            <span class="pr-value">{{ $single_payment_line->cheque_number }}</span>
          </div>
        @elseif(in_array($single_payment_line->method, ['custom_pay_1', 'custom_pay_2', 'custom_pay_3']) && !empty($single_payment_line->transaction_no))
          <div class="pr-row">
            <span class="pr-label">@lang('lang_v1.transaction_number')</span>
            <span class="pr-value">{{ $single_payment_line->transaction_no }}</span>
          </div>
        @endif

        <div class="pr-amount">
          <span class="pr-amt-label">@lang('purchase.amount')</span>
          <span class="pr-amt-value">
            <span class="display_currency" data-currency_symbol="true">{{ $single_payment_line->amount }}</span>
          </span>
        </div>

        @if(!empty($single_payment_line->note))
          <div class="pr-block">
            <strong>@lang('purchase.payment_note'):</strong>
            {{ $single_payment_line->note }}
          </div>
        @endif

        <div class="pr-foot">
          {{ config('app.brand_footer', 'Thank you') }}
        </div>
      </div>

      @if(!empty($single_payment_line->document_path))
        <div class="tw-mt-3 no-print">
          <a href="{{$single_payment_line->document_path}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent" download="{{$single_payment_line->document_name}}">
            <i class="fa fa-download"></i> {{__('purchase.download_document')}}
          </a>
        </div>
      @endif
    </div>
    <div class="modal-footer">
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" id="print_payment_receipt_80mm">
        <i class="fa fa-print"></i> @lang( 'messages.print' )
      </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" data-dismiss="modal">@lang( 'messages.close' )
      </button>
    </div>
  </div>
</div>

<script type="text/javascript">
(function () {
    $('#print_payment_receipt_80mm').off('click.pay80').on('click.pay80', function () {
        var src = document.getElementById('payment_receipt_80mm');
        if (!src) {
            return;
        }

        var clone = src.cloneNode(true);
        // Flatten display_currency spans to plain text for print window
        clone.querySelectorAll('.display_currency').forEach(function (el) {
            el.replaceWith(document.createTextNode(el.textContent.trim()));
        });

        var html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Payment Receipt</title>'
            + '<style>'
            + '@page{size:80mm auto;margin:0;}'
            + 'html,body{margin:0;padding:0;background:#fff;}'
            + 'body{font-family:"Courier New",Courier,monospace;font-size:11px;color:#000;width:80mm;max-width:80mm;margin:0 auto;padding:3mm 2.5mm 4mm;box-sizing:border-box;-webkit-print-color-adjust:exact;print-color-adjust:exact;}'
            + '.pr-head{text-align:center;margin-bottom:6px;padding-bottom:6px;border-bottom:1px dashed #000;}'
            + '.pr-biz{font-size:13px;font-weight:900;text-transform:uppercase;margin:0 0 2px;letter-spacing:.4px;}'
            + '.pr-sub{margin:0;font-size:10px;line-height:1.35;}'
            + '.pr-title{text-align:center;font-weight:900;font-size:12px;letter-spacing:1px;text-transform:uppercase;margin:6px 0 8px;}'
            + '.pr-row{display:flex;justify-content:space-between;gap:6px;padding:3px 0;border-bottom:1px dotted #999;font-size:11px;}'
            + '.pr-label{color:#333;flex:0 0 auto;}'
            + '.pr-value{text-align:right;font-weight:600;word-break:break-word;flex:1 1 auto;}'
            + '.pr-amount{text-align:center;margin:8px 0;padding:8px 0;border-top:1px dashed #000;border-bottom:1px dashed #000;}'
            + '.pr-amt-label{display:block;font-size:10px;margin-bottom:2px;}'
            + '.pr-amt-value{font-size:18px;font-weight:900;}'
            + '.pr-block{margin-top:6px;font-size:10px;}'
            + '.pr-foot{text-align:center;margin-top:8px;padding-top:6px;border-top:1px dashed #555;font-size:9px;color:#444;}'
            + '@media print{body{width:80mm;max-width:80mm;}}'
            + '</style></head><body>'
            + clone.innerHTML
            + '</body></html>';

        var w = window.open('', '_blank', 'width=360,height=640,scrollbars=yes');
        if (!w) {
            toastr.error('Please allow pop-ups to print the receipt');
            return;
        }
        w.document.open();
        w.document.write(html);
        w.document.close();
        w.focus();
        setTimeout(function () {
            w.print();
        }, 350);
    });
})();
</script>
