@php
    $check_items = \App\MobileRepair::phoneCheckItems();
    $check_statuses = \App\MobileRepair::phoneCheckStatuses();
    $saved = is_array($phone_check ?? null) ? $phone_check : [];
@endphp

<div class="vspos-phone-check" id="vspos_phone_check">
    <div class="vspos-phone-check-head">
        <strong><i class="fa fa-clipboard-check"></i> @lang('lang_v1.phone_check')</strong>
        <span class="text-muted">@lang('lang_v1.phone_check_help')</span>
        <div class="vspos-phone-check-quick">
            <button type="button" class="btn btn-xs btn-success vspos-phone-check-all-ok">@lang('lang_v1.phone_check_mark_all_ok')</button>
            <button type="button" class="btn btn-xs btn-default vspos-phone-check-clear">@lang('lang_v1.phone_check_clear')</button>
        </div>
    </div>
    <div class="vspos-phone-check-grid">
        @foreach($check_items as $key => $label)
            @php $val = $saved[$key] ?? ''; @endphp
            <div class="vspos-phone-check-row" data-key="{{ $key }}">
                <span class="vspos-phone-check-label">{{ $label }}</span>
                <div class="vspos-phone-check-options" role="group" aria-label="{{ $label }}">
                    @foreach($check_statuses as $status => $status_label)
                        <label class="vspos-phone-check-opt vspos-phone-check-opt--{{ $status }} @if($val === $status) is-active @endif">
                            <input type="radio"
                                name="phone_check[{{ $key }}]"
                                value="{{ $status }}"
                                @if($val === $status) checked @endif>
                            <span>{{ $status_label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>

<style>
.vspos-phone-check {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 12px;
    margin: 12px 0 16px;
    background: #fafafa;
}
.vspos-phone-check-head {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 8px 12px;
    margin-bottom: 10px;
}
.vspos-phone-check-head .text-muted {
    font-size: 12px;
    flex: 1;
}
.vspos-phone-check-quick {
    display: flex;
    gap: 6px;
}
.vspos-phone-check-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}
@media (max-width: 768px) {
    .vspos-phone-check-grid { grid-template-columns: 1fr; }
}
.vspos-phone-check-row {
    display: flex;
    flex-direction: column;
    gap: 4px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 8px 10px;
}
.vspos-phone-check-label {
    font-size: 12px;
    font-weight: 700;
    color: #334155;
}
.vspos-phone-check-options {
    display: flex;
    gap: 4px;
}
.vspos-phone-check-opt {
    flex: 1;
    margin: 0;
    text-align: center;
    font-size: 11px;
    font-weight: 700;
    border: 1px solid #e2e8f0;
    border-radius: 6px;
    padding: 4px 2px;
    cursor: pointer;
    color: #64748b;
    background: #f8fafc;
    user-select: none;
}
.vspos-phone-check-opt input { display: none; }
.vspos-phone-check-opt.is-active.vspos-phone-check-opt--ok,
.vspos-phone-check-opt--ok:has(input:checked) {
    background: #dcfce7;
    border-color: #22c55e;
    color: #166534;
}
.vspos-phone-check-opt.is-active.vspos-phone-check-opt--faulty,
.vspos-phone-check-opt--faulty:has(input:checked) {
    background: #fee2e2;
    border-color: #ef4444;
    color: #991b1b;
}
.vspos-phone-check-opt.is-active.vspos-phone-check-opt--na,
.vspos-phone-check-opt--na:has(input:checked) {
    background: #e2e8f0;
    border-color: #94a3b8;
    color: #475569;
}
body.lockscreen .vspos-phone-check {
    background: #161616;
    border-color: #333;
}
body.lockscreen .vspos-phone-check-row {
    background: #0a0a0a;
    border-color: #333;
}
body.lockscreen .vspos-phone-check-label { color: #e5e5e5; }
body.lockscreen .vspos-phone-check-opt {
    background: #111;
    border-color: #333;
    color: #a3a3a3;
}
</style>

<script>
(function ($) {
    function syncPhoneCheckActive($scope) {
        $scope.find('.vspos-phone-check-opt').each(function () {
            $(this).toggleClass('is-active', $(this).find('input').is(':checked'));
        });
    }
    $(document).off('change.vsposPhoneCheck', '.vspos-phone-check-opt input')
        .on('change.vsposPhoneCheck', '.vspos-phone-check-opt input', function () {
            syncPhoneCheckActive($(this).closest('.vspos-phone-check'));
        });
    $(document).off('click.vsposPhoneCheckAll', '.vspos-phone-check-all-ok')
        .on('click.vsposPhoneCheckAll', '.vspos-phone-check-all-ok', function (e) {
            e.preventDefault();
            var $box = $(this).closest('.vspos-phone-check');
            $box.find('input[value="ok"]').prop('checked', true);
            syncPhoneCheckActive($box);
        });
    $(document).off('click.vsposPhoneCheckClear', '.vspos-phone-check-clear')
        .on('click.vsposPhoneCheckClear', '.vspos-phone-check-clear', function (e) {
            e.preventDefault();
            var $box = $(this).closest('.vspos-phone-check');
            $box.find('input[type="radio"]').prop('checked', false);
            syncPhoneCheckActive($box);
        });
})(jQuery);
</script>
