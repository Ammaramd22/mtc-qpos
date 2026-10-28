<?php

namespace App\Console\Commands;

use Database\Seeders\GroceryShopSeeder;
use Illuminate\Console\Command;

class SeedGroceryShop extends Command
{
    protected $signature = 'pos:seed-grocery-shop';

    protected $description = 'Create demo Grocery Shop business with products + images for Touch POS';

    public function handle()
    {
        $this->info('Seeding Grocery Shop demo…');
        $this->call('db:seed', ['--class' => GroceryShopSeeder::class]);

        return self::SUCCESS;
    }
}
