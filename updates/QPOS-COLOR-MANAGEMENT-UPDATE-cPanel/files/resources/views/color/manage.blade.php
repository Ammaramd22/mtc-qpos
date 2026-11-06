@extends('layouts.app')
@section('title', __('color.colors'))

@section('content')

    <section class="content-header">
        <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-black">@lang('color.colors')
            <small class="tw-text-sm md:tw-text-base tw-text-gray-700 tw-font-semibold">@lang('color.manage_your_colors')</small>
        </h1>
    </section>

    <section class="content">
        @component('components.widget', ['class' => 'box-primary', 'title' => __('color.all_your_colors')])
            @slot('tool')
                @can('product.create')
                    <div class="box-tools">
                        <a class="tw-dw-btn tw-bg-gradient-to-r tw-from-indigo-600 tw-to-blue-500 tw-font-bold tw-text-white tw-border-none tw-rounded-full btn-modal pull-right"
                            data-href="{{ url('colors/create') }}"
                            data-container=".colors_modal">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                                class="icon icon-tabler icons-tabler-outline icon-tabler-plus">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                                <path d="M12 5l0 14" />
                                <path d="M5 12l14 0" />
                            </svg> @lang('messages.add')
                        </a>
                    </div>
                @endcan
            @endslot

            <p class="help-block" style="margin-top:0;">@lang('color.help')</p>
            <p class="help-block">@lang('color.examples')</p>

            @if(auth()->user()->can('product.view') || auth()->user()->can('product.create'))
                @php
                    $colorRows = $colors ?? [];
                    $colorCounts = is_array($product_counts ?? null) ? $product_counts : [];
                @endphp
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="colors_table">
                        <thead>
                            <tr>
                                <th>@lang('color.color_name')</th>
                                <th>@lang('color.products')</th>
                                <th class="not-export">@lang('messages.action')</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($colorRows as $color)
                                <tr>
                                    <td>{{ $color->name }}</td>
                                    <td>{{ (int) ($colorCounts[$color->id] ?? 0) }}</td>
                                    <td>
                                        @can('product.update')
                                            <button type="button" data-href="{{ url('colors/'.$color->id.'/edit') }}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary edit_color_button"><i class="glyphicon glyphicon-edit"></i> @lang('messages.edit')</button>
                                        @endcan
                                        @can('product.delete')
                                            <button type="button" data-href="{{ url('colors/'.$color->id) }}" class="tw-dw-btn tw-dw-btn-outline tw-dw-btn-xs tw-dw-btn-error delete_color_button"><i class="glyphicon glyphicon-trash"></i> @lang('messages.delete')</button>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endcomponent

        <div class="modal fade colors_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel"></div>
    </section>

@endsection
