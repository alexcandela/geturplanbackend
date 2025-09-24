<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'province',
        'city',
        'url',
        'img',
        'latitude',
        'longitude',
        'user_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class);
    }

    public function planevent()
    {
        return $this->hasMany(Planevent::class);
    }

    public function secondaryImages()
    {
        return $this->hasMany(Secondaryimage::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    public function storageImage($image, $plan)
    {
        try {
            $imageName = Str::random(40) . '.' . $image->getClientOriginalExtension();
            Storage::disk('public')->putFileAs('images/plans/' . $plan->id . '/', $image, $imageName);
            return $imageName;
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en storageImage@PlanModel. ' . $th->getMessage());
        }
    }

    public function createPlan($data, $user)
    {
        try {
            $lat = (float) $data['latitude'];
            $long = (float) $data['longitude'];

            $plan = Plan::create([
                'name' => $data['name'],
                'description' => $data['description'],
                'province' => $data['province'],
                'city' => $data['city'],
                'latitude' => $lat,
                'longitude' => $long,
                'img' => 'null',
                'user_id' => $user->id
            ]);
            $img = $this->storageImage($data['principal_image'], $plan);
            $plan->update([
                'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $img
            ]);
            foreach ($data['categories'] as $name) {
                $category = Category::where('name', $name)->first();
                if ($category) { // Verifica si la categoría existe
                    if (!$plan->categories()->where('category_id', $category->id)->exists()) {
                        $plan->categories()->attach($category->id);
                    }
                } else {
                    Log::warning("Categoría no encontrada: " . $name);
                }
            }
            if (isset($data['secondary_images'])) {
                foreach ($data['secondary_images'] as $img) {
                    $secimg = $this->storageImage($img, $plan);
                    Secondaryimage::create([
                        'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $secimg,
                        'plan_id' => $plan->id
                    ]);
                }
            }
            return $plan;
        } catch (\Throwable $th) {
            Log::error('Error en createPlan@PlanModel. ' . $th->getMessage());
        }
    }

    public function updateCategories($plan, $categories)
    {
        $plan->categories()->detach();

        foreach ($categories as $name) {
            $category = Category::where('name', $name)->first();
            if ($category) {
                $plan->categories()->attach($category->id);
            }
        }
    }

    public function deleteImg($plan)
    {
        try {
            $imageName = basename($plan->img);
            if (Storage::disk('public')->exists('images/plans/' . $plan->id . '/' . $imageName)) {
                Storage::disk('public')->delete('images/plans/' . $plan->id . '/' . $imageName);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en deleteImg@PlanModel. ' . $th->getMessage());
        }
    }

    public function deleteImagenSecundaria($img, $plan)
    {
        try {
            $imageName = basename($img);
            if (Storage::disk('public')->exists('images/plans/' . $plan->id . '/' . $imageName)) {
                Storage::disk('public')->delete('images/plans/' . $plan->id . '/' . $imageName);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en deleteImagenSecundaria@PlanModel. ' . $th->getMessage());
        }
    }

    public function deleteImagesFromDatabase(array $imageUrls, $planId)
    {
        // Obtener los IDs de las imágenes que se van a eliminar
        $imageIdsToDelete = Secondaryimage::whereIn('img', $imageUrls)
            ->where('plan_id', $planId)
            ->pluck('id'); // Obtener solo los IDs

        // Eliminar las imágenes del modelo Secondaryimage por sus IDs
        Secondaryimage::whereIn('id', $imageIdsToDelete)
            ->delete();
    }

    function excludeUrl(array $urls, $urlToExclude)
    {
        return array_filter($urls, function ($url) use ($urlToExclude) {
            return $url !== $urlToExclude;
        });
    }

    public function updateImgSecundarias($images) {}

    public function updatePlan($data, $plan)
    {
        $lat = (float) $data['latitude'];
        $long = (float) $data['longitude'];

        $plan->update([
            'name' => $data['name'],
            'description' => $data['description'],
            'province' => $data['province'],
            'city' => $data['city'],
            'latitude' => $lat,
            'longitude' => $long,
        ]);
        if (isset($data['principal_image'])) {
            $this->deleteImg($plan);
            $img = $this->storageImage($data['principal_image'], $plan);
            $plan->update([
                'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $img
            ]);
        }

        if (isset($data['categories'])) {
            $this->updateCategories($plan, $data['categories']);
        }
        if (isset($data['imagesToDelete'])) {
            foreach ($data['imagesToDelete'] as $img) {
                $this->deleteImagenSecundaria($img, $plan);
            }
            $this->deleteImagesFromDatabase($data['imagesToDelete'], $plan->id);
        }

        if (isset($data['secondary_images'])) {
            foreach ($data['secondary_images'] as $img) {
                $secimg = $this->storageImage($img, $plan);
                Secondaryimage::create([
                    'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $secimg,
                    'plan_id' => $plan->id
                ]);
            }
        }
        return $plan;
    }
}
