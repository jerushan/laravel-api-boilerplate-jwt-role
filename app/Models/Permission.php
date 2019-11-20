<?php

namespace App\Models;

use Laratrust\Models\LaratrustPermission;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permission extends LaratrustPermission
{
    use SoftDeletes;
}
