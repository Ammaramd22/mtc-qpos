<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Nutmeg: per-line product expenses (transport, etc.) capitalized into stock/AVCO cost.
     */
    public function up(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_lines', 'product_expenses')) {
                $table->json('product_expenses')->nullable()->after('purchase_price_inc_tax');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_lines', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_lines', 'product_expenses')) {
                $table->dropColumn('product_expenses');
            }
        });
    }
};
