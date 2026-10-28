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
        if (Schema::hasTable('pos_trade_ins')) {
            return;
        }

        Schema::create('pos_trade_ins', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('transaction_id');
            $table->unsignedInteger('sell_line_id')->nullable();
            $table->unsignedInteger('location_id');
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();

            // New product sold (line this trade-in is attached to)
            $table->unsignedInteger('new_product_id')->nullable();
            $table->unsignedInteger('new_variation_id')->nullable();
            $table->string('new_product_name')->nullable();
            $table->decimal('new_unit_price', 22, 4)->default(0);
            $table->decimal('new_quantity', 22, 4)->default(1);

            // Old / received item
            $table->unsignedInteger('old_product_id')->nullable();
            $table->unsignedInteger('old_variation_id')->nullable();
            $table->unsignedInteger('opening_stock_transaction_id')->nullable();
            $table->string('old_product_name')->nullable();
            $table->string('brand_name')->nullable();
            $table->unsignedInteger('brand_id')->nullable();
            $table->string('tyre_size')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('sub_category_id')->nullable();
            $table->string('condition')->nullable();
            $table->decimal('usage_percent', 8, 2)->nullable();
            $table->string('barcode')->nullable();
            $table->decimal('quantity', 22, 4)->default(1);
            $table->decimal('exchange_value', 22, 4)->default(0);
            $table->text('notes')->nullable();
            $table->string('reason')->nullable();
            $table->string('status')->default('completed');

            $table->timestamps();

            $table->index(['business_id', 'transaction_id']);
            $table->index('sell_line_id');
            $table->index('old_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pos_trade_ins');
    }
};
