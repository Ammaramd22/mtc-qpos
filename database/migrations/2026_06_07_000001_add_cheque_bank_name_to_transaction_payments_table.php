<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->string('cheque_bank_name')->nullable()->after('cheque_number')->comment('Bank name for cheque payments');
        });
    }

    public function down()
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropColumn('cheque_bank_name');
        });
    }
};
