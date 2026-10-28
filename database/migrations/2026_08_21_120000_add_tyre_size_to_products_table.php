<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'tyre_size')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('tyre_size', 50)->nullable()->after('name')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'tyre_size')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('tyre_size');
            });
        }
    }
};
