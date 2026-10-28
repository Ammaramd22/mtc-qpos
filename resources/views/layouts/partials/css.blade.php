<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Mono:wght@400;500;600&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,600&display=swap" rel="stylesheet">
<link href="{{ asset('css/qpos-fonts.css?v='.$asset_v) }}" rel="stylesheet">

<link href="{{ asset('css/tailwind/app.css?v='.$asset_v) }}" rel="stylesheet">
<link rel="stylesheet" href="https://cdn-uicons.flaticon.com/uicons-regular-rounded/css/uicons-regular-rounded.css">

@php
    $themeColor = session('business.theme_color', 'primary');
    $themeColorMap = [
        'primary' => ['700' => '#004EEB', '800' => '#0040C1', '900' => '#00359E'],
        'indigo'  => ['700' => '#4338CA', '800' => '#3730A3', '900' => '#312E81'],
        'violet'  => ['700' => '#6D28D9', '800' => '#5B21B6', '900' => '#4C1D95'],
        'purple'  => ['700' => '#5925DC', '800' => '#4A1FB8', '900' => '#3E1C96'],
        'teal'    => ['700' => '#0F766E', '800' => '#115E59', '900' => '#134E4A'],
        'emerald' => ['700' => '#047857', '800' => '#065F46', '900' => '#064E3B'],
        'green'   => ['700' => '#067647', '800' => '#085D3A', '900' => '#074D31'],
        'sky'     => ['700' => '#026AA2', '800' => '#065986', '900' => '#0B4A6F'],
        'pink'    => ['700' => '#BE185D', '800' => '#9D174D', '900' => '#831843'],
        'rose'    => ['700' => '#BE123C', '800' => '#9F1239', '900' => '#881337'],
        'red'     => ['700' => '#B42318', '800' => '#912018', '900' => '#7A271A'],
        'orange'  => ['700' => '#B93815', '800' => '#932F19', '900' => '#772917'],
        'yellow'  => ['700' => '#B54708', '800' => '#93370D', '900' => '#7A2E0E'],
        'slate'   => ['700' => '#334155', '800' => '#1E293B', '900' => '#0F172A'],
    ];
    $tc = $themeColorMap[$themeColor] ?? $themeColorMap['primary'];
@endphp
<style>
    /* IC Mode PIN prompt — dark theme, clear typing */
    #__ic_backdrop {position: fixed;inset: 0;z-index: 99998;}html[data-vspos-theme="dark"] #__ic_backdrop{background: rgba(0, 0, 0, 0.72);}
    #__ic_overlay,
    .vspos-ic-prompt {font-family: var(--qpos-font, 'Plus Jakarta Sans', sans-serif) !important;}
    #__ic_overlay input[type="password"],
    .vspos-ic-prompt input[type="password"] {caret-color: #f5c518 !important;border: 1px solid #333 !important;}html[data-vspos-theme="dark"] #__ic_overlay input[type="password"],
html[data-vspos-theme="dark"] .vspos-ic-prompt input[type="password"]{background: #0a0a0a !important;color: #fafafa !important;-webkit-text-fill-color: #fafafa !important;}
    #__ic_overlay input[type="password"]::placeholder,
    .vspos-ic-prompt input[type="password"]::placeholder {color: #737373 !important;opacity: 1 !important;letter-spacing: normal !important;}
    #__ic_overlay input[type="password"]:focus,
    .vspos-ic-prompt input[type="password"]:focus {border-color: #f5c518 !important;box-shadow: 0 0 0 3px rgba(245, 197, 24, 0.18) !important;}

    /* Force full dark theme across all non-POS pages */
    body:not(.lockscreen) .container,
    body:not(.lockscreen) .container-fluid,
    body:not(.lockscreen) .row,
    body:not(.lockscreen) .col-md-12,
    body:not(.lockscreen) .col-sm-12,
    body:not(.lockscreen) .col-lg-12 {background-color: transparent !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .content-wrapper,
html[data-vspos-theme="dark"] body:not(.lockscreen) .right-side,
html[data-vspos-theme="dark"] body:not(.lockscreen) .content,
html[data-vspos-theme="dark"] body:not(.lockscreen) .wrapper,
html[data-vspos-theme="dark"] body:not(.lockscreen) .content-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) section.content{background: #050505 !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-solid,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-primary,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-default,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-info,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .panel,
html[data-vspos-theme="dark"] body:not(.lockscreen) .panel-default,
html[data-vspos-theme="dark"] body:not(.lockscreen) .well{background: #111111 !important;color: #e2e8f0 !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box-header.with-border{border-bottom: 1px solid #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .content-header > h1,
html[data-vspos-theme="dark"] body:not(.lockscreen) .content-header > h1 small,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-title,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h1,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h2,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h3,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h4,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h5,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body h6,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body p,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-body label{color: #f8fafc !important;}
    body:not(.lockscreen) .breadcrumb,
    body:not(.lockscreen) .breadcrumb > li + li:before,
    body:not(.lockscreen) .breadcrumb > li > a {background: transparent !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .breadcrumb,
html[data-vspos-theme="dark"] body:not(.lockscreen) .breadcrumb > li + li:before,
html[data-vspos-theme="dark"] body:not(.lockscreen) .breadcrumb > li > a{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom > .nav-tabs > li > a{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom > .nav-tabs > li.active > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom > .nav-tabs > li.active:hover > a{background: #141414 !important;color: #fff !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom > .tab-content,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tab-content,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tab-pane{background: #111111 !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom > .nav-tabs{background: #141414 !important;border-bottom: 1px solid #2a2a2a !important;}
    body:not(.lockscreen) .dataTables_wrapper,
    body:not(.lockscreen) .dataTables_wrapper .row,
    body:not(.lockscreen) .dataTables_wrapper .col-sm-12,
    body:not(.lockscreen) .dataTables_wrapper .col-sm-6,
    body:not(.lockscreen) .dataTables_wrapper .col-md-12 {background: transparent !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .row,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .col-sm-12,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .col-sm-6,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .col-md-12{color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTable,
html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable,
html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable.no-footer{background: #111111 !important;color: #e2e8f0 !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable thead th,
html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable thead td,
html[data-vspos-theme="dark"] body:not(.lockscreen) .table > thead > tr > th{background: #141414 !important;color: #f8fafc !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable tbody tr,
html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable tbody tr.odd,
html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable tbody tr.even,
html[data-vspos-theme="dark"] body:not(.lockscreen) .table-striped > tbody > tr:nth-of-type(odd),
html[data-vspos-theme="dark"] body:not(.lockscreen) .table-striped > tbody > tr:nth-of-type(even),
html[data-vspos-theme="dark"] body:not(.lockscreen) .table > tbody > tr{background: #111111 !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable tbody td,
html[data-vspos-theme="dark"] body:not(.lockscreen) .table > tbody > tr > td,
html[data-vspos-theme="dark"] body:not(.lockscreen) .table > tbody > tr > th{background: #111111 !important;color: #e2e8f0 !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) table.dataTable tbody tr:hover td,
html[data-vspos-theme="dark"] body:not(.lockscreen) .table-hover > tbody > tr:hover > td{background: #222222 !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_empty{background: #111111 !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_wrapper .dt-buttons .dt-button,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-copy,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-csv,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-excel,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-print,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-pdf,
html[data-vspos-theme="dark"] body:not(.lockscreen) .buttons-colvis{background: #141414 !important;color: #f8fafc !important;border: 1px solid #333333 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_filter input,
html[data-vspos-theme="dark"] body:not(.lockscreen) .dataTables_length select,
html[data-vspos-theme="dark"] body:not(.lockscreen) .form-control{background: #141414 !important;color: #f8fafc !important;border: 1px solid #333333 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > span{background: #141414 !important;color: #e2e8f0 !important;border-color: #333333 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .active > span{background: #f5c518 !important;border-color: #f5c518 !important;color: #111111 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-default{background: #141414 !important;color: #f8fafc !important;border: 1px solid #333333 !important;}
    /* Tailwind utility overrides for legacy pages still using light classes */
    html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-bg-white,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-bg-gray-50,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-bg-gray-100{background: #111111 !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-black,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-gray-700,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-gray-800,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-gray-900{color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-gray-500,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-text-gray-600{color: #94a3b8 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-border-gray-200,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-border-gray-300{border-color: #2a2a2a !important;}
    /* Filters panel cleanup */
    html[data-vspos-theme="dark"] body:not(.lockscreen) #filters,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter_box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box.filters-box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box.filter-box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[id*="filter"],
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"]{background: #111111 !important;color: #e2e8f0 !important;border: 1px solid #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter_box .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters > .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"] > .box-header{background: #141414 !important;color: #f8fafc !important;border-bottom: 1px solid #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) #filters .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter_box .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box#filters > .box-body,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box[class*="filter"] > .box-body{background: #111111 !important;color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .filters,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter-box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box-tools,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .row .col-sm-3,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .row .col-sm-4,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .row .col-md-3,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .row .col-md-4{color: #e2e8f0 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body input[type="checkbox"] + label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .checkbox label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .radio label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .control-label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .small,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .text-muted{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) #filters label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter_box label{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) #filters input[type="checkbox"],
html[data-vspos-theme="dark"] body:not(.lockscreen) .filters input[type="checkbox"],
html[data-vspos-theme="dark"] body:not(.lockscreen) .filter_box input[type="checkbox"]{accent-color: #f5c518;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-tools .btn-box-tool{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-tools .btn-box-tool:hover{color: #fff !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body hr,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-header.with-border{border-color: #2a2a2a !important;}

    /*
     * Pagination: style the real controls (inner a/span), not li.paginate_button.
     * DataTables Bootstrap wraps each control in <li class="paginate_button"> and
     * Bootstrap floats the <a>, which otherwise leaves empty boxes after Next.
     */
    body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li,
    body:not(.lockscreen) .dataTables_wrapper .dataTables_paginate ul.pagination > li.paginate_button {display: inline-flex !important;float: none !important;padding: 0 !important;margin: 0 !important;min-width: 0 !important;min-height: 0 !important;border: 0 !important;background: transparent !important;box-shadow: none !important;}
    body:not(.lockscreen) .pagination > li > a,
    body:not(.lockscreen) .pagination > li > span {float: none !important;display: inline-flex !important;align-items: center;justify-content: center;min-width: 34px;height: 32px;padding: 4px 12px !important;margin: 0 !important;font-size: 12px !important;font-weight: 600;line-height: 1.2 !important;border-radius: 6px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li > span{background: #141414 !important;color: #e2e8f0 !important;border: 1px solid #333333 !important;}
    body:not(.lockscreen) .pagination > .disabled > a,
    body:not(.lockscreen) .pagination > .disabled > span {color: #64748b !important;cursor: not-allowed !important;opacity: 0.75;}html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > .disabled > span{background: #141414 !important;border-color: #2a2a2a !important;}

    html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-content{background: #111111 !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-footer{border-color: #2a2a2a !important;}

    :root {--theme-700: {{ $tc['700'] }};--theme-800: {{ $tc['800'] }};--theme-900: {{ $tc['900'] }};}
    .theme-header-bg {background-image: linear-gradient(to right, var(--theme-800), var(--theme-900));}
    .theme-btn-bg {background-color: var(--theme-800);}
    .theme-btn-bg:hover {background-color: var(--theme-700);}
    .theme-btn-bg:active,
    .theme-btn-bg:focus,
    .theme-btn-bg:focus-visible {background-color: var(--theme-900);color: #fff;outline: 2px solid color-mix(in srgb, var(--theme-700) 40%, transparent);outline-offset: 0px;}
    .theme-logo-bg {background-color: var(--theme-800);}
    #side-bar a svg, #side-bar a i {color: #c4c4c4;stroke: currentColor;}
    #side-bar a:hover svg, #side-bar a:hover i,
    #side-bar a.theme-sidebar-active svg, #side-bar a.theme-sidebar-active i {color: #f5c518;}
    #side-bar .theme-sidebar-hover:hover,
    #side-bar .theme-sidebar-hover:active,
    #side-bar .theme-sidebar-hover:focus {background-color: rgba(255, 255, 255, 0.04);color: #fafafa;outline: none;}
    #side-bar .theme-sidebar-active {background-color: rgba(245, 197, 24, 0.12);color: #f5c518;}
    #side-bar .theme-sidebar-child-hover:hover,
    #side-bar .theme-sidebar-child-hover:active,
    #side-bar .theme-sidebar-child-hover:focus {color: #f5c518;outline: none;}
    #side-bar .theme-sidebar-child-active {color: #f5c518;}
</style>

<!-- Global compact table styling -->
<style>
    /* Global app dark theme (non POS screens) */
    html[data-vspos-theme="dark"] body:not(.lockscreen),
html[data-vspos-theme="dark"] body:not(.lockscreen) .content-wrapper,
html[data-vspos-theme="dark"] body:not(.lockscreen) .right-side,
html[data-vspos-theme="dark"] body:not(.lockscreen) .wrapper,
html[data-vspos-theme="dark"] body:not(.lockscreen) .content,
html[data-vspos-theme="dark"] body:not(.lockscreen) #scrollable-container,
html[data-vspos-theme="dark"] body:not(.lockscreen) main{background: #050505 !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-content,
html[data-vspos-theme="dark"] body:not(.lockscreen) .small-box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .info-box,
html[data-vspos-theme="dark"] body:not(.lockscreen) .panel,
html[data-vspos-theme="dark"] body:not(.lockscreen) .nav-tabs-custom{background: linear-gradient(160deg, rgba(15, 23, 42, .92), rgba(15, 23, 42, .74)) !important;border: 1px solid #2a2a2a !important;color: #e2e8f0 !important;box-shadow: 0 12px 30px rgba(2, 6, 23, .35);}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box-header,
html[data-vspos-theme="dark"] body:not(.lockscreen) .panel-heading{border-bottom: 1px solid #2a2a2a !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box-title,
html[data-vspos-theme="dark"] body:not(.lockscreen) h1,
html[data-vspos-theme="dark"] body:not(.lockscreen) h2,
html[data-vspos-theme="dark"] body:not(.lockscreen) h3,
html[data-vspos-theme="dark"] body:not(.lockscreen) h4,
html[data-vspos-theme="dark"] body:not(.lockscreen) h5,
html[data-vspos-theme="dark"] body:not(.lockscreen) h6,
html[data-vspos-theme="dark"] body:not(.lockscreen) label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .help-block,
html[data-vspos-theme="dark"] body:not(.lockscreen) .text-muted{color: #cbd5e1 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .form-control,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--single,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown{background: rgba(26, 26, 26, .85) !important;border: 1px solid #333333 !important;color: #f8fafc !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-search--dropdown{background: #111111 !important;}
    body:not(.lockscreen) .select2-container--default .select2-search--dropdown .select2-search__field,
    body:not(.lockscreen) .select2-dropdown .select2-search__field,
    body:not(.lockscreen) .select2-search--dropdown .select2-search__field {border-radius: 8px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--dropdown .select2-search__field,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-dropdown .select2-search__field,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-search--dropdown .select2-search__field{background: #1a1a1a !important;background-color: #1a1a1a !important;border: 1px solid #333333 !important;color: #f5f5f5 !important;-webkit-text-fill-color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--single .select2-selection__rendered,
html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option{color: #f8fafc !important;}
    body:not(.lockscreen) .select2-results__option--highlighted[aria-selected] {background: rgba(245, 197, 24, .25) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-results__option--highlighted[aria-selected]{color: #fff !important;}
    body:not(.lockscreen) .btn-default,
    body:not(.lockscreen) .btn-primary,
    body:not(.lockscreen) .btn-success,
    body:not(.lockscreen) .btn-danger,
    body:not(.lockscreen) .btn-warning,
    body:not(.lockscreen) .btn-info {border-radius: 9px !important;}

    /* Compact DataTables on admin pages only.
       POS (body.lockscreen) keeps its own cart metrics — global nowrap here
       pushed ticket cells and action buttons over neighbouring columns. */
    body:not(.lockscreen) .table {font-size: 12px;margin-bottom: 0;}html[data-vspos-theme="dark"] body:not(.lockscreen) .table{color: #e2e8f0;}
    body:not(.lockscreen) .table thead th {padding: 8px 12px !important;font-size: 11px;text-transform: uppercase;letter-spacing: 0.4px;white-space: nowrap;font-weight: 700;}html[data-vspos-theme="dark"] body:not(.lockscreen) .table thead th{background: rgba(20, 20, 20, .95) !important;border-bottom: 2px solid #2a2a2a !important;color: #f5f5f5;}
    body:not(.lockscreen) .table tbody td {padding: 8px 12px !important;vertical-align: middle;white-space: normal;overflow-wrap: anywhere;}html[data-vspos-theme="dark"] body:not(.lockscreen) .table tbody td{color: #e5e5e5;background: rgba(17, 17, 17, .95) !important;border-color: #222222 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .table tbody tr:hover td{background: #222222 !important;}
    body:not(.lockscreen) .table .btn-group > .btn, body:not(.lockscreen) .table .btn {padding: 2px 8px !important;font-size: 11px !important;border-radius: 6px !important;line-height: 1.4 !important;}
    body:not(.lockscreen) .table .btn-group .dropdown-menu {font-size: 12px;min-width: 120px;}
    body:not(.lockscreen) .table tfoot td {padding: 8px 12px !important;font-size: 12px;font-weight: 700;}html[data-vspos-theme="dark"] body:not(.lockscreen) .table tfoot td{background: rgba(20, 20, 20, .95) !important;border-top: 2px solid #2a2a2a !important;color: #f5f5f5 !important;}
    .dataTables_wrapper .dataTables_filter input {padding: 4px 10px;font-size: 12px;border-radius: 8px;}html[data-vspos-theme="dark"] .dataTables_wrapper .dataTables_filter input{border: 1px solid #333333;background: #1a1a1a;color: #f5f5f5;}
    .dataTables_wrapper .dataTables_length select {padding: 3px 8px;font-size: 12px;border-radius: 6px;}html[data-vspos-theme="dark"] .dataTables_wrapper .dataTables_length select{background: #1a1a1a;color: #f5f5f5;border: 1px solid #333333;}
    .dataTables_wrapper .dt-buttons .dt-button {padding: 4px 10px !important;font-size: 11px !important;border-radius: 6px !important;}
    .table .badge, .table .label {font-size: 10px !important;padding: 2px 6px !important;border-radius: 4px !important;font-weight: 600;}
    .box .box-body {padding: 10px !important;}
    .box .box-header {padding: 10px 12px !important;}
    .box .box-title {font-size: 14px;font-weight: 700;}html[data-vspos-theme="dark"] .box .box-title{color: #f8fafc;}
    /* Collapsed table borders */
    html[data-vspos-theme="dark"] .table-bordered{border: 1px solid #2a2a2a;}
    html[data-vspos-theme="dark"] .table-bordered th,
html[data-vspos-theme="dark"] .table-bordered td{border: 1px solid #2a2a2a !important;}
    .dataTables_wrapper .dataTables_info,
    .dataTables_wrapper .dataTables_filter label,
    .dataTables_wrapper .dataTables_length label {color: #cbd5e1 !important;}
    .dataTables_wrapper .dataTables_paginate {float: right;text-align: right;padding-top: 4px;}
    .dataTables_wrapper .dataTables_paginate ul.pagination,
    body:not(.lockscreen) .pagination {display: inline-flex !important;flex-wrap: wrap;align-items: center;justify-content: flex-end;gap: 4px;margin: 4px 0;padding-left: 0;white-space: normal;}
    html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > li > a,
html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > li > span{background: #141414 !important;color: #e2e8f0 !important;border-color: #333333 !important;}
    .dataTables_wrapper .pagination > .active > a,
    .dataTables_wrapper .pagination > .active > span,
    .dataTables_wrapper .pagination > .active > a:hover,
    .dataTables_wrapper .pagination > .active > span:hover {background: #f5c518 !important;border-color: #f5c518 !important;}html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > .active > a,
html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > .active > span,
html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > .active > a:hover,
html[data-vspos-theme="dark"] .dataTables_wrapper .pagination > .active > span:hover{color: #111111 !important;}
    .pagination > li:first-child > a,
    .pagination > li:first-child > span,
    .pagination > li:last-child > a,
    .pagination > li:last-child > span {border-radius: 6px !important;}
    body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > a:hover,
    body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > span:hover {border-color: #444444 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > a:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .pagination > li:not(.disabled):not(.active) > span:hover{background: #1f1f1f !important;color: #f8fafc !important;}
    @media (max-width: 767px) {
        .dataTables_wrapper .dataTables_paginate {float: none !important;display: block;width: 100%;text-align: center;margin-top: 8px;}
        .dataTables_wrapper .dataTables_paginate ul.pagination,
        body:not(.lockscreen) .pagination {justify-content: center;}
        .dataTables_wrapper .dataTables_info {float: none !important;display: block;text-align: center;}
        .pagination > li > a,
        .pagination > li > span {min-width: 36px;height: 34px;}
    }
</style>

<link rel="stylesheet" href="{{ asset('css/vendor.css?v='.$asset_v) }}">

@if( in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')) )
	<link rel="stylesheet" href="{{ asset('css/rtl.css?v='.$asset_v) }}">
@endif

@yield('css')

<!-- app css -->
<link rel="stylesheet" href="{{ asset('css/app.css?v='.$asset_v) }}">

@if(isset($pos_layout) && $pos_layout)
	<style type="text/css">
		.content{padding-bottom: 0px !important;}
	</style>
@endif
<style type="text/css">
	/*
	* Pattern lock css
	* Pattern direction
	* http://ignitersworld.com/lab/patternLock.html
	*/
	.patt-wrap {z-index: 10;}
	.patt-circ.hovered {background-color: #cde2f2;border: none;}
	.patt-circ.hovered .patt-dots {display: none;}
	.patt-circ.dir {background-image: url("{{asset('/img/pattern-directionicon-arrow.png')}}");background-position: center;background-repeat: no-repeat;}
	.patt-circ.e {-webkit-transform: rotate(0);transform: rotate(0);}
	.patt-circ.s-e {-webkit-transform: rotate(45deg);transform: rotate(45deg);}
	.patt-circ.s {-webkit-transform: rotate(90deg);transform: rotate(90deg);}
	.patt-circ.s-w {-webkit-transform: rotate(135deg);transform: rotate(135deg);}
	.patt-circ.w {-webkit-transform: rotate(180deg);transform: rotate(180deg);}
	.patt-circ.n-w {-webkit-transform: rotate(225deg);transform: rotate(225deg);}
	.patt-circ.n {-webkit-transform: rotate(270deg);transform: rotate(270deg);}
	.patt-circ.n-e {-webkit-transform: rotate(315deg);transform: rotate(315deg);}

	/* ===== Business Settings — UltimatePOS-style layout ===== */
	body:not(.lockscreen) .upos-settings-header h1 {font-size: 24px !important;font-weight: 700 !important;margin: 0 0 8px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-header h1{color: #f5f5f5 !important;}
	body:not(.lockscreen) .upos-settings-header .breadcrumb {background: transparent !important;margin: 0 !important;padding: 0 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-header .breadcrumb > li,
html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-header .breadcrumb > li > a{color: #a3a3a3 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-header .breadcrumb > .active{color: #f5c518 !important;}
	body:not(.lockscreen) .upos-settings-page {padding-bottom: 28px !important;}
	body:not(.lockscreen) .upos-settings-search {margin-bottom: 14px;}
	body:not(.lockscreen) .upos-settings-box.pos-tab-container,
	body:not(.lockscreen) div.pos-tab-container.upos-settings-box {border-radius: 12px !important;overflow: hidden;margin-bottom: 0 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-box.pos-tab-container,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-container.upos-settings-box{background: #111111 !important;border: 1px solid #2a2a2a !important;box-shadow: 0 10px 28px rgba(0,0,0,.35) !important;color: #e5e5e5 !important;}
	body:not(.lockscreen) .upos-settings-box-head {padding: 12px 16px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-box-head{background: #141414 !important;border-bottom: 1px solid #2a2a2a !important;}
	body:not(.lockscreen) .upos-settings-box-head .box-title {font-size: 16px !important;font-weight: 700 !important;margin: 0 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-box-head .box-title{color: #fafafa !important;}
	body:not(.lockscreen) .upos-settings-box-head .box-title .fa {margin-right: 8px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-box-head .box-title .fa{color: #f5c518 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-body{background: #111111 !important;}
	body:not(.lockscreen) .upos-settings-nav.pos-tab-menu {padding: 12px 10px !important;min-height: 520px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav.pos-tab-menu{background: #0c0c0c !important;border-right: 1px solid #2a2a2a !important;}
	body:not(.lockscreen) .upos-settings-nav-list {position: relative;margin: 0 !important;padding-left: 10px !important;border: 0 !important;}
	/* UltimatePOS-style gold rail for submenu */
	body:not(.lockscreen) .upos-settings-nav-list::before {content: '';position: absolute;left: 18px;top: 8px;bottom: 8px;width: 2px;border-radius: 2px;opacity: 0.85;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav-list::before{background: #f5c518;}
	body:not(.lockscreen) .upos-settings-nav .list-group-item,
	body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a,
	body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.list-group-item {display: flex !important;align-items: center !important;gap: 10px !important;text-align: left !important;background: transparent !important;color: #c4c4c4 !important;border: 0 !important;border-radius: 8px !important;margin: 2px 0 2px 14px !important;padding: 9px 12px !important;font-size: 13px !important;font-weight: 600 !important;position: relative;transition: background .15s ease, color .15s ease;}
	body:not(.lockscreen) .upos-settings-nav .list-group-item > i,
	body:not(.lockscreen) .upos-settings-nav .list-group-item .fa {width: 18px;text-align: center;flex-shrink: 0;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item > i,
html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item .fa{color: #a3a3a3 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a:hover{background: #1a1a1a !important;color: #fafafa !important;}
	body:not(.lockscreen) .upos-settings-nav .list-group-item.active,
	body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.active,
	body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.active.list-group-item {border: 1px solid #333 !important;box-shadow: none !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item.active,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.active,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.active.list-group-item{background: #1a1a1a !important;color: #fafafa !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item.active > i,
html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item.active .fa{color: #f5c518 !important;}
	body:not(.lockscreen) .upos-settings-nav .list-group-item.active::before {content: '';position: absolute;left: -14px;top: 50%;transform: translateY(-50%);width: 8px;height: 8px;border-radius: 50%;box-shadow: 0 0 0 3px rgba(245,197,24,0.2);}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav .list-group-item.active::before{background: #f5c518;}
	body:not(.lockscreen) div.pos-tab-menu.upos-settings-nav div.list-group > a.active:after {display: none !important;}
	body:not(.lockscreen) .upos-settings-content.pos-tab {padding: 0 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-content.pos-tab{background: #111111 !important;}
	body:not(.lockscreen) .upos-settings-content div.pos-tab-content,
	body:not(.lockscreen) div.pos-tab-content {padding: 18px 20px 24px !important;min-height: 480px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-content div.pos-tab-content,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content{background: #111111 !important;color: #e5e5e5 !important;}
	body:not(.lockscreen) .upos-settings-content .well,
	body:not(.lockscreen) div.pos-tab-content .well {border-radius: 10px !important;box-shadow: none !important;margin-bottom: 16px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-content .well,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .well{background: #141414 !important;border: 1px solid #2a2a2a !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content label,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .control-label,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content h4,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content h5,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content p,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .help-block,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content small{color: #e5e5e5 !important;}
	body:not(.lockscreen) div.pos-tab-content h4 {font-size: 15px !important;font-weight: 700 !important;margin: 0 0 12px !important;padding-bottom: 8px;}html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content h4{border-bottom: 1px solid #2a2a2a;}
	body:not(.lockscreen) div.pos-tab-content .form-control,
	body:not(.lockscreen) div.pos-tab-content .select2-container--default .select2-selection--single {border-color: #333 !important;border-radius: 8px !important;min-height: 36px;}html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .form-control,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .select2-container--default .select2-selection--single{background: #0a0a0a !important;color: #f5f5f5 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .input-group-addon{background: #141414 !important;color: #f5c518 !important;border-color: #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content input[type="file"]{color: #cbd5e1 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .checkbox label,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .radio label{color: #e5e5e5 !important;}
	body:not(.lockscreen) .upos-settings-footer {padding: 14px 16px !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-footer{background: #141414 !important;border-top: 1px solid #2a2a2a !important;}
	body:not(.lockscreen) .upos-settings-save.btn-success {background: #16a34a !important;border-color: #15803d !important;font-weight: 700 !important;border-radius: 8px !important;padding: 10px 28px !important;box-shadow: 0 6px 16px rgba(22,163,74,.25);}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-save.btn-success{color: #fff !important;}
	body:not(.lockscreen) .upos-settings-save.btn-success:hover {filter: brightness(1.06);}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .pos-tab-container .tw-bg-gray-50{background: rgba(15, 23, 42, .75) !important;border-color: #2a2a2a !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .content-header .input-group-addon{background: #141414 !important;color: #cbd5e1 !important;border-color: #333333 !important;}
	@media (max-width: 767px) {
		body:not(.lockscreen) .upos-settings-nav.pos-tab-menu {min-height: 0;border-right: 0 !important;max-height: 220px;overflow: auto;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-nav.pos-tab-menu{border-bottom: 1px solid #2a2a2a !important;}
		body:not(.lockscreen) .upos-settings-nav-list::before {display: none;}
		body:not(.lockscreen) .upos-settings-nav .list-group-item {margin-left: 0 !important;}
		body:not(.lockscreen) .upos-settings-nav .list-group-item.active::before {display: none;}
	}

	/* Product & form pages — TinyMCE, select2, file inputs */
	html[data-vspos-theme="dark"] body:not(.lockscreen) .tox-tinymce{border-color: #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-toolbar-overlord,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-toolbar__primary{background: #141414 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-tbtn,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-tbtn svg{color: #cbd5e1 !important;fill: #cbd5e1 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-statusbar{background: #111111 !important;border-color: #2a2a2a !important;color: #94a3b8 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .tox .tox-edit-area__iframe{background: #141414 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--multiple{background: rgba(26, 26, 26, .85) !important;border: 1px solid #333333 !important;}
	body:not(.lockscreen) .select2-container--default .select2-selection--multiple .select2-selection__rendered {background: transparent !important;}
	body:not(.lockscreen) .select2-container--default .select2-selection--multiple .select2-selection__choice {border-color: #1d4ed8 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--multiple .select2-selection__choice{background: #f5c518 !important;color: #fff !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-selection--multiple .select2-selection__choice__remove{color: #fff !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .select2-container--default .select2-search--inline .select2-search__field{color: #f8fafc !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) input[type="file"]{color: #cbd5e1 !important;}
	body:not(.lockscreen) input[type="file"]::file-selector-button {border-radius: 6px !important;padding: 4px 12px !important;margin-right: 8px !important;cursor: pointer !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) input[type="file"]::file-selector-button{background: #141414 !important;color: #e2e8f0 !important;border: 1px solid #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .btn.bg-white,
html[data-vspos-theme="dark"] body:not(.lockscreen) .bg-white.btn-flat{background: #141414 !important;color: #e2e8f0 !important;border: 1px solid #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body .form-group label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .box .box-body label{color: #e2e8f0 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .add-product-price-table{background: #111111 !important;border-color: #2a2a2a !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .add-product-price-table td{background: #111111 !important;color: #e2e8f0 !important;border-color: #2a2a2a !important;}

	/* Modals — dark theme on screen only (print stays light) */
	@media screen {
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-content{background: linear-gradient(160deg, rgba(15, 23, 42, .98), rgba(15, 23, 42, .92)) !important;border: 1px solid #2a2a2a !important;color: #e2e8f0 !important;box-shadow: 0 20px 50px rgba(2, 6, 23, .5) !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-header{background: linear-gradient(135deg, #141414 0%, #1a1a1a 55%, #222222 100%) !important;border-bottom: 1px solid #2a2a2a !important;color: #f8fafc !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-header .modal-title,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-header .modal-title i{color: #f8fafc !important;}
	body:not(.lockscreen) .modal-header .close {opacity: .85;text-shadow: none;}html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-header .close{color: #f8fafc !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-body{background: #050505 !important;color: #e2e8f0 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-body label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-body .control-label,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-body .help-block{color: #e2e8f0 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal-footer{background: rgba(20, 20, 20, .95) !important;border-top: 1px solid #2a2a2a !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal .input-group-addon{background: #141414 !important;color: #cbd5e1 !important;border-color: #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal .form-control,
html[data-vspos-theme="dark"] body:not(.lockscreen) .modal textarea.form-control{background: rgba(26, 26, 26, .85) !important;border-color: #333333 !important;color: #f8fafc !important;}
	body:not(.lockscreen) .modal .form-control::placeholder {color: #64748b !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal .select2-container--default .select2-selection--single{background: rgba(26, 26, 26, .85) !important;border-color: #333333 !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal .select2-container--default .select2-selection--single .select2-selection__rendered{color: #f8fafc !important;}
	html[data-vspos-theme="dark"] body:not(.lockscreen) .modal .select2-container--default .select2-selection--single .select2-selection__arrow b{border-color: #cbd5e1 transparent transparent transparent !important;}
	} /* end @media screen — modals */

	/* Global tax system disabled */
	body.tax-system-disabled .tax-field,
	body.tax-system-disabled .tax-col,
	body.tax-system-disabled #tax_rate_id,
	body.tax-system-disabled #order_tax,
	body.tax-system-disabled #pos-edit-tax,
	body.tax-system-disabled #posEditOrderTaxModal,
	body.tax-system-disabled .item_tax,
	body.tax-system-disabled .purchase_product_unit_tax,
	body.tax-system-disabled .order_tax_section,
	body.tax-system-disabled .inline_tax_col,
	body.tax-system-disabled .tax_id,
	body.tax-system-disabled .product_unit_tax,
	body.tax-system-disabled [data-tax-field="1"],
	body.tax-system-disabled th.tax-col,
	body.tax-system-disabled td.tax-col {display: none !important;}

    /* Vsoft retail accent — outline buttons on admin/list pages */
    body:not(.lockscreen) .tw-dw-btn-outline,
    body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-info,
    body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-primary,
    body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-accent {border: 1px solid rgba(245, 197, 24, 0.45) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-info,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-primary,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-accent{background: #141414 !important;color: #f5c518 !important;}
    body:not(.lockscreen) .tw-dw-btn-outline:hover,
    body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-info:hover,
    body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-primary:hover {background: rgba(245, 197, 24, 0.12) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-info:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .tw-dw-btn-outline.tw-dw-btn-primary:hover{color: #f5c518 !important;border-color: #f5c518 !important;}
    body:not(.lockscreen) .btn-group .dropdown-toggle {border: 1px solid rgba(245, 197, 24, 0.45) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-group .dropdown-toggle{background: #141414 !important;color: #f5c518 !important;}
    body:not(.lockscreen) .btn-group.open .dropdown-toggle,
    body:not(.lockscreen) .btn-group .dropdown-toggle:hover {background: rgba(245, 197, 24, 0.12) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-group.open .dropdown-toggle,
html[data-vspos-theme="dark"] body:not(.lockscreen) .btn-group .dropdown-toggle:hover{color: #f5c518 !important;border-color: #f5c518 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dropdown-menu{background: #141414 !important;border: 1px solid #2a2a2a !important;}
    body:not(.lockscreen) .dropdown-menu.vspos-dt-dropdown {min-width: 160px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .dropdown-menu.vspos-dt-dropdown{background: #141414 !important;border: 1px solid #2a2a2a !important;box-shadow: 0 12px 28px rgba(0, 0, 0, 0.55) !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .dropdown-menu > li > a{color: #f5f5f5 !important;}
    body:not(.lockscreen) .dropdown-menu > li > a:hover {background: rgba(245, 197, 24, 0.12) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .dropdown-menu > li > a:hover{color: #f5c518 !important;}

    /* Date range picker — readable on dark admin theme */
    body:not(.lockscreen) .daterangepicker {z-index: 3000 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker{background: #141414 !important;border: 1px solid #2a2a2a !important;color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker:before,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker:after{border-bottom-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .calendar-table{background: #141414 !important;border-color: #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.off,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.off.in-range,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.off.start-date,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.off.end-date{background: #0a0a0a !important;color: #737373 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.available,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker th.available{color: #e5e5e5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.available:hover,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker th.available:hover{background: #222222 !important;color: #fafafa !important;}
    body:not(.lockscreen) .daterangepicker td.in-range {background: rgba(245, 197, 24, 0.12) !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.in-range{color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.active,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker td.active:hover{background: #f5c518 !important;color: #0a0a0a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .calendar-table .next span,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .calendar-table .prev span{border-color: #e5e5e5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .drp-buttons{border-top: 1px solid #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .drp-selected{color: #a3a3a3 !important;}
    body:not(.lockscreen) .daterangepicker .ranges li {background: transparent !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .ranges li{color: #e5e5e5 !important;}
    body:not(.lockscreen) .daterangepicker .ranges li:hover {border-color: transparent !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .ranges li:hover{background: #222222 !important;color: #fafafa !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .ranges li.active{background: #f5c518 !important;color: #0a0a0a !important;border-color: #f5c518 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker select.monthselect,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker select.yearselect,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker select.hourselect,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker select.minuteselect,
html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker select.ampmselect{background: #0a0a0a !important;color: #f5f5f5 !important;border: 1px solid #2a2a2a !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .daterangepicker .input-mini{background: #0a0a0a !important;color: #f5f5f5 !important;border: 1px solid #2a2a2a !important;}

    body:not(.lockscreen) .box,
    body:not(.lockscreen) .box-body {overflow: visible !important;}
    /* Purchase add/edit product grid must scroll above totals bar */
    body:not(.lockscreen) .purchase-entry-scroll,
    body:not(.lockscreen) .vspos-purchase-entry-scroll,
    body:not(.lockscreen) #purchase_entry_scroll,
    body:not(.lockscreen) .table-responsive.purchase-entry-scroll,
    body:not(.lockscreen) .table-responsive.vspos-purchase-entry-scroll {overflow-x: auto !important;overflow-y: scroll !important;min-height: 260px !important;}
    /*
     * DataTables scrollX/scrollY creates .dataTables_scrollBody. Forcing
     * overflow:visible here disables inner scrolling, so wide tables expand
     * the page and the card/header slide away. Actions menus are relocated
     * to <body> in common.js, so they do not need overflow:visible.
     */
    body:not(.lockscreen) .dataTables_wrapper {max-width: 100%;}
    body:not(.lockscreen) .dataTables_wrapper .dataTables_scroll {max-width: 100%;overflow: hidden;}
    body:not(.lockscreen) .dataTables_wrapper .dataTables_scrollHead,
    body:not(.lockscreen) .dataTables_wrapper .dataTables_scrollFoot {max-width: 100%;overflow: hidden;}
    body:not(.lockscreen) .dataTables_wrapper .dataTables_scrollBody,
    body:not(.lockscreen) div.dataTables_wrapper div.dataTables_scrollBody {overflow-x: auto !important;overflow-y: auto !important;max-width: 100%;}
    body:not(.lockscreen) .table-responsive.vspos-table-scroll-x,
    body:not(.lockscreen) .vspos-purchase-table-wrap,
    body:not(.lockscreen) .vspos-sell-table-wrap,
    body:not(.lockscreen) .table-responsive.pvs-table-scroll,
    body:not(.lockscreen) .pvs-table-scroll {max-width: 100% !important;overflow: hidden !important;}
    body:not(.lockscreen) .vspos-table-scroll-x div.dataTables_wrapper,
    body:not(.lockscreen) .pvs-table-scroll div.dataTables_wrapper {overflow: hidden !important;max-width: 100% !important;}
    body:not(.lockscreen) .vspos-table-scroll-x div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-purchase-table-wrap div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-sell-table-wrap div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .vspos-table-scroll-x .dataTables_scrollBody,
    body:not(.lockscreen) .vspos-purchase-table-wrap .dataTables_scrollBody,
    body:not(.lockscreen) .vspos-sell-table-wrap .dataTables_scrollBody,
    body:not(.lockscreen) .pvs-table-scroll div.dataTables_wrapper div.dataTables_scrollBody,
    body:not(.lockscreen) .pvs-table-scroll .dataTables_scrollBody {overflow-x: auto !important;overflow-y: auto !important;max-width: 100% !important;}
    /* Product view modal: body scrolls; expenses never clipped */
    body:not(.lockscreen) .modal-content.product-view-sheet {max-height: calc(100vh - 32px) !important;}
    body:not(.lockscreen) .product-view-sheet > .modal-body {overflow-x: hidden !important;overflow-y: auto !important;max-height: calc(100vh - 150px) !important;padding-bottom: 20px !important;}

    /* Profile / list-group cards — no white panels on dark theme */
    html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-item{background: #0a0a0a !important;border-color: #2a2a2a !important;color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-unbordered > .list-group-item{border-color: #2a2a2a !important;}
    body:not(.lockscreen) .list-group-item > b,
    body:not(.lockscreen) .list-group-item strong {font-weight: 700;}html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-item > b,
html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-item strong{color: #a3a3a3 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-item > a,
html[data-vspos-theme="dark"] body:not(.lockscreen) .list-group-item > a.pull-right{color: #f5c518 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .box-profile .profile-username{color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .profile-user-img{border: 3px solid #2a2a2a !important;}

    /* Info popovers — readable on dark theme (avoid white-on-white) */
    body:not(.lockscreen) .popover,
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover {
        background: #141414 !important;
        border: 1px solid #2a2a2a !important;
        color: #f5f5f5 !important;
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.45);
        max-width: 320px;
    }
    body:not(.lockscreen) .popover .popover-title,
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover .popover-title {
        background: #1a1a1a !important;
        border-bottom: 1px solid #2a2a2a !important;
        color: #f5c518 !important;
        font-weight: 700;
    }
    body:not(.lockscreen) .popover .popover-content,
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover .popover-content {
        background: #141414 !important;
        color: #f5f5f5 !important;
        font-size: 13px;
        line-height: 1.45;
    }
    body:not(.lockscreen) .popover.top > .arrow,
    body:not(.lockscreen) .popover.bottom > .arrow,
    body:not(.lockscreen) .popover.left > .arrow,
    body:not(.lockscreen) .popover.right > .arrow {border-color: transparent !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover.top > .arrow:after{border-top-color: #141414 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover.bottom > .arrow:after{border-bottom-color: #141414 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover.left > .arrow:after{border-left-color: #141414 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .popover.right > .arrow:after{border-right-color: #141414 !important;}
    /* Calculator popover keeps its own layout; force dark chrome only */
    body:not(.lockscreen) .popover:has(#calculator) {max-width: none;}

    /* Settings file Browse button — match dark theme */
    body:not(.lockscreen) .upos-settings-content .btn-file,
    body:not(.lockscreen) div.pos-tab-content .btn-file,
    body:not(.lockscreen) div.pos-tab-content label.btn-primary,
    body:not(.lockscreen) div.pos-tab-content .btn-primary {border-color: #333 !important;}html[data-vspos-theme="dark"] body:not(.lockscreen) .upos-settings-content .btn-file,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .btn-file,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content label.btn-primary,
html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .btn-primary{background: #1a1a1a !important;color: #f5c518 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .select2-selection__rendered{color: #f5f5f5 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) div.pos-tab-content .select2-selection__arrow b{border-color: #a3a3a3 transparent transparent transparent !important;}

    /* Cheque details panel — admin / sell (matches retail theme) */
    body:not(.lockscreen) .vspos-cheque-details-panel {border-radius: 12px;padding: 14px 16px;margin: 4px 0 10px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-details-panel{background: #141414 !important;border: 1px solid #2a2a2a !important;}
    body:not(.lockscreen) .vspos-cheque-details-title {font-size: 0.75rem;font-weight: 700;text-transform: uppercase;letter-spacing: 0.04em;margin-bottom: 12px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-details-title{color: #a3a3a3;}
    body:not(.lockscreen) .vspos-cheque-details-title i {margin-right: 6px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-details-title i{color: #f5c518;}
    body:not(.lockscreen) .vspos-cheque-details-grid {display: grid;grid-template-columns: 1fr 1fr;gap: 12px 14px;align-items: end;}
    @media (max-width: 767px) {
        body:not(.lockscreen) .vspos-cheque-details-grid {grid-template-columns: 1fr;}
    }
    body:not(.lockscreen) .vspos-cheque-field {margin: 0;min-width: 0;}
    body:not(.lockscreen) .vspos-cheque-label {display: block;font-weight: 600;margin: 0 0 5px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-label{color: #d4d4d4 !important;}
    html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-addon{background: #1a1a1a !important;border-color: #2a2a2a !important;color: #f5c518 !important;}
    body:not(.lockscreen) .vspos-cheque-help {font-size: 11px;margin: 10px 0 0;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-help{color: #a3a3a3;}
    body:not(.lockscreen) .vspos-cheque-help i {margin-right: 4px;}html[data-vspos-theme="dark"] body:not(.lockscreen) .vspos-cheque-help i{color: #f5c518;}
    body:not(.lockscreen) .vspos-cheque-walkin-note {color: #f87171;}

    /* Print: beat dark-theme specificity for all admin reports + modals */
    @media print {
        body:not(.lockscreen),
        body:not(.lockscreen) .thetop,
        body:not(.lockscreen) #scrollable-container,
        body:not(.lockscreen) main,
        body:not(.lockscreen) .content-wrapper,
        body:not(.lockscreen) .right-side,
        body:not(.lockscreen) .wrapper,
        body:not(.lockscreen) .content,
        body:not(.lockscreen) section.content,
        body:not(.lockscreen) .content-header,
        body:not(.lockscreen) .box,
        body:not(.lockscreen) .box-solid,
        body:not(.lockscreen) .box-body,
        body:not(.lockscreen) .box-header,
        body:not(.lockscreen) .box-footer,
        body:not(.lockscreen) .table,
        body:not(.lockscreen) .table th,
        body:not(.lockscreen) .table td,
        body:not(.lockscreen) .modal,
        body:not(.lockscreen) .modal-dialog,
        body:not(.lockscreen) .modal-content,
        body:not(.lockscreen) .modal-body,
        body:not(.lockscreen) .modal-header,
        body:not(.lockscreen) .modal-footer,
        body:not(.lockscreen) .product-view-sheet,
        body:not(.lockscreen) .product-view-sheet .pvs-expenses,
        body:not(.lockscreen) .product-view-sheet .pvs-exp-item,
        body:not(.lockscreen) .product-view-sheet .pvs-table,
        body:not(.lockscreen) .product-view-sheet .pvs-table th,
        body:not(.lockscreen) .product-view-sheet .pvs-table td {
            background: #ffffff !important;
            background-color: #ffffff !important;
            background-image: none !important;
            color: #000000 !important;
            box-shadow: none !important;
        }
        body:not(.lockscreen) .box-title,
        body:not(.lockscreen) .content-header > h1,
        body:not(.lockscreen) .tw-text-black,
        body:not(.lockscreen) .tw-text-gray-100,
        body:not(.lockscreen) span,
        body:not(.lockscreen) td,
        body:not(.lockscreen) th,
        body:not(.lockscreen) .modal-title,
        body:not(.lockscreen) .product-view-sheet b,
        body:not(.lockscreen) .product-view-sheet .lbl,
        body:not(.lockscreen) .product-view-sheet .val,
        body:not(.lockscreen) .product-view-sheet .text-muted {
            color: #000000 !important;
        }
        body:not(.lockscreen) .no-print,
        body:not(.lockscreen) .side-bar,
        body:not(.lockscreen) .thetop > aside,
        body:not(.lockscreen) .modal-footer {
            display: none !important;
        }
    }
</style>
@if(!empty($__system_settings['additional_css']))
    {!! $__system_settings['additional_css'] !!}
@endif

