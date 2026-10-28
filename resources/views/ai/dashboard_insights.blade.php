{{-- Local AI Insights — built from live POS data inside QPOS (no external API) --}}
@php
    $ai = $ai_insights;
    $sym = session('currency.symbol', 'Rs');
    $vsPct = $ai['vs_yesterday_pct'];
    $vsUp = $vsPct !== null && $vsPct >= 0;
@endphp
<div class="db-card full db-ai-card">
    <div class="db-card-header db-ai-header">
        <div class="db-card-title">
            <div class="db-card-title-icon db-ai-icon"><i class="fas fa-brain"></i></div>
            <div>
                <div class="db-ai-title-row">
                    AI Insights
                    <span class="db-ai-badge">Local · live data</span>
                </div>
                <div class="db-ai-headline">{{ $ai['headline'] }}</div>
            </div>
        </div>
        <div class="db-ai-actions">
            <span class="db-ai-date">{{ $ai['date_label'] }}</span>
            @if(Route::has('ai.insights'))
                <a href="{{ route('ai.insights') }}" class="db-ai-link">Full insights <i class="fas fa-arrow-right"></i></a>
            @endif
        </div>
    </div>

    <div class="db-card-body db-ai-body">
        <div class="db-ai-kpis">
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">Today sales</div>
                <div class="db-ai-kpi-val">{{ $sym }} {{ number_format($ai['today_total'], 2) }}</div>
                <div class="db-ai-kpi-sub">{{ $ai['today_count'] }} txn
                    @if($vsPct !== null)
                        <span class="db-ai-delta {{ $vsUp ? 'up' : 'down' }}">
                            {{ $vsUp ? '+' : '' }}{{ $vsPct }}% vs yesterday
                        </span>
                    @endif
                </div>
            </div>
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">This month</div>
                <div class="db-ai-kpi-val">{{ $sym }} {{ number_format($ai['month_total'], 2) }}</div>
                <div class="db-ai-kpi-sub">{{ $ai['month_count'] }} transactions</div>
            </div>
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">Receivable cheques</div>
                <div class="db-ai-kpi-val {{ ($ai['recv_cheque_overdue'] ?? 0) > 0 ? 'warn' : '' }}">{{ $sym }} {{ number_format($ai['recv_cheque_total'] ?? 0, 2) }}</div>
                <div class="db-ai-kpi-sub">
                    {{ $ai['recv_cheque_count'] ?? 0 }} pending
                    @if(($ai['recv_cheque_overdue'] ?? 0) > 0)
                        · {{ $ai['recv_cheque_overdue'] }} overdue
                    @elseif(($ai['recv_cheque_due_soon'] ?? 0) > 0)
                        · {{ $ai['recv_cheque_due_soon'] }} due ≤7d
                    @endif
                </div>
            </div>
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">Payable cheques</div>
                <div class="db-ai-kpi-val {{ ($ai['pay_cheque_overdue'] ?? 0) > 0 ? 'warn' : '' }}">{{ $sym }} {{ number_format($ai['pay_cheque_total'] ?? 0, 2) }}</div>
                <div class="db-ai-kpi-sub">
                    {{ $ai['pay_cheque_count'] ?? 0 }} pending
                    @if(($ai['pay_cheque_overdue'] ?? 0) > 0)
                        · {{ $ai['pay_cheque_overdue'] }} overdue
                    @elseif(($ai['pay_cheque_due_soon'] ?? 0) > 0)
                        · {{ $ai['pay_cheque_due_soon'] }} due ≤7d
                    @endif
                </div>
            </div>
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">Receivables due</div>
                <div class="db-ai-kpi-val {{ $ai['dues_total'] > 0 ? 'warn' : '' }}">{{ $sym }} {{ number_format($ai['dues_total'], 2) }}</div>
                <div class="db-ai-kpi-sub">{{ $ai['dues_count'] }} unpaid invoice(s)</div>
            </div>
            <div class="db-ai-kpi">
                <div class="db-ai-kpi-label">Low stock</div>
                <div class="db-ai-kpi-val {{ $ai['low_stock_count'] > 0 ? 'warn' : '' }}">{{ $ai['low_stock_count'] }}</div>
                <div class="db-ai-kpi-sub">items at / below alert</div>
            </div>
        </div>

        <div class="db-ai-grid">
            <div class="db-ai-panel">
                <div class="db-ai-panel-title"><i class="fas fa-lightbulb"></i> Action tips</div>
                <ul class="db-ai-tips">
                    @foreach($ai['tips'] as $tip)
                        <li class="tone-{{ $tip['tone'] }}">
                            <span class="db-ai-tip-icon"><i class="fas {{ $tip['icon'] }}"></i></span>
                            <span>{{ $tip['text'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <div class="db-ai-panel">
                <div class="db-ai-panel-title"><i class="fas fa-money-check"></i> Cheques due / overdue</div>
                @if(($ai['upcoming_cheques'] ?? collect())->isEmpty())
                    <p class="db-ai-empty">No receivable or payable cheques due in the next 7 days.</p>
                @else
                    <ul class="db-ai-cheques">
                        @foreach($ai['upcoming_cheques'] as $c)
                            @php
                                $isPay = ($c->cheque_type ?? '') === 'outgoing';
                                $cDate = !empty($c->cheque_date) ? \Carbon\Carbon::parse($c->cheque_date) : null;
                                $isOver = $cDate && $cDate->lt(\Carbon\Carbon::today());
                            @endphp
                            <li class="{{ $isOver ? 'overdue' : '' }}">
                                <span class="db-ai-chq-type {{ $isPay ? 'pay' : 'recv' }}">{{ $isPay ? 'Payable' : 'Receivable' }}</span>
                                <span class="db-ai-chq-main">
                                    <strong>#{{ $c->cheque_number ?: '—' }}</strong>
                                    <em>{{ $c->contact_name ?: '—' }}</em>
                                </span>
                                <span class="db-ai-chq-meta">
                                    {{ $cDate ? $cDate->format('d M') : '—' }}
                                    · {{ $sym }} {{ number_format((float) $c->amount, 2) }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    @if(Route::has('cheque.index'))
                        <a href="{{ route('cheque.index') }}" class="db-ai-chq-link">Open cheque management <i class="fas fa-arrow-right"></i></a>
                    @endif
                @endif

                <div class="db-ai-panel-title" style="margin-top:14px;"><i class="fas fa-trophy"></i> Top products (this month)</div>
                @if($ai['top_products']->isEmpty())
                    <p class="db-ai-empty">No product sales this month yet.</p>
                @else
                    <ol class="db-ai-products">
                        @foreach($ai['top_products'] as $p)
                            <li>
                                <span class="name">{{ $p->name }}</span>
                                <span class="meta">{{ number_format($p->qty, 0) }} qty · {{ $sym }} {{ number_format($p->revenue, 2) }}</span>
                            </li>
                        @endforeach
                    </ol>
                @endif

                @if($ai['low_stock_count'] > 0)
                    <div class="db-ai-stock">
                        <div class="db-ai-panel-title" style="margin-top:12px;"><i class="fas fa-exclamation-triangle"></i> Needs reorder</div>
                        <div class="db-ai-chips">
                            @foreach($ai['low_stock'] as $item)
                                <span class="db-ai-chip">{{ $item->name }} <em>{{ number_format($item->qty_available, 0) }}</em></span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="db-ai-foot">
            Generated from your QPOS sales, stock, dues &amp; cheques — works offline, no cloud AI required.
        </div>
    </div>
</div>
