@extends('layouts.app')
@section('title', __('lang_v1.new_repair'))

@section('content')
<section class="content-header">
    <h1>@lang('lang_v1.new_repair')</h1>
</section>

<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        @include('mobile_shop.partials.repair_form', ['imei' => $imei ?? '', 'form_id' => 'mobile_repair_page_form', 'is_modal' => false])
    @endcomponent
</section>
@endsection

@section('javascript')
<script>
$(function () {
    $('#mobile_repair_page_form').submit(function (e) {
        e.preventDefault();
        $.post($(this).attr('action'), $(this).serialize(), function (r) {
            if (r.success) {
                toastr.success(r.msg);
                window.location.href = '{{ action([\App\Http\Controllers\MobileShopController::class, 'repairIndex']) }}';
            } else {
                toastr.error(r.msg);
            }
        });
    });
});
</script>
@endsection
