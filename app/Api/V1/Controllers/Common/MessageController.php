<?php

namespace App\Api\V1\Controllers\Common;

use DB;
use Auth;
use Config;
use Storage;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\School;
use App\Models\Message;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewMessageNotification;
use App\Api\V1\Controllers\Teacher\ClassesController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Http\Resources\Notification\NotificationResource;
use App\Api\V1\Controllers\Notification\RealTimeNotificationController;


class MessageController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    public function getChatList()
    {
        $user = Auth::guard()->user();
        $fullUsersIds = $this->getCanChatIds($user);
                                                   
        $id = $user->id;                                  
        $messages = DB::select('SELECT t1.*
                                FROM messages AS t1
                                INNER JOIN
                                (
                                    SELECT
                                        LEAST(sender_id, receiver_id) AS sender_id,
                                        GREATEST(sender_id, receiver_id) AS receiver_id,
                                        MAX(id) AS max_id
                                    FROM messages
                                    GROUP BY
                                        LEAST(sender_id, receiver_id),
                                        GREATEST(sender_id, receiver_id)
                                ) AS t2
                                    ON LEAST(t1.sender_id, t1.receiver_id) = t2.sender_id AND
                                       GREATEST(t1.sender_id, t1.receiver_id) = t2.receiver_id AND
                                       t1.id = t2.max_id
                                    WHERE t1.sender_id = ? OR t1.receiver_id = ?
                                ', [$id, $id]);

        if($messages){
            $messages = array_reverse($messages);
            foreach( $messages as $key => $message){
                if($message->receiver_id == $user->id){
					$message->type  = 'receive';
					$oppositeUserId = $message->sender_id;
					$oppositerData  = User::select('id','name','profile_image')->find($message->sender_id);
                    if($oppositerData) $message->sender = $oppositerData;
                    
                    if (($key = array_search($message->sender_id, $fullUsersIds)) !== false) {
                        unset($fullUsersIds[$key]);
                    }
                }
                else{
					$message->type  = 'send';
					$oppositeUserId = $message->receiver_id;
					$oppositerData  = User::select('id','name','profile_image')->find($message->receiver_id);
                    if($oppositerData) $message->receiver = $oppositerData;
                    
                    if (($key = array_search($message->receiver_id, $fullUsersIds)) !== false) {
                        unset($fullUsersIds[$key]);
                    }
                }

                $message->unseen_message_count = Message::whereNull('seen')
                                                            ->where(['receiver_id' => $user->id])
                                                            ->where(['sender_id' => $oppositeUserId])
                                                            ->count();
            }
        }

        $contactList = array();
        if(value($fullUsersIds)){
        	$contactList = User::select('id','school_id','name','profile_image','roles')->whereIn('id', $fullUsersIds)->get();
        }
           
        return response()->json([
			'chat_list'    => $messages,
			'contact_list' => $contactList,
        ], 201);
    }

    public function sendMessageToUser(Request $request)
    {
        $user = Auth::guard()->user();
        $fullUsersIds = $this->getCanChatIds($user);
        $fullUsersIdsString = implode(', ',$fullUsersIds);

        $errorMessages = [
			'receiver_id.in'     => 'You cannot send message to this selected user',
			'receiver_id.not_in' => 'You cannot send message to yourself',
        ];

        $validator = Validator::make($request->all(), [
			'receiver_id' => 'required|in:'.$fullUsersIdsString.'|not_in:'.$user->id,
            'message'     => 'required_without:attachments|string:1000|max:1000', 
            'attachments' => 'required_without:message|mimes:jpeg,jpg,png,pdf|max:1000',
        ], $errorMessages);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $request->merge(["sender_id" => $user->id]);
        $message = new Message($request->all());

        if($message->save()){
            $per_page       = 1;
            $whereMessageId = 'id = "'.$message->id.'"';
            $messagesRes    = $this->messageLayout($user->id, $request->receiver_id, $per_page, $whereMessageId);
            $responceArray  = json_decode($messagesRes->getContent(),true);
            $messages       = $responceArray['messages'];
            $messages_2     = $responceArray['messages_2'];
            $RTNotiData     = $messages_2[0][0];

            $fromUser = $user;
            $RTNotiData['type'] = 'receive';
            $toUser = User::find($request->receiver_id);
            $RTController = new RealTimeNotificationController;
            $sendRTNRes = $RTController->sendRealTimeNotification($toUser, $RTNotiData);
            
            // $notificationMessage = "You've got a new message from NAME !";
            // $notificationMessage = str_replace('NAME', $fromUser->name , $notificationMessage);

            // $notiData =  array( 
            //     'message'              => $request->message,
            //     'message_data'         => $RTNotiData,
            //     'user_id'              => $toUser->id,
            //     'opposite_user_id'     => $fromUser->id,
            //     'notification_type'    => 'message',
            //     'notification_message' => $notificationMessage,
            //     'created_at'           => Carbon::now(),
            //     'updated_at'           => Carbon::now(),
            // );

            // $sendNotificationRes = Notification::send($toUser, new NewMessageNotification($notiData));
            // $notiLayout = new NotificationResource($toUser->unreadNotifications()->orderBy('created_at', 'desc')->limit(1)->first());

            $RTNotiData['type'] = 'send';
            return response()->json([
            	'message' => $RTNotiData,
            	'status' => Lang::get('messages.message_send_sucess'),
                'send_real_time_notification_responce' => $sendRTNRes,
            ], 201);
    	}
    	else return response()->json(['status' => Lang::get('messages.message_send_fail'),], 201);
    }

    public function viewMessageForUser(Request $request)
    {
    	$user = Auth::guard()->user();
        $fullUsersIds = $this->getCanChatIds($user);
        $fullUsersIdsString = implode(', ',$fullUsersIds);
        if(! value($request->per_page)) $request->merge(["per_page" => 10 ]);

        $errorMessages = [
			'user_id.in'     => 'You cannot view message to this selected user',
			'user_id.not_in' => 'You cannot view message to yourself',
        ];

        $validator = Validator::make($request->all(), [
			'per_page' => 'required|integer',
			'user_id'  => 'required|in:'.$fullUsersIdsString.'|not_in:'.$user->id,
        ], $errorMessages);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $userId = $user->id;
        $per_page = $request->per_page;
        $oppositeUserId = $request->user_id;
        $updateSeen = Message::whereNull('seen')->where(['receiver_id' => $userId])->update(['seen' => Carbon::now()]);
        $messagesRes = $this->messageLayout($userId, $oppositeUserId, $per_page);

        if($messagesRes->status() == 201){
			$responceArray     = json_decode($messagesRes->getContent(),true);
			$messages          = $responceArray['messages'];
			$messages_2        = $responceArray['messages_2'];
			$messagesPaginated = new \Illuminate\Pagination\LengthAwarePaginator(
		        $messages_2,
		        $messages['total'],
		        $messages['per_page'],
		        $messages['current_page'], [
		            'path' => \Request::url(),
		            'query' => [
		                'page' => $messages['current_page']
		            ]
		        ]
		    );

            return $messagesPaginated;
        }
        else return response()->json(['status' => Lang::get('messages.no_message_between_these_users'),], 201);
    } 

    public function messageLayout($userId, $oppositeUserId, $perPage=1, $whereMessageId='id <> ""')
    {
        $messages = Message::with('sender:id,name,profile_image')
        						->with('receiver:id,name,profile_image')
	                            ->where(function($or) use($userId, $oppositeUserId){
	                                $or->orWhere(function($query) use($userId, $oppositeUserId){
	                                    $query->where(['sender_id' => $userId])
	                                          ->where(['receiver_id' => $oppositeUserId]);
	                                })
	                                ->orWhere(function($query) use($userId, $oppositeUserId){
	                                    $query->where(['receiver_id' => $userId])
	                                          ->where(['sender_id' => $oppositeUserId]);
	                                });
	                            })
	                            ->whereRaw($whereMessageId)
	                            ->orderBy('id', 'DESC')
	                            ->paginate($perPage);

        if($messages->count()){
        	$messages_2 = $messages->getCollection()->map(function ($message) use($userId){
						    if($message->receiver_id == $userId){
			                    $message->type = 'receive';
			                }
			                else{
			                    $sender = $message->sender;
			                    $message->sender = $message->receiver;
			                    $message->receiver = $sender;
			                    $message->type = 'send';
			                }

			                if(! is_null($message->created_at)){
			                    $message->messaged_day = $this->getTheDayForMessage($message->created_at->todatestring());
			                    $message->messaged_date = $message->created_at->todatestring();
			                }
							return $message;
						});

        	$messages_2 = $messages_2->groupBy(function($query){
                return $query->messaged_date;
            })->values()->toArray();
            $messages_2 = collect($messages_2);

            return response()->json(['messages' => $messages, 'messages_2' => $messages_2], 201);
        }
        else return response()->json(['messages' => [], 'messages_2' => []], 404);
    } 

    public function getCanChatIds($user)
    {
    	$request = new Request();
        $classCon = new ClassesController();

    	if($user->hasRole('ROLE_STUDENT')){
    		$request->merge(["student_id" => $user->id]);
	        $classTeacherIds = $classCon->viewStudentClassesTeacherIds($request);
	        $schoolAdminIds = School::where('id', $user->school_id)->pluck('admin_id')->toArray();
	        return array_unique(array_merge($classTeacherIds, $schoolAdminIds));
    	}
    	elseif($user->hasRole('ROLE_TEACHER')){
    		$teacherAllStudentsIds = [];
    		$request->merge(["teacher_id" => $user->id]);
    		$teacherAllStudents = $classCon->viewAllStudentsInAllClasses($request, true);
    		$schoolAdminIds = School::where('id', $user->school_id)->pluck('admin_id')->toArray();
    		if($teacherAllStudents->count()) $teacherAllStudentsIds = $teacherAllStudents->pluck('id')->toArray();

    		return array_unique(array_merge($teacherAllStudentsIds, $schoolAdminIds));
    	}
    	elseif($user->hasRole('ROLE_ADMIN')){
    		$schoolUsersIds = User::where('school_id', $user->school_id)->whereNotIn('id', [$user->id])->pluck('id')->toArray();
    		$superAdminsIds = User::where('roles', 'like', "%ROLE_SUPERADMIN%")->pluck('id')->toArray();
    		return array_unique(array_merge($schoolUsersIds, $superAdminsIds));
    	}
    	elseif($user->hasRole('ROLE_SUPERADMIN')){
    		return User::whereNotIn('id', [$user->id])->pluck('id')->toArray();
    	}
    }
}