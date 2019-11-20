<?php
namespace App\Api\V1\Controllers\Notification;

use Pusher\Pusher;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PusherController extends Controller
{
    public function sendNotification($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        //$receiverToken = $pusherNotificationData['receiver_token'];
        $receiverToken = '960c52277915a08118a59c159adeb130';
        if($pusher->trigger($receiverToken, 'admin_payment_request_notification', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function sendPaymentAcceptNotification($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'admin_payment_request_accept_notification', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function sendPaymentPaidNotification($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );

        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'admin_payment_request_paid_notification', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function clubStatusNotification($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'club_status_notification', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function monthlyPaymentNotification($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        if($pusher->trigger('notify', 'monthly_subcription_status_notification', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function informTeacherCancelBooking($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );

        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'teacher_cancel_booking', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }
    
    public function informStudentCancelBooking($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );

        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );

        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'teacher_cancel_booking', $pusherNotificationData))
        {
            return true;
        }
        else return false;
    }

    public function sendMessageToUser($pusherNotificationData)
    {
        //Remember to change this with your cluster name.
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );
 
       //Remember to set your credentials below.
        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'sendMessagesToUser', $pusherNotificationData)){
            return true;
        }
        else return false;
    }

    public function commonPusherController($pusherNotificationData)
    {
        $options = array(
            'cluster' => 'ap2', 
            'encrypted' => true
        );
 
        $pusher = new Pusher(
            'e9455c16bc5f0d6c4547',
            'e53a15e66982f653d355',
            '666000',
            $options
        );
        
        $receiverToken = $pusherNotificationData['receiver_token'];
        if($pusher->trigger($receiverToken, 'commonBladeUserFunction', $pusherNotificationData)){
            return true;
        }
        else return false;
    }
}
