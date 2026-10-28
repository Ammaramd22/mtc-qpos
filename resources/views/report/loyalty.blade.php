@extends('layouts.app')
@section('title', __('lang_v1.loyalty_report'))

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">{{ __('lang_v1.loyalty_report') }}</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.filters', ['title' => __('report.filters')])
                {!! Form::open(['url' => action([\App\Http\Controllers\ReportController::class, 'getLoyaltyReport']), 'method' => 'get', 'id' => 'loyalty_report_filter_form' ]) !!}
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('loyalty_customer_id', __('contact.customer') . ':') !!}
                        {!! Form::select('loyalty_customer_id', $customers, null, ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all'), 'id' => 'loyalty_customer_id']); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('loyalty_only_with_points', __('lang_v1.loyalty_only_with_balance') . ':') !!}
                        {!! Form::select('loyalty_only_with_points', ['0' => __('messages.no'), '1' => __('messages.yes')], '1', ['class' => 'form-control select2', 'style' => 'width:100%', 'id' => 'loyalty_only_with_points']); !!}
                    </div>
                </div>
                {!! Form::close() !!}
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="loyalty_report_table">
                    <thead>
                        <tr>
                            <th>@lang('contact.customer')</th>
                            <th>@lang('contact.mobile')</th>
                            <th>@lang('lang_v1.contact_id')</th>
                            <th>@lang('lang_v1.available')</th>
                            <th>@lang('lang_v1.redeemed')</th>
                            <th>@lang('lang_v1.earned') (@lang('sale.total'))</th>
                            <th>@lang('lang_v1.loyalty_cash_value')</th>
                        </tr>
                    </thead>
                </table>
            </div>
            @endcomponent
        </div>
    </div>
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function () {
        var redeem_rate = {{ (float) ($redeem_amount_per_unit_rp ?? 1) }};

        var loyalty_report_table = $('#loyalty_report_table').DataTable({
            processing: true,
            serverSide: true,
            fixedHeader: false,
            ajax: {
                url: '/reports/loyalty-report',
                data: function (d) {
                    d.customer_id = $('#loyalty_customer_id').val();
                    d.only_with_points = $('#loyalty_only_with_points').val();
                }
            },
            columns: [
                { data: 'name', name: 'contacts.name' },
                { data: 'mobile', name: 'contacts.mobile' },
                { data: 'contact_id', name: 'contacts.contact_id' },
                { data: 'total_rp', name: 'contacts.total_rp' },
                { data: 'total_rp_used', name: 'contacts.total_rp_used' },
                { data: 'lifetime_earned', name: 'lifetime_earned', searchable: false, orderable: false },
                { data: 'cash_value', name: 'cash_value', searchable: false, orderable: false }
            ],
            fnDrawCallback: function () {
                __currency_convert_recursively($('#loyalty_report_table'));
            }
        });

        $(document).on('change', '#loyalty_customer_id, #loyalty_only_with_points', function () {
            loyalty_report_table.ajax.reload();
        });
    });
</script>
@endsection
