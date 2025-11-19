<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Models\User;
use Illuminate\Support\Facades\Response;
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
    $user = User::find(19);

    return [
        'image_path' => $user->image,
        'image_url' => $user->image_url,
        'host' => request()->getHttpHost(),
        'environment' => app()->environment(),
    ];
});

Route::get('debug-image-full', function () {
    $user = User::find(19);

    if (! $user) {
        return ['error' => 'User not found'];
    }

    $imagePath = $user->image;

    // Limpiar el path para diagnóstico
    $cleanPath = str_replace('/storage/', '', $imagePath);
    $cleanPath = ltrim($cleanPath, '/');

    return [
        // Información del usuario
        'user_id' => $user->id,
        'image_column_raw' => $imagePath,
        'clean_path' => $cleanPath,

        // Verificaciones de archivo
        'file_exists_with_raw' => $imagePath ? Storage::disk('public')->exists($imagePath) : false,
        'file_exists_with_clean' => $cleanPath ? Storage::disk('public')->exists($cleanPath) : false,

        // Rutas físicas
        'storage_public_path' => storage_path('app/public'),
        'full_physical_path_raw' => $imagePath ? storage_path('app/public/'.$imagePath) : null,
        'full_physical_path_clean' => $cleanPath ? storage_path('app/public/'.$cleanPath) : null,

        // Verificar directorio
        'directory_exists' => $cleanPath ? Storage::disk('public')->exists(dirname($cleanPath)) : false,
        'files_in_user_dir' => Storage::disk('public')->exists('users/19') ?
            Storage::disk('public')->files('users/19') : 'Directory not found',

        // URLs generadas
        'url_with_raw' => $imagePath ? url("api/storage/{$imagePath}") : null,
        'url_with_clean' => $cleanPath ? url("api/storage/{$cleanPath}") : null,

        // Test directo de la ruta de archivos
        'direct_test_url' => $cleanPath ? url('api/storage/users/19/OYNHnyDeAzcliVuHn1pvvDgnkdaOX68F5gZKqgOB.png') : null,
    ];
});

Route::get('test-file-serving', function () {
    $testPath = 'users/19/OYNHnyDeAzcliVuHn1pvpDgnkdaOX68F5gZKqgOB.png';
    $disk = Storage::disk('public');

    if (! $disk->exists($testPath)) {
        return [
            'error' => 'File does not exist',
            'tested_path' => $testPath,
            'available_files' => $disk->files('users/19'),
        ];
    }

    try {
        $file = $disk->get($testPath);

        // Obtener el path físico completo
        $fullPath = storage_path('app/public/'.$testPath);

        // Usar mime_content_type para detectar el tipo MIME
        $type = mime_content_type($fullPath);

        return Response::make($file, 200)
            ->header('Content-Type', $type)
            ->header('Content-Disposition', 'inline');

    } catch (\Exception $e) {
        return [
            'error' => 'Error reading file',
            'message' => $e->getMessage(),
        ];
    }
});

Route::get('storage-config-debug', function () {
    return [
        'filesystem_default' => config('filesystems.default'),
        'public_disk_config' => config('filesystems.disks.public'),
        'app_url' => config('app.url'),
        'storage_path' => storage_path(),
        'public_storage_path' => storage_path('app/public'),
        'public_storage_link' => public_path('storage'),
        'link_exists' => is_link(public_path('storage')),
        'link_target' => is_link(public_path('storage')) ? readlink(public_path('storage')) : 'No link',
    ];
});
