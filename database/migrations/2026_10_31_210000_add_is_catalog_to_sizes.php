<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mark sizes that were saved from Size Management.
     * Existing rows stay at 0, including old product size text that was copied in.
     * No size names, product links, stock, or sales rows are changed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sizes') || Schema::hasColumn('sizes', 'is_catalog')) {
            return;
        }

        Schema::table('sizes', function (Blueprint $table) {
            $table->unsignedTinyInteger('is_catalog')->default(0)->after('name');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sizes') || ! Schema::hasColumn('sizes', 'is_catalog')) {
            return;
        }

        Schema::table('sizes', function (Blueprint $table) {
            $table->dropColumn('is_catalog');
        });
    }
};
