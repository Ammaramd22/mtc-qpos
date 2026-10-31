<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $guarded = ['id'];

    protected $table = 'sizes';

    /**
     * Products that use this size.
     */
    public function products()
    {
        return $this->belongsToMany(\App\Product::class, 'product_size', 'size_id', 'product_id')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * Sizes saved in Size Management for this business.
     * Sizes already assigned to the product are included so an edit can keep them.
     * Old product size text that was copied into this table is not offered as a new choice.
     *
     * @param  int  $business_id
     * @param  array<int, mixed>  $includeIds
     * @return \Illuminate\Support\Collection
     */
    public static function forDropdown($business_id, array $includeIds = [])
    {
        $includeIds = array_values(array_unique(array_filter(array_map('intval', $includeIds))));

        try {
            return static::where('business_id', $business_id)
                ->where(function ($q) use ($includeIds) {
                    $q->where('is_catalog', 1);
                    if (! empty($includeIds)) {
                        $q->orWhereIn('id', $includeIds);
                    }
                })
                ->orderBy('name', 'asc')
                ->pluck('name', 'id');
        } catch (\Throwable $e) {
            return static::where('business_id', $business_id)
                ->orderBy('name', 'asc')
                ->pluck('name', 'id');
        }
    }

    /**
     * Query for the Sizes page: only sizes created there, for this business.
     *
     * @param  int  $business_id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function catalogQuery($business_id)
    {
        return static::where('business_id', $business_id)->where('is_catalog', 1);
    }
}
