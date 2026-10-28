<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mobile_reload_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('mobile_reload_transactions', 'payment_type')) {
                $table->string('payment_type', 30)->default('reload')->after('reference_no');
            }
            if (! Schema::hasColumn('mobile_reload_transactions', 'bill_provider')) {
                $table->string('bill_provider', 60)->nullable()->after('payment_type');
            }
            if (! Schema::hasColumn('mobile_reload_transactions', 'account_number')) {
                $table->string('account_number', 60)->nullable()->after('mobile_number');
            }
            if (! Schema::hasColumn('mobile_reload_transactions', 'notes')) {
                $table->text('notes')->nullable()->after('provider_response');
            }
        });
    }

    public function down()
    {
        Schema::table('mobile_reload_transactions', function (Blueprint $table) {
            $table->dropColumn(['payment_type', 'bill_provider', 'account_number', 'notes']);
        });
    }
};
