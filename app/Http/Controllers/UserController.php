<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserController extends Controller
{
    // Obtener usuario por username
    public function getUser($username)
    {
        try {
            $same = false;
            $user = User::where('username', $username)->firstOrFail();

            $loggedUser = JWTAuth::user();
            if ($loggedUser) {
                $same = $loggedUser->username == $username;
            }
            return response()->json([
                'status' => 'success',
                'sameuser' => $same,
                'user' => $user,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en getUser@UserController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el usuario.'
            ], 500);
        }
    }

    // Obtener planes del usuario paginados
    public function getUserPlans(Request $request)
    {
        $username = $request->query('username');
        $page = $request->query('page');

        try {
            $user = User::where('username', $username)->firstOrFail();
            $plans = Plan::where('user_id', $user->id)
                ->withCount('likes')
                ->with(['user', 'categories', 'comments', 'secondaryImages'])
                ->paginate(3);

            $loggedUser = JWTAuth::user();
            if ($loggedUser) {
                $userId = $loggedUser->id;
                foreach ($plans as $plan) {
                    $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
                }
            }

            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getUserPlans@UserController: ' . $th->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los planes del usuario.'
            ], 500);
        }
    }

    // Email de recuperacion de password
    public function sendEmailResetPassword()
    {
        try {
            $user = JWTAuth::user();
            $user->notify(new ResetPassword());
            return response()->json([
                'status' => 'success',
                'message' => 'Email enviado correctamente.'
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'status' => 'error',
                'message' => 'Error al enviar el email al usuario.'
            ], 500);
        }
    }
}
