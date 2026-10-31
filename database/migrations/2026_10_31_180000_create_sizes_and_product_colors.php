<?php

use App\Utils\ProductSizeColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sizes catalog, product-size links, and per-product colours.
     * Existing products.size / products.color values are copied in.
     * Those text columns are kept so current screens continue to work.
     */
    public function up(): void
    {
        if (! Schema::hasTable('sizes')) {
            Schema::create('sizes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned();
                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->string('name', 150);
                $table->unsignedTinyInteger('is_catalog')->default(0);
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'name'], 'sizes_business_name_unique');
            });
        }

        if (! Schema::hasTable('product_size')) {
            Schema::create('product_size', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->unsigned();
                $table->integer('size_id')->unsigned();
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'size_id'], 'product_size_unique');
                $table->index('size_id', 'product_size_size_id_index');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('size_id')->references('id')->on('sizes')->onDelete('restrict');
            });
        }

        if (! Schema::hasTable('product_colors')) {
            Schema::create('product_colors', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned();
                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->integer('product_id')->unsigned();
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->string('name', 150);
                $table->string('name_key', 150);
                $table->timestamps();
                $table->unique(['product_id', 'name_key'], 'product_colors_unique');
                $table->index(['business_id', 'name_key'], 'product_colors_business_name_index');
            });
        }

        if (! Schema::hasTable('transaction_sell_lines')) {
            // POS line choice is optional display data. Skip if the table is absent.
        } else {
            if (! Schema::hasColumn('transaction_sell_lines', 'line_size')) {
                Schema::table('transaction_sell_lines', function (Blueprint $table) {
                    $table->string('line_size', 191)->nullable();
                });
            }
            if (! Schema::hasColumn('transaction_sell_lines', 'line_color')) {
                Schema::table('transaction_sell_lines', function (Blueprint $table) {
                    $table->string('line_color', 191)->nullable();
                });
            }
        }

        $this->widenTextColumns();
        $this->backfillExistingProducts();
    }

    public function down(): void
    {
        if (Schema::hasColumn('transaction_sell_lines', 'line_color')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->dropColumn('line_color');
            });
        }
        if (Schema::hasColumn('transaction_sell_lines', 'line_size')) {
            Schema::table('transaction_sell_lines', function (Blueprint $table) {
                $table->dropColumn('line_size');
            });
        }

        Schema::dropIfExists('product_colors');
        Schema::dropIfExists('product_size');
        Schema::dropIfExists('sizes');
    }

    /**
     * Multiple sizes and colours no longer fit in a 191-character string.
     */
    private function widenTextColumns(): void
    {
        if (Schema::hasColumn('products', 'size')) {
            DB::statement('ALTER TABLE `products` MODIFY `size` TEXT NULL');
        }
        if (Schema::hasColumn('products', 'color')) {
            DB::statement('ALTER TABLE `products` MODIFY `color` TEXT NULL');
        }
    }

    /**
     * Copy existing single size/colour text into the new tables.
     * Products with a blank size or colour are left alone.
     */
    private function backfillExistingProducts(): void
    {
        if (! ProductSizeColor::tablesReady()) {
            return;
        }

        $hasSize = Schema::hasColumn('products', 'size');
        $hasColor = Schema::hasColumn('products', 'color');
        if (! $hasSize && ! $hasColor) {
            return;
        }

        $columns = ['id', 'business_id', 'created_by'];
        if ($hasSize) {
            $columns[] = 'size';
        }
        if ($hasColor) {
            $columns[] = 'color';
        }

        DB::table('products')->select($columns)->orderBy('id')->chunkById(200, function ($rows) use ($hasSize, $hasColor) {
            foreach ($rows as $row) {
                $already = DB::table('product_size')->where('product_id', $row->id)->exists()
                    || DB::table('product_colors')->where('product_id', $row->id)->exists();
                if ($already) {
                    continue;
                }

                $sizeNames = $hasSize ? ProductSizeColor::splitList($row->size ?? null) : [];
                $colorNames = $hasColor ? ProductSizeColor::splitList($row->color ?? null) : [];
                if (empty($sizeNames) && empty($colorNames)) {
                    continue;
                }

                $userId = ! empty($row->created_by) ? (int) $row->created_by : null;
                $now = now();

                foreach ($sizeNames as $position => $name) {
                    $existing = DB::table('sizes')
                        ->where('business_id', $row->business_id)
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                        ->first();
                    if ($existing) {
                        $sizeId = $existing->id;
                    } else {
                        $sizeId = DB::table('sizes')->insertGetId([
                            'business_id' => $row->business_id,
                            'name' => $name,
                            'created_by' => $userId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('product_size')->insert([
                        'product_id' => $row->id,
                        'size_id' => $sizeId,
                        'position' => $position,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                foreach ($colorNames as $name) {
                    DB::table('product_colors')->insert([
                        'business_id' => $row->business_id,
                        'product_id' => $row->id,
                        'name' => $name,
                        'name_key' => mb_strtolower($name),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }

                $updates = [];
                if ($hasSize) {
                    $updates['size'] = ProductSizeColor::joinList($sizeNames);
                }
                if ($hasColor) {
                    $updates['color'] = ProductSizeColor::joinList($colorNames);
                }
                if (! empty($updates)) {
                    DB::table('products')->where('id', $row->id)->update($updates);
                }
            }
        }, 'id');
    }
};
