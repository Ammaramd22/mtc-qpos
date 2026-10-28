<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mobile_imeis', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id');
            $table->unsignedInteger('location_id');
            $table->string('imei', 20);
            $table->enum('status', ['available', 'sold', 'in_repair', 'returned'])->default('available');
            $table->unsignedInteger('sell_transaction_id')->nullable();
            $table->unsignedInteger('sell_line_id')->nullable();
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('warranty_id')->nullable();
            $table->dateTime('sold_at')->nullable();
            $table->dateTime('warranty_starts_at')->nullable();
            $table->dateTime('warranty_ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'imei']);
            $table->index(['business_id', 'product_id', 'status']);
            $table->index(['business_id', 'location_id', 'status']);
        });

        Schema::create('mobile_repairs', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('mobile_imei_id');
            $table->string('reference_no', 50);
            $table->enum('status', ['received', 'in_progress', 'completed', 'cancelled'])->default('received');
            $table->text('issue_description');
            $table->text('repair_notes')->nullable();
            $table->decimal('cost', 22, 4)->default(0);
            $table->decimal('charge', 22, 4)->default(0);
            $table->boolean('warranty_covered')->default(false);
            $table->unsignedInteger('contact_id')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->dateTime('received_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'mobile_imei_id']);
        });

        Schema::create('mobile_reload_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('business_id');
            $table->string('reference_no', 50);
            $table->string('mobile_number', 20);
            $table->string('operator', 30);
            $table->decimal('amount', 22, 4);
            $table->enum('status', ['pending', 'success', 'failed'])->default('pending');
            $table->string('provider_ref', 100)->nullable();
            $table->text('provider_response')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_reload_transactions');
        Schema::dropIfExists('mobile_repairs');
        Schema::dropIfExists('mobile_imeis');
    }
};
