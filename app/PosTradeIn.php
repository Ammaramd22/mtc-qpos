<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PosTradeIn extends Model
{
    protected $table = 'pos_trade_ins';

    protected $guarded = ['id'];

    protected $casts = [
        'exchange_value' => 'float',
        'usage_percent' => 'float',
        'quantity' => 'float',
        'new_unit_price' => 'float',
        'new_quantity' => 'float',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
    }

    public function sellLine()
    {
        return $this->belongsTo(TransactionSellLine::class, 'sell_line_id');
    }

    public function oldProduct()
    {
        return $this->belongsTo(Product::class, 'old_product_id');
    }

    public function newProduct()
    {
        return $this->belongsTo(Product::class, 'new_product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brands::class, 'brand_id');
    }

    /**
     * Human-readable label for cart / invoice.
     */
    public function displayLabel(): string
    {
        $parts = array_filter([
            $this->brand_name,
            $this->tyre_size,
            $this->condition,
            $this->old_product_name,
        ]);

        $label = trim(implode(' ', array_unique($parts)));
        if ($label === '') {
            $label = $this->old_product_name ?: 'Trade-In';
        }

        if ($this->usage_percent !== null && $this->usage_percent !== '') {
            $label .= ' · '.$this->usage_percent.'% used';
        }

        return $label;
    }
}
