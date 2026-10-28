<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CapitalPartner extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected $casts = [
        'investment_amount' => 'float',
        'profit_percent' => 'float',
        'is_active' => 'boolean',
    ];

    public function entries()
    {
        return $this->hasMany(CapitalEntry::class, 'capital_partner_id');
    }

    public function displayName(): string
    {
        if (! empty($this->short_name)) {
            return $this->name.' ('.$this->short_name.')';
        }

        return (string) $this->name;
    }
}
