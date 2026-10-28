<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('mobile_repairs', function (Blueprint $table) {
            if (! Schema::hasColumn('mobile_repairs', 'phone_check')) {
                $table->json('phone_check')->nullable()->after('issue_description');
            }
        });
    }

    public function down()
    {
        Schema::table('mobile_repairs', function (Blueprint $table) {
            if (Schema::hasColumn('mobile_repairs', 'phone_check')) {
                $table->dropColumn('phone_check');
            }
        });
    }
};
