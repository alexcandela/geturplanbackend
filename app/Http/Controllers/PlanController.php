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

class PlanController extends Controller
{
    public function getPopularPlans()
    {
        try {
            $plans = Plan::withCount(['likes' => function ($query) {
                $query->whereBetween('created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()]);
            }])
                ->orderByDesc('likes_count')
                ->with('user', 'categories', 'comments')
                ->take(4)
                ->get();
            if ($user = auth()->user()) {
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

    public function getPlanById($id)
    {
        try {
            $plan = Plan::withCount('likes')
                ->with(['user', 'categories', 'secondaryImages', 'comments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                }, 'comments.user'])
                ->findOrFail($id);
            if ($user = auth()->user()) {
                $userId = $user->id;
                $plan->has_liked = $plan->likes()->where('user_id', $userId)->exists();
            }
            return response()->json([
                'status' => 'success',
                'plan' => $plan,
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en getPlanById@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener el plan por id.'
            ], 500);
        }
    }

    public function getAllPlans(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
        ]);

        try {
            $plansQuery = Plan::withCount('likes')
                ->with('user', 'categories', 'comments');

            $plans = $plansQuery->paginate(8);

            if ($user = auth()->user()) {
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

    public function getFavoritePlans(Request $request)
    {
        $request->validate([
            'page' => 'required|integer',
        ]);

        try {
            $user = auth()->user();

            $plansQuery = Plan::withCount('likes')
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

            $plans = $plansQuery->paginate(8);


            $plans->getCollection()->transform(function ($plan) use ($user) {
                $plan->has_liked = $plan->likes()->where('user_id', $user->id)->exists();
                return $plan;
            });


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

    public function buscar(BusquedaRequest $request)
    {
        try {
            $query = $this->buildQuery($request);
            $plans = $query->paginate(8);
            if ($user = auth()->user()) {
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

    public function postPlan(PlanFormRequest $request)
    {
        try {
            $data = $request->validated();
            $data['categories'] = explode(',', $data['categories']);
            $user = auth()->user();
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

    public function deletePlan($id)
    {
        try {
            $user = auth()->user();
            $plan = Plan::findOrFail($id);
            if ($plan->user_id == $user->id) {
                $plan->delete();
            } else {
                return;
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Plan eliminado correctamente',
            ], 200);
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en postPlan@PlanController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar el plan.'
            ], 500);
        }
    }

    public function checkPlan($userId, $planId)
    {
        try {
            $plan = Plan::findOrfail($planId);
            if ($plan->user_id = $userId) {
                return $plan;
            } else {
                return false;;
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en checkPlan@PlanController. ' . $th->getMessage());
        }
    }

    function excludeUrl(array $urls, $urlToExclude)
    {
        return array_filter($urls, function ($url) use ($urlToExclude) {
            return $url !== $urlToExclude;
        });
    }

    public function updatePlan(PlanUpdateFormRequest $request, $planId)
    {
        try {
            $data = $request->validated();
            $user = auth()->user();
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
