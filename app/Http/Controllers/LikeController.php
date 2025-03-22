<?php

namespace App\Http\Controllers;

use App\Models\Like;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class LikeController extends Controller
{
    // Agregar o quitar like a un plan
    public function gestionarLike($user, $plan){
        if ($plan->likes()->where('user_id', $user->id)->exists()) {
            $like = $plan->likes()->where('user_id', $user->id);
            Like::removeLike($like);
            return false;
        } else {
            $like = Like::addLike($user, $plan->id);
            return true;
        }
    }

    // Funcion principal de like
    public function like(Request $request) {
        try {
            $data = $request->validate([
                'planId' => 'required|integer',
            ]);
            $planId = $data['planId'];
            $plan = Plan::findOrFail($planId);
            $user = JWTAuth::user();

            $message = $this->gestionarLike($user, $plan);
            
            return response()->json([
                'status' => 'success',
                'message' => $message
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error("Error en like@LikeController. " . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al dar like en el plan. ' . $th->getMessage()
            ], 500);
        }
    }
}
