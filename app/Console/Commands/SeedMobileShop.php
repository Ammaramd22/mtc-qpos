<?php

namespace App\Console\Commands;

use Database\Seeders\MobileShopSeeder;
use Illuminate\Console\Command;

class SeedMobileShop extends Command
{
    protected $signature = 'pos:seed-mobile-shop';

    protected $description = 'Seed Mobile Shop demo (phones, IMEI, warranty, repairs, MyReload)';

    public function handle()
    {
        $this->info('Seeding Mobile Shop demo…');
        $this->call('db:seed', ['--class' => MobileShopSeeder::class]);

        return self::SUCCESS;
    }
}
