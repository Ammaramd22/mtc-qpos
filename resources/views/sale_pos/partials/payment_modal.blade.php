<div class="modal fade" tabindex="-1" role="dialog" id="modal_payment">
    <div class="modal-dialog vspos-pay-dialog" role="document">
        <div class="modal-content vspos-pay-modal">

            {{-- Header --}}
            <div class="vspos-pay-header">
                <div class="vspos-pay-header-left">
                    <div class="vspos-pay-header-icon"><i class="fas fa-cash-register"></i></div>
                    <div>
                        <div class="vspos-pay-header-title">@lang('lang_v1.payment')</div>
                        <div class="vspos-pay-header-sub">
                            @lang('lang_v1.advance_balance'): <span id="advance_balance_text" class="vspos-pay-advance">—</span>
                            {!! Form::hidden('advance_balance', null, ['id' => 'advance_balance', 'data-error-msg' => __('lang_v1.required_advance_balance_not_available')]) !!}
                        </div>
                    </div>
                </div>
                <button type="button" class="vspos-pay-close" data-dismiss="modal"><i class="fas fa-times"></i></button>
            </div>

            <div class="modal-body vspos-pay-body">

                    {{-- TOP: checkout hero + meters --}}
                    <div class="vspos-pay-summary">
                        <div class="vspos-pay-hero">
                            <div class="vspos-pay-hero-meta">
                                <span class="vspos-pay-hero-kicker">Checkout</span>
                                <span class="vspos-pay-hero-items"><i class="fas fa-cubes"></i> <span class="total_quantity">0</span> @lang('lang_v1.total_items')</span>
                            </div>
                            <div class="vspos-pay-hero-label">@lang('sale.total_payable')</div>
                            <div class="vspos-pay-hero-amount total_payable_span">0</div>
                        </div>
                        <div class="vspos-pay-meters">
                            <div class="vspos-pay-meter">
                                <span class="vspos-psc-label">@lang('lang_v1.total_paying')</span>
                                <span class="vspos-psc-value total_paying">0</span>
                                <input type="hidden" id="total_paying_input">
                            </div>
                            <div class="vspos-pay-meter vspos-pay-meter--due">
                                <span class="vspos-psc-label">@lang('lang_v1.balance')</span>
                                <span class="vspos-psc-value balance_due">0</span>
                                <input type="hidden" id="in_balance_due" value=0>
                            </div>
                            <div class="vspos-pay-meter vspos-pay-meter--change">
                                <span class="vspos-psc-label">@lang('lang_v1.change_return')</span>
                                <span class="vspos-psc-value change_return_span">0</span>
                                {!! Form::hidden('change_return', $change_return['amount'], ['class' => 'change_return input_number', 'id' => 'change_return']) !!}
                                @if (!empty($change_return['id']))
                                    <input type="hidden" name="change_return_id" value="{{ $change_return['id'] }}">
                                @endif
                            </div>
                        </div>
                    </div>

                <div class="vspos-pay-layout">
                    {{-- Payment rows + notes --}}
                    <div class="vspos-pay-left">

                        {{-- Payment rows --}}
                        <div id="payment_rows_div">
                            @php
                                $pos_settings = !empty(session()->get('business.pos_settings')) ? json_decode(session()->get('business.pos_settings'), true) : [];
                                $show_in_pos = '';
                                if (isset($pos_settings['enable_cash_denomination_on']) && ($pos_settings['enable_cash_denomination_on'] == 'all_screens' || $pos_settings['enable_cash_denomination_on'] == 'pos_screen')) {
                                    $show_in_pos = true;
                                }
                            @endphp
                            @foreach ($payment_lines as $payment_line)
                                @if (($payment_line['is_return'] ?? 0) == 1)
                                    @php $change_return = $payment_line; @endphp
                                    @continue
                                @endif
                                @include('sale_pos.partials.payment_row', [
                                    'removable' => !$loop->first,
                                    'row_index' => $loop->index,
                                    'payment_line' => $payment_line,
                                    'show_denomination' => true,
                                    'show_in_pos' => $show_in_pos,
                                ])
                            @endforeach
                        </div>
                        <input type="hidden" id="payment_row_index" value="{{ count($payment_lines) }}">

                        <button type="button" class="vspos-add-payment-row" id="add-payment-row">
                            <i class="fas fa-plus-circle"></i> @lang('sale.add_payment_row')
                        </button>

                        {{-- Change return --}}
                        <div class="vspos-change-return-box @if ($change_return['amount'] == 0) hide @endif payment_row" id="change_return_payment_data">
                            <div class="vspos-change-return-label"><i class="fas fa-exchange-alt"></i> @lang('lang_v1.change_return_payment_method')</div>
                            <div class="vspos-change-return-row">
                                <div class="vspos-change-return-field">
                                    @php
                                        $_payment_method = empty($change_return['method']) && array_key_exists('cash', $payment_types) ? 'cash' : $change_return['method'];
                                        $_payment_types = $payment_types;
                                        if (isset($_payment_types['advance'])) { unset($_payment_types['advance']); }
                                    @endphp
                                    {!! Form::select('payment[change_return][method]', $_payment_types, $_payment_method, [
                                        'class' => 'form-control payment_types_dropdown',
                                        'id' => 'change_return_method',
                                    ]) !!}
                                </div>
                                @if (!empty($accounts))
                                <div class="vspos-change-return-field">
                                    {!! Form::select('payment[change_return][account_id]', $accounts,
                                        !empty($change_return['account_id']) ? $change_return['account_id'] : '',
                                        ['class' => 'form-control select2', 'id' => 'change_return_account']) !!}
                                </div>
                                @endif
                            </div>
                            @include('sale_pos.partials.payment_type_details', ['payment_line' => $change_return, 'row_index' => 'change_return'])
                        </div>

                        {{-- Notes --}}
                        <div class="vspos-notes-row">
                            <div class="vspos-note-field">
                                <label class="vspos-note-label"><i class="fas fa-sticky-note"></i> @lang('sale.sell_note')</label>
                                {!! Form::textarea('sale_note', !empty($transaction) ? $transaction->additional_notes : null, ['class' => 'form-control vspos-textarea', 'rows' => 2, 'placeholder' => __('sale.sell_note')]) !!}
                            </div>
                            <div class="vspos-note-field">
                                <label class="vspos-note-label"><i class="fas fa-user-lock"></i> @lang('sale.staff_note')</label>
                                {!! Form::textarea('staff_note', !empty($transaction) ? $transaction->staff_note : null, ['class' => 'form-control vspos-textarea', 'rows' => 2, 'placeholder' => __('sale.staff_note')]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="vspos-pay-footer">
                <button type="button" class="vspos-btn-close-modal" data-dismiss="modal">
                    <i class="fas fa-times"></i> @lang('messages.close')
                </button>
                <button type="submit" class="vspos-finalize-btn-footer" id="pos-save-footer">
                    <i class="fas fa-check-circle"></i> @lang('sale.finalize_payment')
                </button>
            </div>

        </div>
    </div>
</div>

<style>
/* ===== VsoftPOS Payment Modal ===== */
.vspos-pay-dialog { max-width: 580px; }

.vspos-pay-modal {
    border-radius: 16px;
    overflow: hidden;
    border: none;
    box-shadow: 0 25px 60px rgba(0,0,0,0.35);
}

/* Header */
.vspos-pay-header {
    background: linear-gradient(135deg, #1a1a1a, #0a0a0a);
    padding: 11px 16px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid #2a2a2a;
}
.vspos-pay-header-left { display: flex; align-items: center; gap: 14px; }
.vspos-pay-header-icon {
    width: 34px; height: 34px;
    background: rgba(255,255,255,0.12);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; color: #fff;
}
.vspos-pay-header-title { font-size: 15px; font-weight: 800; color: #fff; }
.vspos-pay-header-sub { font-size: 10px; color: rgba(255,255,255,0.55); margin-top: 1px; }
.vspos-pay-advance { color: #f5c518; font-weight: 700; }
.vspos-pay-close {
    background: rgba(255,255,255,0.1);
    border: none; color: #fff;
    width: 32px; height: 32px; border-radius: 50%;
    cursor: pointer; font-size: 14px;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.vspos-pay-close:hover { background: rgba(255,255,255,0.2); }

/* Body */
.vspos-pay-body { padding: 12px; background: #111111; color: #f5f5f5; }

.vspos-pay-layout {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 10px;
}
.vspos-pay-left { width: 100%; }

/* Add payment row btn */
.vspos-add-payment-row {
    width: 100%;
    padding: 8px;
    background: rgba(245, 197, 24, 0.04);
    border: 1px dashed rgba(245, 197, 24, 0.35);
    border-radius: 10px;
    color: #f5c518;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    margin-top: 8px;
    transition: border-color 0.2s, color 0.2s, background 0.2s;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.vspos-add-payment-row:hover { border-color: #f5c518; color: #f5c518; background: rgba(245, 197, 24, 0.1); }

/* Change return box */
.vspos-change-return-box {
    background: #141414;
    border: 1px solid #2a2a2a;
    border-radius: 12px;
    padding: 12px 14px;
    margin-top: 10px;
}
.vspos-change-return-label { font-size: 11px; font-weight: 700; color: #f5c518; text-transform: uppercase; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }
.vspos-change-return-row { display: flex; gap: 10px; flex-wrap: wrap; }
.vspos-change-return-field { flex: 1; min-width: 140px; }

/* Notes */
.vspos-notes-row { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 8px; }
.vspos-note-field { flex: 1; min-width: 160px; }
.vspos-note-label { font-size: 10px; font-weight: 700; color: #a3a3a3; display: flex; align-items: center; gap: 5px; margin-bottom: 3px; }
.vspos-textarea { border-radius: 6px !important; border-color: #333 !important; background: #1a1a1a !important; color: #f5f5f5 !important; font-size: 11px !important; resize: none; }

/* Checkout hero + meters (not UltimatePOS card grid) */
.vspos-pay-summary {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 4px;
}
.vspos-pay-hero {
    text-align: center;
    padding: 10px 12px 4px;
    background:
        radial-gradient(ellipse 70% 80% at 50% 0%, rgba(245, 197, 24, 0.1), transparent 60%);
    border-radius: 14px;
}
.vspos-pay-hero-meta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin-bottom: 6px;
}
.vspos-pay-hero-kicker {
    font-size: 10px;
    font-weight: 800;
    letter-spacing: 0.14em;
    text-transform: uppercase;
    color: #f5c518;
}
.vspos-pay-hero-items {
    font-size: 11px;
    font-weight: 600;
    color: #a3a3a3;
}
.vspos-pay-hero-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: rgba(255,255,255,0.45);
    margin-bottom: 4px;
}
.vspos-pay-hero-amount {
    font-size: 36px;
    font-weight: 900;
    font-variant-numeric: tabular-nums;
    letter-spacing: -0.03em;
    color: #fff;
    line-height: 1.1;
}
.vspos-pay-meters {
    display: flex;
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 12px;
    overflow: hidden;
    background: rgba(255,255,255,0.03);
}
.vspos-pay-meter {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 3px;
    padding: 10px 8px;
    border-right: 1px solid rgba(255,255,255,0.06);
    min-width: 0;
}
.vspos-pay-meter:last-child { border-right: none; }
.vspos-psc-label {
    font-size: 9px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: rgba(255,255,255,0.4);
    white-space: nowrap;
}
.vspos-psc-value {
    font-size: 14px;
    font-weight: 800;
    color: #f5f5f5;
    font-variant-numeric: tabular-nums;
}
.vspos-pay-meter--due .vspos-psc-value { color: #f5c518; }
.vspos-pay-meter--change .vspos-psc-value { color: #4ade80; }

/* Finalize button hidden (no longer inside summary) */
.vspos-finalize-btn { display: none !important; }

/* Footer */
.vspos-pay-footer {
    background: #141414;
    border-top: 1px solid #2a2a2a;
    padding: 12px 16px;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}
.vspos-btn-close-modal {
    padding: 9px 20px;
    border-radius: 10px;
    background: #1a1a1a;
    border: 1px solid #333;
    color: #d4d4d4;
    font-size: 12px; font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; gap: 6px;
    transition: background 0.2s;
}
.vspos-btn-close-modal:hover { background: #262626; }

.vspos-finalize-btn-footer {
    padding: 9px 24px;
    border-radius: 10px;
    background: linear-gradient(135deg, #f5c518, #d4a017);
    border: none;
    color: #111;
    font-size: 13px; font-weight: 800;
    cursor: pointer;
    display: flex; align-items: center; gap: 7px;
    box-shadow: 0 4px 16px rgba(245, 197, 24, 0.35);
    transition: opacity 0.2s, transform 0.1s;
}
.vspos-finalize-btn-footer:hover { opacity: 0.92; }
.vspos-finalize-btn-footer:active { transform: scale(0.98); }
</style>

<script>
$(document).ready(function() {
    $(document).on('click', '#pos-save-footer', function() {
        $('#pos-save').trigger('click');
    });
});
</script>

<!-- Used for express checkout card transaction -->
<div class="modal fade vspos-pos-modal" tabindex="-1" role="dialog" id="card_details_modal">
    <div class="modal-dialog" role="document">
        <div class="modal-content vspos-pos-modal-content">
            <div class="modal-header vspos-pos-modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
                        aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">@lang('lang_v1.card_transaction_details')</h4>
            </div>
            <div class="modal-body vspos-pos-modal-body">
                <div class="row">
                    <div class="col-md-12">

                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_number', __('lang_v1.card_no') . ' (first 4)') !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control card_number_input',
                                    'placeholder' => 'e.g. 5455',
                                    'id' => 'card_number',
                                    'maxlength' => 4,
                                    'inputmode' => 'numeric',
                                    'autocomplete' => 'off',
                                    'autofocus',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_holder_name', __('lang_v1.card_holder_name')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_holder_name'),
                                    'id' => 'card_holder_name',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                {!! Form::label('card_transaction_number', __('lang_v1.card_transaction_no')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.card_transaction_no'),
                                    'id' => 'card_transaction_number',
                                ]) !!}
                            </div>
                        </div>
                        <div class="clearfix"></div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_type', __('lang_v1.card_type')) !!}
                                {!! Form::select('', ['visa' => 'Visa', 'master' => 'MasterCard'], 'visa', [
                                    'class' => 'form-control select2',
                                    'id' => 'card_type',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_month', __('lang_v1.month')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.month'),
                                    'id' => 'card_month',
                                ]) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_year', __('lang_v1.year')) !!}
                                {!! Form::text('', null, ['class' => 'form-control', 'placeholder' => __('lang_v1.year'), 'id' => 'card_year']) !!}
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                {!! Form::label('card_security', __('lang_v1.security_code')) !!}
                                {!! Form::text('', null, [
                                    'class' => 'form-control',
                                    'placeholder' => __('lang_v1.security_code'),
                                    'id' => 'card_security',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer vspos-pos-modal-footer">
                <button type="button" class="tw-dw-btn tw-dw-btn-primary tw-text-white" id="pos-save-card">@lang('sale.finalize_payment')</button>
            </div>
        </div>
    </div>
</div>
