@extends('layouts.app')
@section('title', 'Cheque Report')

@section('css')
<style>
    .cheque-report-page h1 { color: #f5f5f5 !important; }
    .cheque-report-page .breadcrumb { background: transparent !important; }
    .cheque-report-page .breadcrumb > li,
    .cheque-report-page .breadcrumb > li > a { color: #a3a3a3 !important; }
    .cheque-report-page .breadcrumb > .active { color: #d4d4d4 !important; }
    .cheque-report-stats {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 12px;
        margin-bottom: 16px;
    }
    @media (max-width: 1199px) { .cheque-report-stats { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 767px) { .cheque-report-stats { grid-template-columns: repeat(2, 1fr); } }
    .cheque-report-page .cr-stat {
        background: linear-gradient(160deg, rgba(17, 17, 17, .95), rgba(17, 17, 17, .78));
        border: 1px solid rgba(42, 42, 42, .22);
        border-radius: 12px;
        padding: 14px 16px;
        min-height: 88px;
    }
    .cheque-report-page .cr-stat-label {
        font-size: 0.72rem;
        font-weight: 700;
        color: #a3a3a3;
        text-transform: uppercase;
        letter-spacing: .05em;
        margin-bottom: 6px;
    }
    .cheque-report-page .cr-stat-value {
        font-size: 1.4rem;
        font-weight: 800;
        color: #f5f5f5;
        line-height: 1.1;
    }
    .cheque-report-page .cr-stat.aqua { border-top: 3px solid #f5c518; }
    .cheque-report-page .cr-stat.yellow { border-top: 3px solid #fbbf24; }
    .cheque-report-page .cr-stat.green { border-top: 3px solid #4ade80; }
    .cheque-report-page .cr-stat.red { border-top: 3px solid #f87171; }
    .cheque-report-page .cr-stat.info { border-top: 3px solid #f5c518; }
    .cheque-report-page .cr-stat.purple { border-top: 3px solid #a78bfa; }
    .cheque-report-page .amount-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 12px;
    }
    @media (max-width: 991px) { .cheque-report-page .amount-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 575px) { .cheque-report-page .amount-grid { grid-template-columns: 1fr; } }
    .cheque-report-page .amount-card {
        background: rgba(17, 17, 17, .9);
        border: 1px solid rgba(42, 42, 42, .2);
        border-radius: 12px;
        padding: 14px 16px;
    }
    .cheque-report-page .amount-card-label { color: #a3a3a3; font-size: 0.78rem; font-weight: 600; margin-bottom: 6px; }
    .cheque-report-page .amount-card-value { color: #f5f5f5; font-size: 1.15rem; font-weight: 800; }
    .cheque-report-page .table tfoot tr { background: rgba(26, 26, 26, .85) !important; }
    .cheque-report-page .table tfoot td { color: #f5f5f5 !important; font-weight: 700; }
</style>
@endsection

@section('content')
<div class="cheque-report-page">

<!-- Content Header -->
<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-3xl tw-font-bold tw-text-white">Cheque Report</h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('home') }}"><i class="fa fa-dashboard"></i> Home</a></li>
        <li><a href="{{ route('cheque.index') }}">Cheque Management</a></li>
        <li class="active">Cheque Report</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    
    <!-- Filter Box -->
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-filter"></i> Filters</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i></button>
            </div>
        </div>
        <div class="box-body">
            <form method="GET" action="{{ route('cheque.report') }}" id="cheque_report_filter">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="cheque_type">Cheque Type</label>
                            <select name="cheque_type" id="cheque_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="outgoing" {{ $cheque_type == 'outgoing' ? 'selected' : '' }}>Supplier (Outgoing)</option>
                                <option value="incoming" {{ $cheque_type == 'incoming' ? 'selected' : '' }}>Customer (Incoming)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="status">Status</label>
                            <select name="status" id="status" class="form-control">
                                <option value="">All Status</option>
                                <option value="pending" {{ $status == 'pending' ? 'selected' : '' }}>Pending</option>
                                <option value="cleared" {{ $status == 'cleared' ? 'selected' : '' }}>Cleared</option>
                                <option value="returned" {{ $status == 'returned' ? 'selected' : '' }}>Returned</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_from">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="{{ $date_from }}">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="date_to">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="{{ $date_to }}">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
                        <a href="{{ route('cheque.report') }}" class="btn btn-default"><i class="fas fa-undo"></i> Reset</a>
                        <button type="button" class="btn btn-success pull-right" onclick="printReport()"><i class="fas fa-print"></i> Print Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="cheque-report-stats">
        <div class="cr-stat aqua">
            <div class="cr-stat-label">Total Cheques</div>
            <div class="cr-stat-value">{{ $stats['total_count'] }}</div>
        </div>
        <div class="cr-stat yellow">
            <div class="cr-stat-label">Pending</div>
            <div class="cr-stat-value">{{ $stats['pending_count'] }}</div>
        </div>
        <div class="cr-stat green">
            <div class="cr-stat-label">Cleared</div>
            <div class="cr-stat-value">{{ $stats['cleared_count'] }}</div>
        </div>
        <div class="cr-stat red">
            <div class="cr-stat-label">Returned</div>
            <div class="cr-stat-value">{{ $stats['returned_count'] }}</div>
        </div>
        <div class="cr-stat info">
            <div class="cr-stat-label">Supplier</div>
            <div class="cr-stat-value">{{ $stats['supplier_count'] }}</div>
        </div>
        <div class="cr-stat purple">
            <div class="cr-stat-label">Customer</div>
            <div class="cr-stat-value">{{ $stats['customer_count'] }}</div>
        </div>
    </div>

    <!-- Amount Summary -->
    <div class="box box-info">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-chart-bar"></i> Amount Summary</h3>
        </div>
        <div class="box-body">
            <div class="amount-grid">
                <div class="amount-card">
                    <div class="amount-card-label">Total Amount</div>
                    <div class="amount-card-value">Rs {{ number_format($stats['total_amount'], 2) }}</div>
                </div>
                <div class="amount-card">
                    <div class="amount-card-label">Pending Amount</div>
                    <div class="amount-card-value">Rs {{ number_format($stats['pending_amount'], 2) }}</div>
                </div>
                <div class="amount-card">
                    <div class="amount-card-label">Cleared Amount</div>
                    <div class="amount-card-value">Rs {{ number_format($stats['cleared_amount'], 2) }}</div>
                </div>
                <div class="amount-card">
                    <div class="amount-card-label">Returned Amount</div>
                    <div class="amount-card-value">Rs {{ number_format($stats['returned_amount'], 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cheque List -->
    <div class="box box-primary" id="printable-area">
        <div class="box-header with-border">
            <h3 class="box-title"><i class="fas fa-list"></i> Cheque Details</h3>
            <div class="box-tools pull-right">
                <span class="label label-primary">{{ $cheques->count() }} Records</span>
            </div>
        </div>
        <div class="box-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Cheque #</th>
                            <th>Cheque Name</th>
                            <th>Type</th>
                            <th>Contact</th>
                            <th>Transaction</th>
                            <th class="text-right">Amount</th>
                            <th>Cheque Date</th>
                            <th>Paid On</th>
                            <th>Status</th>
                            <th>Payment Ref</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cheques as $index => $cheque)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td><strong>{{ $cheque->cheque_number }}</strong></td>
                            <td>{{ $cheque->cheque_name ?? '-' }}</td>
                            <td>
                                @if($cheque->cheque_type == 'outgoing')
                                    <span class="label label-info"><i class="fas fa-arrow-up"></i> Supplier</span>
                                @else
                                    <span class="label label-default"><i class="fas fa-arrow-down"></i> Customer</span>
                                @endif
                            </td>
                            <td>
                                @if($cheque->contact_name)
                                    {{ $cheque->contact_name }} <small class="text-muted">({{ $cheque->contact_code }})</small>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($cheque->transaction_ref)
                                    {{ $cheque->transaction_ref }}
                                @else
                                    <span class="text-muted">Advance Payment</span>
                                @endif
                            </td>
                            <td class="text-right">Rs {{ number_format($cheque->amount, 2) }}</td>
                            <td>{{ $cheque->cheque_date ? date('d-m-Y', strtotime($cheque->cheque_date)) : '-' }}</td>
                            <td>{{ $cheque->paid_on ? date('d-m-Y', strtotime($cheque->paid_on)) : '-' }}</td>
                            <td>
                                @if($cheque->cheque_status == 'pending')
                                    <span class="label label-warning">Pending</span>
                                @elseif($cheque->cheque_status == 'cleared')
                                    <span class="label label-success">Cleared</span>
                                @else
                                    <span class="label label-danger">Returned</span>
                                @endif
                            </td>
                            <td><small>{{ $cheque->payment_ref_no }}</small></td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">
                                <h4>No cheques found matching the selected filters.</h4>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="5" class="text-right">Total:</td>
                            <td class="text-right">Rs {{ number_format($cheques->sum('amount'), 2) }}</td>
                            <td colspan="4"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

</section>
</div>

@endsection

@section('javascript')
<script>
    function printReport() {
        var printContents = document.getElementById('printable-area').innerHTML;
        var originalContents = document.body.innerHTML;
        
        document.body.innerHTML = `
            <div style="padding: 20px;">
                <h2 style="text-align: center; margin-bottom: 20px;">Cheque Report</h2>
                <p style="text-align: center; color: #666; margin-bottom: 30px;">
                    Generated on: ${new Date().toLocaleString()}<br>
                    Filters: ${$('#cheque_type option:selected').text()}, ${$('#status option:selected').text()}
                </p>
                ${printContents}
            </div>
        `;
        
        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
</script>
@endsection
