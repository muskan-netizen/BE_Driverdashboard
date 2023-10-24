<?php

namespace App\Jobs;

use App\Model\Roster;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Config;
use Log;
use Carbon\Carbon;
use App\Model\Client;
use Exception;
use Kawankoding\Fcm\Fcm;

class RosterDelete implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $order_id;
    protected $type;
    protected $driver_id;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($order_id,$type,$driver_id = '')
    {
        $this->order_id  = $order_id;
        $this->type      = $type;
        $this->driver_id = $driver_id;
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $schemaName = 'royodelivery_db';
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
            config(["database.connections.mysql.database" => $schemaName]);
            if($this->type=='B'){
                DB::connection($schemaName)->table('rosters')->where('batch_no',$this->order_id)->where('is_particular_driver','=',0)->delete();
            }else if($this->type=='PD'){
                DB::connection($schemaName)->table('rosters')->where('order_id',$this->order_id)
                ->where('is_particular_driver',0)
                ->delete();
            }else{
                if(empty($this->driver_id)){
                    DB::connection($schemaName)->table('rosters')->where('order_id',$this->order_id)->where('is_particular_driver','=',0)->delete();
                }else{
                    DB::connection($schemaName)->table('rosters')->where('order_id',$this->order_id)->where('is_particular_driver','=',0)->where('driver_id',$this->driver_id)->delete();
                    $this->getData();
                }
            }
            DB::disconnect($schemaName);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    public function getData()
    {
        $schemaName       = 'royodelivery_db';
        $get              =  DB::connection($schemaName)->table('rosters')->where('status',0)->where('order_id',$this->order_id)
        ->leftJoin('roster_details', 'rosters.detail_id', '=', 'roster_details.unique_id')
        ->select('rosters.*', 'roster_details.customer_name', 'roster_details.customer_phone_number',
            'roster_details.short_name','roster_details.address','roster_details.lat','roster_details.long','roster_details.task_count')->orderBy('id','asc')->limit(1)->get();
        $getids           = $get->pluck('id');     
        DB::connection($schemaName)->table('rosters')->where('status',10)->delete();
        if(count($get) > 0){
            //Log::info('getdata count inder'.count($get));
        DB::connection($schemaName)->table('rosters')->whereIn('id',$getids)->delete();
            // DB::connection($schemaName)->table('rosters')->whereIn('id',$newget)->update(['status'=>1]);
            $this->sendnotification($get);
        }
        return;
        
    }
    
    public function sendnotification($recipients)
    {
        try {
            $array = json_decode(json_encode($recipients), true);          
            foreach($array as $item){                
                if(isset($item['device_token']) && !empty($item['device_token'])){
                    $item['title']     = 'Pickup Request';
                    $item['body']      = 'Check All Details For This Request In App';
                    $new = [];
                    $item['notificationType'] = $item['type'];
                    unset($item['type']); // done by Preet due to notification title is displaying like AR in iOS
                    
                    array_push($new,$item['device_token']);                    
                    $clientRecord = Client::where('code', $item['client_code'])->first();
                    $this->seperate_connection('db_'.$clientRecord->database_name);
                    $client_preferences = DB::connection('db_'.$clientRecord->database_name)->table('client_preferences')->where('client_id', $item['client_code'])->first();                    
                    if(isset($new)){
                        try{
                            $fcm_server_key = !empty($client_preferences->fcm_server_key)? $client_preferences->fcm_server_key : 'null';
                            $fcmObj = new Fcm($fcm_server_key);
                            if($item['is_particular_driver'] != 2 ){
                                $fcm_store = $fcmObj->to($new) // $recipients must an array
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
                               //  \Log::info( "fcm" );
                               // \Log::info( $fcm_store );
                            }else{
                                $fcm_store =   $fcmObj
                                ->to([$item['device_token']])
                                ->priority('high')
                                ->timeToLive(0)
                                ->data([
                                    'title' => 'Reminder Order',
                                    'body' => 'Pickup your order #'.$item['order_id'],
                                ])
                                ->notification([
                                    'title' => 'Reminder Order',
                                    'body' => 'Pickup your order #'.$item['order_id'],
                                ])
                                ->send();
                            }
                        }
                        catch(Exception $e){
                            Log::info($e->getMessage());
                        }
                    }
                }
            }
           // sleep(5);
          //  $this->getData();
        } catch (\Exception $ex) {
            \Log::info($ex->getMessage());
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
