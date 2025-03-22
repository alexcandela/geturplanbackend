<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\GeneralSettingsRequest;
use App\Http\Requests\UpdatePassowordRequest;

class EditProfileController extends Controller
{
    // Editar ajustes generales
    public function generalSettings(GeneralSettingsRequest $request)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();

            if ($this->isEmailOrUsernameTaken($data, $user)) {
                return $this->getConflictResponse($data, $user);
            }

            User::updateGeneralSettings($data, $user);

            // Generar un nuevo token con los datos editados
            $newToken = $this->createToken($user);

            return response()->json([
                'status' => 'success',
                'message' => 'Opciones generales actualizadas',
                'newUsername' => $user->username,
                'newEmail' => $user->email,
                'token' => $newToken,
            ], 200);
        } catch (\Throwable $th) {
            Log::error('Error en generalSettings@EditProfileController ' . $th);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el perfil'
            ], 500);
        }
    }

    // Detectar si ya existe el email o username
    private function isEmailOrUsernameTaken($data, $user)
    {
        $emailExists = User::where('email', $data['email'])
            ->where('id', '!=', $user->id)
            ->exists();

        $usernameExists = User::where('username', $data['username'])
            ->where('id', '!=', $user->id)
            ->exists();

        return $emailExists || $usernameExists;
    }

    // Enviar mensaje de error
    private function getConflictResponse($data, $user)
    {
        if (User::where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'El email ya está en uso',
                'type' => 'email'
            ], 409);
        }

        if (User::where('username', $data['username'])->where('id', '!=', $user->id)->exists()) {
            return response()->json([
                'status' => 'conflict',
                'message' => 'El nombre de usuario ya está en uso',
                'type' => 'username'
            ], 409);
        }
    }

    // Generar nuevo token
    private function createToken($user)
    {
        $custom_claims = [
            'id' => $user->id,
            'username' => $user->username,
        ];

        return JWTAuth::claims($custom_claims)->fromUser($user);
    }


    // Actualizar password
    public function updatePassword(UpdatePassowordRequest $request)
    {
        try {
            $data = $request->validated();
            $user = JWTAuth::user();

            $passwordNoMatch = !Hash::check($data['actualPass'], $user->password);

            if ($passwordNoMatch) {
                return response()->json([
                    'status' => 'conflict',
                    'message' => 'La contraseña no es correcta',
                    'type' => 'email'
                ], 409);
            } else {
                User::updatePassword($data['newPass'], $user);
                return response()->json([
                    'status' => 'success',
                    'message' => 'Contraseña actualizada',
                ], 200);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en updatePassword@EditProfileController ' . $th);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la contraseña'
            ],  500);
        }
    }
}
