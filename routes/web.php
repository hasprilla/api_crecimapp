<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Acceso denegado',
        'status' => 403, ,
    ], 403);
});
