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
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class ProductionDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {     

        $categories = [
            "Playa", "Río", "Monte", "Deporte", "Comida", "Saltos",
            "Cultura", "Aventura", "Naturaleza", "Historia", "Música", "Festivales",
            "Relax", "Senderismo", "Gastronomía", "Arquitectura", "Paisajes", "Arte",
            "Espectáculos", "Diversión", "Ocio", "Exploración", "Camping",
            "Viajes", "Excursionismo", "Aire Libre", "Belleza Natural",
            "Mercados", "Paseos", "Escapadas"
        ];

        foreach ($categories as $category) {
            DB::table('categories')->insert([
                'name' => $category
            ]);
        }
    }
}
