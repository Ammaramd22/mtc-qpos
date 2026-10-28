<?php

namespace App\Console\Commands;

use Database\Seeders\ShopModeDummyDataSeeder;
use Illuminate\Console\Command;

class SeedShopModes extends Command
{
    protected $signature = 'pos:seed-shop-modes
                            {mode? : Shop mode key (normal_retail, tyre_shop, mobile_shop, vehicle_modification, vehicle_spare_parts, hardware_shop) or omit for all}';

    protected $description = 'Seed 10 dummy products (+ customers / vehicles / jobs where relevant) for each Shop Mode demo business';

    public function handle(): int
    {
        $mode = $this->argument('mode');
        $this->info($mode
            ? "Seeding dummy data for shop mode: {$mode}"
            : 'Seeding dummy data for ALL shop modes…');

        $seeder = new ShopModeDummyDataSeeder();
        $seeder->setCommand($this);
        $seeder->run($mode);

        return self::SUCCESS;
    }
}
