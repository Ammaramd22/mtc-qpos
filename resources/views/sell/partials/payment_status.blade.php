@php
    $hasPendingCheque = false;
    if ($payment_status === 'paid' && !empty($id)) {
        $transactionUtil = app(\App\Utils\TransactionUtil::class);
        $hasPendingCheque = $transactionUtil->hasPendingChequePayments($id);
    }
@endphp
<a href="{{ action([\App\Http\Controllers\TransactionPaymentController::class, 'show'], [$id])}}" class="view_payment_modal payment-status-label" data-orig-value="{{$payment_status}}" data-status-name="{{__('lang_v1.' . $payment_status)}}">
    @if($hasPendingCheque)
        <span class="label bg-orange" title="Payment made by cheque - pending clearance">
            <i class="fas fa-clock"></i> {{__('lang_v1.paid')}} (Chq. Pending)
        </span>
    @else
        <span class="label @payment_status($payment_status)">{{__('lang_v1.' . $payment_status)}}
        </span>
    @endif
</a>