<?php

namespace App\Http\Controllers\Api;

use App\DriverRefferal;
use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Model\{Agent, AgentLog, AllocationRule, Client, ClientPreference, Cms, Order, Task, TaskProof, Timezone, User, PaymentOption, UserBidRideRequest, DeclineBidRequest, DriverGeo, SmtpDetail, UserRating};
use Validation;
use DB, Log;
use Illuminate\Support\Facades\Storage;
use App\Model\Roster;
use Config;
use Illuminate\Support\Facades\URL;
use GuzzleHttp\Client as GClient;
use App\Traits\FormAttributeTrait;
use App\Traits\{GlobalFunction};
use Exception;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class ActivityController extends BaseController
{
    use FormAttributeTrait;
    use GlobalFunction;
    /**
     * Store/Update Client Preferences
     */
    public function clientPreferences()
    {
        $preferences = ClientPreference::with('currency')->where('id', 1)->first();

        $payment_codes = ['stripe'];
        $payment_creds = PaymentOption::select('code', 'credentials')->whereIn('code', $payment_codes)->where('status', 1)->get();
        if ($payment_creds) {
            foreach ($payment_creds as $creds) {
                $creds_arr = json_decode($creds->credentials);
                if ($creds->code == 'stripe') {
                    $preferences->stripe_publishable_key = (isset($creds_arr->publishable_key) && (!empty($creds_arr->publishable_key))) ? $creds_arr->publishable_key : '';
                }
            }
        }
        return response()->json([
            'message' => '',
            'data' => $preferences,
            'status' => 200
        ]);
    }

    /**
     * update driver availability status if 0 than 1 if 1 than 0

     */
    public function updateDriverStatus(Request $request)
    {
        $agent               = Agent::findOrFail(Auth::user()->id);
        $agent->is_available = ($agent->is_available == 1) ? 0 : 1;
        $agent->device_token = ((!empty($request->device_token) && $agent->is_available == 1) ? $request->device_token : '');
        $agent->update();

        // if driver is offline so do not send push notification-------------start--code---
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
        if($agent->is_available == 1){
            DB::connection($schemaName)->table('rosters')->where('created_at', '<', date('Y-m-d H:i:s'))->where(['driver_id'=>Auth::user()->id,'device_type'=>Auth::user()->device_type])->delete();
        }else{
            DB::connection($schemaName)->table('rosters')->where(['driver_id'=>Auth::user()->id,'device_type'=>Auth::user()->device_type])->delete();
        }
        DB::disconnect($schemaName);
        // if driver is offline so do not send push notification---------------end--code---

        return response()->json([
            'message' => __('Status updated Successfully'),
            'data' => array('is_available' => $agent->is_available),
            'status' => 200
        ]);
    }


    //function to enable/disable availability for cab pooling for drivers 
    public function updateDriverCabPoolingStatus(Request $request)
    {
        try{
            $agent               = Agent::findOrFail(Auth::user()->id);
            $agent->is_pooling_available = ($request->is_pooling_available == 1) ? 1 : 0;
            $agent->update();

            return response()->json([
                'message' => __('Cab Pooling Status updated Successfully'),
                'data' => array('is_pooling_available' => $agent->is_pooling_available),
                'status' => 200
            ]);
        }
        catch(Exception $e){
            return response()->json([
                'data' => [],
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Login user and create token
     *
     */
    public function tasks(Request $request)
    {
        $header = $request->header();
        $client_code = Client::where('database_name', $header['client'][0])->first();
        $preferences = ClientPreference::with('currency')->where('client_id', $client_code->code)->first();
        $client_currency = $preferences->currency;
        $tz = new Timezone();
        $client_code->timezone = $tz->timezone_name($client_code->timezone);
        $start     = Carbon::now($client_code->timezone ?? 'UTC')->startOfDay();
        $end       = Carbon::now($client_code->timezone ?? 'UTC')->endOfDay();
        $utc_start = Carbon::parse($start . $client_code->timezone ?? 'UTC')->tz('UTC');
        $utc_end   = Carbon::parse($end . $client_code->timezone ?? 'UTC')->tz('UTC');

        $id     = Auth::user()->id;

        $all    = $request->all;
        $tasks   = [];

        if ($all == 1) {
            $orders = Order::where('driver_id', $id)->where('status', 'assigned')->orderBy("order_time","ASC")->orderBy("id","ASC")->pluck('id')->toArray();
        } else {
            $orders = Order::where('driver_id', $id)->where('order_time', '>=', $utc_start)->where('order_time', '<=', $utc_end)->where('status', 'assigned')->orderBy("order_time","ASC")->orderBy("id","ASC")->pluck('id')->toArray();
        }


        if (count($orders) > 0) {

            if($preferences->is_dispatcher_allocation == 1)
            {
                $tasks = Task::whereIn('order_id', $orders)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)->where('driver_id', $id)
                ->with(['location','tasktype','order.customer','order.customer.resources','order.task.location','order.additionData','order.waitingTimeLogs'])->orderBy("order_id", "DESC")
                ->orderBy("id","ASC")
                ->get();
            }else{
                $tasks = Task::whereIn('order_id', $orders)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)
                ->with(['location','tasktype','order.customer','order.customer.resources','order.task.location','order.additionData','order.waitingTimeLogs'])->orderBy("order_id", "DESC")
                ->orderBy("id","ASC")
                ->get();
            }
           
           
            if (count($tasks) > 0) {
                //sort according to task_order
                $tasks = $tasks->toArray();
                if ($tasks[0]['task_order'] !=0) {
                    usort($tasks, function ($a, $b) {
                        return $a['task_order'] <=> $b['task_order'];
                    });
                }
            }
        }

        return response()->json([
            'data' => $tasks,
            'currency' => $client_currency,
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

    /**
     * Login user and create token
     *

     */
    public function profile(Request $request)
    {
        $agent = Agent::where('id', Auth::user()->id)->first();
        return response()->json([
        'data' => $agent,
        'status' => 200,
        'message' => __('success')
       ], 200);
    }


    public function updateProfile(Request $request)
    {
        $saved = Agent::where('id', Auth::user()->id)->first();

        $header = $request->header();
        $client_code = Client::where('database_name', $header['client'][0])->first('code');
        $getFileName = '';
        // Handle File Upload
        if (isset($request->profile_picture)) {
            if ($request->hasFile('profile_picture')) {
                $folder = str_pad($client_code->code, 8, '0', STR_PAD_LEFT);
                $folder = 'client_'.$folder;
                $file = $request->file('profile_picture');
                $file_name = uniqid() .'.'.  $file->getClientOriginalExtension();
                $s3filePath = '/assets/'.$folder.'/agents' . $file_name;
                $path = Storage::disk('s3')->put($s3filePath, $file, 'public');
                $getFileName = $path;
            }
        } else {
            $getFileName = $saved->profile_picture;
        }

        $agent                   = Agent::find(Auth::user()->id);
        $agent->name             = isset($request->name)?$request->name:$saved->name;
        $agent->profile_picture  = $getFileName;
        $agent->vehicle_type_id  = $request->vehicle_type_id;
        $agent->make_model       = isset($request->make_model)?$request->make_model:$saved->make_model;
        $agent->plate_number     = isset($request->plate_number)?$request->plate_number:$saved->plate_number;
        $agent->phone_number     = isset($request->phone_number)?$request->phone_number:$saved->phone_number;
        $agent->color            = isset($request->color)?$request->color:$saved->color;

        if ($agent->save()) {
            return response()->json([
                'message' => __('Profile Updated Successfully'),
            ], 200);
        } else {
            return response()->json([
                'message' => __('Sorry Something Went Wrong'),
            ], 404);
        }
    }

    public function agentLog(Request $request)
    {
        $user_id = Auth::user()->id;
        $header = $request->header();
        $client_code = Client::where('database_name', $header['client'][0])->first();
        $preferences = ClientPreference::with('currency')->first();
        $tz = new Timezone();
        $client_code->timezone = $tz->timezone_name($client_code->timezone);
        $start     = Carbon::now($client_code->timezone ?? 'UTC')->startOfDay();
        $end       = Carbon::now($client_code->timezone ?? 'UTC')->endOfDay();
        $utc_start = Carbon::parse($start . $client_code->timezone ?? 'UTC')->tz('UTC');
        $utc_end   = Carbon::parse($end . $client_code->timezone ?? 'UTC')->tz('UTC');

        $tasks   = [];
        $data =  [
            'agent_id'          => Auth::user()->id,
            'lat'               => $request->lat,
            'long'              => $request->long,
            'battery_level'     => $request->battery_level,
            'os_version'        => $request->os_version,
            'app_version'       => $request->app_version,
            'current_speed'     => $request->current_speed,
            'on_route'          => $request->on_route,
            'device_type'       => ucwords($request->device_type),
            'heading_angle'     => $request->heading_angle ?? 0
           
        ];

        $is_cab_pooling_toggle = isset($preferences->is_cab_pooling_toggle)?$preferences->is_cab_pooling_toggle:0;
        
        if($is_cab_pooling_toggle == 1){
            if(isset($request->is_pooling_available)){
                $agentupdate =  Agent::where('id', Auth::user()->id)->update(['is_pooling_available' => $request->is_pooling_available]);
            }
        }else{
            $agentupdate =  Agent::where('id', Auth::user()->id)->update(['is_pooling_available' => 0]);
        }
        

        if ($request->lat=="" || $request->lat==0 || $request->lat== '0.00000000') {
        } else {

            $custom_mode = !empty($preferences->custom_mode) ? json_decode($preferences->custom_mode) : [];
            $clientPreference = !empty($preferences->customer_notification_per_distance) ? json_decode($preferences->customer_notification_per_distance) : [];
            
            if(!empty($custom_mode->is_hide_customer_notification) && ($custom_mode->is_hide_customer_notification == 1) && !empty($clientPreference->is_send_customer_notification) && ($clientPreference->is_send_customer_notification == 'on')){

            //    \Log::info('permission success');
                //get agent orders 
                $orders = Order::where('driver_id', Auth::user()->id)->where('status', 'assigned')->orderBy('order_time')->pluck('id')->toArray();
                if (count($orders) > 0) {
                    //\Log::info('get order');
                    
                   
                    //get agent current task
                   if($preferences->is_dispatcher_allocation == 1)
                   {
                      $tasks = Task::whereIn('order_id', $orders)->where(['task_status' => 2,'driver_id' => $user_id ])->with(['location','tasktype','order.customer','order.additionData'])->orderBy('order_id', 'desc')->orderBy('id', 'ASC')->get()->first();

                   }else{

                       $tasks = Task::whereIn('order_id', $orders)->where('task_status', 2)->with(['location','tasktype','order.customer','order.additionData'])->orderBy('order_id', 'desc')->orderBy('id', 'ASC')->get()->first();
                   }
                    if (!empty($tasks)) {

                        //\Log::info('get tasks--');
                        //\Log::info($tasks);
                        //\Log::info('get tasks--');
                        $callBackUrl = str_ireplace('dispatch-pickup-delivery', 'dispatch/customer/distance/notification', $tasks->order->call_back_url);
                        $latitude    = [];
                        $longitude   = [];

                        //\Log::info($callBackUrl);
                        // check task location in not empty and task created by custmer from order penel  
                        if(!empty($tasks->location) && !empty($callBackUrl)){
                            //\Log::info('get task location');
                            $tasksLocationLat  = $tasks->location->latitude;
                            $tasksLocationLong = $tasks->location->longitude;
        
                            //get distance using lat-long
                            $getDistance = $this->getLatLongDistance($tasksLocationLat, $tasksLocationLong, $request->lat, $request->long, $clientPreference->distance_unit);
        
                            // insert agent coverd distance
                            $data['distance_covered'] = $getDistance;
                            $data['current_task_id'] = $tasks->id;
                            AgentLog::create($data);

                            // check notification send to customer pr km/miles
                            $agentDistanceCovered = AgentLog::where('current_task_id', $tasks->id)->where('distance_covered', 'LIKE', '%'.$getDistance.'%')->count();
                            
                            if($agentDistanceCovered == 1 && $getDistance > 0){
                                //\Log::info('in send notification');
                                $notificationTitle       = $clientPreference->title;
                                $notificationDiscription = str_ireplace("{distance}", $getDistance.' '.$clientPreference->distance_unit, $clientPreference->description);
                                $notificationDiscription = str_ireplace("{co2_emission}", $clientPreference->co2_emission * $getDistance, $notificationDiscription);
                                
                                $postdata =  ['notificationTitle' => $notificationTitle, 'notificationDiscription' => $notificationDiscription];
        
                                $client = new GClient(['content-type' => 'application/json']);
                                
                                $res = $client->post($callBackUrl,
                                    ['form_params' => ($postdata)]
                                );
                                $response = json_decode($res->getBody(), true);   
                                //\Log::info('responce');
                                //\Log::info($response);

                            }
                            
                        }
                    }
                }                                           
            }else{
                AgentLog::create($data);
                //event(new \App\Events\agentLogFetch());
            }
        }

        $id    = Auth::user()->id;
        $all   = $request->all;

        if ($all == 1) {
            $orders = Order::where('driver_id', $id)->where('status', 'assigned')->orderBy('order_time')->pluck('id')->toArray();
        } else {
            $orders = Order::where('driver_id', $id)->whereBetween('order_time',[$utc_start, $utc_end])->where('status', 'assigned')->orderBy('order_time')->pluck('id')->toArray();
        }

        $agent =  Agent::with('team')->where('id',$id)->first();
        $agent->device_type = $request->device_type??null;
        $agent->device_token = $request->device_token??null;;
        $agent->save();


        if (count($orders) > 0) {

            if($preferences->is_dispatcher_allocation == 1)
            {
                $tasks = Task::whereIn('order_id', $orders)->where('driver_id' ,$id)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)->with(['location','tasktype','order.customer','order.additionData','order.waitingTimeLogs'])->orderBy('order_id', 'desc')->orderBy('id', 'ASC')->get();

            }else{
                $tasks = Task::whereIn('order_id', $orders)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)->with(['location','tasktype','order.customer','order.additionData','order.waitingTimeLogs'])->orderBy('order_id', 'desc')->orderBy('id', 'ASC')->get();
            }
            
            if (count($tasks) > 0) {
                //sort according to task_order
                $tasks = $tasks->toArray();
                if ($tasks[0]['task_order'] !=0) {
                    usort($tasks, function ($a, $b) {
                        return $a['task_order'] <=> $b['task_order'];
                    });
                }
            }
        }

        $agents    = $agent; //Agent::where('id', $id)->with('team')->first();
        $taskProof = TaskProof::all();

        $payment_codes = ['stripe'];
        $payment_creds = PaymentOption::select('code', 'credentials')->whereIn('code', $payment_codes)->where('status', 1)->get();
        if ($payment_creds) {
            foreach ($payment_creds as $creds) {
                $creds_arr = json_decode($creds->credentials);
                if ($creds->code == 'stripe') {
                    $preferences->stripe_publishable_key = (isset($creds_arr->publishable_key) && (!empty($creds_arr->publishable_key))) ? $creds_arr->publishable_key : '';
                }
            }
        }
        
        $getAdditionalPreference = getAdditionalPreference([
            'pickup_type',
            'drop_type',
            'is_attendence',
            'idle_time'
        ]);
        $preferences['isAttendence'] = ($getAdditionalPreference['is_attendence'] == 1) ? $getAdditionalPreference['is_attendence'] : 0;
        $allcation = AllocationRule::first('request_expiry');

        $datas['attribute_form'] = $this->getAttributeForm($request);

        $averageTaskComplete   = $this->getDriverTaskDonePercentage( $agents->id);
        $preferences['alert_dismiss_time'] = (int)$allcation->request_expiry;
        $agents['client_preference']  = $preferences;
        $agents['task_proof']         = $taskProof;
        $agents['averageTaskComplete']= $averageTaskComplete['averageRating'];
        $agents['CompletedTasks']= $averageTaskComplete['CompletedTasks'];
        if($preferences->unique_id_show){
            $agents['unique_id'] = base64_encode('DId_'.$agent->id);
        }
        $agents['is_road_side_toggle']= $preferences->is_road_side_toggle;

        $datas['user']                = $agents;
        $datas['tasks']               = $tasks;

        return response()->json([
            'data' => $datas,
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

    function getLatLongDistance($lat1, $lon1, $lat2, $lon2, $unit) {

        $earthRadius = 6371;  // earth radius in km

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);
        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;
        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
        cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        $final = round($angle * $earthRadius);
        if ($unit == "km") {
            return $final;
        } else {
            return round($final * 0.6214);
        }
    }

    public function cmsData(Request $request)
    {
        $data = Cms::where('id', $request->cms_id)->first();

        return response()->json([
            'data' => $data,
            'status' => 200,
            'message' => __('success')
           ], 200);
    }


    public function taskHistory(Request $request)
    {
        $id    = Auth::user()->id;
       
        $orders = Order::where('driver_id', $id);
        if(!empty($request->from_date) && !empty($request->to_date)){
            $orders =  $orders->whereBetween('order_time', [$request->from_date." 00:00:00",$request->to_date." 23:59:59"]);
        }

        $orders =  $orders->pluck('id');
        
        $hisoryStatus = [4,5];

        if($request->has('task_status') && $request->task_status !=''){
            $hisoryStatus = [$request->task_status];
        }
        if (isset($orders)) {
            $tasks = Task::with(['location','tasktype','order.customer','order.task.location','order.additionData','order.userRating'])
            ->whereIn('order_id', $orders)
            ->where(function($q) use ($hisoryStatus){
                $q->whereIn('task_status', $hisoryStatus)
                ->orWhereHas('order', function($q1){
                    $q1->where('status', 'cancelled');
                });
            })
            ->orderBy('order_id', 'DESC')
            ->get(['id','order_id','dependent_task_id','task_type_id','location_id','appointment_duration','task_status','allocation_type','created_at','barcode']);

            $driverearning = 0;
            $previousorder = 0;
            foreach($tasks as $task){
                if(!empty($task->order->driver_cost) && ($previousorder != $task->order_id) && $task->order->status !='cancelled' && $task->order->status !='failed'){
                    $driverearning += $task->order->driver_cost;
                    $previousorder = $task->order_id;
                }
            }
        } else {
            $task = [];
        }

        return response()->json([
            'data' => array('tasks' =>$tasks, 'totalCashCollected'=>$driverearning),
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

    //-----------function for the cab pooling suggession----------------------------//
    public function poolingTasksSuggessions(Request $request)
    {
        try{
            $preferences     = ClientPreference::where('id', 1)->first();
            $radius          = $preferences->radius_for_pooling_km;
            $TaskController  = new TaskController();
            $date            = date('Y-m-d', time());
            $suggessions     = [];
            $agentdata       = Agent::with(['agentlog', 'tags'])->where('id', Auth::user()->id)->first();
            if($agentdata->is_pooling_available ==1)
            {
                $agent_tags      = [];
                foreach($agentdata->tags as $tags):
                    $agent_tags[]    = $tags->id;
                endforeach;
                
                $assignedorder   = Order::with(['customer', 'task.location'])->where('status', '=', 'assigned')->where('driver_id', $agentdata->id)->where('is_cab_pooling', 1)->first();
                $origin_latitude = $origin_longitude = $destination_latitude = $destination_longitude = array();
                $origin_latitude[0]  = $agentdata->agentlog->lat;
                $origin_longitude[0] = $agentdata->agentlog->long;
                if(!empty($assignedorder)):
                    $k = 0;
                    foreach($assignedorder->task as $task):
                        if($k>0):
                            $destination_latitude[0]  = $task->location->latitude;
                            $destination_longitude[0] = $task->location->longitude;
                        endif;
                        $k++;
                    endforeach;
                    /* $orders = Order::with(['customer', 'task.location'])->where('status', '=', 'unassigned')->where('is_cab_pooling', 1)->get();
                    foreach($orders as $order):
                        $m = 0;
                        foreach($order->task as $task):
                            if($m == 0)://dd($task->location);
                                $origin_latitude[1]  = $task->location->latitude;
                                $origin_longitude[1] = $task->location->longitude;
                            else:
                                $destination_latitude[1]  = $task->location->latitude;
                                $destination_longitude[1] = $task->location->longitude;
                            endif;
                            $m++;
                        endforeach;
                        $pickupdistancedata = $TaskController->GoogleDistanceMatrix($origin_latitude, $origin_longitude);
                        $dropoffdistancedata = $TaskController->GoogleDistanceMatrix($destination_latitude, $destination_longitude);
                        if($pickupdistancedata['distance'] < 3 && $dropoffdistancedata['distance'] < 3):
                            $order->distance_from_driver  = $pickupdistancedata['distance'];
                            $order->distance_from_dropoff = $dropoffdistancedata['distance'];
                            $suggessions[] = $order;
                        endif;
                    endforeach; */
                    $origin_latitude       = $origin_latitude[0];
                    $origin_longitude      = $origin_longitude[0];
                    $destination_latitude  = $destination_latitude[0];
                    $destination_longitude = $destination_longitude[0];
                    $orders = Order::with(['customer', 'task.location', 'pickup_task.location' => function ($query) use ($origin_latitude, $origin_longitude) {
                                    $query->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $origin_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $origin_longitude . ")) 
                                    + sin(radians(" .$origin_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_from_pickup"));
                                }, 'dropoff_task.location' => function ($query) use ($destination_latitude, $destination_longitude) {
                                    $query->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $destination_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $destination_longitude . ")) 
                                    + sin(radians(" .$destination_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_from_dropoff"));
                                }])
                                ->where('status', '=', 'unassigned')->where('is_cab_pooling', 1)->whereDate('order_time', $date)
                                ->whereHas('pickup_task.location', function($q) use ($origin_latitude, $origin_longitude, $radius){
                                    $q->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $origin_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $origin_longitude . ")) 
                                    + sin(radians(" .$origin_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_pickup"))
                                        ->having("distance_pickup", "<", $radius);
                                })
                                ->whereHas('dropoff_task.location', function($q) use ($destination_latitude, $destination_longitude, $radius){
                                    $q->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $destination_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $destination_longitude . ")) 
                                    + sin(radians(" .$destination_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_dropoff"))
                                        ->having("distance_dropoff", "<", $radius);
                                })
                                ->whereHas('drivertag_combination', function($q) use ($agent_tags){
                                    $q->whereIn("tag_id", $agent_tags);
                                })->get();

                    $suggessions = $orders;
                else:
                    $origin_latitude       = $origin_latitude[0];
                    $origin_longitude      = $origin_longitude[0];
                    $suggessions = Order::with(['customer', 'task.location', 'pickup_task.location' => function ($query) use ($origin_latitude, $origin_longitude) {
                                $query->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $origin_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $origin_longitude . ")) 
                                    + sin(radians(" .$origin_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_from_pickup"));
                                }])
                                ->where('status', '=', 'unassigned')->where('is_cab_pooling', 1)->whereDate('order_time', $date)
                                ->whereHas('pickup_task.location', function($q) use ($origin_latitude, $origin_longitude, $radius){
                                $q->select("id", "address", "latitude", "longitude", DB::raw("6371 * acos(cos(radians(" . $origin_latitude . ")) 
                                    * cos(radians(latitude)) 
                                    * cos(radians(longitude) - radians(" . $origin_longitude . ")) 
                                    + sin(radians(" .$origin_latitude. ")) 
                                    * sin(radians(latitude))) AS distance_pickup"))
                                    ->having("distance_pickup", "<", $radius);
                                })
                                ->whereHas('drivertag_combination', function($q) use ($agent_tags){
                                    $q->whereIn("tag_id", $agent_tags);
                                })->get();
                endif;

                foreach($suggessions as $suggession):
                    if(isset($suggession->pickup_task[0]->location)):
                        $suggession->distance_pickup = isset($suggession->pickup_task[0]->location->distance_from_pickup)?$suggession->pickup_task[0]->location->distance_from_pickup:0;
                        $suggession->distance_dropoff = isset($suggession->dropoff_task[0]->location->distance_from_dropoff)?$suggession->dropoff_task[0]->location->distance_from_dropoff:0;
                    else:
                        $suggession->distance_pickup = 0;
                        $suggession->distance_dropoff = 0;
                    endif;
                endforeach;

                return response()->json([
                    'data' => array('order_suggession' => $suggessions),
                    'status' => 200,
                    'message' => __('success')
                ], 200);
            }else{
                return response()->json([
                    'data' => array('order_suggession' => []),
                    'status' => 200,
                    'message' => __('success')
                ], 200);
            }
        }
        catch(Exception $e){
            return response()->json([
                'data' => [],
                'status' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
        }
    }

    public function getReferOrder(Request $request)
    {
        $id     = Auth::user()->id;
 
        $tasks   = [];
      
        $orders = Order::where('refer_driver_id', $id)->whereNull('driver_id')->where('status', 'unassigned')->orderBy("order_time","ASC")->orderBy("id","ASC")->pluck('id')->toArray();
        


        if (count($orders) > 0) {
            $tasks = Task::whereIn('order_id', $orders)->where('task_status', '!=', 4)->Where('task_status', '!=', 5)
            ->with(['location','tasktype','order.customer','order.customer.resources','order.task.location','order.additionData'])->orderBy("order_id", "DESC")
            ->orderBy("id","ASC")
            ->get();
            if (count($tasks) > 0) {
                //sort according to task_order
                $tasks = $tasks->toArray();
                if ($tasks[0]['task_order'] !=0) {
                    usort($tasks, function ($a, $b) {
                        return $a['task_order'] <=> $b['task_order'];
                    });
                }
            }
        }

        return response()->json([
            'data' => $tasks,
            'status' => 200,
            'message' => __('success')
        ], 200);
    }


    //--------------------get bid request based on agent tag and geoid--------------
    public function getBidRideRequests(Request $request)
    {
        $id        = Auth::user()->id;
        \Log::info($id);
        $geo_ids   =  DriverGeo::where('driver_id', $id)->pluck('geo_id');
        \Log::info($geo_ids);
        // $agenttags =  Agent::with('tags')->where('id', $id)->first();
        // $tags = array();
        // foreach($agenttags->tags as $agenttags)
        // {
        //     $tags[] = $agenttags->name;
        // }

        if( count($geo_ids) > 0){
            $currenttime = Carbon::now()->format('Y-m-d H:i:s');
            $requestdata = UserBidRideRequest::whereIn('geo_id', $geo_ids)->where('expired_at', '>', $currenttime)
                           ->whereDoesntHave('declinedbyAgent', function($q) use ($id){
                            $q->where('agent_id', $id);
                        })->get();
        }else{
            $requestdata = [];
        }
           \Log::info('request data');
           \Log::info($requestdata);
        return response()->json([
            'data' => array('requestdata' =>$requestdata),
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

    public function getAcceptDeclinedBidRideRequests(Request $request)
    {
        $id        = Auth::user()->id;
        $biddata =  UserBidRideRequest::where('id', $request->id)->first();
        if(!empty($biddata)){
            $inseted = DeclineBidRequest::insert(['bid_id' => $request->id, 'agent_id' => $id, 'status' => $request->status]);

            if($request->status == 1){
                return response()->json([
                    'data' =>[],
                    'status' => 200,
                    'message' => __('Request accepted')
                ], 200);
            }else{
                return response()->json([
                    'data' =>[],
                    'status' => 200,
                    'message' => __('Request Declined')
                ], 200);
            }
            
        }else{
            return response()->json([
                'data' =>[],
                'status' => 404,
                'message' => __('!Error, Something went wrong.')
            ], 200);
        }
    }
    public function userRating(Request $request)
    {
       
        $UserRating = UserRating::where('order_id',$request->order_id)->first() ?? new UserRating();
        $UserRating->driver_id = Auth::user() ? Auth::user()->id :  $request->driver_id;
        $UserRating->user_id = $request->user_id;
        $UserRating->order_id = $request->order_id;
        $UserRating->rating = $request->rating;
        $UserRating->review = $request->review;
        $UserRating->order_webhook = $request->order_webhook;
        $UserRating->save() ;
        $client = new GClient(['content-type' => 'application/json']);
        $url = $request->order_webhook;
        $res = $client->get($url);
        $response = json_decode($res->getBody(), true);
        return response()->json([
            'data' => $UserRating ,
            'status' => 200,
            'message' => __('Rating Submited!')
        ], 200);
    }

    public function pendingPaymentOrder(Request $request)
    {
        $id    = Auth::user()->id;
        $orders = Order::where('driver_id', $id)->with(['task','task.location','additionData','userRating','customer']);
        if(!empty($request->from_date) && !empty($request->to_date)){
            $orders =  $orders->whereBetween('order_time', [$request->from_date." 00:00:00",$request->to_date." 23:59:59"]);
        }
        
        $orders =  $orders->where('is_comm_settled','0')->where('driver_cost','>=',0)->whereHas('task', function ($query) {
            $query->where('task_status', 4); // completed task
        });
        $orders = $orders->orderBy('id', 'DESC')->paginate(10);
     
        return response()->json([
            'orders' => $orders,
            'status' => 200,
            'message' => __('success')
        ], 200);
    }

    public function postSendReffralCode(Request $request)
    {          
        $validator = Validator::make($request->all(), [
            'email' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 201, 'message' => $validator->errors()->first()], 201);
        }

        // try {
            $driver = Auth::user();
            $client = Client::first();
            
            $driver_refferal_detail = DriverRefferal::where('driver_id', $driver->id)->first();
            if ($driver_refferal_detail) {
                $smtp = SmtpDetail::where('id', 1)->first();
                if(!empty($smtp) && !empty($client->contact_email))
                {             
                    $email_template_content = '';
                    $email_template_content = str_ireplace("{code}", $driver_refferal_detail->refferal_code, $email_template_content);
                    $email_template_content = str_ireplace("{customer_name}", ucwords($driver->name), $email_template_content);
                    
                    $sendto = $request->email;
                    $client_name = $client->name;
                    $mail_from = $smtp->from_address;
                    $t = Mail::send('email.verify', [
                        'email' => $request->email,
                        'mail_from' => $smtp->from_address,
                        'client_name' => $client->name,
                        'code' => $request->refferal_code,
                        'logo' => $client->logo['original'],
                        'customer_name' => "Link from " . $driver->name,
                        'code_text' => 'Register yourself using this referral code below to get bonus offer',
                        'link' => url()."/user/register?refferal_code=" . $request->refferal_code,
                        'email_template_content' => $email_template_content
                    ], function ($message) use ($sendto, $client_name, $mail_from) {
                        $message->from($mail_from, $client_name);
                        $message->to($sendto)->subject('Referral For Registration');
                    });
                }
            }
        // } catch (Exception $e) {
        //     return response()->json($e->getMessage());
        // }
    }
}
