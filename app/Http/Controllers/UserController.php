<?php

namespace App\Http\Controllers;

use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function findById(int $id)
    {
        $user = $this->service->findById($id);

        return response()->json($user, 200);
    }
}
