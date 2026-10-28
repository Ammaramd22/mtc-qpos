<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

class CustomerVehicle extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    /**
     * SoftDeletes only when deleted_at exists (migration / cPanel SQL applied).
     */
    public static function bootSoftDeletes()
    {
        try {
            if (! Schema::hasTable('customer_vehicles')
                || ! Schema::hasColumn('customer_vehicles', 'deleted_at')) {
                return;
            }
        } catch (\Throwable $e) {
            return;
        }

        static::addGlobalScope(new \Illuminate\Database\Eloquent\SoftDeletingScope);

        static::deleting(function ($model) {
            if (! $model->forceDeleting) {
                $model->runSoftDelete();

                return false;
            }
        });

        static::restoring(function ($model) {
            $model->{$model->getDeletedAtColumn()} = null;
        });

        static::restored(function ($model) {
            //
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function (CustomerVehicle $vehicle) {
            try {
                if (! Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
                    return;
                }
            } catch (\Throwable $e) {
                return;
            }

            $display = trim((string) ($vehicle->registration_number ?? ''));
            $vehicle->registration_number = $display !== '' ? $display : null;
            $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $display) ?? '');
            $vehicle->registration_normalized = $norm !== '' ? $norm : null;
        });
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function jobs()
    {
        return $this->hasMany(WorkshopJob::class, 'vehicle_id');
    }

    public function displayName(): string
    {
        $parts = array_filter([
            $this->registration_number,
            $this->make,
            $this->model,
            $this->year,
        ]);

        return implode(' · ', $parts) ?: ('#'.$this->id);
    }
}
