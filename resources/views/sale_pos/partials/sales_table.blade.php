@php
    $custom_labels = json_decode(session('business.custom_labels'), true);
@endphp
<style>
    #sell_table { font-size: 12px; border-collapse: separate; border-spacing: 0; }
    #sell_table thead th { padding: 6px 8px; font-size: 11px; text-transform: uppercase; letter-spacing: 0.4px; background: #f8fafc; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
    #sell_table tbody td { padding: 5px 8px; vertical-align: middle; white-space: nowrap; }
    #sell_table tbody tr:hover td { background: #f1f5f9; }
    #sell_table .btn-group > .btn { padding: 2px 8px; font-size: 11px; border-radius: 6px; }
    #sell_table .btn-group .dropdown-menu { font-size: 12px; min-width: 120px; }
    #sell_table tfoot td { padding: 6px 8px; font-size: 12px; background: #f8fafc; border-top: 2px solid #e2e8f0; }
    .dataTables_wrapper .dataTables_filter input { padding: 4px 10px; font-size: 12px; border-radius: 8px; border: 1px solid #cbd5e1; }
    .dataTables_wrapper .dataTables_length select { padding: 3px 8px; font-size: 12px; border-radius: 6px; }
    .dataTables_wrapper .dt-buttons .dt-button { padding: 4px 10px; font-size: 11px; border-radius: 6px; }
    #sell_table .badge { font-size: 10px; padding: 2px 6px; border-radius: 4px; }
    .box .box-body { padding: 10px; }
</style>
@include('sell.partials.sell_table_scroll_styles')
<div class="table-responsive vspos-table-scroll-x vspos-sell-table-wrap">
<table class="table table-bordered table-striped ajax_view" id="sell_table" style="width:100%;">
    <thead>
        <tr>
            <th class="not-export">@lang('messages.action')</th>
            <th>@lang('messages.date')</th>
            <th>@lang('sale.invoice_no')</th>
            <th>@lang('sale.customer_name')</th>
            <th>@lang('lang_v1.contact_no')</th>
            <th>@lang('sale.location')</th>
            <th>@lang('sale.payment_status')</th>
            <th>@lang('lang_v1.payment_method')</th>
            <th>@lang('sale.total_amount')</th>
            <th>@lang('sale.total_paid')</th>
            <th>@lang('lang_v1.sell_due')</th>
            <th>@lang('lang_v1.sell_return_due')</th>
            <th>@lang('lang_v1.shipping_status')</th>
            <th>@lang('lang_v1.total_items')</th>
            <th>@lang('lang_v1.types_of_service')</th>
            <th>{{ $custom_labels['types_of_service']['custom_field_1'] ?? __('lang_v1.service_custom_field_1' )}}</th>
            <th>@lang('lang_v1.added_by')</th>
            <th>@lang('sale.sell_note')</th>
            <th>@lang('sale.staff_note')</th>
            <th>@lang('sale.shipping_details')</th>
            <th>@lang('restaurant.table')</th>
            <th>@lang('restaurant.service_staff')</th>
        </tr>
    </thead>
    <tfoot>
        {{-- 22 columns to match thead / DataTables columns --}}
        <tr class="bg-gray font-17 footer-total text-center">
            <td colspan="6"><strong>@lang('sale.total'):</strong></td>
            <td class="footer_payment_status_count"></td>
            <td class="payment_method_count"></td>
            <td class="footer_sale_total"></td>
            <td class="footer_total_paid"></td>
            <td class="footer_total_remaining"></td>
            <td class="footer_total_sell_return_due"></td>
            <td colspan="2"></td>
            <td class="service_type_count"></td>
            <td colspan="7"></td>
        </tr>
    </tfoot>
</table>
</div>