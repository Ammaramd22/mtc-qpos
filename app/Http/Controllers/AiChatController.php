<?php

namespace App\Http\Controllers;

use App\Business;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AiChatController extends Controller
{
    protected function checkModuleEnabled()
    {
        $enabled = session('business.enabled_modules', []);
        if (! in_array('ai_assistant', $enabled)) {
            abort(403, 'AI Assistant module is not enabled.');
        }
    }

    protected function money($amount)
    {
        $symbol = session('currency.symbol', 'Rs');
        $thousand = session('currency.thousand_separator', ',');
        $decimal = session('currency.decimal_separator', '.');

        return $symbol.' '.number_format((float) $amount, 2, $decimal, $thousand);
    }

    /**
     * Live POS stats used by both Gemini context and offline replies.
     */
    protected function getBusinessStats()
    {
        $business_id = request()->session()->get('user.business_id');
        $today = Carbon::today();

        $todaySales = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $today)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(final_total),0) as total')
            ->first();

        $yesterday = Carbon::yesterday();
        $yesterdaySales = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereDate('transaction_date', $yesterday)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(final_total),0) as total')
            ->first();

        $monthSales = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereYear('transaction_date', $today->year)
            ->whereMonth('transaction_date', $today->month)
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(final_total),0) as total')
            ->first();

        $topProducts = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereYear('t.transaction_date', $today->year)
            ->whereMonth('t.transaction_date', $today->month)
            ->selectRaw('p.name, SUM(tsl.quantity) as qty, SUM(tsl.unit_price_inc_tax * tsl.quantity) as revenue')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();

        $lowStock = DB::table('variation_location_details as vld')
            ->join('product_variations as pv', 'pv.id', '=', 'vld.product_variation_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')
            ->where('p.business_id', $business_id)
            ->whereRaw('vld.qty_available <= p.alert_quantity')
            ->where('p.alert_quantity', '>', 0)
            ->selectRaw('p.name, vld.qty_available, p.alert_quantity')
            ->orderBy('vld.qty_available')
            ->limit(10)
            ->get();

        $payments = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereYear('t.transaction_date', $today->year)
            ->whereMonth('t.transaction_date', $today->month)
            ->selectRaw('tp.method, SUM(tp.amount) as total')
            ->groupBy('tp.method')
            ->get();

        $dues = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->where('payment_status', '!=', 'paid')
            ->selectRaw('COALESCE(SUM(final_total - (SELECT COALESCE(SUM(amount),0) FROM transaction_payments WHERE transaction_id = transactions.id)),0) as total_due, COUNT(*) as count')
            ->first();

        $expenseMonth = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'expense')
            ->whereYear('transaction_date', $today->year)
            ->whereMonth('transaction_date', $today->month)
            ->selectRaw('COALESCE(SUM(final_total),0) as total')
            ->value('total');

        // Pending receivable (incoming) & payable (outgoing) cheques
        $chequeBase = function () use ($business_id) {
            return DB::table('transaction_payments')
                ->where('business_id', $business_id)
                ->where('method', 'cheque')
                ->whereNull('parent_id')
                ->where(function ($q) {
                    $q->where('is_return', 0)->orWhereNull('is_return');
                })
                ->where('cheque_status', 'pending');
        };

        $recvPending = $chequeBase()->where('cheque_type', 'incoming')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount),0) as total')->first();
        $payPending = $chequeBase()->where('cheque_type', 'outgoing')
            ->selectRaw('COUNT(*) as cnt, COALESCE(SUM(amount),0) as total')->first();

        $todayStr = $today->toDateString();
        $in7 = $today->copy()->addDays(7)->toDateString();

        $recvOverdue = $chequeBase()->where('cheque_type', 'incoming')
            ->where('cheque_date', '<', $todayStr)->count();
        $payOverdue = $chequeBase()->where('cheque_type', 'outgoing')
            ->where('cheque_date', '<', $todayStr)->count();
        $recvDueSoon = $chequeBase()->where('cheque_type', 'incoming')
            ->whereBetween('cheque_date', [$todayStr, $in7])->count();
        $payDueSoon = $chequeBase()->where('cheque_type', 'outgoing')
            ->whereBetween('cheque_date', [$todayStr, $in7])->count();

        $upcomingCheques = DB::table('transaction_payments as tp')
            ->leftJoin('transactions as t', 'tp.transaction_id', '=', 't.id')
            ->leftJoin('contacts as c1', 't.contact_id', '=', 'c1.id')
            ->leftJoin('contacts as c2', 'tp.payment_for', '=', 'c2.id')
            ->where('tp.business_id', $business_id)
            ->where('tp.method', 'cheque')
            ->whereNull('tp.parent_id')
            ->where(function ($q) {
                $q->where('tp.is_return', 0)->orWhereNull('tp.is_return');
            })
            ->where('tp.cheque_status', 'pending')
            ->where(function ($q) use ($todayStr, $in7) {
                $q->where('tp.cheque_date', '<', $todayStr)
                    ->orWhereBetween('tp.cheque_date', [$todayStr, $in7]);
            })
            ->select([
                'tp.cheque_number',
                'tp.cheque_date',
                'tp.amount',
                'tp.cheque_type',
                DB::raw('COALESCE(c1.name, c2.name) as contact_name'),
            ])
            ->orderBy('tp.cheque_date', 'asc')
            ->limit(6)
            ->get();

        $business = Business::find($business_id);

        return [
            'business_name' => $business->name ?? 'Your business',
            'today' => $today,
            'today_count' => (int) ($todaySales->count ?? 0),
            'today_total' => (float) ($todaySales->total ?? 0),
            'yesterday_count' => (int) ($yesterdaySales->count ?? 0),
            'yesterday_total' => (float) ($yesterdaySales->total ?? 0),
            'month_count' => (int) ($monthSales->count ?? 0),
            'month_total' => (float) ($monthSales->total ?? 0),
            'top_products' => $topProducts,
            'low_stock' => $lowStock,
            'payments' => $payments,
            'dues_count' => (int) ($dues->count ?? 0),
            'dues_total' => (float) ($dues->total_due ?? 0),
            'expense_month' => (float) ($expenseMonth ?? 0),
            'recv_cheque_count' => (int) ($recvPending->cnt ?? 0),
            'recv_cheque_total' => (float) ($recvPending->total ?? 0),
            'recv_cheque_overdue' => (int) $recvOverdue,
            'recv_cheque_due_soon' => (int) $recvDueSoon,
            'pay_cheque_count' => (int) ($payPending->cnt ?? 0),
            'pay_cheque_total' => (float) ($payPending->total ?? 0),
            'pay_cheque_overdue' => (int) $payOverdue,
            'pay_cheque_due_soon' => (int) $payDueSoon,
            'upcoming_cheques' => $upcomingCheques,
        ];
    }

    protected function getBusinessContextString(array $s)
    {
        $topProductsList = $s['top_products']->map(fn ($p) => "{$p->name} (qty: {$p->qty}, revenue: {$p->revenue})")->implode(', ');
        $lowStockList = $s['low_stock']->map(fn ($p) => "{$p->name} (available: {$p->qty_available}, alert: {$p->alert_quantity})")->implode(', ');
        $paymentList = $s['payments']->map(fn ($p) => "{$p->method}: {$p->total}")->implode(', ');

        return 'You are a smart business assistant AI for a POS retail system called QPOS. '
            ."Business: {$s['business_name']}. Today: ".$s['today']->format('d M Y').'. '
            ."TODAY - Sales: {$s['today_count']} transactions, Total: {$s['today_total']}. "
            ."THIS MONTH - Sales: {$s['month_count']} transactions, Total: {$s['month_total']}. "
            ."TOP PRODUCTS THIS MONTH: {$topProductsList}. "
            ."PAYMENT METHODS: {$paymentList}. "
            .'LOW STOCK ITEMS: '.($lowStockList ?: 'none').'. '
            ."OUTSTANDING DUES: {$s['dues_count']} invoices, Total: {$s['dues_total']}. "
            ."RECEIVABLE CHEQUES (pending from customers): {$s['recv_cheque_count']} totalling {$s['recv_cheque_total']} "
            ."(overdue: {$s['recv_cheque_overdue']}, due within 7 days: {$s['recv_cheque_due_soon']}). "
            ."PAYABLE CHEQUES (pending to suppliers): {$s['pay_cheque_count']} totalling {$s['pay_cheque_total']} "
            ."(overdue: {$s['pay_cheque_overdue']}, due within 7 days: {$s['pay_cheque_due_soon']}). "
            .'Answer questions about sales, products, customers, stock, cheques, and give actionable business insights. '
            .'Be concise, friendly, and professional. Format numbers nicely. Use bullet points when listing.';
    }

    /**
     * Rule-based assistant using live DB data — works offline, no API key.
     */
    protected function offlineReply(string $message, array $s)
    {
        $q = mb_strtolower(trim($message));
        $dateLabel = $s['today']->format('d M Y');

        // Today's sales
        if (preg_match('/\b(today|todays|day\'?s)\b.*\b(sale|sales|revenue|earning)/', $q)
            || preg_match('/\b(sale|sales|revenue)\b.*\b(today|todays)\b/', $q)
            || str_contains($q, 'how are my sales today')
            || str_contains($q, "today's sales")
            || str_contains($q, 'todays sales')) {
            $vsY = $s['today_total'] - $s['yesterday_total'];
            $vsLine = $s['yesterday_total'] > 0
                ? ($vsY >= 0
                    ? 'Up '.$this->money(abs($vsY)).' vs yesterday.'
                    : 'Down '.$this->money(abs($vsY)).' vs yesterday.')
                : 'No sales recorded yesterday for comparison.';

            return "**Today's Sales** ({$dateLabel})\n"
                ."- Transactions: **{$s['today_count']}**\n"
                .'- Total: **'.$this->money($s['today_total'])."**\n"
                ."- {$vsLine}";
        }

        // Month sales
        if (preg_match('/\b(this month|month|monthly)\b.*\b(sale|sales|revenue)/', $q)
            || preg_match('/\b(sale|sales|revenue)\b.*\b(this month|month|monthly)\b/', $q)) {
            $avg = $s['month_count'] > 0 ? $s['month_total'] / $s['month_count'] : 0;

            return "**This Month's Sales**\n"
                ."- Transactions: **{$s['month_count']}**\n"
                .'- Total: **'.$this->money($s['month_total'])."**\n"
                .'- Average ticket: **'.$this->money($avg).'**';
        }

        // Top products
        if (preg_match('/\b(top|best|selling|popular)\b.*\b(product|item|sku)/', $q)
            || preg_match('/\b(product|item)\b.*\b(top|best|selling)\b/', $q)
            || str_contains($q, 'top selling')
            || str_contains($q, 'top products')) {
            if ($s['top_products']->isEmpty()) {
                return 'No product sales found for this month yet.';
            }
            $lines = ["**Top Products This Month**"];
            $i = 1;
            foreach ($s['top_products'] as $p) {
                $qty = rtrim(rtrim(number_format((float) $p->qty, 3, '.', ''), '0'), '.');
                $lines[] = "{$i}. **{$p->name}** — qty {$qty}, revenue ".$this->money($p->revenue);
                $i++;
            }

            return implode("\n", $lines);
        }

        // Low stock
        if (preg_match('/\b(low|out of|reorder|alert)\b.*\b(stock|inventory|item)/', $q)
            || preg_match('/\b(stock|inventory)\b.*\b(low|alert|out)\b/', $q)
            || str_contains($q, 'low in stock')
            || str_contains($q, 'low stock')) {
            if ($s['low_stock']->isEmpty()) {
                return '✅ **Stock looks healthy** — no items are at or below their alert quantity.';
            }
            $lines = ['**Low Stock Items** (at or below alert qty)'];
            foreach ($s['low_stock'] as $p) {
                $avail = rtrim(rtrim(number_format((float) $p->qty_available, 3, '.', ''), '0'), '.');
                $alert = rtrim(rtrim(number_format((float) $p->alert_quantity, 3, '.', ''), '0'), '.');
                $lines[] = "- **{$p->name}** — available {$avail} (alert {$alert})";
            }
            $lines[] = "\nTip: Reorder these soon to avoid stockouts.";

            return implode("\n", $lines);
        }

        // Dues / receivables
        if ((preg_match('/\b(due|dues|outstanding|receivable|credit|unpaid)\b/', $q)
            && ! preg_match('/\bcheque\b/', $q))
            || str_contains($q, 'customer due')) {
            return "**Outstanding Customer Dues**\n"
                ."- Unpaid / partial invoices: **{$s['dues_count']}**\n"
                .'- Total due: **'.$this->money($s['dues_total'])."**\n"
                .($s['dues_total'] > 0
                    ? "\nTip: Follow up on overdue invoices today to improve cash flow."
                    : "\nNice — no receivables outstanding.");
        }

        // Cheques — receivable / payable
        if (preg_match('/\bcheque\b/', $q)
            || preg_match('/\b(payable|receivable)\b.*\bcheque\b/', $q)
            || str_contains($q, 'cheque due')
            || str_contains($q, 'pending cheque')) {
            $lines = ["**Cheque Status**"];
            $lines[] = '- **Receivable** (from customers): **'.$s['recv_cheque_count'].'** pending · '.$this->money($s['recv_cheque_total'])
                ." (overdue {$s['recv_cheque_overdue']}, due ≤7 days {$s['recv_cheque_due_soon']})";
            $lines[] = '- **Payable** (to suppliers): **'.$s['pay_cheque_count'].'** pending · '.$this->money($s['pay_cheque_total'])
                ." (overdue {$s['pay_cheque_overdue']}, due ≤7 days {$s['pay_cheque_due_soon']})";
            if ($s['upcoming_cheques']->isNotEmpty()) {
                $lines[] = "\n**Due / overdue soon:**";
                foreach ($s['upcoming_cheques'] as $c) {
                    $type = ($c->cheque_type ?? '') === 'outgoing' ? 'Payable' : 'Receivable';
                    $date = ! empty($c->cheque_date) ? Carbon::parse($c->cheque_date)->format('d M') : '—';
                    $who = $c->contact_name ?: '—';
                    $no = $c->cheque_number ?: '—';
                    $lines[] = "- {$type} #{$no} · {$who} · {$date} · ".$this->money($c->amount);
                }
            }

            return implode("\n", $lines);
        }

        // Payments breakdown
        if (preg_match('/\b(payment|cash|cheque|card|method)\b/', $q) && preg_match('/\b(break|summary|how|total|method)\b/', $q)
            || str_contains($q, 'payment method')) {
            if ($s['payments']->isEmpty()) {
                return 'No payments recorded this month yet.';
            }
            $lines = ['**Payment Methods This Month**'];
            foreach ($s['payments'] as $p) {
                $method = ucfirst(str_replace('_', ' ', (string) $p->method));
                $lines[] = '- **'.$method.'**: '.$this->money($p->total);
            }

            return implode("\n", $lines);
        }

        // Expense
        if (preg_match('/\b(expense|expenses|spending|cost)\b/', $q)) {
            return "**Operating Expenses This Month**\n"
                .'- Total: **'.$this->money($s['expense_month']).'**';
        }

        // Business tips (local, data-driven)
        if (preg_match('/\b(tip|tips|advice|improve|suggest|recommendation|idea)\b/', $q)
            || str_contains($q, 'actionable')) {
            return $this->buildLocalTips($s);
        }

        // Greeting / help
        if (preg_match('/^(hi|hello|hey|help|what can you|menu)\b/', $q) || $q === '?') {
            return "Hi! I'm your **local QPOS assistant** (works offline, no API key).\n\n"
                ."I can answer from your live data:\n"
                ."- Today's / this month's **sales**\n"
                ."- **Top products**\n"
                ."- **Low stock** alerts\n"
                ."- Customer **dues**\n"
                ."- Receivable / payable **cheques**\n"
                ."- **Payment** methods\n"
                ."- **Expenses**\n"
                ."- Actionable **business tips**\n\n"
                .'Try a quick button above, or ask in plain English.';
        }

        // Overview / summary
        if (preg_match('/\b(overview|summary|dashboard|how is|how\'s|status|report)\b/', $q)) {
            return "**{$s['business_name']} — Snapshot ({$dateLabel})**\n"
                .'- Today: **'.$this->money($s['today_total'])."** ({$s['today_count']} sales)\n"
                .'- This month: **'.$this->money($s['month_total'])."** ({$s['month_count']} sales)\n"
                .'- Dues: **'.$this->money($s['dues_total'])."** ({$s['dues_count']} invoices)\n"
                .'- Receivable cheques: **'.$this->money($s['recv_cheque_total'])."** ({$s['recv_cheque_count']} pending)\n"
                .'- Payable cheques: **'.$this->money($s['pay_cheque_total'])."** ({$s['pay_cheque_count']} pending)\n"
                .'- Low stock items: **'.$s['low_stock']->count()."**\n"
                .'- Expenses (month): **'.$this->money($s['expense_month']).'**';
        }

        // Default: helpful snapshot + guide
        return "I couldn't match that exactly, but here's a quick snapshot for **{$s['business_name']}**:\n\n"
            .'- Today: **'.$this->money($s['today_total'])."** ({$s['today_count']} sales)\n"
            .'- This month: **'.$this->money($s['month_total'])."**\n"
            .'- Dues: **'.$this->money($s['dues_total'])."**\n"
            .'- Receivable cheques: **'.$this->money($s['recv_cheque_total'])."**\n"
            .'- Payable cheques: **'.$this->money($s['pay_cheque_total'])."**\n"
            .'- Low stock: **'.$s['low_stock']->count()." items**\n\n"
            .'Ask about **sales**, **top products**, **low stock**, **dues**, **cheques**, **payments**, or **tips**.';
    }

    /**
     * Structured tip list from live POS data (no external API).
     */
    protected function buildLocalTipsList(array $s)
    {
        $tips = [];

        // Prioritise cheque alerts when overdue / due soon
        if ($s['recv_cheque_overdue'] > 0 || $s['recv_cheque_due_soon'] > 0) {
            $parts = [];
            if ($s['recv_cheque_overdue'] > 0) {
                $parts[] = $s['recv_cheque_overdue'].' overdue';
            }
            if ($s['recv_cheque_due_soon'] > 0) {
                $parts[] = $s['recv_cheque_due_soon'].' due within 7 days';
            }
            $tips[] = [
                'icon' => 'fa-money-check-alt',
                'tone' => $s['recv_cheque_overdue'] > 0 ? 'red' : 'amber',
                'text' => 'Receivable cheques: '.$this->money($s['recv_cheque_total']).' pending ('.implode(', ', $parts).') — bank or remind customers.',
            ];
        } elseif ($s['recv_cheque_count'] > 0) {
            $tips[] = [
                'icon' => 'fa-money-check-alt',
                'tone' => 'cyan',
                'text' => 'Receivable cheques: '.$s['recv_cheque_count'].' pending · '.$this->money($s['recv_cheque_total']).'. Track clearing dates.',
            ];
        }

        if ($s['pay_cheque_overdue'] > 0 || $s['pay_cheque_due_soon'] > 0) {
            $parts = [];
            if ($s['pay_cheque_overdue'] > 0) {
                $parts[] = $s['pay_cheque_overdue'].' overdue';
            }
            if ($s['pay_cheque_due_soon'] > 0) {
                $parts[] = $s['pay_cheque_due_soon'].' due within 7 days';
            }
            $tips[] = [
                'icon' => 'fa-file-invoice-dollar',
                'tone' => $s['pay_cheque_overdue'] > 0 ? 'red' : 'amber',
                'text' => 'Payable cheques: '.$this->money($s['pay_cheque_total']).' pending ('.implode(', ', $parts).') — ensure funds before deposit.',
            ];
        } elseif ($s['pay_cheque_count'] > 0) {
            $tips[] = [
                'icon' => 'fa-file-invoice-dollar',
                'tone' => 'cyan',
                'text' => 'Payable cheques: '.$s['pay_cheque_count'].' pending · '.$this->money($s['pay_cheque_total']).'. Plan cash for supplier deposits.',
            ];
        }

        if ($s['dues_total'] > 0) {
            $tips[] = [
                'icon' => 'fa-hand-holding-usd',
                'tone' => 'amber',
                'text' => 'Recover '.$this->money($s['dues_total']).' outstanding dues from '.$s['dues_count'].' invoice(s) — improves cash this week.',
            ];
        } elseif (count($tips) < 3) {
            $tips[] = [
                'icon' => 'fa-check-circle',
                'tone' => 'green',
                'text' => 'Receivables look clear. Keep credit tight by reviewing pay terms for new customers.',
            ];
        }

        if ($s['low_stock']->isNotEmpty()) {
            $names = $s['low_stock']->take(3)->pluck('name')->implode(', ');
            $extra = $s['low_stock']->count() > 3 ? ' and more' : '';
            $tips[] = [
                'icon' => 'fa-boxes',
                'tone' => 'red',
                'text' => "Reorder soon: {$names}{$extra} are at or below alert qty.",
            ];
        } elseif (count($tips) < 3) {
            $tips[] = [
                'icon' => 'fa-warehouse',
                'tone' => 'green',
                'text' => 'Stock alerts are clear — review slow movers and push a promo on low-turnover items.',
            ];
        }

        if ($s['today_total'] < $s['yesterday_total'] && $s['yesterday_total'] > 0) {
            $tips[] = [
                'icon' => 'fa-chart-line',
                'tone' => 'amber',
                'text' => "Today's sales are behind yesterday. Try a short bundle offer or suggest add-ons at checkout.",
            ];
        } elseif ($s['today_count'] === 0) {
            $tips[] = [
                'icon' => 'fa-store',
                'tone' => 'amber',
                'text' => 'No sales yet today — open POS, check displays, and run a quick WhatsApp or counter offer.',
            ];
        } elseif (count($tips) < 3) {
            $avg = $s['month_count'] > 0 ? $s['month_total'] / $s['month_count'] : 0;
            $tips[] = [
                'icon' => 'fa-bullseye',
                'tone' => 'cyan',
                'text' => 'Raise average ticket (now '.$this->money($avg).') with upsells and combo deals.',
            ];
        }

        if ($s['expense_month'] > 0 && $s['month_total'] > 0 && ($s['expense_month'] / max($s['month_total'], 1)) > 0.3) {
            $tips[] = [
                'icon' => 'fa-receipt',
                'tone' => 'red',
                'text' => 'Expenses are high vs monthly sales ('.$this->money($s['expense_month']).'). Review recurring costs this week.',
            ];
        }

        return array_slice($tips, 0, 4);
    }

    protected function buildLocalTips(array $s)
    {
        $tips = $this->buildLocalTipsList($s);
        $lines = ["**3 actionable tips for {$s['business_name']} this week**"];
        foreach ($tips as $i => $tip) {
            $lines[] = ($i + 1).'. '.$tip['text'];
        }
        $lines[] = "\n_Based on your live QPOS data — works offline._";

        return implode("\n", $lines);
    }

    /**
     * Local dashboard insight payload from in-app sales/stock/dues data.
     */
    public function getDashboardInsights()
    {
        $s = $this->getBusinessStats();
        $vsY = $s['today_total'] - $s['yesterday_total'];
        $vsPct = $s['yesterday_total'] > 0
            ? round(($vsY / $s['yesterday_total']) * 100, 1)
            : null;

        $headline = 'Steady day so far';
        if ($s['today_count'] === 0) {
            $headline = 'Waiting for the first sale today';
        } elseif ($s['recv_cheque_overdue'] > 0 || $s['pay_cheque_overdue'] > 0) {
            $headline = 'Cheque follow-ups needed today';
        } elseif ($vsPct !== null && $vsPct >= 10) {
            $headline = 'Sales are up vs yesterday';
        } elseif ($vsPct !== null && $vsPct <= -10) {
            $headline = 'Sales are trailing yesterday';
        } elseif ($s['dues_total'] > 0 && $s['dues_total'] > ($s['today_total'] * 2)) {
            $headline = 'Credit recovery can unlock cash quickly';
        } elseif ($s['low_stock']->isNotEmpty()) {
            $headline = 'Watch stock — some items need reorder';
        }

        return [
            'business_name' => $s['business_name'],
            'date_label' => $s['today']->format('d M Y'),
            'headline' => $headline,
            'today_total' => $s['today_total'],
            'today_count' => $s['today_count'],
            'yesterday_total' => $s['yesterday_total'],
            'vs_yesterday' => $vsY,
            'vs_yesterday_pct' => $vsPct,
            'month_total' => $s['month_total'],
            'month_count' => $s['month_count'],
            'dues_total' => $s['dues_total'],
            'dues_count' => $s['dues_count'],
            'expense_month' => $s['expense_month'],
            'low_stock_count' => $s['low_stock']->count(),
            'low_stock' => $s['low_stock']->take(5)->values(),
            'top_products' => $s['top_products']->take(5)->values(),
            'recv_cheque_count' => $s['recv_cheque_count'],
            'recv_cheque_total' => $s['recv_cheque_total'],
            'recv_cheque_overdue' => $s['recv_cheque_overdue'],
            'recv_cheque_due_soon' => $s['recv_cheque_due_soon'],
            'pay_cheque_count' => $s['pay_cheque_count'],
            'pay_cheque_total' => $s['pay_cheque_total'],
            'pay_cheque_overdue' => $s['pay_cheque_overdue'],
            'pay_cheque_due_soon' => $s['pay_cheque_due_soon'],
            'upcoming_cheques' => $s['upcoming_cheques'],
            'tips' => $this->buildLocalTipsList($s),
        ];
    }

    public function chat(Request $request)
    {
        $this->checkModuleEnabled();
        $request->validate(['message' => 'required|string|max:1000']);

        $stats = $this->getBusinessStats();
        $userMessage = $request->input('message');
        $apiKey = config('services.gemini.key');
        $hasKey = ! empty($apiKey) && $apiKey !== 'your_gemini_api_key_here';

        // No Gemini key → local offline assistant
        if (! $hasKey) {
            return response()->json([
                'reply' => $this->offlineReply($userMessage, $stats),
                'mode' => 'local',
            ]);
        }

        // Try Gemini; fall back to local on any failure
        try {
            $systemContext = $this->getBusinessContextString($stats);
            $history = $request->input('history', []);
            $contents = [];

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $systemContext]],
            ];
            $contents[] = [
                'role' => 'model',
                'parts' => [['text' => 'Understood. I am your QPOS business assistant. How can I help you today?']],
            ];

            foreach ($history as $turn) {
                if (! empty($turn['role']) && ! empty($turn['text'])) {
                    $contents[] = [
                        'role' => $turn['role'] === 'user' ? 'user' : 'model',
                        'parts' => [['text' => $turn['text']]],
                    ];
                }
            }

            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]],
            ];

            $response = Http::withHeaders(['Content-Type' => 'application/json'])
                ->timeout(20)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}", [
                    'contents' => $contents,
                    'generationConfig' => [
                        'temperature' => 0.7,
                        'maxOutputTokens' => 1024,
                    ],
                ]);

            if ($response->failed()) {
                return response()->json([
                    'reply' => $this->offlineReply($userMessage, $stats),
                    'mode' => 'local_fallback',
                ]);
            }

            $text = $response->json('candidates.0.content.parts.0.text') ?? null;
            if (empty($text)) {
                return response()->json([
                    'reply' => $this->offlineReply($userMessage, $stats),
                    'mode' => 'local_fallback',
                ]);
            }

            return response()->json([
                'reply' => $text,
                'mode' => 'gemini',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'reply' => $this->offlineReply($userMessage, $stats),
                'mode' => 'local_fallback',
            ]);
        }
    }

    public function insights()
    {
        $this->checkModuleEnabled();
        $business_id = request()->session()->get('user.business_id');
        $today = Carbon::today();

        $dailySales = DB::table('transactions')
            ->where('business_id', $business_id)
            ->where('type', 'sell')
            ->where('status', 'final')
            ->whereBetween('transaction_date', [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()])
            ->selectRaw('DATE(transaction_date) as date, COUNT(*) as count, SUM(final_total) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $topProducts = DB::table('transaction_sell_lines as tsl')
            ->join('transactions as t', 't.id', '=', 'tsl.transaction_id')
            ->join('products as p', 'p.id', '=', 'tsl.product_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereBetween('t.transaction_date', [Carbon::now()->subDays(29)->startOfDay(), Carbon::now()->endOfDay()])
            ->selectRaw('p.name, SUM(tsl.quantity) as qty, SUM(tsl.unit_price_inc_tax * tsl.quantity) as revenue')
            ->groupBy('p.id', 'p.name')
            ->orderByDesc('revenue')
            ->limit(10)
            ->get();

        $payments = DB::table('transaction_payments as tp')
            ->join('transactions as t', 't.id', '=', 'tp.transaction_id')
            ->where('t.business_id', $business_id)
            ->where('t.type', 'sell')
            ->where('t.status', 'final')
            ->whereMonth('t.transaction_date', $today->month)
            ->selectRaw('tp.method, SUM(tp.amount) as total')
            ->groupBy('tp.method')
            ->get();

        $stats = $this->getBusinessStats();

        return view('ai.insights', compact('dailySales', 'topProducts', 'payments', 'stats'));
    }
}
