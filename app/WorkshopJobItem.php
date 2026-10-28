<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class WorkshopJobItem extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
    ];

    public function job()
    {
        return $this->belongsTo(WorkshopJob::class, 'workshop_job_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function lineTotal(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
