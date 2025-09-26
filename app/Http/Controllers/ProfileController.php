<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\EditProfileRequest;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProfileController extends Controller
{

    public function checkProd()
    {
        return app()->environment('production');
    }
    // Guardar imagen en servidor
    public function storageImage($image, $user)
    {
        try {
            $imageName = Str::random(40) . '.' . $image->getClientOriginalExtension();
            $path = 'images/users/' . $user->id . '/' . $imageName;

            if ($this->checkProd()) {
                // Producción: subir a Supabase (disco supabase)
                Storage::disk('supabase')->putFileAs(
                    'images/users/' . $user->id . '/',
                    $image,
                    $imageName
                );
                $imageName = 'https://xvzgprxywegcfprvqhtr.supabase.co/storage/v1/object/public/storage/' . $path;
            } else {
                // Local: guardar en storage/app/public
                Storage::disk('public')->putFileAs(
                    'images/users/' . $user->id . '/',
                    $image,
                    $imageName
                );
            }
            return $imageName;
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en storageImage@ProfileController. ' . $th->getMessage());
        }
    }

    // Eliminar imagen del servidor
    public function deleteImg($user)
    {
        try {
            $imageName = basename($user->img);
            if ($this->checkProd()) {
                Storage::disk('supabase')->delete('images/users/' . $user->id . '/' . $imageName);
            } else {

                if (Storage::disk('public')->exists('images/users/' . $user->id . '/' . $imageName)) {
                    Storage::disk('public')->delete('images/users/' . $user->id . '/' . $imageName);
                }
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en deleteImg@ProfileController. ' . $th->getMessage());
        }
    }

    // Editar datos de usuario como la imagen o la descripcion
    public function editProfile(EditProfileRequest $request)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();

            $defaultImgUrl = $this->checkProd()
                ? 'https://tu-bucket.supabase.co/storage/v1/object/public/storage/default/default_user.png'
                : env('APP_URL') . '/storage/default/default_user.png';


            if ($request->hasFile('img')) {
                if ($user->img != $defaultImgUrl) {
                    $this->deleteImg($user);
                }
                $data['img'] = $this->storageImage($request->file('img'), $user);
            } else if (isset($data['default_img'])) {
                if ($user->img != $defaultImgUrl) {
                    $this->deleteImg($user);
                }
            }

            User::updateUser($data, $user);

            return response()->json([
                'status' => 'success',
                'message' => 'Datos actualizados correctamente.'
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en editProfile@ProfileController. ' . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar los datos.'
            ], 500);
        }
    }
}
