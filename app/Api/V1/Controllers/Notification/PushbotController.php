<?php

namespace App\Api\V1\Controllers\Notification;

use GuzzleHttp\Psr7;
use Pushbots\PushbotsClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ServerException;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Device;
use Tymon\JWTAuth\JWTAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use App\Http\Controllers\Controller;
use Tymon\JWTAuth\Exceptions\JWTException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class PushbotController extends Controller
{
	public $PUSHBOTS_APP_ID;
	public $PUSHBOTS_APP_SECRET;
    public function __construct()
    {
    	$this->PUSHBOTS_APP_ID = '5dc1138db7941260ca49ee92';
    	$this->PUSHBOTS_APP_SECRET = '49d4f0bb4162d98a6572cecf55b9c0b5';
    }

    public function sendNotificationToUser($deviceTokens, $arrayData, $platform=1)
    {
    	$body = $arrayData['notification_message'];
    	$title = (array_key_exists('user_name', $arrayData)) ? $arrayData['user_name'] : $arrayData['notification_message'];

		$data = [
			"topic"      => "welcome_campaign",
			"platform"   => $platform, 
			"recipients" => [
				"tokens" 	=> $deviceTokens,
			],
			"message" => [
				"title"   => $title,
				"body"    => $body,
				"payload" => $arrayData,
			]
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.pushbots.com/3/push/transactional');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$headers = array();
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'X-Pushbots-Appid: 5dc1138db7941260ca49ee92';
		$headers[] = 'X-Pushbots-Secret: 49d4f0bb4162d98a6572cecf55b9c0b5';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
		    return 'Error:' . curl_error($ch);
		}
		curl_close($ch);
		return $result;
    }

    public function sendNotificationToUsers($deviceTokens, $arrayData, $platforms=[1])
    {
    	$body = $arrayData['notification_message'];
    	$title = (array_key_exists('user_name', $arrayData)) ? $arrayData['user_name'] : $arrayData['notification_message'];

		$data = [
			"topic"     => "welcome_campaign",
			"language"  => "en",
			"platforms" => $platforms, 
			"recipients" => [
				"tokens" 	=> $deviceTokens,
			],
			"message" => [
				"title"   => $title,
				"body"    => $body,
				"payload" => $arrayData,
			]
		];

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'http://api.pushbots.com/3/push/campaign');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

		$headers = array();
		$headers[] = 'Content-Type: application/json';
		$headers[] = 'X-Pushbots-Appid: 5dc1138db7941260ca49ee92';
		$headers[] = 'X-Pushbots-Secret: 49d4f0bb4162d98a6572cecf55b9c0b5';
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$result = curl_exec($ch);
		if (curl_errno($ch)) {
		    return 'Error:' . curl_error($ch);
		}
		curl_close($ch);
		return $result;
    }

    public function testData(Request $request)
    {
    	$devices = Device::where('platform', 'android')->get();
        if($devices->count()){
        	$deviceTokens = $devices->pluck('device_token')->toArray();

        	  $data =[
					"topic"      => "welcome_campaign",
					"platform"   => 1, 
					"recipients" => [
						"tokens" 	=> $deviceTokens,
					],
					"message" => [
						"title"=> $request->title,
						"body" => $request->body,
						"payload" => ["key"=>"value"],
					]
				];

        	$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, 'http://api.pushbots.com/3/push/transactional');
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$headers = array();
			$headers[] = 'Content-Type: application/json';
			$headers[] = 'X-Pushbots-Appid: 5dc1138db7941260ca49ee92';
			$headers[] = 'X-Pushbots-Secret: 49d4f0bb4162d98a6572cecf55b9c0b5';
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$result = curl_exec($ch);
			if (curl_errno($ch)) {
			    return 'Error:' . curl_error($ch);
			}
			curl_close($ch);
			return $result;
        }
        else return "no_devices";
    }
}