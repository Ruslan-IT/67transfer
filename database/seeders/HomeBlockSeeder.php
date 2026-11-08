<?php

namespace Database\Seeders;

use App\Support\HomeBlocks;
use Illuminate\Database\Seeder;

class HomeBlockSeeder extends Seeder
{
    public function run(): void
    {
        app(HomeBlocks::class)->ensureSystemBlocks();
    }
}
