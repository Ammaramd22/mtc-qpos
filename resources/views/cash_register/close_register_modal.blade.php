<div class="modal-dialog modal-lg" role="document">
  <div class="modal-content vspos-pos-modal-content">
    {!! Form::open(['url' => action([\App\Http\Controllers\CashRegisterController::class, 'postCloseRegister']), 'method' => 'post' ]) !!}

    {!! Form::hidden('user_id', $register_details->user_id); !!}
    <div class="modal-header vspos-pos-modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <h3 class="modal-title">@lang( 'cash_register.current_register' ) ( {{ \Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('jS M, Y h:i A') }} - {{ \Carbon::now()->format('jS M, Y h:i A') }})</h3>
    </div>

    <div class="modal-body vspos-pos-modal-body">
      @if(auth()->user()->can('view_cash_register'))
        @include('cash_register.payment_details')
        <hr>
      @endif
      <div class="row">
      @if(auth()->user()->can('view_cash_register'))
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('closing_amount', __( 'cash_register.total_cash' ) . ':*') !!}
              {!! Form::text('closing_amount', @num_format($register_details->cash_in_hand + $register_details->total_cash - $register_details->total_cash_refund - $register_details->total_cash_expense), ['class' => 'form-control input_number', 'required', 'placeholder' => __( 'cash_register.total_cash' ) ]); !!}
          </div>
        </div>
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('total_card_slips', __( 'cash_register.total_card_slips' ) . ':*') !!} @show_tooltip(__('tooltip.total_card_slips'))
              {!! Form::number('total_card_slips', $register_details->total_card_slips, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_card_slips' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <div class="col-sm-4">
          <div class="form-group">
            {!! Form::label('total_cheques', __( 'cash_register.total_cheques' ) . ':*') !!} @show_tooltip(__('tooltip.total_cheques'))
              {!! Form::number('total_cheques', $register_details->total_cheques, ['class' => 'form-control', 'required', 'placeholder' => __( 'cash_register.total_cheques' ), 'min' => 0 ]); !!}
          </div>
        </div> 
        <hr>
        <div class="col-md-8 col-sm-12">
          <h3>@lang( 'lang_v1.cash_denominations' )</h3>
          @if(!empty($pos_settings['cash_denominations']))
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
                @foreach(explode(',', $pos_settings['cash_denominations']) as $dnm)
                <tr>
                  <td class="text-right">{{$dnm}}</td>
                  <td class="text-center" >X</td>
                  <td>{!! Form::number("denominations[$dnm]", null, ['class' => 'form-control cash_denomination input-sm', 'min' => 0, 'data-denomination' => $dnm, 'style' => 'width: 100px; margin:auto;' ]); !!}</td>
                  <td class="text-center">=</td>
                  <td class="text-left">
                    <span class="denomination_subtotal">0</span>
                  </td>
                </tr>
                @endforeach
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="4" class="text-center">@lang('sale.total')</th>
                  <td><span class="denomination_total">0</span></td>
                </tr>
              </tfoot>
            </table>
          @else
            <p class="help-block">@lang('lang_v1.denomination_add_help_text')</p>
          @endif
        </div>
      @endif
        <hr>

        <div class="col-sm-12">
          <div class="form-group">
            {!! Form::label('closing_note', __( 'cash_register.closing_note' ) . ':') !!}
              {!! Form::textarea('closing_note', null, ['class' => 'form-control', 'placeholder' => __( 'cash_register.closing_note' ), 'rows' => 3 ]); !!}
          </div>
        </div>
      </div> 

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
      <div style="display:flex;align-items:center;justify-content:space-between;width:100%;flex-wrap:wrap;gap:8px;">
        <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
          {{-- Print Report --}}
          <button type="button" class="tw-dw-btn tw-dw-btn-accent tw-text-white" id="cr_print_report_btn">
            <i class="fas fa-print"></i> @lang('messages.print')
          </button>
          {{-- Email to owner --}}
          <label style="display:flex;align-items:center;gap:6px;margin:0;font-size:13px;font-weight:600;cursor:pointer;">
            {!! Form::hidden('send_report_email', 0) !!}
            {!! Form::checkbox('send_report_email', 1, false, ['id' => 'send_report_email', 'class' => 'input-icheck']) !!}
            <i class="fas fa-envelope" style="color:#6366f1;"></i>
            @lang('lang_v1.send_report_email')
          </label>
        </div>
        <div style="display:flex;gap:8px;">
          <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang( 'messages.cancel' )</button>
          <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang( 'cash_register.close_register' )</button>
        </div>
      </div>
    </div>
    {!! Form::close() !!}
    <script>
    document.getElementById('cr_print_report_btn').addEventListener('click', function() {
        var businessName = @json(session('business.name'));
        var now = @json(\Carbon::now()->format('d M Y, h:i A'));
        var openTime = @json(\Carbon::createFromFormat('Y-m-d H:i:s', $register_details->open_time)->format('d M Y, h:i A'));
        var user = @json($register_details->user_name);
        var location = @json($register_details->location_name);

        // Collect all currency values from .mini_print section
        function cv(selector) {
            var el = document.querySelector(selector);
            return el ? el.textContent.trim() : '0.00';
        }

        var rows = [];
        document.querySelectorAll('.mini_print .table-condensed tr').forEach(function(tr) {
            var tds = tr.querySelectorAll('td, th');
            if (tds.length >= 2) {
                rows.push([tds[0].textContent.trim(), tds[tds.length-1].textContent.trim()]);
            }
        });

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
        html += '<p class="sub"><strong>REGISTER CLOSE REPORT</strong></p>';
        html += '<p class="sub">Opened: ' + openTime + '</p>';
        html += '<p class="sub">Closed: ' + now + '</p>';
        html += '<p class="sub">Cashier: ' + user + ' | ' + location + '</p>';
        html += '<hr class="solid">';

        // Extract sections from mini_print
        var sections = document.querySelectorAll('.mini_print h4');
        sections.forEach(function(h4) {
            html += '<h4>' + h4.textContent.trim() + '</h4>';
            var tbl = h4.nextElementSibling;
            // skip hr elements
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
    </script>
  </div><!-- /.modal-content -->
</div><!-- /.modal-dialog -->