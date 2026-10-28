<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content vspos-pos-modal-content">
    <div class="modal-header vspos-pos-modal-header mini_print">
      <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.register_details' ) ( {{ $register_details->open_time ? \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') : '-' }} -  {{ $close_time ? \Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('jS M, Y h:i A') : '-' }} )</h3>
    </div>

    <div class="modal-body vspos-pos-modal-body">
      @include('cash_register.payment_details')
      <hr>
      @if(!empty($register_details->denominations))
        @php
          $total = 0;
        @endphp
        <div class="row">
          <div class="col-md-8 col-sm-12">
            <h3>@lang( 'lang_v1.cash_denominations' )</h3>
            <table class="table table-slim">
              <thead>
                <tr>
                  <th width="20%" class="text-right">@lang('lang_v1.denomination')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-center">@lang('lang_v1.count')</th>
                  <th width="20%">&nbsp;</th>
                  <th width="20%" class="text-left">@lang('sale.subtotal')</th>
                </tr>
              </thead>
              <tbody>
                @foreach($register_details->denominations as $key => $value)
                <tr>
                  <td class="text-right">{{$key}}</td>
                  <td class="text-center">X</td>
                  <td class="text-center">{{$value ?? 0}}</td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    @format_currency($key * ($value ?? 0))
                  </td>
                </tr>
                @php $total += ((float) $key) * ((float) ($value ?? 0)); @endphp
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center">@lang('sale.total')</th>
                  <td>@format_currency($total)</td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      @endif
      
      <div class="row">
        <div class="col-xs-6">
          <b>@lang('report.user'):</b> {{ $register_details->user_name}}<br>
          <b>@lang('business.email'):</b> {{ $register_details->email}}<br>
          <b>@lang('business.business_location'):</b> {{ $register_details->location_name}}<br>
        </div>
        @if(!empty($register_details->closing_note))
          <div class="col-xs-6">
            <strong>@lang('cash_register.closing_note'):</strong><br>
            {{$register_details->closing_note}}
          </div>
        @endif
      </div>
    </div>

    <div class="modal-footer vspos-pos-modal-footer">
  <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print print-mini-button" 
          aria-label="Print">
      <i class="fa fa-print"></i> @lang('messages.print_mini')
  </button>
      <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white no-print" 
        aria-label="Print" 
          onclick="$(this).closest('div.modal').printThis();">
        <i class="fa fa-print"></i> @lang( 'messages.print_detailed' )
      </button>

      <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white no-print" 
        data-dismiss="modal">@lang( 'messages.cancel' )
      </button>
    </div>

  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->
<style type="text/css">
  @media print {
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        overflow: visible!important;
    }
}
</style>
<script>
  $(document).ready(function () {
      $(document).on('click', '.print-mini-button', function () {
          var businessName = @json(session('business.name'));
          var now = @json(\Carbon::now()->format('d M Y, h:i A'));
          var openTime = @json($register_details->open_time ? \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('d M Y, h:i A') : '-');
          var closeTime = @json($close_time ? \Carbon::createFromFormat('Y-m-d H:i:s', $close_time)->format('d M Y, h:i A') : '-');
          var user = @json($register_details->user_name ?? '');
          var location = @json($register_details->location_name ?? '');

          // Build clean 80mm receipt HTML
          var html = '<!DOCTYPE html><html><head><meta charset="utf-8">'
              + '<style>'
              + 'body{font-family:"Courier New",Courier,monospace;font-size:11px;color:#000;width:80mm;margin:0 auto;padding:6px;box-sizing:border-box;}'
              + 'h2{font-size:13px;font-weight:900;text-align:center;margin:0 0 2px;text-transform:uppercase;}'
              + 'p.sub{font-size:10px;text-align:center;margin:0 0 1px;}'
              + 'hr{border:none;border-top:1px dashed #555;margin:5px 0;}'
              + 'hr.solid{border-top:1px solid #000;}'
              + 'h4{font-size:11px;font-weight:900;border-bottom:1px solid #000;padding-bottom:2px;margin:7px 0 4px;text-transform:uppercase;letter-spacing:.3px;}'
              + 'table{width:100%;border-collapse:collapse;font-size:11px;}'
              + 'td{padding:2px 0;vertical-align:top;}'
              + 'td:last-child{text-align:right;font-weight:600;}'
              + '.bold td{font-weight:900;border-top:1px solid #000;padding-top:3px;}'
              + '.footer{text-align:center;font-size:9px;color:#555;margin-top:8px;border-top:1px dashed #555;padding-top:5px;}'
              + '@media print{body{width:80mm;}}'
              + '</style></head><body>';

          html += '<h2>' + businessName + '</h2>';
          html += '<p class="sub"><strong>REGISTER DETAILS REPORT</strong></p>';
          html += '<p class="sub">Opened: ' + openTime + '</p>';
          html += '<p class="sub">Closed: ' + closeTime + '</p>';
          html += '<p class="sub">Cashier: ' + user + ' | ' + location + '</p>';
          html += '<hr class="solid">';

          // Extract sections from mini_print
          document.querySelectorAll('.mini_print h4').forEach(function(h4) {
              html += '<h4>' + h4.textContent.trim() + '</h4>';
              var tbl = h4.nextElementSibling;
              while (tbl && tbl.tagName === 'HR') { tbl = tbl.nextElementSibling; }
              if (tbl && (tbl.tagName === 'TABLE' || tbl.querySelector('table'))) {
                  var targetTable = tbl.tagName === 'TABLE' ? tbl : tbl.querySelector('table');
                  if (targetTable) {
                      html += '<table>';
                      targetTable.querySelectorAll('tr').forEach(function(tr) {
                          var cells = tr.querySelectorAll('td, th');
                          if (cells.length >= 2) {
                              var cls = tr.className.indexOf('success') >= 0 || tr.className.indexOf('warning') >= 0 ? ' class="bold"' : '';
                              html += '<tr' + cls + '>';
                              html += '<td>' + cells[0].textContent.trim() + '</td>';
                              html += '<td>' + cells[cells.length-1].textContent.trim() + '</td>';
                              html += '</tr>';
                          }
                      });
                      html += '</table>';
                  }
              }
              html += '<hr>';
          });

          html += '<div class="footer">{{ config('app.brand_footer') }}</div>';
          html += '</body></html>';

          var w = window.open('', '_blank', 'width=350,height=600,scrollbars=yes');
          w.document.write(html);
          w.document.close();
          w.focus();
          setTimeout(function(){ w.print(); }, 400);
      });
  });
</script>