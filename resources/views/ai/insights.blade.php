@extends('layouts.app')
@section('title', 'AI Insights')

@section('content')
<section class="content-header">
    <h1>
        <i class="fas fa-brain" style="color:#f5c518;"></i>
        AI Business Insights
        <small style="font-size:13px;color:#f5c518;font-weight:600;">Local · live POS data</small>
    </h1>
</section>

<section class="content">
<div class="container-fluid" style="padding:0 16px;">

{{-- KPI Cards --}}
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#6366f1,#8b5cf6);">
        <div class="vsai-kpi-icon"><i class="fas fa-chart-line"></i></div>
        <div class="vsai-kpi-label">This Month Sales</div>
        <div class="vsai-kpi-val" id="kpi-month-total">...</div>
    </div>
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#10b981,#059669);">
        <div class="vsai-kpi-icon"><i class="fas fa-receipt"></i></div>
        <div class="vsai-kpi-label">Transactions</div>
        <div class="vsai-kpi-val" id="kpi-month-count">...</div>
    </div>
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#f59e0b,#d97706);">
        <div class="vsai-kpi-icon"><i class="fas fa-trophy"></i></div>
        <div class="vsai-kpi-label">Top Product</div>
        <div class="vsai-kpi-val" id="kpi-top-product" style="font-size:13px;">...</div>
    </div>
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#ef4444,#dc2626);">
        <div class="vsai-kpi-icon"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="vsai-kpi-label">Low Stock Items</div>
        <div class="vsai-kpi-val" id="kpi-low-stock">...</div>
    </div>
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#22c55e,#15803d);">
        <div class="vsai-kpi-icon"><i class="fas fa-money-check-alt"></i></div>
        <div class="vsai-kpi-label">Receivable Cheques</div>
        <div class="vsai-kpi-val">{{ session('currency.symbol', 'Rs') }} {{ number_format($stats['recv_cheque_total'] ?? 0, 2) }}</div>
        <div style="font-size:11px;opacity:.85;">{{ $stats['recv_cheque_count'] ?? 0 }} pending
            @if(($stats['recv_cheque_overdue'] ?? 0) > 0) · {{ $stats['recv_cheque_overdue'] }} overdue @endif
        </div>
    </div>
    <div class="vsai-kpi-card" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8);">
        <div class="vsai-kpi-icon"><i class="fas fa-file-invoice-dollar"></i></div>
        <div class="vsai-kpi-label">Payable Cheques</div>
        <div class="vsai-kpi-val">{{ session('currency.symbol', 'Rs') }} {{ number_format($stats['pay_cheque_total'] ?? 0, 2) }}</div>
        <div style="font-size:11px;opacity:.85;">{{ $stats['pay_cheque_count'] ?? 0 }} pending
            @if(($stats['pay_cheque_overdue'] ?? 0) > 0) · {{ $stats['pay_cheque_overdue'] }} overdue @endif
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    {{-- Sales trend chart --}}
    <div class="vsai-card">
        <div class="vsai-card-header"><i class="fas fa-chart-bar"></i> Daily Sales — Last 30 Days</div>
        <div class="vsai-card-body" style="height:240px;"><canvas id="vsai-sales-chart"></canvas></div>
    </div>
    {{-- Payment methods --}}
    <div class="vsai-card">
        <div class="vsai-card-header"><i class="fas fa-credit-card"></i> Payment Methods (This Month)</div>
        <div class="vsai-card-body" style="height:240px;display:flex;align-items:center;justify-content:center;">
            <canvas id="vsai-payment-chart" style="max-height:220px;"></canvas>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
    {{-- Top products table --}}
    <div class="vsai-card">
        <div class="vsai-card-header"><i class="fas fa-boxes"></i> Top 10 Products (Last 30 Days)</div>
        <div class="vsai-card-body" style="padding:0;">
            <table class="table table-condensed" style="margin:0;font-size:12px;">
                <thead><tr style="background:#f8fafc;">
                    <th style="padding:8px 12px;">#</th>
                    <th style="padding:8px 12px;">Product</th>
                    <th style="padding:8px 12px;text-align:right;">Qty</th>
                    <th style="padding:8px 12px;text-align:right;">Revenue</th>
                </tr></thead>
                <tbody>
                @foreach($topProducts as $i => $p)
                <tr>
                    <td style="padding:7px 12px;color:#9ca3af;font-weight:700;">{{ $i+1 }}</td>
                    <td style="padding:7px 12px;font-weight:600;">{{ $p->name }}</td>
                    <td style="padding:7px 12px;text-align:right;">{{ number_format($p->qty, 0) }}</td>
                    <td style="padding:7px 12px;text-align:right;font-weight:700;color:#059669;">
                        @format_currency($p->revenue)
                    </td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- AI Report Generator --}}
    <div class="vsai-card">
        <div class="vsai-card-header"><i class="fas fa-robot"></i> AI Report Generator</div>
        <div class="vsai-card-body" style="display:flex;flex-direction:column;gap:10px;">
            <p style="font-size:12px;color:#6b7280;margin:0;">Ask AI to generate a detailed report based on your live business data.</p>
            <div style="display:flex;flex-wrap:wrap;gap:6px;">
                <button class="vsai-gen-btn" data-prompt="Analyze my sales performance for the last 30 days and give me a detailed report with insights and recommendations.">
                    📊 Sales Report
                </button>
                <button class="vsai-gen-btn" data-prompt="Review my top products and payment methods and give me insights on what to focus on to grow revenue.">
                    💡 Growth Insights
                </button>
                <button class="vsai-gen-btn" data-prompt="What are 5 specific actions I should take this week to improve my business performance?">
                    🎯 Action Plan
                </button>
                <button class="vsai-gen-btn" data-prompt="Summarize my pending receivable and payable cheques, highlight overdue ones, and suggest what I should do this week.">
                    🏦 Cheque Follow-up
                </button>
                <button class="vsai-gen-btn" data-prompt="Identify any potential problems or risks in my current business data and suggest how to address them.">
                    ⚠️ Risk Analysis
                </button>
            </div>
            <div id="vsai-report-output" style="display:none;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;padding:12px;font-size:12px;line-height:1.7;max-height:220px;overflow-y:auto;color:#374151;"></div>
            <div id="vsai-report-loading" style="display:none;text-align:center;padding:20px;color:#6366f1;">
                <i class="fas fa-spinner fa-spin"></i> Generating AI report…
            </div>
        </div>
    </div>
</div>

</div>
</section>

<style>
.vsai-kpi-card {
    border-radius: 16px; padding: 18px 20px; color: #fff;
    display: flex; flex-direction: column; gap: 6px;
}
.vsai-kpi-icon { font-size: 22px; opacity: .85; }
.vsai-kpi-label { font-size: 12px; opacity: .85; font-weight: 600; }
.vsai-kpi-val { font-size: 22px; font-weight: 800; letter-spacing: -.3px; }
.vsai-card {
    background: #fff; border-radius: 16px; overflow: hidden;
    box-shadow: 0 2px 16px rgba(17,24,39,0.07); border: 1px solid #e5e7eb;
}
.vsai-card-header {
    padding: 12px 16px; font-size: 13px; font-weight: 700; color: #374151;
    border-bottom: 1px solid #f3f4f6; background: #fafafa;
    display: flex; align-items: center; gap: 7px;
}
.vsai-card-header i { color: #6366f1; }
.vsai-card-body { padding: 14px 16px; }
.vsai-gen-btn {
    background: #ede9fe; color: #5b21b6; border: none; border-radius: 8px;
    padding: 7px 12px; font-size: 12px; font-weight: 600; cursor: pointer;
    transition: background .15s;
}
.vsai-gen-btn:hover { background: #ddd6fe; }
@media (max-width: 767px) {
    .vsai-card { margin-bottom: 12px; }
    div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>

@section('javascript')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ── KPI updates ──
var dailySales   = @json($dailySales);
var topProducts  = @json($topProducts);
var payments     = @json($payments);

// Month totals from daily data
var monthTotal = dailySales.reduce(function(s,d){ return s + parseFloat(d.total||0); }, 0);
var monthCount = dailySales.reduce(function(s,d){ return s + parseInt(d.count||0); }, 0);
document.getElementById('kpi-month-total').textContent = monthTotal.toLocaleString('en', {minimumFractionDigits:2, maximumFractionDigits:2});
document.getElementById('kpi-month-count').textContent = monthCount;
document.getElementById('kpi-top-product').textContent = topProducts.length ? topProducts[0].name : 'N/A';
document.getElementById('kpi-low-stock').textContent = '—';

// ── Sales Chart ──
var salesLabels = dailySales.map(function(d){ return d.date ? d.date.slice(5) : ''; });
var salesData   = dailySales.map(function(d){ return parseFloat(d.total||0); });
new Chart(document.getElementById('vsai-sales-chart'), {
    type: 'bar',
    data: {
        labels: salesLabels,
        datasets: [{
            label: 'Sales',
            data: salesData,
            backgroundColor: 'rgba(99,102,241,0.7)',
            borderRadius: 5,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false }, ticks: { font: { size: 10 } } },
            y: { grid: { color: '#f3f4f6' }, ticks: { font: { size: 10 } } }
        }
    }
});

// ── Payment Donut ──
var payLabels = payments.map(function(p){ return p.method; });
var payData   = payments.map(function(p){ return parseFloat(p.total||0); });
var payColors = ['#6366f1','#10b981','#f59e0b','#ef4444','#3b82f6','#8b5cf6','#ec4899'];
new Chart(document.getElementById('vsai-payment-chart'), {
    type: 'doughnut',
    data: {
        labels: payLabels,
        datasets: [{
            data: payData,
            backgroundColor: payColors,
            borderWidth: 2,
            borderColor: '#fff',
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position: 'bottom', labels: { font: { size: 11 }, padding: 10 } }
        }
    }
});

// ── AI Report Generator ──
document.querySelectorAll('.vsai-gen-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var prompt = this.getAttribute('data-prompt');
        var output  = document.getElementById('vsai-report-output');
        var loading = document.getElementById('vsai-report-loading');
        output.style.display = 'none';
        loading.style.display = 'block';

        fetch('{{ route("ai.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({ message: prompt, history: [] })
        })
        .then(function(r){ return r.json(); })
        .then(function(data) {
            loading.style.display = 'none';
            output.style.display = 'block';
            output.innerHTML = formatMd(data.reply || 'No response.');
        })
        .catch(function() {
            loading.style.display = 'none';
            output.style.display = 'block';
            output.textContent = '❌ Failed to generate report.';
        });
    });
});

function formatMd(text) {
    text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
    text = text.replace(/^\s*[\*\-]\s+(.+)$/gm, '<li>$1</li>');
    text = text.replace(/(<li>.*<\/li>)/s, '<ul style="padding-left:16px;margin:4px 0;">$1</ul>');
    text = text.replace(/\n/g, '<br>');
    return text;
}
</script>
@endsection
@endsection
