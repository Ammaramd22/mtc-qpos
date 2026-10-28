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
                if (! Schema::hasColumn('capital_entries', 'expense_category_id')) {
                    $table->unsignedInteger('expense_category_id')->nullable()->after('source');
                }
                if (! Schema::hasColumn('capital_entries', 'asset_name')) {
                    $table->string('asset_name')->nullable()->after('reference_no');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('capital_entries')) {
            Schema::table('capital_entries', function (Blueprint $table) {
                if (Schema::hasColumn('capital_entries', 'asset_name')) {
                    $table->dropColumn('asset_name');
                }
                if (Schema::hasColumn('capital_entries', 'expense_category_id')) {
                    $table->dropColumn('expense_category_id');
                }
            });
        }
    }
};
