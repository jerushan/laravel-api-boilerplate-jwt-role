<?php

namespace App\Models;

use Laratrust\Models\LaratrustRole;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends LaratrustRole
{
	use SoftDeletes;
	
    protected $fillable = [
        'name', 
        'display_name', 
        'description', 
    ];
}
