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
        // Crear 10 usuarios
        // User::factory(50)->create([
        //     'img' => 'https://geturplanbackend.online/storage/default/default_user.png'
        // ]);      

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

        // Obtener los IDs de los usuarios y las categorías
        // $userIds = User::pluck('id')->toArray();
        // $categoryIds = Category::pluck('id')->toArray();

        // Crear 50 planes y asignarles 3 categorías aleatorias a cada uno
        // for ($i = 0; $i < 50; $i++) {
        //     $plan = Plan::factory()->create([
        //         'user_id' => $userIds[array_rand($userIds)]
        //     ]);

        //     // Asignar 3 categorías aleatorias al plan
        //     $randomCategories = array_rand($categoryIds, 3);
        //     foreach ($randomCategories as $categoryId) {
        //         $plan->categories()->attach($categoryIds[$categoryId]);
        //     }
        //     Secondaryimage::factory()->count(4)->create([
        //         'plan_id' => $plan->id
        //     ]);
        // }

        // Obtener los IDs de los planes
        // $planIds = Plan::pluck('id')->toArray();

        // Crear 30 comentarios y asignarles un usuario y un plan aleatorios
        // for ($i = 0; $i < 30; $i++) {
        //     Comment::factory()->create([
        //         'user_id' => $userIds[array_rand($userIds)],
        //         'plan_id' => $planIds[array_rand($planIds)],
        //     ]);
        // }

        // Asignar un número aleatorio de likes a cada plan
        // foreach ($planIds as $planId) {
        //     // Obtener un número aleatorio de likes para el plan, entre 0 y la cantidad de usuarios
        //     $likesCount = rand(0, count($userIds));

        //     if ($likesCount > 0) {
        //         // Seleccionar un conjunto aleatorio de usuarios sin repetición
        //         $randomUserIds = array_rand(array_flip($userIds), $likesCount);

        //         // Si $randomUserIds no es un array, lo convertimos a array para iterar correctamente
        //         if (!is_array($randomUserIds)) {
        //             $randomUserIds = [$randomUserIds];
        //         }

        //         // Crear los likes para el plan
        //         foreach ($randomUserIds as $userId) {
        //             Like::create([
        //                 'user_id' => $userId,
        //                 'plan_id' => $planId,
        //             ]);
        //         }
        //     }
        // }
        // User::factory()->create([
        //     'name' => 'Alex',
        //     'last_name' => 'Candela',
        //     'username' => 'alexcandela22',
        //     'email' => 'alex.candelaa@gmail.com',
        //     'password' => '123',
        //     'img' => 'https://geturplanbackend.online/storage/default/default_user.png'
        // ]);
    }
}
