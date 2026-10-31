<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductColor extends Model
{
    protected $guarded = ['id'];

    protected $table = 'product_colors';

    public function product()
    {
        return $this->belongsTo(\App\Product::class);
    }
}
