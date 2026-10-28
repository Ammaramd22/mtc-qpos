<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CapitalEntry extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'amount' => 'float',
        'entry_date' => 'date',
    ];

    /** Types that increase capital credit */
    public const CREDIT_TYPES = ['investment', 'opening_balance', 'profit', 'adjustment_in'];

    /** Types that decrease capital credit (P&L / draws) */
    public const DEBIT_TYPES = ['expense', 'petty_cash', 'purchase', 'withdrawal', 'adjustment_out'];

    /** Cash/bank out that becomes a maintained asset (does not reduce capital credit) */
    public const ASSET_TYPES = ['asset'];

    /** Types that post into the Expenses module */
    public const EXPENSE_CATEGORY_TYPES = ['expense', 'petty_cash', 'purchase'];

    public static function typeLabels(): array
    {
        return [
            'investment' => __('lang_v1.capital_type_investment'),
            'opening_balance' => __('lang_v1.capital_type_opening_balance'),
            'expense' => __('lang_v1.capital_type_expense'),
            'petty_cash' => __('lang_v1.capital_type_petty_cash'),
            'purchase' => __('lang_v1.capital_type_purchase'),
            'asset' => __('lang_v1.capital_type_asset'),
            'withdrawal' => __('lang_v1.capital_type_withdrawal'),
            'profit' => __('lang_v1.capital_type_profit'),
            'adjustment_in' => __('lang_v1.capital_type_adjustment_in'),
            'adjustment_out' => __('lang_v1.capital_type_adjustment_out'),
        ];
    }

    public function isAsset(): bool
    {
        return in_array($this->type, self::ASSET_TYPES, true);
    }

    public function needsExpenseCategory(): bool
    {
        return in_array($this->type, self::EXPENSE_CATEGORY_TYPES, true);
    }

    public static function sourceLabels(): array
    {
        return [
            'bank' => __('lang_v1.capital_source_bank'),
            'cash_drawer' => __('lang_v1.capital_source_cash_drawer'),
        ];
    }

    public function isCredit(): bool
    {
        return in_array($this->type, self::CREDIT_TYPES, true);
    }

    public function signedAmount(): float
    {
        $amt = (float) $this->amount;

        // Assets leave cash/bank but stay on the balance sheet as assets
        if ($this->isAsset()) {
            return -1 * $amt;
        }

        return $this->isCredit() ? $amt : -1 * $amt;
    }

    public function partner()
    {
        return $this->belongsTo(CapitalPartner::class, 'capital_partner_id');
    }

    public function expenseCategory()
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
    }
}
