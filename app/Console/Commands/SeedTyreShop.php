<?php

namespace App\Console\Commands;

use Database\Seeders\TyreShopSeeder;
use Illuminate\Console\Command;

class SeedTyreShop extends Command
{
    protected $signature = 'pos:seed-tyre-shop';

    protected $description = 'Create demo Tyre Shop business with tyres, tubes, accessories + images';

    public function handle()
    {
        $this->info('Seeding Tyre Shop demo…');
        $this->call('db:seed', ['--class' => TyreShopSeeder::class]);

        return self::SUCCESS;
    }
}
