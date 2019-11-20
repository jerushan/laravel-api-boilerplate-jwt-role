<?php

namespace App\Api\V1\Controllers\Common;

use Auth;
use Config;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Verification;
use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Lang;
use App\Http\Controllers\Controller;
use \Illuminate\Support\Facades\Crypt;
use App\Api\V1\Controllers\SmsController;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class VerificationController extends Controller
{
    use Helpers;
    public function __construct()
    {
        
    }
//------------------------------------------ Send Verification to phone & verify phone ------------------------------------------//
    public function sendVerificationPin($user)
    {
        $pin = mt_rand(100000, 999999);
        $count = Verification::where('code', $pin)->count();

        while (!$count == 0){
            $pin = mt_rand(100000, 999999);
            $count = Verification::where('code', $pin)->count();
        }

        $verificationData =  array(
            'user_id'    => $user->id, 
            'code'       => $pin,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        );

        if(Verification::insert($verificationData)){
            $sendSmsController = new SmsController;
            $message = Lang::get('messages.send_verification_pin');
            $message = str_replace('APPNAME', ucfirst(env('APP_NAME')) , $message);
            $message = str_replace('PIN', $pin, $message);

            $userPhoneNumber = $user->country_code.$user->phone;
            return $sendSmsController->sendAnyMessages($message, $userPhoneNumber);
        }
        else return Lang::get('messages.error_in_inserting_verification_data');
    }

    public function sendAnotherVerificationPin($user)
    {   
        $currentUserId = $user->id;
        $deleteOldRecords = Verification::where('user_id' , $currentUserId)->delete();
        $sendSmsStatus = $this->sendVerificationPin($user);

        if($sendSmsStatus == 'messageSend'){
            return response()->json([
                'status'      => 'ok',
                'status_code' => 201,
            ], 201);
        }
        else return response()->json(['pin' => 'error_in_send_pin']);
    }

    public function verifyPhoneNumber($currentUser, $pin)
    {
        if(! $currentUser->phone_verify == 1){
            $currentUserId = $currentUser->id;
            $verification = Verification::where('user_id', $currentUserId)->get()->last();
            if ($verification->code == $pin){
                $updateRes = User::find($currentUserId)->update(['phone_verify' => 1]);
                $deleteRes = Verification::find($verification->id)->delete();

                return response()->json([
                    'status'         => 'ok',
                    'status_message' => Lang::get('messages.phone_verified_success'),
                    'status_code'    => 201,
                ], 201);
            }
            else return response()->json(['status' => Lang::get('messages.account_incorrect_pin'),], 422);
        }
        else  return response()->json(['status' => Lang::get('messages.account_already_verified'),], 200);
    }

//------------------------------------------ ------------------------------------- --------------------------------------------//



//-------------------------------------------- Send Verification emails & verify  ---------------------------------------------//
    public function sendVerificationEmail($user)
    {
        $userEmail = $user->email;
        if($userEmail){
            $userType = $user->role;
            $url = env('APP_FRONT_END_URL');
            $encryptedEmail = $this->emailEncrypt($userEmail);
            $emailConfirlLink =  $url.'/confirmation/'.$encryptedEmail;

            $emailHeading = 'Well Come to '.env('APP_NAME');

            $emailContent = '<tr>
                                <td class="content-block">Dear <strong>'.$user->name.'</strong>,</td>
                            </tr>
                            <tr>
                                <td class="content-block">
                                    Thank you for choosing <strong>'.env('APP_NAME').'</strong> ! Your '.env('APP_NAME').' account application has been received and it is ready to use.

                                </td>
                            </tr>
                            <tr>
                                <td class="content-block">
                                    <a href="'.$emailConfirlLink.'" class="btn-primary">Confirm your email</a>
                                </td>
                            </tr>
                            <tr>
                                <td class="content-block">Thank you for choosing '.env('APP_NAME').' Inc.</td>
                            </tr>';

            $emailData =  array(
                'name'          => env('APP_NAME'), 
                'subject'       => 'Confirm your email !', 
                'from_address'  => env('MAIL_FROM_ADDRESS'), 
                'user_name'     => $user->name, 
                'confirm_link'  => $emailConfirlLink, 
                'email_content' => $emailContent, 
                'email_heading' => $emailHeading, 
            );

            try{
                $sendVerificationEmailResponce = \Mail::to($userEmail)->send(new CommonEmail($emailData));
            }
            catch(\Exception $e){
                
            } 
            
            return $emailConfirlLink;
        }
    } 

    public function sendAnotherEmailVerification($user)
    {
        $responce = $this->sendVerificationEmail($user);

        return response()->json([
            'status' => 'ok',
            'status_code' => 201,
        ], 201);
    }

    public function confirmVerificationEmail($id)
    {
        $email = $this->emailDecrypt($id);
        $userCount = User::where('email' , $email)->count();
        if($userCount) {
            $user = User::where('email' , $email)->first();
            if($user->email_verify != 1){
                $user->email_verify = 1;
                if($user->save()){
                    return response()->json([
                        'status'         => 'ok',
                        'email'          => $email,
                        'status_message' => Lang::get('messages.email_verified_success'),
                        'status_code'    => 201,
                    ], 201);
                }
            } 
            else return $this->response->error(Lang::get('messages.email_already_verified'), 200);
        }
        else return $this->response->error(Lang::get('messages.invalid_url_or_not_found'), 200);
    }
//------------------------------------------ ------------------------------------- ---------------------------------------------//

//-------------------------------------------- Send Pin to emails & verify  ---------------------------------------------//
    public function sendPinToEmail($user, $pin)
    {
        $userEmail = $user->email;
        if($userEmail){
            $emailHeading = 'Reset Password';

            $emailContent = '<tr>
                                <td class="content-block">Dear <strong>'.$user->name.'</strong>,</td>
                            </tr>
                            <tr>
                                <td class="content-block">
                                    We received a request to access your <strong>'.env('APP_NAME').'</strong> Account through your email address. Your <strong>'.env('APP_NAME').'</strong> verification code is: <strong>'.$pin.'</strong>
                                </td>
                            </tr>
                            <tr>
                                <td class="content-block">Thank you for choosing '.env('APP_NAME').' Inc.</td>
                            </tr>';

            $emailData =  array(
                'name'          => env('APP_NAME'), 
                'subject'       => $emailHeading, 
                'from_address'  => env('MAIL_FROM_ADDRESS'), 
                'user_name'     => $user->name, 
                'email_content' => $emailContent, 
                'email_heading' => $emailHeading, 
            );

            $sendRes = "false";

            try{
                $sendVerificationEmailResponce = \Mail::to($userEmail)->send(new CommonEmail($emailData));
                $sendRes = "true";
            }
            catch(\Exception $e){
                
            } 
            
            return $sendRes;
        }
    } 

//------------------------------------------ ------------------------------------- ---------------------------------------------//

//-------------------------------------------- Send Pin to phone & verify  ---------------------------------------------//
    public function sendPinToPhone($user, $pin)
    {
        $userPhone = $user->phone;
        $sendSmsController = new SmsController;

        $message = 'We received a request to access your '.env('APP_NAME').' Account through your phone number. Your '.env('APP_NAME').' verification code is: '.$pin;

        if($sendSmsController->sendAnyMessages($message, $userPhone)){
            $smsStatus = 'true';
        } 
        else $smsStatus = 'false';

        return $smsStatus;
    } 

//------------------------------------------ ------------------------------------- ---------------------------------------------//

//------------------------------------------ Send Verification to Old & New emails ---------------------------------------------//
    public function sendVerificationToNewEmail($user)
    {
        $userEmail = $user->email;
        if($userEmail){
            $url = env('APP_FRONT_END_URL');
            $encryptedEmail = $this->emailEncrypt($userEmail);
            $emailConfirlLink =  $url.'/changeEmail/'.$encryptedEmail;

            $emailData =  array(
                'user_name' => $user->name, 
                'confirm_link'=> $emailConfirlLink, 
            );

            try{
                $sendVerificationToNewEmailResponce = \Mail::to($userEmail)->send(new newChangedEmail($emailData));
            }
            catch(\Exception $e){
                
            } 

            return $emailConfirlLink;
        }
    }

    public function sendVerificationToOldEmail($user)
    {
        $userEmail = $user->email;
        if($userEmail){
            $url = env('APP_FRONT_END_URL');
            $encryptedEmail = $this->emailEncrypt($userEmail);
            $emailConfirlLink =  $url.'/confirmation/'.$encryptedEmail;

            $emailData =  array(
                'user_name' => $user->name, 
                'confirm_link'=> $emailConfirlLink, 
            );

            try{
                $sendVerificationToOldEmailResponce = \Mail::to($userEmail)->send(new oldChangedEmail($emailData));
            }
            catch(\Exception $e){
            
            }  
            
            return $emailConfirlLink;
        }
    }

    public function confirmVerificationToNewEmail($id)
    {
        $email = $this->emailDecrypt($id);
        $changeEmailRequest = UpdateData::where('email' , $email)->count();
        if($changeEmailRequest){
            $changeEmailData = UpdateData::where('email' , $email)->get()->last();
            $user = User::find($changeEmailData->user_id);
            $user->email_verify = 1;
            $user->email = $email;
            if($user->save()){
                $deleteEmailData = UpdateData::where('email' , $email)->delete();
                return response()->json([
                    'status'         => 'ok',
                    'email'          => $email,
                    'status_message' => Lang::get('messages.email_verified_and_changed_success'),
                    'status_code'    => 201,
                ], 201);
            }
        }
        else return $this->response->error(Lang::get('messages.invalid_url_or_not_found'), 200);
    }

    
//------------------------------------------ ------------------------------------- ---------------------------------------------//



//--------------------------------------- Send Verification to Old & New phone numbers -----------------------------------------//
    public function sendVerificationToNewPhone($user)
    {
        $userPhone = $user->country_code.$user->phone;
        if($userPhone){
            $pin = mt_rand(100000, 999999);
            $count = Verification::where('code', $pin)->count();

            while (!$count == 0){
                $pin = mt_rand(100000, 999999);
                $count = Verification::where('code', $pin)->count();
            }

            $verificationData =  array(
                'user_id'    => $user->id, 
                'code'       => $pin,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            );

            if(Verification::insert($verificationData)){
                $sendSmsController = new SmsController;
                $message = "Your verification code for APPNAME is ".$pin;
                $message = str_replace('APPNAME', ucfirst(env('APP_NAME')) , $message);

                if($sendSmsController->sendAnyMessages($message, $userPhone)){
                    $smsStatus = Lang::get('messages.sms_send_success');
                } 
                else $smsStatus = Lang::get('messages.sms_send_fail');

                return $smsStatus;
            }
        }
    }

    public function sendVerificationToOldPhone($user)
    {
        $userPhone = $user->country_code.$user->phone;
        if($userPhone){
            $sendSmsController = new SmsController;
            $message = "This message is to confirm that your APPNAME account phone number has been successfully changed. Once you confirm your new phone number, it will be updated.";
            $message = str_replace('APPNAME', ucfirst(env('APP_NAME')) , $message);

            if($sendSmsController->sendAnyMessages($message, $userPhone)){
                $smsStatus = Lang::get('messages.sms_send_success');
            } 
            else $smsStatus = Lang::get('messages.sms_send_fail');
            
            return $smsStatus;
        }
    }

    public function confirmVerificationToNewPhone($user, $pin)
    {
        $verification = Verification::where('user_id', $user->id)->get()->last();
        if($verification){
            if ($verification->code == $pin){
                $changedData = UpdateData::where('user_id' , $user->id)->whereNull('email')->get()->last();
                if($changedData){
                    $user->phone = $changedData->phone;
                    $user->phone_verify = 1;
                    $deleteVerification = Verification::where('user_id', $user->id)->delete();
                    if($user->save()){ 
                        $deleteUpdatedData = UpdateData::where('phone' , $changedData->phone)->delete();
                        return response()->json([
                            'status'         => 'ok',
                            'status_message' => Lang::get('messages.phone_changed_success'),
                            'status_code'    => 201,
                        ], 201);
                    } 
                    else return $this->response->error(Lang::get('messages.account_verified_failed'), 200);
                } 
                else return $this->response->error(Lang::get('messages.changed_phone_data_not_found'), 200);
            }
            else return $this->response->error(Lang::get('messages.account_incorrect_pin'), 200);
        }
        else return $this->response->error(Lang::get('messages.changed_phone_data_not_found'), 200);
    }

//--------------------------------------- -------------------------------------------- -----------------------------------------//



//------------------------------------------------------------ Assets ----------------------------------------------------------//
    public function getTheFrontEndUrl($userType)
    {
        switch ($userType) {
            case "superadmin":
                $url = env('APP_FRONT_END_SUPERADMIN_URL');
                break;
            case "admin":
                $url = env('APP_FRONT_END_ADMIN_URL');
                break;
            case "student":
                $url = env('APP_FRONT_END_STUDENT_URL');
                break;
            case "teacher":
                $url = env('APP_FRONT_END_TEACHER_URL');
                break;
            default:
                $url = env('APP_FRONT_END_URL');
        }
        return $url;
    }

    public function emailEncrypt($email)
    {
        $time = time();
        $emailAndTime = $time.$email;
        $encriptedEmail = Crypt::encrypt($emailAndTime);
        return  $encriptedEmail;
    }

    public function emailDecrypt($encriptedEmail)
    {
        $emailAndTime = Crypt::decrypt($encriptedEmail);
        $email = substr($emailAndTime, 10);
        return  $email;
    }
//--------------------------------------- -------------------------------------------- -----------------------------------------//



//---------------------------------------------- Delete phone verification data ------------------------------------------------//
    public function deletePhoneVerificationData()
    {
        $verifications = Verification::get();
        if($verifications){   
            foreach( $verifications as $key => $verification ){
                $differentInDays = 0;
                $now = date('Y-m-d');
                $currentId = $verification->id;
                $currentExpiryDate = Carbon::parse($verification->created_at);
                $differentInDays = $currentExpiryDate->diffInDays($now);

                if (!(($now < $currentExpiryDate) || ($differentInDays == 0))){
                    if($differentInDays >= 2){
                        $responce = Verification::where('id' , $currentId)->delete();
                    }
                }
            }
        }
    }
//--------------------------------------- -------------------------------------------- -----------------------------------------//
}