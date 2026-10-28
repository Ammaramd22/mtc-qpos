<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Normalize registration for flexible search while preserving display value.
 * Soft deletes protect vehicles that have job / sale history.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_vehicles')) {
            return;
        }

        Schema::table('customer_vehicles', function (Blueprint $table) {
            if (! Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
                $table->string('registration_normalized', 40)->nullable()->after('registration_number');
                $table->index(['business_id', 'registration_normalized'], 'cv_biz_reg_norm_idx');
            }
            if (! Schema::hasColumn('customer_vehicles', 'deleted_at')) {
                $table->softDeletes();
            }
        });

        // Backfill normalized values from existing display registrations
        if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
            $rows = DB::table('customer_vehicles')
                ->whereNotNull('registration_number')
                ->where('registration_number', '!=', '')
                ->where(function ($q) {
                    $q->whereNull('registration_normalized')
                        ->orWhere('registration_normalized', '');
                })
                ->select('id', 'registration_number')
                ->get();

            foreach ($rows as $row) {
                $norm = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $row->registration_number) ?? '');
                if ($norm === '') {
                    continue;
                }
                DB::table('customer_vehicles')
                    ->where('id', $row->id)
                    ->update(['registration_normalized' => $norm]);
            }
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('customer_vehicles')) {
            return;
        }

        Schema::table('customer_vehicles', function (Blueprint $table) {
            if (Schema::hasColumn('customer_vehicles', 'registration_normalized')) {
                $table->dropIndex('cv_biz_reg_norm_idx');
                $table->dropColumn('registration_normalized');
            }
            if (Schema::hasColumn('customer_vehicles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
