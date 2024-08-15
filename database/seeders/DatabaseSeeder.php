<?php

namespace Database\Seeders;

use App\Models\Like;
use App\Models\Plan;
use App\Models\User;
use App\Models\Comment;
use App\Models\Category;
use App\Models\Secondaryimage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Database\Seeders\LocalDatabaseSeeder;
use Database\Factories\SecondarymageFactory;
use Database\Factories\SecondarymagesFactory;
use Database\Seeders\ProductionDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (App::environment('local')) {
            $this->call(LocalDatabaseSeeder::class);
        } elseif (App::environment('production')) {
            $this->call(ProductionDatabaseSeeder::class);
        }
    }
}
