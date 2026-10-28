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
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (! Schema::hasColumn('transaction_payments', 'cheque_name')) {
                $table->string('cheque_name')->nullable()->after('cheque_number')
                    ->comment('Name written on the cheque');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            if (Schema::hasColumn('transaction_payments', 'cheque_name')) {
                $table->dropColumn('cheque_name');
            }
        });
    }
};
