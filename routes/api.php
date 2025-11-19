<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileStorageController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::middleware(['jwt.auth'])->group(function () {
    Route::get('/users/{id}', [UserController::class, 'findById']);
    Route::put('/users/{id}', [UserController::class, 'update']);
});

// Route::post('/users', [UserController::class, 'create']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/register', [AuthController::class, 'register']);

// Ruta para servir archivos
Route::get('storage/{path}', [FileStorageController::class, 'serveFile'])
    ->where('path', '.*');
