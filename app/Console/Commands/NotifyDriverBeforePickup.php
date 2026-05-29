<?php

namespace App\Console\Commands;

use App\Jobs\NotifyDriverBeforePickupJob;
use App\Model\Client;
use App\Model\ClientPreference;
use App\Model\Order;
use Carbon\Carbon;
use Exception;
use Illuminate\Console\Command;
// use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kawankoding\Fcm\Fcm;

class NotifyDriverBeforePickup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notify:pickup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notification To Drivers before Pickup';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $clients = Client::where('status', 1)->get();
        // Log::warning(['clients' => $clients]);

        foreach ($clients as $client) {
            $database_name = 'db_' . $client->database_name;
            $query = "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME =  ?";
            $db = DB::select($query, [$database_name]);
            if ($db) {
                $default = [
                    'driver' => env('DB_CONNECTION', 'mysql'),
                    'host' => config('database.connections.mysql.host'),
                    'port' => config('database.connections.mysql.port'),
                    'database' => $database_name,
                    'username' => config('database.connections.mysql.username'),
                    'password' => config('database.connections.mysql.password'),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => false,
                    'engine' => null
                ];
                \Config::set("database.connections.$database_name", $default);
                $db =DB::setDefaultConnection($database_name);
                // Log::info(['db' => $db]);

                $this->cron($database_name);

                DB::disconnect($database_name);
            } else {
                DB::disconnect($database_name);
            }
        }
    }

    public function cron($database_name)
    {
        $client_preferences = ClientPreference::first();
        $orders = Order::where('is_cab_pooling',2)->where('driver_id','!=',null)->where('scheduled_date_time',Carbon::now()->addMinute(3)->format("Y-m-d H:i"))->get();

        foreach ($orders as $order) {
              
            $data['registration_ids']     = [$order->agent->device_token];
            $data['notification'] = [
                'title'     => 'Reminder Order',
                'body'      => 'Pickup your order #'.$order->order_number
            ];
           $notify =  $this->sendnotification($data,$client_preferences->fcm_server_key);

        }

        // dispatch(new NotifyDriverBeforePickupJob($orders, $client_preferences));

    }

    public function sendnotification($data,$fcmKey)
    {
        if(isset($data)){
            $fcmObj = new Fcm($fcmKey);
            
            return $fcmObj
            ->to($data['registration_ids'])
            ->priority('high')
            ->timeToLive(0)
            ->data([
                'title' => $data['notification']['title'],
                'body' => $data['notification']['body'],
            ])
            ->notification([
                'title' => $data['notification']['title'],
                'body' => $data['notification']['body'],
            ])
            ->send();
        }
    }

}
