<?php

namespace App\Api\V1\Controllers\Notification;

use DB;
use Auth;
use Config;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use Tymon\JWTAuth\JWTAuth;
use App\Models\Notification;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class NotificationController extends Controller
{
    use Helpers;
    private $accessPerson = array();

    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    public function viewNotifications($pagination = 10)
    {
        $user = Auth::guard()->user();
        return NotificationResource::collection($user->notifications()->paginate(10));
    }

    public function markAsReadNotifications(Request $request)
    {
        $user = Auth::guard()->user();
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|exists:notifications,id,notifiable_id,'.$user->id,
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $updateRes = $user->unreadNotifications->where('id', '<=', $request->notification_id)->markAsRead();
        return response()->json(['status' => Lang::get('messages.notification_update_success'),], 201);
    }
}