@extends('layouts.app')
@section('title', __('lang_v1.capital_settings'))

@section('content')
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">
        @lang('lang_v1.capital_settings')
        <small><a href="{{ action([\App\Http\Controllers\CapitalAccountController::class, 'index']) }}">@lang('lang_v1.partner_balance_sheet')</a></small>
    </h1>
</section>

<section class="content">
    @if(session('status'))
        <div class="alert @if(session('status.success') == 1) alert-success @else alert-danger @endif">
            {{ session('status.msg') }}
        </div>
    @endif

    {!! Form::open(['url' => action([\App\Http\Controllers\CapitalAccountController::class, 'updateSettings']), 'method' => 'post']) !!}
    @component('components.widget', ['class' => 'box-primary', 'title' => __('lang_v1.capital_link_payment_accounts')])
        <p class="help-block">@lang('lang_v1.capital_link_payment_accounts_help')</p>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('bank_account_id', __('lang_v1.capital_source_bank') . ':') !!}
                    {!! Form::select('bank_account_id', $accounts, $settings['bank_account_id'], ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('cash_account_id', __('lang_v1.capital_source_cash_drawer') . ':') !!}
                    {!! Form::select('cash_account_id', $accounts, $settings['cash_account_id'], ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                </div>
            </div>
        </div>
    @endcomponent

    @component('components.widget', ['class' => 'box-solid', 'title' => __('lang_v1.capital_link_expenses')])
        <p class="help-block">@lang('lang_v1.capital_link_expenses_help')</p>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('expense_category_id', __('lang_v1.capital_default_expense_category') . ':') !!}
                    @include('expense.partials.expense_category_select', [
                        'categories' => $expense_categories,
                        'selected' => $settings['expense_category_id'],
                        'selectId' => 'expense_category_id',
                    ])
                    <p class="help-block">
                        <a href="{{ action([\App\Http\Controllers\ExpenseCategoryController::class, 'index']) }}" target="_blank">
                            <i class="fa fa-external-link"></i> @lang('lang_v1.capital_manage_expense_categories')
                        </a>
                    </p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                    {!! Form::select('location_id', $locations, $settings['location_id'], ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('messages.please_select')]) !!}
                    <p class="help-block">@lang('lang_v1.capital_location_help')</p>
                </div>
            </div>
        </div>
        <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
    @endcomponent
    {!! Form::close() !!}
</section>
@endsection
