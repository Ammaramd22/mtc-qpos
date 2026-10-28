@extends('layouts.app')
@section('title', __('home.home'))

@section('content')
<div class="db-page db-theme-dark">

    {{-- ── HERO BANNER ── --}}
    <div class="db-hero">
        <div class="db-hero-inner">
            <div>
                <div class="db-hero-greeting">{{ now()->format('l, d M Y') }}</div>
                <h1>{{ __('home.welcome_message', ['name' => Session::get('user.first_name')]) }}</h1>
                <p class="db-hero-sub">Here's what's happening with your business today.</p>
                <div class="db-hero-live">
                    <div class="db-hero-quote" id="db_business_quote">Smart effort today builds strong profit tomorrow.</div>
                    <div class="db-hero-chip">
                        <span class="db-hero-chip-label">Local Time</span>
                        <span id="db_local_clock" class="db-led-time"></span>
                    </div>
                </div>
            </div>
            @if (auth()->user()->can('dashboard.data'))
            <div class="db-hero-actions">
                @if (count($all_locations) > 1)
                <div style="min-width:200px;">
                    {!! Form::select('dashboard_location', $all_locations, null, [
                        'class' => 'form-control select2',
                        'placeholder' => __('lang_v1.select_location'),
                        'id' => 'dashboard_location',
                        'style' => 'background:#fff;border-radius:10px;border:none;font-size:0.82rem;',
                    ]) !!}
                </div>
                @endif
                <button type="button" id="dashboard_date_filter" class="db-filter-btn">
                    <svg class="tw-size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7z"/>
                        <path d="M16 3v4M8 3v4M4 11h16"/>
                    </svg>
                    {{ __('messages.filter_by_date') }}
                </button>
            </div>
            @endif
        </div>
    </div>

    @php
        $__dash_links = class_exists(\App\Utils\ShopModeUtil::class)
            ? \App\Utils\ShopModeUtil::dashboardLinks()
            : [];
    @endphp
    @if(!empty($__dash_links))
        <div class="box box-solid" style="margin:12px 16px 0;">
            <div class="box-body" style="padding:10px 16px;">
                @foreach($__dash_links as $i => $link)
                    @if($i > 0)
                        &nbsp;·&nbsp;
                    @endif
                    <a href="{{ url($link['url']) }}">
                        @if(!empty($link['icon']))<i class="{{ $link['icon'] }}"></i> @endif
                        @lang($link['label'])
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    {{-- ── FLOATING STAT CARDS ── --}}
    @if (auth()->user()->can('dashboard.data'))
    <div class="db-stats db-stats-primary db-stats-mobile-2col">
        {{-- Total Sales --}}
        <div class="db-stat">
            <div class="db-stat-icon blue">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="9" cy="20" r="1"></circle>
                    <circle cx="18" cy="20" r="1"></circle>
                    <path d="M3 4h2l2.3 11.1a1 1 0 0 0 1 .8h8.9a1 1 0 0 0 1-.8L20 7H7.2"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Gross Sales</div>
                <div class="db-stat-value total_sell"></div>
                <div class="db-stat-sub">Revenue collected</div>
            </div>
        </div>

        {{-- Net Profit --}}
        <div class="db-stat">
            <div class="db-stat-icon green">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19h16"></path>
                    <path d="M6 15l4-4 3 3 5-6"></path>
                    <path d="M18 8h-3"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Net Earnings @show_tooltip(__('lang_v1.net_home_tooltip'))</div>
                <div class="db-stat-value net"></div>
                <div class="db-stat-sub">After expenses</div>
            </div>
        </div>

        {{-- Invoice Due --}}
        <div class="db-stat">
            <div class="db-stat-icon amber">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 3H7a2 2 0 0 0-2 2v14l3-1.5L12 19l4-1.5 3 1.5V8z"></path>
                    <path d="M14 3v5h5"></path>
                    <path d="M9 11h6"></path>
                    <path d="M9 14h4"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Receivables Due</div>
                <div class="db-stat-value invoice_due"></div>
                <div class="db-stat-sub">Pending customer dues</div>
            </div>
        </div>

        {{-- Sell Returns --}}
        <div class="db-stat">
            <div class="db-stat-icon red">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 10H4V5"></path>
                    <path d="M4 10a8 8 0 1 1 3.7 6.7"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Sales Returns <i class="fa fa-info-circle text-info hover-q no-print" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="auto bottom" id="total_srp" data-value="{{ __('lang_v1.total_sell_return') }}-{{ __('lang_v1.total_sell_return_paid') }}" data-content="" data-html="true" data-trigger="hover"></i></div>
                <div class="db-stat-value total_sell_return"></div>
                <div class="db-stat-sub">Refunded sales</div>
            </div>
        </div>
    </div>{{-- /.db-stats --}}
    @endif
    @if (auth()->user()->can('dashboard.data'))
    {{-- ── PURCHASE STAT CARDS ── --}}
    <div class="db-stats db-stats-secondary db-stats-mobile-2col">
        {{-- Total Purchase --}}
        <div class="db-stat">
            <div class="db-stat-icon cyan">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 4h13v11H1z"></path>
                    <path d="M14 8h4l3 3v4h-7z"></path>
                    <circle cx="6" cy="18" r="2"></circle>
                    <circle cx="18" cy="18" r="2"></circle>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Total Purchases</div>
                <div class="db-stat-value total_purchase"></div>
                <div class="db-stat-sub">Stock procurement</div>
            </div>
        </div>
        {{-- Purchase Due --}}
        <div class="db-stat">
            <div class="db-stat-icon amber tone-pay">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10.3 3.9 1.8 18.2a1.2 1.2 0 0 0 1 1.8h18.4a1.2 1.2 0 0 0 1-1.8L13.7 3.9a2 2 0 0 0-3.4 0z"></path>
                    <path d="M12 9v4"></path>
                    <path d="M12 17h.01"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Payables Due</div>
                <div class="db-stat-value purchase_due"></div>
                <div class="db-stat-sub">Supplier outstanding</div>
            </div>
        </div>
        {{-- Purchase Return --}}
        <div class="db-stat">
            <div class="db-stat-icon red">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M10 8 5 12l5 4"></path>
                    <path d="M5 12h8a5 5 0 0 1 5 5v1"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Purchase Returns <i class="fa fa-info-circle text-info hover-q no-print" aria-hidden="true" data-container="body" data-toggle="popover" data-placement="auto bottom" id="total_prp" data-value="{{ __('lang_v1.total_purchase_return') }}-{{ __('lang_v1.total_purchase_return_paid') }}" data-content="" data-html="true" data-trigger="hover"></i></div>
                <div class="db-stat-value total_purchase_return"></div>
                <div class="db-stat-sub">Returned to suppliers</div>
            </div>
        </div>
        {{-- Expenses --}}
        <div class="db-stat">
            <div class="db-stat-icon purple">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M7 3h10a1 1 0 0 1 1 1v17l-3-1.7L12 21l-3-1.7L6 21V4a1 1 0 0 1 1-1z"></path>
                    <path d="M9 8h6"></path>
                    <path d="M9 12h6"></path>
                    <path d="M9 16h4"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Operating Expense</div>
                <div class="db-stat-value total_expense"></div>
                <div class="db-stat-sub">Overall spending</div>
            </div>
        </div>
    </div>
    <div class="db-stats db-stats-secondary db-stats-mobile-2col db-stats-two">
        <div class="db-stat">
            <div class="db-stat-icon yellow">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="14" rx="2"></rect>
                    <path d="M3 10h18"></path>
                    <path d="M7 15h4"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Total Cheques Received</div>
                <div class="db-stat-value total_cheque_received">{{ session('currency.symbol', 'Rs') }} 0.00</div>
                <div class="db-stat-sub">Cheque collections</div>
            </div>
        </div>
        <div class="db-stat">
            <div class="db-stat-icon yellow">
                <svg class="db-stat-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="6" width="18" height="12" rx="2"></rect>
                    <circle cx="12" cy="12" r="2.5"></circle>
                    <path d="M7 12h.01M17 12h.01"></path>
                </svg>
            </div>
            <div>
                <div class="db-stat-label">Total Cash Received</div>
                <div class="db-stat-value total_cash_received">{{ session('currency.symbol', 'Rs') }} 0.00</div>
                <div class="db-stat-sub">Cash collections</div>
            </div>
        </div>
    </div>
    @endif

    {{-- Main menu shortcuts with boxed icons --}}
    @include('home.partials.quick_menu')

    {{-- ── MAIN WIDGET GRID ── --}}
    <div class="db-content">
                @if (!empty($ai_insights))
                    @include('ai.dashboard_insights')
                @endif
                @if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view'))
                    @if (!empty($all_locations))
                        <div class="db-card full db-chart-card">
                            <div class="db-card-header">
                                <div class="db-card-title">
                                    <div class="db-card-title-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-chart-line"></i></div>
                                    {{ __('home.sells_last_30_days') }}
                                </div>
                                <span class="db-chart-badge" style="background:#dbeafe;color:#2563eb;">Last 30 Days</span>
                            </div>
                            <div class="db-card-body db-chart-body">
                                {!! $sells_chart_1->container() !!}
                            </div>
                        </div>
                    @endif

                    {{-- @if (!empty($widgets['after_sales_last_30_days']))
                        @foreach ($widgets['after_sales_last_30_days'] as $widget)
                            {!! $widget !!}
                        @endforeach
                    @endif --}}
                    @if (!empty($all_locations))
                        <div class="db-card full db-chart-card">
                            <div class="db-card-header">
                                <div class="db-card-title">
                                    <div class="db-card-title-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-chart-area"></i></div>
                                    {{ __('home.sells_current_fy') }}
                                </div>
                                <span class="db-chart-badge" style="background:#dcfce7;color:#16a34a;">Current Year</span>
                            </div>
                            <div class="db-card-body db-chart-body">
                                {!! $sells_chart_2->container() !!}
                            </div>
                        </div>
                    @endif
                @endif
                {{-- @if (!empty($widgets['after_sales_current_fy']))
                    @foreach ($widgets['after_sales_current_fy'] as $widget)
                        {!! $widget !!}
                    @endforeach
                @endif --}}
                @if (auth()->user()->can('sell.view') || auth()->user()->can('direct_sell.view'))
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-exclamation-circle"></i></div>
                                {{ __('lang_v1.sales_payment_dues') }} @show_tooltip(__('lang_v1.tooltip_sales_payment_dues'))
                            </div>
                            <div style="min-width:160px;">
                                {!! Form::select('sales_payment_dues_location', $all_locations, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'sales_payment_dues_location',
                                ]) !!}
                            </div>
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped" id="sales_payment_dues_table" style="width:100%;">
                                    <thead><tr>
                                        <th>@lang('contact.customer')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('home.due_amount')</th>
                                        <th class="not-export">@lang('messages.action')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                @can('purchase.view')
                    <div class="db-card">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-truck"></i></div>
                                {{ __('lang_v1.purchase_payment_dues') }} @show_tooltip(__('tooltip.payment_dues'))
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('purchase_payment_dues_location', $all_locations, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'purchase_payment_dues_location',
                                ]) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped" id="purchase_payment_dues_table" style="width:100%;">
                                    <thead><tr>
                                        <th>@lang('purchase.supplier')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('home.due_amount')</th>
                                        <th class="not-export">@lang('messages.action')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endcan
                @can('stock_report.view')
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#fef3c7;color:#d97706;"><i class="fas fa-boxes"></i></div>
                                {{ __('home.product_stock_alert') }} @show_tooltip(__('tooltip.product_stock_alert'))
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('stock_alert_location', $all_locations, null, [
                                    'class' => 'form-control select2',
                                    'placeholder' => __('lang_v1.select_location'),
                                    'id' => 'stock_alert_location',
                                ]) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped" id="stock_alert_table" style="width:100%;">
                                    <thead><tr>
                                        <th>@lang('sale.product')</th>
                                        <th>@lang('business.location')</th>
                                        <th>@lang('report.current_stock')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                    @if (session('business.enable_product_expiry') == 1)
                        <div class="db-card">
                            <div class="db-card-header">
                                <div class="db-card-title">
                                    <div class="db-card-title-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-calendar-times"></i></div>
                                    {{ __('home.stock_expiry_alert') }} @show_tooltip(__('tooltip.stock_expiry_alert', ['days' => session('business.stock_expiry_alert_days', 30)]))
                                </div>
                            </div>
                            <div class="db-card-body" style="padding-top:0;">
                                <input type="hidden" id="stock_expiry_alert_days" value="{{ \Carbon::now()->addDays(session('business.stock_expiry_alert_days', 30))->format('Y-m-d') }}">
                                <div style="overflow-x:auto;">
                                    <table class="table table-bordered table-striped" id="stock_expiry_alert_table">
                                        <thead><tr>
                                            <th>@lang('business.product')</th>
                                            <th>@lang('business.location')</th>
                                            <th>@lang('report.stock_left')</th>
                                            <th>@lang('product.expires_in')</th>
                                        </tr></thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @endif
                @endcan
                @if (auth()->user()->can('so.view_all') || auth()->user()->can('so.view_own'))
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#ede9fe;color:#7c3aed;"><i class="fas fa-clipboard-list"></i></div>
                                {{ __('lang_v1.sales_order') }}
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('so_location', $all_locations, null, ['class'=>'form-control select2','placeholder'=>__('lang_v1.select_location'),'id'=>'so_location']) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped ajax_view" id="sales_order_table">
                                    <thead><tr>
                                        <th class="not-export">@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('restaurant.order_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        <th>@lang('lang_v1.quantity_remaining')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                @if (!empty($common_settings['enable_purchase_requisition']) && (auth()->user()->can('purchase_requisition.view_all') || auth()->user()->can('purchase_requisition.view_own')))
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#cffafe;color:#0891b2;"><i class="fas fa-file-alt"></i></div>
                                @lang('lang_v1.purchase_requisition')
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('pr_location', $all_locations, null, ['class'=>'form-control select2','placeholder'=>__('lang_v1.select_location'),'id'=>'pr_location']) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped ajax_view" id="purchase_requisition_table" style="width:100%;">
                                    <thead><tr>
                                        <th class="not-export">@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('purchase.location')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.required_by_date')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif

                @if (!empty($common_settings['enable_purchase_order']) && (auth()->user()->can('purchase_order.view_all') || auth()->user()->can('purchase_order.view_own')))
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-shopping-bag"></i></div>
                                @lang('lang_v1.purchase_order')
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('po_location', $all_locations, null, ['class'=>'form-control select2','placeholder'=>__('lang_v1.select_location'),'id'=>'po_location']) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped ajax_view" id="purchase_order_table" style="width:100%;">
                                    <thead><tr>
                                        <th class="not-export">@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('purchase.ref_no')</th>
                                        <th>@lang('purchase.location')</th>
                                        <th>@lang('purchase.supplier')</th>
                                        <th>@lang('sale.status')</th>
                                        <th>@lang('lang_v1.quantity_remaining')</th>
                                        <th>@lang('lang_v1.added_by')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                @if (auth()->user()->can('access_pending_shipments_only') || auth()->user()->can('access_shipping') || auth()->user()->can('access_own_shipping'))
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-shipping-fast"></i></div>
                                @lang('lang_v1.pending_shipments')
                            </div>
                            @if (count($all_locations) > 1)
                            <div style="min-width:160px;">
                                {!! Form::select('pending_shipments_location', $all_locations, null, ['class'=>'form-control select2','placeholder'=>__('lang_v1.select_location'),'id'=>'pending_shipments_location']) !!}
                            </div>
                            @endif
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped ajax_view" id="shipments_table">
                                    <thead><tr>
                                        <th class="not-export">@lang('messages.action')</th>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('sale.invoice_no')</th>
                                        <th>@lang('sale.customer_name')</th>
                                        <th>@lang('lang_v1.contact_no')</th>
                                        <th>@lang('sale.location')</th>
                                        <th>@lang('lang_v1.shipping_status')</th>
                                        @foreach(['custom_field_1','custom_field_2','custom_field_3','custom_field_4','custom_field_5'] as $cf)
                                            @if(!empty($custom_labels['shipping'][$cf]))<th>{{ $custom_labels['shipping'][$cf] }}</th>@endif
                                        @endforeach
                                        <th>@lang('sale.payment_status')</th>
                                        <th>@lang('restaurant.service_staff')</th>
                                    </tr></thead>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)
                    <div class="db-card full">
                        <div class="db-card-header">
                            <div class="db-card-title">
                                <div class="db-card-title-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-coins"></i></div>
                                @lang('lang_v1.payment_recovered_today')
                            </div>
                        </div>
                        <div class="db-card-body" style="padding-top:0;">
                            <div style="overflow-x:auto;">
                                <table class="table table-bordered table-striped" id="cash_flow_table">
                                    <thead><tr>
                                        <th>@lang('messages.date')</th>
                                        <th>@lang('account.account')</th>
                                        <th>@lang('lang_v1.description')</th>
                                        <th>@lang('lang_v1.payment_method')</th>
                                        <th>@lang('lang_v1.payment_details')</th>
                                        <th>@lang('account.credit')</th>
                                        <th>@lang('lang_v1.account_balance') @show_tooltip(__('lang_v1.account_balance_tooltip'))</th>
                                        <th>@lang('lang_v1.total_balance') @show_tooltip(__('lang_v1.total_balance_tooltip'))</th>
                                    </tr></thead>
                                    <tfoot>
                                        <tr class="bg-gray font-17 footer-total text-center">
                                            <td colspan="5"><strong>@lang('sale.total'):</strong></td>
                                            <td class="footer_total_credit"></td>
                                            <td colspan="2"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                @endif
                {{-- @if (!empty($widgets['after_dashboard_reports']))
                    @foreach ($widgets['after_dashboard_reports'] as $widget)
                        {!! $widget !!}
                    @endforeach
                @endif --}}

                <style>
                @keyframes blink-red {
                    0%, 100% { background-color: #dc2626; }
                    50% { background-color: #991b1b; }
                }
                .blink-red {
                    animation: blink-red 1s infinite;
                }
                </style>

                {{-- Cheque Reminder Widget --}}
                @if(!empty($due_cheques) && $due_cheques->count() > 0)
                <div class="db-card full">
                    <div class="db-card-header">
                        <div class="db-card-title">
                            <div class="db-card-title-icon" style="background:rgba(245,197,24,.12);color:#f5c518;"><i class="fas fa-money-check-alt"></i></div>
                            Cheque Reminders
                            @php
                                $overdue_count = $due_cheques->where('due_status', 'overdue')->count();
                                $due_tomorrow_count = $due_cheques->where('due_status', 'due_tomorrow')->count();
                                $due_today_count = $due_cheques->where('due_status', 'due_today')->count();
                                $coming_count = $due_cheques->where('due_status', 'due_soon')->count();
                            @endphp
                            @if($overdue_count > 0)
                                <span class="badge blink-red" style="margin-left:8px;">{{ $overdue_count }} OVERDUE</span>
                            @elseif($due_today_count > 0)
                                <span class="badge" style="background:#f59e0b;color:#fff;margin-left:8px;">{{ $due_today_count }} DUE TODAY</span>
                            @elseif($due_tomorrow_count > 0)
                                <span class="badge" style="background:#f5c518;color:#111;margin-left:8px;">{{ $due_tomorrow_count }} DUE TOMORROW</span>
                            @elseif($coming_count > 0)
                                <span class="badge" style="background:#3b82f6;color:#fff;margin-left:8px;">{{ $coming_count }} COMING SOON</span>
                            @else
                                <span class="badge badge-danger" style="background:#dc2626;color:#fff;margin-left:8px;">{{ $due_cheques->count() }}</span>
                            @endif
                        </div>
                        <a href="{{ route('cheque.index') }}" class="btn btn-sm btn-primary"><i class="fas fa-arrow-right"></i> View All</a>
                    </div>
                    <div class="db-card-body">
                        <p style="margin:0 0 12px;color:#9ca3af;font-size:13px;">
                            <i class="fas fa-bell" style="color:#f5c518;"></i>
                            Remind customers before the cheque date — use <strong>Remind</strong> to send WhatsApp.
                        </p>
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Cheque #</th>
                                    <th>Cheque Name</th>
                                    <th>Bank</th>
                                    <th>Contact</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Due Date</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($due_cheques as $cheque)
                                <tr @if($cheque->due_status == 'overdue') style="background-color: #fef2f2;" @endif>
                                    <td><strong>{{ $cheque->cheque_number }}</strong></td>
                                    <td>{{ $cheque->cheque_name ?? '-' }}</td>
                                    <td>{{ $cheque->cheque_bank_name ?? '-' }}</td>
                                    <td>{{ $cheque->contact_name ?? 'N/A' }}</td>
                                    <td>
                                        @if($cheque->cheque_type == 'outgoing')
                                            <span class="label label-info">Pay Supplier</span>
                                        @else
                                            <span class="label label-default">Receive</span>
                                        @endif
                                    </td>
                                    <td class="text-right">Rs {{ number_format($cheque->amount, 2) }}</td>
                                    <td>{{ $cheque->cheque_date ? date('d-m-Y', strtotime($cheque->cheque_date)) : '-' }}</td>
                                    <td>
                                        @if($cheque->due_status == 'overdue')
                                            <span class="badge blink-red"><i class="fas fa-exclamation-circle"></i> OVERDUE</span>
                                        @elseif($cheque->due_status == 'due_today')
                                            <span class="badge" style="background:#f59e0b;"><i class="fas fa-bell"></i> DUE TODAY</span>
                                        @elseif($cheque->due_status == 'due_tomorrow')
                                            <span class="badge" style="background:#f5c518;color:#111;"><i class="fas fa-clock"></i> DUE TOMORROW</span>
                                        @else
                                            <span class="badge" style="background:#3b82f6;"><i class="fas fa-bell"></i> COMING SOON</span>
                                        @endif
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-xs btn-warning btn-dashboard-cheque-remind" data-id="{{ $cheque->id }}" title="Remind customer before due date">
                                            <i class="fas fa-bell"></i> Remind
                                        </button>
                                        <a href="{{ route('cheque.index') }}" class="btn btn-xs btn-primary">View</a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background:#f4f4f4;font-weight:bold;">
                                    <td colspan="4" class="text-right">Total Pending:</td>
                                    <td class="text-right">Rs {{ number_format($due_cheques->sum('amount'), 2) }}</td>
                                    <td colspan="3"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                @endif

            </div>{{-- /.db-content --}}

</div>{{-- /.db-page --}}
@endsection


<div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>
<div class="modal fade edit_pso_status_modal" tabindex="-1" role="dialog"></div>
<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
</div>

@section('css')
<style>
    /* ── Dashboard page variables (retail charcoal + yellow) ── */
    :root {
        --db-bg: radial-gradient(1100px 600px at 12% -8%, rgba(245, 197, 24, 0.08) 0%, transparent 55%), linear-gradient(180deg, #050505 0%, #0a0a0a 45%, #111111 100%);
        --db-card: rgba(17, 17, 17, 0.92);
        --db-card-solid: #111111;
        --db-card-soft: #141414;
        --db-border: rgba(42, 42, 42, 0.95);
        --db-radius: 18px;
        --db-shadow: 0 16px 40px rgba(0, 0, 0, 0.55);
        --db-shadow-hover: 0 24px 48px rgba(0, 0, 0, 0.65);
        --db-text-strong: #f5f5f5;
        --db-text: #d4d4d4;
        --db-text-muted: #9ca3af;
        --db-sub: #737373;
        --db-accent: #f5c518;
        --db-accent-2: #e6b800;
        --db-accent-soft: rgba(245, 197, 24, 0.12);
        --db-green: #4ade80;
        --db-amber: #f5c518;
        --db-red: #f87171;
        --db-cyan: #a3a3a3;
        --db-purple: #d4d4d4;
        --db-info: #60a5fa;
        --db-ink: #f8fafc;
        --db-hero-bg: radial-gradient(520px 240px at 88% 8%, rgba(245, 197, 24, 0.14), transparent 72%), linear-gradient(132deg, #050505 0%, #111111 55%, #1a1a1a 100%);
        --db-hero-muted: rgba(226, 232, 240, .8);
        --db-hero-sub: rgba(203, 213, 225, .8);
        --db-line: #2a2a2a;
        --db-stat-bg: linear-gradient(160deg, rgba(20, 20, 20, 0.95), rgba(17, 17, 17, 0.88));
        --db-card-bg: linear-gradient(160deg, rgba(20, 20, 20, 0.95), rgba(17, 17, 17, 0.9));
        --db-chip-bg: rgba(20, 20, 20, 0.75);
        --db-date-bg: rgba(255,255,255,.15);
        --db-date-border: rgba(255,255,255,.2);
        --db-date-text: #fff;
        --db-filter-hover: #fff;
        --db-green-soft: rgba(74, 222, 128, 0.12);
        --db-red-soft: rgba(248, 113, 113, 0.12);
        --db-cyan-soft: rgba(163, 163, 163, 0.12);
        --db-purple-soft: rgba(212, 212, 212, 0.1);
        --db-kpi-bg: #141414;
        --db-panel-bg: #121212;
        --db-table-head: #111111;
        --db-table-cell: #141414;
        --db-table-hover: #1a1a1a;
        --db-table-foot: #111111;
        --db-input-bg: #141414;
        --db-btn-bg: #141414;
        --db-btn-text: #e5e5e5;
        --db-alert-bg: rgba(69, 10, 10, 0.75);
        --db-alert-text: #f5f5f5;
        --db-cheque-bg: linear-gradient(135deg, #111111 0%, #1a1a1a 100%);
        --db-cheque-red: #fca5a5;
        --db-cheque-amber: #f5c518;
        --db-cheque-green: #86efac;
        --db-cheque-sub: rgba(255,255,255,.55);
        --db-chart-fill: #9ca3af;
        --db-chart-stroke: rgba(42, 42, 42, 0.9);
        --db-empty-bg: rgba(15, 23, 42, 0.86);
        --db-empty-text: #e2e8f0;
        --db-header-bar: linear-gradient(to right, rgba(20, 20, 20, 0.95), rgba(17, 17, 17, 0.5));
        --db-quick-bg: #111111;
        --db-quick-btn: #0a0a0a;
        --db-quick-btn-hover: #0f0f0f;
    }

    .select2-container { width: 100% !important; }

    /* Page bg */
    .db-page {
        background: var(--db-bg);
        min-height: 100vh;
        color: var(--db-ink);
    }
    .db-page,
    .db-page p,
    .db-page span,
    .db-page div,
    .db-page small,
    .db-page strong,
    .db-page label {
        color: var(--db-ink);
    }

    /* ── Hero banner ── */
    .db-hero {
        background: var(--db-hero-bg);
        border-bottom: 1px solid var(--db-line);
        padding: 28px 28px 94px;
        position: relative;
        overflow: hidden;
    }
    .db-hero::before {
        content: '';
        position: absolute; top: -80px; right: -80px;
        width: 360px; height: 360px; border-radius: 50%;
        background: rgba(245, 197, 24, .06);
    }
    .db-hero::after {
        content: '';
        position: absolute; bottom: -120px; left: 20%;
        width: 500px; height: 300px; border-radius: 50%;
        background: rgba(245, 197, 24, .05);
    }
    .db-hero-inner {
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 16px;
        position: relative;
        z-index: 1;
    }
    .db-hero-greeting {
        color: var(--db-hero-muted);
        font-size: 0.82rem;
        font-weight: 500;
        letter-spacing: .04em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .db-hero h1 {
        color: var(--db-ink) !important;
        font-size: clamp(1.4rem, 3vw, 2rem) !important;
        font-weight: 800 !important;
        letter-spacing: -.03em;
        margin: 0 0 4px !important;
        line-height: 1.2;
    }
    .db-hero-sub {
        color: var(--db-hero-sub);
        font-size: 0.82rem;
        margin: 0;
    }
    .db-hero-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .db-hero-date {
        background: var(--db-date-bg);
        border: 1px solid var(--db-date-border);
        color: var(--db-date-text);
        border-radius: 10px;
        padding: 8px 16px;
        font-size: 0.82rem;
        font-weight: 600;
        font-variant-numeric: tabular-nums;
    }
    .db-filter-btn {
        background: var(--db-accent-soft) !important;
        border: 1px solid rgba(245, 197, 24, 0.35) !important;
        color: var(--db-accent) !important;
        border-radius: 10px !important;
        padding: 9px 16px !important;
        font-size: 0.8rem !important;
        font-weight: 700 !important;
        transition: all .15s !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .db-filter-btn:hover {
        background: rgba(245, 197, 24, 0.2) !important;
        color: var(--db-filter-hover) !important;
        transform: translateY(-1px);
        box-shadow: 0 8px 20px rgba(245, 197, 24, 0.2);
    }
    .db-hero-live {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-top: 12px;
    }
    .db-hero-quote {
        width: 100%;
        padding: 10px 12px;
        border-radius: 12px;
        border: 1px solid var(--db-line);
        background: var(--db-chip-bg);
        color: var(--db-ink);
        font-size: 0.8rem;
        line-height: 1.35;
        font-weight: 500;
    }
    .db-hero-chip {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 7px 10px;
        border-radius: 999px;
        border: 1px solid var(--db-line);
        background: var(--db-chip-bg);
        color: var(--db-text);
        font-size: 0.75rem;
        line-height: 1;
        white-space: nowrap;
    }
    .db-hero-chip-label {
        color: var(--db-accent);
        font-weight: 700;
        letter-spacing: .04em;
        text-transform: uppercase;
        font-size: 0.64rem;
    }
    .db-led-time {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        margin-left: 4px;
    }
    .db-led-digit {
        width: 24px;
        height: 34px;
        border-radius: 4px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(180deg, #141414, #050505);
        border: 1px solid rgba(245, 197, 24, 0.25);
        color: var(--db-accent) !important;
        font-size: 1.15rem;
        font-weight: 800;
        font-family: "Courier New", monospace;
        letter-spacing: 0;
        text-shadow: 0 0 10px rgba(245, 197, 24, 0.55), 0 0 20px rgba(245, 197, 24, 0.25);
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.04), 0 0 12px rgba(245, 197, 24, 0.12);
    }
    .db-led-colon {
        color: var(--db-accent);
        font-size: 1.1rem;
        font-weight: 800;
        margin: 0 1px;
        text-shadow: 0 0 8px rgba(245, 197, 24, 0.45);
    }

    /* ── Floating stat cards ── */
    .db-stats {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        padding: 0 28px;
        margin-top: 0;
        position: relative;
        z-index: 5;
    }
    .db-stats-primary {
        margin-top: -52px;
    }
    @media (max-width: 1100px) { .db-stats { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px)  { .db-stats { grid-template-columns: 1fr; padding: 0 14px; } }

    .db-stat {
        background: var(--db-stat-bg);
        backdrop-filter: blur(10px);
        border-radius: var(--db-radius);
        box-shadow: var(--db-shadow);
        padding: 20px 22px;
        display: flex;
        align-items: flex-start;
        gap: 14px;
        transition: box-shadow .2s, transform .2s, border-color .2s;
        border: 1px solid var(--db-border);
        min-height: 112px;
    }
    .db-stats-secondary {
        margin-top: 20px;
    }
    .db-stats-two {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        margin-bottom: 10px;
    }
    .db-stat:hover {
        box-shadow: var(--db-shadow-hover);
        transform: translateY(-3px);
        border-color: rgba(245, 197, 24, 0.28);
    }
    .db-stat-icon {
        width: 46px; height: 46px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
        box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .16), 0 8px 18px rgba(2, 6, 23, .28);
    }
    .db-stat-svg {
        width: 22px;
        height: 22px;
        display: block;
    }
    .db-stat-icon.blue   { background: var(--db-accent-soft); color: var(--db-accent) !important; }
    .db-stat-icon.yellow { background: var(--db-accent-soft); color: var(--db-accent) !important; }
    .db-stat-icon.green  { background: var(--db-green-soft); color: var(--db-green) !important; }
    .db-stat-icon.amber  { background: var(--db-accent-soft); color: var(--db-amber) !important; }
    .db-stat-icon.red    { background: var(--db-red-soft); color: var(--db-red) !important; }
    .db-stat-icon.cyan   { background: var(--db-cyan-soft); color: var(--db-cyan) !important; }
    .db-stat-icon.purple { background: var(--db-purple-soft); color: var(--db-purple) !important; }
    .db-page .db-stat-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--db-text-muted) !important;
        text-transform: uppercase;
        letter-spacing: .06em;
        margin-bottom: 5px;
    }
    .db-page .db-stat-value {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--db-ink) !important;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }
    .db-page .db-stat-sub {
        font-size: 0.72rem;
        color: var(--db-sub) !important;
        margin-top: 4px;
    }

    /* ── Main content area ── */
    .db-content {
        padding: 24px 28px 40px;
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }
    @media (max-width: 900px) { .db-content { grid-template-columns: 1fr; padding: 16px 14px 32px; } }

    .db-card {
        background: var(--db-card-bg);
        backdrop-filter: blur(10px);
        border-radius: var(--db-radius);
        box-shadow: var(--db-shadow);
        overflow: hidden;
        border: 1px solid var(--db-border);
        transition: box-shadow .2s, border-color .2s;
    }
    .db-card:hover { box-shadow: var(--db-shadow-hover); }
    .db-card.full { grid-column: 1 / -1; }
    .db-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--db-line);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 8px;
    }
    .db-card-title {
        font-size: 0.92rem;
        font-weight: 700;
        color: var(--db-ink);
        display: flex;
        align-items: center;
        gap: 9px;
    }
    .db-card-title-icon {
        width: 30px; height: 30px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
    }
    .db-card-body { padding: 16px 20px; }

    /* ── Chart cards ── */
    .db-chart-card .db-card-header {
        background: var(--db-header-bar);
    }
    .db-chart-badge {
        display: inline-flex;
        align-items: center;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: .04em;
    }
    .db-chart-body {
        padding: 8px 4px 4px !important;
    }

    /* ── Local AI Insights card ── */
    .db-ai-card {
        border-color: rgba(245, 197, 24, 0.28);
        background:
            radial-gradient(680px 220px at 100% 0%, rgba(245, 197, 24, 0.1), transparent 60%),
            var(--db-card);
    }
    .db-ai-header {
        background: linear-gradient(90deg, rgba(245, 197, 24, 0.1), transparent 55%);
        align-items: flex-start;
    }
    .db-ai-icon {
        background: linear-gradient(145deg, #f5c518, #d4a012) !important;
        color: #111 !important;
        box-shadow: 0 4px 14px rgba(245, 197, 24, 0.35);
    }
    .db-ai-title-row {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
        font-weight: 800;
        color: var(--db-ink);
    }
    .db-ai-badge {
        font-size: 0.62rem;
        font-weight: 800;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        color: #111;
        background: #f5c518;
        border-radius: 999px;
        padding: 2px 8px;
    }
    .db-ai-headline {
        margin-top: 3px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #a3a3a3;
    }
    .db-ai-actions {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
    }
    .db-ai-date {
        font-size: 0.72rem;
        color: var(--db-text-muted);
        font-weight: 600;
    }
    .db-ai-link {
        font-size: 0.75rem;
        font-weight: 800;
        color: var(--db-amber) !important;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .db-ai-link:hover { filter: brightness(1.1); }
    .db-ai-body { padding-top: 14px !important; }
    .db-ai-kpis {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 10px;
        margin-bottom: 14px;
    }
    .db-ai-kpi {
        background: var(--db-kpi-bg);
        border: 1px solid var(--db-line);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .db-ai-kpi-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: var(--db-text-muted) !important;
        margin-bottom: 4px;
    }
    .db-ai-kpi-val {
        font-size: 1.05rem;
        font-weight: 800;
        color: var(--db-ink) !important;
        line-height: 1.2;
    }
    .db-ai-kpi-val.warn { color: var(--db-amber) !important; }
    .db-ai-kpi-sub {
        margin-top: 4px;
        font-size: 0.7rem;
        color: var(--db-sub) !important;
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        align-items: center;
    }
    .db-ai-delta {
        font-weight: 800;
        font-size: 0.68rem;
        padding: 1px 6px;
        border-radius: 999px;
    }
    .db-ai-delta.up { background: var(--db-green-soft); color: var(--db-green) !important; }
    .db-ai-delta.down { background: var(--db-red-soft); color: var(--db-red) !important; }
    .db-ai-grid {
        display: grid;
        grid-template-columns: 1.15fr 0.85fr;
        gap: 12px;
    }
    .db-ai-panel {
        background: var(--db-panel-bg);
        border: 1px solid var(--db-line);
        border-radius: 12px;
        padding: 12px 14px;
    }
    .db-ai-panel-title {
        font-size: 0.78rem;
        font-weight: 800;
        color: var(--db-text) !important;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 7px;
    }
    .db-ai-panel-title i { color: #f5c518; }
    .db-ai-tips {
        list-style: none;
        margin: 0;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .db-ai-tips li {
        display: flex;
        gap: 10px;
        align-items: flex-start;
        font-size: 0.8rem;
        line-height: 1.45;
        color: #d4d4d4 !important;
        padding: 8px 10px;
        border-radius: 10px;
        background: #181818;
        border: 1px solid #262626;
    }
    .db-ai-tip-icon {
        width: 26px; height: 26px;
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        font-size: 0.72rem;
        background: rgba(245, 197, 24, 0.12);
        color: #f5c518;
    }
    .db-ai-tips li.tone-red .db-ai-tip-icon { background: var(--db-red-soft); color: var(--db-red) !important; }
    .db-ai-tips li.tone-green .db-ai-tip-icon { background: var(--db-green-soft); color: var(--db-green) !important; }
    .db-ai-tips li.tone-cyan .db-ai-tip-icon { background: var(--db-cyan-soft); color: var(--db-cyan) !important; }
    .db-ai-products {
        list-style: none;
        margin: 0;
        padding: 0;
        counter-reset: ai-prod;
    }
    .db-ai-products li {
        counter-increment: ai-prod;
        display: flex;
        justify-content: space-between;
        gap: 10px;
        align-items: baseline;
        padding: 7px 0;
        border-bottom: 1px dotted var(--db-line);
        font-size: 0.78rem;
    }
    .db-ai-products li:last-child { border-bottom: none; }
    .db-ai-products .name {
        font-weight: 700;
        color: var(--db-ink) !important;
        min-width: 0;
    }
    .db-ai-products .name::before {
        content: counter(ai-prod) ". ";
        color: #f5c518;
        font-weight: 800;
    }
    .db-ai-products .meta {
        color: var(--db-text-muted) !important;
        white-space: nowrap;
        font-weight: 600;
        font-size: 0.72rem;
    }
    .db-ai-empty {
        margin: 0;
        font-size: 0.78rem;
        color: var(--db-text-muted) !important;
    }
    .db-ai-chips {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }
    .db-ai-chip {
        font-size: 0.7rem;
        font-weight: 700;
        color: #fecaca !important;
        background: rgba(248, 113, 113, 0.12);
        border: 1px solid rgba(248, 113, 113, 0.28);
        border-radius: 999px;
        padding: 3px 9px;
    }
    .db-ai-chip em {
        font-style: normal;
        color: #f87171 !important;
        margin-left: 4px;
    }
    .db-ai-foot {
        margin-top: 12px;
        font-size: 0.68rem;
        color: #737373 !important;
        font-weight: 600;
        letter-spacing: 0.02em;
    }
    .db-ai-cheques {
        list-style: none;
        margin: 0 0 8px;
        padding: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    .db-ai-cheques li {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        padding: 7px 9px;
        border-radius: 10px;
        background: #181818;
        border: 1px solid #262626;
        font-size: 0.74rem;
    }
    .db-ai-cheques li.overdue {
        border-color: rgba(248, 113, 113, 0.35);
        background: rgba(248, 113, 113, 0.06);
    }
    .db-ai-chq-type {
        font-size: 0.62rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 2px 7px;
        border-radius: 999px;
    }
    .db-ai-chq-type.recv {
        background: rgba(74, 222, 128, 0.14);
        color: var(--db-green) !important;
    }
    .db-ai-chq-type.pay {
        background: rgba(96, 165, 250, 0.14);
        color: var(--db-info) !important;
    }
    .db-ai-chq-main {
        flex: 1;
        min-width: 0;
        color: var(--db-text) !important;
    }
    .db-ai-chq-main strong { color: var(--db-ink) !important; }
    .db-ai-chq-main em {
        font-style: normal;
        color: var(--db-text-muted) !important;
        margin-left: 6px;
    }
    .db-ai-chq-meta {
        color: #a3a3a3 !important;
        font-weight: 700;
        white-space: nowrap;
    }
    .db-ai-chq-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 0.72rem;
        font-weight: 700;
        color: var(--db-amber) !important;
        margin-top: 4px;
    }
    .db-ai-chq-link:hover { filter: brightness(1.1); }
    @media (max-width: 900px) {
        .db-ai-kpis { grid-template-columns: 1fr 1fr; }
        .db-ai-grid { grid-template-columns: 1fr; }
    }
    @media (max-width: 520px) {
        .db-ai-kpis { grid-template-columns: 1fr; }
    }
    .db-chart-body {
        min-height: 320px;
    }
    .db-chart-body > div,
    .db-chart-body > figure {
        min-height: 300px !important;
    }
    /* Hide Highcharts default border and make chart bg transparent */
    .db-chart-body .highcharts-background { fill: transparent !important; }
    .db-chart-body text,
    .db-chart-body .highcharts-axis-labels text { fill: var(--db-chart-fill) !important; }
    .db-chart-body .highcharts-axis-line,
    .db-chart-body .highcharts-grid-line,
    .db-chart-body .highcharts-tick { stroke: var(--db-chart-stroke) !important; }
    .db-chart-body .highcharts-root { overflow: visible; }
    .db-chart-body .highcharts-container {
        border-radius: 0 0 var(--db-radius) var(--db-radius);
    }

    /* ── Cheque reminder banner on dashboard ── */
    .db-cheque-banner {
        grid-column: 1 / -1;
        background: var(--db-cheque-bg);
        border-radius: var(--db-radius);
        padding: 20px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        flex-wrap: wrap;
        border: 1px solid var(--db-line);
        box-shadow: var(--db-shadow);
    }
    .db-cheque-cards { display: flex; gap: 12px; flex-wrap: wrap; flex: 1; }
    .db-cheque-card {
        border-radius: 10px;
        padding: 12px 18px;
        flex: 1;
        min-width: 150px;
    }
    .db-cheque-card.red   { background: rgba(248, 113, 113, 0.1); border: 1px solid rgba(248, 113, 113, 0.22); }
    .db-cheque-card.amber { background: rgba(245, 197, 24, 0.1); border: 1px solid rgba(245, 197, 24, 0.28); }
    .db-cheque-card.green { background: rgba(74, 222, 128, 0.1); border: 1px solid rgba(74, 222, 128, 0.22); }
    .db-cheque-card-label { font-size: 0.65rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase; margin-bottom: 4px; }
    .db-cheque-card.red   .db-cheque-card-label { color: var(--db-cheque-red) !important; }
    .db-cheque-card.amber .db-cheque-card-label { color: var(--db-cheque-amber) !important; }
    .db-cheque-card.green .db-cheque-card-label { color: var(--db-cheque-green) !important; }
    .db-page .db-cheque-card-value { font-size: 1.6rem; font-weight: 900; color: var(--db-ink) !important; line-height: 1; }
    .db-page .db-cheque-card-sub   { font-size: 0.7rem; color: var(--db-cheque-sub) !important; margin-top: 2px; }

    /* ── Location selector ── */
    .db-page .select2-container--default .select2-selection--single {
        background: var(--db-input-bg) !important;
        border: 1px solid var(--db-line) !important;
        color: var(--db-ink) !important;
        border-radius: 10px !important;
        height: 36px !important;
        line-height: 36px !important;
    }
    .db-page .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: var(--db-ink) !important;
        line-height: 34px !important;
        padding-left: 10px !important;
    }
    .db-page .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 34px !important;
    }

    .db-page .table {
        border-color: var(--db-line) !important;
        color: var(--db-ink) !important;
    }
    .db-page .table thead th {
        background: var(--db-table-head) !important;
        border-color: var(--db-line) !important;
        color: var(--db-ink) !important;
        font-weight: 700 !important;
        letter-spacing: .06em;
    }
    .db-page .table tbody td {
        background: var(--db-table-cell) !important;
        border-color: var(--db-line) !important;
        color: var(--db-text) !important;
        font-weight: 500;
    }
    .db-page .table tbody tr,
    .db-page .table tbody tr.odd,
    .db-page .table tbody tr.even {
        background: var(--db-table-cell) !important;
    }
    .db-page .dataTable tbody tr > td,
    .db-page .dataTable tbody tr.odd > td,
    .db-page .dataTable tbody tr.even > td {
        background: var(--db-table-cell) !important;
        color: var(--db-text) !important;
    }
    .db-page .table tbody tr:hover td {
        background: var(--db-table-hover) !important;
    }
    .db-page .table tfoot td {
        background: var(--db-table-foot) !important;
        border-color: var(--db-line) !important;
        color: var(--db-ink) !important;
        font-weight: 700 !important;
    }
    .db-page .dataTables_wrapper .dataTables_filter input,
    .db-page .dataTables_wrapper .dataTables_length select {
        background: var(--db-input-bg) !important;
        border: 1px solid var(--db-line) !important;
        color: var(--db-ink) !important;
    }
    .db-page .pagination > li > a,
    .db-page .pagination > li > span {
        background: var(--db-input-bg) !important;
        border-color: var(--db-line) !important;
        color: var(--db-text) !important;
    }
    .db-page .pagination > .active > a,
    .db-page .pagination > .active > span {
        background: var(--db-accent) !important;
        border-color: var(--db-accent) !important;
        color: #111 !important;
    }
    .db-page .dataTables_info,
    .db-page .dataTables_length label,
    .db-page .dataTables_filter label {
        color: var(--db-ink) !important;
        font-weight: 500;
    }
    .db-page .dataTables_empty {
        color: var(--db-empty-text) !important;
        background: var(--db-empty-bg) !important;
        border-color: rgba(148, 163, 184, 0.24) !important;
        opacity: 1 !important;
    }

    .db-page .btn,
    .db-page .btn-primary,
    .db-page .btn-default {
        border-radius: 10px !important;
        border: 1px solid rgba(148, 163, 184, 0.28) !important;
        transition: all .15s ease !important;
    }
    .db-page .btn-primary {
        background: linear-gradient(135deg, #f5c518, #e6b800) !important;
        color: #111 !important;
        border-color: rgba(245, 197, 24, 0.45) !important;
        font-weight: 700 !important;
    }
    .db-page .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 18px rgba(245, 197, 24, 0.28);
        color: #111 !important;
    }
    .db-page .btn-default {
        background: var(--db-btn-bg) !important;
        color: var(--db-btn-text) !important;
        border-color: var(--db-line) !important;
    }
    .db-page .badge,
    .db-page .label {
        font-weight: 700 !important;
        letter-spacing: .02em;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.15) !important;
    }
    .db-page .label-default { background: #262626 !important; }
    .db-page .label-info { background: rgba(245, 197, 24, 0.2) !important; color: var(--db-amber) !important; }
    .db-page .badge-danger { background: #dc2626 !important; }
    .db-page .badge-warning { background: #d97706 !important; }

    /* Cheque widget readability improvements */
    .db-page .db-card .table tbody tr td .btn-xs.btn-primary {
        background: var(--db-accent) !important;
        border-color: var(--db-accent) !important;
        color: #111 !important;
        font-weight: 700;
    }
    .db-page .db-card .table tbody tr td .btn-xs.btn-primary:hover {
        background: var(--db-accent-2) !important;
        color: #111 !important;
    }
    .db-page .db-card .table tbody tr[style*="background-color"] td {
        background: var(--db-alert-bg) !important;
        color: var(--db-alert-text) !important;
    }
    .db-page .db-card .table tbody tr td strong {
        color: var(--db-ink) !important;
    }
    .db-page .db-card-title,
    .db-page .db-card-header,
    .db-page .db-card-body,
    .db-page .db-chart-badge {
        color: var(--db-ink) !important;
    }
    .db-page .db-card-title .text-info,
    .db-page .db-card-title .fa-info-circle {
        color: var(--db-accent) !important;
    }

    .db-page .db-card-title-icon[style] {
        background: var(--db-accent-soft) !important;
        color: var(--db-accent) !important;
    }
    .db-page .db-chart-badge[style] {
        background: var(--db-accent-soft) !important;
        color: var(--db-accent) !important;
        border: 1px solid rgba(245, 197, 24, 0.28);
    }

    /* Cheque reminders card accent */
    .db-page .db-card:has(.fa-money-check-alt) .db-card-header {
        border-bottom-color: rgba(245, 197, 24, 0.2);
    }
    .db-page .db-card:has(.fa-money-check-alt) .badge[style*="3b82f6"] {
        background: var(--db-accent) !important;
        color: #111 !important;
    }

    @media (max-width: 600px) {
        .db-hero { padding: 18px 14px 84px; }
        .db-hero h1 { font-size: 1.3rem !important; }
        .db-content { gap: 14px; }
        .db-stat { padding: 14px; }
        .db-card-header { padding: 14px 14px; }
        .db-card-body { padding: 12px 14px; }
        .db-filter-btn { width: 100%; justify-content: center; }
        .db-page .table { font-size: 11px; }
        .db-stats {
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 0;
            gap: 10px;
        }
        .db-stats-primary {
            margin-top: -34px !important;
        }
        .db-stats-mobile-2col {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .db-stats-secondary {
            margin-top: 12px !important;
        }
        .db-stat {
            min-height: auto;
            transform: none !important;
            gap: 10px;
            border-radius: 14px;
        }
        .db-stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 11px;
        }
        .db-stat-svg {
            width: 19px;
            height: 19px;
        }
        .db-stat-label {
            font-size: 0.64rem;
            letter-spacing: .05em;
        }
        .db-stat-value {
            font-size: 1.02rem;
        }
        .db-stat-sub {
            font-size: 0.68rem;
            line-height: 1.25;
        }
        .db-hero-live {
            flex-direction: column;
            align-items: stretch;
        }
        .db-hero-chip {
            justify-content: space-between;
            width: 100%;
        }
    }

    @media (max-width: 768px) {
        .db-page {
            padding-bottom: 18px;
        }
        .db-hero-inner,
        .db-card-header {
            gap: 10px;
        }
        .db-hero-actions {
            width: 100%;
        }
        .db-hero-actions > div,
        .db-hero-actions .select2-container {
            width: 100% !important;
        }
        .db-filter-btn {
            width: 100%;
            min-height: 42px;
            font-size: 0.9rem !important;
        }
        .db-stats {
            margin-top: 0;
            gap: 12px;
        }
        .db-stats-primary {
            margin-top: -42px !important;
        }
        .db-stats-mobile-2col {
            display: grid !important;
            grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
        }
        .db-stat {
            border-radius: 14px;
            gap: 12px;
        }
        .db-stat-value {
            font-size: 1.18rem;
        }
        .db-card {
            border-radius: 14px;
        }
        .db-page .btn,
        .db-page .btn-xs,
        .db-page .btn-sm {
            min-height: 34px !important;
            font-size: 12px !important;
            padding: 6px 10px !important;
        }

        /* Mobile table usability */
        .db-page .db-card-body > div[style*="overflow-x:auto"] {
            overflow-x: auto !important;
            -webkit-overflow-scrolling: touch;
            border-radius: 10px;
        }
        .db-page .table {
            min-width: 640px;
            font-size: 12px !important;
        }
        .db-page .table thead th,
        .db-page .table tbody td {
            padding: 8px 10px !important;
        }

        /* Stack DataTables controls nicely on mobile */
        .db-page .dataTables_wrapper .row:first-child > div,
        .db-page .dataTables_wrapper .row:last-child > div {
            width: 100%;
            text-align: left !important;
            margin-bottom: 8px;
        }
        .db-page .dataTables_wrapper .dataTables_filter input {
            width: 100%;
            min-height: 38px;
            margin-left: 0 !important;
            margin-top: 6px;
        }
        .db-page .dataTables_wrapper .dataTables_length select {
            min-height: 34px;
        }
        .db-page .pagination > li > a,
        .db-page .pagination > li > span {
            min-width: 34px;
            min-height: 34px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
    }

    /* Light theme: same components, readable surfaces. Dark values stay on :root. */
    html[data-vspos-theme="light"] .db-page {
        --db-bg: #f8fafc;
        --db-card: #ffffff;
        --db-card-solid: #ffffff;
        --db-card-soft: #f1f5f9;
        --db-border: #e2e8f0;
        --db-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 20px rgba(15, 23, 42, 0.04);
        --db-shadow-hover: 0 10px 24px rgba(15, 23, 42, 0.08);
        --db-text-strong: #111827;
        --db-text: #1f2937;
        --db-text-muted: #475569;
        --db-sub: #64748b;
        --db-ink: #111827;
        --db-green: #16a34a;
        --db-amber: #ca8a04;
        --db-red: #e11d48;
        --db-cyan: #0284c7;
        --db-purple: #7c3aed;
        --db-info: #1d4ed8;
        --db-hero-bg: #ffffff;
        --db-hero-muted: #64748b;
        --db-hero-sub: #64748b;
        --db-line: #e2e8f0;
        --db-stat-bg: #ffffff;
        --db-card-bg: #ffffff;
        --db-chip-bg: #f1f5f9;
        --db-date-bg: #ffffff;
        --db-date-border: #e5e7eb;
        --db-date-text: #111827;
        --db-filter-hover: #111827;
        --db-green-soft: #dcfce7;
        --db-red-soft: #ffe4e6;
        --db-cyan-soft: #e0f2fe;
        --db-purple-soft: #ede9fe;
        --db-kpi-bg: #f8fafc;
        --db-panel-bg: #ffffff;
        --db-table-head: #f8fafc;
        --db-table-cell: #ffffff;
        --db-table-hover: #f3f4f6;
        --db-table-foot: #f8fafc;
        --db-input-bg: #ffffff;
        --db-btn-bg: #ffffff;
        --db-btn-text: #111827;
        --db-alert-bg: #fee2e2;
        --db-alert-text: #7f1d1d;
        --db-cheque-bg: #ffffff;
        --db-cheque-red: #b91c1c;
        --db-cheque-amber: #b45309;
        --db-cheque-green: #15803d;
        --db-cheque-sub: #4b5563;
        --db-chart-fill: #4b5563;
        --db-chart-stroke: #e5e7eb;
        --db-empty-bg: #f8fafc;
        --db-empty-text: #4b5563;
        --db-header-bar: #ffffff;
        --db-accent-soft: rgba(180, 83, 9, 0.12);
    }
    /* Light theme does not float cards over the hero. Dark keeps the overlap. */
    html[data-vspos-theme="light"] .db-hero {
        padding-bottom: 24px !important;
    }
    html[data-vspos-theme="light"] .db-stats-primary {
        margin-top: 18px !important;
    }
    html[data-vspos-theme="light"] .db-hero::before,
    html[data-vspos-theme="light"] .db-hero::after {
        background: rgba(59, 130, 246, 0.07);
    }
    html[data-vspos-theme="light"] .db-stat {
        border-color: #e8edf3;
    }
    html[data-vspos-theme="light"] .db-stat-icon {
        box-shadow: none;
        border-radius: 12px;
    }
    html[data-vspos-theme="light"] .db-stat-icon.blue {
        background: #ffedd5 !important;
        color: #ea580c !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.green {
        background: #dcfce7 !important;
        color: #16a34a !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.amber {
        background: #fef9c3 !important;
        color: #ca8a04 !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.tone-pay {
        background: #ffedd5 !important;
        color: #ea580c !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.red {
        background: #ffe4e6 !important;
        color: #e11d48 !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.cyan {
        background: #e0f2fe !important;
        color: #0284c7 !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.purple {
        background: #ede9fe !important;
        color: #7c3aed !important;
    }
    html[data-vspos-theme="light"] .db-stat-icon.yellow {
        background: #d1fae5 !important;
        color: #059669 !important;
    }
    html[data-vspos-theme="light"] .db-led-digit {
        background: #e8eef6 !important;
        border: 1px solid #dbe3ee !important;
        color: #1e293b !important;
        text-shadow: none !important;
        box-shadow: none !important;
    }
    html[data-vspos-theme="light"] .db-led-colon {
        color: #64748b !important;
        text-shadow: none !important;
    }
    html[data-vspos-theme="light"] .db-filter-btn {
        background: #ffffff !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }
    html[data-vspos-theme="light"] .db-filter-btn:hover {
        background: #f8fafc !important;
        color: #111827 !important;
        box-shadow: none;
    }
</style>
@endsection

@section('javascript')
    <script src="{{ asset('js/home.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    @includeIf('sales_order.common_js')
    @includeIf('purchase_order.common_js')
    @if (!empty($all_locations))
        {!! $sells_chart_1->script() !!}
        {!! $sells_chart_2->script() !!}
    @endif
    <script type="text/javascript">
        $(document).ready(function() {
            var dbQuotes = [
                "Sales grow where service stays excellent.",
                "Strong stock control protects your profit.",
                "Every happy customer is long-term revenue.",
                "Consistency in operations creates reliable growth.",
                "Smart decisions today make cash flow stronger.",
                "Good teams and clear numbers build great businesses."
            ];
            var dbTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone || 'Asia/Colombo';
            var dbQuoteEl = document.getElementById('db_business_quote');
            var dbClockEl = document.getElementById('db_local_clock');
            if (dbQuoteEl) {
                var dbIndex = Math.floor(Math.random() * dbQuotes.length);
                dbQuoteEl.textContent = dbQuotes[dbIndex];
            }
            function pad2(v) {
                return String(v).padStart(2, '0');
            }
            function updateHeroTimers() {
                var now = new Date();
                if (dbClockEl) {
                    var timeTxt = now.toLocaleTimeString([], {
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: false,
                        timeZone: dbTimezone
                    });
                    var t = timeTxt.split(':');
                    if (t.length === 3) {
                        var hh = t[0], mm = t[1], ss = t[2];
                        dbClockEl.innerHTML =
                            '<span class="db-led-digit">' + hh.charAt(0) + '</span>' +
                            '<span class="db-led-digit">' + hh.charAt(1) + '</span>' +
                            '<span class="db-led-colon">:</span>' +
                            '<span class="db-led-digit">' + mm.charAt(0) + '</span>' +
                            '<span class="db-led-digit">' + mm.charAt(1) + '</span>' +
                            '<span class="db-led-colon">:</span>' +
                            '<span class="db-led-digit">' + ss.charAt(0) + '</span>' +
                            '<span class="db-led-digit">' + ss.charAt(1) + '</span>';
                    } else {
                        dbClockEl.textContent = timeTxt;
                    }
                }
            }
            updateHeroTimers();
            setInterval(updateHeroTimers, 1000);

            sales_order_table = $('#sales_order_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                aaSorting: [
                    [1, 'desc']
                ],
                "ajax": {
                    "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}?sale_type=sales_order',
                    "data": function(d) {
                        d.for_dashboard_sales_order = true;

                        if ($('#so_location').length > 0) {
                            d.location_id = $('#so_location').val();
                        }
                    }
                },
                columnDefs: [{
                    "targets": 7,
                    "orderable": false,
                    "searchable": false
                }],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    {
                        data: 'so_qty_remaining',
                        name: 'so_qty_remaining',
                        "searchable": false
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                ]
            });

            @if (auth()->user()->can('account.access') && config('constants.show_payments_recovered_today') == true)

                // Cash Flow Table
                cash_flow_table = $('#cash_flow_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    "ajax": {
                        "url": "{{ action([\App\Http\Controllers\AccountController::class, 'cashFlow']) }}",
                        "data": function(d) {
                            d.type = 'credit';
                            d.only_payment_recovered = true;
                        }
                    },
                    "ordering": false,
                    "searching": false,
                    columns: [{
                            data: 'operation_date',
                            name: 'operation_date'
                        },
                        {
                            data: 'account_name',
                            name: 'account_name'
                        },
                        {
                            data: 'sub_type',
                            name: 'sub_type'
                        },
                        {
                            data: 'method',
                            name: 'TP.method'
                        },
                        {
                            data: 'payment_details',
                            name: 'payment_details',
                            searchable: false
                        },
                        {
                            data: 'credit',
                            name: 'amount'
                        },
                        {
                            data: 'balance',
                            name: 'balance'
                        },
                        {
                            data: 'total_balance',
                            name: 'total_balance'
                        },
                    ],
                    "fnDrawCallback": function(oSettings) {
                        __currency_convert_recursively($('#cash_flow_table'));
                    },
                    "footerCallback": function(row, data, start, end, display) {
                        var footer_total_credit = 0;

                        for (var r in data) {
                            footer_total_credit += $(data[r].credit).data('orig-value') ? parseFloat($(
                                data[r].credit).data('orig-value')) : 0;
                        }
                        $('.footer_total_credit').html(__currency_trans_from_en(footer_total_credit));
                    }
                });
            @endif

            $('#so_location').change(function() {
                sales_order_table.ajax.reload();
            });
            @if (!empty($common_settings['enable_purchase_order']))
                //Purchase table
                purchase_order_table = $('#purchase_order_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: {
                        url: '{{ action([\App\Http\Controllers\PurchaseOrderController::class, 'index']) }}',
                        data: function(d) {
                            d.from_dashboard = true;

                            if ($('#po_location').length > 0) {
                                d.location_id = $('#po_location').val();
                            }
                        },
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'ref_no',
                            name: 'ref_no'
                        },
                        {
                            data: 'location_name',
                            name: 'BS.name'
                        },
                        {
                            data: 'name',
                            name: 'contacts.name'
                        },
                        {
                            data: 'status',
                            name: 'transactions.status'
                        },
                        {
                            data: 'po_qty_remaining',
                            name: 'po_qty_remaining',
                            "searchable": false
                        },
                        {
                            data: 'added_by',
                            name: 'u.first_name'
                        }
                    ]
                })

                $('#po_location').change(function() {
                    purchase_order_table.ajax.reload();
                });
            @endif

            @if (!empty($common_settings['enable_purchase_requisition']))
                //Purchase table
                purchase_requisition_table = $('#purchase_requisition_table').DataTable({
                    processing: true,
                    serverSide: true,
                    fixedHeader:false,
                    aaSorting: [
                        [1, 'desc']
                    ],
                    scrollY: "75vh",
                    scrollX: true,
                    scrollCollapse: true,
                    ajax: {
                        url: '{{ action([\App\Http\Controllers\PurchaseRequisitionController::class, 'index']) }}',
                        data: function(d) {
                            d.from_dashboard = true;

                            if ($('#pr_location').length > 0) {
                                d.location_id = $('#pr_location').val();
                            }
                        },
                    },
                    columns: [{
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        },
                        {
                            data: 'transaction_date',
                            name: 'transaction_date'
                        },
                        {
                            data: 'ref_no',
                            name: 'ref_no'
                        },
                        {
                            data: 'location_name',
                            name: 'BS.name'
                        },
                        {
                            data: 'status',
                            name: 'status'
                        },
                        {
                            data: 'delivery_date',
                            name: 'delivery_date'
                        },
                        {
                            data: 'added_by',
                            name: 'u.first_name'
                        },
                    ]
                })

                $('#pr_location').change(function() {
                    purchase_requisition_table.ajax.reload();
                });

                $(document).on('click', 'a.delete-purchase-requisition', function(e) {
                    e.preventDefault();
                    swal({
                        title: LANG.sure,
                        icon: 'warning',
                        buttons: true,
                        dangerMode: true,
                    }).then(willDelete => {
                        if (willDelete) {
                            var href = $(this).attr('href');
                            $.ajax({
                                method: 'DELETE',
                                url: href,
                                dataType: 'json',
                                success: function(result) {
                                    if (result.success == true) {
                                        toastr.success(result.msg);
                                        purchase_requisition_table.ajax.reload();
                                    } else {
                                        toastr.error(result.msg);
                                    }
                                },
                            });
                        }
                    });
                });
            @endif

            sell_table = $('#shipments_table').DataTable({
                processing: true,
                serverSide: true,
                fixedHeader:false,
                aaSorting: [
                    [1, 'desc']
                ],
                scrollY: "75vh",
                scrollX: true,
                scrollCollapse: true,
                "ajax": {
                    "url": '{{ action([\App\Http\Controllers\SellController::class, 'index']) }}',
                    "data": function(d) {
                        d.only_pending_shipments = true;
                        if ($('#pending_shipments_location').length > 0) {
                            d.location_id = $('#pending_shipments_location').val();
                        }
                    }
                },
                columns: [{
                        data: 'action',
                        name: 'action',
                        searchable: false,
                        orderable: false
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'invoice_no',
                        name: 'invoice_no'
                    },
                    {
                        data: 'conatct_name',
                        name: 'conatct_name'
                    },
                    {
                        data: 'mobile',
                        name: 'contacts.mobile'
                    },
                    {
                        data: 'business_location',
                        name: 'bl.name'
                    },
                    {
                        data: 'shipping_status',
                        name: 'shipping_status'
                    },
                    @if (!empty($custom_labels['shipping']['custom_field_1']))
                        {
                            data: 'shipping_custom_field_1',
                            name: 'shipping_custom_field_1'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_2']))
                        {
                            data: 'shipping_custom_field_2',
                            name: 'shipping_custom_field_2'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_3']))
                        {
                            data: 'shipping_custom_field_3',
                            name: 'shipping_custom_field_3'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_4']))
                        {
                            data: 'shipping_custom_field_4',
                            name: 'shipping_custom_field_4'
                        },
                    @endif
                    @if (!empty($custom_labels['shipping']['custom_field_5']))
                        {
                            data: 'shipping_custom_field_5',
                            name: 'shipping_custom_field_5'
                        },
                    @endif {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'waiter',
                        name: 'ss.first_name',
                        @if (empty($is_service_staff_enabled))
                            visible: false
                        @endif
                    }
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#sell_table'));
                },
                createdRow: function(row, data, dataIndex) {
                    $(row).find('td:eq(4)').attr('class', 'clickable_td');
                }
            });

            $('#pending_shipments_location').change(function() {
                sell_table.ajax.reload();
            });
        });

        // Dashboard: remind customer before cheque due date
        $(document).on('click', '.btn-dashboard-cheque-remind', function(e) {
            e.preventDefault();
            var id = $(this).data('id');
            $.ajax({
                url: '/cheque/' + id + '/remind',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (!res.success) {
                        toastr.error(res.msg || 'Could not build reminder');
                        return;
                    }
                    swal({
                        title: 'Remind ' + (res.contact_name || 'Customer'),
                        text: res.message,
                        icon: 'info',
                        buttons: {
                            cancel: { text: 'Close', value: null, visible: true, className: 'btn-default' },
                            copy: { text: 'Copy Message', value: 'copy', className: 'btn-default' },
                            whatsapp: {
                                text: res.whatsapp_link ? 'Send WhatsApp' : 'No Mobile',
                                value: res.whatsapp_link ? 'whatsapp' : null,
                                visible: !!res.whatsapp_link,
                                className: 'btn-success'
                            }
                        }
                    }).then(function(action) {
                        if (action === 'whatsapp' && res.whatsapp_link) {
                            window.open(res.whatsapp_link, '_blank');
                        } else if (action === 'copy' && res.message) {
                            if (navigator.clipboard && navigator.clipboard.writeText) {
                                navigator.clipboard.writeText(res.message).then(function() {
                                    toastr.success('Message copied');
                                });
                            } else {
                                toastr.info(res.message);
                            }
                        }
                    });
                },
                error: function(xhr) {
                    var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed to remind';
                    toastr.error(msg);
                }
            });
        });
    </script>
    
@endsection
