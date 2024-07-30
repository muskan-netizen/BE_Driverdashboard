<?php
namespace App\Traits;
use DB, Log;
use Illuminate\Support\Collection;
use App\Model\{Client, ClientPreference, User, Agent, Order, PaymentOption, PayoutOption, AgentPayout};
use App\Services\FirebaseService;
use Kawankoding\Fcm\Fcm;

trait sendCustomNotification{

    //------------------------------Function created by surendra singh--------------------------//
    public function sendnotification($item, $client_preferences)
    {
        $new = [];
        array_push($new, $item['device_token']);
        if(isset($new)){
            $data = [
                "registration_ids" => is_array($item['device_token']) ? $item['device_token'] : array($item['device_token']),//$item['device_token'],
                "notification" => [
                    'title' => 'Pickup Request',
                    'body' => 'Check All Details For This Request In App',
                    'sound' => 'notification.mp3',
                    "android_channel_id" => "Royo-Delivery",
                ],
                // "data" => [
                //     'title' => 'Pickup Request',
                //     'body' => 'Check All Details For This Request In App',
                //     'data' => json_encode($item),
                //     'soundPlay' => true,
                //     'show_in_foreground' => true,
                // ],
                "priority" => "high"
            ];

            
            $response = FirebaseService::sendNotification($data,$item);
            // $fcm_server_key = !empty($client_preferences->fcm_server_key)? $client_preferences->fcm_server_key : 'null';

            // $fcmObj = new Fcm($fcm_server_key);
            // $fcm_store = $fcmObj->to($new) // $recipients must an array
            //                 ->priority('high')
            //                 ->timeToLive(0)
            //                 ->data($data)
            //                 ->notification([
            //                     'title'              => (empty($data['title']))?'Pickup Request':$data['title'],
            //                     'body'               => (empty($data['body']))?'Check All Details For This Request In App':$data['body'],
            //                     'sound'              => 'notification.mp3',
            //                     'android_channel_id' => 'Royo-Delivery',
            //                     'soundPlay'          => true,
            //                     'show_in_foreground' => true,
            //                 ])
            //                 ->send();
        }
    }
    
    public function sendBidNotification($item, $client_preferences)
    {
        $new = [];
        array_push($new, $item['device_token']);
        if(isset($new)){
            $data = [
                "registration_ids" => is_array($item['device_token']) ? $item['device_token'] : array($item['device_token']),//$item['device_token'],
                "notification" => [
                    'title' => 'Pickup Request',
                    'body' => 'Check All Details For This Request In App',
                    'sound' => 'notification.mp3',
                    "android_channel_id" => "Royo-Delivery",
                ],
                "data" => [
                    'title' => 'Pickup Request',
                    'body' => 'Check All Details For This Request In App',
                    'data' => json_encode($item),
                    'soundPlay' => true,
                    'show_in_foreground' => true,
                ],
                "priority" => "high"
            ];
            $response = FirebaseService::sendNotification($data);
            // $fcm_server_key = !empty($client_preferences->fcm_server_key)? $client_preferences->fcm_server_key : 'null';
            
            // $fcmObj = new Fcm($fcm_server_key);
            // $fcm_store = $fcmObj->to($new) // $recipients must an array
            // ->priority('high')
            // ->timeToLive(0)
            // ->data($data)
            // ->notification([
            //     'title'              => (empty($data['title']))?'Pickup Request':$data['title'],
            //     'body'               => (empty($data['body']))?'Check All Details For This Request In App':$data['body'],
            //     'sound'              => 'default',
            //     'android_channel_id' => 'Royo-Delivery',
            //     'soundPlay'          => true,
            //     'show_in_foreground' => true,
            // ])
            // ->send();            
        }
    }
}
