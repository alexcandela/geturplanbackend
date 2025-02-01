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
    public function generalSettings(GeneralSettingsRequest $request)
    {
        try {
            $data = $request->validated();
            $user = auth()->user();

            $emailExists = User::where('email', $data['email'])
                ->where('id', '!=', $user->id)
                ->exists();

            $usernameExists = User::where('username', $data['username'])
                ->where('id', '!=', $user->id)
                ->exists();
            if ($emailExists || $usernameExists) {
                if ($emailExists) {
                    return response()->json([
                        'status' => 'conflict',
                        'message' => 'El email ya esta en uso',
                        'type' => 'email'
                    ], 409);
                } else if ($usernameExists) {
                    return response()->json([
                        'status' => 'conflict',
                        'message' => 'El nombre de ususario ya esta en uso',
                        'type' => 'username'
                    ], 409);
                }
            } else {
                User::updateGeneralSettings($data, $user);
                $custom_claims = [
                    'id' => $user->id,
                    'username' => $user->username,
                ];
                $newToken = JWTAuth::claims($custom_claims)->fromUser($user);

                return response()->json([
                    'status' => 'success',
                    'message' => 'Opciones generales actualizadas',
                    'newUsername' => $user->username,
                    'newEmail' => $user->email,
                    'token' => $newToken,
                ], 200);
            }
        } catch (\Throwable $th) {
            //throw $th;
            Log::error('Error en generalSettings@EditProfileController ' . $th);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el perfil'
            ],  500);
        }
    }
    public function updatePassword(UpdatePassowordRequest $request)
    {
        try {

            $data = $request->validated();
            $user = auth()->user();
            
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
