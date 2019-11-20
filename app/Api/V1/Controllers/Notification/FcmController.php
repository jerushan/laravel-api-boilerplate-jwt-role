<?php
namespace App\Api\V1\Controllers\Notification;

use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;

use FCM;
use LaravelFCM\Message\Topics;
use LaravelFCM\Message\OptionsBuilder;
use LaravelFCM\Message\PayloadDataBuilder;
use LaravelFCM\Message\PayloadNotificationBuilder;

class FcmController extends Controller
{
    public function sendNotificationToUser($deviceToken, $arrayData, $platform=1)
    {
        $optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60*20);

		$title = (array_key_exists('user_name', $arrayData)) ? $arrayData['user_name'] : $arrayData['notification_message'];

		if(array_key_exists('user_image', $arrayData)){
            $path  = $arrayData['user_image'];
            $imagePath = (file_exists($path)) ? $path : URL::asset('images/users/driver3.png');
        }
        elseif(array_key_exists('image', $arrayData)){
            $path  = $arrayData['image'];
            $imagePath = (file_exists($path)) ? $path : URL::asset('images/users/driver3.png');
        }
        else $imagePath = URL::asset('images/users/driver3.png');

		$notificationBuilder = new PayloadNotificationBuilder($title);
		$notificationBuilder->setBody($arrayData['notification_message'])
							->setIcon($imagePath)
							->setSound('default');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($arrayData);

		$option       = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data         = $dataBuilder->build();

		$downstreamResponse = FCM::sendTo($deviceToken, $option, $notification, $data);

		$downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();

		$downstreamResponse->tokensToDelete();
		$downstreamResponse->tokensToModify();
		$downstreamResponse->tokensToRetry();
    }

    public function sendNotificationToUsers($deviceToken, $arrayData, $platform=1)
    {
        $optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60*20);

		$title = (array_key_exists('user_name', $arrayData)) ? $arrayData['user_name'] : $arrayData['notification_message'];

		if(array_key_exists('user_image', $arrayData)){
            $path  = $arrayData['user_image'];
            $imagePath = (file_exists($path)) ? $path : URL::asset('images/users/driver3.png');
        }
        elseif(array_key_exists('image', $arrayData)){
            $path  = $arrayData['image'];
            $imagePath = (file_exists($path)) ? $path : URL::asset('images/users/driver3.png');
        }
        else $imagePath = URL::asset('images/users/driver3.png');

		$notificationBuilder = new PayloadNotificationBuilder($title);
		$notificationBuilder->setBody($arrayData['notification_message'])
							->setIcon($imagePath)
							->setSound('default');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($arrayData);

		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$downstreamResponse = FCM::sendTo($deviceToken, $option, $notification, $data);

		$downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();

		$downstreamResponse->tokensToDelete();
		$downstreamResponse->tokensToModify();
		$downstreamResponse->tokensToRetry();
		$downstreamResponse->tokensWithError();
    }

    public function sendNotificationAlertToUsers($deviceToken, $arrayData)
    {
        $optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60*20);

		$title = "HeyWay";
		$notificationBuilder = new PayloadNotificationBuilder($title);
		$notificationBuilder->setBody($arrayData['alert_type'])
							->setSound('default');

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($arrayData);

		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$downstreamResponse = FCM::sendTo($deviceToken, $option, $notification, $data);

		return $downstreamResponse->numberSuccess();
		// $downstreamResponse->numberFailure();
		// $downstreamResponse->numberModification();

		// $downstreamResponse->tokensToDelete();
		// $downstreamResponse->tokensToModify();
		// $downstreamResponse->tokensToRetry();
		// $downstreamResponse->tokensWithError();
    }

    public function sendMessageToATopic($topic)
    {
        $notificationBuilder = new PayloadNotificationBuilder('my title');
		$notificationBuilder->setBody('Hello world')->setSound('default');

		$notification = $notificationBuilder->build();

		$topic = new Topics();
		$topic->topic('news');

		$topicResponse = FCM::sendToTopic($topic, null, $notification, null);

		$topicResponse->isSuccess();
		$topicResponse->shouldRetry();
		$topicResponse->error();
    }

    public function sendMessageToMultipleTopics($topic)
    {
        $notificationBuilder = new PayloadNotificationBuilder('my title');
		$notificationBuilder->setBody('Hello world')->setSound('default');

		$notification = $notificationBuilder->build();

		$topic = new Topics();
		$topic->topic('news')->andTopic(function($condition) {
			$condition->topic('economic')->orTopic('cultural');
		});

		$topicResponse = FCM::sendToTopic($topic, null, $notification, null);

		$topicResponse->isSuccess();
		$topicResponse->shouldRetry();
		$topicResponse->error();
    }
    
    public function sendNotificationToUsersTest()
    {
    	$arrayData =  array(
			'icon'                 => 'https://backend.tittam.com/public/images/users/driver3.png', 
			'image'                => 'https://backend.tittam.com/public/images/users/driver3.png', 
			'notification_message' => 'Hi,', 
        );

    	$deviceToken = Device::where('platform', 'android')->pluck('device_token')->toArray();
        $optionBuilder = new OptionsBuilder();
		$optionBuilder->setTimeToLive(60*20);

		$notificationBuilder = new PayloadNotificationBuilder("HeyWay");
		$notificationBuilder->setBody("Test Notification")
							->setSound('default')
							->setIcon("https://backend.tittam.com/public/images/users/driver3.png");

		$dataBuilder = new PayloadDataBuilder();
		$dataBuilder->addData($arrayData);

		$option = $optionBuilder->build();
		$notification = $notificationBuilder->build();
		$data = $dataBuilder->build();

		$downstreamResponse = FCM::sendTo($deviceToken, $option, $notification, $data);

		$downstreamResponse->numberSuccess();
		$downstreamResponse->numberFailure();
		$downstreamResponse->numberModification();

		$downstreamResponse->tokensToDelete();
		$downstreamResponse->tokensToModify();
		$downstreamResponse->tokensToRetry();
		$downstreamResponse->tokensWithError();

		return $downstreamResponse->numberSuccess();
    }
}