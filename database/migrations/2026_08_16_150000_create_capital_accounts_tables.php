<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Capital Accounts — partners & ledger entries (partnership investment).
     */
    public function up()
    {
        if (! Schema::hasTable('capital_partners')) {
            Schema::create('capital_partners', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id');
                $table->string('name');
                $table->string('short_name', 50)->nullable();
                $table->decimal('investment_amount', 22, 4)->default(0);
                $table->decimal('profit_percent', 8, 4)->default(0);
                $table->boolean('is_active')->default(1);
                $table->text('notes')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index('business_id');
            });
        }

        if (! Schema::hasTable('capital_entries')) {
            Schema::create('capital_entries', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('capital_partner_id')->nullable();
                // investment|opening_balance|expense|petty_cash|purchase|withdrawal|profit|adjustment
                $table->string('type', 40);
                // bank|cash_drawer
                $table->string('source', 40)->default('cash_drawer');
                $table->decimal('amount', 22, 4);
                $table->date('entry_date');
                $table->string('reference_no')->nullable();
                $table->text('note')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['business_id', 'entry_date']);
                $table->index('capital_partner_id');
                $table->index('type');
                $table->index('source');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('capital_entries');
        Schema::dropIfExists('capital_partners');
    }
};
