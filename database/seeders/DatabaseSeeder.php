<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Usage:
     *   php artisan db:seed                          → Production (datos mínimos)
     *   php artisan db:seed --class=DevelopmentSeeder → Development (datos de demo)
     */
    public function run(): void
    {
        $this->call([
            ProductionSeeder::class,
        ]);
    }
}
