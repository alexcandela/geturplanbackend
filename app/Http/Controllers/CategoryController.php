<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CategoryController extends Controller
{
    public function getCategories()
    {
        try {
            $categories = Category::all();

            return response()->json([
                'status' => 'success',
                'categories' => $categories,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getCategories@CategoryController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las categorias.'
            ], 500);
        }
    }
}
