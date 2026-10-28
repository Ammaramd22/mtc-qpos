<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariationStockPrice extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function variation()
    {
        return $this->belongsTo(\App\Variation::class);
    }
}
