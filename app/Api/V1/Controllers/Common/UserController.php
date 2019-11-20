<?php

namespace App\Api\V1\Controllers\Common;

use Auth;
use Tymon\JWTAuth\JWTAuth;
use App\Http\Controllers\Controller;
use App\Api\V1\Requests\LoginRequest;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('jwt.auth', []);
    }

    public function viewProfile($id = null)
    {
        if(is_null($id)) $user = Auth::guard()->user();
        else $user = User::find($id);
        if(!$user) throw new NotFoundHttpException(Lang::get('messages.request_user_not_found')); 
        return $user;
    }

    public function updateProfile(Request $request, $id = null)
    {
        if(is_null($id)) $user = Auth::guard()->user();
        else $user = User::find($id);
        if(!$user) throw new NotFoundHttpException(Lang::get('messages.request_user_not_found')); 

        $maxSize = Config::get('filepaths.max_file_sizes.user_profile_image');
        $validator = Validator::make($request->all(), [
            'name'             => 'required|max:100',
            'email'            => 'required|email|max:150|unique:users,email,'.$user->id,
            'phone'            => 'required|max:8|regex:/[0-9]{8}/|unique:users,phone,'.$user->id,
            'dob'              => 'nullable|date_format:Y-m-d',
            'city'             => 'nullable|max:100',
            'door'             => 'nullable|max:100',
            'floor'            => 'nullable|max:100',
            'street'           => 'nullable|max:100',
            'streetNumber'     => 'nullable|max:100',
            'zipCode'          => 'nullable|max:100',
            'one_line_address' => 'nullable|max:500',
            'gender'           => 'nullable|in:male,female,non_binary',
            'profile_image'    => 'nullable|mimes:jpeg,jpg,png|max:'.$maxSize,
            //'password'         => 'nullable|min:6',

        ]);
        if($validator->fails()) return response()->json($validator->errors(), 422);
        $verificationController = new VerificationController;

        if($request->email != $user->email){
            $oldEmail = $user->email;
            //$sendVerificationToOldEmailResponce = $verificationController->sendVerificationToOldEmail($user);

            $user->email = $request->email;
            $sendVerificationResponce = $verificationController->sendVerificationToNewEmail($user);
            $user->email = $oldEmail;

            $updatedData =  array(
                'user_id'    => $user->id, 
                'school_id'  => null, 
                'email'      => $request->email,
                'phone'      => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),            
            );

            $deleteOldRecord = UpdateData::where('user_id', $user->id)->whereNull('phone')->delete();
            $insertNewRecord = UpdateData::insert($updatedData);
            $request->merge(["email" => $user->email ]);
        }

        if($request->phone != $user->phone){
            $oldPhone = $user->phone;
            //$sendVerificationToOldPhoneResponce = $verificationController->sendVerificationToOldPhone($user);

            $user->phone = $request->phone;
            $sendVerificationToNewPhoneResponce = $verificationController->sendVerificationToNewPhone($user);
            $user->phone = $oldPhone;

            $updatedData =  array(
                'user_id'    => $user->id, 
                'school_id'  => null, 
                'email'      => null,
                'phone'      => $request->phone,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),            
            );

            $deleteOldRecord = UpdateData::where('user_id', $user->id)->whereNull('email')->delete();
            $insertNewRecord = UpdateData::insert($updatedData);
            $request->merge(["phone" => $user->phone ]);
        }

        $fields = $request->only($user->getTeacherUpdatable());
        $user->fill($fields);

        if(value($request->profile_image)) {
            $user->profile_image = SaveFilesController::updateProfileImage($user, $request);
        }

        if($user->save()) return response()->json(['status' => Lang::get('messages.user_update_success'),], 201);
        else return response()->json(['status' => Lang::get('messages.user_update_fail'),], 200);
    }

    public function uploadProfileImage(Request $request, $id = null)
    {
        if(is_null($id)) $user = Auth::guard()->user();
        else $user = User::find($id);
        if(!$user) throw new NotFoundHttpException(Lang::get('messages.request_user_not_found'));           
                                                                                                                    
        $maxSize = Config::get('filepaths.max_file_sizes.user_profile_image');
        $validator = Validator::make($request->all(), [
            'profile_image' => 'required|mimes:jpeg,jpg,png|max:'.$maxSize,
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        if(value($request->profile_image)) {
            $user->profile_image = SaveFilesController::updateProfileImage($user, $request);
        }

        if($user->save()) return response()->json(['status' => Lang::get('messages.image_updated'),], 201);
        else return response()->json(['status' => Lang::get('messages.error_in_image_updating'),], 200);
    }

    public function uploadCoverImage(Request $request, $id = null)
    {
        if(is_null($id)) $user = Auth::guard()->user();
        else $user = User::find($id);
        if(!$user) throw new NotFoundHttpException(Lang::get('messages.request_user_not_found'));              
                                                                                                                        
        $maxSize = Config::get('filepaths.max_file_sizes.user_cover_image');
        $validator = Validator::make($request->all(), [
            'cover_image' => 'required|mimes:jpeg,jpg,png|max:'.$maxSize,
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        if(value($request->cover_image)) {
            $user->cover_image = SaveFilesController::updateCoverImage($user, $request);
        }

        if($user->save()) return response()->json(['status' => Lang::get('messages.image_updated'),], 201);
        else return response()->json(['status' => Lang::get('messages.error_in_image_updating'),], 200);
    }

    public function changePassword(Request $request)
    {
        $user = Auth::guard()->user();
        $validator = Validator::make($request->all(), [
            'old_password'         => 'required',
            'new_password'         => 'required|max:10|min:6|different:old_password|same:confirm_new_password',
            'confirm_new_password' => 'required|max:10|min:6',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        if(Hash::check($request->old_password, Auth::user()->password)){
            $user->password = $request->new_password;
            if($user->save()) return response()->json(['status' => Lang::get('messages.password_updated'),], 201);
            else return response()->json(['status' => Lang::get('messages.error_in_password_updating'),], 200);
        }
        else return $this->response->error(Lang::get('messages.old_password_is_wrong'), 403);
    }

    public function verifyPhone(Request $request)
    {
        $user = Auth::guard()->user();
        $validator = Validator::make($request->all(), [
            'pin' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $con = new VerificationController;
        return $con->verifyPhoneNumber($user, $request->pin);
    }

    public function verifyChangedPhone(Request $request)
    {
        $user = Auth::guard()->user();
        $validator = Validator::make($request->all(), [
            'pin' => 'required|numeric|digits:6',
        ]);
        if ($validator->fails()) return response()->json($validator->errors(), 422);

        $con = new VerificationController;
        return $con->confirmVerificationToNewPhone($user, $request->pin);
    }

    public function sendAnotherPin()
    {
        $user = Auth::guard()->user();
        $con = new VerificationController;
        return $con->sendAnotherVerificationPin($user);
    }

    public function verifyEmail($id)
    {
        $user = Auth::guard()->user();
        $con = new VerificationController;
        return $con->confirmVerificationEmail($id);
    }

    public function verifyChangedEmail($id)
    {
        $user = Auth::guard()->user();
        $con = new VerificationController;
        return $con->confirmVerificationToNewEmail($id);
    }

    public function letMeOnlineActive()
    {
        return response()->json(['status' => 'ok'], 201);
    }
}
