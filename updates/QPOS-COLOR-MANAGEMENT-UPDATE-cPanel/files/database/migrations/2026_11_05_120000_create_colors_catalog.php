<?php

use App\Utils\ProductSizeColor;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business-wide colour catalog (same pattern as sizes).
     * Existing product_colors tags and products.color text are copied in.
     * products.color stays as a comma-separated display copy.
     */
    public function up(): void
    {
        if (! Schema::hasTable('colors')) {
            Schema::create('colors', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('business_id')->unsigned();
                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->string('name', 150);
                $table->unsignedTinyInteger('is_catalog')->default(0);
                $table->integer('created_by')->unsigned()->nullable();
                $table->timestamps();
                $table->unique(['business_id', 'name'], 'colors_business_name_unique');
            });
        }

        if (! Schema::hasTable('product_color')) {
            Schema::create('product_color', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->unsigned();
                $table->integer('color_id')->unsigned();
                $table->unsignedSmallInteger('position')->default(0);
                $table->timestamps();
                $table->unique(['product_id', 'color_id'], 'product_color_unique');
                $table->index('color_id', 'product_color_color_id_index');
                $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
                $table->foreign('color_id')->references('id')->on('colors')->onDelete('restrict');
            });
        }

        $this->backfillFromExisting();
    }

    public function down(): void
    {
        Schema::dropIfExists('product_color');
        Schema::dropIfExists('colors');
    }

    /**
     * Map existing free-text colours into the catalog without duplicates.
     * Migrated rows stay is_catalog = 0 (same as size backfill).
     */
    private function backfillFromExisting(): void
    {
        if (! Schema::hasTable('colors') || ! Schema::hasTable('product_color')) {
            return;
        }

        $hasLegacyTags = Schema::hasTable('product_colors');
        $hasColorColumn = Schema::hasColumn('products', 'color');

        if ($hasLegacyTags) {
            DB::table('product_colors')->orderBy('id')->chunkById(200, function ($rows) {
                foreach ($rows as $row) {
                    $this->linkProductColor(
                        (int) $row->business_id,
                        (int) $row->product_id,
                        (string) ($row->name ?? ''),
                        null
                    );
                }
            }, 'id');
        }

        if (! $hasColorColumn) {
            return;
        }

        DB::table('products')->select(['id', 'business_id', 'created_by', 'color'])->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                if (DB::table('product_color')->where('product_id', $row->id)->exists()) {
                    continue;
                }
                $names = ProductSizeColor::splitList($row->color ?? null);
                if ($names === []) {
                    continue;
                }
                $userId = ! empty($row->created_by) ? (int) $row->created_by : null;
                foreach ($names as $position => $name) {
                    $this->linkProductColor((int) $row->business_id, (int) $row->id, $name, $userId, $position);
                }
                DB::table('products')->where('id', $row->id)->update([
                    'color' => ProductSizeColor::joinList($names),
                ]);
            }
        }, 'id');
    }

    private function linkProductColor(int $businessId, int $productId, string $name, ?int $userId, ?int $position = null): void
    {
        $name = ProductSizeColor::cleanName($name);
        if ($name === null || $businessId < 1 || $productId < 1) {
            return;
        }

        $existing = DB::table('colors')
            ->where('business_id', $businessId)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        $now = now();
        if ($existing) {
            $colorId = (int) $existing->id;
        } else {
            $colorId = (int) DB::table('colors')->insertGetId([
                'business_id' => $businessId,
                'name' => $name,
                'is_catalog' => 0,
                'created_by' => $userId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('product_color')->where('product_id', $productId)->where('color_id', $colorId)->exists()) {
            return;
        }

        if ($position === null) {
            $position = (int) DB::table('product_color')->where('product_id', $productId)->count();
        }

        DB::table('product_color')->insert([
            'product_id' => $productId,
            'color_id' => $colorId,
            'position' => $position,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
};
