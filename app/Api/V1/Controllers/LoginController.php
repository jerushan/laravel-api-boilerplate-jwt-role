<?php

namespace App\Api\V1\Controllers;

use Auth;
use Validator;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class LoginController extends Controller
{
    /**
     * Log the user in
     *
     * @param LoginRequest $request
     * @param JWTAuth $JWTAuth
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request, JWTAuth $JWTAuth)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email',
            'password' => 'required',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $credentials = $request->only(['email', 'password']);

        try {
            $token = Auth::guard()->attempt($credentials);

            if(!$token) {
                throw new AccessDeniedHttpException();
            }

        } catch (JWTException $e) {
            throw new HttpException(500);
        }

        $user = Auth::guard()->user();
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