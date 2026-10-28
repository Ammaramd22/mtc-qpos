<?php

namespace App\Console\Commands;

use Database\Seeders\CarpetShopSeeder;
use Illuminate\Console\Command;

class SeedCarpetShop extends Command
{
    protected $signature = 'pos:seed-carpet-shop';

    protected $description = 'Create demo Carpet Shop business with Foot/Yard products + images for Touch POS';

    public function handle()
    {
        $this->info('Seeding Carpet Shop demo…');
        $this->call('db:seed', ['--class' => CarpetShopSeeder::class]);

        return self::SUCCESS;
    }
}
