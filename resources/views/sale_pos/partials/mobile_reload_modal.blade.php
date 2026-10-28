@php
    $common = session('business.common_settings', []);
    if (is_string($common)) {
        $common = json_decode($common, true) ?: [];
    }
    if (! is_array($common)) {
        $common = [];
    }
    $reload = $common['reload'] ?? [];
    $legacy = $common['mobile_shop'] ?? [];
    $myreload_configured = (! empty($reload['myreload_api_key']) && ! empty($reload['myreload_api_username']))
        || (! empty($legacy['myreload_api_key']) && ! empty($legacy['myreload_merchant_id']));
    $myreload_demo = strtolower((string) ($reload['myreload_api_username'] ?? '')) === 'demo'
        || (string) ($reload['myreload_api_key'] ?? '') === (string) config('myreload.demo_api_key');
@endphp

<div class="modal fade vspos-pos-modal" id="pos_mobile_reload_modal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content vspos-pos-modal-content">
            <div class="modal-header vspos-pos-modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fa fa-bolt"></i> @lang('lang_v1.bill_payments')</h4>
            </div>
            {!! Form::open(['url' => action([\App\Http\Controllers\MobileShopController::class, 'reloadStore']), 'method' => 'post', 'id' => 'pos_mobile_reload_form', 'class' => 'vspos-bill-payment-form']) !!}
            <div class="modal-body vspos-pos-modal-body">
                @if(!$myreload_configured)
                <div class="alert alert-info vspos-bill-demo-alert">
                    @lang('lang_v1.myreload_demo_mode')
                </div>
                @elseif($myreload_demo)
                <div class="alert alert-warning vspos-bill-demo-alert">
                    @lang('lang_v1.myreload_demo_mode')
                </div>
                @endif
                @include('mobile_shop.partials.bill_payment_form', ['form_id' => 'pos_mobile_reload_form', 'show_history_link' => true])
            </div>
            <div class="modal-footer vspos-pos-modal-footer">
                <button type="button" class="btn btn-default vspos-modal-close-btn" data-dismiss="modal">@lang('messages.cancel')</button>
                <button type="submit" class="btn btn-primary" id="pos_mobile_reload_submit">
                    <i class="fa fa-check"></i> @lang('lang_v1.process_payment')
                </button>
            </div>
            {!! Form::close() !!}
        </div>
    </div>
</div>
