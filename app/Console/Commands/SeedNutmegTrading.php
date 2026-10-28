<?php

namespace App\Console\Commands;

use Database\Seeders\NutmegTradingSeeder;
use Illuminate\Console\Command;

class SeedNutmegTrading extends Command
{
    protected $signature = 'pos:seed-nutmeg';

    protected $description = 'Create Nutmeg Buying & Selling Centre demo (POP + AVCO trading)';

    public function handle()
    {
        $this->info('Seeding Nutmeg Buying & Selling Centre…');
        $this->call('db:seed', ['--class' => NutmegTradingSeeder::class]);

        return self::SUCCESS;
    }
}
