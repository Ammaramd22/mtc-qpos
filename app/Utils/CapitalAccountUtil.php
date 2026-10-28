<?php

namespace App\Utils;

use App\Account;
use App\AccountTransaction;
use App\Business;
use App\BusinessLocation;
use App\CapitalEntry;
use App\CapitalPartner;
use App\ExpenseCategory;
use App\Transaction;
use App\TransactionPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CapitalAccountUtil
{
    /** Types that create an Expense record and debit payment account via payment. */
    public const EXPENSE_TYPES = ['expense', 'petty_cash', 'purchase'];

    /** Types that only credit a payment account (no expense). */
    public const ACCOUNT_CREDIT_TYPES = ['investment', 'opening_balance', 'adjustment_in'];

    /** Types that only debit a payment account (no expense). Includes assets (cash→fixed asset). */
    public const ACCOUNT_DEBIT_TYPES = ['withdrawal', 'adjustment_out', 'asset'];

    /**
     * Whether Capital Accounts module is enabled for the current business session.
     */
    public static function isEnabled($business_id = null): bool
    {
        try {
            $modules = session('business.enabled_modules', []);
            if (! empty($business_id)) {
                $biz = Business::find($business_id);
                $modules = $biz ? (array) ($biz->enabled_modules ?? []) : $modules;
            }
            if (! is_array($modules) || ! in_array('capital_account', $modules, true)) {
                return false;
            }

            return ! function_exists('feature_enabled') || feature_enabled('capital_account');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public static function getSettings(int $business_id): array
    {
        $biz = Business::find($business_id);
        $common = is_array($biz->common_settings ?? null) ? $biz->common_settings : [];
        $cfg = is_array($common['capital_account'] ?? null) ? $common['capital_account'] : [];

        return [
            'bank_account_id' => ! empty($cfg['bank_account_id']) ? (int) $cfg['bank_account_id'] : null,
            'cash_account_id' => ! empty($cfg['cash_account_id']) ? (int) $cfg['cash_account_id'] : null,
            'expense_category_id' => ! empty($cfg['expense_category_id']) ? (int) $cfg['expense_category_id'] : null,
            'location_id' => ! empty($cfg['location_id']) ? (int) $cfg['location_id'] : null,
        ];
    }

    public static function saveSettings(int $business_id, array $input): void
    {
        $biz = Business::findOrFail($business_id);
        $common = is_array($biz->common_settings) ? $biz->common_settings : [];
        $common['capital_account'] = [
            'bank_account_id' => ! empty($input['bank_account_id']) ? (int) $input['bank_account_id'] : null,
            'cash_account_id' => ! empty($input['cash_account_id']) ? (int) $input['cash_account_id'] : null,
            'expense_category_id' => ! empty($input['expense_category_id']) ? (int) $input['expense_category_id'] : null,
            'location_id' => ! empty($input['location_id']) ? (int) $input['location_id'] : null,
        ];
        $biz->common_settings = $common;
        $biz->save();
    }

    public static function paymentAccountIdForSource(int $business_id, string $source): ?int
    {
        $settings = self::getSettings($business_id);
        if ($source === 'bank') {
            return $settings['bank_account_id'];
        }

        return $settings['cash_account_id'];
    }

    public static function getPaymentAccountBalance(?int $account_id): ?float
    {
        if (empty($account_id)) {
            return null;
        }

        $row = AccountTransaction::where('account_id', $account_id)
            ->select(DB::raw("SUM(IF(type='credit', amount, -1 * amount)) as balance"))
            ->first();

        return $row ? (float) $row->balance : 0.0;
    }

    /**
     * Sync capital entry → Payment Account and/or Expenses.
     */
    public static function syncLinkedRecords(CapitalEntry $entry, int $user_id): void
    {
        // Undo previous links first (update-safe)
        self::unlinkLinkedRecords($entry);

        $settings = self::getSettings((int) $entry->business_id);
        $account_id = self::paymentAccountIdForSource((int) $entry->business_id, (string) $entry->source);
        $amount = (float) $entry->amount;
        if ($amount <= 0) {
            return;
        }

        $note = trim('Capital: '.($entry->note ?: (CapitalEntry::typeLabels()[$entry->type] ?? $entry->type)));
        if ($entry->type === 'asset' && ! empty($entry->asset_name)) {
            $note = trim('Capital asset: '.$entry->asset_name.($entry->note ? ' — '.$entry->note : ''));
        }
        $op_date = $entry->entry_date
            ? \Carbon\Carbon::parse($entry->entry_date)->format('Y-m-d H:i:s')
            : \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        // Expense module types → create Expense + pay from linked account
        if (in_array($entry->type, self::EXPENSE_TYPES, true)) {
            if (! self::expensesModuleOn((int) $entry->business_id)) {
                // Fall back to direct account debit if expenses module off but account linked
                if ($account_id && self::accountsModuleOn((int) $entry->business_id)) {
                    $at = AccountTransaction::createAccountTransaction([
                        'amount' => $amount,
                        'account_id' => $account_id,
                        'type' => 'debit',
                        'sub_type' => 'capital_'.$entry->type,
                        'operation_date' => $op_date,
                        'created_by' => $user_id,
                        'note' => $note,
                    ]);
                    $entry->account_transaction_id = $at->id;
                    $entry->save();
                }

                return;
            }

            $location_id = $settings['location_id'];
            if (empty($location_id)) {
                $location_id = BusinessLocation::where('business_id', $entry->business_id)->value('id');
            }
            if (empty($location_id)) {
                return;
            }

            $payment = [];
            if ($account_id && self::accountsModuleOn((int) $entry->business_id)) {
                $payment[] = [
                    'amount' => $amount,
                    'method' => $entry->source === 'bank' ? 'bank_transfer' : 'cash',
                    'account_id' => $account_id,
                    'paid_on' => $op_date,
                    'note' => $note,
                ];
            }

            $req = new Request([
                'ref_no' => $entry->reference_no,
                'final_total' => $amount,
                'transaction_date' => $op_date,
                'location_id' => $location_id,
                'additional_notes' => $note,
                'expense_category_id' => ! empty($entry->expense_category_id)
                    ? $entry->expense_category_id
                    : $settings['expense_category_id'],
                'payment' => $payment,
            ]);

            $expense = (new TransactionUtil())->createExpense($req, (int) $entry->business_id, $user_id, false);
            $entry->expense_id = $expense->id;

            // Capture the account transaction created by the payment listener (if any)
            if ($account_id) {
                $at = AccountTransaction::where('transaction_id', $expense->id)
                    ->where('account_id', $account_id)
                    ->orderBy('id', 'desc')
                    ->first();
                if ($at) {
                    $entry->account_transaction_id = $at->id;
                }
            }
            $entry->save();

            return;
        }

        // Direct payment-account movements (no expense)
        if (! $account_id || ! self::accountsModuleOn((int) $entry->business_id)) {
            return;
        }

        if (in_array($entry->type, self::ACCOUNT_CREDIT_TYPES, true)) {
            $at = AccountTransaction::createAccountTransaction([
                'amount' => $amount,
                'account_id' => $account_id,
                'type' => 'credit',
                'sub_type' => $entry->type === 'opening_balance' ? 'opening_balance' : 'deposit',
                'operation_date' => $op_date,
                'created_by' => $user_id,
                'note' => $note,
            ]);
            $entry->account_transaction_id = $at->id;
            $entry->save();
        } elseif (in_array($entry->type, self::ACCOUNT_DEBIT_TYPES, true)) {
            $at = AccountTransaction::createAccountTransaction([
                'amount' => $amount,
                'account_id' => $account_id,
                'type' => 'debit',
                'sub_type' => 'capital_'.$entry->type,
                'operation_date' => $op_date,
                'created_by' => $user_id,
                'note' => $note,
            ]);
            $entry->account_transaction_id = $at->id;
            $entry->save();
        }
        // profit: capital-only (no payment account / expense by default)
    }

    /**
     * Remove linked expense / account transaction for a capital entry.
     */
    public static function unlinkLinkedRecords(CapitalEntry $entry): void
    {
        if (! empty($entry->expense_id)) {
            $expense = Transaction::where('business_id', $entry->business_id)
                ->where('type', 'expense')
                ->where('id', $entry->expense_id)
                ->first();
            if ($expense) {
                // Same cleanup as ExpenseController::destroy
                AccountTransaction::where('transaction_id', $expense->id)->forceDelete();
                TransactionPayment::where('transaction_id', $expense->id)->delete();
                $expense->delete();
            }
            $entry->expense_id = null;
            $entry->account_transaction_id = null;
            $entry->save();

            return;
        }

        if (! empty($entry->account_transaction_id)) {
            AccountTransaction::where('id', $entry->account_transaction_id)->forceDelete();
            $entry->account_transaction_id = null;
            $entry->save();
        }
    }

    protected static function accountsModuleOn(int $business_id): bool
    {
        $modules = session('business.enabled_modules', []);
        if (empty($modules)) {
            $biz = Business::find($business_id);
            $modules = $biz ? (array) ($biz->enabled_modules ?? []) : [];
        }

        return is_array($modules) && in_array('account', $modules, true);
    }

    protected static function expensesModuleOn(int $business_id): bool
    {
        $modules = session('business.enabled_modules', []);
        if (empty($modules)) {
            $biz = Business::find($business_id);
            $modules = $biz ? (array) ($biz->enabled_modules ?? []) : [];
        }

        return is_array($modules) && in_array('expenses', $modules, true);
    }

    /**
     * Build partner balance sheet + asset summary for a business.
     */
    public static function getBalanceSheet(int $business_id, $start = null, $end = null): array
    {
        $partners = CapitalPartner::where('business_id', $business_id)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get();

        $entriesQ = CapitalEntry::where('business_id', $business_id);
        if (! empty($start)) {
            $entriesQ->whereDate('entry_date', '>=', $start);
        }
        if (! empty($end)) {
            $entriesQ->whereDate('entry_date', '<=', $end);
        }
        $entries = $entriesQ->get();

        $total_investment_base = (float) $partners->sum('investment_amount');

        $extra_investment = (float) $entries->where('type', 'investment')->sum('amount');
        $opening = (float) $entries->where('type', 'opening_balance')->sum('amount');
        $profit_recorded = (float) $entries->where('type', 'profit')->sum('amount');
        $adj_in = (float) $entries->where('type', 'adjustment_in')->sum('amount');

        $expenses = (float) $entries->where('type', 'expense')->sum('amount');
        $petty_cash = (float) $entries->where('type', 'petty_cash')->sum('amount');
        $purchases = (float) $entries->where('type', 'purchase')->sum('amount');
        $withdrawals = (float) $entries->where('type', 'withdrawal')->sum('amount');
        $adj_out = (float) $entries->where('type', 'adjustment_out')->sum('amount');
        $fixed_assets = (float) $entries->where('type', 'asset')->sum('amount');

        $total_capital_in = $total_investment_base + $extra_investment + $opening + $profit_recorded + $adj_in;
        // Assets convert cash → fixed asset; they do not reduce capital credit
        $total_deductions = $expenses + $petty_cash + $purchases + $withdrawals + $adj_out;
        $credit_balance = $total_capital_in - $total_deductions;

        $by_source = ['bank' => 0.0, 'cash_drawer' => 0.0];
        foreach ($entries as $e) {
            $src = in_array($e->source, ['bank', 'cash_drawer'], true) ? $e->source : 'cash_drawer';
            $by_source[$src] += $e->signedAmount();
        }
        $cash_drawer_balance = $by_source['cash_drawer'] + $total_investment_base;
        $bank_balance = $by_source['bank'];

        $settings = self::getSettings($business_id);
        $linked = [
            'bank_account_id' => $settings['bank_account_id'],
            'cash_account_id' => $settings['cash_account_id'],
            'bank_account_name' => null,
            'cash_account_name' => null,
            'bank_from_payment_account' => false,
            'cash_from_payment_account' => false,
        ];

        if ($settings['bank_account_id']) {
            $acc = Account::find($settings['bank_account_id']);
            $linked['bank_account_name'] = $acc->name ?? null;
            $bal = self::getPaymentAccountBalance($settings['bank_account_id']);
            if ($bal !== null) {
                $bank_balance = $bal;
                $linked['bank_from_payment_account'] = true;
            }
        }
        if ($settings['cash_account_id']) {
            $acc = Account::find($settings['cash_account_id']);
            $linked['cash_account_name'] = $acc->name ?? null;
            $bal = self::getPaymentAccountBalance($settings['cash_account_id']);
            if ($bal !== null) {
                $cash_drawer_balance = $bal;
                $linked['cash_from_payment_account'] = true;
            }
        }

        $partner_rows = [];
        foreach ($partners as $p) {
            $invest = (float) $p->investment_amount;
            $p_extra_in = (float) $entries->where('capital_partner_id', $p->id)
                ->whereIn('type', ['investment', 'opening_balance', 'profit', 'adjustment_in'])
                ->sum('amount');
            $p_out = (float) $entries->where('capital_partner_id', $p->id)
                ->whereIn('type', ['expense', 'petty_cash', 'purchase', 'withdrawal', 'adjustment_out'])
                ->sum('amount');

            $partner_capital = $invest + $p_extra_in - $p_out;
            $ownership_pct = $total_investment_base > 0
                ? ($invest / $total_investment_base) * 100
                : 0;
            $profit_pct = (float) $p->profit_percent;
            $profit_amount = $profit_recorded > 0
                ? ($profit_recorded * $profit_pct / 100)
                : 0;

            $partner_rows[] = [
                'id' => $p->id,
                'name' => $p->name,
                'short_name' => $p->short_name,
                'display_name' => $p->displayName(),
                'investment_amount' => $invest,
                'ownership_percent' => round($ownership_pct, 2),
                'profit_percent' => $profit_pct,
                'profit_amount' => round($profit_amount, 4),
                'partner_balance' => round($partner_capital, 4),
                'share_of_credit' => $credit_balance != 0
                    ? round($credit_balance * $profit_pct / 100, 4)
                    : 0,
            ];
        }

        return [
            'partners' => $partner_rows,
            'linked' => $linked,
            'settings' => $settings,
            'totals' => [
                'total_investment' => round($total_investment_base + $extra_investment, 4),
                'partner_investment_base' => round($total_investment_base, 4),
                'extra_investment' => round($extra_investment, 4),
                'opening_balance' => round($opening, 4),
                'profit_recorded' => round($profit_recorded, 4),
                'expenses' => round($expenses, 4),
                'petty_cash' => round($petty_cash, 4),
                'purchases' => round($purchases, 4),
                'withdrawals' => round($withdrawals, 4),
                'fixed_assets' => round($fixed_assets, 4),
                'total_deductions' => round($total_deductions, 4),
                'credit_balance' => round($credit_balance, 4),
                'bank_balance' => round($bank_balance, 4),
                'cash_drawer_balance' => round($cash_drawer_balance, 4),
                'total_assets' => round($bank_balance + $cash_drawer_balance + $fixed_assets, 4),
            ],
            'assets' => $entries->where('type', 'asset')->values()->map(function ($e) {
                return [
                    'id' => $e->id,
                    'name' => $e->asset_name ?: ($e->reference_no ?: (__('lang_v1.capital_type_asset').' #'.$e->id)),
                    'amount' => (float) $e->amount,
                    'entry_date' => $e->entry_date,
                    'source' => $e->source,
                    'note' => $e->note,
                ];
            })->all(),
            'cash_drawer_breakdown' => [
                'opening_balance' => round((float) $entries->where('type', 'opening_balance')->where('source', 'cash_drawer')->sum('amount'), 4),
                'expenses' => round((float) $entries->where('type', 'expense')->where('source', 'cash_drawer')->sum('amount'), 4),
                'petty_cash' => round((float) $entries->where('type', 'petty_cash')->where('source', 'cash_drawer')->sum('amount'), 4),
                'purchases' => round((float) $entries->where('type', 'purchase')->where('source', 'cash_drawer')->sum('amount'), 4),
                'assets' => round((float) $entries->where('type', 'asset')->where('source', 'cash_drawer')->sum('amount'), 4),
            ],
        ];
    }

    public static function accountsDropdown(int $business_id): array
    {
        return Account::forDropdown($business_id, false, false, true);
    }

    public static function expenseCategoriesDropdown(int $business_id): array
    {
        return ExpenseCategory::where('business_id', $business_id)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }
}
