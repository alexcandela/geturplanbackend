<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class HealthController extends Controller
{
    // Función para maneter el backend activo, a través de Uptime Robot
    public function active()
    {
        return response()->json([
            'status' => 'success',
        ], 200);
    }
}
