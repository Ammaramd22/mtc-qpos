<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->date('cheque_date')->nullable()->after('cheque_number');
            $table->enum('cheque_status', ['pending', 'cleared', 'returned'])->default('pending')->after('cheque_date');
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropColumn(['cheque_date', 'cheque_status']);
        });
    }
};
