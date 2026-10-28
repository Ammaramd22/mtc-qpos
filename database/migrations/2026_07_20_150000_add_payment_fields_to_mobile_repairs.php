<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mobile_repairs', function (Blueprint $table) {
            $table->enum('payment_status', ['unpaid', 'paid', 'waived'])->default('waived')->after('warranty_covered');
            $table->unsignedInteger('sell_transaction_id')->nullable()->after('payment_status');
        });
    }

    public function down()
    {
        Schema::table('mobile_repairs', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'sell_transaction_id']);
        });
    }
};
