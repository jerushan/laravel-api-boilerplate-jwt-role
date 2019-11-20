<?php

namespace App\Api\V1\Controllers;

use Auth;
use Config;
use Validator;
use App\Models\User;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Symfony\Component\HttpKernel\Exception\HttpException;

class SignUpController extends Controller
{
    public function signUp(Request $request, JWTAuth $JWTAuth)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|max:100',
            'email'    => 'required|unique:users,email|email|max:100',
            'password' => 'required|max:10',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $user = new User($request->all());
        if(!$user->save()) {
            throw new HttpException(500);
        }

        if(!Config::get('boilerplate.sign_up.release_token')) {
            return response()->json([
                'status' => 'ok'
            ], 201);
        }

        $token = $JWTAuth->fromUser($user);
        $userUniqueString = $user->id.$user->email;
        $userUniqueToken = md5($userUniqueString);

        return response()->json([
            'code'         => 201,
            'status'       => 'ok',
            'user'         => $user,
            'token'        => $token,
            'unique_token' => $userUniqueToken,
            'expires_in'   => Auth::guard()->factory()->getTTL() * 60
        ], 201);
    }
}