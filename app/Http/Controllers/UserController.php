<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\UpdateUserRequest;
use App\Services\UserService;

class UserController extends Controller
{
    public function __construct(private UserService $service) {}

    public function findById(int $id)
    {
        $user = $this->service->findById($id);

        return response()->json($user, 200);
    }

    public function update(int $id, UpdateUserRequest $request)
    {
        $user = $this->service->update($id, $request);

        return response()->json($user, 200);
    }
}
