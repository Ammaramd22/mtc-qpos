<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Shop Mode (per business) plus industry product columns and workshop tables.
     * Existing products / sales / customers are never dropped.
     */
    public function up(): void
    {
        if (Schema::hasTable('business') && ! Schema::hasColumn('business', 'shop_mode')) {
            Schema::table('business', function (Blueprint $table) {
                $table->string('shop_mode', 50)->nullable()->after('enabled_modules');
            });
        }

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                if (! Schema::hasColumn('products', 'part_number')) {
                    $table->string('part_number', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'oem_number')) {
                    $table->string('oem_number', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'compatible_make')) {
                    $table->string('compatible_make', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'compatible_model')) {
                    $table->string('compatible_model', 80)->nullable()->index();
                }
                if (! Schema::hasColumn('products', 'year_from')) {
                    $table->unsignedSmallInteger('year_from')->nullable();
                }
                if (! Schema::hasColumn('products', 'year_to')) {
                    $table->unsignedSmallInteger('year_to')->nullable();
                }
                if (! Schema::hasColumn('products', 'engine_type')) {
                    $table->string('engine_type', 80)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_size')) {
                    $table->string('hw_size', 50)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_length')) {
                    $table->string('hw_length', 50)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_width')) {
                    $table->string('hw_width', 50)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_thickness')) {
                    $table->string('hw_thickness', 50)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_material')) {
                    $table->string('hw_material', 80)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_grade')) {
                    $table->string('hw_grade', 50)->nullable();
                }
                if (! Schema::hasColumn('products', 'hw_colour')) {
                    $table->string('hw_colour', 50)->nullable();
                }
            });
        }

        if (! Schema::hasTable('customer_vehicles')) {
            Schema::create('customer_vehicles', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('contact_id');
                $table->string('registration_number', 40)->nullable()->index();
                $table->string('make', 80)->nullable();
                $table->string('model', 80)->nullable();
                $table->unsignedSmallInteger('year')->nullable();
                $table->string('vehicle_type', 40)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'contact_id']);
            });
        }

        if (! Schema::hasTable('workshop_jobs')) {
            Schema::create('workshop_jobs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('business_id');
                $table->unsignedInteger('contact_id')->nullable();
                $table->unsignedInteger('vehicle_id')->nullable();
                $table->unsignedInteger('location_id')->nullable();
                $table->string('reference_no', 30)->nullable()->index();
                $table->string('status', 30)->default('pending')->index();
                $table->text('description')->nullable();
                $table->text('notes')->nullable();
                $table->decimal('estimated_total', 22, 4)->default(0);
                $table->string('payment_status', 20)->default('unpaid')->index();
                $table->unsignedInteger('sell_transaction_id')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('delivered_at')->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['business_id', 'status']);
            });
        }

        if (! Schema::hasTable('workshop_job_items')) {
            Schema::create('workshop_job_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('workshop_job_id');
                $table->unsignedInteger('product_id')->nullable();
                $table->unsignedInteger('variation_id')->nullable();
                $table->string('item_type', 20)->default('part');
                $table->string('name', 191)->nullable();
                $table->decimal('quantity', 22, 4)->default(1);
                $table->decimal('unit_price', 22, 4)->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('workshop_job_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_job_items');
        Schema::dropIfExists('workshop_jobs');
        Schema::dropIfExists('customer_vehicles');

        if (Schema::hasTable('products')) {
            Schema::table('products', function (Blueprint $table) {
                foreach ([
                    'part_number', 'oem_number', 'compatible_make', 'compatible_model',
                    'year_from', 'year_to', 'engine_type',
                    'hw_size', 'hw_length', 'hw_width', 'hw_thickness',
                    'hw_material', 'hw_grade', 'hw_colour',
                ] as $col) {
                    if (Schema::hasColumn('products', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('business') && Schema::hasColumn('business', 'shop_mode')) {
            Schema::table('business', function (Blueprint $table) {
                $table->dropColumn('shop_mode');
            });
        }
    }
};
