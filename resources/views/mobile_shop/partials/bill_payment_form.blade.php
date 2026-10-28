@php
    use App\MobileReloadTransaction;
    $payment_types = MobileReloadTransaction::paymentTypes();
    $reload_operators = MobileReloadTransaction::operators();
    $utility_providers = MobileReloadTransaction::utilityProviders();
    $amount_presets = [100, 200, 500, 1000, 2000, 5000];
    $form_id = $form_id ?? 'mobile_bill_payment_form';
    $show_history_link = $show_history_link ?? false;
@endphp

<div class="vspos-bill-pay-shell" id="{{ $form_id }}_shell">
    <ul class="nav nav-tabs vspos-bill-pay-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#{{ $form_id }}_tab_reload" aria-controls="{{ $form_id }}_tab_reload" role="tab" data-toggle="tab" data-payment-type="reload">
                <i class="fa fa-bolt"></i> @lang('lang_v1.mobile_reload')
            </a>
        </li>
        <li role="presentation">
            <a href="#{{ $form_id }}_tab_utility" aria-controls="{{ $form_id }}_tab_utility" role="tab" data-toggle="tab" data-payment-type="utility">
                <i class="fa fa-file-invoice-dollar"></i> @lang('lang_v1.utility_bill')
            </a>
        </li>
        <li role="presentation">
            <a href="#{{ $form_id }}_tab_other" aria-controls="{{ $form_id }}_tab_other" role="tab" data-toggle="tab" data-payment-type="other">
                <i class="fa fa-wallet"></i> @lang('lang_v1.other_payment')
            </a>
        </li>
        @if($show_history_link)
        <li role="presentation" class="pull-right">
            <a href="#{{ $form_id }}_tab_history" aria-controls="{{ $form_id }}_tab_history" role="tab" data-toggle="tab" data-payment-type="history">
                <i class="fa fa-history"></i> @lang('lang_v1.bill_history')
            </a>
        </li>
        @endif
    </ul>

    <div class="tab-content vspos-bill-pay-tab-content">
        <div role="tabpanel" class="tab-pane active" id="{{ $form_id }}_tab_reload">
            <div class="form-group">
                <label>@lang('lang_v1.mobile_number')</label>
                <input type="text" class="form-control input-lg vspos-bill-field-mobile" placeholder="07xxxxxxxx" autocomplete="tel">
            </div>
            <div class="form-group">
                <label>@lang('lang_v1.operator')</label>
                <select class="form-control vspos-bill-field-operator">
                    @foreach($reload_operators as $key => $label)
                        <option value="{{ $key }}" @if($key === 'dialog') selected @endif>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            @include('mobile_shop.partials.bill_amount_picker', ['amount_name' => $form_id.'_reload', 'default' => 500, 'presets' => $amount_presets])
        </div>

        <div role="tabpanel" class="tab-pane" id="{{ $form_id }}_tab_utility">
            <div class="form-group">
                <label>@lang('lang_v1.bill_provider')</label>
                <select class="form-control vspos-bill-field-provider">
                    @foreach($utility_providers as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group">
                <label>@lang('lang_v1.account_number')</label>
                <input type="text" class="form-control input-lg vspos-bill-field-account" placeholder="@lang('lang_v1.account_number_placeholder')">
            </div>
            @include('mobile_shop.partials.bill_amount_picker', ['amount_name' => $form_id.'_utility', 'default' => null, 'presets' => $amount_presets, 'custom_only' => true])
            <div class="form-group">
                <label>@lang('brand.note')</label>
                <input type="text" class="form-control vspos-bill-field-notes" placeholder="@lang('lang_v1.optional')">
            </div>
        </div>

        <div role="tabpanel" class="tab-pane" id="{{ $form_id }}_tab_other">
            <div class="form-group">
                <label>@lang('lang_v1.payment_description')</label>
                <input type="text" class="form-control vspos-bill-field-other-desc" placeholder="@lang('lang_v1.other_payment_placeholder')">
            </div>
            <div class="form-group">
                <label>@lang('lang_v1.reference_account')</label>
                <input type="text" class="form-control vspos-bill-field-other-account" placeholder="@lang('lang_v1.optional')">
            </div>
            @include('mobile_shop.partials.bill_amount_picker', ['amount_name' => $form_id.'_other', 'default' => null, 'presets' => $amount_presets, 'custom_only' => true])
        </div>

        @if($show_history_link)
        <div role="tabpanel" class="tab-pane" id="{{ $form_id }}_tab_history">
            <div class="vspos-bill-history-loading text-center hide"><i class="fa fa-spinner fa-spin"></i></div>
            <div class="vspos-bill-history-empty alert alert-info hide" style="margin:0;">@lang('lang_v1.bill_history_empty')</div>
            <div class="vspos-bill-history-list"></div>
        </div>
        @endif
    </div>

    {!! Form::hidden('payment_type', 'reload', ['class' => 'vspos-bill-hidden-type']) !!}
    {!! Form::hidden('amount', '500', ['class' => 'vspos-bill-hidden-amount']) !!}
    {!! Form::hidden('mobile_number', '', ['class' => 'vspos-bill-hidden-mobile']) !!}
    {!! Form::hidden('operator', 'dialog', ['class' => 'vspos-bill-hidden-operator']) !!}
    {!! Form::hidden('bill_provider', '', ['class' => 'vspos-bill-hidden-bill-provider']) !!}
    {!! Form::hidden('account_number', '', ['class' => 'vspos-bill-hidden-account']) !!}
    {!! Form::hidden('notes', '', ['class' => 'vspos-bill-hidden-notes']) !!}
</div>
