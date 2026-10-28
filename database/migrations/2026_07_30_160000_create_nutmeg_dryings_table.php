<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nutmeg_dryings')) {
            Schema::create('nutmeg_dryings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('location_id');
                $table->unsignedInteger('product_id');
                $table->unsignedInteger('variation_id');
                $table->decimal('qty_before', 22, 4);
                $table->decimal('qty_after', 22, 4);
                $table->decimal('qty_wastage', 22, 4);
                $table->decimal('unit_cost_before', 22, 4)->default(0);
                $table->decimal('unit_cost_after', 22, 4)->default(0);
                $table->decimal('stock_before', 22, 4)->default(0);
                $table->decimal('stock_after', 22, 4)->default(0);
                $table->unsignedInteger('stock_adjustment_id')->nullable();
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by');
                $table->timestamps();

                $table->index(['business_id', 'variation_id']);
                $table->index(['business_id', 'location_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nutmeg_dryings');
    }
};
