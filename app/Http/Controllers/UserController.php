<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function getUser($username)
    {
        try {
            $same = false;
            $user = User::where('username', $username)->firstOrFail();

            $plans = Plan::where('user_id', $user->id)
                ->withCount('likes')
                ->with(['user', 'categories', 'comments'])
                ->paginate(3);

            $loggedUser = auth()->user();
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

            $loggedUser = auth()->user();
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
}
