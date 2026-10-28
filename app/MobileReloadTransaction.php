<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MobileReloadTransaction extends Model
{
    protected $guarded = ['id'];

    public static function paymentTypes(): array
    {
        return [
            'reload' => __('lang_v1.mobile_reload'),
            'utility' => __('lang_v1.utility_bill'),
            'other' => __('lang_v1.other_payment'),
        ];
    }

    public static function operators(): array
    {
        return [
            'dialog' => 'Dialog',
            'mobitel' => 'Mobitel',
            'hutch' => 'Hutch',
            'airtel' => 'Airtel',
        ];
    }

    public static function utilityProviders(): array
    {
        return [
            'ceb' => 'CEB — Electricity',
            'water' => 'National Water Supply',
            'dialog_tv' => 'Dialog TV / Satellite',
            'slt' => 'SLT / Fiber',
            'insurance' => 'Insurance Premium',
            'other_utility' => 'Other Utility',
        ];
    }

    public function getTypeLabelAttribute(): string
    {
        return self::paymentTypes()[$this->payment_type ?? 'reload'] ?? ucfirst($this->payment_type ?? 'reload');
    }

    public function getProviderLabelAttribute(): string
    {
        if ($this->payment_type === 'reload') {
            return self::operators()[$this->operator] ?? ($this->operator ?? '—');
        }

        if ($this->payment_type === 'utility') {
            return self::utilityProviders()[$this->bill_provider] ?? ($this->bill_provider ?? '—');
        }

        return $this->notes ?? __('lang_v1.other_payment');
    }

    public function getAccountDisplayAttribute(): string
    {
        if (! empty($this->account_number)) {
            return $this->account_number;
        }

        return $this->mobile_number ?? '—';
    }
}
