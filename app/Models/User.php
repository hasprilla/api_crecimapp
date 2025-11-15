<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    protected $fillable = [
        'name',
        'lastname',
        'email',
        'phone',
        'password',
    ];

    protected $hidden = ['password'];

    public function roles()
    {

        return $this->belongsToMany(Role::class, 'user_has_roles', 'id_user', 'id_rol');
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }
}
