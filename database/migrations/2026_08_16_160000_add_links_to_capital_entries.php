<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (Schema::hasTable('capital_entries')) {
            Schema::table('capital_entries', function (Blueprint $table) {
                if (! Schema::hasColumn('capital_entries', 'account_transaction_id')) {
                    $table->unsignedInteger('account_transaction_id')->nullable()->after('created_by');
                }
                if (! Schema::hasColumn('capital_entries', 'expense_id')) {
                    $table->unsignedInteger('expense_id')->nullable()->after('account_transaction_id');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('capital_entries')) {
            Schema::table('capital_entries', function (Blueprint $table) {
                if (Schema::hasColumn('capital_entries', 'expense_id')) {
                    $table->dropColumn('expense_id');
                }
                if (Schema::hasColumn('capital_entries', 'account_transaction_id')) {
                    $table->dropColumn('account_transaction_id');
                }
            });
        }
    }
};
