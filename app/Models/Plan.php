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

    public function checkProd()
    {
        return app()->environment('production');
    }


    public function storageImage($image, $plan)
    {
        try {
            $imageName = Str::random(40) . '.' . $image->getClientOriginalExtension();
            $path = 'images/plans/' . $plan->id . '/' . $imageName;
            if ($this->checkProd()) {
                // Producción: subir a Supabase (disco supabase)
                Storage::disk('supabase')->putFileAs(
                    'images/plans/' . $plan->id . '/',
                    $image,
                    $imageName
                );
                $imageName = 'https://xvzgprxywegcfprvqhtr.supabase.co/storage/v1/object/public/storage/' . $path;
            } else {
                // Local: guardar en storage/app/public
                Storage::disk('public')->putFileAs(
                    'images/plans/' . $plan->id . '/',
                    $image,
                    $imageName
                );
            }
            return $imageName;
        } catch (\Throwable $th) {
            Log::error('Error en storageImage@PlanModel. ' . $th->getMessage());
        }
    }

    public function storeSecImages($secImgs, $plan)
    {
        foreach ($secImgs as $img) {
            $imageName = Str::random(40) . '.' . $img->getClientOriginalExtension();

            if ($this->checkProd()) {
                // Subir a Supabase
                Storage::disk('supabase')->putFileAs(
                    'images/plans/' . $plan->id . '/',
                    $img,
                    $imageName
                );

                // URL pública correcta de Supabase
                $secimgUrl = 'https://xvzgprxywegcfprvqhtr.supabase.co/storage/v1/object/public/storage/images/plans/'
                    . $plan->id . '/' . $imageName;
            } else {
                // Guardar en storage local
                Storage::disk('public')->putFileAs(
                    'images/plans/' . $plan->id . '/',
                    $img,
                    $imageName
                );

                $secimgUrl = env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $imageName;
            }

            // Guardar en la base de datos
            Secondaryimage::create([
                'img' => $secimgUrl,
                'plan_id' => $plan->id
            ]);
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
            if ($this->checkProd()) {
                $plan->update([
                    'img' => $img
                ]);
            } else {
                $plan->update([
                    'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $img
                ]);
            }


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
                $this->storeSecImages($data['secondary_images'], $plan);
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
            if ($this->checkProd()) {
                Storage::disk('supabase')->delete('images/plans/' . $plan->id . '/' . $imageName);
            } else {

                if (Storage::disk('public')->exists('images/plans/' . $plan->id . '/' . $imageName)) {
                    Storage::disk('public')->delete('images/plans/' . $plan->id . '/' . $imageName);
                }
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
            if ($this->checkProd()) {
                Storage::disk('supabase')->delete('images/plans/' . $plan->id . '/' . $imageName);
            } else {
                if (Storage::disk('public')->exists('images/plans/' . $plan->id . '/' . $imageName)) {
                    Storage::disk('public')->delete('images/plans/' . $plan->id . '/' . $imageName);
                }
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
            $img = $this->storageImage($data['principal_image'], $plan);
            $this->deleteImg($plan);
            if ($this->checkProd()) {
                $plan->update([
                    'img' => $img
                ]);
            } else {
                $plan->update([
                    'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $img
                ]);
            }
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
                if ($this->checkProd()) {
                    Secondaryimage::create([
                        'img' => $secimg,
                        'plan_id' => $plan->id
                    ]);
                } else {
                    Secondaryimage::create([
                        'img' => env('APP_URL') . '/storage/images/plans/' . $plan->id . '/' . $secimg,
                        'plan_id' => $plan->id
                    ]);
                }
            }
        }
        return $plan;
    }

    public function deletePlan($plan)
    {
        try {
            // Eliminar imagen principal
            $this->deleteImg($plan);

            // Eliminar imágenes secundarias
            $secondaryImages = $plan->secondaryImages;
            foreach ($secondaryImages as $secImg) {
                $this->deleteImagenSecundaria($secImg->img, $plan);
            }

            // Eliminar registros de imágenes secundarias de la base de datos
            Secondaryimage::where('plan_id', $plan->id)->delete();

            // Finalmente, eliminar el plan
            $plan->delete();
        } catch (\Throwable $th) {
            Log::error('Error en deletePlan@PlanModel. ' . $th->getMessage());
        }
    }
}
