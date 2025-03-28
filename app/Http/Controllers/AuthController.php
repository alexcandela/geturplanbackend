<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Cookie;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    // Generar el token JWT con datos del usuario
    public function createToken($user)
    {
        $custom_claims = [
            'id' => $user->id,
            'username' => $user->username,
        ];
        
        return JWTAuth::claims($custom_claims)->fromUser($user);
    }

    // Registro de usuarios pasando un token JWT.
    public function register(RegisterRequest $request)
    {
        $request->validated();
        try {
            $user = User::createUser($request);
            $token = $this->createToken($user);

            return response()->json([
                'status' => 'success',
                'token' => $token,
            ], 200);
        } catch (ValidationException $e) {
            $errors = $e->validator->errors()->getMessages();
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación al registrar el usuario.',
                'errors' => $errors,
            ], 422);
        } catch (\Exception $e) {
            Log::error("Error al registrar usuario: " . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al registrar el usuario. ' . $e->getMessage()
            ], 500);
        }
    }

    // Generar token personalizado al iniciar sesion
    public function jwtAttempt($user, $input)
    {
        $custom_claims = [
            'id' => $user->id,
            'username' => $user->username,
        ];

        return JWTAuth::claims($custom_claims)->attempt($input);
    }

    // Login de usuarios pasando un token JWT.
    public function login(LoginRequest $request)
    {
        try {
            $input = $request->only('email', 'password');

            // Si las credenciales no son correctas enviar error 401
            if (!$jwt_token = JWTAuth::attempt($input)) {
                return response()->json([
                    'status' => 'invalid_credentials',
                    'message' => 'Correo o contraseña no válidos.',
                ], 401);
            }

            $user = JWTAuth::user();

            // Si la autenticación es exitosa, generar el token
            $jwt_token = $this->jwtAttempt($user, $input);

            return response()->json([
                'status' => 'success',
                'token' => $jwt_token,
            ]);
        } catch (\Throwable $th) {
            Log::error("Error al loguear usuario: " . $th->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error al loguear el usuario. ' . $th->getMessage()
            ], 500);
        }
    }


    public function logout()
    {
        // Parsear y validar el token
        $token = JWTAuth::parseToken()->getToken();

        try {
            // Invalidar el token
            JWTAuth::invalidate($token);

            return response()->json([
                'status' => 'success',
                'message' => 'Cierre de sesión exitoso.'
            ]);
        } catch (JWTException $exception) {
            Log::error("Error al hacer logout: " . $exception->getMessage());
            return response()->json([
                'status' => 'unknown_error',
                'message' => 'No se pudo cerrar la sesión del usuario. ' . $exception->getMessage()
            ], 500);
        }
    }
}
