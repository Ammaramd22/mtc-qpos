<?php

namespace App\Console\Commands;

use Database\Seeders\SupermarketSeeder;
use Illuminate\Console\Command;

class SeedSupermarket extends Command
{
    protected $signature = 'pos:seed-supermarket';

    protected $description = 'Create demo Supermarket business with products + images for Touch POS';

    public function handle()
    {
        $this->info('Seeding Supermarket demo…');
        $this->call('db:seed', ['--class' => SupermarketSeeder::class]);

        return self::SUCCESS;
    }
}
