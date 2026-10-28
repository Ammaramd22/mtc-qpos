<?php

namespace App\Console\Commands;

use Database\Seeders\TilesCeramicsSeeder;
use Illuminate\Console\Command;

class SeedTilesCeramics extends Command
{
    protected $signature = 'pos:seed-tiles-ceramics';

    protected $description = 'Create Tiles & Ceramics demo business with products, photos, Classic A4 + Classic 80mm invoices';

    public function handle()
    {
        $this->info('Seeding Tiles & Ceramics demo…');
        $this->call('db:seed', ['--class' => TilesCeramicsSeeder::class]);

        return self::SUCCESS;
    }
}
