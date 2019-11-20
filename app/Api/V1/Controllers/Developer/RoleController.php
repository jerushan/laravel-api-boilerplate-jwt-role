<?php

namespace App\Api\V1\Controllers\Developer;

use Auth;
use Validator;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Lang;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoleController extends Controller
{
    /**
     * Create a new RoleController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
        $this->middleware('role:developer');
    }

    /**
     * Get the authenticated User
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'         => 'required|unique:roles,name|max:200',
            'display_name' => 'required|max:200',
            'description'  => 'nullable|max:200',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $role = new Role($request->all());

        if($role->save()){
            return response()->json([
                'code' => 201,
                'data' => $role,
                'status' => Lang::get('messages.role_create_success'),
            ], 201);
        }
        else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_create_fail')], 200);
    }

    public function index(Request $request)
    {
        $roles = Role::get();

        if($roles->count()) return $roles;
        else throw new NotFoundHttpException(Lang::get('messages.role_not_found')); 
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id'           => 'required|exists:roles,id|max:200',
            'name'         => 'required|max:200|unique:roles,name,'.$request->id,
            'display_name' => 'required|max:200',
            'description'  => 'nullable|max:200',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $role = Role::find($request->id);
        $role->fill($request->all());

        if($role->save()){
            return response()->json([
                'code' => 201,
                'data' => $role,
                'status' => Lang::get('messages.role_update_success'),
            ], 201);
        }
        else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_update_fail')], 200);

        $roles = Role::get();

        if($roles->count()) return $roles;
        else throw new NotFoundHttpException(Lang::get('messages.role_not_found')); 
    }

    public function assignRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|max:200',
            'role_id' => 'required|exists:roles,id|max:200',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $user = User::find($request->user_id);
        $role = Role::find($request->role_id);

        if(! $user->hasRole($role->name)){
            if($user->attachRole($role->name)){
                return response()->json([
                    'code' => 201,
                    'status' => Lang::get('messages.role_assign_success'),
                ], 201);
            }
            else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_assign_fail')], 200);
        }
        else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_already_assigned')], 200);
    }

    public function detachRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id|max:200',
            'role_id' => 'required|exists:roles,id|max:200',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $user = User::find($request->user_id);
        $role = Role::find($request->role_id);

        if($user->hasRole($role->name)){
            if($user->detachRole($role->name)){
                return response()->json([
                    'code' => 201,
                    'status' => Lang::get('messages.role_detach_success'),
                ], 201);
            }
            else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_detach_fail')], 200);
        }
        else return response()->json(['code' => 200, 'status' => Lang::get('messages.role_already_detached')], 200);
    }
}
