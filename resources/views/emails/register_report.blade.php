<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body { font-family: Arial, sans-serif; font-size: 13px; color: #222; background: #f5f5f5; margin: 0; padding: 0; }
.wrap { max-width: 600px; margin: 20px auto; background: #fff; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; }
.header { background: #1d4ed8; color: #fff; padding: 20px 24px; }
.header h2 { margin: 0; font-size: 18px; }
.header p { margin: 4px 0 0; font-size: 12px; opacity: 0.85; }
.body { padding: 20px 24px; }
.section-title { font-size: 13px; font-weight: 700; color: #1d4ed8; border-bottom: 2px solid #e5e7eb; padding-bottom: 4px; margin: 18px 0 10px; text-transform: uppercase; letter-spacing: 0.4px; }
table { width: 100%; border-collapse: collapse; font-size: 13px; }
table td { padding: 6px 4px; border-bottom: 1px solid #f3f4f6; }
table td:last-child { text-align: right; font-weight: 600; }
.total-row td { font-weight: 800; background: #f0fdf4; color: #15803d; border-top: 2px solid #bbf7d0; }
.warning-row td { font-weight: 800; background: #fffbeb; color: #92400e; border-top: 2px solid #fde68a; }
.footer { padding: 14px 24px; background: #f9fafb; border-top: 1px solid #e5e7eb; font-size: 11px; color: #9ca3af; text-align: center; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h2>{{ $businessName }}</h2>
    <p>Register Close Report &mdash; {{ $closedAt }}</p>
  </div>
  <div class="body">

    <div class="section-title">Cash Drawer Summary</div>
    <table>
      <tr><td>Opening Balance</td><td>{{ number_format($reportData['register_details']->cash_in_hand, 2) }}</td></tr>
      <tr><td>(+) Cash Received (Sales)</td><td>{{ number_format($reportData['register_details']->total_cash, 2) }}</td></tr>
      <tr><td>(+) Cash Received (Due Payments)</td><td>{{ number_format($reportData['register_details']->total_cash_advance_payment, 2) }}</td></tr>
      <tr><td>(-) Cash Refunds</td><td>{{ number_format($reportData['register_details']->total_cash_refund, 2) }}</td></tr>
      <tr><td>(-) Cash Expenses</td><td>{{ number_format($reportData['register_details']->total_cash_expense, 2) }}</td></tr>
      <tr class="total-row">
        <td>Expected Cash in Drawer</td>
        <td>{{ number_format($reportData['register_details']->cash_in_hand + $reportData['register_details']->total_cash + $reportData['register_details']->total_cash_advance_payment - $reportData['register_details']->total_cash_refund - $reportData['register_details']->total_cash_expense, 2) }}</td>
      </tr>
      <tr>
        <td>Closing Cash (Counted)</td>
        <td>{{ number_format($reportData['closing_amount'], 2) }}</td>
      </tr>
    </table>

    <div class="section-title">Sales Summary</div>
    <table>
      <tr><td>Gross Sales</td><td>{{ number_format($reportData['transaction_details']->total_sales, 2) }}</td></tr>
      <tr><td>(-) Refunds</td><td>{{ number_format($reportData['register_details']->total_refund, 2) }}</td></tr>
      <tr class="total-row">
        <td>Net Sales</td>
        <td>{{ number_format($reportData['transaction_details']->total_sales - $reportData['register_details']->total_refund, 2) }}</td>
      </tr>
    </table>

    <div class="section-title">Payments Collected</div>
    <table>
      @if($reportData['register_details']->total_cash > 0)
      <tr><td>Cash</td><td>{{ number_format($reportData['register_details']->total_cash, 2) }}</td></tr>
      @endif
      @if($reportData['register_details']->total_card > 0)
      <tr><td>Card</td><td>{{ number_format($reportData['register_details']->total_card, 2) }}</td></tr>
      @endif
      @if($reportData['register_details']->total_cheque > 0)
      <tr><td>Cheque</td><td>{{ number_format($reportData['register_details']->total_cheque, 2) }}</td></tr>
      @endif
      @if($reportData['register_details']->total_bank_transfer > 0)
      <tr><td>Bank Transfer</td><td>{{ number_format($reportData['register_details']->total_bank_transfer, 2) }}</td></tr>
      @endif
      <tr class="total-row">
        <td>Total Collected</td>
        <td>{{ number_format($reportData['register_details']->total_sale + $reportData['register_details']->total_advance_payment, 2) }}</td>
      </tr>
    </table>

    <div class="section-title">Outstanding Receivables</div>
    <table>
      <tr><td>Gross Sales</td><td>{{ number_format($reportData['transaction_details']->total_sales, 2) }}</td></tr>
      <tr><td>(-) Total Collected</td><td>{{ number_format($reportData['register_details']->total_sale, 2) }}</td></tr>
      <tr class="warning-row">
        <td>Pending from Customers</td>
        <td>{{ number_format($reportData['transaction_details']->total_sales - $reportData['register_details']->total_sale - ($reportData['due_applied'] ?? 0), 2) }}</td>
      </tr>
    </table>

    @if(!empty($reportData['closing_note']))
    <div class="section-title">Closing Note</div>
    <p style="color:#374151;">{{ $reportData['closing_note'] }}</p>
    @endif

  </div>
  <div class="footer">
    This report was automatically generated when the register was closed.<br>
    {{ config('app.brand_footer') }}
  </div>
</div>
</body>
</html>
