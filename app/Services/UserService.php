<?php

namespace App\Services;

use App\Http\Requests\User\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UserService
{
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $user = $this->createUser($data);
            $this->assignClientRole($user);
            $token = $this->generateToken($user);

            return $this->buildAuthResponse($user, $token);
        });
    }

    public function login(array $data): array
    {
        $user = $this->findUserByEmail($data['email']);
        $this->validateCredentials($user, $data['password']);
        $token = $this->generateToken($user);

        return $this->buildAuthResponse($user, $token);
    }

    public function findById(int $id): User
    {
        return User::with('roles')->findOrFail($id);
    }

    public function update(int $id, UpdateUserRequest $request): User
    {
        return DB::transaction(function () use ($id, $request) {
            $user = $this->findById($id);
            $this->updateUserAttributes($user, $request);
            $this->handleImageUpload($user, $request);
            $user->save();

            return $user;
        });
    }

    private function createUser(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
        ]);
    }

    private function assignClientRole(User $user): void
    {
        $clientRole = Role::find('CLIENT');

        if (! $clientRole) {
            throw new \Exception('El rol del cliente no existe');
        }

        $user->roles()->attach($clientRole->id);
    }

    private function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    private function validateCredentials(?User $user, string $password): void
    {
        if (! $user || ! Hash::check($password, $user->password)) {
            throw new HttpException(404, 'El usuario y/o la contraseña son incorrectos');
        }
    }

    private function generateToken(User $user): string
    {
        return JWTAuth::fromUser($user);
    }

    private function buildAuthResponse(User $user, string $token): array
    {
        return [
            'token' => 'Bearer '.$token,
            'user' => $this->formatUserData($user),
        ];
    }

    private function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'lastname' => $user->lastname,
            'image' => $user->image,
            'notification_token' => $user->name, // Nota: ¿esto debería ser el nombre?
            'roles' => $this->formatRoles($user->roles),
        ];
    }

    private function formatRoles($roles): array
    {
        return $roles->map(function ($role) {
            return [
                'id' => $role->id,
                'name' => $role->name,
                'route' => $role->route,
                'image' => $role->image,
            ];
        })->toArray();
    }

    private function updateUserAttributes(User $user, UpdateUserRequest $request): void
    {
        $attributes = ['name', 'lastname', 'phone'];

        foreach ($attributes as $attribute) {
            if ($request->filled($attribute)) {
                $user->$attribute = $request->input($attribute);
            }
        }
    }

    private function handleImageUpload(User $user, UpdateUserRequest $request): void
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $path = $file->store("users/{$user->id}", 'public');
            $user->image = $path;
        }
    }
}
