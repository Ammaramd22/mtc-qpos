<?php

namespace App\Console\Commands;

use Database\Seeders\FishShopSeeder;
use Illuminate\Console\Command;

class SeedFishShop extends Command
{
    protected $signature = 'pos:seed-fish-shop';

    protected $description = 'Create demo Fish Shop business with fish products + images for Touch POS';

    public function handle()
    {
        $this->info('Seeding Fish Shop demo…');
        $this->call('db:seed', ['--class' => FishShopSeeder::class]);

        return self::SUCCESS;
    }
}
