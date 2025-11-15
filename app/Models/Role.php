<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    public $incrementing = false; 

     protected $fillable = [
        'id',
        'name',
        'image',
        'route'
    ];


    public function users(){
        return $this->belongsToMany(User::class,'user_has_roles', 'id_rol', 'id_user' );
    }
}
