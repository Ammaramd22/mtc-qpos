<?php

namespace App\Console\Commands;

use Database\Seeders\PackagingShopSeeder;
use Illuminate\Console\Command;

class SeedPackagingShop extends Command
{
    protected $signature = 'pos:seed-packaging-shop';

    protected $description = 'Create demo Packaging Shop (food packs, tissue, pizza boxes, tooth sticks, picks)';

    public function handle()
    {
        $this->info('Seeding Packaging Shop demo…');
        $this->call('db:seed', ['--class' => PackagingShopSeeder::class]);

        return self::SUCCESS;
    }
}
