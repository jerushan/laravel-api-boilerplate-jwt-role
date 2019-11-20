<?php
namespace App\Api\V1\Controllers\Notification;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Api\V1\Controllers\Common\FcmController;
use App\Api\V1\Controllers\Common\PushbotController;

class SendNotificationController extends Controller
{
	private $iosNotiSender;
	private $androidNotiSender;
	public function __construct()
    {
        $this->iosNotiSender = new PushbotController();
        $this->androidNotiSender = new PushbotController();
    }

    public function sendNotificationToUser($userId, $arrayData, $whereApp='app_type <> ""')
    {
    	$devices = Device::where('user_id', $userId)->where('platform', 'android')->whereRaw($whereApp)->get();
        if($devices->count()){
        	$deviceTokens = $devices->pluck('device_token')->toArray();
        	return $sendAndroidRes = $this->sendNotificationToAndroid($deviceTokens, $arrayData);
        } 
        
        $devices = Device::where('user_id', $userId)->where('platform', 'ios')->whereRaw($whereApp)->get();
        if($devices->count()){
        	$deviceToken = $devices->pluck('device_token')->toArray();
        	return $sendAndroidRes = $this->sendNotificationToIos($deviceToken, $arrayData);
        }

        return true;
    }

    public function sendNotificationToIos($deviceTokens, $arrayData)
    {
        return $this->iosNotiSender->sendNotificationToUser($deviceTokens, $arrayData, 0);
    }

    public function sendNotificationToAndroid($deviceTokens, $arrayData)
    {
    	return $this->androidNotiSender->sendNotificationToUser($deviceTokens, $arrayData, 1);
    }
}