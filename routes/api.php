<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\EditProfileController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Auth\Middleware\Authenticate;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::get('/categories', [CategoryController::class, 'getCategories']);

Route::get('/popular-plans', [PlanController::class, 'getPopularPlans']);
Route::get('/all-plans', [PlanController::class, 'getAllPlans']);
Route::get('/buscar', [PlanController::class, 'buscar']);

Route::get('/get-plan/{id}', [PlanController::class, 'getPlanById']);
Route::get('/get-user/{username}', [UserController::class, 'getUser']);

Route::get('get-user-plans', [UserController::class, 'getUserPlans']);

Route::get('reset-password', [UserController::class, 'sendEmailResetPassword']);

Route::middleware([Authenticate::class])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/like', [LikeController::class, 'like']);
    Route::post('/comment', [CommentController::class, 'comment']);
    Route::post('/comment-like', [CommentController::class, 'like']);
    Route::delete('/delete-comment/{id}', [CommentController::class, 'deleteComment']);
    Route::post('/edit-profile', [ProfileController::class, 'editProfile']);
    Route::post('/general-settings', [EditProfileController::class, 'generalSettings']);
    Route::post('/update-password', [EditProfileController::class, 'updatePassword']);
    Route::post('/post-plan', [PlanController::class, 'postPlan']);
    Route::delete('/delete-plan/{id}', [PlanController::class, 'deletePlan']);
    Route::post('/update-plan/{id}', [PlanController::class, 'updatePlan']);
    Route::get('/favorite-plans', [PlanController::class, 'getFavoritePlans']);
});