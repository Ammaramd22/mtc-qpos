<style>
/* Label | amount rows — prevent amount/label overlap in POS dark modal */
.vspos-register-kv-table {
    width: 100% !important;
    table-layout: fixed !important;
    border-collapse: collapse !important;
}
.vspos-register-kv-table > tbody > tr,
.vspos-register-kv-table > tr {
    display: flex !important;
    width: 100% !important;
    align-items: baseline;
    justify-content: space-between;
    gap: 12px;
}
.vspos-register-kv-table > tbody > tr > td,
.vspos-register-kv-table > tbody > tr > th,
.vspos-register-kv-table > tr > td,
.vspos-register-kv-table > tr > th {
    display: block !important;
    float: none !important;
    position: static !important;
    left: auto !important;
    right: auto !important;
    width: auto !important;
    max-width: none !important;
}
.vspos-register-kv-table > tbody > tr > td:first-child,
.vspos-register-kv-table > tbody > tr > th:first-child,
.vspos-register-kv-table > tr > td:first-child,
.vspos-register-kv-table > tr > th:first-child {
    flex: 1 1 auto;
    min-width: 0;
    text-align: left !important;
    white-space: normal !important;
}
.vspos-register-kv-table > tbody > tr > td:last-child,
.vspos-register-kv-table > tbody > tr > th:last-child,
.vspos-register-kv-table > tr > td:last-child,
.vspos-register-kv-table > tr > th:last-child {
    flex: 0 0 auto;
    text-align: right !important;
    white-space: nowrap !important;
}
.vspos-register-kv-table .display_currency {
    float: none !important;
    position: static !important;
    display: inline !important;
    margin: 0 !important;
}

@media print {
    .modal-header, .modal-footer, .hidden-print, .hidden-print * { display: none !important; }
    .modal-dialog { width: 80mm !important; max-width: 80mm !important; margin: 0 auto !important; }
    .modal-content { border: none !important; box-shadow: none !important; }
    body, .mini_print { font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #000; width: 80mm; margin: 0 auto; }
    .mini_print table { width: 100%; border-collapse: collapse; font-size: 11px; }
    .mini_print table td, .mini_print table th { padding: 2px 4px; font-size: 11px; }
    .mini_print h4 { font-size: 12px; font-weight: 900; border-bottom: 1px solid #000 !important; padding-bottom: 3px; margin-bottom: 6px; margin-top: 8px; }
    .mini_print hr { border-top: 1px dashed #555; margin: 5px 0; }
    .mini_print .table-condensed > tbody > tr > td,
    .mini_print .table-condensed > tbody > tr > th,
    .mini_print .table-condensed > thead > tr > th { padding: 2px 4px !important; }
    .mini_print .success td { font-weight: 900; }
    .mini_print .warning td { font-weight: 900; }
    .cr-print-header { text-align: center; margin-bottom: 8px; }
    .cr-print-header h3 { font-size: 14px; font-weight: 900; margin: 0 0 2px; }
    .cr-print-header p { font-size: 10px; margin: 0; }
    .cr-print-footer { text-align: center; margin-top: 8px; border-top: 1px dashed #555; padding-top: 5px; font-size: 9px; color: #444; }
    /* Restore real table layout for print / Print Mini extraction */
    .vspos-register-kv-table > tbody > tr,
    .vspos-register-kv-table > tr {
        display: table-row !important;
    }
    .vspos-register-kv-table > tbody > tr > td,
    .vspos-register-kv-table > tbody > tr > th,
    .vspos-register-kv-table > tr > td,
    .vspos-register-kv-table > tr > th {
        display: table-cell !important;
    }
    .vspos-register-kv-table > tbody > tr > td:last-child,
    .vspos-register-kv-table > tr > td:last-child {
        text-align: right !important;
        white-space: nowrap !important;
    }
}
</style>
<div class="row mini_print">
  <div class="col-sm-12">
    <div class="cr-print-header hidden-screen">
      <h3>{{ session('business.name') }}</h3>
      <p>{{ \Carbon::now()->format('d M Y, h:i A') }}</p>
      <p><strong>REGISTER CLOSE REPORT</strong></p>
    </div>
  </div>
  <div class="col-sm-12">

    {{-- SECTION 1: CASH DRAWER SUMMARY --}}
    <h4 class="vspos-register-section-title" style="margin-top:0; margin-bottom:10px;">
      @lang('cash_register.cash_drawer_summary')
    </h4>
    <table class="table table-condensed vspos-register-kv-table">
      <tr>
        <td>@lang('cash_register.opening_balance'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->cash_in_hand }}</span></td>
      </tr>
      <tr>
        <td>(+) @lang('cash_register.cash_received_sales'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</span></td>
      </tr>
      <tr>
        <td>(+) @lang('cash_register.cash_received_due_payment'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_advance_payment }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('cash_register.cash_refunds'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('cash_register.cash_expenses'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_expense }}</span></td>
      </tr>
      <tr>
        <td>(+) @lang('lang_v1.cash_in'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_in ?? 0 }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('lang_v1.cash_out'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_out ?? 0 }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('lang_v1.purchases_paid_cash'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ ($register_details->total_cash_purchase_crt ?? 0) > 0 ? $register_details->total_cash_purchase_crt : ($register_details->total_cash_purchase_paid ?? 0) }}</span></td>
      </tr>
      <tr class="success" style="font-weight:bold;">
        <td><b>@lang('cash_register.expected_cash_in_drawer'):</b></td>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{
            $register_details->cash_in_hand
            + $register_details->total_cash
            + $register_details->total_cash_advance_payment
            - $register_details->total_cash_refund
            - $register_details->total_cash_expense
            + ($register_details->total_cash_in ?? 0)
            - ($register_details->total_cash_out ?? 0)
            - ((($register_details->total_cash_purchase_crt ?? 0) > 0) ? $register_details->total_cash_purchase_crt : ($register_details->total_cash_purchase_paid ?? 0))
          }}</span></b>
        </td>
      </tr>
    </table>

    <hr>

    {{-- SECTION 2: SALES SUMMARY --}}
    <h4 class="vspos-register-section-title" style="margin-bottom:10px;">
      @lang('cash_register.sales_summary')
    </h4>
    <table class="table table-condensed vspos-register-kv-table">
      <tr>
        <td>@lang('cash_register.gross_sales'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('cash_register.total_refund'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></td>
      </tr>
      <tr class="success" style="font-weight:bold;">
        <td><b>@lang('cash_register.net_sales'):</b></td>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales - $register_details->total_refund }}</span></b>
        </td>
      </tr>
    </table>

    <hr>

    {{-- SECTION 3: PAYMENTS COLLECTED (by method) with Due Payments column --}}
    <h4 class="vspos-register-section-title" style="margin-bottom:10px;">
      @lang('cash_register.payments_collected')
    </h4>
    @php
      $has_any_payment = $register_details->total_sale > 0 || $register_details->total_advance_payment > 0 || $register_details->total_expense > 0;
    @endphp
    @if($has_any_payment)
    <table class="table table-condensed">
      <tr>
        <th>@lang('lang_v1.payment_method')</th>
        <th>@lang('sale.sale')</th>
        <th>@lang('cash_register.due_payments_collected')</th>
        <th>@lang('lang_v1.expense')</th>
      </tr>
      @if($register_details->total_cash > 0 || $register_details->total_cash_advance_payment > 0 || $register_details->total_cash_expense > 0)
      <tr>
        <td>@lang('cash_register.cash_payment'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_advance_payment }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_expense }}</span></td>
      </tr>
      @endif
      @if($register_details->total_cheque > 0 || $register_details->total_cheque_advance_payment > 0 || $register_details->total_cheque_expense > 0)
      <tr>
        <td>@lang('cash_register.checque_payment'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_advance_payment }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_expense }}</span></td>
      </tr>
      @endif
      @if($register_details->total_card > 0 || $register_details->total_card_advance_payment > 0 || $register_details->total_card_expense > 0)
      <tr>
        <td>@lang('cash_register.card_payment'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_advance_payment }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_expense }}</span></td>
      </tr>
      @endif
      @if($register_details->total_bank_transfer > 0 || $register_details->total_bank_transfer_advance_payment > 0 || $register_details->total_bank_transfer_expense > 0)
      <tr>
        <td>@lang('cash_register.bank_transfer'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_advance_payment }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_expense }}</span></td>
      </tr>
      @endif
      @if($register_details->total_advance > 0 || $register_details->total_advance_expense > 0)
      <tr>
        <td>@lang('lang_v1.advance_payment'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance }}</span></td>
        <td>--</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance_expense }}</span></td>
      </tr>
      @endif
      @if(array_key_exists('custom_pay_1', $payment_types) && ($register_details->total_custom_pay_1 > 0 || $register_details->total_custom_pay_1_advance_payment > 0 || $register_details->total_custom_pay_1_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_1']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_2', $payment_types) && ($register_details->total_custom_pay_2 > 0 || $register_details->total_custom_pay_2_advance_payment > 0 || $register_details->total_custom_pay_2_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_2']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_3', $payment_types) && ($register_details->total_custom_pay_3 > 0 || $register_details->total_custom_pay_3_advance_payment > 0 || $register_details->total_custom_pay_3_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_3']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_4', $payment_types) && ($register_details->total_custom_pay_4 > 0 || $register_details->total_custom_pay_4_advance_payment > 0 || $register_details->total_custom_pay_4_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_4']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_4 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_4_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_4_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_5', $payment_types) && ($register_details->total_custom_pay_5 > 0 || $register_details->total_custom_pay_5_advance_payment > 0 || $register_details->total_custom_pay_5_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_5']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_5 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_5_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_5_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_6', $payment_types) && ($register_details->total_custom_pay_6 > 0 || $register_details->total_custom_pay_6_advance_payment > 0 || $register_details->total_custom_pay_6_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_6']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_6 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_6_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_6_expense }}</span></td>
        </tr>
      @endif
      @if(array_key_exists('custom_pay_7', $payment_types) && ($register_details->total_custom_pay_7 > 0 || $register_details->total_custom_pay_7_advance_payment > 0 || $register_details->total_custom_pay_7_expense > 0))
        <tr>
          <td>{{$payment_types['custom_pay_7']}}:</td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_7 }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_7_advance_payment }}</span></td>
          <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_7_expense }}</span></td>
        </tr>
      @endif
      @if($register_details->total_other > 0 || $register_details->total_other_advance_payment > 0 || $register_details->total_other_expense > 0)
      <tr>
        <td>@lang('cash_register.other_payments'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_advance_payment }}</span></td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_expense }}</span></td>
      </tr>
      @endif
      {{-- Totals row aligned under respective columns --}}
      <tr style="font-weight:bold; border-top:2px solid #333;">
        <td><b>@lang('receipt.total')</b></td>
        <td class="success" style="border:1px solid #d6e9c6;">
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale }}</span></b>
        </td>
        <td class="info" style="border:1px solid #bce8f1;">
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_advance_payment }}</span></b>
        </td>
        <td class="danger" style="border:1px solid #ebccd1;">
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_expense }}</span></b>
        </td>
      </tr>
      {{-- Grand Total Collected row --}}
      <tr class="success" style="font-weight:bold;">
        <td colspan="2"><b>@lang('cash_register.grand_total_collected'):</b></td>
        <td colspan="2">
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale + $register_details->total_advance_payment }}</span></b>
        </td>
      </tr>
    </table>
    @else
    <p class="text-muted text-center">@lang('lang_v1.no_data')</p>
    @endif

    {{-- Refund breakdown --}}
    @if($register_details->total_refund > 0)
    <table class="table table-condensed vspos-register-kv-table">
      <tr class="danger">
        <th>@lang('cash_register.total_refund')</th>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_refund }}</span></b><br>
          <small>
          @if($register_details->total_cash_refund != 0)
            Cash: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_refund }}</span><br>
          @endif
          @if($register_details->total_cheque_refund != 0)
            Cheque: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cheque_refund }}</span><br>
          @endif
          @if($register_details->total_card_refund != 0)
            Card: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_card_refund }}</span><br>
          @endif
          @if($register_details->total_bank_transfer_refund != 0)
            Bank Transfer: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_bank_transfer_refund }}</span><br>
          @endif
          @if(array_key_exists('custom_pay_1', $payment_types) && $register_details->total_custom_pay_1_refund != 0)
            {{$payment_types['custom_pay_1']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_1_refund }}</span><br>
          @endif
          @if(array_key_exists('custom_pay_2', $payment_types) && $register_details->total_custom_pay_2_refund != 0)
            {{$payment_types['custom_pay_2']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_2_refund }}</span><br>
          @endif
          @if(array_key_exists('custom_pay_3', $payment_types) && $register_details->total_custom_pay_3_refund != 0)
            {{$payment_types['custom_pay_3']}}: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_custom_pay_3_refund }}</span><br>
          @endif
          @if($register_details->total_other_refund != 0)
            Other: <span class="display_currency" data-currency_symbol="true">{{ $register_details->total_other_refund }}</span>
          @endif
          </small>
        </td>
      </tr>
    </table>
    @endif

    <hr>

    {{-- SECTION 4: OUTSTANDING RECEIVABLES --}}
    <h4 class="vspos-register-section-title" style="margin-bottom:10px;">
      @lang('cash_register.outstanding_receivables')
    </h4>
    <table class="table table-condensed vspos-register-kv-table">
      <tr>
        <td>@lang('cash_register.gross_sales'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('cash_register.total_collected'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_sale }}</span></td>
      </tr>
      <tr>
        <td>(-) @lang('cash_register.due_applied_to_register'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $due_applied_to_register ?? 0 }}</span></td>
      </tr>
      <tr class="warning" style="font-weight:bold;">
        <td><b>@lang('cash_register.pending_from_customers'):</b></td>
        <td>
          <b><span class="display_currency" data-currency_symbol="true">{{ $details['transaction_details']->total_sales - $register_details->total_sale - ($due_applied_to_register ?? 0) }}</span></b>
        </td>
      </tr>
    </table>

    <hr>

    {{-- SECTION 5: PURCHASES --}}
    @if(!empty($details['purchase_details']) && ($details['purchase_details']->purchase_count ?? 0) > 0)
    <h4 class="vspos-register-section-title" style="margin-bottom:10px;">
      <i class="fas fa-shopping-cart"></i> @lang('lang_v1.purchases')
    </h4>
    <table class="table table-condensed vspos-register-kv-table">
      <tr>
        <td>@lang('lang_v1.total_purchases') ({{ $details['purchase_details']->purchase_count ?? 0 }} @lang('lang_v1.invoices')):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $details['purchase_details']->total_purchases ?? 0 }}</span></td>
      </tr>
      @if(($details['purchase_details']->total_purchase_returns ?? 0) > 0)
      <tr>
        <td>(-) @lang('lang_v1.purchase_return'):</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $details['purchase_details']->total_purchase_returns }}</span></td>
      </tr>
      @endif
      <tr class="warning" style="font-weight:bold;">
        <td><b>@lang('lang_v1.net_purchases'):</b></td>
        <td><b><span class="display_currency" data-currency_symbol="true">{{ ($details['purchase_details']->total_purchases ?? 0) - ($details['purchase_details']->total_purchase_returns ?? 0) }}</span></b></td>
      </tr>
    </table>
    <hr>
    @endif

    {{-- SECTION 6: CASH IN / OUT LOG --}}
    @if(!empty($details['cash_in_out_log']) && $details['cash_in_out_log']->count() > 0)
    <h4 class="vspos-register-section-title" style="margin-bottom:10px;">
      <i class="fas fa-exchange-alt"></i> @lang('lang_v1.cash_in_out_log')
    </h4>
    <table class="table table-condensed">
      <tr>
        <th>@lang('lang_v1.type')</th>
        <th>@lang('sale.amount')</th>
        <th>@lang('lang_v1.note')</th>
        <th>@lang('messages.time')</th>
      </tr>
      @foreach($details['cash_in_out_log'] as $log)
      <tr class="{{ $log->transaction_type === 'cash_in' ? 'success' : 'danger' }}">
        <td>
          @if($log->transaction_type === 'cash_in')
            <span style="color:#15803d;"><i class="fas fa-plus-circle"></i> @lang('lang_v1.cash_in')</span>
          @else
            <span style="color:#dc2626;"><i class="fas fa-minus-circle"></i> @lang('lang_v1.cash_out')</span>
          @endif
        </td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $log->amount }}</span></td>
        <td>{{ $log->note ?? '—' }}</td>
        <td style="font-size:11px;">{{ \Carbon::parse($log->created_at)->format('h:i A') }}</td>
      </tr>
      @endforeach
      <tr style="font-weight:bold;border-top:2px solid #333;">
        <td>@lang('lang_v1.cash_in') Total:</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_in ?? 0 }}</span></td>
        <td colspan="2"></td>
      </tr>
      <tr style="font-weight:bold;">
        <td>@lang('lang_v1.cash_out') Total:</td>
        <td><span class="display_currency" data-currency_symbol="true">{{ $register_details->total_cash_out ?? 0 }}</span></td>
        <td colspan="2"></td>
      </tr>
    </table>
    <hr>
    @endif

  </div>
</div>

@include('cash_register.register_product_details')
