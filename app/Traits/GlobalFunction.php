<?php
namespace App\Traits;

use App\AgentOrderLog;
use DB;
use Illuminate\Support\Collection;
use Log;
use App\Model\{ChatSocket, Client, Agent, ClientPreference, DistanceWisePricingRule, DriverGeo,Order,Task,OrderAdditionData, PricingRule, DriverHomeAddress, Location,Team,LocationDistance,AgentLog,Countries,AgentsTag};
use Illuminate\Support\Facades\Config;
use PhpParser\Node\Stmt\Else_;
use App\Model\Timezone;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Arr;
use App\Traits\googleMapApiFunctions;
use File;

trait GlobalFunction{



    public static function socketDropDown()
    {
        // $chatSocket= ChatSocket::where('status', 1)->get();
        // return $chatSocket;
    }

    public static function checkDbStat($id)
    {
        try {
            $client = Client::find($id);

            $schemaName = 'db_' . $client->database_name;
            $database_host = !empty($client->database_host) ? $client->database_host : env('DB_HOST', '127.0.0.1');
            $database_port = !empty($client->database_port) ? $client->database_port : env('DB_PORT', '3306');
            $database_username = !empty($client->database_username) ? $client->database_username : env('DB_USERNAME', 'root');
            $database_password = !empty($client->database_password) ? $client->database_password : env('DB_PASSWORD', '');

            $default = [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => $database_host,
            'port' => $database_port,
            'database' => $schemaName,
            'username' => $database_username,
            'password' => $database_password,
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null
        ];

            Config::set("database.connections.$schemaName", $default);
            config(["database.connections.mysql.database" => $schemaName]);
            $return['schemaName'] =  $schemaName;
            $return['clientData'] =  $client;

            return $return;
        } catch (\Throwable $th) {
            $return['schemaName'] =  '';
            $return['clientData'] =  [];
            return $return;
        }

    }


    public function getGeoBasedAgentsData($geo, $is_cab_pooling, $agent_tag = '', $date, $cash_at_hand,$order_id='',$particular_driver_id = '')
    {
        try {
            $preference = ClientPreference::select('manage_fleet', 'is_cab_pooling_toggle', 'is_threshold','is_go_to_home','go_to_home_radians')->first();
            $geoagents_ids =  DriverGeo::where('geo_id', $geo);
            if($preference->is_cab_pooling_toggle == 1 && $is_cab_pooling == 1){
                $geoagents_ids = $geoagents_ids->whereHas('agent', function($q) use ($geo, $is_cab_pooling){
                    $q->where('is_pooling_available', $is_cab_pooling);
                });
            }

            $agents = [];
            if (!empty($agent_tag)) {

                if (is_array($agent_tag)) {

                    $agents = AgentsTag::whereIn('tag_id', $agent_tag)->whereHas('agent',function($qry){
                        $qry->where('is_available',1);
                    })->pluck('agent_id')->toArray();

                } else {

                    // Case 2: $agent_tag is a string

                    $agents = AgentsTag::whereHas('tags', function ($qry) use ($agent_tag) {

                        $qry->where('name', 'LIKE', '%' . $agent_tag . '%');

                    })->whereHas('agent',function($qry){
                        $qry->where('is_available',1);
                    })->pluck('agent_id')->toArray();

                }
                
                
                $geoagents_ids =  DriverGeo::where('geo_id', $geo)->whereIn('driver_id', $agents);

                

            }

            $order = Order::find($order_id);

            if($order)
            {
                $geoagents_ids = $geoagents_ids->whereHas('agent', function($q) use ($order){
                    $q->where('id', '!=', $order->driver_id);
                });
            }            
            $geoagents_ids =  $geoagents_ids->pluck('driver_id');

            $geoagents = Agent::whereIn('id',  $geoagents_ids)->with(['logs','order'=> function ($f) use ($date) {
                $f->whereDate('order_time', $date)->with('task');
            }]);

            if($particular_driver_id){
                $geoagents = $geoagents->where('id','!=',$particular_driver_id);
            }
            if(@$preference->is_threshold == 1){
                $geoagents = $geoagents->where('is_threshold', 1);
            }
           
            if(@$preference->manage_fleet){
                $geoagents = $geoagents->whereHas('agentFleet');
            }
            // geting task only 
            if((@$preference->is_go_to_home ==1) && ($order_id!='')){
                $dropOfTask = Task::with('location')->where(['order_id'=>$order_id,'task_type_id'=>2])->first();
                $dropLat  = $dropOfTask ?  ($dropOfTask->location ? $dropOfTask->location->latitude : '' ) : '' ;
                $dropLong =$dropOfTask ?  ($dropOfTask->location ? $dropOfTask->location->longitude : '') : '' ;
                $radians = (int)($preference->go_to_home_radians ?? 0) ;
                if($dropLat !='' && $dropLong !='' ){
                    $geoagents = $geoagents->onlyGetingAgentByHomeAddress($dropLat, $dropLong, $radians);
                }
            }
          
            $geoagents = $geoagents->orderBy('id', 'DESC');
            $geoagents = $geoagents->get()->where("agent_cash_at_hand", '<', $cash_at_hand);

            return $geoagents;

        } catch (\Throwable $th) {
            return [];
        }

    }
    public function getDriverTaskDonePercentage($agent_id)
    {
        $orders = Order::where('driver_id', $agent_id)->pluck('id')->toArray();
        $CompletedTasks = Task::whereIn('order_id', $orders)
                                ->where(function($q) {
                                    $q->where('task_status',4 );
                                })->count();
        $totalTask = Task::whereIn('order_id', $orders)
                                ->where(function($q) {
                                    $q->whereIn('task_status',[5,4] ) 
                                    ->orWhereHas('order', function($q1){
                                        $q1->where('status', 'cancelled');
                                    });
                                })->count();
        $average =0;
        if( $CompletedTasks > 0){
            $average  = (  $CompletedTasks * 100) /$totalTask;        
        }         
        $data['averageRating'] = number_format($average,2);
        $data['CompletedTasks'] = $CompletedTasks;
        $data['totalTask'] =  $totalTask;
        return  $data;
    }

      //---------function to get pricing rule based on agent_tag/geo fence/timetable/day/time
      public function getPricingRuleData($geoid, $agent_tag = '', $order_datetime = '')
      {
  
          try {
              $pricingRule = '';
              if(!empty($geoid))
              {
                  $pricingRule = PricingRule::whereHas('priceRuleTags.geoFence',function($q)use($geoid){
                      $q->where('id', $geoid);
                  });
  
                  if(!empty($agent_tag)){
                      $pricingRule->whereHas('priceRuleTags.tagsForAgent',function($q)use($agent_tag){
                      $q->where('name', $agent_tag);
                  });
                  }
  
                  if(!empty($order_datetime)){
                      $dayname =Carbon::parse($order_datetime)->format('l') ;
                      $time =  Carbon::parse($order_datetime)->format('H:i');
                      $pricingRule->whereHas('priceRuleTimeframe', function($query) use ($dayname, $time){
                          $query->where('is_applicable', 1)
                              ->Where('day_name', '=', $dayname)
                              ->whereTime('start_time', '<=', $time)
                              ->whereTime('end_time', '>=', $time);
                      });
                  }else{
                      $pricingRule->where('apply_timetable', '!=', 1);
                  }
                  $pricingRule =   $pricingRule->orderBy('id', 'desc')->first();
  
                  if(empty($pricingRule)){
                      $pricingRule = PricingRule::whereHas('priceRuleTags.geoFence',function($q)use($geoid){
                          $q->where('id', $geoid);
                      });
      
                      if(!empty($agent_tag)){
                          $pricingRule->whereHas('priceRuleTags.tagsForAgent',function($q)use($agent_tag){
                          $q->where('name', $agent_tag);
                      });
                      }
                      $pricingRule =   $pricingRule->orderBy('id', 'desc')->first();
                  }
              }
            
              if(empty($pricingRule)){
                  $pricingRule = PricingRule::where('is_default', 1)->first();
              }
              return $pricingRule;
  
          } catch (\Throwable $th) {
              
              // \Log::info('Eror '.$th->getMessage());
  
              return [];
          }
      }

// $numbers = ['10'=>7,'20'=>5,'50'=>2]; // array of numbers}
// $distanceTotal = '55'; // array to store differences
// $last = 1;
// $sum = 0;
// foreach ($numbers as $key => $number) {
//     $no = ($key -$last);
//     // echo $no.'---';
//     $pr = $no * $number;
//     // echo $pr.'=';
//    $sum +=  $pr; 
//    $last = $key;
// }
// echo $sum;


    public function setPricingRuleDynamic($id,$time)
    {
        try {           
            $order  = Order::where('id', $id)->first();
            $timeTotal = Task::where('order_id',$order->id)->sum('waiting_time');   
            $time = $timeTotal??$time;

            if(isset($order)) {
                $waitPrice = $time * $order->waiting_price;
                $total = $order->order_cost + $waitPrice;
                $agent_details = Agent::where('id', $order->driver_id)->first();
                if ($agent_details->type == 'Employee') {
                    $percentage = $order->agent_commission_fixed + (($total / 100) * $order->agent_commission_percentage);
                } else {
                    $percentage = $order->freelancer_commission_fixed + (($total / 100) * $order->freelancer_commission_percentage);
                }
            }
            $data['order_cost'] = $total;
            $data['driver_cost'] = $percentage;
            $data['waiting_time'] = $time;
            $data['cash_to_be_collected'] = $order->cash_to_be_collected + $waitPrice;
            $order->update($data);
            // \Log::info(json_encode(['total_waiting_time'=>$time,'total_waiting_price'=>$waitPrice]));

            return ['total_waiting_time'=>$time,'total_waiting_price'=>$waitPrice];

        } catch (\Throwable $th) {
            \Log::info(json_encode($th->getMessage()));
            return 0;
        }

    }

    //---------function to get pricing rule based on agent_tag/geo fence/timetable/day/time
    public function getPricingRuleDynamic($pricingRule,$distance,$perKm=0)
    {
        // \Log::info('dynamic in');
        try {
            // \Log::info('pricingRuleDistance nninn : '.$distance);
            $lastDistance = $distance - $pricingRule->base_distance??1;
            $sum = 0;
            if($perKm)
            {
                    $distancePricing = [];
                    if(!empty($pricingRule) && $distance>1){
                        $distancePricing = DistanceWisePricingRule::where('price_rule_id',$pricingRule->id)->where('distance_fee','<=',$lastDistance)->orderBy('distance_fee','asc')->get();
                    }
                    $sum = 0;
                    $last = $pricingRule->base_distance??1;

                    if(empty($distancePricing)  && count($distancePricing)==0)
                    {
                        return $sum??0;  
                    }
                    
                    foreach($distancePricing as $key => $number)
                    {
                        $no = ($number->distance_fee - $last);
                        if($lastDistance >= $number->distance_fee)
                        {
                            $pr = $no * $number->duration_price;
                            $sum +=  $pr; 
                        }
                        $lastDistance = $lastDistance - $no;
                        $last = $number->distance_fee;
                    }

                    if($lastDistance){
                        $upperPrice = DistanceWisePricingRule::where('price_rule_id',$pricingRule->id)->where('distance_fee','>',$lastDistance)->value('duration_price');
                        $pr = $lastDistance * $upperPrice;
                        $sum +=  $pr; 
                    }
                }else{
                    $distancePricing = DistanceWisePricingRule::where('price_rule_id',$pricingRule->id)->where('distance_fee','>=',$lastDistance)->orderBy('distance_fee','asc')->first();
                    if(empty($distancePricing)  && count($distancePricing)==0)
                    {
                        return $sum??0;  
                    }
                    $sum = $lastDistance * $distancePricing->duration_price;
                }
                    return $sum??0;

        } catch (\Throwable $th) {
          \Log::info(json_encode($th->getMessage()));
          return 0;
        }
    
    }


    public function getConvertUTCToLocalTime($datetime, $localtimezone = 'UTC')
    {
        $local_datetime = Carbon::parse($datetime, 'UTC')->setTimezone($localtimezone)->format('Y-m-d H:i:s');
        return $local_datetime;
    }


    public function updateOrderAdditional($request=[],$order_id)
    {
        $requestOnly = ['category_name','specific_instruction'];
        $validated_keys = $request->only($requestOnly);
       
        $order_id = @$order_id;
    
        foreach($validated_keys as $key => $value){
          OrderAdditionData::updateOrCreate(
                ['key_name' => $key, 'order_id' => $order_id],
                ['key_name' => $key, 'key_value' => $value,'order_id' => $order_id]);
        }
        return 1;
        
    }


    
    /**
     * Check agent go to home address Distance enable or disabled
     */
    public function CheckAgentHomeAddress($finalLocation,$id)
    {
        try {
            $checkHomeaddress   =  Agent::where(['id'=>$id,'is_go_to_home_address'=>1])->count();
            $max_distance       =  5;
            if($checkHomeaddress > 0){
                $address = DriverHomeAddress::where(['agent_id'=>$id,'is_default'=>1])->first();
                if(!empty($address)){
                    $latitude       =   $address->latitude;
                    $longitude      =   $address->longitude;
                    $distance       =   $this->DistanceAgentHomeAddess($finalLocation->latitude,$finalLocation->longitude,$latitude,$longitude);
                    if($distance <= $max_distance){
                        return true;
                    }else{
                        return false;
                    }
                    
                }else{
                    $location =  $this->lastAgentDropoffLocation($id);
                   
                    if(isset($location) && !empty($location)){
                        $location   = $location->location;
                        $latitude   = $location->latitude;
                        $longitude  = $location->longitude;
                        $distance   = $this->DistanceAgentHomeAddess($finalLocation->latitude,$finalLocation->longitude,$latitude,$longitude);
                        
                        if($distance <= $max_distance){
                            return true;
                        }else{
                            return false;
                        }
                    }else{
                        return false;
                    }
                }
            }
        }catch (\Throwable $th) {
            return response()->json([
                'message' => $th->getMessage()
            ], 400);
        }
    }


    /**
     *  Distance between go to home address and customer final location
     */
    public function DistanceAgentHomeAddess($client_lat,$client_long,$agent_home_addres_lat,$agent_home_addres_long){
        $client = ClientPreference::where('id', 1)->first();
        $value = [];
        $send   = [];
        $ch = curl_init();
            $headers = array('Accept: application/json',
                    'Content-Type: application/json',
                    );
            $url =  'https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins='.$client_lat.','.$client_long.'&destinations='.$agent_home_addres_lat.','.$agent_home_addres_long.'&key='.$client->map_key_1.'';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $result = json_decode($response);
            curl_close($ch); // Close the connection
            $new =   $result;
            if(count($result->rows) > 0){
                array_push($value, $result->rows[0]->elements);
            }
            if (isset($value)) {
                $totalDistance = 0;
                $totalDuration = 0;
                foreach ($value as $i => $item) {
                    //dd($item);
                    $totalDistance = $totalDistance + (isset($item[$i]->distance) ? $item[$i]->distance->value : 0);
                    $totalDuration = $totalDuration + (isset($item[$i]->duration) ? $item[$i]->duration->value : 0);
                }
    
    
                if ($client->distance_unit == 'metric') {
                    $send['distance'] = round($totalDistance/1000, 2);      //km
                } else {
                    $send['distance'] = round($totalDistance/1609.34, 2);  //mile
                }
                //
                $newvalue = round($totalDuration/60, 2);
                $whole = floor($newvalue);
                $fraction = $newvalue - $whole;
    
                if ($fraction >= 0.60) {
                    $send['duration'] = $whole + 1;
                } else {
                    $send['duration'] = $whole;
                }
            }
            return $send;
            
    }
   
   /**
     *  Distance between go to home address and agent last frop off location
     */
    public function lastAgentDropoffLocation($id){
        $count = Order::where('driver_id', $id)->where('status', 'completed')->orderBy('id', 'desc')->count();
        if($count > 0){
            $order = Order::where('driver_id', $id)->where('status', 'completed')->orderBy('id', 'desc')->first();
            $drop_off = task::where('task_type_id',2)->where('order_id',$order->id)->with('location')->first();
            if(isset($drop_off) && !empty($drop_off)){
                return $drop_off;
            }
        }
    }


    //function to create distance matrix
    public function distanceMatrix($pointarray, $taskids)
    {
        $taskids = explode(',', $taskids);
        $pointarray[0][2] = 0;
        for ($t=1; $t < count($pointarray); $t++) {
            $pointarray[$t][2] = $taskids[$t - 1];
        }

        $matrixarray = array();
        for ($i=0; $i < count($pointarray); $i++) {
            for ($k=0; $k < count($pointarray); $k++) {
                if ($pointarray[$i][2] ==0 || $pointarray[$k][2]==0) {
                    if ($i==$k) {
                        $matrixarray[$i][$k] = 0;
                    } elseif ($i > $k) {
                        if ($k==0) {
                            $matrixarray[$i][$k] = 0;
                        } else {
                            $matrixarray[$i][$k] = $matrixarray[$k][$i];
                        }
                    } else {
                        $distance = $this->GoogleDistanceMatrix($pointarray[$i][0], $pointarray[$i][1], $pointarray[$k][0], $pointarray[$k][1]);
                        $matrixarray[$i][$k] = $distance;
                    }
                } else {
                    if ($i==$k) {
                        $matrixarray[$i][$k] = 0;
                    } elseif ($i > $k) {
                        $matrixarray[$i][$k] = $matrixarray[$k][$i];
                    } else {
                        $loc1 = $pointarray[$i][2];
                        $loc2 = $pointarray[$k][2];
                        //check if distance exist
                        $checkdistance = LocationDistance::where(['from_loc_id'=>$loc1,'to_loc_id'=>$loc2])->first();
                        if (isset($checkdistance->id)) {
                            $matrixarray[$i][$k] = $checkdistance->distance;
                        } else {
                            $distance = $this->GoogleDistanceMatrix($pointarray[$i][0], $pointarray[$i][1], $pointarray[$k][0], $pointarray[$k][1]);
                            $matrixarray[$i][$k] = $distance;
                            $locdata = array('from_loc_id'=>$loc1,'to_loc_id'=>$loc2,'distance'=>$distance);
                            LocationDistance::create($locdata);
                        }
                    }
                }
            }
        }
        return $matrixarray;
    }

    public static function splitOrder($orders)
    {
        $new_order = [];
        if (is_array($orders) && count($orders)>0 && !empty($orders)) {
            $counter = 0;
            foreach ($orders as $order) {
                foreach ($order['tasks'] as $task) {
                    $new_order[] = $order;
                    $new_order[$counter]['task_order'] = $task['task_order'];
                    unset($new_order[$counter]['task']);
                    $new_order[$counter]['task'][] = $task;
                    $counter++;
                }
            }

            //sort array
            usort($new_order, function ($a, $b) {
                return $a['task_order'] <=> $b['task_order'];
            });
            return $new_order;
        } else {
            return $orders;
        }
    }

        // function to get distance between 2 location
        public function GoogleDistanceMatrix($lat1, $long1, $lat2, $long2)
        {
           
            $client = ClientPreference::where('id', 1)->first();
            $ch = curl_init();
            $headers = array('Accept: application/json',
                       'Content-Type: application/json',
                       );
            $url =  'https://maps.googleapis.com/maps/api/distancematrix/json?units=metric&origins='.$lat1.','.$long1.'&destinations='.$lat2.','.$long2.'&key='.$client->map_key_1.'';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $result = json_decode($response);
          
            curl_close($ch); // Close the connection
    
            $value =   $result->rows[0]->elements??'';
            if (isset($value[0]->distance)) {
                $totalDistance = $value[0]->distance->value;
            } else {
                $totalDistance = 0;
            }
            return round($totalDistance);
        }
    
        //for optimizing route
        public function optimizeRoute(Request $request)
        {
            $driver_start_time = $request->driver_start_time;
            $task_duration = $request->task_duration;
            $brake_start_time = $request->brake_start_time;
            $brake_end_time = $request->brake_end_time;
            $driver_start_location = $request->driver_start_location;
            $driver_latitude = $request->latitude;
            $driver_longitude = $request->longitude;
            $taskids =  $request->route_taskids;
            $agentid = $request->route_agentid;
            $distancematrix = $request->distance_matrix;
            $distancematrixarray = json_decode($distancematrix);
    
            if ($driver_start_location=='current') {
                if ($agentid != 0) {
                    $singleagentdetail = Agent::where('id', $agentid)->with('agentlog')->first();
                    if ($singleagentdetail->is_available == 1) {
                        $driver_lat = $singleagentdetail->agentlog->lat??'';
                        $driver_long = $singleagentdetail->agentlog->long??'';
                    } else {
                        $driver_lat = $distancematrixarray[0][0];
                        $driver_long = $distancematrixarray[0][1];
                    }
                } else {
                    $driver_lat = $distancematrixarray[0][0];
                    $driver_long = $distancematrixarray[0][1];
                }
            } elseif ($driver_start_location=='task_location') {
                $startingtasklocation = Task::where('id', $request->task_start_location)->with('location')->first();
                $driver_lat = $startingtasklocation->location->latitude;
                $driver_long = $startingtasklocation->location->longitude;
                if ($driver_lat==0 || $driver_long==0) {
                    $driver_lat = $distancematrixarray[0][0];
                    $driver_long = $distancematrixarray[0][1];
                }
            } else {
                if ($driver_latitude==0 || $driver_longitude==0) {
                    $driver_lat = $distancematrixarray[0][0];
                    $driver_long = $distancematrixarray[0][1];
                } else {
                    $driver_lat = $driver_latitude;
                    $driver_long = $driver_longitude;
                }
            }
            //arranging starting location in distance matrix
            $distancematrixarray[0][0] = $driver_lat;
            $distancematrixarray[0][1] = $driver_long;
    
            $auth = Client::where('code', Auth::user()->code)->with(['getAllocation', 'getPreference'])->first();
    
            //setting timezone from id
            $tz = new Timezone();
            $auth->timezone = $tz->timezone_name(Auth::user()->timezone);
    
            $startdate = date("Y-m-d 00:00:00", strtotime($request->route_date));
            $enddate = date("Y-m-d 23:59:59", strtotime($request->route_date));
            $startdate = Carbon::parse($startdate . $auth->timezone ?? 'UTC')->tz('UTC');
            $enddate = Carbon::parse($enddate . $auth->timezone ?? 'UTC')->tz('UTC');
            $points = $distancematrixarray;
            $distance_matrix = $this->distanceMatrix($points, $taskids);
            $payload = json_encode(array("data" => $distance_matrix));
    
            //api for getting optimize path
            $url = "https://optimizeroute.royodispatch.com/optimize";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
            if ($result) {
                $taskids = explode(',', $taskids);
                $newtaskidorder = [];
                $newroute = json_decode($result);
                $routecount = count($newroute->data)-1;
                for ($i=1; $i < $routecount; $i++) {
                    $taskorder = [
                        'task_order'        => $i
                    ];
                    $index =  $newroute->data[$i]-1;
                    Task::where('id', $taskids[$index])->update($taskorder);
                    $newtaskidorder[] = $taskids[$index];
                }
                $output = array();
                echo json_encode($output);
            } else {
                echo "Try again later";
            }
        }
    
    public function updateAgentLog($data ,$order_id =""){
      
        if(empty($order_id)){
            AgentLog::where('agent_id', $data['agent_id'])
            ->latest('created_at')
            ->firstOrNew() // Get the latest or create a new instance if not found
            ->fill($data) // Fill the data to be updated or created
            ->save();
            $log = AgentLog::where('agent_id',$data['agent_id'])->latest('created_at')->first();
            }else{
                $log = AgentOrderLog::create($data);
            }
            return $log ;
    }

        //This is for drag and drop functionality
        public function arrangeRoute(Request $request)
        {
          
            $taskids = explode(',', $request->taskids);
            $taskids = array_filter($taskids);
    
            $auth = Client::where('code', Auth::user()->code)->with(['getAllocation', 'getPreference'])->first();
    
            //setting timezone from id
            $tz = new Timezone();
            $auth->timezone = $tz->timezone_name(Auth::user()->timezone);
    
            $startdate = date("Y-m-d 00:00:00", strtotime($request->date));
            $enddate = date("Y-m-d 23:59:59", strtotime($request->date));
            $startdate = Carbon::parse($startdate . $auth->timezone ?? 'UTC')->tz('UTC');
            $enddate = Carbon::parse($enddate . $auth->timezone ?? 'UTC')->tz('UTC');
            $task = Task::with('location')->whereIn('id',$taskids)->get();
           
            $agentid = $request->agentid;
            
            for ($i=0; $i < count($taskids); $i++) {
                $taskorder = [
                    'task_order' => $i
                 ];
                Task::where('id', $taskids[$i])->update($taskorder);
            }
    
            $orderdetail = Task::where('id', $taskids[0])->with('order')->first();
            $orderdate =  date("Y-m-d", strtotime($orderdetail->order->order_time));
    
            //getting all routes
            $allTasks = Order::where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->with(['customer', 'task.location', 'agent.team'])->get();
    
            $allmarker = [];
            foreach ($allTasks as $key => $tasks) {
                $append = [];
                foreach ($tasks->task as $task) {
                    if ($task->task_type_id == 1) {
                        $name = 'Pickup';
                    } elseif ($task->task_type_id == 2) {
                        $name = 'DropOff';
                    } else {
                        $name = 'Appointment';
                    }
                    $append['task_type']             = $name;
                    $append['task_id']               = $task->id;
                    $append['latitude']              = floatval($task->location->latitude);
                    $append['longitude']             = floatval($task->location->longitude);
                    $append['address']               = $task->location->address;
                    $append['task_type_id']          = $task->task_type_id;
                    $append['task_status']           = (int)$task->task_status;
                    $append['team_id']               = isset($tasks->driver_id) ? @$tasks->agent->team_id : 0;
                    $append['driver_name']           = isset($tasks->driver_id) ? @$tasks->agent->name : '';
                    $append['driver_id']             = isset($tasks->driver_id) ? $tasks->driver_id : '';
                    $append['customer_name']         = isset($tasks->customer->name)?$tasks->customer->name:'';
                    $append['customer_phone_number'] = isset($tasks->customer->phone_number)?$tasks->customer->phone_number:'';
                    $append['task_order']            = $task->task_order;
    
                    array_push($allmarker, $append);
                }
            }
           
    
            $allagents = Agent::with('agentlog')->get()->toArray();
            $alldrivers = array();
            $j = 0;
            foreach ($allagents as $singleagent) {
                if (is_array($singleagent['agentlog'])) {
                    $alltaskarray = array();
                    foreach ($allmarker as $singlemark) {
                        if ($singlemark['driver_id'] == $singleagent['agentlog']['agent_id']) {
                            $alltaskarray[] = $singlemark;
                        }
                    }
                    if (!empty($alltaskarray)) {
                        usort($alltaskarray, function ($a, $b) {
                            return $a['task_order'] <=> $b['task_order'];
                        });
                        if ($request->date != date('Y-m-d')) {
                            $singleagent['agentlog']['lat'] = $alltaskarray[0]['latitude'];
                            $singleagent['agentlog']['long'] = $alltaskarray[0]['longitude'];
                        }
                        $alldrivers[$j]['driver_detail'] = $singleagent['agentlog'];
                        $alldrivers[$j]['task_details'] = $alltaskarray;
                        $j++;
                    }
                }
            }

            
    
            //unassigned_orders
            $unassigned_orders = array();
            $un_order  = Order::where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('auto_alloction', 'u')->with(['customer', 'task.location'])->get();
            if (count($un_order)>=1) {
                $unassigned_orders = $this->splitOrder($un_order->toarray());
                if (count($unassigned_orders)>1) {
                    $un_route = array();
                    foreach ($unassigned_orders as $singleua) {
                        //for drawing route
                        $s_task = $singleua['task'][0];
                        if ($s_task['task_type_id'] == 1) {
                            $nname = 'Pickup';
                        } elseif ($s_task['task_type_id'] == 2) {
                            $nname = 'DropOff';
                        } else {
                            $nname = 'Appointment';
                        }
                        $aappend = array();
                        $aappend['task_type']             = $nname;
                        $aappend['task_id']               =  $s_task['id'];
                        $aappend['latitude']              =  $s_task['location']['latitude'];
                        $aappend['longitude']             = $s_task['location']['longitude'];
                        $aappend['address']               = $s_task['location']['address'];
                        $aappend['task_type_id']          = $s_task['task_type_id'];
                        $aappend['task_status']           = $s_task['task_status'];
                        $aappend['team_id']               = 0;
                        $aappend['driver_name']           = '';
                        $aappend['driver_id']             = 0;
                        $aappend['customer_name']         = $singleua['customer']['name'];
                        $aappend['customer_phone_number'] = $singleua['customer']['phone_number'];
                        $aappend['task_order']            = $singleua['task_order'];
                        $un_route[] = $aappend;
                    }
    
                    $first_un_loc = array('lat'=>floatval($unassigned_orders[0]['task'][0]['location']['latitude']),'long'=>floatval($unassigned_orders[0]['task'][0]['location']['longitude']));
                    $final_un_route['driver_detail'] = $first_un_loc;
                    $final_un_route['task_details'] = $un_route;
                    $alldrivers[] = $final_un_route;
                }
            }
    
            //calculating distance
            $driverlocation = [];
            if ($agentid != 0) {
                $singleagentdetail = Agent::where('id', $agentid)->with('agentlog')->first();
                if(empty($singleagentdetail)){
                    $singleagentdetail = Agent::with('agentlog')->first();
                }
                if ($singleagentdetail->is_available == 1) {
                    $driverlocation['lat'] = $singleagentdetail->agentlog->lat;
                    $driverlocation['long'] = $singleagentdetail->agentlog->long;
                } else{
                    $driverlocation['lat'] = $singleagentdetail->agentlog->lat;
                    $driverlocation['long'] = $singleagentdetail->agentlog->long;
                }
            }
    
            // $gettotal_distance = $this->getTotalDistance($taskids, $driverlocation);
            // $distance  = $gettotal_distance['total_distance_miles'];
    
            if ($agentid!=0) {
                $allcation_type = 'silent';
                $oneagent = Agent::where('id', $agentid)->first();
                $notification_data = [
                    'title'               => 'Update Order',
                    'body'                => 'Check All Details For This Request In App',
                    'order_id'            => '',
                    'driver_id'           => $agentid,
                    'notification_time'   => Carbon::now()->toDateTimeString(),
                    'type'                => $allcation_type,
                    'client_code'         => '',
                    'created_at'          => Carbon::now()->toDateTimeString(),
                    'updated_at'          => Carbon::now()->toDateTimeString(),
                    'device_type'         => @$oneagent->device_type,
                    'device_token'        => @$oneagent->device_token,
                    'detail_id'           => '',
                ];
                $this->sendsilentnotification($notification_data);
            }
    
            $output = array();
            $output['allroutedata'] = $alldrivers;
             
            // $output['total_distance'] = $distance;
            $output['current_location'] = Task::where('id', $taskids[0])->whereHas('order.agent.agentlog')->count();
            echo json_encode($output);
        }
    
        //for updating task time after drag drop functionality
        public function optimizeArrangeRoute(Request $request)
        {
          
            $driver_start_time = $request->driver_start_time;
            $task_duration = $request->task_duration;
            $brake_start_time = $request->brake_start_time;
            $brake_end_time = $request->brake_end_time;
            $driver_start_location = $request->driver_start_location;
            $driver_latitude = $request->latitude;
            $driver_longitude = $request->longitude;
    
            $agentid = $request->route_agentid;
            $distancematrix = $request->distance_matrix;
    
            $taskids = explode(',', $request->route_taskids);
            $taskids = array_filter($taskids);
            $firsttaskdetail = Task::where('id', $taskids[0])->with('location')->first();
    
            if ($driver_start_location=='current') {
                if ($agentid != 0) {
                    $singleagentdetail = Agent::where('id', $agentid)->with('agentlog')->first();
                    if(empty($singleagentdetail)){
                        $singleagentdetail = Agent::with('agentlog')->first(); 
                    }
                    if ($singleagentdetail->is_available == 1) {
                        $driver_lat = $singleagentdetail->agentlog->lat;
                        $driver_long = $singleagentdetail->agentlog->long;
                    } else {
                        $driver_lat = $firsttaskdetail->location->latitude;
                        $driver_long = $firsttaskdetail->location->longitude;
                    }
                } else {
                    $driver_lat = $firsttaskdetail->location->latitude;
                    $driver_long = $firsttaskdetail->location->longitude;
                }
            } elseif ($driver_start_location=='task_location') {
                $startingtasklocation = Task::where('id', $request->task_start_location)->with('location')->first();
                $driver_lat = $startingtasklocation->location->latitude;
                $driver_long = $startingtasklocation->location->longitude;
                if ($driver_lat==0 || $driver_long==0) {
                    $driver_lat = $firsttaskdetail->location->latitude;
                    $driver_long = $firsttaskdetail->location->longitude;
                }
            } else {
                if ($driver_latitude==0 || $driver_longitude==0) {
                    $driver_lat = $firsttaskdetail->location->latitude;
                    $driver_long = $firsttaskdetail->location->longitude;
                } else {
                    $driver_lat = $driver_latitude;
                    $driver_long = $driver_longitude;
                }
            }

            if ($driver_start_location=='select') {
               $this->getTotalDistancewithLatLong($taskids,$driver_latitude,$driver_longitude);
            }

           
    
            $output = array();
            
            echo json_encode($output);
        }
    
        public function getTotalDistance($taskids=null, $driverlocation=null)
        {
            $points = array();
            $totaldistance = 0;
            $distancearray  = [];
            $loc1 = $loc2 = $prev_latitude = $prev_longitude = 0;
           
            for ($i=0;$i<count($taskids);$i++) {
                
                $Taskdetail = Task::where('id', $taskids[$i])->with('location')->first();
               
                    if (isset($driverlocation['lat'])) {
                        $distance = $this->GoogleDistanceMatrix($driverlocation['lat'], $driverlocation['long'], $Taskdetail->location->latitude??'', $Taskdetail->location->longitude??'');
                        $totaldistance += $distance;
                        $distancearray[$Taskdetail->id] = number_format($distance/1000, 2);
                        
                    } else {
                        $distancearray[] = 0;
                    }
                    $loc1           = $Taskdetail->location_id;
                    $prev_latitude  = $Taskdetail->location->latitude??'';
                    $prev_longitude = $Taskdetail->location->longitude??'';
                // }else{
                //     $loc2 = $Taskdetail->location_id;
                //     $checkdistance = LocationDistance::where(['from_loc_id'=>$loc1,'to_loc_id'=>$loc2])->first();
                //     if (isset($checkdistance->id)) {
                //         $totaldistance += $checkdistance->distance;
                //         $distancearray[] = $checkdistance->distance;
                //     } else {
                //         $distance = $this->GoogleDistanceMatrix($prev_latitude, $prev_longitude, $Taskdetail->location->latitude ?? '', $Taskdetail->location->longitude ?? '');
                //         $totaldistance += $distance;
                //         $distancearray[] = $distance;
                //         $locdata = array('from_loc_id'=>$loc1,'to_loc_id'=>$loc2,'distance'=>$distance);
                //         LocationDistance::create($locdata);
                //     }
                //     $loc1 = $loc2;
                //     $prev_latitude  = $Taskdetail->location->latitude ?? '';
                //     $prev_longitude = $Taskdetail->location->longitude ?? '';
                // }
            }
            
            $distance_in_km = number_format($totaldistance/1000, 2);
            $distance_in_miles = number_format($totaldistance/1609.344, 2);
            $output['total_distance'] = $totaldistance;
            $output['distance'] = $distancearray;
            $output['distance'] = Arr::sort($output['distance']);

          // If you want to maintain keys, use sortBy()
           $output['distances'] = collect($output['distance'])->sortBy(function ($distance) {
           return $distance;
            })->toArray();
            $output['total_distance_km'] = $distance_in_km . __('km');
            $output['total_distance_miles'] = $distance_in_miles . __('miles');
            $j = 0;
           foreach($output['distances'] as $taskId => $distance){
              $update_task = Task::where('id',$taskId)->update(['task_order'=>$j]);
              $j++; 
           }
            return $output;
        }


        public function getTotalDistancewithLatLong($taskids=null, $driverlat=null,$driverlong=null)
        {
            
            $points = array();
            $totaldistance = 0;
            $distancearray  = [];
            $loc1 = $loc2 = $prev_latitude = $prev_longitude = 0;
          
            for ($i=0;$i<count($taskids);$i++) {
                
                $Taskdetail = Task::where('id', $taskids[$i])->with('location')->first();
                    
                    if (isset($driverlat)) {
                        $distance = $this->GoogleDistanceMatrix($driverlat, $driverlong, $Taskdetail->location->latitude??'', $Taskdetail->location->longitude??'');
                        
                        $totaldistance += $distance;
                        $distancearray[$Taskdetail->id] = number_format($distance/1000, 2);
                        
                    } else {
                        $distancearray[] = 0;
                    }
                    $loc1           = $Taskdetail->location_id;
                    $prev_latitude  = $Taskdetail->location->latitude??'';
                    $prev_longitude = $Taskdetail->location->longitude??'';
                // }else{
                //     $loc2 = $Taskdetail->location_id;
                //     $checkdistance = LocationDistance::where(['from_loc_id'=>$loc1,'to_loc_id'=>$loc2])->first();
                //     if (isset($checkdistance->id)) {
                //         $totaldistance += $checkdistance->distance;
                //         $distancearray[] = $checkdistance->distance;
                //     } else {
                //         $distance = $this->GoogleDistanceMatrix($prev_latitude, $prev_longitude, $Taskdetail->location->latitude ?? '', $Taskdetail->location->longitude ?? '');
                //         $totaldistance += $distance;
                //         $distancearray[] = $distance;
                //         $locdata = array('from_loc_id'=>$loc1,'to_loc_id'=>$loc2,'distance'=>$distance);
                //         LocationDistance::create($locdata);
                //     }
                //     $loc1 = $loc2;
                //     $prev_latitude  = $Taskdetail->location->latitude ?? '';
                //     $prev_longitude = $Taskdetail->location->longitude ?? '';
                // }
            }
            
            $distance_in_km = number_format($totaldistance/1000, 2);
            $distance_in_miles = number_format($totaldistance/1609.344, 2);
            $output['total_distance'] = $totaldistance;
            $output['distance'] = $distancearray;
            
            $output['distance'] = Arr::sort($output['distance']);
          // If you want to maintain keys, use sortBy()
           $output['distances'] = collect($output['distance'])->sortBy(function ($distance) {
           return $distance;
            })->toArray();
            $output['total_distance_km'] = $distance_in_km . __('km');
            $output['total_distance_miles'] = $distance_in_miles . __('miles');
            $j = 0;
           foreach($output['distances'] as $taskId => $distance){
              $update_task = Task::where('id',$taskId)->update(['task_order'=>$j]);
              $j++; 
           }
            return $output;
        }


      
    
        // for turn by turn funcationality
        public function ExportPdfPath(Request $request)
        {
            $taskids = explode(',', $request->taskids);
            $agentid = $request->agentid;
            $origin = [];
            $destination = [];
            $waypoints = [];
            $location = [];
            $agent_name = "";
            if ($agentid != 0) {
                $singleagentdetail = Agent::where('id', $agentid)->with('agentlog')->first();
                if ($singleagentdetail->is_available == 1) {
                    $origin['lat'] = $singleagentdetail->agentlog->lat;
                    $origin['long'] = $singleagentdetail->agentlog->long;
                }
                $agent_name = $singleagentdetail->name;
            }
    
            $totallocations = count($taskids);
            $w=0;
            $taskids = Task::whereIn('id',$taskids)->orderBy('task_order','asc')->pluck('id');
            for ($i=0;$i<$totallocations;$i++) {
                $Taskdetail = Task::where('id', $taskids[$i])->with('location')->first();
                $location[] = $Taskdetail->location->address;
                if ($i == $totallocations-1) {
                    $destination['lat'] = $Taskdetail->location->latitude;
                    $destination['long'] = $Taskdetail->location->longitude;
                } elseif (empty($origin) && $i == 0) {
                    $origin['lat'] = $Taskdetail->location->latitude;
                    $origin['long'] = $Taskdetail->location->longitude;
                } else {
                    $waypoints[$w]['lat'] = $Taskdetail->location->latitude;
                    $waypoints[$w]['long'] = $Taskdetail->location->longitude;
                    $w++;
                }
            }
    
            $routedetail = $this->GetRouteDirection($origin, $destination, $waypoints);
    
            $p['route']=$routedetail;
            $p['path'] = $location;
            $p['date'] = $request->date;
            $p['agent_name'] = $agent_name;
            // $pdf_doc = PDF::loadView('pdf',$p);
            // return $pdf_doc->download('routedetail.pdf');
            echo json_encode($p);
        }
    
        public function generatePdf(Request $request)
        {
            if (isset(Auth::user()->logo)) {
                $urlImg = Storage::disk('s3')->url(Auth::user()->logo);
            }
            $imgproxyurl = 'https://imgproxy.royodispatch.com/insecure/fit/300/100/sm/0/plain/';
            $image = $imgproxyurl.$urlImg;
    
            $result = json_decode($request->pdfdata);
            $p['route'] = $result->route;
            $p['path'] = $result->path;
            $p['date'] = $result->date;
            $p['agent_name'] = $result->agent_name;
            $p['logo'] =  $image;
            // $pdf_doc = PDF::loadView('pdf',$p);
            // return $pdf_doc->download('routedetail.pdf');
            return view('pdf', $p);
        }
    
        public function GetRouteDirection($origin, $destination, $midpoints)
        {
            $lat1 = $origin['lat'];
            $long1 = $origin['long'];
            $lat2 = $destination['lat'];
            $long2 = $destination['long'];
            $waypoint = "";
            if (!empty($midpoints)) {
                $via = [];
                for ($i=0;$i<count($midpoints);$i++) {
                    $via[]="via:".$midpoints[$i]['lat'].",".$midpoints[$i]['long'];
                }
                $waypoints = implode('|', $via);
                $waypoint = "&waypoints=".$waypoints;
            }
            $client = ClientPreference::where('id', 1)->first();
            $ch = curl_init();
            $headers = array('Accept: application/json',
                       'Content-Type: application/json',
                       );
            $url =  'https://maps.googleapis.com/maps/api/directions/json?origin='.$lat1.','.$long1.'&destination='.$lat2.','.$long2.'&key='.$client->map_key_1.$waypoint;
    
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = curl_exec($ch);
            $result = json_decode($response);
            curl_close($ch); // Close the connection
    
            $routes = $result->routes[0]->legs[0]->steps??'';
            $output = array();
            if (isset($routes)) {
                $j=0;
                if(is_array($routes)){
                    foreach ($routes as $singlestep) {
                        $output[$j]['distance'] = $singlestep->distance->text??'';
                        $output[$j]['duration'] = $singlestep->duration->text??'';
                        $output[$j]['turn'] = $singlestep->html_instructions??'';
                        $j++;
                    }
                }
    
            }
            return $output;
        }
    
        // this function is for getting all the task details with location address
        public function getTaskDetails(Request $request)
        {
            $taskids = explode(',', $request->taskids);
            $taskids = array_filter($taskids);
    
            $taskdetails = [];
            $html = "";
    
            for ($i=0;$i<count($taskids);$i++) {
                $singletaskdetail = Task::where('id', $taskids[$i])->with('location')->first();
                $singletaskdetail['current_location'] = Task::where('id', $taskids[0])->whereHas('order.agent.agentlog')->count();
                $taskdetails[] = $singletaskdetail->toArray();
            }
            echo json_encode($taskdetails);
        }
    
        // This function is for sending silent push notification
        public function sendsilentnotification($notification_data)
        {
            $new = [];
            array_push($new, $notification_data['device_token']);
            if (isset($new)) {
                fcm()
                ->to($new) // $recipients must an array
                ->data($notification_data)
                ->notification([
                    'sound' =>  'default',
                ])
                ->send();
            }
        }
    
        //function to load latest order/route and agent data with or without html
        public function dashboardTeamData(Request $request)
        {
            $userstatus = isset($request->userstatus)?$request->userstatus:2;
            $is_load_html = isset($request->is_load_html)?$request->is_load_html:1;
            $auth = Client::where('code', Auth::user()->code)->with(['getAllocation', 'getPreference'])->first();
    
            //setting timezone from id
            $tz = new Timezone();
            $auth->timezone = $tz->timezone_name(Auth::user()->timezone);
    
            if(isset($request->routedate)) {
                $date = Carbon::parse(strtotime($request->routedate))->format('Y-m-d');
            }else{
                $date = date('Y-m-d');
            }
            $startdate = date("Y-m-d 00:00:00", strtotime($date));
            $enddate = date("Y-m-d 23:59:59", strtotime($date));
    
    
            $startdate = Carbon::parse($startdate . @$auth->timezone ?? 'UTC')->tz('UTC');
            $enddate = Carbon::parse($enddate . @$auth->timezone ?? 'UTC')->tz('UTC');
    
            //left side bar list for display all teams
            if($userstatus!=2):
                $teams  = Team::with(
                    [ 
                        'agents' => function ($query) use ($userstatus, $startdate, $enddate) {
                            $query->where('is_available', '=', $userstatus)
                                ->with(['agentlog', 
                                    'order'  => function ($q) use ($startdate, $enddate){
                                    $q->where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status','!=','completed')->with(['customer', 'task.location']);
                                    }
                                ]
                            );
                        },
                    ]
                );
            else:
                $teams  = Team::with(
                    [
                        'agents.order' => function ($o) use ($startdate, $enddate) {
                        $o->where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status','!=','completed')->with(['customer', 'task.location']);
                        },
                    ]
                );
            endif;
            
            if (Auth::user()->is_superadmin == 0 && Auth::user()->all_team_access == 0) {
                $teams = $teams->whereHas('permissionToManager', function ($query) {
                    $query->where('sub_admin_id', Auth::user()->id);
                });
            }
            $teams = $teams->get();
    
            foreach ($teams as $team) {
                $online  = 0;
                $offline = 0;
                $count   = 0;
                foreach ($team->agents as $agent) {
                    $agent_task_count = 0;
                    foreach ($agent->order as $tasks) {
                        $agent_task_count = $agent_task_count + count($tasks->task);
                    }
                    if ($agent->is_available == 1) {
                        $online++;
                    } else {
                        $offline++;
                    }
                    $count++;
                    $agent['free'] = count($agent->order) > 0 ? 'Busy' : 'Free';
                    $agent['agent_task_count'] = $agent_task_count;
                }
    
                $team['online_agents']  = $online;
                $team['offline_agents'] = $offline;
                $agent['agent_count']   = $count;
            }
    
            //left side bar list for display unassigned team
            $unassigned = Agent::where('team_id', null)->with(['order' => function ($o) use ($startdate, $enddate) {
                $o->where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->with('customer')->with('task.location');
            }])->get();
    
            $online  = 0;
            $offline = 0;
            $count   = 0;
    
            foreach ($unassigned as $agent) {
                $agent_task_count = 0;
                foreach ($agent->order as $tasks) {
                    $agent_task_count = $agent_task_count + count($tasks->task);
                }
    
                if ($agent->is_available == 1) {
                    $online++;
                } else {
                    $offline++;
                }
                $count++;
    
                $agent['free'] = count($agent->order) > 0 ? 'Busy' : 'Free';
                $agent['online_agents']    = $online;
                $agent['offline_agents']   = $offline;
                $agent['agent_count']      = $count;
                $agent['agent_task_count'] = $agent_task_count;
            }
    
            //create array for map marker
            $allTasks = Order::where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status','!=','completed')->with(['customer', 'task.location', 'agent.team'])->get();
            $newmarker = [];
    
            foreach ($allTasks as $key => $tasks) {
                $append = [];
                foreach ($tasks->task as $task) {
                    if ($task->task_type_id == 1) {
                        $name = 'Pickup';
                    } elseif ($task->task_type_id == 2) {
                        $name = 'DropOff';
                    } else {
                        $name = 'Appointment';
                    }
                    $append['task_type']             = $name;
                    $append['task_id']               = $task->id;
                    $append['latitude']              = isset($task->location->latitude) ? floatval($task->location->latitude):0.00;
                    $append['longitude']             = isset($task->location->longitude) ? floatval($task->location->longitude): 0.00;
                    $append['address']               = isset($task->location->address) ? $task->location->address : '';
                    $append['task_type_id']          = isset($task->task_type_id) ? $task->task_type_id : '';
                    $append['task_status']           = (int)$task->task_status;
                    $append['team_id']               = isset($tasks->driver_id) ? @$tasks->agent->team_id : 0;
                    $append['driver_name']           = isset($tasks->driver_id) ? @$tasks->agent->name : '';
                    $append['driver_id']             = isset($tasks->driver_id) ? $tasks->driver_id : '';
                    $append['customer_name']         = isset($tasks->customer->name)?$tasks->customer->name:'';
                    $append['customer_phone_number'] = isset($tasks->customer->phone_number)?$tasks->customer->phone_number:'';
                    $append['task_order']            = isset($task->task_order)?$task->task_order:0;
                    array_push($newmarker, $append);
                }
            }
    
            $unassigned->toArray();
            $teams->toArray();
    
            $agents = Agent::with('agentlog','getDriver');
            if($userstatus!=2):
                $agents->where('is_available', $userstatus);
            endif;
            $agents = $agents->get()->toArray();
            // \Log::info($agents);
            $preference  = ClientPreference::where('id', 1)->first(['theme','date_format','time_format']);
    
            $uniquedrivers = array();
            $j = 0;
            foreach ($agents as $singleagent) {
                if(empty($singleagent['agentlog'])){
                    $singleagent['agentlog']['id'] = null;
                    $singleagent['agentlog']['agent_id'] = $singleagent['id'];
                    $singleagent['agentlog']['current_task_id'] = null;
                    $singleagent['agentlog']['lat'] = null;
                    $singleagent['agentlog']['long'] = null;
                    $singleagent['agentlog']['battery_level'] = null;
                    $singleagent['agentlog']['os_version'] = null;
                    $singleagent['agentlog']['app_version'] = null;
                    $singleagent['agentlog']['current_speed'] = null;
                    $singleagent['agentlog']['on_route '] = null;
                    $singleagent['agentlog']['app_version'] = null;
                }
                if (is_array($singleagent['agentlog'])) {
                    $taskarray = array();
                    foreach ($newmarker as $singlemark) {
                        if ($singlemark['driver_id'] == $singleagent['agentlog']['agent_id']) {
                            $taskarray[] = $singlemark;
                        }
                    }
                    if (!empty($taskarray)) {
                        usort($taskarray, function ($a, $b) {
                            return $a['task_order'] <=> $b['task_order'];
                        });
                        if ($date != date('Y-m-d')) {
                            $singleagent['agentlog']['lat'] = $taskarray[0]['latitude'];
                            $singleagent['agentlog']['long'] = $taskarray[0]['longitude'];
                        }
                        $uniquedrivers[$j]['driver_detail'] = $singleagent['agentlog'];
                        $uniquedrivers[$j]['task_details'] = $taskarray;
                        $j++;
                    }
                }else{
    
                }
            }
    
            //for route optimization
            $routeoptimization = array();
            $taskarray = array();
            foreach ($uniquedrivers as $singledriver) {
                if (count($singledriver['task_details'])>1) {
                    $points = array();
                    $points[] = array(floatval($singledriver['driver_detail']['lat']),floatval($singledriver['driver_detail']['long']));
                    $taskids = array();
                    foreach ($singledriver['task_details'] as $singletask) {
                        $points[] = array(floatval($singletask['latitude']),floatval($singletask['longitude']));
                        $taskids[] = $singletask['task_id'];
                    }
    
                    $taskarray[$singledriver['driver_detail']['agent_id']] = implode(',', $taskids);
                    $routeoptimization[$singledriver['driver_detail']['agent_id']] = $points;
                }
            }
    
            //create distance matrix
            $distancematrix = array();
            foreach ($routeoptimization as $key=>$value) {
                $distancematrix[$key]['tasks'] = $taskarray[$key];
                $distancematrix[$key]['distance'] = $routeoptimization[$key];
            }
    
            $teamdata = $teams->toArray();
    
            foreach ($teamdata as $k1=>$singleteam) {
                foreach ($singleteam['agents'] as $k2=>$singleagent) {
                    $teamdata[$k1]['agents'][$k2]['taskids']  = [];
                    $teamdata[$k1]['agents'][$k2]['total_distance']  = '';
                    if (count($singleagent['order'])>0) {
                        //for calculating total distance
                        $sorted_orders = $this->splitOrder($singleagent['order']);
                        if (!empty($sorted_orders)) {
                            $tasklistids = [];
                            foreach ($sorted_orders as $singlesort) {
                                $tasklistids[] = $singlesort['task'][0]['id'];
                            }
                            $teamdata[$k1]['agents'][$k2]['taskids'] = $tasklistids;
                            $driverlocation = [];
                            if ($singleagent['is_available']==1 || $singleagent['is_available']==0) {
                                $singleagentdetail = Agent::where('id', $singleagent['id'])->with('agentlog')->first();
                                $driverlocation['lat'] = $singleagentdetail->agentlog->lat??$singleagentdetail->order[0]['task'][0]['location']['latitude']??'0.000';
                                $driverlocation['long'] = $singleagentdetail->agentlog->long??$singleagentdetail->order[0]['task'][0]['location']['longitude']??'0.000';
                            }
                            $gettotal_distance = $this->getTotalDistance($tasklistids, $driverlocation);
                            $clientPreference  = ClientPreference::where('id', 1)->first();
                            $teamdata[$k1]['agents'][$k2]['total_distance'] = ($clientPreference->distance_unit == 'metric')? $gettotal_distance['total_distance_km'] : $gettotal_distance['total_distance_miles'];
                        }
                        $teamdata[$k1]['agents'][$k2]['order'] = $sorted_orders;
                    }
                }
            }
    
            //unassigned_orders
            $unassigned_orders = array();
            $un_total_distance = '';
            $un_order  = Order::where('order_time', '>=', $startdate)->where('order_time', '<=', $enddate)->where('status', 'unassigned')->with(['customer', 'task.location'])->get();
    
            if (count($un_order)>=1) {
                $unassigned_orders = $this->splitOrder($un_order->toarray());
                if (count($unassigned_orders)>1) {
                    $unassigned_distance_mat = array();
                    $unassigned_points = [];
                    if(!empty($unassigned_orders[0]['task'][0]['location'])){
                        $unassigned_points[] = array(floatval($unassigned_orders[0]['task'][0]['location']['latitude']),floatval($unassigned_orders[0]['task'][0]['location']['longitude']));
                    }
                    $unassigned_taskids = array();
                    $un_route = array();
                    foreach ($unassigned_orders as $singleua) {
                        $unassigned_taskids[] = $singleua['task'][0]['id'];
                        if(!empty($singleua['task'][0]['location'])){
                            // dd($singleua['task'][0]['location']['latitude']);
                            $unassigned_points[] = array(floatval($singleua['task'][0]['location']['latitude']),floatval($singleua['task'][0]['location']['longitude']));
                        }
    
                        //for drawing route
                        $s_task = $singleua['task'][0];
                        if ($s_task['task_type_id'] == 1) {
                            $nname = 'Pickup';
                        } elseif ($s_task['task_type_id'] == 2) {
                            $nname = 'DropOff';
                        } else {
                            $nname = 'Appointment';
                        }
                        $aappend = array();
                        $aappend['task_type']             = $nname;
                        $aappend['task_id']               =  $s_task['id'];
                        $aappend['latitude']              =  $s_task['location']['latitude'] ?? '';
                        $aappend['longitude']             = $s_task['location']['longitude'] ?? '';
                        $aappend['address']               = $s_task['location']['address'] ?? '';
                        $aappend['task_type_id']          = $s_task['task_type_id'];
                        $aappend['task_status']           = $s_task['task_status'];
                        $aappend['team_id']               = 0;
                        $aappend['driver_name']           = '';
                        $aappend['driver_id']             = 0;
                        $aappend['customer_name']         = $singleua['customer']['name'];
                        $aappend['customer_phone_number'] = $singleua['customer']['phone_number'];
                        $aappend['task_order']            = $singleua['task_order'];
                        $un_route[] = $aappend;
                    }
                    $unassigned_distance_mat['tasks'] = implode(',', $unassigned_taskids);
                    $unassigned_distance_mat['distance'] = $unassigned_points;
                    $distancematrix[0] = $unassigned_distance_mat;
                    $first_un_loc = [];
                    if(!empty($unassigned_orders[0]['task'][0]['location'])){
                        $first_un_loc = array('lat'=>floatval($unassigned_orders[0]['task'][0]['location']['latitude']),'long'=>floatval($unassigned_orders[0]['task'][0]['location']['longitude']));
                    }
                    $final_un_route['driver_detail'] = $first_un_loc;
                    $final_un_route['task_details'] = $un_route;
                    $uniquedrivers[] = $final_un_route;
    
                    $gettotal_un_distance = $this->getTotalDistance($unassigned_taskids);
    
                    $un_total_distance = $gettotal_un_distance['total_distance_miles'];
                }
            }
    
            $client = ClientPreference::where('id', 1)->first();
    
            $googleapikey = $client->map_key_1??'';
    
            $getAdminCurrentCountry = Countries::where('id', '=', Auth::user()->country_id)->get()->first();
            if(!empty($getAdminCurrentCountry)){
                $defaultCountryLatitude  = $getAdminCurrentCountry->latitude;
                $defaultCountryLongitude  = $getAdminCurrentCountry->longitude;
            }else{
                $defaultCountryLatitude  = '';
                $defaultCountryLongitude  = '';
            }
            
            $data = array('status' =>"success", 'teams' => $teamdata, 'userstatus' => $userstatus, 'client_code' => Auth::user()->code, 'defaultCountryLongitude' => $defaultCountryLongitude, 'defaultCountryLatitude' => $defaultCountryLatitude, 'newmarker' => $newmarker, 'unassigned' => $unassigned, 'agents' => $agents,'date'=> $date,'preference' =>$preference, 'routedata' => $uniquedrivers,'distance_matrix' => $distancematrix, 'unassigned_orders' => $unassigned_orders,'unassigned_distance' => $un_total_distance, 'map_key'=>$googleapikey, 'client_timezone'=>$auth->timezone);
            if($is_load_html == 1)
            {
                return view('dashboard_task_html')->with($data)->render();
            }else{
                return json_encode($data);
            }
        }
    
        public function api_documentation(){
         
            return File::get(public_path() . '/assets/api_documentation/index.html');
        }


        public function splitOrderv2($orders)
        {
            $new_order = [];
            if (is_array($orders) && count($orders)>0 && !empty($orders)) {
                $counter = 0;
                
                
                foreach ($orders as $order) {
    
                    
                    if(isset($order['task'] )){
                    foreach ($order['task'] as $task) {
                        $new_order[] = $order;
                        $new_order[$counter]['task_order'] = $task['task_order'];
                        unset($new_order[$counter]['task']);
                        $new_order[$counter]['task'][] = $task;
                        $counter++;
                    }
                 }
                }
    
                //sort array
                usort($new_order, function ($a, $b) {
                    return $a['task_order'] <=> $b['task_order'];
                });
                return $new_order;
            } else {
                return $orders;
            }
        }



}


