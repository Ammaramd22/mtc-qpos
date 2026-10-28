<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class NutmegDrying extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'qty_before' => 'float',
        'qty_after' => 'float',
        'qty_wastage' => 'float',
        'unit_cost_before' => 'float',
        'unit_cost_after' => 'float',
        'stock_before' => 'float',
        'stock_after' => 'float',
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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
