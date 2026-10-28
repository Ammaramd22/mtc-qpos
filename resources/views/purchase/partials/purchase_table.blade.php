@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
    $purchase_cf = $custom_labels['purchase'] ?? [];
@endphp
{{-- vspos-table-scroll-x overrides dark-theme overflow:visible so wide tables stay inside the card --}}
<div class="table-responsive vspos-table-scroll-x vspos-purchase-table-wrap">
<table class="table table-bordered table-striped ajax_view" id="purchase_table" style="width: 100%;">
    <thead>
        <tr>
            <th class="not-export">@lang('messages.action')</th>
            <th>@lang('messages.date')</th>
            <th>@lang('purchase.ref_no')</th>
            <th>@lang('purchase.location')</th>
            <th>@lang('purchase.supplier')</th>
            <th>@lang('purchase.purchase_status')</th>
            <th>@lang('purchase.payment_status')</th>
            <th>@lang('purchase.grand_total')</th>
            <th>@lang('purchase.payment_due') &nbsp;&nbsp;<i class="fa fa-info-circle text-info no-print" data-toggle="tooltip" data-placement="bottom" data-html="true" data-original-title="{{ __('messages.purchase_due_tooltip')}}" aria-hidden="true"></i></th>
            <th>{{ $purchase_cf['custom_field_1'] ?? '' }}</th>
            <th>{{ $purchase_cf['custom_field_2'] ?? '' }}</th>
            <th>{{ $purchase_cf['custom_field_3'] ?? '' }}</th>
            <th>{{ $purchase_cf['custom_field_4'] ?? '' }}</th>
            <th>@lang('lang_v1.added_by')</th>
        </tr>
    </thead>
    <tfoot>
        <tr class="bg-gray font-17 text-center footer-total">
            {{-- Match thead: 14 columns --}}
            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
            <td class="footer_status_count"></td>
            <td class="footer_payment_status_count"></td>
            <td class="footer_purchase_total"></td>
            <td class="text-left">
                <small>
                    @lang('report.purchase_due') - <span class="footer_total_due"></span><br>
                    @lang('lang_v1.purchase_return') - <span class="footer_total_purchase_return_due"></span>
                </small>
            </td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
        </tr>
    </tfoot>
</table>
</div>
<style>
    /* Scroll lives on DataTables' body so the cloned header stays aligned.
       Scrolling the outer card as well squeezed header columns over the body. */
    body:not(.lockscreen) .vspos-purchase-table-wrap.vspos-table-scroll-x,
    body:not(.lockscreen) .vspos-purchase-table-wrap {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_wrapper {
        width: 100% !important;
        max-width: 100% !important;
        overflow: visible !important;
    }
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_scroll,
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_scrollHead,
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_scrollFoot {
        width: 100% !important;
        max-width: 100% !important;
        overflow: hidden !important;
    }
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_scrollBody {
        width: 100% !important;
        max-width: 100% !important;
        overflow-x: auto !important;
        overflow-y: auto !important;
        -webkit-overflow-scrolling: touch;
    }
    body:not(.lockscreen) .vspos-purchase-table-wrap table.dataTable {
        margin: 0 !important;
        width: auto !important;
        min-width: 100% !important;
    }
</style>
