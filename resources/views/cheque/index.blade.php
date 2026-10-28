@extends('layouts.app')
@section('title', 'Cheque Management')

@section('css')
<style>
    /* Page-level styles — retail charcoal + yellow theme */
    .cheque-page { padding: 0; color: #e5e5e5; }

    /* Hero bar */
    .cheque-hero {
        background: linear-gradient(135deg, #0a0a0a 0%, #111111 55%, #1a1a1a 100%);
        padding: 24px 28px 28px;
        margin-bottom: 0;
        position: relative;
        overflow: hidden;
        border-bottom: 2px solid #f5c518;
    }
    .cheque-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.04);
    }
    .cheque-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; left: 30%;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,.03);
    }
    .cheque-hero h1 {
        color: #f5f5f5 !important;
        font-size: 1.5rem !important;
        font-weight: 700 !important;
        margin: 0 0 4px 0 !important;
        letter-spacing: -.03em;
        display: flex !important;
        align-items: center;
        gap: 10px;
    }
    .cheque-hero p { color: #a3a3a3; font-size: 0.875rem; margin: 0; }

    /* Stat cards row */
    .cheque-stats {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        padding: 20px 24px;
        background: #050505;
    }
    @media (max-width: 767px) {
        .cheque-stats { grid-template-columns: 1fr; padding: 12px; }
        .cheque-hero { padding: 18px 16px 22px; }
        .cheque-hero h1 { font-size: 1.2rem !important; }
    }
    .cstat {
        background: linear-gradient(160deg, rgba(17, 17, 17, .95), rgba(17, 17, 17, .78));
        border: 1px solid rgba(42, 42, 42, .22);
        border-radius: 14px;
        padding: 18px 20px;
        box-shadow: 0 12px 30px rgba(2, 6, 23, .35);
        display: flex;
        align-items: center;
        gap: 14px;
        transition: box-shadow .2s, transform .2s, border-color .2s;
        text-decoration: none;
    }
    .cstat:hover {
        box-shadow: 0 16px 36px rgba(2, 6, 23, .45);
        transform: translateY(-2px);
        text-decoration: none;
        border-color: rgba(245, 197, 24, .35);
    }
    .cstat-icon {
        width: 48px; height: 48px;
        border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }
    .cstat-icon.yellow { background: rgba(251, 191, 36, .16); color: #fbbf24; }
    .cstat-icon.red    { background: rgba(248, 113, 113, .16); color: #f87171; }
    .cstat-icon.green  { background: rgba(74, 222, 128, .16); color: #4ade80; }
    .cstat-label { font-size: 0.72rem; font-weight: 700; color: #a3a3a3; text-transform: uppercase; letter-spacing: .06em; margin-bottom: 3px; }
    .cstat-value { font-size: 1.5rem; font-weight: 800; color: #f5f5f5; line-height: 1; font-variant-numeric: tabular-nums; }

    /* Table section */
    .cheque-table-section {
        padding: 0 24px 28px;
        background: #050505;
    }
    @media (max-width: 767px) { .cheque-table-section { padding: 0 12px 16px; } }

    .cheque-card {
        background: linear-gradient(160deg, rgba(17, 17, 17, .95), rgba(17, 17, 17, .78));
        border: 1px solid rgba(42, 42, 42, .22);
        border-radius: 16px;
        box-shadow: 0 12px 30px rgba(2, 6, 23, .35);
        overflow: visible;
    }
    .cheque-card .cheque-card-header {
        border-radius: 16px 16px 0 0;
        overflow: hidden;
    }
    .cheque-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid rgba(42, 42, 42, .2);
        display: flex;
        align-items: center;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 10px;
    }
    .cheque-card-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #f5f5f5;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cheque-card-title i { color: #f5c518 !important; }
    .cheque-toolbar-divider {
        width: 1px;
        height: 24px;
        background: rgba(42, 42, 42, .28);
    }
    .filter-tabs {
        display: flex;
        gap: 4px;
        background: rgba(17, 17, 17, .85);
        border: 1px solid rgba(42, 42, 42, .18);
        border-radius: 10px;
        padding: 3px;
    }
    .filter-tab {
        padding: 5px 14px;
        border-radius: 8px;
        font-size: 0.78rem;
        font-weight: 600;
        color: #a3a3a3;
        cursor: pointer;
        border: none;
        background: transparent;
        transition: all .15s;
    }
    .filter-tab.active {
        background: rgba(245, 197, 24, .14);
        color: #f5c518;
        box-shadow: 0 1px 3px rgba(0,0,0,.25);
        border: 1px solid rgba(245, 197, 24, .35);
    }
    .filter-tab:hover:not(.active) { color: #d4d4d4; }

    /* Fix dropdown clipping inside DataTables */
    .cheque-card .dataTables_wrapper,
    .cheque-card .dataTables_scroll,
    .cheque-card .dataTables_scrollBody {
        overflow: visible !important;
    }
    .cheque-card table.dataTable {
        overflow: visible !important;
    }
    .cheque-card .btn-group .dropdown-menu {
        position: absolute !important;
        z-index: 9999 !important;
        background: #111111 !important;
        border: 1px solid rgba(42, 42, 42, .25) !important;
    }
    .cheque-card .btn-group .dropdown-menu > li > a {
        color: #d4d4d4 !important;
    }
    .cheque-card .btn-group .dropdown-menu > li > a:hover {
        background: rgba(34, 34, 34, .95) !important;
        color: #f5f5f5 !important;
    }
    #chequeReportsModal .modal-content {
        background: linear-gradient(160deg, rgba(17, 17, 17, .98), rgba(17, 17, 17, .9)) !important;
        border: 1px solid rgba(42, 42, 42, .22) !important;
        color: #d4d4d4 !important;
    }
    #chequeReportsModal .modal-header {
        background: linear-gradient(135deg, #111111 0%, #141414 55%, #2a2a2a 100%) !important;
        border-bottom: 1px solid rgba(42, 42, 42, .2) !important;
        color: #f5f5f5 !important;
    }
    #chequeReportsModal .modal-header .close {
        color: #f5f5f5 !important;
        opacity: .85;
    }
    #chequeReportsModal .modal-title {
        color: #f5f5f5 !important;
    }
    #chequeReportsModal .modal-body {
        background: #050505 !important;
        padding: 20px !important;
    }
    #chequeReportsModal .modal-footer {
        background: rgba(17, 17, 17, .95) !important;
        border-top: 1px solid rgba(42, 42, 42, .2) !important;
    }
    .cheque-report-summary {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
        margin-bottom: 20px;
    }
    @media (max-width: 991px) {
        .cheque-report-summary { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 575px) {
        .cheque-report-summary { grid-template-columns: 1fr; }
    }
    .cheque-report-stat {
        background: rgba(17, 17, 17, .9);
        border: 1px solid rgba(42, 42, 42, .2);
        border-radius: 12px;
        padding: 14px 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        min-height: 84px;
    }
    .cheque-report-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }
    .cheque-report-stat-icon.yellow { background: rgba(251, 191, 36, .16); color: #fbbf24; }
    .cheque-report-stat-icon.green { background: rgba(74, 222, 128, .16); color: #4ade80; }
    .cheque-report-stat-icon.red { background: rgba(248, 113, 113, .16); color: #f87171; }
    .cheque-report-stat-icon.blue { background: rgba(245, 197, 24, .16); color: #f5c518; }
    .cheque-report-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #a3a3a3;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 4px;
    }
    .cheque-report-stat-value {
        font-size: 1.35rem;
        font-weight: 800;
        color: #f5f5f5;
        line-height: 1.1;
        font-variant-numeric: tabular-nums;
        word-break: break-word;
    }
    .cheque-report-panel {
        background: rgba(17, 17, 17, .9);
        border: 1px solid rgba(42, 42, 42, .2);
        border-radius: 12px;
        overflow: hidden;
        height: 100%;
    }
    .cheque-report-panel-header {
        padding: 12px 16px;
        border-bottom: 1px solid rgba(42, 42, 42, .18);
        font-size: 0.92rem;
        font-weight: 700;
        color: #f5f5f5;
    }
    .cheque-report-panel-header.supplier { border-top: 3px solid #f5c518; }
    .cheque-report-panel-header.customer { border-top: 3px solid #4ade80; }
    .cheque-report-panel-body { padding: 0; }
    #chequeReportsModal .cheque-report-table {
        margin-bottom: 0;
        background: transparent !important;
    }
    #chequeReportsModal .cheque-report-table td {
        color: #d4d4d4 !important;
        border-color: rgba(42, 42, 42, .14) !important;
        background: transparent !important;
        padding: 10px 16px !important;
        vertical-align: middle !important;
    }
    #chequeReportsModal .cheque-report-table tr:not(.cheque-report-total-row):hover td {
        background: rgba(26, 26, 26, .55) !important;
    }
    #chequeReportsModal .table tr.cheque-report-total-row {
        background: rgba(26, 26, 26, .85) !important;
    }
    #chequeReportsModal .table tr.cheque-report-total-row td {
        color: #f5f5f5 !important;
        font-weight: 700;
    }
    #chequeReportsModal .badge {
        font-size: 11px !important;
        padding: 4px 8px !important;
        border-radius: 6px !important;
    }
    #chequeReportsModal .alert-warning {
        background: rgba(251, 191, 36, .12) !important;
        border-color: rgba(251, 191, 36, .35) !important;
        color: #fde68a !important;
    }
    #chequeReportsModal .alert-warning h4,
    #chequeReportsModal .alert-warning p,
    #chequeReportsModal .alert-warning strong {
        color: #fde68a !important;
    }
    #viewChequeModal .modal-body {
        background: #111111 !important;
    }
    #viewChequeModal .modal-footer {
        background: rgba(17, 17, 17, .95) !important;
        border-top: 1px solid rgba(42, 42, 42, .2) !important;
    }
    .cheque-meta-card {
        background: rgba(17, 17, 17, .85);
        border: 1px solid rgba(42, 42, 42, .2);
        border-radius: 8px;
        padding: 12px 14px;
    }
    .cheque-meta-label {
        font-size: 0.65rem;
        color: #a3a3a3;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        margin-bottom: 4px;
    }
    .cheque-meta-value {
        font-size: 0.85rem;
        font-weight: 600;
        color: #f5f5f5;
    }

    /* Edit / Add / View modals — charcoal + yellow */
    #editChequeModal .modal-content,
    #addSupplierChequeModal .modal-content,
    #viewChequeModal .modal-content {
        background: linear-gradient(160deg, rgba(17, 17, 17, .98), rgba(17, 17, 17, .9)) !important;
        border: 1px solid rgba(42, 42, 42, .22) !important;
        color: #d4d4d4 !important;
        border-radius: 14px !important;
        overflow: hidden;
    }
    #editChequeModal .modal-header,
    #addSupplierChequeModal .modal-header,
    #viewChequeModal .modal-header {
        background: linear-gradient(135deg, #0a0a0a 0%, #111111 55%, #1a1a1a 100%) !important;
        border-bottom: 2px solid #f5c518 !important;
        color: #f5f5f5 !important;
    }
    #editChequeModal .modal-header .close,
    #addSupplierChequeModal .modal-header .close,
    #viewChequeModal .modal-header .close {
        color: #f5f5f5 !important;
        opacity: .85;
        text-shadow: none;
    }
    #editChequeModal .modal-title,
    #addSupplierChequeModal .modal-title,
    #viewChequeModal .modal-title {
        color: #f5f5f5 !important;
        font-weight: 700;
    }
    #editChequeModal .modal-title i,
    #addSupplierChequeModal .modal-title i,
    #viewChequeModal .modal-title i {
        color: #f5c518 !important;
        margin-right: 6px;
    }
    #editChequeModal .modal-body,
    #addSupplierChequeModal .modal-body {
        background: #050505 !important;
        color: #e5e5e5 !important;
    }
    #editChequeModal .modal-footer,
    #addSupplierChequeModal .modal-footer,
    #viewChequeModal .modal-footer {
        background: rgba(17, 17, 17, .95) !important;
        border-top: 1px solid rgba(42, 42, 42, .2) !important;
    }
    #editChequeModal .form-control,
    #addSupplierChequeModal .form-control,
    #editChequeModal .select2-container--default .select2-selection--single,
    #addSupplierChequeModal .select2-container--default .select2-selection--single {
        background: #141414 !important;
        border-color: #2a2a2a !important;
        color: #f5f5f5 !important;
    }
    #editChequeModal label,
    #addSupplierChequeModal label {
        color: #a3a3a3 !important;
        font-weight: 600;
    }
    #editChequeModal .input-group-addon,
    #addSupplierChequeModal .input-group-addon {
        background: #1a1a1a !important;
        border-color: #2a2a2a !important;
        color: #f5c518 !important;
    }
    #viewChequeModal .modal-header {
        border-bottom: 2px solid #f5c518 !important;
    }
</style>
@endsection

@section('content')
<div class="cheque-page">

    {{-- Hero --}}
    <div class="cheque-hero">
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 5m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z"/><path d="M3 10l18 0"/><path d="M7 15l.01 0"/><path d="M11 15l6 0"/>
            </svg>
            Cheque Management
        </h1>
        <p>Track, manage, and get reminded about all cheque payments in one place.</p>
    </div>

    {{-- Stats --}}
    <div class="cheque-stats">
        <a href="#" class="cstat" onclick="filterTable('pending'); return false;">
            <div class="cstat-icon yellow"><i class="fas fa-clock"></i></div>
            <div>
                <div class="cstat-label">Pending</div>
                <div class="cstat-value" id="pending_count">—</div>
            </div>
        </a>
        <a href="#" class="cstat" onclick="filterTable('pending'); return false;">
            <div class="cstat-icon red"><i class="fas fa-exclamation-circle"></i></div>
            <div>
                <div class="cstat-label">Overdue</div>
                <div class="cstat-value" id="overdue_count">—</div>
            </div>
        </a>
        <div class="cstat">
            <div class="cstat-icon green"><i class="fas fa-money-bill-wave"></i></div>
            <div>
                <div class="cstat-label">Pending Amount</div>
                <div class="cstat-value" id="pending_total">—</div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="cheque-table-section">
        <div class="cheque-card">
            <div class="cheque-card-header">
                <div class="cheque-card-title">
                    <i class="fas fa-list-ul"></i>
                    All Cheques
                </div>
                <div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
                    {{-- Cheque Type Tabs --}}
                    <div class="filter-tabs" id="cheque_type_tabs" style="margin-right:10px;">
                        <button class="filter-tab active" data-type="">All Cheques</button>
                        <button class="filter-tab" data-type="outgoing">
                            <i class="fas fa-arrow-up" style="font-size:0.7rem;"></i> Supplier
                        </button>
                        <button class="filter-tab" data-type="incoming">
                            <i class="fas fa-arrow-down" style="font-size:0.7rem;"></i> Customer
                        </button>
                    </div>
                    
                    <div class="cheque-toolbar-divider"></div>
                    
                    @if(auth()->user()->can('purchase.payments') || auth()->user()->can('purchase.create'))
                        <button type="button" class="btn btn-sm btn-success" id="btn_add_supplier_cheque" data-toggle="modal" data-target="#addSupplierChequeModal">
                            <i class="fas fa-plus"></i> Pay Supplier
                        </button>
                    @endif
                    
                    <button type="button" class="btn btn-sm btn-info" id="btn_cheque_reports" data-toggle="modal" data-target="#chequeReportsModal">
                        <i class="fas fa-chart-bar"></i> Reports
                    </button>
                    
                    <div class="filter-tabs" id="status_filter_tabs">
                        @foreach($status_filter as $val => $label)
                            <button class="filter-tab {{ $val === '' ? 'active' : '' }}" data-value="{{ $val }}">{{ $label }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div style="padding: 16px 20px; overflow: visible;">
                <div style="overflow-x: auto; overflow-y: visible;">
                    <table id="cheque_table" class="table table-bordered table-hover" style="width:100%;">
                        <thead>
                            <tr>
                                <th>Cheque #</th>
                                <th>Cheque Name</th>
                                <th>Bank</th>
                                <th>Type</th>
                                <th>Customer / Supplier</th>
                                <th>Transaction</th>
                                <th>Amount</th>
                                <th>Received/Paid On</th>
                                <th>Due Date</th>
                                <th>Due Alert</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- Edit Cheque Modal --}}
<div class="modal fade" id="editChequeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-edit"></i> Edit Cheque</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" id="edit_cheque_id">
                <div class="form-group">
                    <label for="edit_cheque_number">Cheque Number <span class="text-danger">*</span></label>
                    <input type="text" id="edit_cheque_number" class="form-control" placeholder="Cheque Number">
                </div>
                <div class="form-group">
                    <label for="edit_cheque_name">Cheque Name</label>
                    <input type="text" id="edit_cheque_name" class="form-control" placeholder="Name on cheque">
                </div>
                <div class="form-group">
                    <label for="edit_cheque_bank_name">Bank</label>
                    @if(\App\Utils\SriLankanBankUtil::enabled())
                        <select id="edit_cheque_bank_name" class="form-control select2">
                            @foreach(\App\Utils\SriLankanBankUtil::options() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="edit_cheque_bank_name" class="form-control" placeholder="Bank Name">
                    @endif
                </div>
                <div class="form-group">
                    <label for="edit_cheque_date">Cheque Due Date</label>
                    <div class="input-group">
                        <span class="input-group-addon" id="edit_cal_icon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="edit_cheque_date" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label for="edit_cheque_status">Status</label>
                    <select id="edit_cheque_status" class="form-control">
                        <option value="pending">Pending</option>
                        <option value="cleared">Cleared</option>
                        <option value="returned">Returned</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_save_cheque_edit"><i class="fas fa-save"></i> Save Changes</button>
            </div>
        </div>
    </div>
</div>

{{-- View Cheque Modal --}}
<div class="modal fade" id="viewChequeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-money-check-alt"></i> Cheque Preview</h4>
            </div>
            <div class="modal-body" style="background:#111111;padding:24px;">
                {{-- Cheque Design --}}
                <div id="cheque_preview_container" style="max-width:760px;margin:0 auto;">
                    <div class="real-cheque" style="background:#fff;border-radius:8px;box-shadow:0 4px 20px rgba(0,0,0,.12);overflow:hidden;position:relative;">
                        {{-- Security background pattern --}}
                        <div style="position:absolute;inset:0;background:repeating-linear-gradient(45deg,#f5f5f5,#f5f5f5 8px,#f1f5f9 8px,#f1f5f9 16px);opacity:0.6;z-index:0;"></div>
                        <div style="position:relative;z-index:1;padding:28px 32px 24px;">
                            {{-- Header --}}
                            <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:18px;border-bottom:2px solid #111111;padding-bottom:12px;">
                                <div>
                                    <div id="view_bank_name" style="font-size:1.15rem;font-weight:800;color:#111111;letter-spacing:-0.01em;">Bank Name</div>
                                    <div style="font-size:0.72rem;color:#64748b;margin-top:2px;font-weight:500;">Authorised by Central Bank of Sri Lanka</div>
                                </div>
                                <div style="text-align:right;">
                                    <div style="font-size:0.65rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.06em;">Cheque No.</div>
                                    <div id="view_cheque_number" style="font-size:1.05rem;font-weight:800;color:#111111;font-family:monospace;letter-spacing:2px;">000000</div>
                                </div>
                            </div>
                            {{-- Date --}}
                            <div style="display:flex;justify-content:flex-end;margin-bottom:14px;align-items:center;gap:8px;">
                                <span style="font-size:0.72rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;">Date</span>
                                <div id="view_cheque_date" style="border-bottom:1px solid #111111;min-width:140px;text-align:center;font-size:0.9rem;font-weight:600;color:#111111;padding:2px 8px;">DD/MM/YYYY</div>
                            </div>
                            {{-- Pay line --}}
                            <div style="margin-bottom:14px;display:flex;align-items:center;gap:10px;">
                                <span style="font-size:0.85rem;color:#111111;font-weight:700;white-space:nowrap;">Pay</span>
                                <div id="view_payee" style="flex:1;border-bottom:1px solid #111111;padding:4px 8px;font-size:0.95rem;font-weight:600;color:#111111;">Payee Name</div>
                                <span style="font-size:0.85rem;color:#111111;font-weight:700;white-space:nowrap;">Or Bearer</span>
                            </div>
                            {{-- Amount in words --}}
                            <div style="margin-bottom:14px;display:flex;align-items:flex-start;gap:10px;">
                                <span style="font-size:0.85rem;color:#111111;font-weight:700;white-space:nowrap;margin-top:4px;">Rupees</span>
                                <div id="view_amount_words" style="flex:1;border-bottom:1px solid #111111;padding:4px 8px;font-size:0.85rem;font-weight:600;color:#111111;line-height:1.5;min-height:32px;">Amount in words</div>
                            </div>
                            {{-- Amount & Signature row --}}
                            <div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:16px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <span style="font-size:0.85rem;color:#111111;font-weight:700;">Rs.</span>
                                    <div id="view_amount_figures" style="border-bottom:1px solid #111111;min-width:160px;padding:4px 8px;font-size:1rem;font-weight:800;color:#111111;font-family:monospace;text-align:right;">0.00</div>
                                </div>
                                <div style="text-align:center;">
                                    <div style="border-bottom:1px solid #111111;min-width:180px;padding:4px 0;"></div>
                                    <div style="font-size:0.65rem;color:#64748b;margin-top:4px;font-weight:500;">Authorised Signature</div>
                                </div>
                            </div>
                            {{-- Crossing & Account Payee --}}
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:8px;padding-top:10px;border-top:1px dashed #cbd5e1;">
                                <div style="display:flex;gap:6px;align-items:center;">
                                    <span style="font-size:0.65rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:0.04em;"><i class="fas fa-lock" style="margin-right:3px;color:#dc2626;"></i>Account Payee Only</span>
                                    <span style="font-size:0.65rem;color:#a3a3a3;">| Not Negotiable</span>
                                </div>
                                <div id="view_cheque_status_badge" style="font-size:0.65rem;font-weight:700;padding:3px 10px;border-radius:20px;background:#fef3c7;color:#b45309;">PENDING</div>
                            </div>
                        </div>
                        {{-- Bottom security strip --}}
                        <div style="height:6px;background:linear-gradient(90deg,#111111 0%,#2a2a2a 50%,#111111 100%);"></div>
                    </div>
                    {{-- Meta info below cheque --}}
                    <div style="margin-top:16px;display:grid;grid-template-columns:repeat(3,1fr);gap:12px;">
                        <div class="cheque-meta-card">
                            <div class="cheque-meta-label">Transaction Ref</div>
                            <div id="view_transaction_ref" class="cheque-meta-value">-</div>
                        </div>
                        <div class="cheque-meta-card">
                            <div class="cheque-meta-label">Paid On</div>
                            <div id="view_paid_on" class="cheque-meta-value">-</div>
                        </div>
                        <div class="cheque-meta-card">
                            <div class="cheque-meta-label">Type</div>
                            <div id="view_cheque_type" class="cheque-meta-value">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print();"><i class="fas fa-print"></i> Print</button>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * { visibility: hidden !important; }
    #viewChequeModal,
    #viewChequeModal * { visibility: visible !important; }
    #viewChequeModal {
        position: absolute !important;
        left: 0 !important;
        top: 0 !important;
        width: 100% !important;
        display: block !important;
        background: #fff !important;
        color: #000 !important;
    }
    #viewChequeModal .modal-dialog,
    #viewChequeModal .modal-content,
    #viewChequeModal .modal-body {
        background: #fff !important;
        color: #000 !important;
        box-shadow: none !important;
        border: none !important;
        width: 100% !important;
        margin: 0 !important;
    }
    #viewChequeModal .modal-header,
    #viewChequeModal .modal-footer { display: none !important; }
    #viewChequeModal .modal-body { padding: 20px !important; }
    #viewChequeModal .real-cheque {
        box-shadow: none !important;
        border: 1px solid #111111 !important;
        background: #fff !important;
        color: #000 !important;
    }
}
</style>

{{-- Add Supplier Cheque Modal --}}
<div class="modal fade" id="addSupplierChequeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-money-check-alt"></i> Pay Supplier by Cheque</h4>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="add_supplier_id">Supplier <span class="text-danger">*</span></label>
                    <select id="add_supplier_id" class="form-control select2" style="width:100%;">
                        <option value="">Select Supplier</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="add_amount">Amount <span class="text-danger">*</span></label>
                    <input type="number" id="add_amount" class="form-control" placeholder="0.00" step="0.01" min="0.01">
                </div>
                <div class="form-group">
                    <label for="add_cheque_number">Cheque Number <span class="text-danger">*</span></label>
                    <input type="text" id="add_cheque_number" class="form-control" placeholder="Cheque Number">
                </div>
                <div class="form-group">
                    <label for="add_cheque_name">Cheque Name</label>
                    <input type="text" id="add_cheque_name" class="form-control" placeholder="Name on cheque">
                </div>
                <div class="form-group">
                    <label for="add_cheque_bank_name">Bank</label>
                    @if(\App\Utils\SriLankanBankUtil::enabled())
                        <select id="add_cheque_bank_name" class="form-control select2">
                            @foreach(\App\Utils\SriLankanBankUtil::options() as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @else
                        <input type="text" id="add_cheque_bank_name" class="form-control" placeholder="Bank Name">
                    @endif
                </div>
                <div class="form-group">
                    <label for="add_cheque_date">Cheque Due Date <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-addon" id="add_cal_icon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="add_cheque_date" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label for="add_paid_on">Paid On <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-addon" id="add_paid_cal_icon" style="cursor:pointer;"><i class="fa fa-calendar"></i></span>
                        <input type="text" id="add_paid_on" class="form-control" placeholder="DD/MM/YYYY" autocomplete="off">
                    </div>
                </div>
                <div class="form-group">
                    <label for="add_note">Note (Optional)</label>
                    <textarea id="add_note" class="form-control" rows="2" placeholder="Enter any notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="btn_save_supplier_cheque"><i class="fas fa-save"></i> Record Payment</button>
            </div>
        </div>
    </div>
</div>

{{-- Cheque Reports Modal --}}
<div class="modal fade" id="chequeReportsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><i class="fas fa-chart-pie"></i> Cheque Reports & Analytics</h4>
            </div>
            <div class="modal-body">
                <div class="cheque-report-summary">
                    <div class="cheque-report-stat">
                        <div class="cheque-report-stat-icon yellow"><i class="fas fa-clock"></i></div>
                        <div>
                            <div class="cheque-report-stat-label">Pending Cheques</div>
                            <div class="cheque-report-stat-value" id="report_pending_count">0</div>
                        </div>
                    </div>
                    <div class="cheque-report-stat">
                        <div class="cheque-report-stat-icon green"><i class="fas fa-check-circle"></i></div>
                        <div>
                            <div class="cheque-report-stat-label">Cleared Cheques</div>
                            <div class="cheque-report-stat-value" id="report_cleared_count">0</div>
                        </div>
                    </div>
                    <div class="cheque-report-stat">
                        <div class="cheque-report-stat-icon red"><i class="fas fa-times-circle"></i></div>
                        <div>
                            <div class="cheque-report-stat-label">Returned Cheques</div>
                            <div class="cheque-report-stat-value" id="report_returned_count">0</div>
                        </div>
                    </div>
                    <div class="cheque-report-stat">
                        <div class="cheque-report-stat-icon blue"><i class="fas fa-money-bill-wave"></i></div>
                        <div>
                            <div class="cheque-report-stat-label">Total Amount</div>
                            <div class="cheque-report-stat-value" id="report_total_amount">Rs 0.00</div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6" style="margin-bottom: 12px;">
                        <div class="cheque-report-panel">
                            <div class="cheque-report-panel-header supplier">
                                <i class="fas fa-arrow-up text-info"></i> Supplier Cheques
                            </div>
                            <div class="cheque-report-panel-body">
                                <table class="table cheque-report-table">
                                    <tr>
                                        <td>Pending</td>
                                        <td class="text-right"><span id="report_supplier_pending" class="badge bg-yellow">0</span></td>
                                        <td class="text-right" id="report_supplier_pending_amount">Rs 0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Cleared</td>
                                        <td class="text-right"><span id="report_supplier_cleared" class="badge bg-green">0</span></td>
                                        <td class="text-right" id="report_supplier_cleared_amount">Rs 0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Returned</td>
                                        <td class="text-right"><span id="report_supplier_returned" class="badge bg-red">0</span></td>
                                        <td class="text-right" id="report_supplier_returned_amount">Rs 0.00</td>
                                    </tr>
                                    <tr class="cheque-report-total-row">
                                        <td>Total</td>
                                        <td class="text-right" id="report_supplier_total_count">0</td>
                                        <td class="text-right" id="report_supplier_total_amount">Rs 0.00</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6" style="margin-bottom: 12px;">
                        <div class="cheque-report-panel">
                            <div class="cheque-report-panel-header customer">
                                <i class="fas fa-arrow-down text-success"></i> Customer Cheques
                            </div>
                            <div class="cheque-report-panel-body">
                                <table class="table cheque-report-table">
                                    <tr>
                                        <td>Pending</td>
                                        <td class="text-right"><span id="report_customer_pending" class="badge bg-yellow">0</span></td>
                                        <td class="text-right" id="report_customer_pending_amount">Rs 0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Cleared</td>
                                        <td class="text-right"><span id="report_customer_cleared" class="badge bg-green">0</span></td>
                                        <td class="text-right" id="report_customer_cleared_amount">Rs 0.00</td>
                                    </tr>
                                    <tr>
                                        <td>Returned</td>
                                        <td class="text-right"><span id="report_customer_returned" class="badge bg-red">0</span></td>
                                        <td class="text-right" id="report_customer_returned_amount">Rs 0.00</td>
                                    </tr>
                                    <tr class="cheque-report-total-row">
                                        <td>Total</td>
                                        <td class="text-right" id="report_customer_total_count">0</td>
                                        <td class="text-right" id="report_customer_total_amount">Rs 0.00</td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-warning" id="report_overdue_alert" style="display: none; border-radius: 10px; margin-top: 15px; margin-bottom: 0;">
                    <h4><i class="fas fa-exclamation-triangle"></i> Attention Required!</h4>
                    <p>You have <strong id="report_overdue_count">0</strong> overdue cheque(s) totaling <strong id="report_overdue_amount">Rs 0.00</strong> that need immediate action.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printChequeReport()"><i class="fas fa-print"></i> Print Report</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function() {

    var activeStatus = '';
    var activeChequeType = ''; // '' = all, 'outgoing' = supplier, 'incoming' = customer

    // Filter function
    window.filterTable = function(status) {
        activeStatus = status;
        $('#status_filter_tabs .filter-tab').removeClass('active');
        $('#status_filter_tabs .filter-tab[data-value="' + status + '"]').addClass('active');
        table.draw();
    };

    // Cheque type filter function
    window.filterByType = function(type) {
        activeChequeType = type;
        $('#cheque_type_tabs .filter-tab').removeClass('active');
        $('#cheque_type_tabs .filter-tab[data-type="' + type + '"]').addClass('active');
        table.draw();
    };

    // Load summary stats
    function loadStats() {
        $.get('{{ route("cheque.stats") }}', function(data) {
            var pending = (data.overdue || 0) + (data.due_soon || 0);
            $('#pending_count').text(pending);
            $('#overdue_count').text(data.overdue || 0);
            $('#pending_total').text(parseFloat(data.pending_total || 0).toFixed(2));
        });
    }
    loadStats();

    // DataTable
    var table = $('#cheque_table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ route("cheque.index") }}',
            data: function(d) {
                d.status = activeStatus || '';
                d.cheque_type = activeChequeType || '';
                return d;
            }
        },
        columns: [
            { data: 'cheque_number', name: 'transaction_payments.cheque_number' },
            { data: 'cheque_name', name: 'transaction_payments.cheque_name', render: function(d) { return d || '-'; } },
            { data: 'cheque_bank_name', name: 'transaction_payments.cheque_bank_name', render: function(d) { return d || '-'; } },
            { data: 'cheque_type', name: 'transaction_payments.cheque_type', orderable: false, searchable: false },
            { data: 'contact_name', name: 'c.name' },
            { data: 'transaction_ref', name: 't.ref_no' },
            { data: 'amount', name: 'transaction_payments.amount' },
            { data: 'paid_on', name: 'transaction_payments.paid_on' },
            { data: 'cheque_date', name: 'transaction_payments.cheque_date' },
            { data: 'due_alert', name: 'due_alert', orderable: false, searchable: false },
            { data: 'status_badge', name: 'status_badge', orderable: false, searchable: false },
            { data: 'action', name: 'action', orderable: false, searchable: false },
        ],
        order: [[6, 'asc']],
    });

    // Force reload on page load to ensure data appears
    setTimeout(function() {
        table.draw();
    }, 100);

    // Status filter tab click
    $(document).on('click', '#status_filter_tabs .filter-tab', function() {
        filterTable($(this).data('value'));
    });

    // Cheque type tab click
    $(document).on('click', '#cheque_type_tabs .filter-tab', function() {
        filterByType($(this).data('type'));
    });

    // Load Reports Data
    function loadReportsData() {
        $.get('{{ route("cheque.stats") }}', function(data) {
            // Overall counts
            $('#report_pending_count').text(data.pending_count || 0);
            $('#report_cleared_count').text(data.cleared_count || 0);
            $('#report_returned_count').text(data.returned_count || 0);
            $('#report_total_amount').text('Rs ' + parseFloat(data.total_amount || 0).toFixed(2));

            // Supplier breakdown
            $('#report_supplier_pending').text(data.supplier_pending || 0);
            $('#report_supplier_pending_amount').text('Rs ' + parseFloat(data.supplier_pending_amount || 0).toFixed(2));
            $('#report_supplier_cleared').text(data.supplier_cleared || 0);
            $('#report_supplier_cleared_amount').text('Rs ' + parseFloat(data.supplier_cleared_amount || 0).toFixed(2));
            $('#report_supplier_returned').text(data.supplier_returned || 0);
            $('#report_supplier_returned_amount').text('Rs ' + parseFloat(data.supplier_returned_amount || 0).toFixed(2));
            $('#report_supplier_total_count').text((data.supplier_pending || 0) + (data.supplier_cleared || 0) + (data.supplier_returned || 0));
            $('#report_supplier_total_amount').text('Rs ' + parseFloat(data.supplier_total || 0).toFixed(2));

            // Customer breakdown
            $('#report_customer_pending').text(data.customer_pending || 0);
            $('#report_customer_pending_amount').text('Rs ' + parseFloat(data.customer_pending_amount || 0).toFixed(2));
            $('#report_customer_cleared').text(data.customer_cleared || 0);
            $('#report_customer_cleared_amount').text('Rs ' + parseFloat(data.customer_cleared_amount || 0).toFixed(2));
            $('#report_customer_returned').text(data.customer_returned || 0);
            $('#report_customer_returned_amount').text('Rs ' + parseFloat(data.customer_returned_amount || 0).toFixed(2));
            $('#report_customer_total_count').text((data.customer_pending || 0) + (data.customer_cleared || 0) + (data.customer_returned || 0));
            $('#report_customer_total_amount').text('Rs ' + parseFloat(data.customer_total || 0).toFixed(2));

            // Overdue alert
            if (data.overdue > 0) {
                $('#report_overdue_alert').show();
                $('#report_overdue_count').text(data.overdue);
                $('#report_overdue_amount').text('Rs ' + parseFloat(data.overdue_amount || 0).toFixed(2));
            } else {
                $('#report_overdue_alert').hide();
            }
        });
    }

    // Load reports when modal opens
    $('#chequeReportsModal').on('shown.bs.modal', function() {
        loadReportsData();
    });

    // Print report function
    window.printChequeReport = function() {
        var printWindow = window.open('', '_blank');
        var reportContent = `
            <html>
            <head>
                <title>Cheque Report</title>
                <style>
                    body { font-family: Arial, sans-serif; padding: 20px; }
                    h1 { color: #141414; }
                    table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
                    th { background: #f5f5f5; }
                    .header { background: linear-gradient(135deg, #141414 0%, #2a2a2a 100%); color: #fff; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
                    .section { margin-bottom: 30px; }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1><i class="fas fa-chart-pie"></i> Cheque Report</h1>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>
                <div class="section">
                    <h2>Supplier Cheques</h2>
                    <table>
                        <tr><th>Status</th><th>Count</th><th>Amount</th></tr>
                        <tr><td>Pending</td><td>${$('#report_supplier_pending').text()}</td><td>${$('#report_supplier_pending_amount').text()}</td></tr>
                        <tr><td>Cleared</td><td>${$('#report_supplier_cleared').text()}</td><td>${$('#report_supplier_cleared_amount').text()}</td></tr>
                        <tr><td>Returned</td><td>${$('#report_supplier_returned').text()}</td><td>${$('#report_supplier_returned_amount').text()}</td></tr>
                    </table>
                </div>
                <div class="section">
                    <h2>Customer Cheques</h2>
                    <table>
                        <tr><th>Status</th><th>Count</th><th>Amount</th></tr>
                        <tr><td>Pending</td><td>${$('#report_customer_pending').text()}</td><td>${$('#report_customer_pending_amount').text()}</td></tr>
                        <tr><td>Cleared</td><td>${$('#report_customer_cleared').text()}</td><td>${$('#report_customer_cleared_amount').text()}</td></tr>
                        <tr><td>Returned</td><td>${$('#report_customer_returned').text()}</td><td>${$('#report_customer_returned_amount').text()}</td></tr>
                    </table>
                </div>
            </body>
            </html>
        `;
        printWindow.document.write(reportContent);
        printWindow.document.close();
        printWindow.print();
    };

    // Init edit modal datepicker (use business date format)
    var chequeEditDpFormat = '{{ str_replace(["Y","m","d"], ["yy","mm","dd"], session("business.date_format", "m/d/Y")) }}';
    $('#editChequeModal').on('shown.bs.modal', function() {
        if (!$('#edit_cheque_date').hasClass('cheque-edit-dp-ready')) {
            $('#edit_cheque_date').addClass('cheque-edit-dp-ready').datepicker({
                dateFormat: chequeEditDpFormat,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-1:c+5',
                showButtonPanel: false,
                onSelect: function(dateText) {
                    $(this).val(dateText).trigger('change');
                }
            });
        }
        $('#edit_cheque_date').focus();
    });
    $('#edit_cal_icon').on('click', function() {
        $('#edit_cheque_date').datepicker('show');
    });

    // Open edit modal
    $(document).on('click', '.btn-cheque-edit', function() {
        var btn = $(this);
        $('#edit_cheque_id').val(btn.data('id'));
        $('#edit_cheque_number').val(btn.data('cheque_number'));
        $('#edit_cheque_name').val(btn.data('cheque_name') || '');
        $('#edit_cheque_bank_name').val(btn.data('cheque_bank_name')).trigger('change');
        $('#edit_cheque_date').val(btn.data('cheque_date'));
        $('#edit_cheque_status').val(btn.data('status'));
        $('#editChequeModal').modal('show');
    });

    // Save edit
    $('#btn_save_cheque_edit').on('click', function() {
        var id = $('#edit_cheque_id').val();
        if (!$('#edit_cheque_number').val().trim()) {
            toastr.error('Cheque number is required.');
            return;
        }
        $.ajax({
            url: '/cheque/' + id + '/update',
            method: 'POST',
            data: {
                cheque_number:    $('#edit_cheque_number').val(),
                cheque_name:      $('#edit_cheque_name').val(),
                cheque_bank_name: $('#edit_cheque_bank_name').val(),
                cheque_date:      $('#edit_cheque_date').val(),
                cheque_status:    $('#edit_cheque_status').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    $('#editChequeModal').modal('hide');
                    table.draw();
                    loadStats();
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function() {
                toastr.error('Something went wrong.');
            }
        });
    });

    // Mark cleared / returned
    $(document).on('click', '.btn-cheque-action', function() {
        var id = $(this).data('id');
        var status = $(this).data('status');

        var isReturned = status === 'returned';
        swal({
            title: isReturned ? 'Mark as Returned?' : 'Mark as Cleared?',
            text: isReturned
                ? 'This will mark the cheque as returned and revert the payment status on the transaction.'
                : 'This will mark the cheque as cleared/deposited.',
            icon: isReturned ? 'warning' : 'info',
            buttons: {
                cancel: { text: 'Cancel', value: null, visible: true, className: 'btn-default' },
                confirm: {
                    text: isReturned ? 'Yes, Returned' : 'Yes, Cleared',
                    value: true,
                    className: isReturned ? 'btn-danger' : 'btn-success'
                }
            },
            dangerMode: isReturned,
        }).then(function(confirmed) {
            if (!confirmed) return;
            $.ajax({
                url: '/cheque/' + id + '/status',
                method: 'POST',
                data: { status: status, _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.msg);
                        table.draw();
                        loadStats();
                    } else {
                        toastr.error(res.msg);
                    }
                },
                error: function() {
                    toastr.error('Something went wrong.');
                }
            });
        });
    });

    // Supplier Cheque Modal - Load suppliers
    $('#addSupplierChequeModal').on('shown.bs.modal', function() {
        // Load suppliers via AJAX
        if ($('#add_supplier_id option').length <= 1) {
            $.ajax({
                url: '/purchases/get_suppliers?q=*',
                dataType: 'json',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                success: function(result) {
                    var data = (typeof result === 'string') ? JSON.parse(result) : result;
                    var suppliers = Array.isArray(data) ? data : (data.data || []);
                    var options = '<option value="">Select Supplier</option>';
                    suppliers.forEach(function(s) {
                        options += '<option value="' + s.id + '">' + (s.text || s.name) + '</option>';
                    });
                    $('#add_supplier_id').html(options).select2({
                        dropdownParent: $('#addSupplierChequeModal')
                    });
                },
                error: function() {
                    toastr.error('Failed to load suppliers');
                }
            });
        } else {
            $('#add_supplier_id').select2({
                dropdownParent: $('#addSupplierChequeModal')
            });
        }

        // Initialize datepickers
        var dateFormat = '{{ str_replace(["Y","m","d"], ["yy","mm","dd"], session("business.date_format", "m/d/Y")) }}';
        if (!$('#add_cheque_date').hasClass('dp-ready')) {
            $('#add_cheque_date').addClass('dp-ready').datepicker({
                dateFormat: dateFormat,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-1:c+5'
            });
        }
        if (!$('#add_paid_on').hasClass('dp-ready')) {
            $('#add_paid_on').addClass('dp-ready').datepicker({
                dateFormat: dateFormat,
                changeMonth: true,
                changeYear: true,
                yearRange: 'c-1:c+5'
            });
        }
    });

    // Calendar icons click
    $('#add_cal_icon').on('click', function() {
        $('#add_cheque_date').datepicker('show');
    });
    $('#add_paid_cal_icon').on('click', function() {
        $('#add_paid_on').datepicker('show');
    });

    // Save supplier cheque payment
    $('#btn_save_supplier_cheque').on('click', function() {
        var supplier_id = $('#add_supplier_id').val();
        var amount = $('#add_amount').val();
        var cheque_number = $('#add_cheque_number').val();
        var cheque_name = $('#add_cheque_name').val();
        var cheque_bank_name = $('#add_cheque_bank_name').val();
        var cheque_date = $('#add_cheque_date').val();
        var paid_on = $('#add_paid_on').val();

        if (!supplier_id) { toastr.error('Please select a supplier'); return; }
        if (!amount || parseFloat(amount) <= 0) { toastr.error('Please enter a valid amount'); return; }
        if (!cheque_number.trim()) { toastr.error('Please enter cheque number'); return; }
        if (!cheque_date) { toastr.error('Please select cheque due date'); return; }
        if (!paid_on) { toastr.error('Please select paid on date'); return; }

        var dateFormat = '{{ session("business.date_format", "m/d/Y") }}';

        $.ajax({
            url: '{{ route("cheque.store") }}',
            method: 'POST',
            data: {
                supplier_id: supplier_id,
                amount: amount,
                cheque_number: cheque_number,
                cheque_name: cheque_name,
                cheque_bank_name: cheque_bank_name,
                cheque_date: cheque_date,
                paid_on: paid_on,
                note: $('#add_note').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.msg);
                    $('#addSupplierChequeModal').modal('hide');
                    // Reset form
                    $('#add_supplier_id').val('').trigger('change');
                    $('#add_amount').val('');
                    $('#add_cheque_number').val('');
                    $('#add_cheque_name').val('');
                    $('#add_cheque_bank_name').val('').trigger('change');
                    $('#add_cheque_date').val('');
                    $('#add_paid_on').val('');
                    $('#add_note').val('');
                    table.draw();
                    loadStats();
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            }
        });
    });

    // View cheque
    $(document).on('click', '.btn-cheque-view', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({
            url: '/cheque/' + id,
            method: 'GET',
            success: function(res) {
                if (res.success) {
                    var c = res.cheque;
                    $('#view_bank_name').text(c.cheque_bank_name || 'Bank Name');
                    $('#view_cheque_number').text(c.cheque_number || '000000');
                    $('#view_cheque_date').text(c.cheque_date || '-');
                    $('#view_payee').text(c.cheque_name || c.contact_name || 'Payee Name');
                    $('#view_amount_words').text(c.amount_words || '-');
                    $('#view_amount_figures').text(c.amount || '0.00');
                    $('#view_transaction_ref').text(c.transaction_ref || '-');
                    $('#view_paid_on').text(c.paid_on || '-');
                    $('#view_cheque_type').text(c.cheque_type === 'outgoing' ? 'Pay Supplier' : 'Receive from Customer');

                    var statusBadge = $('#view_cheque_status_badge');
                    if (c.cheque_status === 'cleared') {
                        statusBadge.css({background:'#dcfce7',color:'#166534'}).text('CLEARED');
                    } else if (c.cheque_status === 'returned') {
                        statusBadge.css({background:'#fee2e2',color:'#991b1b'}).text('RETURNED');
                    } else {
                        statusBadge.css({background:'#fef3c7',color:'#b45309'}).text('PENDING');
                    }

                    $('#viewChequeModal').modal('show');
                } else {
                    toastr.error(res.msg);
                }
            },
            error: function(xhr) {
                var msg = 'Something went wrong.';
                if (xhr.responseJSON && xhr.responseJSON.msg) {
                    msg = xhr.responseJSON.msg;
                }
                toastr.error(msg);
            }
        });
    });

    // Delete cheque payment
    $(document).on('click', '.btn-cheque-delete', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        swal({
            title: 'Delete Cheque Payment?',
            text: 'This will permanently delete this pending cheque payment and reverse any balance changes. This action cannot be undone.',
            icon: 'warning',
            buttons: {
                cancel: { text: 'Cancel', value: null, visible: true, className: 'btn-default' },
                confirm: { text: 'Delete', value: true, className: 'btn-danger' }
            },
            dangerMode: true,
        }).then(function(confirmed) {
            if (!confirmed) return;
            $.ajax({
                url: '/cheque/' + id,
                method: 'DELETE',
                data: { _token: '{{ csrf_token() }}' },
                success: function(res) {
                    if (res.success) {
                        toastr.success(res.msg);
                        table.draw();
                        loadStats();
                    } else {
                        toastr.error(res.msg);
                    }
                },
                error: function(xhr) {
                    var msg = 'Something went wrong.';
                    if (xhr.responseJSON && xhr.responseJSON.msg) {
                        msg = xhr.responseJSON.msg;
                    }
                    toastr.error(msg);
                }
            });
        });
    });

    // Remind customer before cheque due date (WhatsApp / copy)
    $(document).on('click', '.btn-cheque-remind', function(e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({
            url: '{{ url("/cheque") }}/' + id + '/remind',
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (!res.success) {
                    toastr.error(res.msg || 'Could not build reminder');
                    return;
                }
                var daysText = '';
                if (res.days_until_due !== null && res.days_until_due !== undefined) {
                    if (res.days_until_due < 0) daysText = ' (overdue)';
                    else if (res.days_until_due === 0) daysText = ' (due today)';
                    else if (res.days_until_due === 1) daysText = ' (due tomorrow)';
                    else daysText = ' (in ' + res.days_until_due + ' days)';
                }
                swal({
                    title: 'Remind Customer' + daysText,
                    text: res.message,
                    icon: 'info',
                    buttons: {
                        cancel: { text: 'Close', value: null, visible: true, className: 'btn-default' },
                        copy: { text: 'Copy Message', value: 'copy', className: 'btn-default' },
                        sms: {
                            text: 'Send SMS',
                            value: res.mobile ? 'sms' : null,
                            visible: !!res.mobile,
                            className: 'btn-warning'
                        },
                        whatsapp: {
                            text: res.whatsapp_link ? 'Send WhatsApp' : 'No Mobile on Contact',
                            value: res.whatsapp_link ? 'whatsapp' : null,
                            visible: true,
                            className: res.whatsapp_link ? 'btn-success' : 'btn-default'
                        }
                    }
                }).then(function(action) {
                    if (action === 'whatsapp' && res.whatsapp_link) {
                        window.open(res.whatsapp_link, '_blank');
                        toastr.success('WhatsApp opened');
                    } else if (action === 'sms') {
                        $.ajax({
                            url: '{{ url("/cheque") }}/' + id + '/remind',
                            method: 'POST',
                            data: { _token: '{{ csrf_token() }}', send_sms: 1 },
                            success: function(r2) {
                                if (r2.success && r2.sms_sent) {
                                    toastr.success('SMS reminder sent');
                                } else if (r2.success) {
                                    toastr.warning(r2.msg || 'SMS not sent — check SMSlenz settings');
                                } else {
                                    toastr.error(r2.msg || 'SMS failed');
                                }
                            },
                            error: function(xhr) {
                                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'SMS failed';
                                toastr.error(msg);
                            }
                        });
                    } else if (action === 'copy' && res.message) {
                        if (navigator.clipboard && navigator.clipboard.writeText) {
                            navigator.clipboard.writeText(res.message).then(function() {
                                toastr.success('Reminder message copied');
                            });
                        } else {
                            prompt('Copy message:', res.message);
                        }
                    }
                });
            },
            error: function(xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.msg) ? xhr.responseJSON.msg : 'Failed to build reminder';
                toastr.error(msg);
            }
        });
    });

});
</script>
@endsection
