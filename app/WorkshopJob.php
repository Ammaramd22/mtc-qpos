<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkshopJob extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'estimated_total' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(CustomerVehicle::class, 'vehicle_id');
    }

    public function items()
    {
        return $this->hasMany(WorkshopJobItem::class);
    }

    public function location()
    {
        return $this->belongsTo(BusinessLocation::class, 'location_id');
    }

    public function sellTransaction()
    {
        return $this->belongsTo(Transaction::class, 'sell_transaction_id');
    }

    public function recalcTotal(): float
    {
        $total = (float) $this->items()->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as t')->value('t');
        $this->estimated_total = $total;
        $this->save();

        return $total;
    }
}
