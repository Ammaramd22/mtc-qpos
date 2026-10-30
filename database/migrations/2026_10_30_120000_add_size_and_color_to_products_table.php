<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add optional product-level size and color. Existing rows stay NULL.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'size')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('size', 191)->nullable()->after('name');
            });
        }

        if (! Schema::hasColumn('products', 'color')) {
            Schema::table('products', function (Blueprint $table) {
                $after = Schema::hasColumn('products', 'size') ? 'size' : 'name';
                $table->string('color', 191)->nullable()->after($after);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'color')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('color');
            });
        }

        if (Schema::hasColumn('products', 'size')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('size');
            });
        }
    }
};
