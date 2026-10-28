@extends('layouts.app')
@section('title', __('lang_v1.capital_accounts'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.partner_balance_sheet')
        <small>@lang('lang_v1.capital_accounts_help')</small>
    </h1>
</section>

<section class="content">
    @php $t = $sheet['totals']; @endphp

    <div class="row">
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_credit_balance')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['credit_balance'] }}</span></h2>
                <p class="help-block" style="margin-bottom:0;">@lang('lang_v1.capital_credit_balance_help')</p>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_total_assets')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['total_assets'] }}</span></h2>
                <p class="help-block" style="margin-bottom:0;">@lang('lang_v1.capital_total_assets_help')</p>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_source_bank')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['bank_balance'] }}</span></h2>
                @if(!empty($sheet['linked']['bank_from_payment_account']))
                    <p class="help-block" style="margin-bottom:0;">
                        <i class="fa fa-link"></i> {{ $sheet['linked']['bank_account_name'] }}
                        <span class="text-muted">(@lang('lang_v1.payment_accounts'))</span>
                    </p>
                @endif
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_source_cash_drawer')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['cash_drawer_balance'] }}</span></h2>
                @if(!empty($sheet['linked']['cash_from_payment_account']))
                    <p class="help-block" style="margin-bottom:0;">
                        <i class="fa fa-link"></i> {{ $sheet['linked']['cash_account_name'] }}
                        <span class="text-muted">(@lang('lang_v1.payment_accounts'))</span>
                    </p>
                @endif
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_total_investment')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['total_investment'] }}</span></h2>
            @endcomponent
        </div>
        <div class="col-md-3 col-sm-6">
            @component('components.widget', ['class' => 'box-solid'])
                <h4 class="text-muted" style="margin-top:0;">@lang('lang_v1.capital_fixed_assets')</h4>
                <h2 style="margin:0;"><span class="display_currency" data-currency_symbol="true">{{ $t['fixed_assets'] }}</span></h2>
            @endcomponent
        </div>
    </div>

    @if(empty($sheet['linked']['bank_account_id']) || empty($sheet['linked']['cash_account_id']))
    <div class="alert alert-warning">
        <i class="fa fa-info-circle"></i>
        @lang('lang_v1.capital_link_accounts_prompt')
        <a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'settings']) }}" class="alert-link">@lang('lang_v1.capital_settings')</a>
    </div>
    @endif

    <div class="row">
        <div class="col-md-4">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.capital_cash_drawer_breakdown')])
                <table class="table table-slim mb-0">
                    <tr>
                        <td>@lang('lang_v1.capital_type_opening_balance')</td>
                        <td class="text-right"><span class="display_currency" data-currency_symbol="true">{{ $sheet['cash_drawer_breakdown']['opening_balance'] }}</span></td>
                    </tr>
                    <tr>
                        <td>@lang('lang_v1.capital_type_expense')</td>
                        <td class="text-right text-danger">− <span class="display_currency" data-currency_symbol="true">{{ $sheet['cash_drawer_breakdown']['expenses'] }}</span></td>
                    </tr>
                    <tr>
                        <td>@lang('lang_v1.capital_type_petty_cash')</td>
                        <td class="text-right text-danger">− <span class="display_currency" data-currency_symbol="true">{{ $sheet['cash_drawer_breakdown']['petty_cash'] }}</span></td>
                    </tr>
                    <tr>
                        <td>@lang('lang_v1.capital_type_purchase')</td>
                        <td class="text-right text-danger">− <span class="display_currency" data-currency_symbol="true">{{ $sheet['cash_drawer_breakdown']['purchases'] }}</span></td>
                    </tr>
                    <tr>
                        <td>@lang('lang_v1.capital_type_asset')</td>
                        <td class="text-right text-danger">− <span class="display_currency" data-currency_symbol="true">{{ $sheet['cash_drawer_breakdown']['assets'] ?? 0 }}</span></td>
                    </tr>
                    <tr>
                        <th>@lang('lang_v1.capital_total_deductions')</th>
                        <th class="text-right text-danger">− <span class="display_currency" data-currency_symbol="true">{{ $t['total_deductions'] }}</span></th>
                    </tr>
                    <tr>
                        <th>@lang('lang_v1.capital_type_profit')</th>
                        <th class="text-right text-success"><span class="display_currency" data-currency_symbol="true">{{ $t['profit_recorded'] }}</span></th>
                    </tr>
                </table>
            @endcomponent
        </div>
        <div class="col-md-8">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.capital_partners')])
                @slot('tool')
                    <div class="box-tools">
                        <a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'partners']) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary">@lang('lang_v1.manage_partners')</a>
                        <a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'entries']) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-accent">@lang('lang_v1.capital_entries')</a>
                        <a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'settings']) }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-info">@lang('lang_v1.capital_settings')</a>
                    </div>
                @endslot
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.capital_partner_name')</th>
                                <th>@lang('lang_v1.capital_short_name')</th>
                                <th>@lang('lang_v1.capital_investment')</th>
                                <th>@lang('lang_v1.capital_ownership_percent')</th>
                                <th>@lang('lang_v1.capital_profit_percent')</th>
                                <th>@lang('lang_v1.capital_profit_amount')</th>
                                <th>@lang('lang_v1.capital_partner_balance')</th>
                                <th>@lang('lang_v1.capital_share_of_credit')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($sheet['partners'] as $p)
                                <tr>
                                    <td>{{ $p['name'] }}</td>
                                    <td>{{ $p['short_name'] ?: '—' }}</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $p['investment_amount'] }}</span></td>
                                    <td>{{ number_format($p['ownership_percent'], 2) }}%</td>
                                    <td>{{ number_format($p['profit_percent'], 2) }}%</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $p['profit_amount'] }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $p['partner_balance'] }}</span></td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $p['share_of_credit'] }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">@lang('lang_v1.capital_no_partners')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <p class="help-block">@lang('lang_v1.capital_profit_amount_help')</p>
            @endcomponent
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.capital_assets_register')])
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mb-0">
                        <thead>
                            <tr>
                                <th>@lang('lang_v1.capital_asset_name')</th>
                                <th>@lang('messages.date')</th>
                                <th>@lang('lang_v1.capital_source')</th>
                                <th>@lang('sale.amount')</th>
                                <th>@lang('brand.note')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse(($sheet['assets'] ?? []) as $a)
                                <tr>
                                    <td>{{ $a['name'] }}</td>
                                    <td>@if(!empty($a['entry_date'])) @format_date($a['entry_date']) @endif</td>
                                    <td>{{ \App\CapitalEntry::sourceLabels()[$a['source']] ?? $a['source'] }}</td>
                                    <td><span class="display_currency" data-currency_symbol="true">{{ $a['amount'] }}</span></td>
                                    <td>{{ $a['note'] ?: '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">@lang('lang_v1.capital_assets_empty')</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection
