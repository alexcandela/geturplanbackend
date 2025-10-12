<?php

namespace App\Http\Controllers;

use App\Http\Requests\BusquedaRequest;
use App\Http\Requests\PlanFormRequest;
use App\Http\Requests\PlanUpdateFormRequest;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Contracts\Providers\Auth;
use Illuminate\Database\Eloquent\Collection;
use Tymon\JWTAuth\Facades\JWTAuth;

class PlanController extends Controller
{
    // public function hasReplies($plans)
    // {
    //     try {
    //     } catch (\Throwable $th) {
    //         Log::error('Error en hasReplies@PlanController. ' . $th->getMessage());
    //     }
    // }

    // Obtener planes populares
    public function getPopularPlans()
    {
        try {
            $plans = Plan::withCount(['likes' => function ($query) {
                // $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            }])
                ->orderByDesc('likes_count')
                ->with('user', 'categories', 'comments')
                ->take(4)
                ->get();
            if ($user = JWTAuth::user()) {
                $userId = $user->id;
                $plans->each(function ($plan) use ($userId) {
                    $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
                });
            }

            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getPopularPlans@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los planes populares.'
            ], 500);
        }
    }

    // Obtener plan por id
    public function getPlanById($id)
    {
        try {
            $plan = $this->consultaSQLPlan($id);
            $plan->comments = $this->agregarDatosComments($plan->comments);

            if ($user = JWTAuth::user()) {
                $plan = $this->detectarUserLike($plan, $user->id);
            }
            return response()->json([
                'status' => 'success',
                'plan' => $plan,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getPlanById@PlanController. ' . $th->getMessage() . $th);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el plan por id.'
            ], 500);
        }
    }

    // Obtener el plan junto a todas sus relaciones 
    public function consultaSQLPlan($id)
    {
        return Plan::withCount('likes')
            ->with([
                'user',
                'categories',
                'secondaryImages',
                'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'comments.user',
                'comments.likes',
                'comments.replies',
                'comments.replies.user',
                'comments.replies.likes'
            ])
            ->findOrFail($id);
    }

    // Agregar datos adicionales necesarios como el conteo de likes y las respuestas a los comentarios
    public function agregarDatosComments($comments)
    {
        foreach ($comments as $comment) {
            $comment->likes_count = $comment->likes->count();
            $comment->has_replies = $comment->replies->isNotEmpty();
            foreach ($comment->replies as $reply) {
                $reply->likes_count = $reply->likes->count();
            }
        }
        return $comments;
    }

    // Detectar si el usuario logeado ya habia dado like en el plan y/o comentarios para mostrarlo luego visualmente
    public function detectarUserLike($plan, $userId)
    {
        $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
        foreach ($plan->comments as $comment) {
            $comment->has_liked = $comment->likes()->where('user_id', $userId)->exists();
            foreach ($comment->replies as $reply) {
                $reply->has_liked = $reply->likes()->where('user_id', $userId)->exists();
            }
        }
        return $plan;
    }

    // Obtener todos los planes con paginate
    public function getAllPlans(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
        ]);

        try {
            $plansQuery = Plan::withCount('likes')
                ->with('user', 'categories', 'comments');

            $plans = $plansQuery->paginate(8);

            if ($user = JWTAuth::user()) {
                $userId = $user->id;
                $plans->getCollection()->transform(function ($plan) use ($userId) {
                    $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
                    return $plan;
                });
            }

            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getAllPlans@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los planes populares.'
            ], 500);
        }
    }

    // Obtener los planes marcados como favoritos
    public function getFavoritePlans(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
        ]);
        try {
            if ($user = JWTAuth::user()) {
                $plansQuery = $this->buildQueryFavoritePlans($user);

                $plans = $plansQuery->paginate(8);


                $plans->getCollection()->transform(function ($plan) use ($user) {
                    $plan->has_liked = $plan->likes()->where('user_id', $user->id)->exists();
                    return $plan;
                });
            }
            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en getFavoritePlans@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los planes favoritos.'
            ], 500);
        }
    }

    // Consulta para obtener los planes que el usuario ha marcado como favoritos
    public function buildQueryFavoritePlans($user)
    {
        return Plan::withCount('likes')
            ->whereHas('likes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with('user', 'categories', 'comments')
            ->whereIn('id', function ($query) use ($user) {
                $query->select('plan_id')
                    ->from('likes')
                    ->where('user_id', $user->id)
                    ->orderBy('created_at', 'desc');
            });
    }

    // Ordenar planes por fecha o mas populares
    public function ordenar($data, $query)
    {
        try {
            switch ($data) {
                case 'fecha':
                    $query->orderBy('created_at', 'DESC');
                    break;
                case 'popular':
                    $query->orderBy('likes_count', 'DESC');
                    break;
                default:
                    # code...
                    break;
            }
            return $query;
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en ordenar@PlanController. ' . $th->getMessage());
        }
    }

    // Buscar planes segun los filtros obtenidos
    public function buildQuery($data)
    {
        try {
            $query = Plan::withCount('likes')
                ->with('user', 'categories', 'comments');
            if ($data['value']) {
                $query->whereRaw("unaccent(LOWER(name)) LIKE unaccent(LOWER(?))", ["%{$data['value']}%"]);
            }
            if ($data['provincia'] != 'Cualquiera') {
                $query->where('province', $data['provincia']);
            }
            if ($data['categorias']) {
                $categorias = explode(',', $data['categorias']);
                $query->whereHas('categories', function ($q) use ($categorias) {
                    $q->whereIn('name', $categorias);
                });
            }
            if ($data['ordenar'] != 'false') {
                $query = $this->ordenar($data['ordenar'], $query);
            }
            $query->get();
            return $query;
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en buildQuery@PlanController. ' . $th->getMessage());
        }
    }

    // Obtener los planes de la busqueda con filtros
    public function buscar(BusquedaRequest $request)
    {
        try {
            $query = $this->buildQuery($request);
            $plans = $query->paginate(8);
            if ($user = JWTAuth::user()) {
                $userId = $user->id;
                $plans->getCollection()->transform(function ($plan) use ($userId) {
                    $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
                    return $plan;
                });
            }
            return response()->json([
                'status' => 'success',
                'plans' => $plans,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en buscar@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener los planes. ' . $th->getMessage()
            ], 500);
        }
    }

    // Publicar plan
    public function postPlan(PlanFormRequest $request)
    {
        try {
            $data = $request->validated();
            $data['categories'] = explode(',', $data['categories']);
            $user = JWTAuth::user();
            $plan = new Plan();
            $plan = $plan->createPlan($data, $user);
            return response()->json([
                'status' => 'success',
                'message' => 'Plan publicado correctamente',
                'planId' => $plan->id
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en postPlan@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al publicar plan. ' . $th->getMessage()
            ], 500);
        }
    }

    // Eliminar plan
    public function deletePlan($id)
    {
        try {
            $user = JWTAuth::user();
            $plan = Plan::find($id);

            if (!$plan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Plan no encontrado',
                ], 404);
            }

            if ($plan->user_id == $user->id) {
                $p = new Plan();
                $p->deletePlan($plan);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Plan eliminado correctamente',
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permisos para eliminar este plan',
                ], 403);
            }
        } catch (\Throwable $th) {
            Log::error('Error en deletePlan@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Hubo un problema al intentar eliminar el plan.'
            ], 500);
        }
    }

    // Comprobar que el plan pertenece al usuario
    public function checkPlan($userId, $planId)
    {
        try {
            $plan = Plan::findOrfail($planId);
            if ($plan->user_id == $userId) {
                return $plan;
            } else {
                return false;;
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en checkPlan@PlanController. ' . $th->getMessage());
        }
    }

    // Devolver el array sin la url enviada para eliminar ($urlToExclude)
    function excludeUrl(array $urls, $urlToExclude)
    {
        return array_filter($urls, function ($url) use ($urlToExclude) {
            return $url !== $urlToExclude;
        });
    }

    // Actualizar plan
    public function updatePlan(PlanUpdateFormRequest $request, $planId)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();
            if ($planToEdit = $this->checkPlan($user->id, $planId)) {
                $data['categories'] = explode(',', $data['categories']);
                if (isset($data['imagesToDelete'])) {
                    $data['imagesToDelete'] = json_decode($data['imagesToDelete'], true);
                    $data['imagesToDelete'] = $this->excludeUrl($data['imagesToDelete'], 'http://localhost:8000/storage/default/noimage.png');
                }

                $plan = new Plan();
                $plan = $plan->updatePlan($data, $planToEdit);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Plan editado correctamente',
                    'planId' => $plan->id
                ], 200);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No tienes permisos para editar este plan',
                ], 403);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en updatePlan@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al editar plan. ' . $th->getMessage()
            ], 500);
        }
    }
}
