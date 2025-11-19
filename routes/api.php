<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

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

Route::get('/check-file', function () {
    $filePath = 'users/19/pMV8ZRJNPtg0muUbBDYyKJc69D51qI1ZxHzDvArH.png';

    return [
        'exists_in_public' => Storage::disk('public')->exists($filePath),
        'full_path' => storage_path('app/public/'.$filePath),
        'files_in_directory' => Storage::disk('public')->files('users/19'),
    ];
});

Route::get('test-image-url', function () {
    $user = User::find(5);

    return [
        'image_path' => $user->image,
        'image_url' => $user->image_url,
        'host' => request()->getHttpHost(),
        'environment' => app()->environment(),
    ];
});
