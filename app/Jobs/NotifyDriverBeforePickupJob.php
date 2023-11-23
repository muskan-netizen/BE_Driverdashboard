<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Model\Agent;
use Illuminate\Support\Facades\Log;
use App\Model\Client;
use Exception;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Kawankoding\Fcm\Fcm;

class NotifyDriverBeforePickupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */

    public $orders;
    public $client_preferences;

    public function __construct($orders, $client_preferences)
    {
        $this->orders      = $orders;
        $this->client_preferences = $client_preferences;
        $this->handle();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $orders = $this->orders;
        Log::warning(['orders===' => $orders]);

        foreach ($orders as $order) {

            $agent = Agent::find($order->driver_id);
            Log::error(['agent===' => $this->client_preferences]);

            if (!empty($this->client_preferences->fcm_server_key)) {
            Log::error(['fcm_server_key' => $this->client_preferences->fcm_server_key]);
              
         
                $this->sendnotification($order,$this->client_preferences->client_id);

                // DB::table('rosters')->where('driver_id',$order->driver_id)->delete();
                
            }
        }
    }

    public function sendnotification($order,$code)
    {
        try {
            Log::warning(['$order->agent' => $order->agent]);
            if(isset($order->agent->device_token)){

                $item['title']     = 'Pickup Request';
                $item['body']      = 'Check All Details For This Request In App';
                $new = [$order->agent->device_token];

                $item['notificationType'] = "AR";

                $clientRecord = Client::where('code', $code)->first();

                $this->seperate_connection('db_'.$clientRecord->database_name);
                $client_preferences = DB::connection('db_'.$clientRecord->database_name)->table('client_preferences')->where('client_id', $code)->first();
                
                $client_preferences = DB::connection('db_'.$clientRecord->database_name)->table('client_preferences')->where('client_id', $code)->first();

                if(isset($new)){
                    try{
                        $fcm_server_key = !empty($client_preferences->fcm_server_key)? $client_preferences->fcm_server_key : 'null';
                
                        $fcmObj = new Fcm($fcm_server_key);
                        $fcm_store = $fcmObj->to($new) 
                                        ->priority('high')
                                        ->timeToLive(0)
                                        ->data($item)
                                        ->notification([
                                            'title'              => 'Pickup Request',
                                            'body'               => 'Check All Details For This Request In App',
                                            'sound'              => 'notification.mp3',
                                            'android_channel_id' => 'Royo-Delivery',
                                            'soundPlay'          => true,
                                            'show_in_foreground' => true,
                                        ])
                                        ->send();

                                        Log::warning(['was' => $fcm_store]);
                    }
                    catch(Exception $e){
                        Log::info($e->getMessage());
                    }

                }
            }

        } catch (Exception $ex) {
            Log::info($ex->getMessage());
        }
    }

    public function seperate_connection($schemaName){
        $default = [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST'),
            'port' => env('DB_PORT'),
            'database' => $schemaName,
            'username' => env('DB_USERNAME'),
            'password' => env('DB_PASSWORD'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null
        ];

        Config::set("database.connections.$schemaName", $default);

    }
}
