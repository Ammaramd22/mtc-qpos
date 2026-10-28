<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MobileImei extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'sold_at' => 'datetime',
        'warranty_starts_at' => 'datetime',
        'warranty_ends_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variation()
    {
        return $this->belongsTo(Variation::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function warranty()
    {
        return $this->belongsTo(Warranty::class);
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function sellTransaction()
    {
        return $this->belongsTo(Transaction::class, 'sell_transaction_id');
    }

    public function repairs()
    {
        return $this->hasMany(MobileRepair::class);
    }

    public function isUnderWarranty(): bool
    {
        if (empty($this->warranty_ends_at)) {
            return false;
        }

        return now()->lte($this->warranty_ends_at);
    }
}
