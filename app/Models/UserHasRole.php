<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserHasRole extends Pivot
{
    protected $table = "user_has_roles";
    
    protected $fillable = ['id_user', 'id_rol'];

     public $incrementing = false; 

     public $timesmtamps = false; 


}
