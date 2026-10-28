<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    protected $appends = ['image_url'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'sub_unit_ids' => 'array',
    ];

    /**
     * Append tyre size to a product name when present.
     * Skips appending when the name already includes the size (auto-named tyres).
     *
     * @param  string|null  $name
     * @param  string|null  $tyreSize
     * @return string
     */
    public static function formatNameWithTyreSize($name, $tyreSize = null)
    {
        $name = trim((string) $name);
        $tyreSize = trim((string) $tyreSize);
        if ($tyreSize === '') {
            return $name;
        }
        if ($name === '') {
            return $tyreSize;
        }
        // Avoid "215/55/17 Ceat Jambo - 215/55/17"
        if (mb_stripos($name, $tyreSize) !== false) {
            return $name;
        }

        return $name.' - '.$tyreSize;
    }

    /**
     * Build tyre Item Name: "[Tyre Size] [Brand] [Note]" (space-separated, empty parts skipped).
     *
     * @param  string|null  $tyreSize
     * @param  string|null  $brandName
     * @param  string|null  $note
     * @return string|null  null when nothing to compose (caller should keep manual name)
     */
    public static function buildTyreItemName($tyreSize = null, $brandName = null, $note = null)
    {
        $parts = [];
        foreach ([$tyreSize, $brandName, $note] as $part) {
            $part = trim((string) ($part ?? ''));
            if ($part !== '') {
                $parts[] = $part;
            }
        }

        if (empty($parts)) {
            return null;
        }

        return implode(' ', $parts);
    }

    /**
     * Whether tyre auto-naming should run (tyre size and/or note provided).
     * Brand alone does not force auto-name (keeps Engine Oil + Brand Shell intact).
     *
     * @param  string|null  $tyreSize
     * @param  string|null  $note
     * @return bool
     */
    public static function shouldAutoBuildTyreName($tyreSize = null, $note = null)
    {
        return trim((string) ($tyreSize ?? '')) !== ''
            || trim((string) ($note ?? '')) !== '';
    }

    /**
     * Product name with tyre size when set (non-tyre products unchanged).
     *
     * @return string
     */
    public function getNameWithTyreSizeAttribute()
    {
        return self::formatNameWithTyreSize($this->name, $this->tyre_size);
    }

    /**
     * Get the products image.
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (! empty($this->image)) {
            $image_url = asset('/uploads/img/'.rawurlencode($this->image));
        } else {
            $image_url = asset('/img/default.png');
        }

        return $image_url;
    }

    /**
     * Get the products image path.
     *
     * @return string
     */
    public function getImagePathAttribute()
    {
        if (! empty($this->image)) {
            $image_path = public_path('uploads').'/'.config('constants.product_img_path').'/'.$this->image;
        } else {
            $image_path = null;
        }

        return $image_path;
    }

    public function product_variations()
    {
        return $this->hasMany(\App\ProductVariation::class);
    }

    /**
     * Get the brand associated with the product.
     */
    public function brand()
    {
        return $this->belongsTo(\App\Brands::class);
    }

    /**
     * Default / preferred supplier for this product.
     */
    public function supplier()
    {
        return $this->belongsTo(\App\Contact::class, 'supplier_id');
    }

    /**
     * Get the unit associated with the product.
     */
    public function unit()
    {
        return $this->belongsTo(\App\Unit::class);
    }

    /**
     * Get the unit associated with the product.
     */
    public function second_unit()
    {
        return $this->belongsTo(\App\Unit::class, 'secondary_unit_id');
    }

    /**
     * Get category associated with the product.
     */
    public function category()
    {
        return $this->belongsTo(\App\Category::class);
    }

    /**
     * Get sub-category associated with the product.
     */
    public function sub_category()
    {
        return $this->belongsTo(\App\Category::class, 'sub_category_id', 'id');
    }

    /**
     * Get the tax associated with the product.
     */
    public function product_tax()
    {
        return $this->belongsTo(\App\TaxRate::class, 'tax', 'id');
    }

    /**
     * Get the variations associated with the product.
     */
    public function variations()
    {
        return $this->hasMany(\App\Variation::class);
    }

    /**
     * If product type is modifier get products associated with it.
     */
    public function modifier_products()
    {
        return $this->belongsToMany(\App\Product::class, 'res_product_modifier_sets', 'modifier_set_id', 'product_id');
    }

    /**
     * If product type is modifier get products associated with it.
     */
    public function modifier_sets()
    {
        return $this->belongsToMany(\App\Product::class, 'res_product_modifier_sets', 'product_id', 'modifier_set_id');
    }

    /**
     * Get the purchases associated with the product.
     */
    public function purchase_lines()
    {
        return $this->hasMany(\App\PurchaseLine::class);
    }

    /**
     * Scope a query to only include active products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('products.is_inactive', 0);
    }

    /**
     * Scope a query to only include inactive products.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('products.is_inactive', 1);
    }

    /**
     * Scope a query to only include products for sales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductForSales($query)
    {
        return $query->where('not_for_selling', 0);
    }

    /**
     * Scope a query to only include products not for sales.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProductNotForSales($query)
    {
        return $query->where('not_for_selling', 1);
    }

    public function product_locations()
    {
        return $this->belongsToMany(\App\BusinessLocation::class, 'product_locations', 'product_id', 'location_id');
    }

    /**
     * Scope a query to only include products available for a location.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForLocation($query, $location_id)
    {
        return $query->where(function ($q) use ($location_id) {
            $q->whereHas('product_locations', function ($query) use ($location_id) {
                $query->where('product_locations.location_id', $location_id);
            });
        });
    }

    /**
     * Get warranty associated with the product.
     */
    public function warranty()
    {
        return $this->belongsTo(\App\Warranty::class);
    }

    public function media()
    {
        return $this->morphMany(\App\Media::class, 'model');
    }

    public function rack_details()
    {
        return $this->hasMany(\App\ProductRack::class);
    }
}
