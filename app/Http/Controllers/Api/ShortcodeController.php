<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseController;
use App\Model\{Client,ClientPreference, Currency};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\User;
use Config;
use Validation;
use DB;

class ShortcodeController extends BaseController
{
    /**
     * Get Company ShortCode
     *
     */
    public function validateCompany(Request $request)
    {
        $client = Client::where('is_deleted', 0)->where('code', $request->shortCode)->select('id','country_id', 'name', 'phone_number', 'email', 'database_name', 'timezone', 'custom_domain', 'database_host', 'database_port', 'database_username', 'database_password', 'logo', 'dark_logo', 'company_name', 'company_address', 'is_blocked', 'socket_url')->with('getCountrySet','getPreference')->first();
        //$client_id = ClientPreference::where('client_id', $request->shortCode)->first();
        if (!$client) {
            return response()->json([
                'error' => 'Company not found',
                'message' => 'Invalid short code. Please enter a valid short code.'], 404);
        }
        if ($client->is_blocked == 1) {
            return response()->json([
                'error' => 'Blocked Company',
                'message' => 'Company has been blocked. Please contact administration.'], 404);
        }
       
        $img = env('APP_URL').'/assets/images/default_image.png';

        if (file_exists(public_path().'/assets/images/'.$client->logo)) {
            $img = public_path().'/assets/images/'.$client->logo;
        }
        if ($client->logo) {
            $client->logo = \Storage::disk("s3")->url($client->logo);
        }

        if($client->dark_logo){
            $client->dark_logo = \Storage::disk("s3")->url($client->dark_logo);
        }
        


        if (!empty($client)) {
            $database_name =  'db_'.$client->database_name;
            $database_host = !empty($client->database_host) ? $client->database_host : env('DB_HOST','127.0.0.1');
            $database_port = !empty($client->database_port) ? $client->database_port : env('DB_PORT','3306');
            $database_username = !empty($client->database_username) ? $client->database_username : env('DB_USERNAME','cbladmin');
            $database_password = !empty($client->database_password) ? $client->database_password : env('DB_PASSWORD','');
            $default = [
                'driver' => env('DB_CONNECTION', 'mysql'),
                'host' => $database_host,
                'port' => $database_port,
                'database' => $database_name,
                'username' => env('DB_USERNAME'),
                'password' => env('DB_PASSWORD'),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'prefix_indexes' => true,
                'strict' => false,
                'engine' => null
            ];
            Config::set("database.connections.$database_name", $default);
            Config::set("client_connected", true);
            DB::setDefaultConnection($database_name);

            DB::purge($database_name);
           
            $client_db_data = Client::where('is_deleted', 0)->where('code',$request->shortCode)->select('id', 'code')->with('getPreference')->first();
            $getAdditionalPreference = getAdditionalPreference([
                'pickup_type',
                'drop_type',
                'is_attendence',
                'idle_time',
                'hold_to_start',
                'hold_to_arrive',
                'hold_to_pick',
                'hold_to_complete',
            ]);
            if(!empty($client_db_data)){
                $client->client_db_id = $client_db_data->id;
                $client->client_db_code = $client_db_data->code;
                $client->is_driver_slot = !empty($client_db_data->getPreference) && isset($client_db_data->getPreference->is_driver_slot) ? $client_db_data->getPreference->is_driver_slot : 0;
                $client->is_freelancer = !empty($client_db_data->getPreference) && isset($client_db_data->getPreference->is_freelancer) ? $client_db_data->getPreference->is_freelancer : 0;
                $client->is_road_side_toggle = !empty($client_db_data->getPreference) && isset($client_db_data->getPreference->is_road_side_toggle) ? $client_db_data->getPreference->is_road_side_toggle : 0;
                $client['isAttendence'] = ($getAdditionalPreference['is_attendence'] == 1) ? $getAdditionalPreference['is_attendence'] : 0;
                $Currency = Currency::where('id', $client_db_data->getPreference->currency_id)->first();
                $client['currencyCode'] = $Currency->symbol??null;
                $client['distanceUnit'] = $client_db_data->getPreference->distance_unit??null;
                
            }
        }
        unset($client->database_host);
        unset($client->database_port);
        unset($client->database_username);
        unset($client->database_password);
        $client->is_refferal_code_enable = ClientPreference::value('refer_earn_driver_to_driver_toggle');
        $client->distance_in_meter = ClientPreference::value('distance_in_meter');
        $client->getAdditionalPreference = $getAdditionalPreference;
        return response()->json([
            'data' => $client,
            'status' => 200,
            'message' => __('success')
        ]);
    }
}
