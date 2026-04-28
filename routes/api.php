<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TaskController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api'])->controller(AuthController::class)->group(function () {
    Route::get('/me', 'me');
    Route::get('/logout', 'logout');
    Route::get('/dashboard', function () {
        return response()->json(['message' => 'User Dashboard']);
    });
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/admin', function () {
            return response()->json(['message' => 'Admin Panel']);
        });
    });
});

Route::middleware('auth:api')->group(function () {
    Route::apiResource('tasks', TaskController::class);
});

