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
        if (! Schema::hasColumn('products', 'tyre_note')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('tyre_note', 100)->nullable()->after('tyre_size');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'tyre_note')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('tyre_note');
            });
        }
    }
};
