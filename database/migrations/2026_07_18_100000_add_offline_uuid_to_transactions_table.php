<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Idempotency key for offline POS sales — prevents duplicate invoices on retry/sync.
     */
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('transactions', 'offline_uuid')) {
                $table->string('offline_uuid', 80)->nullable()->after('invoice_token');
                $table->unique(['business_id', 'offline_uuid'], 'transactions_business_offline_uuid_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            if (Schema::hasColumn('transactions', 'offline_uuid')) {
                $table->dropUnique('transactions_business_offline_uuid_unique');
                $table->dropColumn('offline_uuid');
            }
        });
    }
};
